<?php
/**
 * Create Booking API Endpoint
 * Handles booking creation
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../backend/models/Property.php';
require_once __DIR__ . '/../../backend/models/Booking.php';

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
    $required_fields = ['property_id', 'check_in_date', 'check_out_date', 'monthly_rent'];
    foreach ($required_fields as $field) {
        if (empty($input[$field])) {
            json_response(['success' => false, 'message' => "Field '{$field}' is required"], 400);
        }
    }

    $property_id = (int)$input['property_id'];
    $check_in_date = sanitize_input($input['check_in_date']);
    $check_out_date = sanitize_input($input['check_out_date']);
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
        json_response(['success' => false, 'message' => 'Property is not available for booking'], 400);
    }

    // Validate dates
    $check_in_timestamp = strtotime($check_in_date);
    $check_out_timestamp = strtotime($check_out_date);
    $today_timestamp = strtotime(date('Y-m-d'));

    if ($check_in_timestamp < $today_timestamp) {
        json_response(['success' => false, 'message' => 'Check-in date cannot be in the past'], 400);
    }

    if ($check_out_timestamp <= $check_in_timestamp) {
        json_response(['success' => false, 'message' => 'Check-out date must be after check-in date'], 400);
    }

    // Calculate total amount
    $start_date = new DateTime($check_in_date);
    $end_date = new DateTime($check_out_date);
    $diff = $start_date->diff($end_date);
    $months = $diff->y * 12 + $diff->m + ($diff->d > 0 ? 1 : 0);
    $total_amount = $monthly_rent * $months + $security_deposit;

    // Create booking
    $booking_data = [
        'property_id' => $property_id,
        'customer_id' => $_SESSION['user_id'],
        'owner_id' => $property['owner_id'],
        'start_date' => $check_in_date,
        'end_date' => $check_out_date,
        'monthly_rent' => $monthly_rent,
        'security_deposit' => $security_deposit,
        'total_amount' => $total_amount,
        'status' => 'pending',
        'notes' => $notes
    ];

    $booking_model = new Booking();
    $booking_id = $booking_model->create($booking_data);

    if (!$booking_id) {
        json_response(['success' => false, 'message' => 'Failed to create booking'], 500);
    }

    json_response([
        'success' => true,
        'message' => 'Booking created successfully',
        'data' => [
            'booking_id' => $booking_id,
            'property_title' => $property['title'],
            'check_in_date' => $check_in_date,
            'check_out_date' => $check_out_date,
            'total_amount' => $total_amount
        ],
        'redirect' => '/frontend/payment.php?booking_id=' . $booking_id
    ]);

} catch (Exception $e) {
    error_log("Create Booking Error: " . $e->getMessage());
    json_response([
        'success' => false,
        'message' => 'An error occurred while creating booking'
    ], 500);
}
?>
