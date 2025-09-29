<?php
/**
 * Property Approval API Endpoint
 * Handles property approval/rejection by admin
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../backend/models/Property.php';

// Set JSON response header
header('Content-Type: application/json');

try {
    // Only allow POST requests
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        json_response(['success' => false, 'message' => 'Method not allowed'], 405);
    }

    // Check if user is logged in and is admin
    if (!is_logged_in()) {
        json_response(['success' => false, 'message' => 'Authentication required'], 401);
    }

    if ($_SESSION['user_type'] !== 'admin') {
        json_response(['success' => false, 'message' => 'Admin access required'], 403);
    }

    // Get input data
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['property_id']) || !isset($input['action'])) {
        json_response(['success' => false, 'message' => 'Property ID and action are required'], 400);
    }

    $property_id = (int)$input['property_id'];
    $action = $input['action']; // 'approve' or 'reject'
    $notes = $input['notes'] ?? null;

    if (!in_array($action, ['approve', 'reject'])) {
        json_response(['success' => false, 'message' => 'Invalid action. Must be approve or reject'], 400);
    }

    // Get property details
    $property_model = new Property();
    $property = $property_model->getById($property_id);

    if (!$property) {
        json_response(['success' => false, 'message' => 'Property not found'], 404);
    }

    // Perform the action
    if ($action === 'approve') {
        $result = $property_model->approve($property_id, $notes);
        $message = 'Property approved successfully and is now visible to customers';
    } else {
        $result = $property_model->reject($property_id, $notes);
        $message = 'Property rejected and is hidden from customers';
    }

    if (!$result) {
        json_response(['success' => false, 'message' => 'Failed to update property status'], 500);
    }

    json_response([
        'success' => true,
        'message' => $message,
        'data' => [
            'property_id' => $property_id,
            'action' => $action,
            'property_title' => $property['title']
        ]
    ]);

} catch (Exception $e) {
    error_log("Property Approval Error: " . $e->getMessage());
    json_response([
        'success' => false,
        'message' => 'An error occurred while processing the request'
    ], 500);
}
?>
