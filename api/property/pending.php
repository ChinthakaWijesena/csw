<?php
/**
 * Pending Properties API Endpoint
 * Returns properties pending admin approval
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../backend/models/Property.php';

// Set JSON response header
header('Content-Type: application/json');

try {
    // Check if user is logged in and is admin
    if (!is_logged_in()) {
        json_response(['success' => false, 'message' => 'Authentication required'], 401);
    }

    if ($_SESSION['user_type'] !== 'admin') {
        json_response(['success' => false, 'message' => 'Admin access required'], 403);
    }

    // Get pagination parameters
    $page = (int)($_GET['page'] ?? 1);
    $limit = (int)($_GET['limit'] ?? 20);

    // Validate pagination
    if ($page < 1) $page = 1;
    if ($limit < 1 || $limit > 50) $limit = 20;

    // Get pending properties
    $property_model = new Property();
    $properties = $property_model->getPendingApproval($page, $limit);
    $total_count = $property_model->getPendingApprovalCount();

    // Calculate pagination info
    $total_pages = ceil($total_count / $limit);
    $has_next = $page < $total_pages;
    $has_prev = $page > 1;

    json_response([
        'success' => true,
        'properties' => $properties,
        'total_count' => $total_count,
        'page' => $page,
        'limit' => $limit,
        'total_pages' => $total_pages,
        'has_next' => $has_next,
        'has_prev' => $has_prev
    ]);

} catch (Exception $e) {
    error_log("Pending Properties Error: " . $e->getMessage());
    json_response([
        'success' => false,
        'message' => 'An error occurred while fetching pending properties'
    ], 500);
}
?>
