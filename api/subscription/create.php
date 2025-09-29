<?php
/**
 * Create Subscription API Endpoint
 * Handles subscription creation
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../backend/models/Property.php';
require_once __DIR__ . '/../../backend/models/Subscription.php';
require_once __DIR__ . '/../../backend/models/PayHere.php';

// Set JSON response header
header('Content-Type: application/json');

try {
    // Only allow POST requests
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        json_response(['success' => false, 'message' => 'Method not allowed'], 405);
    }

    // Check if user is logged in
    if (!is_logged_in()) {
        json_response(['success' => false, 'message' => 'Authentication required'], 401);
    }

    // Get input data
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        $input = $_POST;
    }

    // Validate required fields
    $required_fields = ['property_id', 'start_date', 'monthly_rent'];
    foreach ($required_fields as $field) {
        if (empty($input[$field])) {
            json_response(['success' => false, 'message' => "Field '{$field}' is required"], 400);
        }
    }

    $property_id = (int)$input['property_id'];
    $start_date = sanitize_input($input['start_date']);
    $monthly_rent = (float)$input['monthly_rent'];
    $security_deposit = (float)($input['security_deposit'] ?? 0);
    $notes = sanitize_input($input['notes'] ?? '');

    // Validate property exists and is available
    $property_model = new Property();
    $property = $property_model->getById($property_id);
    
    if (!$property) {
        json_response(['success' => false, 'message' => 'Property not found'], 404);
    }

    if (!$property['is_available']) {
        json_response(['success' => false, 'message' => 'Property is not available for subscription'], 400);
    }

    // Validate start date
    $start_timestamp = strtotime($start_date);
    $today_timestamp = strtotime(date('Y-m-d'));

    if ($start_timestamp < $today_timestamp) {
        json_response(['success' => false, 'message' => 'Start date cannot be in the past'], 400);
    }

    // Validate rent amount
    if ($monthly_rent <= 0) {
        json_response(['success' => false, 'message' => 'Monthly rent must be greater than 0'], 400);
    }

    // Create subscription
    $subscription_data = [
        'property_id' => $property_id,
        'customer_id' => $_SESSION['user_id'],
        'owner_id' => $property['owner_id'],
        'start_date' => $start_date,
        'monthly_rent' => $monthly_rent,
        'security_deposit' => $security_deposit,
        'status' => 'pending',
        'notes' => $notes
    ];

    $subscription_model = new Subscription();
    $subscription_id = $subscription_model->create($subscription_data);

    if (!$subscription_id) {
        json_response(['success' => false, 'message' => 'Failed to create subscription'], 500);
    }

    // Generate PayHere payment URL
    $payhere = new PayHere();
    $payment_data = [
        'amount' => $monthly_rent + $security_deposit,
        'order_id' => 'SUB_' . $subscription_id . '_' . time(),
        'item_name' => 'Monthly Subscription - ' . $property['title'],
        'customer_name' => $_SESSION['user_name'],
        'customer_email' => $_SESSION['user_email'] ?? '',
        'customer_phone' => $_SESSION['user_phone'],
        'return_url' => APP_URL . '/frontend/payment-success.php',
        'cancel_url' => APP_URL . '/frontend/payment-cancel.php',
        'notify_url' => APP_URL . '/api/payment/notify.php'
    ];

    $payhere_data = $payhere->generatePaymentData($payment_data);

    json_response([
        'success' => true,
        'message' => 'Subscription created successfully',
        'data' => [
            'subscription_id' => $subscription_id,
            'property_title' => $property['title'],
            'start_date' => $start_date,
            'monthly_rent' => $monthly_rent,
            'total_amount' => $monthly_rent + $security_deposit
        ],
        'payment_url' => $payhere_data['payment_url']
    ]);

} catch (Exception $e) {
    error_log("Create Subscription Error: " . $e->getMessage());
    json_response([
        'success' => false,
        'message' => 'An error occurred while creating subscription'
    ], 500);
}
?>
