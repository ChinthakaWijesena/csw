<?php
/**
 * Add to Favorites API
 * Adds a property to the user's favorites (wishlist)
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../backend/models/Property.php';

$response = ['success' => false, 'message' => ''];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Only POST method allowed');
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['property_id'])) {
        throw new Exception('Property ID is required');
    }
    
    $property_id = (int)$input['property_id'];
    
    if ($property_id <= 0) {
        throw new Exception('Invalid property ID');
    }
    
    // Check if property exists
    $property_model = new Property();
    $property = $property_model->getById($property_id);
    
    if (!$property) {
        throw new Exception('Property not found');
    }
    
    // Check if property is available
    if (!$property['is_available']) {
        throw new Exception('Property is not available');
    }
    
    // For now, we'll just return success since the frontend handles the wishlist via localStorage
    // In a real application, you would save this to the database for logged-in users
    $response = [
        'success' => true,
        'message' => 'Property added to favorites successfully',
        'property_id' => $property_id,
        'property_title' => $property['title']
    ];
    
} catch (Exception $e) {
    $response = [
        'success' => false,
        'message' => $e->getMessage()
    ];
}

echo json_encode($response);
?>
