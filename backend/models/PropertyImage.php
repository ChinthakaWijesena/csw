<?php
/**
 * Property Image Model
 * Handles property image-related database operations
 */

require_once __DIR__ . '/../../config/config.php';

class PropertyImage {
    private $db;
    
    public function __construct() {
        global $database;
        $this->db = $database;
    }
    
    /**
     * Create a new property image
     */
    public function create($data) {
        $sql = "INSERT INTO property_images (property_id, image_path, is_primary) VALUES (?, ?, ?)";
        
        $params = [
            $data['property_id'],
            $data['image_path'],
            $data['is_primary'] ?? 0
        ];
        
        $this->db->query($sql, $params);
        return $this->db->lastInsertId();
    }
    
    /**
     * Get images by property ID
     */
    public function getByProperty($property_id) {
        $sql = "SELECT * FROM property_images WHERE property_id = ? ORDER BY is_primary DESC, uploaded_at ASC";
        return $this->db->fetchAll($sql, [$property_id]);
    }
    
    /**
     * Get primary image by property ID
     */
    public function getPrimaryByProperty($property_id) {
        $sql = "SELECT * FROM property_images WHERE property_id = ? AND is_primary = 1 LIMIT 1";
        return $this->db->fetch($sql, [$property_id]);
    }
    
    /**
     * Set primary image
     */
    public function setPrimary($image_id, $property_id) {
        // First, unset all primary images for this property
        $sql = "UPDATE property_images SET is_primary = 0 WHERE property_id = ?";
        $this->db->query($sql, [$property_id]);
        
        // Then set the specified image as primary
        $sql = "UPDATE property_images SET is_primary = 1 WHERE id = ? AND property_id = ?";
        return $this->db->query($sql, [$image_id, $property_id]);
    }
    
    /**
     * Delete image
     */
    public function delete($image_id) {
        $sql = "DELETE FROM property_images WHERE id = ?";
        return $this->db->query($sql, [$image_id]);
    }
    
    /**
     * Get image by ID
     */
    public function getById($image_id) {
        $sql = "SELECT * FROM property_images WHERE id = ?";
        return $this->db->fetch($sql, [$image_id]);
    }
    
    /**
     * Update image
     */
    public function update($image_id, $data) {
        $fields = [];
        $params = [];
        
        if (isset($data['image_path'])) {
            $fields[] = "image_path = ?";
            $params[] = $data['image_path'];
        }
        
        if (isset($data['is_primary'])) {
            $fields[] = "is_primary = ?";
            $params[] = $data['is_primary'];
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $params[] = $image_id;
        $sql = "UPDATE property_images SET " . implode(', ', $fields) . " WHERE id = ?";
        
        return $this->db->query($sql, $params);
    }
    
    /**
     * Get image count by property
     */
    public function getCountByProperty($property_id) {
        $sql = "SELECT COUNT(*) as count FROM property_images WHERE property_id = ?";
        $result = $this->db->fetch($sql, [$property_id]);
        return $result['count'];
    }
}
?>
