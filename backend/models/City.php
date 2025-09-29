<?php
/**
 * City Model
 * Handles city-related database operations
 */

require_once __DIR__ . '/../../config/config.php';

class City {
    private $db;
    
    public function __construct() {
        global $database;
        $this->db = $database;
    }
    
    /**
     * Get cities by district ID
     */
    public function getByDistrict($district_id) {
        $sql = "SELECT * FROM cities WHERE district_id = ? AND is_active = 1 ORDER BY sort_order ASC, name ASC";
        return $this->db->fetchAll($sql, [$district_id]);
    }
    
    /**
     * Get city by ID
     */
    public function getById($id) {
        $sql = "SELECT * FROM cities WHERE id = ?";
        return $this->db->fetch($sql, [$id]);
    }
    
    /**
     * Get city by code
     */
    public function getByCode($code) {
        $sql = "SELECT * FROM cities WHERE code = ?";
        return $this->db->fetch($sql, [$code]);
    }
    
    /**
     * Get all active cities
     */
    public function getAllActive() {
        $sql = "SELECT c.*, d.name as district_name, p.name as province_name 
                FROM cities c 
                JOIN districts d ON c.district_id = d.id 
                JOIN provinces p ON d.province_id = p.id 
                WHERE c.is_active = 1 
                ORDER BY p.sort_order ASC, d.sort_order ASC, c.sort_order ASC, c.name ASC";
        return $this->db->fetchAll($sql);
    }
}
?>
