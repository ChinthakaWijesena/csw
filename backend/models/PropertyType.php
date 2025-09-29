<?php
/**
 * PropertyType Model (minimal)
 */

class PropertyType {
    /** @var Database */
    private $database;

    public function __construct() {
        global $database;
        $this->database = $database;
    }

    /**
     * Get all active property types.
     * Aliases `name` as `type_name` to match existing templates.
     */
    public function getAllActive() {
        $sql = "SELECT id, name AS type_name FROM property_types WHERE is_active = 1 ORDER BY name";
        return $this->database->fetchAll($sql);
    }

    /**
     * Find a property type by its name.
     */
    public function getByName($name) {
        $sql = "SELECT id, name, description, is_active FROM property_types WHERE name = ? LIMIT 1";
        return $this->database->fetch($sql, [$name]);
    }
}

?>


