<?php
/**
 * Send OTP API Endpoint
 * Handles OTP sending for login and registration
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/OTPService.php';

// Set JSON response header
header('Content-Type: application/json');

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

    // Validate required fields
    $required_fields = ['phone', 'user_type'];
    foreach ($required_fields as $field) {
        if (empty($input[$field])) {
            json_response(['success' => false, 'message' => "Field '{$field}' is required"], 400);
        }
    }

    $phone = sanitize_input($input['phone']);
    $user_type = sanitize_input($input['user_type']);

    // Validate user type
    if (!in_array($user_type, ['customer', 'owner', 'admin'])) {
        json_response(['success' => false, 'message' => 'Invalid user type'], 400);
    }

    // Validate phone number
    if (!validate_phone($phone)) {
        json_response(['success' => false, 'message' => 'Invalid phone number format'], 400);
    }

    // Check if user exists for login
    if ($user_type !== 'admin') {
        $user_model = new User();
        $user = $user_model->getByPhone($phone);
        
        if (!$user) {
            json_response(['success' => false, 'message' => 'User not found. Please register first.'], 404);
        }
    }

    // Send OTP
    $otp_service = new OTPService();
    $otp_code = $otp_service->generateOTP();
    $result = $otp_service->sendOTP($phone, $otp_code);

    if ($result['success']) {
        // Store session data
        $_SESSION['otp_phone'] = $result['formatted_phone'] ?? $phone;
        $_SESSION['otp_user_type'] = $user_type;
        
        json_response([
            'success' => true,
            'message' => 'OTP sent successfully to your phone number',
            'formatted_phone' => $result['formatted_phone'] ?? $phone
        ]);
    } else {
        json_response([
            'success' => false,
            'message' => $result['message'] ?? 'Failed to send OTP'
        ], 500);
    }

} catch (Exception $e) {
    error_log("OTP Send Error: " . $e->getMessage());
    json_response([
        'success' => false,
        'message' => 'An error occurred while sending OTP'
    ], 500);
}
?>
