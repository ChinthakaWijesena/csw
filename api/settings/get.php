<?php
/**
 * Settings Get API Endpoint
 * Retrieves current settings
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../backend/controllers/SettingsController.php';

// Set JSON response header
header('Content-Type: application/json');

// Check if user is logged in and is admin
if (!is_logged_in() || $_SESSION['user_type'] !== 'admin') {
    json_response(['success' => false, 'message' => 'Unauthorized access'], 401);
}

try {
    $controller = new SettingsController();
    $settings = $controller->getAllSettings();
    
    json_response([
        'success' => true,
        'settings' => $settings
    ]);

} catch (Exception $e) {
    error_log("Settings Get API Error: " . $e->getMessage());
    json_response([
        'success' => false,
        'message' => 'An error occurred while retrieving settings'
    ], 500);
}
?>
