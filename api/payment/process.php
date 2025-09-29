<?php
/**
 * Payment Processing API
 * Handles PayHere payment processing
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../backend/models/PayHere.php';
require_once __DIR__ . '/../../backend/models/Booking.php';
require_once __DIR__ . '/../../backend/models/Payment.php';

$response = ['success' => false, 'message' => '', 'data' => []];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Only POST method allowed');
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid JSON input');
    }
    
    // Validate required fields
    if (empty($input['action'])) {
        throw new Exception('Action is required');
    }
    
    // Rate limiting (basic implementation)
    $client_ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $rate_limit_key = 'payment_rate_limit_' . $client_ip;
    
    if (isset($_SESSION[$rate_limit_key])) {
        $last_request = $_SESSION[$rate_limit_key];
        if (time() - $last_request < 5) { // 5 second rate limit
            throw new Exception('Rate limit exceeded. Please wait before making another request.');
        }
    }
    
    $_SESSION[$rate_limit_key] = time();
    
    $action = $input['action'] ?? '';
    
    switch ($action) {
        case 'initiate_payment':
            $response = handleInitiatePayment($input);
            break;
            
        case 'verify_payment':
            $response = handleVerifyPayment($input);
            break;
            
        default:
            throw new Exception('Invalid action');
    }
    
} catch (Exception $e) {
    $response = [
        'success' => false,
        'message' => $e->getMessage(),
        'data' => []
    ];
}

echo json_encode($response);

/**
 * Handle payment initiation
 */
function handleInitiatePayment($input) {
    global $database;
    
    $booking_id = $input['booking_id'] ?? '';
    $amount = $input['amount'] ?? 0;
    $customer_name = $input['customer_name'] ?? '';
    $customer_email = $input['customer_email'] ?? '';
    $customer_phone = $input['customer_phone'] ?? '';
    
    // Validate required fields
    if (empty($booking_id) || empty($amount) || empty($customer_name)) {
        throw new Exception('Missing required fields: booking_id, amount, and customer_name are required');
    }
    
    // Validate data types and ranges
    if (!is_numeric($booking_id) || $booking_id <= 0) {
        throw new Exception('Invalid booking ID');
    }
    
    if (!is_numeric($amount) || $amount <= 0) {
        throw new Exception('Invalid amount');
    }
    
    if (strlen($customer_name) < 2 || strlen($customer_name) > 100) {
        throw new Exception('Customer name must be between 2 and 100 characters');
    }
    
    if (!empty($customer_email) && !filter_var($customer_email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email format');
    }
    
    if (!empty($customer_phone) && !preg_match('/^[0-9+\-\s()]+$/', $customer_phone)) {
        throw new Exception('Invalid phone number format');
    }
    
    // Get booking details
    $booking_model = new Booking();
    $booking = $booking_model->getById($booking_id);
    
    if (!$booking) {
        throw new Exception('Booking not found');
    }
    
    // Generate order ID
    $order_id = 'BK' . $booking_id . '_' . time();
    
    // Prepare payment data
    $payhere = new PayHere();
    $payment_data = $payhere->generatePaymentData([
        'amount' => $amount,
        'order_id' => $order_id,
        'item_name' => 'Property Booking - ' . $booking['property_title'],
        'customer_name' => $customer_name,
        'customer_email' => $customer_email,
        'customer_phone' => $customer_phone,
        'return_url' => APP_URL . '/frontend/payment-success.php',
        'cancel_url' => APP_URL . '/frontend/payment-cancel.php',
        'notify_url' => APP_URL . '/api/payment/notify.php'
    ]);
    
    // Create payment record
    $payment_model = new Payment();
    $payment_id = $payment_model->create([
        'booking_id' => $booking_id,
        'customer_id' => $booking['customer_id'],
        'property_id' => $booking['property_id'],
        'owner_id' => $booking['owner_id'],
        'amount' => $amount,
        'payment_method' => 'payhere',
        'payment_status' => 'pending',
        'payment_reference' => $order_id,
        'due_date' => date('Y-m-d H:i:s', time() + 3600), // 1 hour from now
        'commission_amount' => $amount * 0.05, // 5% commission
        'owner_payout_amount' => $amount * 0.95 // 95% to owner
    ]);
    
    return [
        'success' => true,
        'message' => 'Payment initiated successfully',
        'data' => [
            'payment_id' => $payment_id,
            'order_id' => $order_id,
            'payment_form' => $payhere->createPaymentForm($payment_data),
            'payment_url' => $payhere->getPaymentUrl()
        ]
    ];
}

/**
 * Handle payment verification
 */
function handleVerifyPayment($input) {
    $order_id = $input['order_id'] ?? '';
    $payment_id = $input['payment_id'] ?? '';
    
    if (empty($order_id) || empty($payment_id)) {
        throw new Exception('Missing order ID or payment ID');
    }
    
    // Get payment record
    $payment_model = new Payment();
    $payment = $payment_model->getById($payment_id);
    
    if (!$payment) {
        throw new Exception('Payment record not found');
    }
    
    return [
        'success' => true,
        'message' => 'Payment verification completed',
        'data' => [
            'payment' => $payment,
            'status' => $payment['status']
        ]
    ];
}
?>
