<?php
/**
 * Payment Model
 * Handles payment-related database operations
 */

require_once __DIR__ . '/../../config/config.php';

class Payment {
    private $db;
    
    public function __construct() {
        global $database;
        $this->db = $database;
    }
    
    /**
     * Create a new payment
     */
    public function create($data) {
        $sql = "INSERT INTO rent_payments (
                    booking_id, subscription_id, customer_id, property_id, owner_id, amount, 
                    payment_method, payment_status, payment_reference, due_date, 
                    paid_date, commission_amount, owner_payout_amount, is_recurring
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $params = [
            $data['booking_id'] ?? null,
            $data['subscription_id'] ?? null,
            $data['customer_id'],
            $data['property_id'],
            $data['owner_id'],
            $data['amount'],
            $data['payment_method'],
            $data['payment_status'] ?? 'pending',
            $data['payment_reference'] ?? null,
            $data['due_date'],
            $data['paid_date'] ?? null,
            $data['commission_amount'] ?? 0.00,
            $data['owner_payout_amount'] ?? 0.00,
            $data['is_recurring'] ?? 0
        ];
        
        $this->db->query($sql, $params);
        return $this->db->lastInsertId();
    }
    
    /**
     * Get payment by ID
     */
    public function getById($id) {
        $sql = "SELECT p.*, 
                       c.name as customer_name, c.phone as customer_phone, c.email as customer_email,
                       pr.title as property_title, pr.address as property_address, pr.city as property_city,
                       o.name as owner_name, o.phone as owner_phone,
                       b.start_date, b.end_date, b.monthly_rent
                FROM rent_payments p
                JOIN users c ON p.customer_id = c.id
                JOIN properties pr ON p.property_id = pr.id
                JOIN users o ON p.owner_id = o.id
                JOIN rental_bookings b ON p.booking_id = b.id
                WHERE p.id = ?";
        return $this->db->fetch($sql, [$id]);
    }
    
    /**
     * Get payments by customer
     */
    public function getByCustomer($customer_id, $page = 1, $limit = 20) {
        $offset = ($page - 1) * $limit;
        $sql = "SELECT p.*, 
                       pr.title as property_title, pr.address as property_address, pr.city as property_city,
                       o.name as owner_name, o.phone as owner_phone
                FROM rent_payments p
                JOIN properties pr ON p.property_id = pr.id
                JOIN users o ON p.owner_id = o.id
                WHERE p.customer_id = ? 
                ORDER BY p.created_at DESC 
                LIMIT ? OFFSET ?";
        return $this->db->fetchAll($sql, [$customer_id, $limit, $offset]);
    }
    
    /**
     * Get payments by property
     */
    public function getByProperty($property_id, $page = 1, $limit = 20) {
        $offset = ($page - 1) * $limit;
        $sql = "SELECT p.*, 
                       c.name as customer_name, c.phone as customer_phone, c.email as customer_email
                FROM rent_payments p
                JOIN users c ON p.customer_id = c.id
                WHERE p.property_id = ? 
                ORDER BY p.created_at DESC 
                LIMIT ? OFFSET ?";
        return $this->db->fetchAll($sql, [$property_id, $limit, $offset]);
    }
    
    /**
     * Get payments by owner with filters and pagination
     */
    public function getByOwner($owner_id, $page = 1, $limit = 20, $search = '', $filter_status = '', $filter_property = '', $date_from = '', $date_to = '') {
        $offset = ($page - 1) * $limit;
        $where_conditions = ["p.owner_id = ?"];
        $params = [$owner_id];
        
        // Search filter
        if (!empty($search)) {
            $where_conditions[] = "(c.name LIKE ? OR c.phone LIKE ? OR c.email LIKE ? OR pr.title LIKE ? OR pr.address LIKE ? OR pr.city LIKE ? OR p.payment_reference LIKE ?)";
            $search_param = "%{$search}%";
            $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param, $search_param, $search_param, $search_param]);
        }
        
        // Status filter
        if (!empty($filter_status)) {
            $where_conditions[] = "p.payment_status = ?";
            $params[] = $filter_status;
        }
        
        // Property filter
        if (!empty($filter_property)) {
            $where_conditions[] = "p.property_id = ?";
            $params[] = $filter_property;
        }
        
        // Date range filter
        if (!empty($date_from)) {
            $where_conditions[] = "DATE(p.created_at) >= ?";
            $params[] = $date_from;
        }
        
        if (!empty($date_to)) {
            $where_conditions[] = "DATE(p.created_at) <= ?";
            $params[] = $date_to;
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        
        $sql = "SELECT p.*, 
                       c.name as customer_name, c.phone as customer_phone, c.email as customer_email,
                       pr.title as property_title, pr.address as property_address, pr.city as property_city
                FROM rent_payments p
                JOIN users c ON p.customer_id = c.id
                JOIN properties pr ON p.property_id = pr.id
                WHERE {$where_clause}
                ORDER BY p.created_at DESC 
                LIMIT ? OFFSET ?";
        
        $params[] = $limit;
        $params[] = $offset;
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * Get count of payments by owner with filters
     */
    public function getCountByOwner($owner_id, $search = '', $filter_status = '', $filter_property = '', $date_from = '', $date_to = '') {
        $where_conditions = ["p.owner_id = ?"];
        $params = [$owner_id];
        
        // Search filter
        if (!empty($search)) {
            $where_conditions[] = "(c.name LIKE ? OR c.phone LIKE ? OR c.email LIKE ? OR pr.title LIKE ? OR pr.address LIKE ? OR pr.city LIKE ? OR p.payment_reference LIKE ?)";
            $search_param = "%{$search}%";
            $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param, $search_param, $search_param, $search_param]);
        }
        
        // Status filter
        if (!empty($filter_status)) {
            $where_conditions[] = "p.payment_status = ?";
            $params[] = $filter_status;
        }
        
        // Property filter
        if (!empty($filter_property)) {
            $where_conditions[] = "p.property_id = ?";
            $params[] = $filter_property;
        }
        
        // Date range filter
        if (!empty($date_from)) {
            $where_conditions[] = "DATE(p.created_at) >= ?";
            $params[] = $date_from;
        }
        
        if (!empty($date_to)) {
            $where_conditions[] = "DATE(p.created_at) <= ?";
            $params[] = $date_to;
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        
        $sql = "SELECT COUNT(*) as count
                FROM rent_payments p
                JOIN users c ON p.customer_id = c.id
                JOIN properties pr ON p.property_id = pr.id
                WHERE {$where_clause}";
        
        $result = $this->db->fetch($sql, $params);
        return $result['count'];
    }
    
    /**
     * Get all payments with pagination (admin)
     */
    public function getAll($page = 1, $limit = 20, $status = null) {
        $offset = ($page - 1) * $limit;
        $where_clause = "WHERE 1=1";
        $params = [];
        
        if ($status) {
            $where_clause .= " AND p.payment_status = ?";
            $params[] = $status;
        }
        
        $sql = "SELECT p.*, 
                       c.name as customer_name, c.phone as customer_phone, c.email as customer_email,
                       pr.title as property_title, pr.address as property_address, pr.city as property_city,
                       o.name as owner_name, o.phone as owner_phone,
                       b.start_date, b.end_date, b.monthly_rent
                FROM rent_payments p
                JOIN users c ON p.customer_id = c.id
                JOIN properties pr ON p.property_id = pr.id
                JOIN users o ON p.owner_id = o.id
                JOIN rental_bookings b ON p.booking_id = b.id
                {$where_clause} 
                ORDER BY p.created_at DESC 
                LIMIT ? OFFSET ?";
        
        $params[] = $limit;
        $params[] = $offset;
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * Get payment count
     */
    public function getCount($status = null) {
        $where_clause = "WHERE 1=1";
        $params = [];
        
        if ($status) {
            $where_clause .= " AND payment_status = ?";
            $params[] = $status;
        }
        
        $sql = "SELECT COUNT(*) as count FROM rent_payments {$where_clause}";
        $result = $this->db->fetch($sql, $params);
        return $result['count'];
    }
    
    /**
     * Update payment
     */
    public function update($id, $data) {
        $fields = [];
        $params = [];
        
        $allowed_fields = [
            'amount', 'payment_method', 'payment_status', 'payment_reference', 
            'due_date', 'paid_date', 'commission_amount', 'owner_payout_amount', 'is_recurring'
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
        $sql = "UPDATE rent_payments SET " . implode(', ', $fields) . " WHERE id = ?";
        
        return $this->db->query($sql, $params);
    }
    
    /**
     * Mark payment as completed
     */
    public function markCompleted($id, $paid_date = null) {
        $sql = "UPDATE rent_payments SET payment_status = 'completed', paid_date = ? WHERE id = ?";
        return $this->db->query($sql, [$paid_date ?: date('Y-m-d'), $id]);
    }
    
    /**
     * Mark payment as failed
     */
    public function markFailed($id) {
        $sql = "UPDATE rent_payments SET payment_status = 'failed' WHERE id = ?";
        return $this->db->query($sql, [$id]);
    }
    
    /**
     * Refund payment
     */
    public function refund($id, $refund_amount = null) {
        $sql = "UPDATE rent_payments SET payment_status = 'refunded'";
        $params = [];
        
        if ($refund_amount !== null) {
            $sql .= ", amount = ?";
            $params[] = $refund_amount;
        }
        
        $sql .= " WHERE id = ?";
        $params[] = $id;
        
        return $this->db->query($sql, $params);
    }
    
    /**
     * Delete payment
     */
    public function delete($id) {
        $sql = "DELETE FROM rent_payments WHERE id = ?";
        return $this->db->query($sql, [$id]);
    }
    
    /**
     * Get payment statistics
     */
    public function getStats() {
        $stats = [];
        
        // Total payments
        $sql = "SELECT COUNT(*) as count FROM rent_payments";
        $result = $this->db->fetch($sql);
        $stats['total'] = $result['count'];
        
        // Completed payments
        $sql = "SELECT COUNT(*) as count FROM rent_payments WHERE payment_status = 'completed'";
        $result = $this->db->fetch($sql);
        $stats['completed'] = $result['count'];
        
        // Pending payments
        $sql = "SELECT COUNT(*) as count FROM rent_payments WHERE payment_status = 'pending'";
        $result = $this->db->fetch($sql);
        $stats['pending'] = $result['count'];
        
        // Failed payments
        $sql = "SELECT COUNT(*) as count FROM rent_payments WHERE payment_status = 'failed'";
        $result = $this->db->fetch($sql);
        $stats['failed'] = $result['count'];
        
        // Refunded payments
        $sql = "SELECT COUNT(*) as count FROM rent_payments WHERE payment_status = 'refunded'";
        $result = $this->db->fetch($sql);
        $stats['refunded'] = $result['count'];
        
        // Total revenue
        $sql = "SELECT SUM(amount) as total_revenue FROM rent_payments WHERE payment_status = 'completed'";
        $result = $this->db->fetch($sql);
        $stats['total_revenue'] = $result['total_revenue'] ?? 0;
        
        // Total commission
        $sql = "SELECT SUM(commission_amount) as total_commission FROM rent_payments WHERE payment_status = 'completed'";
        $result = $this->db->fetch($sql);
        $stats['total_commission'] = $result['total_commission'] ?? 0;
        
        // Total owner payouts
        $sql = "SELECT SUM(owner_payout_amount) as total_payouts FROM rent_payments WHERE payment_status = 'completed'";
        $result = $this->db->fetch($sql);
        $stats['total_payouts'] = $result['total_payouts'] ?? 0;
        
        // Average payment amount
        $sql = "SELECT AVG(amount) as avg_amount FROM rent_payments WHERE payment_status = 'completed'";
        $result = $this->db->fetch($sql);
        $stats['avg_amount'] = $result['avg_amount'] ?? 0;
        
        // Recent payments (last 30 days)
        $sql = "SELECT COUNT(*) as count FROM rent_payments WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        $result = $this->db->fetch($sql);
        $stats['recent_payments'] = $result['count'];
        
        return $stats;
    }
    
    /**
     * Search payments
     */
    public function search($query, $status = null, $page = 1, $limit = 20) {
        $offset = ($page - 1) * $limit;
        $where_conditions = ["1=1"];
        $params = [];
        
        if ($status) {
            $where_conditions[] = "p.payment_status = ?";
            $params[] = $status;
        }
        
        if (!empty($query)) {
            $where_conditions[] = "(c.name LIKE ? OR c.phone LIKE ? OR c.email LIKE ? OR pr.title LIKE ? OR pr.address LIKE ? OR pr.city LIKE ? OR o.name LIKE ? OR p.payment_reference LIKE ?)";
            $search_param = "%{$query}%";
            $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param, $search_param, $search_param, $search_param, $search_param]);
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        
        $sql = "SELECT p.*, 
                       c.name as customer_name, c.phone as customer_phone, c.email as customer_email,
                       pr.title as property_title, pr.address as property_address, pr.city as property_city,
                       o.name as owner_name, o.phone as owner_phone,
                       b.start_date, b.end_date, b.monthly_rent
                FROM rent_payments p
                JOIN users c ON p.customer_id = c.id
                JOIN properties pr ON p.property_id = pr.id
                JOIN users o ON p.owner_id = o.id
                JOIN rental_bookings b ON p.booking_id = b.id
                WHERE {$where_clause} 
                ORDER BY p.created_at DESC 
                LIMIT ? OFFSET ?";
        
        $params[] = $limit;
        $params[] = $offset;
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * Get overdue payments
     */
    public function getOverdue($page = 1, $limit = 20) {
        $offset = ($page - 1) * $limit;
        $sql = "SELECT p.*, 
                       c.name as customer_name, c.phone as customer_phone, c.email as customer_email,
                       pr.title as property_title, pr.address as property_address, pr.city as property_city,
                       o.name as owner_name, o.phone as owner_phone,
                       b.start_date, b.end_date, b.monthly_rent
                FROM rent_payments p
                JOIN users c ON p.customer_id = c.id
                JOIN properties pr ON p.property_id = pr.id
                JOIN users o ON p.owner_id = o.id
                JOIN rental_bookings b ON p.booking_id = b.id
                WHERE p.payment_status = 'pending' AND p.due_date < CURDATE()
                ORDER BY p.due_date ASC 
                LIMIT ? OFFSET ?";
        
        return $this->db->fetchAll($sql, [$limit, $offset]);
    }
    
    /**
     * Get upcoming payments
     */
    public function getUpcoming($page = 1, $limit = 20) {
        $offset = ($page - 1) * $limit;
        $sql = "SELECT p.*, 
                       c.name as customer_name, c.phone as customer_phone, c.email as customer_email,
                       pr.title as property_title, pr.address as property_address, pr.city as property_city,
                       o.name as owner_name, o.phone as owner_phone,
                       b.start_date, b.end_date, b.monthly_rent
                FROM rent_payments p
                JOIN users c ON p.customer_id = c.id
                JOIN properties pr ON p.property_id = pr.id
                JOIN users o ON p.owner_id = o.id
                JOIN rental_bookings b ON p.booking_id = b.id
                WHERE p.payment_status = 'pending' AND p.due_date >= CURDATE()
                ORDER BY p.due_date ASC 
                LIMIT ? OFFSET ?";
        
        return $this->db->fetchAll($sql, [$limit, $offset]);
    }
    
    /**
     * Get recent payments
     */
    public function getRecentPayments($days = 30) {
        $sql = "SELECT p.*, 
                       c.name as customer_name, c.phone as customer_phone, c.email as customer_email,
                       pr.title as property_title, pr.address as property_address, pr.city as property_city,
                       o.name as owner_name, o.phone as owner_phone,
                       b.start_date, b.end_date, b.monthly_rent
                FROM rent_payments p
                JOIN users c ON p.customer_id = c.id
                JOIN properties pr ON p.property_id = pr.id
                JOIN users o ON p.owner_id = o.id
                JOIN rental_bookings b ON p.booking_id = b.id
                WHERE p.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY) 
                ORDER BY p.created_at DESC";
        return $this->db->fetchAll($sql, [$days]);
    }
    
    /**
     * Get revenue analytics
     */
    public function getRevenueAnalytics($date_from, $date_to) {
        $sql = "SELECT DATE(created_at) as date, SUM(amount) as revenue 
                FROM rent_payments 
                WHERE payment_status = 'completed' 
                AND created_at BETWEEN ? AND ? 
                GROUP BY DATE(created_at) 
                ORDER BY date";
        $results = $this->db->fetchAll($sql, [$date_from, $date_to]);
        $analytics = [];
        foreach ($results as $result) {
            $analytics[$result['date']] = $result['revenue'];
        }
        return $analytics;
    }
    
    /**
     * Get payment method distribution
     */
    public function getPaymentMethodDistribution() {
        $sql = "SELECT payment_method, COUNT(*) as count FROM rent_payments GROUP BY payment_method";
        $results = $this->db->fetchAll($sql);
        $distribution = [];
        foreach ($results as $result) {
            $distribution[$result['payment_method']] = $result['count'];
        }
        return $distribution;
    }
    
    /**
     * Get monthly trends
     */
    public function getMonthlyTrends($months = 12) {
        $sql = "SELECT DATE_FORMAT(created_at, '%Y-%m') as month, SUM(amount) as revenue 
                FROM rent_payments 
                WHERE payment_status = 'completed' 
                AND created_at >= DATE_SUB(NOW(), INTERVAL ? MONTH) 
                GROUP BY DATE_FORMAT(created_at, '%Y-%m') 
                ORDER BY month";
        $results = $this->db->fetchAll($sql, [$months]);
        $trends = [];
        foreach ($results as $result) {
            $trends[$result['month']] = $result['revenue'];
        }
        return $trends;
    }
    
    /**
     * Get payment by transaction ID
     */
    public function getByTransactionId($transaction_id) {
        $sql = "SELECT * FROM rent_payments WHERE payment_reference = ? LIMIT 1";
        return $this->db->fetch($sql, [$transaction_id]);
    }
    
    /**
     * Get monthly earnings for owner
     */
    public function getMonthlyEarnings($owner_id, $months = 12) {
        $sql = "SELECT DATE_FORMAT(created_at, '%Y-%m') as month, 
                       SUM(owner_payout_amount) as earnings
                FROM rent_payments 
                WHERE owner_id = ? 
                AND payment_status = 'completed' 
                AND created_at >= DATE_SUB(NOW(), INTERVAL ? MONTH) 
                GROUP BY DATE_FORMAT(created_at, '%Y-%m') 
                ORDER BY month";
        
        $results = $this->db->fetchAll($sql, [$owner_id, $months]);
        $earnings = [];
        
        // Fill in missing months with 0 earnings
        for ($i = $months - 1; $i >= 0; $i--) {
            $month = date('Y-m', strtotime("-{$i} months"));
            $earnings[$month] = 0;
        }
        
        // Fill in actual earnings
        foreach ($results as $result) {
            $earnings[$result['month']] = (float)$result['earnings'];
        }
        
        return $earnings;
    }
    
    /**
     * Get payment statistics for owner
     */
    public function getOwnerStats($owner_id) {
        $stats = [];
        
        // Total payments
        $sql = "SELECT COUNT(*) as count FROM rent_payments WHERE owner_id = ?";
        $result = $this->db->fetch($sql, [$owner_id]);
        $stats['total_payments'] = $result['count'];
        
        // Completed payments
        $sql = "SELECT COUNT(*) as count FROM rent_payments WHERE owner_id = ? AND payment_status = 'completed'";
        $result = $this->db->fetch($sql, [$owner_id]);
        $stats['completed_payments'] = $result['count'];
        
        // Pending payments
        $sql = "SELECT COUNT(*) as count FROM rent_payments WHERE owner_id = ? AND payment_status = 'pending'";
        $result = $this->db->fetch($sql, [$owner_id]);
        $stats['pending_payments'] = $result['count'];
        
        // Failed payments
        $sql = "SELECT COUNT(*) as count FROM rent_payments WHERE owner_id = ? AND payment_status = 'failed'";
        $result = $this->db->fetch($sql, [$owner_id]);
        $stats['failed_payments'] = $result['count'];
        
        // Refunded payments
        $sql = "SELECT COUNT(*) as count FROM rent_payments WHERE owner_id = ? AND payment_status = 'refunded'";
        $result = $this->db->fetch($sql, [$owner_id]);
        $stats['refunded_payments'] = $result['count'];
        
        // Total earnings
        $sql = "SELECT SUM(owner_payout_amount) as total_earnings FROM rent_payments WHERE owner_id = ? AND payment_status = 'completed'";
        $result = $this->db->fetch($sql, [$owner_id]);
        $stats['total_earnings'] = $result['total_earnings'] ?? 0;
        
        // Total commission paid
        $sql = "SELECT SUM(commission_amount) as total_commission FROM rent_payments WHERE owner_id = ? AND payment_status = 'completed'";
        $result = $this->db->fetch($sql, [$owner_id]);
        $stats['total_commission'] = $result['total_commission'] ?? 0;
        
        // Average payment amount
        $sql = "SELECT AVG(owner_payout_amount) as avg_payout FROM rent_payments WHERE owner_id = ? AND payment_status = 'completed'";
        $result = $this->db->fetch($sql, [$owner_id]);
        $stats['avg_payout'] = $result['avg_payout'] ?? 0;
        
        // This month's earnings
        $sql = "SELECT SUM(owner_payout_amount) as monthly_earnings 
                FROM rent_payments 
                WHERE owner_id = ? AND payment_status = 'completed' 
                AND MONTH(created_at) = MONTH(CURRENT_DATE()) 
                AND YEAR(created_at) = YEAR(CURRENT_DATE())";
        $result = $this->db->fetch($sql, [$owner_id]);
        $stats['monthly_earnings'] = $result['monthly_earnings'] ?? 0;
        
        // Recent payments (last 30 days)
        $sql = "SELECT COUNT(*) as count FROM rent_payments WHERE owner_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        $result = $this->db->fetch($sql, [$owner_id]);
        $stats['recent_payments'] = $result['count'];
        
        return $stats;
    }
    
    /**
     * Get owner earnings analytics
     */
    public function getOwnerEarningsAnalytics($owner_id, $date_from, $date_to) {
        $analytics = [];
        
        // Total earnings in date range
        $sql = "SELECT SUM(owner_payout_amount) as total_earnings 
                FROM rent_payments 
                WHERE owner_id = ? AND payment_status = 'completed' 
                AND DATE(created_at) BETWEEN ? AND ?";
        $result = $this->db->fetch($sql, [$owner_id, $date_from, $date_to]);
        $analytics['total_earnings'] = $result['total_earnings'] ?? 0;
        
        // Total commission paid
        $sql = "SELECT SUM(commission_amount) as total_commission 
                FROM rent_payments 
                WHERE owner_id = ? AND payment_status = 'completed' 
                AND DATE(created_at) BETWEEN ? AND ?";
        $result = $this->db->fetch($sql, [$owner_id, $date_from, $date_to]);
        $analytics['total_commission'] = $result['total_commission'] ?? 0;
        
        // Payment count
        $sql = "SELECT COUNT(*) as payment_count 
                FROM rent_payments 
                WHERE owner_id = ? AND payment_status = 'completed' 
                AND DATE(created_at) BETWEEN ? AND ?";
        $result = $this->db->fetch($sql, [$owner_id, $date_from, $date_to]);
        $analytics['payment_count'] = $result['payment_count'];
        
        // Average payment amount
        $sql = "SELECT AVG(owner_payout_amount) as avg_payment 
                FROM rent_payments 
                WHERE owner_id = ? AND payment_status = 'completed' 
                AND DATE(created_at) BETWEEN ? AND ?";
        $result = $this->db->fetch($sql, [$owner_id, $date_from, $date_to]);
        $analytics['avg_payment'] = $result['avg_payment'] ?? 0;
        
        // Earnings by property
        $sql = "SELECT pr.title as property_title, 
                       SUM(p.owner_payout_amount) as earnings,
                       COUNT(p.id) as payment_count
                FROM rent_payments p
                JOIN properties pr ON p.property_id = pr.id
                WHERE p.owner_id = ? AND p.payment_status = 'completed' 
                AND DATE(p.created_at) BETWEEN ? AND ?
                GROUP BY pr.id, pr.title
                ORDER BY earnings DESC";
        $analytics['by_property'] = $this->db->fetchAll($sql, [$owner_id, $date_from, $date_to]);
        
        // Daily earnings breakdown
        $sql = "SELECT DATE(created_at) as date, 
                       SUM(owner_payout_amount) as daily_earnings
                FROM rent_payments 
                WHERE owner_id = ? AND payment_status = 'completed' 
                AND DATE(created_at) BETWEEN ? AND ?
                GROUP BY DATE(created_at)
                ORDER BY date";
        $results = $this->db->fetchAll($sql, [$owner_id, $date_from, $date_to]);
        $analytics['daily_earnings'] = [];
        foreach ($results as $result) {
            $analytics['daily_earnings'][$result['date']] = (float)$result['daily_earnings'];
        }
        
        return $analytics;
    }
}
?>
