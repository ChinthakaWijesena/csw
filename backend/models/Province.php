<?php
/**
 * Province Model (minimal)
 */

class Province {
    /** @var Database */
    private $database;

    public function __construct() {
        global $database;
        $this->database = $database;
    }

    /**
     * Get all active provinces.
     */
    public function getAllActive() {
        $sql = "SELECT id, name, code FROM provinces WHERE is_active = 1 ORDER BY sort_order, name";
        return $this->database->fetchAll($sql);
    }
}

?>


