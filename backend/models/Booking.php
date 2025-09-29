<?php
/**
 * Booking Model
 * Handles booking-related database operations
 */

require_once __DIR__ . '/../../config/config.php';

class Booking {
    private $db;
    
    public function __construct() {
        global $database;
        $this->db = $database;
    }
    
    /**
     * Create a new booking
     */
    public function create($data) {
        $sql = "INSERT INTO rental_bookings (
                    customer_id, property_id, start_date, end_date, 
                    monthly_rent, security_deposit, status
                ) VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $params = [
            $data['customer_id'],
            $data['property_id'],
            $data['start_date'],
            $data['end_date'] ?? null,
            $data['monthly_rent'],
            $data['security_deposit'],
            $data['status'] ?? 'active'
        ];
        
        $this->db->query($sql, $params);
        return $this->db->lastInsertId();
    }
    
    /**
     * Get booking by ID
     */
    public function getById($id) {
        $sql = "SELECT b.*, 
                       c.name as customer_name, c.phone as customer_phone, c.email as customer_email,
                       p.title as property_title, p.address as property_address, p.city as property_city,
                       o.name as owner_name, o.phone as owner_phone
                FROM rental_bookings b
                JOIN users c ON b.customer_id = c.id
                JOIN properties p ON b.property_id = p.id
                JOIN users o ON p.owner_id = o.id
                WHERE b.id = ?";
        return $this->db->fetch($sql, [$id]);
    }
    
    /**
     * Get bookings by customer
     */
    public function getByCustomer($customer_id, $page = 1, $limit = 20) {
        $offset = ($page - 1) * $limit;
        $sql = "SELECT b.*, 
                       p.title as property_title, p.address as property_address, p.city as property_city,
                       o.name as owner_name, o.phone as owner_phone
                FROM rental_bookings b
                JOIN properties p ON b.property_id = p.id
                JOIN users o ON p.owner_id = o.id
                WHERE b.customer_id = ? 
                ORDER BY b.created_at DESC 
                LIMIT ? OFFSET ?";
        return $this->db->fetchAll($sql, [$customer_id, $limit, $offset]);
    }
    
    /**
     * Get bookings by property
     */
    public function getByProperty($property_id, $page = 1, $limit = 20) {
        $offset = ($page - 1) * $limit;
        $sql = "SELECT b.*, 
                       c.name as customer_name, c.phone as customer_phone, c.email as customer_email
                FROM rental_bookings b
                JOIN users c ON b.customer_id = c.id
                WHERE b.property_id = ? 
                ORDER BY b.created_at DESC 
                LIMIT ? OFFSET ?";
        return $this->db->fetchAll($sql, [$property_id, $limit, $offset]);
    }
    
    /**
     * Get bookings by property owner
     */
    public function getByOwner($owner_id, $page = 1, $limit = 20, $search = '', $filter_status = '', $filter_property = '') {
        $offset = ($page - 1) * $limit;
        $where_conditions = ["p.owner_id = ?"];
        $params = [$owner_id];
        
        if (!empty($search)) {
            $where_conditions[] = "(c.name LIKE ? OR c.phone LIKE ? OR c.email LIKE ? OR p.title LIKE ?)";
            $search_param = "%{$search}%";
            $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
        }
        
        if (!empty($filter_status)) {
            $where_conditions[] = "b.status = ?";
            $params[] = $filter_status;
        }
        
        if (!empty($filter_property)) {
            $where_conditions[] = "b.property_id = ?";
            $params[] = $filter_property;
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        
        $sql = "SELECT b.*, 
                       c.name as customer_name, c.phone as customer_phone, c.email as customer_email,
                       p.title as property_title, p.address as property_address, p.city as property_city
                FROM rental_bookings b
                JOIN users c ON b.customer_id = c.id
                JOIN properties p ON b.property_id = p.id
                WHERE {$where_clause} 
                ORDER BY b.created_at DESC 
                LIMIT ? OFFSET ?";
        
        $params[] = $limit;
        $params[] = $offset;
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * Get count of bookings by property owner
     */
    public function getCountByOwner($owner_id, $search = '', $filter_status = '', $filter_property = '') {
        $where_conditions = ["p.owner_id = ?"];
        $params = [$owner_id];
        
        if (!empty($search)) {
            $where_conditions[] = "(c.name LIKE ? OR c.phone LIKE ? OR c.email LIKE ? OR p.title LIKE ?)";
            $search_param = "%{$search}%";
            $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
        }
        
        if (!empty($filter_status)) {
            $where_conditions[] = "b.status = ?";
            $params[] = $filter_status;
        }
        
        if (!empty($filter_property)) {
            $where_conditions[] = "b.property_id = ?";
            $params[] = $filter_property;
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        
        $sql = "SELECT COUNT(*) as count 
                FROM rental_bookings b
                JOIN users c ON b.customer_id = c.id
                JOIN properties p ON b.property_id = p.id
                WHERE {$where_clause}";
        
        $result = $this->db->fetch($sql, $params);
        return $result['count'];
    }
    
    /**
     * Get all bookings with pagination (admin)
     */
    public function getAll($page = 1, $limit = 20, $status = null) {
        $offset = ($page - 1) * $limit;
        $where_clause = "WHERE 1=1";
        $params = [];
        
        if ($status) {
            $where_clause .= " AND b.status = ?";
            $params[] = $status;
        }
        
        $sql = "SELECT b.*, 
                       c.name as customer_name, c.phone as customer_phone, c.email as customer_email,
                       p.title as property_title, p.address as property_address, p.city as property_city,
                       o.name as owner_name, o.phone as owner_phone
                FROM rental_bookings b
                JOIN users c ON b.customer_id = c.id
                JOIN properties p ON b.property_id = p.id
                JOIN users o ON p.owner_id = o.id
                {$where_clause} 
                ORDER BY b.created_at DESC 
                LIMIT ? OFFSET ?";
        
        $params[] = $limit;
        $params[] = $offset;
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * Get booking count
     */
    public function getCount($status = null) {
        $where_clause = "WHERE 1=1";
        $params = [];
        
        if ($status) {
            $where_clause .= " AND status = ?";
            $params[] = $status;
        }
        
        $sql = "SELECT COUNT(*) as count FROM rental_bookings {$where_clause}";
        $result = $this->db->fetch($sql, $params);
        return $result['count'];
    }
    
    /**
     * Update booking
     */
    public function update($id, $data) {
        $fields = [];
        $params = [];
        
        $allowed_fields = [
            'start_date', 'end_date', 'monthly_rent', 'security_deposit', 'status'
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
        $sql = "UPDATE rental_bookings SET " . implode(', ', $fields) . " WHERE id = ?";
        
        return $this->db->query($sql, $params);
    }
    
    /**
     * Terminate booking
     */
    public function terminate($id, $end_date = null) {
        $sql = "UPDATE rental_bookings SET status = 'terminated', end_date = ? WHERE id = ?";
        return $this->db->query($sql, [$end_date ?: date('Y-m-d'), $id]);
    }
    
    /**
     * Delete booking
     */
    public function delete($id) {
        $sql = "DELETE FROM rental_bookings WHERE id = ?";
        return $this->db->query($sql, [$id]);
    }
    
    /**
     * Get booking statistics
     */
    public function getStats() {
        $stats = [];
        
        // Total bookings
        $sql = "SELECT COUNT(*) as count FROM rental_bookings";
        $result = $this->db->fetch($sql);
        $stats['total'] = $result['count'];
        
        // Active bookings
        $sql = "SELECT COUNT(*) as count FROM rental_bookings WHERE status = 'active'";
        $result = $this->db->fetch($sql);
        $stats['active'] = $result['count'];
        
        // Terminated bookings
        $sql = "SELECT COUNT(*) as count FROM rental_bookings WHERE status = 'terminated'";
        $result = $this->db->fetch($sql);
        $stats['terminated'] = $result['count'];
        
        // Expired bookings
        $sql = "SELECT COUNT(*) as count FROM rental_bookings WHERE status = 'expired'";
        $result = $this->db->fetch($sql);
        $stats['expired'] = $result['count'];
        
        // Total revenue
        $sql = "SELECT SUM(monthly_rent) as total_revenue FROM rental_bookings WHERE status = 'active'";
        $result = $this->db->fetch($sql);
        $stats['total_revenue'] = $result['total_revenue'] ?? 0;
        
        // Average rent
        $sql = "SELECT AVG(monthly_rent) as avg_rent FROM rental_bookings WHERE status = 'active'";
        $result = $this->db->fetch($sql);
        $stats['avg_rent'] = $result['avg_rent'] ?? 0;
        
        // Recent bookings (last 30 days)
        $sql = "SELECT COUNT(*) as count FROM rental_bookings WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        $result = $this->db->fetch($sql);
        $stats['recent_bookings'] = $result['count'];
        
        return $stats;
    }
    
    /**
     * Search bookings
     */
    public function search($query, $status = null, $page = 1, $limit = 20) {
        $offset = ($page - 1) * $limit;
        $where_conditions = ["1=1"];
        $params = [];
        
        if ($status) {
            $where_conditions[] = "b.status = ?";
            $params[] = $status;
        }
        
        if (!empty($query)) {
            $where_conditions[] = "(c.name LIKE ? OR c.phone LIKE ? OR c.email LIKE ? OR p.title LIKE ? OR p.address LIKE ? OR p.city LIKE ? OR o.name LIKE ?)";
            $search_param = "%{$query}%";
            $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param, $search_param, $search_param, $search_param]);
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        
        $sql = "SELECT b.*, 
                       c.name as customer_name, c.phone as customer_phone, c.email as customer_email,
                       p.title as property_title, p.address as property_address, p.city as property_city,
                       o.name as owner_name, o.phone as owner_phone
                FROM rental_bookings b
                JOIN users c ON b.customer_id = c.id
                JOIN properties p ON b.property_id = p.id
                JOIN users o ON p.owner_id = o.id
                WHERE {$where_clause} 
                ORDER BY b.created_at DESC 
                LIMIT ? OFFSET ?";
        
        $params[] = $limit;
        $params[] = $offset;
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * Get recent bookings
     */
    public function getRecentBookings($days = 30) {
        $sql = "SELECT b.*, 
                       c.name as customer_name, c.phone as customer_phone, c.email as customer_email,
                       p.title as property_title, p.address as property_address, p.city as property_city,
                       o.name as owner_name, o.phone as owner_phone
                FROM rental_bookings b
                JOIN users c ON b.customer_id = c.id
                JOIN properties p ON b.property_id = p.id
                JOIN users o ON p.owner_id = o.id
                WHERE b.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY) 
                ORDER BY b.created_at DESC";
        return $this->db->fetchAll($sql, [$days]);
    }
    
    /**
     * Get owner booking analytics
     */
    public function getOwnerBookingAnalytics($owner_id, $date_from, $date_to) {
        $analytics = [];
        
        // Total bookings in date range
        $sql = "SELECT COUNT(*) as total_bookings 
                FROM rental_bookings b
                JOIN properties p ON b.property_id = p.id
                WHERE p.owner_id = ? AND DATE(b.created_at) BETWEEN ? AND ?";
        $result = $this->db->fetch($sql, [$owner_id, $date_from, $date_to]);
        $analytics['total_bookings'] = $result['total_bookings'];
        
        // Active bookings
        $sql = "SELECT COUNT(*) as active_bookings 
                FROM rental_bookings b
                JOIN properties p ON b.property_id = p.id
                WHERE p.owner_id = ? AND b.status = 'active' 
                AND DATE(b.created_at) BETWEEN ? AND ?";
        $result = $this->db->fetch($sql, [$owner_id, $date_from, $date_to]);
        $analytics['active_bookings'] = $result['active_bookings'];
        
        // Completed bookings
        $sql = "SELECT COUNT(*) as completed_bookings 
                FROM rental_bookings b
                JOIN properties p ON b.property_id = p.id
                WHERE p.owner_id = ? AND b.status = 'completed' 
                AND DATE(b.created_at) BETWEEN ? AND ?";
        $result = $this->db->fetch($sql, [$owner_id, $date_from, $date_to]);
        $analytics['completed_bookings'] = $result['completed_bookings'];
        
        // Cancelled bookings
        $sql = "SELECT COUNT(*) as cancelled_bookings 
                FROM rental_bookings b
                JOIN properties p ON b.property_id = p.id
                WHERE p.owner_id = ? AND b.status = 'cancelled' 
                AND DATE(b.created_at) BETWEEN ? AND ?";
        $result = $this->db->fetch($sql, [$owner_id, $date_from, $date_to]);
        $analytics['cancelled_bookings'] = $result['cancelled_bookings'];
        
        // Average booking duration
        $sql = "SELECT AVG(DATEDIFF(b.end_date, b.start_date)) as avg_duration 
                FROM rental_bookings b
                JOIN properties p ON b.property_id = p.id
                WHERE p.owner_id = ? AND b.status = 'completed' 
                AND DATE(b.created_at) BETWEEN ? AND ?";
        $result = $this->db->fetch($sql, [$owner_id, $date_from, $date_to]);
        $analytics['avg_duration'] = $result['avg_duration'] ?? 0;
        
        // Bookings by property
        $sql = "SELECT pr.title as property_title, 
                       COUNT(b.id) as booking_count,
                       AVG(DATEDIFF(b.end_date, b.start_date)) as avg_duration
                FROM rental_bookings b
                JOIN properties pr ON b.property_id = pr.id
                WHERE pr.owner_id = ? AND DATE(b.created_at) BETWEEN ? AND ?
                GROUP BY pr.id, pr.title
                ORDER BY booking_count DESC";
        $analytics['by_property'] = $this->db->fetchAll($sql, [$owner_id, $date_from, $date_to]);
        
        // Daily bookings breakdown
        $sql = "SELECT DATE(b.created_at) as date, 
                       COUNT(b.id) as daily_bookings
                FROM rental_bookings b
                JOIN properties p ON b.property_id = p.id
                WHERE p.owner_id = ? AND DATE(b.created_at) BETWEEN ? AND ?
                GROUP BY DATE(b.created_at)
                ORDER BY date";
        $results = $this->db->fetchAll($sql, [$owner_id, $date_from, $date_to]);
        $analytics['daily_bookings'] = [];
        foreach ($results as $result) {
            $analytics['daily_bookings'][$result['date']] = (int)$result['daily_bookings'];
        }
        
        return $analytics;
    }
    
    /**
     * Get monthly bookings for owner
     */
    public function getMonthlyBookings($owner_id, $months = 12) {
        $sql = "SELECT DATE_FORMAT(b.created_at, '%Y-%m') as month, 
                       COUNT(b.id) as booking_count
                FROM rental_bookings b
                JOIN properties p ON b.property_id = p.id
                WHERE p.owner_id = ? 
                AND b.created_at >= DATE_SUB(NOW(), INTERVAL ? MONTH) 
                GROUP BY DATE_FORMAT(b.created_at, '%Y-%m') 
                ORDER BY month";
        
        $results = $this->db->fetchAll($sql, [$owner_id, $months]);
        $bookings = [];
        
        // Fill in missing months with 0 bookings
        for ($i = $months - 1; $i >= 0; $i--) {
            $month = date('Y-m', strtotime("-{$i} months"));
            $bookings[$month] = 0;
        }
        
        // Fill in actual bookings
        foreach ($results as $result) {
            $bookings[$result['month']] = (int)$result['booking_count'];
        }
        
        return $bookings;
    }
}
?>
