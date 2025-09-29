<?php
/**
 * Wishlist API
 * Fetches property details for wishlist items
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../backend/models/Property.php';

$response = ['success' => false, 'message' => '', 'properties' => []];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Only POST method allowed');
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['property_ids'])) {
        throw new Exception('Property IDs are required');
    }
    
    $property_ids = $input['property_ids'];
    
    if (!is_array($property_ids) || empty($property_ids)) {
        $response = [
            'success' => true,
            'message' => 'No properties requested',
            'properties' => []
        ];
    } else {
        $property_model = new Property();
        $properties = [];
        
        foreach ($property_ids as $property_id) {
            $property = $property_model->getById($property_id);
            if ($property) {
                // Get primary image
                $images = $property_model->getImages($property_id);
                $primary_image = null;
                
                foreach ($images as $image) {
                    if ($image['is_primary']) {
                        $primary_image = $image['image_path'];
                        break;
                    }
                }
                
                if (!$primary_image && !empty($images)) {
                    $primary_image = $images[0]['image_path'];
                }
                
                $properties[] = [
                    'id' => $property['id'],
                    'title' => $property['title'],
                    'description' => $property['description'],
                    'property_type' => $property['property_type'],
                    'bedrooms' => $property['bedrooms'],
                    'bathrooms' => $property['bathrooms'],
                    'area_sqft' => $property['area_sqft'],
                    'monthly_rent' => $property['monthly_rent'],
                    'security_deposit' => $property['security_deposit'],
                    'address' => $property['address'],
                    'city' => $property['city'],
                    'state' => $property['state'],
                    'zip_code' => $property['zip_code'],
                    'is_available' => $property['is_available'],
                    'is_verified' => $property['is_verified'],
                    'primary_image' => $primary_image,
                    'owner_name' => $property['owner_name'],
                    'owner_phone' => $property['owner_phone']
                ];
            }
        }
        
        $response = [
            'success' => true,
            'message' => 'Properties loaded successfully',
            'properties' => $properties
        ];
    }
    
} catch (Exception $e) {
    $response = [
        'success' => false,
        'message' => $e->getMessage(),
        'properties' => []
    ];
}

echo json_encode($response);
?>
