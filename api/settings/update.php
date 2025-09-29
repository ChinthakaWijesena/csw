<?php
/**
 * Settings Update API Endpoint
 * Handles settings updates via AJAX
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
    // Only allow POST requests
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        json_response(['success' => false, 'message' => 'Method not allowed'], 405);
    }

    // Get input data
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        $input = $_POST;
    }

    $action = $input['action'] ?? '';
    $controller = new SettingsController();

    switch ($action) {
        case 'update_general_settings':
            $required_fields = ['app_name', 'app_email', 'app_phone', 'commission_rate'];
            foreach ($required_fields as $field) {
                if (empty($input[$field])) {
                    json_response(['success' => false, 'message' => "Field '{$field}' is required"], 400);
                }
            }
            
            // Validate phone number
            if (!validate_phone($input['app_phone'])) {
                json_response(['success' => false, 'message' => 'Please enter a valid phone number in 07XXXXXXXX format'], 400);
            }
            
            // Validate email
            if (!filter_var($input['app_email'], FILTER_VALIDATE_EMAIL)) {
                json_response(['success' => false, 'message' => 'Please enter a valid email address'], 400);
            }
            
            $result = $controller->updateGeneral($input);
            json_response($result);
            break;
            
        case 'update_payment_settings':
            $result = $controller->updatePayment($input);
            json_response($result);
            break;
            
        case 'update_sms_settings':
            $result = $controller->updateSMS($input);
            json_response($result);
            break;
            
        case 'update_email_settings':
            $result = $controller->updateEmail($input);
            json_response($result);
            break;
            
        case 'update_security_settings':
            $result = $controller->updateSecurity($input);
            json_response($result);
            break;
            
        case 'test_sms':
            $test_phone = $input['test_phone'] ?? '';
            $result = $controller->testSMS($test_phone);
            json_response($result);
            break;
            
        case 'test_email':
            $test_email = $input['test_email'] ?? '';
            $result = $controller->testEmail($test_email);
            json_response($result);
            break;
            
        case 'clear_cache':
            $result = $controller->clearCache();
            json_response($result);
            break;
            
        case 'backup_database':
            $result = $controller->createBackup();
            json_response($result);
            break;
            
        default:
            json_response(['success' => false, 'message' => 'Invalid action'], 400);
    }

} catch (Exception $e) {
    error_log("Settings API Error: " . $e->getMessage());
    json_response([
        'success' => false,
        'message' => 'An error occurred while processing the request'
    ], 500);
}
?>
