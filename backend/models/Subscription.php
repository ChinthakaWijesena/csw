<?php
/**
 * Subscription Model
 * Handles monthly payment subscriptions for properties
 */

require_once __DIR__ . '/../../config/config.php';

class Subscription {
    private $db;
    
    public function __construct() {
        global $database;
        $this->db = $database;
    }
    
    /**
     * Create a new subscription
     */
    public function create($data) {
        $sql = "INSERT INTO subscriptions (
                    property_id, customer_id, owner_id, monthly_amount, 
                    start_date, next_payment_date, status, payment_method,
                    auto_renew, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $params = [
            $data['property_id'],
            $data['customer_id'],
            $data['owner_id'],
            $data['monthly_amount'],
            $data['start_date'],
            $data['next_payment_date'],
            $data['status'] ?? 'active',
            $data['payment_method'] ?? 'payhere',
            $data['auto_renew'] ?? 1
        ];
        
        $this->db->query($sql, $params);
        return $this->db->lastInsertId();
    }
    
    /**
     * Get subscription by ID
     */
    public function getById($id) {
        $sql = "SELECT s.*, p.title as property_title, u.name as customer_name, o.name as owner_name
                FROM subscriptions s
                JOIN properties p ON s.property_id = p.id
                JOIN users u ON s.customer_id = u.id
                JOIN users o ON s.owner_id = o.id
                WHERE s.id = ?";
        return $this->db->fetch($sql, [$id]);
    }
    
    /**
     * Get active subscriptions for a customer
     */
    public function getByCustomer($customer_id) {
        $sql = "SELECT 
                    s.*, 
                    p.title AS property_title,
                    '' AS address,
                    c.name AS city,
                    d.name AS district,
                    (SELECT image_url FROM property_images WHERE property_id = p.id AND is_primary = 1 LIMIT 1) AS property_image
                FROM subscriptions s
                JOIN properties p ON s.property_id = p.id
                LEFT JOIN cities c ON p.city_id = c.id
                LEFT JOIN districts d ON p.district_id = d.id
                WHERE s.customer_id = ? AND s.status = 'active'
                ORDER BY s.created_at DESC";
        return $this->db->fetchAll($sql, [$customer_id]);
    }
    
    /**
     * Get subscriptions for a property owner
     */
    public function getByOwner($owner_id) {
        $sql = "SELECT s.*, p.title as property_title, u.name as customer_name,
                       u.email as customer_email, u.phone as customer_phone
                FROM subscriptions s
                JOIN properties p ON s.property_id = p.id
                JOIN users u ON s.customer_id = u.id
                WHERE s.owner_id = ? AND s.status = 'active'
                ORDER BY s.created_at DESC";
        return $this->db->fetchAll($sql, [$owner_id]);
    }
    
    /**
     * Get subscriptions due for payment
     */
    public function getDueForPayment($date = null) {
        if (!$date) {
            $date = date('Y-m-d');
        }
        
        $sql = "SELECT s.*, p.title as property_title, u.name as customer_name,
                       u.email as customer_email, u.phone as customer_phone
                FROM subscriptions s
                JOIN properties p ON s.property_id = p.id
                JOIN users u ON s.customer_id = u.id
                WHERE s.status = 'active' AND s.next_payment_date <= ? AND s.auto_renew = 1
                ORDER BY s.next_payment_date ASC";
        return $this->db->fetchAll($sql, [$date]);
    }
    
    /**
     * Update subscription
     */
    public function update($id, $data) {
        $fields = [];
        $params = [];
        
        foreach ($data as $key => $value) {
            $fields[] = "{$key} = ?";
            $params[] = $value;
        }
        
        $params[] = $id;
        $sql = "UPDATE subscriptions SET " . implode(', ', $fields) . " WHERE id = ?";
        
        return $this->db->query($sql, $params);
    }
    
    /**
     * Cancel subscription
     */
    public function cancel($id, $reason = '') {
        return $this->update($id, [
            'status' => 'cancelled',
            'cancelled_at' => date('Y-m-d H:i:s'),
            'cancellation_reason' => $reason
        ]);
    }
    
    /**
     * Process monthly payment
     */
    public function processMonthlyPayment($subscription_id) {
        $subscription = $this->getById($subscription_id);
        
        if (!$subscription || $subscription['status'] !== 'active') {
            return false;
        }
        
        // Create payment record
        $payment_model = new Payment();
        $payment_id = $payment_model->create([
            'subscription_id' => $subscription_id,
            'customer_id' => $subscription['customer_id'],
            'property_id' => $subscription['property_id'],
            'owner_id' => $subscription['owner_id'],
            'amount' => $subscription['monthly_amount'],
            'payment_method' => $subscription['payment_method'],
            'payment_status' => 'pending',
            'due_date' => $subscription['next_payment_date'],
            'is_recurring' => 1
        ]);
        
        // Update next payment date
        $next_payment_date = date('Y-m-d', strtotime($subscription['next_payment_date'] . ' +1 month'));
        $this->update($subscription_id, [
            'next_payment_date' => $next_payment_date,
            'last_payment_date' => date('Y-m-d H:i:s')
        ]);
        
        return $payment_id;
    }
    
    /**
     * Get subscription statistics
     */
    public function getStats($owner_id = null) {
        $where_clause = $owner_id ? "WHERE s.owner_id = ?" : "";
        $params = $owner_id ? [$owner_id] : [];
        
        $sql = "SELECT 
                    COUNT(*) as total_subscriptions,
                    SUM(CASE WHEN s.status = 'active' THEN 1 ELSE 0 END) as active_subscriptions,
                    SUM(CASE WHEN s.status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_subscriptions,
                    SUM(CASE WHEN s.status = 'active' THEN s.monthly_amount ELSE 0 END) as monthly_revenue
                FROM subscriptions s {$where_clause}";
        
        return $this->db->fetch($sql, $params);
    }
    
    /**
     * Get subscription history
     */
    public function getHistory($subscription_id) {
        $sql = "SELECT p.*, s.status as payment_status, s.paid_date
                FROM rent_payments p
                JOIN subscriptions s ON p.subscription_id = s.id
                WHERE s.id = ?
                ORDER BY p.due_date DESC";
        return $this->db->fetchAll($sql, [$subscription_id]);
    }
}
?>
