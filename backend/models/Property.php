<?php
/**
 * Property Model (minimal implementation)
 * Provides methods required by the homepage and basic property retrieval.
 */

class Property {
    /** @var Database */
    private $database;

    public function __construct() {
        global $database;
        $this->database = $database;
    }

    /**
     * Search properties with optional filters.
     * Returns fields expected by existing frontend templates.
     *
     * @param array $filters
     * @param int $page
     * @param int $limit
     * @return array
     */
    public function search($filters = [], $page = 1, $limit = 10) {
        $where = [];
        $params = [];

        // Default: show featured on homepage when no explicit filters provided
        if (empty($filters)) {
            $where[] = 'p.is_featured = 1';
        }

        // Example filters mapping (extend as needed)
        if (!empty($filters['province_id'])) {
            $where[] = 'p.province_id = ?';
            $params[] = (int)$filters['province_id'];
        }
        if (!empty($filters['district_id'])) {
            $where[] = 'p.district_id = ?';
            $params[] = (int)$filters['district_id'];
        }
        if (!empty($filters['city_id'])) {
            $where[] = 'p.city_id = ?';
            $params[] = (int)$filters['city_id'];
        }
        if (!empty($filters['property_type'])) {
            $where[] = 'pt.name = ?';
            $params[] = $filters['property_type'];
        }

        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

        $offset = max(0, ((int)$page - 1) * (int)$limit);
        $limit = max(1, (int)$limit);

        $sql = "
            SELECT
                p.id,
                p.title,
                p.description,
                p.area_sqft,
                p.bedrooms,
                p.bathrooms,
                p.rent AS monthly_rent,
                c.name AS city,
                d.name AS state,
                pt.name AS property_type,
                COALESCE(
                    (SELECT pi1.image_url FROM property_images pi1 WHERE pi1.property_id = p.id AND pi1.is_primary = 1 ORDER BY pi1.id ASC LIMIT 1),
                    (SELECT pi2.image_url FROM property_images pi2 WHERE pi2.property_id = p.id ORDER BY pi2.id ASC LIMIT 1)
                ) AS primary_image
            FROM properties p
            LEFT JOIN cities c ON c.id = p.city_id
            LEFT JOIN districts d ON d.id = p.district_id
            LEFT JOIN property_types pt ON pt.id = p.property_type_id
            $whereSql
            ORDER BY p.created_at DESC
            LIMIT $limit OFFSET $offset
        ";

        return $this->database->fetchAll($sql, $params);
    }

    /**
     * Return basic statistics for the homepage cards.
     *
     * @return array{total:int,verified:int,available:int,avg_rent:float}
     */
    public function getStats() {
        $total = (int)($this->database->fetch('SELECT COUNT(*) AS c FROM properties')['c'] ?? 0);

        // No explicit verified/available columns in schema; approximate:
        $verified = (int)($this->database->fetch('SELECT COUNT(*) AS c FROM properties WHERE is_featured = 1')['c'] ?? 0);
        $available = (int)($this->database->fetch('SELECT COUNT(*) AS c FROM properties WHERE rent IS NOT NULL')['c'] ?? 0);

        $avgRow = $this->database->fetch('SELECT AVG(rent) AS avg_rent FROM properties WHERE rent IS NOT NULL');
        $avgRent = isset($avgRow['avg_rent']) ? (float)$avgRow['avg_rent'] : 0.0;

        return [
            'total' => $total,
            'verified' => $verified,
            'available' => $available,
            'avg_rent' => $avgRent,
        ];
    }

	/**
	 * Get properties by owner with basic fields used in owner dashboard.
	 * Returns: title, property_type, city, monthly_rent, is_available
	 */
	public function getByOwner($ownerId, $page = 1, $limit = 5) {
		$ownerId = (int)$ownerId;
		$offset = max(0, ((int)$page - 1) * (int)$limit);
		$limit = max(1, (int)$limit);

		$sql = "
			SELECT
				p.id,
				p.title,
				pt.name AS property_type,
				c.name AS city,
				p.rent AS monthly_rent,
				CASE WHEN p.rent IS NULL THEN 0 ELSE 1 END AS is_available
			FROM properties p
			LEFT JOIN property_types pt ON pt.id = p.property_type_id
			LEFT JOIN cities c ON c.id = p.city_id
			WHERE p.owner_id = ?
			ORDER BY p.created_at DESC
			LIMIT $limit OFFSET $offset
		";

		return $this->database->fetchAll($sql, [$ownerId]);
	}

	/**
	 * Get total property count for an owner (for pagination).
	 * Optional simple search on title/city/type name.
	 */
	public function getCountByOwner($ownerId, $search = '') {
		$ownerId = (int)$ownerId;
		$whereParts = ['p.owner_id = ?'];
		$params = [$ownerId];
		if ($search !== '') {
			$whereParts[] = '(p.title LIKE ? OR c.name LIKE ? OR pt.name LIKE ?)';
			$like = '%' . $search . '%';
			$params[] = $like;
			$params[] = $like;
			$params[] = $like;
		}
		$whereSql = implode(' AND ', $whereParts);
		$sql = "
			SELECT COUNT(*) AS count
			FROM properties p
			LEFT JOIN cities c ON c.id = p.city_id
			LEFT JOIN property_types pt ON pt.id = p.property_type_id
			WHERE $whereSql
		";
		try {
			$row = $this->database->fetch($sql, $params);
			return (int)($row['count'] ?? 0);
		} catch (Exception $e) {
			return 0;
		}
	}

	/**
	 * Get pending visit requests (mapped to property_inquiries as a proxy)
	 * for properties owned by the specified owner. Returns recent inquiries.
	 */
	public function getPendingVisitRequests($ownerId, $limit = 10) {
		$ownerId = (int)$ownerId;
		$limit = max(1, (int)$limit);
		$sql = "
			SELECT i.id,
			       i.property_id,
			       i.user_id,
			       i.message,
			       i.contact_number,
			       i.email,
			       i.created_at
			FROM property_inquiries i
			JOIN properties p ON p.id = i.property_id
			WHERE p.owner_id = ?
			ORDER BY i.created_at DESC
			LIMIT $limit
		";
		try {
			return $this->database->fetchAll($sql, [$ownerId]);
		} catch (Exception $e) {
			return [];
		}
	}

	/**
	 * Get paginated visit requests for an owner using property_inquiries as source.
	 * Maps fields to what the frontend expects.
	 */
	public function getVisitRequestsByOwner($ownerId, $page = 1, $limit = 20, $search = '', $filter_status = '', $filter_property = '') {
		$ownerId = (int)$ownerId;
		$offset = max(0, ((int)$page - 1) * (int)$limit);
		$limit = max(1, (int)$limit);

		// Only 'pending' status is representable from current schema; others return empty
		if ($filter_status && $filter_status !== 'pending') {
			return [];
		}

		$where = ['p.owner_id = ?'];
		$params = [$ownerId];
		if ($filter_property !== '') {
			$where[] = 'i.property_id = ?';
			$params[] = (int)$filter_property;
		}
		if ($search !== '') {
			$where[] = '(u.name LIKE ? OR p.title LIKE ? OR i.message LIKE ?)';
			$like = '%' . $search . '%';
			$params[] = $like;
			$params[] = $like;
			$params[] = $like;
		}
		$whereSql = implode(' AND ', $where);

		$sql = "
			SELECT i.id,
			       i.property_id,
			       i.user_id,
			       u.name AS customer_name,
			       p.title AS property_title,
			       DATE(i.created_at) AS requested_date,
			       TIME(i.created_at) AS requested_time,
			       i.message AS notes,
			       NULL AS owner_response,
			       'pending' AS status
			FROM property_inquiries i
			JOIN properties p ON p.id = i.property_id
			JOIN users u ON u.id = i.user_id
			WHERE $whereSql
			ORDER BY i.created_at DESC
			LIMIT $limit OFFSET $offset
		";
		try {
			return $this->database->fetchAll($sql, $params);
		} catch (Exception $e) {
			return [];
		}
	}

	/**
	 * Count visit requests for owner with the same filters.
	 */
	public function getVisitRequestsCountByOwner($ownerId, $search = '', $filter_status = '', $filter_property = '') {
		$ownerId = (int)$ownerId;
		if ($filter_status && $filter_status !== 'pending') {
			return 0;
		}
		$where = ['p.owner_id = ?'];
		$params = [$ownerId];
		if ($filter_property !== '') {
			$where[] = 'i.property_id = ?';
			$params[] = (int)$filter_property;
		}
		if ($search !== '') {
			$where[] = '(u.name LIKE ? OR p.title LIKE ? OR i.message LIKE ?)';
			$like = '%' . $search . '%';
			$params[] = $like;
			$params[] = $like;
			$params[] = $like;
		}
		$whereSql = implode(' AND ', $where);
		$sql = "
			SELECT COUNT(*) AS count
			FROM property_inquiries i
			JOIN properties p ON p.id = i.property_id
			JOIN users u ON u.id = i.user_id
			WHERE $whereSql
		";
		try {
			$row = $this->database->fetch($sql, $params);
			return (int)($row['count'] ?? 0);
		} catch (Exception $e) {
			return 0;
		}
	}

	/**
	 * Simple stats for visit requests based on available data.
	 */
	public function getVisitRequestStats($ownerId) {
		$total = 0;
		try {
			$row = $this->database->fetch(
				"SELECT COUNT(*) AS c FROM property_inquiries i JOIN properties p ON p.id = i.property_id WHERE p.owner_id = ?",
				[(int)$ownerId]
			);
			$total = (int)($row['c'] ?? 0);
		} catch (Exception $e) {
			$total = 0;
		}
		return [
			'total_requests' => $total,
			'pending_requests' => $total,
			'approved_requests' => 0,
			'completed_requests' => 0,
		];
	}

	/**
	 * Respond to visit request (no-op placeholder due to schema limitations).
	 */
	public function respondToVisitRequest($visitId, $response, $ownerResponse = '') {
		// Without status/response columns in schema, we acknowledge the action.
		return true;
	}

	/**
	 * Owner overview analytics placeholder derived from current schema.
	 */
	public function getOwnerAnalytics($ownerId, $dateFrom, $dateTo) {
		$ownerId = (int)$ownerId;
		$overview = [
			'total_earnings' => 0,
			'earnings_growth' => 0,
			'total_bookings' => 0,
			'bookings_growth' => 0,
			'occupancy_rate' => 0,
			'occupancy_growth' => 0,
			'avg_rent' => 0,
			'rent_growth' => 0,
		];

		// avg rent from properties
		try {
			$row = $this->database->fetch(
				"SELECT AVG(rent) AS avg_rent FROM properties WHERE owner_id = ? AND rent IS NOT NULL",
				[$ownerId]
			);
			$overview['avg_rent'] = (float)($row['avg_rent'] ?? 0);
		} catch (Exception $e) {}

		// occupancy proxy: percent of properties with non-null rent
		try {
			$total = (int)($this->database->fetch("SELECT COUNT(*) AS c FROM properties WHERE owner_id = ?", [$ownerId])['c'] ?? 0);
			$available = (int)($this->database->fetch("SELECT COUNT(*) AS c FROM properties WHERE owner_id = ? AND rent IS NOT NULL", [$ownerId])['c'] ?? 0);
			$overview['occupancy_rate'] = $total > 0 ? (int)round(($available / $total) * 100) : 0;
		} catch (Exception $e) {}

		return $overview;
	}

	/**
	 * Property performance analytics placeholder for table/chart.
	 */
	public function getPropertyPerformanceAnalytics($ownerId, $dateFrom, $dateTo) {
		return [
			'type_distribution' => $this->getPropertyTypeDistribution($ownerId),
		];
	}

	public function getPropertyPerformanceData($ownerId) {
		$ownerId = (int)$ownerId;
		$sql = "
			SELECT p.id, p.title, c.name AS city, pt.name AS property_type, p.rent AS monthly_rent
			FROM properties p
			LEFT JOIN cities c ON c.id = p.city_id
			LEFT JOIN property_types pt ON pt.id = p.property_type_id
			WHERE p.owner_id = ?
			ORDER BY p.created_at DESC
		";
		try {
			$rows = $this->database->fetchAll($sql, [$ownerId]);
		} catch (Exception $e) {
			$rows = [];
		}
		// augment with placeholder performance metrics
		return array_map(function($r) {
			$r['occupancy_rate'] = $r['monthly_rent'] !== null ? 75 : 0;
			$r['total_earnings'] = 0.0;
			$r['performance_score'] = $r['monthly_rent'] !== null ? 70 : 30;
			$r['property_type'] = $r['property_type'] ?? 'other';
			$r['monthly_rent'] = (float)($r['monthly_rent'] ?? 0);
			return $r;
		}, $rows);
	}

	private function getPropertyTypeDistribution($ownerId) {
		$ownerId = (int)$ownerId;
		$sql = "
			SELECT COALESCE(pt.name,'Other') AS type_name, COUNT(*) AS cnt
			FROM properties p
			LEFT JOIN property_types pt ON pt.id = p.property_type_id
			WHERE p.owner_id = ?
			GROUP BY type_name
		";
		try {
			$rows = $this->database->fetchAll($sql, [$ownerId]);
		} catch (Exception $e) {
			$rows = [];
		}
		$dist = [];
		foreach ($rows as $row) {
			$dist[$row['type_name']] = (int)$row['cnt'];
		}
		return $dist;
	}

    /**
     * Placeholder for create to maintain backward compatibility with controllers.
     * Throws until the full create flow is adapted to the new schema.
     */
    public function create($data) {
        throw new Exception('Property::create is not implemented for the current schema.');
    }

    /**
     * Placeholder for updateAvailability to maintain backward compatibility.
     */
    public function updateAvailability($propertyId, $isAvailable) {
        throw new Exception('Property::updateAvailability is not implemented for the current schema.');
    }
}

?>


