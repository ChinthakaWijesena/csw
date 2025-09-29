<?php
/**
 * Property Model
 * Handles property-related database operations
 */

require_once __DIR__ . '/../../config/config.php';

class Property {
    private $db;
    
    public function __construct() {
        global $database;
        $this->db = $database;
    }
    
    /**
     * Create a new property
     */
    public function create($data) {
        $sql = "INSERT INTO properties (
                    owner_id, title, description, property_type, bedrooms, bathrooms, 
                    area_sqft, monthly_rent, security_deposit, address, city, state, 
                    zip_code, latitude, longitude, is_available, is_verified, is_approved
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $params = [
            $data['owner_id'],
            $data['title'],
            $data['description'],
            $data['property_type'],
            $data['bedrooms'] ?? null,
            $data['bathrooms'] ?? null,
            $data['area_sqft'] ?? null,
            $data['monthly_rent'],
            $data['security_deposit'] ?? null,
            $data['address'],
            $data['city'],
            $data['state'],
            $data['zip_code'],
            $data['latitude'] ?? null,
            $data['longitude'] ?? null,
            $data['is_available'] ?? 1,
            $data['is_verified'] ?? 0,
            $data['is_approved'] ?? 0
        ];
        
        $this->db->query($sql, $params);
        return $this->db->lastInsertId();
    }
    
    /**
     * Get property by ID
     */
    public function getById($id) {
        $sql = "SELECT p.*, u.name as owner_name, u.phone as owner_phone 
                FROM properties p 
                JOIN users u ON p.owner_id = u.id 
                WHERE p.id = ?";
        return $this->db->fetch($sql, [$id]);
    }
    
    /**
     * Get properties by owner
     */
    public function getByOwner($owner_id, $page = 1, $limit = 20) {
        $offset = ($page - 1) * $limit;
        $sql = "SELECT * FROM properties WHERE owner_id = ? ORDER BY created_at DESC LIMIT ? OFFSET ?";
        return $this->db->fetchAll($sql, [$owner_id, $limit, $offset]);
    }
    
    /**
     * Search properties with filters
     */
    public function search($filters = [], $page = 1, $limit = 20) {
        $offset = ($page - 1) * $limit;
        $where_conditions = ["p.is_available = 1", "p.is_verified = 1", "p.is_approved = 1"];
        $params = [];
        
        // Location filter - handle both ID-based and text-based searches
        if (!empty($filters['province'])) {
            // Check if it's an ID (numeric) or text
            if (is_numeric($filters['province'])) {
                // Get province name by ID and search in state field
                global $database;
                $province_sql = "SELECT name FROM provinces WHERE id = ?";
                $province_result = $database->fetch($province_sql, [$filters['province']]);
                if ($province_result) {
                    $where_conditions[] = "p.state LIKE ?";
                    $params[] = "%{$province_result['name']}%";
                }
            } else {
                // Direct text search in state field
                $where_conditions[] = "p.state LIKE ?";
                $params[] = "%{$filters['province']}%";
            }
        }
        
        if (!empty($filters['district'])) {
            // Check if it's an ID (numeric) or text
            if (is_numeric($filters['district'])) {
                // Get district name by ID and search in city field
                global $database;
                $district_sql = "SELECT name FROM districts WHERE id = ?";
                $district_result = $database->fetch($district_sql, [$filters['district']]);
                if ($district_result) {
                    $where_conditions[] = "p.city LIKE ?";
                    $params[] = "%{$district_result['name']}%";
                }
            } else {
                // Direct text search in city field
                $where_conditions[] = "p.city LIKE ?";
                $params[] = "%{$filters['district']}%";
            }
        }
        
        if (!empty($filters['city'])) {
            // Check if it's an ID (numeric) or text
            if (is_numeric($filters['city'])) {
                // Get city name by ID and search in city field
                global $database;
                $city_sql = "SELECT name FROM cities WHERE id = ?";
                $city_result = $database->fetch($city_sql, [$filters['city']]);
                if ($city_result) {
                    $where_conditions[] = "p.city LIKE ?";
                    $params[] = "%{$city_result['name']}%";
                }
            } else {
                // Direct text search in city field
                $where_conditions[] = "p.city LIKE ?";
                $params[] = "%{$filters['city']}%";
            }
        }
        
        if (!empty($filters['state'])) {
            $where_conditions[] = "p.state LIKE ?";
            $params[] = "%{$filters['state']}%";
        }
        
        // Price range filter
        if (!empty($filters['min_price'])) {
            $where_conditions[] = "p.monthly_rent >= ?";
            $params[] = $filters['min_price'];
        }
        
        if (!empty($filters['max_price'])) {
            $where_conditions[] = "p.monthly_rent <= ?";
            $params[] = $filters['max_price'];
        }
        
        // Property type filter
        if (!empty($filters['property_type'])) {
            $where_conditions[] = "p.property_type = ?";
            $params[] = $filters['property_type'];
        }
        
        // Bedrooms filter
        if (!empty($filters['bedrooms'])) {
            $where_conditions[] = "p.bedrooms >= ?";
            $params[] = $filters['bedrooms'];
        }
        
        // Bathrooms filter
        if (!empty($filters['bathrooms'])) {
            $where_conditions[] = "p.bathrooms >= ?";
            $params[] = $filters['bathrooms'];
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        
        $sql = "SELECT p.*, u.name as owner_name, u.phone as owner_phone,
                       pi.image_path as primary_image
                FROM properties p 
                JOIN users u ON p.owner_id = u.id 
                LEFT JOIN property_images pi ON p.id = pi.property_id AND pi.is_primary = 1
                WHERE {$where_clause} 
                ORDER BY p.created_at DESC 
                LIMIT ? OFFSET ?";
        
        $params[] = $limit;
        $params[] = $offset;
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * Get property count for search
     */
    public function getSearchCount($filters = []) {
        $where_conditions = ["p.is_available = 1", "p.is_verified = 1", "p.is_approved = 1"];
        $params = [];
        
        // Apply same filters as search method
        if (!empty($filters['province'])) {
            // Check if it's an ID (numeric) or text
            if (is_numeric($filters['province'])) {
                // Get province name by ID and search in state field
                global $database;
                $province_sql = "SELECT name FROM provinces WHERE id = ?";
                $province_result = $database->fetch($province_sql, [$filters['province']]);
                if ($province_result) {
                    $where_conditions[] = "p.state LIKE ?";
                    $params[] = "%{$province_result['name']}%";
                }
            } else {
                // Direct text search in state field
                $where_conditions[] = "p.state LIKE ?";
                $params[] = "%{$filters['province']}%";
            }
        }
        
        if (!empty($filters['district'])) {
            // Check if it's an ID (numeric) or text
            if (is_numeric($filters['district'])) {
                // Get district name by ID and search in city field
                global $database;
                $district_sql = "SELECT name FROM districts WHERE id = ?";
                $district_result = $database->fetch($district_sql, [$filters['district']]);
                if ($district_result) {
                    $where_conditions[] = "p.city LIKE ?";
                    $params[] = "%{$district_result['name']}%";
                }
            } else {
                // Direct text search in city field
                $where_conditions[] = "p.city LIKE ?";
                $params[] = "%{$filters['district']}%";
            }
        }
        
        if (!empty($filters['city'])) {
            // Check if it's an ID (numeric) or text
            if (is_numeric($filters['city'])) {
                // Get city name by ID and search in city field
                global $database;
                $city_sql = "SELECT name FROM cities WHERE id = ?";
                $city_result = $database->fetch($city_sql, [$filters['city']]);
                if ($city_result) {
                    $where_conditions[] = "p.city LIKE ?";
                    $params[] = "%{$city_result['name']}%";
                }
            } else {
                // Direct text search in city field
                $where_conditions[] = "p.city LIKE ?";
                $params[] = "%{$filters['city']}%";
            }
        }
        
        if (!empty($filters['state'])) {
            $where_conditions[] = "p.state LIKE ?";
            $params[] = "%{$filters['state']}%";
        }
        
        if (!empty($filters['min_price'])) {
            $where_conditions[] = "p.monthly_rent >= ?";
            $params[] = $filters['min_price'];
        }
        
        if (!empty($filters['max_price'])) {
            $where_conditions[] = "p.monthly_rent <= ?";
            $params[] = $filters['max_price'];
        }
        
        if (!empty($filters['property_type'])) {
            $where_conditions[] = "p.property_type = ?";
            $params[] = $filters['property_type'];
        }
        
        if (!empty($filters['bedrooms'])) {
            $where_conditions[] = "p.bedrooms >= ?";
            $params[] = $filters['bedrooms'];
        }
        
        if (!empty($filters['bathrooms'])) {
            $where_conditions[] = "p.bathrooms >= ?";
            $params[] = $filters['bathrooms'];
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        $sql = "SELECT COUNT(*) as count FROM properties p WHERE {$where_clause}";
        
        $result = $this->db->fetch($sql, $params);
        return $result['count'];
    }
    
    /**
     * Update property
     */
    public function update($id, $data) {
        $fields = [];
        $params = [];
        
        $allowed_fields = [
            'title', 'description', 'property_type', 'bedrooms', 'bathrooms',
            'area_sqft', 'monthly_rent', 'security_deposit', 'address', 'city',
            'state', 'zip_code', 'latitude', 'longitude', 'is_available'
        ];
        
        foreach ($data as $key => $value) {
            if (in_array($key, $allowed_fields)) {
                $fields[] = "{$key} = ?";
                $params[] = $value;
            }
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $params[] = $id;
        $sql = "UPDATE properties SET " . implode(', ', $fields) . " WHERE id = ?";
        
        return $this->db->query($sql, $params);
    }
    
    /**
     * Verify property
     */
    public function verify($id, $notes = null) {
        $sql = "UPDATE properties SET is_verified = 1, verification_notes = ? WHERE id = ?";
        return $this->db->query($sql, [$notes, $id]);
    }
    
    /**
     * Unverify property
     */
    public function unverify($id, $notes = null) {
        $sql = "UPDATE properties SET is_verified = 0, verification_notes = ? WHERE id = ?";
        return $this->db->query($sql, [$notes, $id]);
    }
    
    /**
     * Approve property (make visible to customers)
     */
    public function approve($id, $notes = null) {
        $sql = "UPDATE properties SET is_approved = 1, approval_notes = ?, approved_at = NOW() WHERE id = ?";
        return $this->db->query($sql, [$notes, $id]);
    }
    
    /**
     * Reject property (hide from customers)
     */
    public function reject($id, $notes = null) {
        $sql = "UPDATE properties SET is_approved = 0, approval_notes = ?, rejected_at = NOW() WHERE id = ?";
        return $this->db->query($sql, [$notes, $id]);
    }
    
    /**
     * Get properties pending approval (admin only)
     */
    public function getPendingApproval($page = 1, $limit = 20) {
        $offset = ($page - 1) * $limit;
        $sql = "SELECT p.*, u.name as owner_name, u.phone as owner_phone 
                FROM properties p 
                JOIN users u ON p.owner_id = u.id 
                WHERE p.is_approved = 0 
                ORDER BY p.created_at ASC 
                LIMIT ? OFFSET ?";
        return $this->db->fetchAll($sql, [$limit, $offset]);
    }
    
    /**
     * Get count of properties pending approval
     */
    public function getPendingApprovalCount() {
        $sql = "SELECT COUNT(*) as count FROM properties WHERE is_approved = 0";
        $result = $this->db->fetch($sql);
        return $result['count'];
    }
    
    /**
     * Delete property
     */
    public function delete($id) {
        $sql = "DELETE FROM properties WHERE id = ?";
        return $this->db->query($sql, [$id]);
    }
    
    /**
     * Get all properties with pagination (admin)
     */
    public function getAll($page = 1, $limit = 20, $status = null) {
        $offset = ($page - 1) * $limit;
        $where_clause = "WHERE 1=1";
        $params = [];
        
        if ($status === 'verified') {
            $where_clause .= " AND p.is_verified = 1";
        } elseif ($status === 'pending') {
            $where_clause .= " AND p.is_verified = 0";
        } elseif ($status === 'available') {
            $where_clause .= " AND p.is_available = 1";
        } elseif ($status === 'unavailable') {
            $where_clause .= " AND p.is_available = 0";
        }
        
        $sql = "SELECT p.*, u.name as owner_name, u.phone as owner_phone 
                FROM properties p 
                JOIN users u ON p.owner_id = u.id 
                {$where_clause} 
                ORDER BY p.created_at DESC 
                LIMIT ? OFFSET ?";
        
        $params[] = $limit;
        $params[] = $offset;
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * Get property count
     */
    public function getCount($status = null) {
        $where_clause = "WHERE 1=1";
        $params = [];
        
        if ($status === 'verified') {
            $where_clause .= " AND is_verified = 1";
        } elseif ($status === 'pending') {
            $where_clause .= " AND is_verified = 0";
        } elseif ($status === 'available') {
            $where_clause .= " AND is_available = 1";
        } elseif ($status === 'unavailable') {
            $where_clause .= " AND is_available = 0";
        }
        
        $sql = "SELECT COUNT(*) as count FROM properties {$where_clause}";
        $result = $this->db->fetch($sql, $params);
        return $result['count'];
    }
    
    /**
     * Get property statistics
     */
    public function getStats() {
        $stats = [];
        
        // Total properties
        $sql = "SELECT COUNT(*) as count FROM properties";
        $result = $this->db->fetch($sql);
        $stats['total'] = $result['count'];
        
        // Verified properties
        $sql = "SELECT COUNT(*) as count FROM properties WHERE is_verified = 1";
        $result = $this->db->fetch($sql);
        $stats['verified'] = $result['count'];
        
        // Available properties
        $sql = "SELECT COUNT(*) as count FROM properties WHERE is_available = 1";
        $result = $this->db->fetch($sql);
        $stats['available'] = $result['count'];
        
        // Approved properties (visible to customers)
        $sql = "SELECT COUNT(*) as count FROM properties WHERE is_approved = 1";
        $result = $this->db->fetch($sql);
        $stats['approved'] = $result['count'];
        
        // Pending approval properties
        $sql = "SELECT COUNT(*) as count FROM properties WHERE is_approved = 0";
        $result = $this->db->fetch($sql);
        $stats['pending_approval'] = $result['count'];
        
        // Properties by type
        $sql = "SELECT property_type, COUNT(*) as count FROM properties GROUP BY property_type";
        $results = $this->db->fetchAll($sql);
        $stats['by_type'] = [];
        foreach ($results as $result) {
            $stats['by_type'][$result['property_type']] = $result['count'];
        }
        
        // Average rent
        $sql = "SELECT AVG(monthly_rent) as avg_rent FROM properties WHERE is_verified = 1";
        $result = $this->db->fetch($sql);
        $stats['avg_rent'] = $result['avg_rent'] ?? 0;
        
        return $stats;
    }
    
    /**
     * Get property images
     */
    public function getImages($property_id) {
        $sql = "SELECT * FROM property_images WHERE property_id = ? ORDER BY is_primary DESC, uploaded_at ASC";
        return $this->db->fetchAll($sql, [$property_id]);
    }
    
    /**
     * Add property image
     */
    public function addImage($property_id, $image_path, $is_primary = false) {
        // If this is the primary image, unset other primary images
        if ($is_primary) {
            $this->db->query(
                "UPDATE property_images SET is_primary = 0 WHERE property_id = ?",
                [$property_id]
            );
        }
        
        $sql = "INSERT INTO property_images (property_id, image_path, is_primary) VALUES (?, ?, ?)";
        return $this->db->query($sql, [$property_id, $image_path, $is_primary ? 1 : 0]);
    }
    
    /**
     * Delete property image
     */
    public function deleteImage($image_id) {
        $sql = "DELETE FROM property_images WHERE id = ?";
        return $this->db->query($sql, [$image_id]);
    }
    
    /**
     * Get recent properties
     */
    public function getRecentProperties($days = 30) {
        $sql = "SELECT * FROM properties 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY) 
                ORDER BY created_at DESC";
        return $this->db->fetchAll($sql, [$days]);
    }
    
    /**
     * Get property type distribution
     */
    public function getPropertyTypeDistribution() {
        $sql = "SELECT property_type, COUNT(*) as count FROM properties GROUP BY property_type";
        $results = $this->db->fetchAll($sql);
        $distribution = [];
        foreach ($results as $result) {
            $distribution[$result['property_type']] = $result['count'];
        }
        return $distribution;
    }
    
    /**
     * Get count of properties by owner with filters
     */
    public function getCountByOwner($owner_id, $search = '', $filter_type = '', $filter_status = '') {
        $sql = "SELECT COUNT(*) as count FROM properties WHERE owner_id = ?";
        $params = [$owner_id];
        
        if (!empty($search)) {
            $sql .= " AND (title LIKE ? OR description LIKE ? OR address LIKE ? OR city LIKE ?)";
            $search_param = "%{$search}%";
            $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
        }
        
        if (!empty($filter_type)) {
            $sql .= " AND property_type = ?";
            $params[] = $filter_type;
        }
        
        if (!empty($filter_status)) {
            switch ($filter_status) {
                case 'available':
                    $sql .= " AND is_available = 1";
                    break;
                case 'unavailable':
                    $sql .= " AND is_available = 0";
                    break;
                case 'verified':
                    $sql .= " AND is_verified = 1";
                    break;
                case 'unverified':
                    $sql .= " AND is_verified = 0";
                    break;
            }
        }
        
        $result = $this->db->fetch($sql, $params);
        return $result['count'];
    }
    
    /**
     * Get pending visit requests for a property owner (for dashboard)
     */
    public function getPendingVisitRequests($owner_id) {
        $sql = "SELECT vr.*, p.title as property_title, u.name as customer_name
                FROM visit_requests vr
                JOIN properties p ON vr.property_id = p.id
                JOIN users u ON vr.customer_id = u.id
                WHERE p.owner_id = ? AND vr.status = 'pending'
                ORDER BY vr.created_at DESC
                LIMIT 10";
        return $this->db->fetchAll($sql, [$owner_id]);
    }
    
    /**
     * Get visit requests by owner with filters and pagination
     */
    public function getVisitRequestsByOwner($owner_id, $page = 1, $limit = 20, $search = '', $filter_status = '', $filter_property = '') {
        $offset = ($page - 1) * $limit;
        $where_conditions = ["p.owner_id = ?"];
        $params = [$owner_id];
        
        // Search filter
        if (!empty($search)) {
            $where_conditions[] = "(u.name LIKE ? OR p.title LIKE ? OR vr.notes LIKE ?)";
            $search_param = "%{$search}%";
            $params = array_merge($params, [$search_param, $search_param, $search_param]);
        }
        
        // Status filter
        if (!empty($filter_status)) {
            $where_conditions[] = "vr.status = ?";
            $params[] = $filter_status;
        }
        
        // Property filter
        if (!empty($filter_property)) {
            $where_conditions[] = "vr.property_id = ?";
            $params[] = $filter_property;
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        
        $sql = "SELECT vr.*, p.title as property_title, u.name as customer_name, u.phone as customer_phone
                FROM visit_requests vr
                JOIN properties p ON vr.property_id = p.id
                JOIN users u ON vr.customer_id = u.id
                WHERE {$where_clause}
                ORDER BY vr.created_at DESC
                LIMIT ? OFFSET ?";
        
        $params[] = $limit;
        $params[] = $offset;
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * Get count of visit requests by owner with filters
     */
    public function getVisitRequestsCountByOwner($owner_id, $search = '', $filter_status = '', $filter_property = '') {
        $where_conditions = ["p.owner_id = ?"];
        $params = [$owner_id];
        
        // Search filter
        if (!empty($search)) {
            $where_conditions[] = "(u.name LIKE ? OR p.title LIKE ? OR vr.notes LIKE ?)";
            $search_param = "%{$search}%";
            $params = array_merge($params, [$search_param, $search_param, $search_param]);
        }
        
        // Status filter
        if (!empty($filter_status)) {
            $where_conditions[] = "vr.status = ?";
            $params[] = $filter_status;
        }
        
        // Property filter
        if (!empty($filter_property)) {
            $where_conditions[] = "vr.property_id = ?";
            $params[] = $filter_property;
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        
        $sql = "SELECT COUNT(*) as count
                FROM visit_requests vr
                JOIN properties p ON vr.property_id = p.id
                JOIN users u ON vr.customer_id = u.id
                WHERE {$where_clause}";
        
        $result = $this->db->fetch($sql, $params);
        return $result['count'];
    }
    
    /**
     * Get visit request statistics for owner
     */
    public function getVisitRequestStats($owner_id) {
        $stats = [];
        
        // Total requests
        $sql = "SELECT COUNT(*) as count
                FROM visit_requests vr
                JOIN properties p ON vr.property_id = p.id
                WHERE p.owner_id = ?";
        $result = $this->db->fetch($sql, [$owner_id]);
        $stats['total_requests'] = $result['count'];
        
        // Pending requests
        $sql = "SELECT COUNT(*) as count
                FROM visit_requests vr
                JOIN properties p ON vr.property_id = p.id
                WHERE p.owner_id = ? AND vr.status = 'pending'";
        $result = $this->db->fetch($sql, [$owner_id]);
        $stats['pending_requests'] = $result['count'];
        
        // Approved requests
        $sql = "SELECT COUNT(*) as count
                FROM visit_requests vr
                JOIN properties p ON vr.property_id = p.id
                WHERE p.owner_id = ? AND vr.status = 'approved'";
        $result = $this->db->fetch($sql, [$owner_id]);
        $stats['approved_requests'] = $result['count'];
        
        // Completed requests
        $sql = "SELECT COUNT(*) as count
                FROM visit_requests vr
                JOIN properties p ON vr.property_id = p.id
                WHERE p.owner_id = ? AND vr.status = 'completed'";
        $result = $this->db->fetch($sql, [$owner_id]);
        $stats['completed_requests'] = $result['count'];
        
        // Rejected requests
        $sql = "SELECT COUNT(*) as count
                FROM visit_requests vr
                JOIN properties p ON vr.property_id = p.id
                WHERE p.owner_id = ? AND vr.status = 'rejected'";
        $result = $this->db->fetch($sql, [$owner_id]);
        $stats['rejected_requests'] = $result['count'];
        
        return $stats;
    }
    
    /**
     * Respond to a visit request (approve/reject/complete)
     */
    public function respondToVisitRequest($visit_id, $response, $owner_response = '') {
        // Validate response
        $valid_responses = ['approved', 'rejected', 'completed', 'cancelled'];
        if (!in_array($response, $valid_responses)) {
            throw new Exception('Invalid response status');
        }
        
        // Update the visit request
        $sql = "UPDATE visit_requests 
                SET status = ?, owner_response = ?, updated_at = NOW() 
                WHERE id = ?";
        
        $result = $this->db->query($sql, [$response, $owner_response, $visit_id]);
        
        if (!$result) {
            throw new Exception('Failed to update visit request');
        }
        
        return true;
    }
    
    /**
     * Get owner analytics overview
     */
    public function getOwnerAnalytics($owner_id, $date_from, $date_to) {
        $analytics = [];
        
        // Total properties
        $sql = "SELECT COUNT(*) as count FROM properties WHERE owner_id = ?";
        $result = $this->db->fetch($sql, [$owner_id]);
        $analytics['total_properties'] = $result['count'];
        
        // Available properties
        $sql = "SELECT COUNT(*) as count FROM properties WHERE owner_id = ? AND is_available = 1";
        $result = $this->db->fetch($sql, [$owner_id]);
        $analytics['available_properties'] = $result['count'];
        
        // Properties added in date range
        $sql = "SELECT COUNT(*) as count FROM properties WHERE owner_id = ? AND DATE(created_at) BETWEEN ? AND ?";
        $result = $this->db->fetch($sql, [$owner_id, $date_from, $date_to]);
        $analytics['new_properties'] = $result['count'];
        
        // Total earnings in date range
        $sql = "SELECT SUM(p.owner_payout_amount) as total_earnings 
                FROM rent_payments p
                WHERE p.owner_id = ? AND p.payment_status = 'completed' 
                AND DATE(p.created_at) BETWEEN ? AND ?";
        $result = $this->db->fetch($sql, [$owner_id, $date_from, $date_to]);
        $analytics['total_earnings'] = $result['total_earnings'] ?? 0;
        
        // Total bookings in date range
        $sql = "SELECT COUNT(*) as total_bookings 
                FROM rental_bookings b
                JOIN properties p ON b.property_id = p.id
                WHERE p.owner_id = ? AND DATE(b.created_at) BETWEEN ? AND ?";
        $result = $this->db->fetch($sql, [$owner_id, $date_from, $date_to]);
        $analytics['total_bookings'] = $result['total_bookings'];
        
        // Occupancy rate calculation
        $total_properties = $analytics['total_properties'];
        $occupied_properties = 0;
        if ($total_properties > 0) {
            $sql = "SELECT COUNT(DISTINCT p.id) as occupied 
                    FROM properties p
                    JOIN rental_bookings b ON p.id = b.property_id
                    WHERE p.owner_id = ? AND b.status = 'active' 
                    AND CURDATE() BETWEEN b.start_date AND b.end_date";
            $result = $this->db->fetch($sql, [$owner_id]);
            $occupied_properties = $result['occupied'];
            $analytics['occupancy_rate'] = round(($occupied_properties / $total_properties) * 100, 1);
        } else {
            $analytics['occupancy_rate'] = 0;
        }
        
        // Calculate growth percentages (simplified - comparing with previous period)
        $previous_start = date('Y-m-d', strtotime($date_from . ' -1 month'));
        $previous_end = date('Y-m-d', strtotime($date_to . ' -1 month'));
        
        // Previous period earnings
        $sql = "SELECT SUM(p.owner_payout_amount) as prev_earnings 
                FROM rent_payments p
                WHERE p.owner_id = ? AND p.payment_status = 'completed' 
                AND DATE(p.created_at) BETWEEN ? AND ?";
        $result = $this->db->fetch($sql, [$owner_id, $previous_start, $previous_end]);
        $prev_earnings = $result['prev_earnings'] ?? 0;
        $analytics['earnings_growth'] = $prev_earnings > 0 ? round((($analytics['total_earnings'] - $prev_earnings) / $prev_earnings) * 100, 1) : 0;
        
        // Previous period bookings
        $sql = "SELECT COUNT(*) as prev_bookings 
                FROM rental_bookings b
                JOIN properties p ON b.property_id = p.id
                WHERE p.owner_id = ? AND DATE(b.created_at) BETWEEN ? AND ?";
        $result = $this->db->fetch($sql, [$owner_id, $previous_start, $previous_end]);
        $prev_bookings = $result['prev_bookings'] ?? 0;
        $analytics['bookings_growth'] = $prev_bookings > 0 ? round((($analytics['total_bookings'] - $prev_bookings) / $prev_bookings) * 100, 1) : 0;
        
        // Previous period occupancy
        $prev_occupied = 0;
        if ($total_properties > 0) {
            $sql = "SELECT COUNT(DISTINCT p.id) as prev_occupied 
                    FROM properties p
                    JOIN rental_bookings b ON p.id = b.property_id
                    WHERE p.owner_id = ? AND b.status = 'active' 
                    AND DATE_ADD(CURDATE(), INTERVAL -1 MONTH) BETWEEN b.start_date AND b.end_date";
            $result = $this->db->fetch($sql, [$owner_id]);
            $prev_occupied = $result['prev_occupied'];
        }
        $prev_occupancy_rate = $total_properties > 0 ? round(($prev_occupied / $total_properties) * 100, 1) : 0;
        $analytics['occupancy_growth'] = $prev_occupancy_rate > 0 ? round($analytics['occupancy_rate'] - $prev_occupancy_rate, 1) : 0;
        
        // Properties by type
        $sql = "SELECT property_type, COUNT(*) as count FROM properties WHERE owner_id = ? GROUP BY property_type";
        $results = $this->db->fetchAll($sql, [$owner_id]);
        $analytics['by_type'] = [];
        foreach ($results as $result) {
            $analytics['by_type'][$result['property_type']] = $result['count'];
        }
        
        // Average rent
        $sql = "SELECT AVG(monthly_rent) as avg_rent FROM properties WHERE owner_id = ? AND is_available = 1";
        $result = $this->db->fetch($sql, [$owner_id]);
        $analytics['avg_rent'] = $result['avg_rent'] ?? 0;
        
        // Previous period average rent for growth calculation
        $sql = "SELECT AVG(monthly_rent) as prev_avg_rent 
                FROM properties 
                WHERE owner_id = ? AND is_available = 1 
                AND DATE(created_at) <= ?";
        $result = $this->db->fetch($sql, [$owner_id, $previous_start]);
        $prev_avg_rent = $result['prev_avg_rent'] ?? 0;
        $analytics['rent_growth'] = $prev_avg_rent > 0 ? round((($analytics['avg_rent'] - $prev_avg_rent) / $prev_avg_rent) * 100, 1) : 0;
        
        // Properties by status
        $sql = "SELECT 
                    SUM(CASE WHEN is_available = 1 THEN 1 ELSE 0 END) as available,
                    SUM(CASE WHEN is_available = 0 THEN 1 ELSE 0 END) as unavailable,
                    SUM(CASE WHEN is_verified = 1 THEN 1 ELSE 0 END) as verified,
                    SUM(CASE WHEN is_verified = 0 THEN 1 ELSE 0 END) as unverified,
                    SUM(CASE WHEN is_approved = 1 THEN 1 ELSE 0 END) as approved,
                    SUM(CASE WHEN is_approved = 0 THEN 1 ELSE 0 END) as pending_approval
                FROM properties WHERE owner_id = ?";
        $result = $this->db->fetch($sql, [$owner_id]);
        $analytics['status_breakdown'] = $result;
        
        return $analytics;
    }
    
    /**
     * Get property performance analytics
     */
    public function getPropertyPerformanceAnalytics($owner_id, $date_from, $date_to) {
        $sql = "SELECT p.id, p.title, p.monthly_rent, p.is_available,
                       COUNT(DISTINCT b.id) as total_bookings,
                       COUNT(DISTINCT vr.id) as total_visits,
                       SUM(CASE WHEN b.status = 'active' THEN 1 ELSE 0 END) as active_bookings,
                       AVG(CASE WHEN b.status = 'completed' THEN DATEDIFF(b.end_date, b.start_date) ELSE NULL END) as avg_booking_duration
                FROM properties p
                LEFT JOIN rental_bookings b ON p.id = b.property_id AND DATE(b.created_at) BETWEEN ? AND ?
                LEFT JOIN visit_requests vr ON p.id = vr.property_id AND DATE(vr.created_at) BETWEEN ? AND ?
                WHERE p.owner_id = ?
                GROUP BY p.id, p.title, p.monthly_rent, p.is_available
                ORDER BY total_bookings DESC";
        
        return $this->db->fetchAll($sql, [$date_from, $date_to, $date_from, $date_to, $owner_id]);
    }
    
    /**
     * Get property performance data for charts
     */
    public function getPropertyPerformanceData($owner_id) {
        $sql = "SELECT p.id, p.title, p.monthly_rent,
                       COUNT(DISTINCT b.id) as total_bookings,
                       COUNT(DISTINCT vr.id) as total_visits,
                       SUM(CASE WHEN b.status = 'active' THEN 1 ELSE 0 END) as active_bookings,
                       SUM(CASE WHEN b.status = 'completed' THEN 1 ELSE 0 END) as completed_bookings
                FROM properties p
                LEFT JOIN rental_bookings b ON p.id = b.property_id
                LEFT JOIN visit_requests vr ON p.id = vr.property_id
                WHERE p.owner_id = ?
                GROUP BY p.id, p.title, p.monthly_rent
                ORDER BY total_bookings DESC";
        
        return $this->db->fetchAll($sql, [$owner_id]);
    }
}
?>
