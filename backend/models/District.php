<?php
/**
 * District Model
 * Handles district-related database operations
 */

require_once __DIR__ . '/../../config/config.php';

class District {
    private $db;
    
    public function __construct() {
        global $database;
        $this->db = $database;
    }
    
    /**
     * Get districts by province ID
     */
    public function getByProvince($province_id) {
        $sql = "SELECT * FROM districts WHERE province_id = ? AND is_active = 1 ORDER BY sort_order ASC, name ASC";
        return $this->db->fetchAll($sql, [$province_id]);
    }
    
    /**
     * Get district by ID
     */
    public function getById($id) {
        $sql = "SELECT * FROM districts WHERE id = ?";
        return $this->db->fetch($sql, [$id]);
    }
    
    /**
     * Get district by code
     */
    public function getByCode($code) {
        $sql = "SELECT * FROM districts WHERE code = ?";
        return $this->db->fetch($sql, [$code]);
    }
    
    /**
     * Get all active districts
     */
    public function getAllActive() {
        $sql = "SELECT d.*, p.name as province_name 
                FROM districts d 
                JOIN provinces p ON d.province_id = p.id 
                WHERE d.is_active = 1 
                ORDER BY p.sort_order ASC, d.sort_order ASC, d.name ASC";
        return $this->db->fetchAll($sql);
    }
}
?>
