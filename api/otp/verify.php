<?php
/**
 * Verify OTP API Endpoint
 * Handles OTP verification for login and registration
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
    $required_fields = ['phone', 'otp_code'];
    foreach ($required_fields as $field) {
        if (empty($input[$field])) {
            json_response(['success' => false, 'message' => "Field '{$field}' is required"], 400);
        }
    }

    $phone = sanitize_input($input['phone']);
    $otp_code = sanitize_input($input['otp_code']);

    // Validate phone number
    if (!validate_phone($phone)) {
        json_response(['success' => false, 'message' => 'Invalid phone number format'], 400);
    }

    // Verify OTP
    $otp_service = new OTPService();
    $verification_result = $otp_service->verifyOTP($phone, $otp_code);

    if ($verification_result) {
        // Get user type from session or input
        $user_type = $_SESSION['otp_user_type'] ?? $input['user_type'] ?? 'customer';
        
        // Get user information
        $user_model = new User();
        $user = $user_model->getByPhone($phone);
        
        if ($user) {
            // Create session
            $session_token = generate_token();
            $expires_at = date('Y-m-d H:i:s', time() + SESSION_TIMEOUT);
            
            // Store session in database
            $database->query(
                "INSERT INTO user_sessions (user_id, session_token, expires_at, ip_address) VALUES (?, ?, ?, ?)",
                [$user['id'], $session_token, $expires_at, get_client_ip()]
            );
            
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_type'] = $user['user_type'];
            $_SESSION['session_token'] = $session_token;
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_phone'] = $user['phone'];
            $_SESSION['user_email'] = $user['email'];
            
            // Determine redirect URL based on user type
            $redirect_url = '/frontend/index.php';
            switch ($user['user_type']) {
                case 'admin':
                    $redirect_url = '/frontend/admin/dashboard/index.php';
                    break;
                case 'owner':
                    $redirect_url = '/frontend/owner/dashboard/index.php';
                    break;
                case 'customer':
                    $redirect_url = '/frontend/dashboard.php';
                    break;
            }
            
            json_response([
                'success' => true,
                'message' => 'OTP verified successfully',
                'redirect' => $redirect_url,
                'user' => [
                    'id' => $user['id'],
                    'name' => $user['name'],
                    'phone' => $user['phone'],
                    'email' => $user['email'],
                    'user_type' => $user['user_type']
                ]
            ]);
        } else {
            json_response([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }
    } else {
        json_response([
            'success' => false,
            'message' => 'Invalid OTP code'
        ], 400);
    }

} catch (Exception $e) {
    error_log("OTP Verify Error: " . $e->getMessage());
    json_response([
        'success' => false,
        'message' => 'An error occurred while verifying OTP'
    ], 500);
}
?>
