<?php
/**
 * Visit Request API Endpoint
 */

require_once __DIR__ . '/../../config/config.php';

// Set JSON header
header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Method not allowed'], 405);
}

// Require login
if (!is_logged_in()) {
    json_response(['success' => false, 'message' => 'Authentication required'], 401);
}

// Require customer role
if ($_SESSION['user_type'] !== 'customer') {
    json_response(['success' => false, 'message' => 'Only customers can request visits'], 403);
}

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        json_response(['success' => false, 'message' => 'Invalid JSON input'], 400);
    }
    
    // Validate required fields
    $required_fields = ['property_id', 'requested_date', 'requested_time'];
    foreach ($required_fields as $field) {
        if (empty($input[$field])) {
            json_response(['success' => false, 'message' => "Field '{$field}' is required"], 400);
        }
    }
    
    $property_id = (int)$input['property_id'];
    $requested_date = sanitize_input($input['requested_date']);
    $requested_time = sanitize_input($input['requested_time']);
    $notes = sanitize_input($input['notes'] ?? '');
    
    // Validate property exists and is available
    $property_model = new Property();
    $property = $property_model->getById($property_id);
    
    if (!$property) {
        json_response(['success' => false, 'message' => 'Property not found'], 404);
    }
    
    if (!$property['is_available'] || !$property['is_verified']) {
        json_response(['success' => false, 'message' => 'Property is not available for visits'], 400);
    }
    
    // Validate date (must be in the future)
    $requested_datetime = strtotime($requested_date . ' ' . $requested_time);
    if ($requested_datetime <= time()) {
        json_response(['success' => false, 'message' => 'Visit date must be in the future'], 400);
    }
    
    // Check for existing visit request on the same date/time
    $existing_request = $database->fetch(
        "SELECT id FROM visit_requests 
         WHERE property_id = ? AND requested_date = ? AND requested_time = ? 
         AND status IN ('pending', 'approved')",
        [$property_id, $requested_date, $requested_time]
    );
    
    if ($existing_request) {
        json_response(['success' => false, 'message' => 'A visit request already exists for this date and time'], 400);
    }
    
    // Create visit request
    $database->query(
        "INSERT INTO visit_requests (customer_id, property_id, requested_date, requested_time, notes, status) 
         VALUES (?, ?, ?, ?, ?, 'pending')",
        [$_SESSION['user_id'], $property_id, $requested_date, $requested_time, $notes]
    );
    
    $visit_id = $database->lastInsertId();
    
    // Log admin action
    $database->query(
        "INSERT INTO admin_logs (admin_id, action, description, target_user_id, target_property_id, ip_address) 
         VALUES (?, 'visit_request', ?, ?, ?, ?)",
        [$_SESSION['user_id'], "Visit request created for property ID {$property_id}", $_SESSION['user_id'], $property_id, $_SERVER['REMOTE_ADDR'] ?? null]
    );
    
    json_response([
        'success' => true,
        'message' => 'Visit request submitted successfully',
        'visit_id' => $visit_id
    ]);
    
} catch (Exception $e) {
    error_log("Visit request error: " . $e->getMessage());
    json_response(['success' => false, 'message' => 'An error occurred while processing your request'], 500);
}
?>
