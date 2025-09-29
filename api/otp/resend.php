<?php
/**
 * Resend OTP API Endpoint
 * Handles OTP resending
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

    // Check if phone is in session
    if (!isset($_SESSION['otp_phone'])) {
        json_response(['success' => false, 'message' => 'No OTP session found'], 400);
    }

    $phone = $_SESSION['otp_phone'];
    $user_type = $_SESSION['otp_user_type'] ?? 'customer';

    // Send new OTP
    $otp_service = new OTPService();
    $otp_code = $otp_service->generateOTP();
    $result = $otp_service->sendOTP($phone, $otp_code);

    if ($result['success']) {
        json_response([
            'success' => true,
            'message' => 'OTP sent successfully to your phone number'
        ]);
    } else {
        json_response([
            'success' => false,
            'message' => $result['message'] ?? 'Failed to send OTP'
        ], 500);
    }

} catch (Exception $e) {
    error_log("OTP Resend Error: " . $e->getMessage());
    json_response([
        'success' => false,
        'message' => 'An error occurred while sending OTP'
    ], 500);
}
?>
