<?php
/**
 * User Model
 * Handles user-related database operations
 */

require_once __DIR__ . '/../../config/config.php';

class User {
    private $db;
    
    public function __construct() {
        global $database;
        $this->db = $database;
    }
    
    /**
     * Create a new user
     */
    public function create($data) {
        $sql = "INSERT INTO users (phone, name, email, user_type, is_verified, is_active) 
                VALUES (?, ?, ?, ?, ?, ?)";
        
        $params = [
            $data['phone'],
            $data['name'],
            $data['email'] ?? null,
            $data['user_type'] ?? 'customer',
            $data['is_verified'] ?? 0,
            $data['is_active'] ?? 1
        ];
        
        $this->db->query($sql, $params);
        return $this->db->lastInsertId();
    }
    
    /**
     * Get user by ID
     */
    public function getById($id) {
        $sql = "SELECT * FROM users WHERE id = ? AND is_active = 1";
        return $this->db->fetch($sql, [$id]);
    }
    
    /**
     * Get user by phone number
     */
    public function getByPhone($phone) {
        $sql = "SELECT * FROM users WHERE phone = ? AND is_active = 1";
        return $this->db->fetch($sql, [$phone]);
    }
    
    /**
     * Get user by email
     */
    public function getByEmail($email) {
        $sql = "SELECT * FROM users WHERE email = ? AND is_active = 1";
        return $this->db->fetch($sql, [$email]);
    }
    
    /**
     * Update user information
     */
    public function update($id, $data) {
        $fields = [];
        $params = [];
        
        foreach ($data as $key => $value) {
            if (in_array($key, ['name', 'email', 'is_verified', 'is_active'])) {
                $fields[] = "{$key} = ?";
                $params[] = $value;
            }
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $params[] = $id;
        $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?";
        
        return $this->db->query($sql, $params);
    }
    
    /**
     * Verify user account
     */
    public function verify($id) {
        $sql = "UPDATE users SET is_verified = 1 WHERE id = ?";
        return $this->db->query($sql, [$id]);
    }
    
    /**
     * Deactivate user account
     */
    public function deactivate($id) {
        $sql = "UPDATE users SET is_active = 0 WHERE id = ?";
        return $this->db->query($sql, [$id]);
    }
    
    /**
     * Activate user account
     */
    public function activate($id) {
        $sql = "UPDATE users SET is_active = 1 WHERE id = ?";
        return $this->db->query($sql, [$id]);
    }
    
    /**
     * Delete user permanently
     */
    public function delete($id) {
        $sql = "DELETE FROM users WHERE id = ?";
        return $this->db->query($sql, [$id]);
    }
    
    /**
     * Get all users with pagination
     */
    public function getAll($page = 1, $limit = 20, $user_type = null, $status = 'all') {
        $offset = ($page - 1) * $limit;
        $where_clause = "WHERE 1=1";
        $params = [];
        
        if ($user_type) {
            $where_clause .= " AND user_type = ?";
            $params[] = $user_type;
        }
        
        if ($status === 'active') {
            $where_clause .= " AND is_active = 1";
        } elseif ($status === 'inactive') {
            $where_clause .= " AND is_active = 0";
        }
        // If status is 'all', no additional filter
        
        $sql = "SELECT * FROM users {$where_clause} ORDER BY created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * Get user count
     */
    public function getCount($user_type = null, $status = 'all') {
        $where_clause = "WHERE 1=1";
        $params = [];
        
        if ($user_type) {
            $where_clause .= " AND user_type = ?";
            $params[] = $user_type;
        }
        
        if ($status === 'active') {
            $where_clause .= " AND is_active = 1";
        } elseif ($status === 'inactive') {
            $where_clause .= " AND is_active = 0";
        }
        // If status is 'all', no additional filter
        
        $sql = "SELECT COUNT(*) as count FROM users {$where_clause}";
        $result = $this->db->fetch($sql, $params);
        return $result['count'];
    }
    
    /**
     * Search users
     */
    public function search($query, $user_type = null, $page = 1, $limit = 20, $status = 'all') {
        $offset = ($page - 1) * $limit;
        $where_clause = "WHERE (name LIKE ? OR phone LIKE ? OR email LIKE ?)";
        $params = ["%{$query}%", "%{$query}%", "%{$query}%"];
        
        if ($user_type) {
            $where_clause .= " AND user_type = ?";
            $params[] = $user_type;
        }
        
        if ($status === 'active') {
            $where_clause .= " AND is_active = 1";
        } elseif ($status === 'inactive') {
            $where_clause .= " AND is_active = 0";
        }
        // If status is 'all', no additional filter
        
        $sql = "SELECT * FROM users {$where_clause} ORDER BY created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * Get user statistics
     */
    public function getStats() {
        $stats = [];
        
        // Total users
        $sql = "SELECT COUNT(*) as count FROM users";
        $result = $this->db->fetch($sql);
        $stats['total'] = $result['count'];
        
        // Verified users
        $sql = "SELECT COUNT(*) as count FROM users WHERE is_verified = 1";
        $result = $this->db->fetch($sql);
        $stats['verified'] = $result['count'];
        
        // Active users
        $sql = "SELECT COUNT(*) as count FROM users WHERE is_active = 1";
        $result = $this->db->fetch($sql);
        $stats['active'] = $result['count'];
        
        // Users by type
        $sql = "SELECT user_type, COUNT(*) as count FROM users GROUP BY user_type";
        $results = $this->db->fetchAll($sql);
        $stats['by_type'] = [];
        foreach ($results as $result) {
            $stats['by_type'][$result['user_type']] = $result['count'];
        }
        
        // Specific user type counts
        $sql = "SELECT COUNT(*) as count FROM users WHERE user_type = 'customer'";
        $result = $this->db->fetch($sql);
        $stats['customers'] = $result['count'];
        
        $sql = "SELECT COUNT(*) as count FROM users WHERE user_type = 'owner'";
        $result = $this->db->fetch($sql);
        $stats['owners'] = $result['count'];
        
        $sql = "SELECT COUNT(*) as count FROM users WHERE user_type = 'admin'";
        $result = $this->db->fetch($sql);
        $stats['admins'] = $result['count'];
        
        return $stats;
    }
    
    /**
     * Get recent users
     */
    public function getRecentUsers($days = 30) {
        $sql = "SELECT * FROM users 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY) 
                ORDER BY created_at DESC";
        return $this->db->fetchAll($sql, [$days]);
    }
    
    /**
     * Check if user exists
     */
    public function exists($phone) {
        $sql = "SELECT COUNT(*) as count FROM users WHERE phone = ?";
        $result = $this->db->fetch($sql, [$phone]);
        return $result['count'] > 0;
    }
    
    /**
     * Get user dashboard data
     */
    public function getDashboardData($user_id) {
        $user = $this->getById($user_id);
        if (!$user) {
            return null;
        }
        
        $data = [
            'user' => $user,
            'properties' => 0,
            'bookings' => 0,
            'payments' => 0,
            'total_earnings' => 0
        ];
        
        if ($user['user_type'] === 'owner') {
            // Get property count
            $sql = "SELECT COUNT(*) as count FROM properties WHERE owner_id = ?";
            $result = $this->db->fetch($sql, [$user_id]);
            $data['properties'] = $result['count'];
            
            // Get total earnings
            try {
                $sql = "SELECT SUM(owner_payout_amount) as total FROM rent_payments WHERE owner_id = ? AND payment_status = 'completed'";
                $result = $this->db->fetch($sql, [$user_id]);
                $data['total_earnings'] = (float)($result['total'] ?? 0);
            } catch (Exception $e) {
                // Fallback if legacy table doesn't exist
                $data['total_earnings'] = 0;
            }
            
        } elseif ($user['user_type'] === 'customer') {
            // Get booking count
            try {
                $sql = "SELECT COUNT(*) as count FROM rental_bookings WHERE customer_id = ?";
                $result = $this->db->fetch($sql, [$user_id]);
                $data['bookings'] = $result['count'];
            } catch (Exception $e) {
                $data['bookings'] = 0;
            }
            
            // Get total payments
            try {
                $sql = "SELECT SUM(amount) as total FROM rent_payments WHERE customer_id = ? AND payment_status = 'completed'";
                $result = $this->db->fetch($sql, [$user_id]);
                $data['payments'] = (float)($result['total'] ?? 0);
            } catch (Exception $e) {
                $data['payments'] = 0;
            }
        }
        
        return $data;
    }
}
?>
