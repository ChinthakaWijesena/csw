<?php
/**
 * Province Model
 * Handles province-related database operations
 */

require_once __DIR__ . '/../../config/config.php';

class Province {
    private $db;
    
    public function __construct() {
        global $database;
        $this->db = $database;
    }
    
    /**
     * Get all active provinces
     */
    public function getAllActive() {
        $sql = "SELECT * FROM provinces WHERE is_active = 1 ORDER BY sort_order ASC, name ASC";
        return $this->db->fetchAll($sql);
    }
    
    /**
     * Get province by ID
     */
    public function getById($id) {
        $sql = "SELECT * FROM provinces WHERE id = ?";
        return $this->db->fetch($sql, [$id]);
    }
    
    /**
     * Get province by code
     */
    public function getByCode($code) {
        $sql = "SELECT * FROM provinces WHERE code = ?";
        return $this->db->fetch($sql, [$code]);
    }
}
?>
