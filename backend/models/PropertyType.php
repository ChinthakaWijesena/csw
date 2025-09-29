<?php
/**
 * Property Type Model
 * Handles property type-related database operations
 */

require_once __DIR__ . '/../../config/config.php';

class PropertyType {
    private $db;
    
    public function __construct() {
        global $database;
        $this->db = $database;
    }
    
    /**
     * Get all active property types
     */
    public function getAllActive() {
        $sql = "SELECT * FROM property_types WHERE is_active = 1 ORDER BY sort_order ASC, type_name ASC";
        return $this->db->fetchAll($sql);
    }
    
    /**
     * Get property type by key
     */
    public function getByKey($type_key) {
        $sql = "SELECT * FROM property_types WHERE type_key = ? AND is_active = 1";
        return $this->db->fetch($sql, [$type_key]);
    }
    
    /**
     * Get property type by ID
     */
    public function getById($id) {
        $sql = "SELECT * FROM property_types WHERE id = ? AND is_active = 1";
        return $this->db->fetch($sql, [$id]);
    }
    
    /**
     * Create a new property type
     */
    public function create($data) {
        $sql = "INSERT INTO property_types (type_key, type_name, description, icon, is_active, sort_order) 
                VALUES (?, ?, ?, ?, ?, ?)";
        
        $params = [
            $data['type_key'],
            $data['type_name'],
            $data['description'] ?? null,
            $data['icon'] ?? null,
            $data['is_active'] ?? 1,
            $data['sort_order'] ?? 0
        ];
        
        $this->db->query($sql, $params);
        return $this->db->lastInsertId();
    }
    
    /**
     * Update property type
     */
    public function update($id, $data) {
        $fields = [];
        $params = [];
        
        if (isset($data['type_name'])) {
            $fields[] = "type_name = ?";
            $params[] = $data['type_name'];
        }
        
        if (isset($data['description'])) {
            $fields[] = "description = ?";
            $params[] = $data['description'];
        }
        
        if (isset($data['icon'])) {
            $fields[] = "icon = ?";
            $params[] = $data['icon'];
        }
        
        if (isset($data['is_active'])) {
            $fields[] = "is_active = ?";
            $params[] = $data['is_active'];
        }
        
        if (isset($data['sort_order'])) {
            $fields[] = "sort_order = ?";
            $params[] = $data['sort_order'];
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $params[] = $id;
        $sql = "UPDATE property_types SET " . implode(', ', $fields) . " WHERE id = ?";
        
        return $this->db->query($sql, $params);
    }
    
    /**
     * Delete property type (soft delete)
     */
    public function delete($id) {
        $sql = "UPDATE property_types SET is_active = 0 WHERE id = ?";
        return $this->db->query($sql, [$id]);
    }
    
    /**
     * Get property type statistics
     */
    public function getStats() {
        $sql = "SELECT 
                    pt.type_name,
                    pt.type_key,
                    COUNT(p.id) as property_count
                FROM property_types pt
                LEFT JOIN properties p ON pt.type_key = p.property_type
                WHERE pt.is_active = 1
                GROUP BY pt.id, pt.type_name, pt.type_key
                ORDER BY property_count DESC";
        
        return $this->db->fetchAll($sql);
    }
}
?>
