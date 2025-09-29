<?php
/**
 * Location API
 * Returns districts and cities based on parent selection
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../backend/models/District.php';
require_once __DIR__ . '/../../backend/models/City.php';

$response = ['success' => false, 'data' => [], 'message' => ''];

try {
    // Handle both GET and POST requests
    $input_data = [];
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $raw_input = file_get_contents('php://input');
        $input_data = json_decode($raw_input, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON input: ' . json_last_error_msg());
        }
        
        $input_data = $input_data ?? [];
    } else {
        $input_data = $_GET;
    }
    
    $action = $input_data['action'] ?? '';
    
    // Validate action
    if (empty($action)) {
        throw new Exception('Action parameter is required');
    }
    
    if (!in_array($action, ['districts', 'cities'])) {
        throw new Exception('Invalid action. Must be "districts" or "cities"');
    }
    
    switch ($action) {
        case 'districts':
            $province_id = $input_data['province_id'] ?? '';
            if (empty($province_id)) {
                throw new Exception('Province ID is required');
            }
            
            // Validate province ID
            if (!is_numeric($province_id) || $province_id <= 0) {
                throw new Exception('Invalid province ID');
            }
            
            $district_model = new District();
            $districts = $district_model->getByProvince($province_id);
            
            $response = [
                'success' => true,
                'districts' => $districts,
                'message' => 'Districts retrieved successfully'
            ];
            break;
            
        case 'cities':
            $district_id = $input_data['district_id'] ?? '';
            if (empty($district_id)) {
                throw new Exception('District ID is required');
            }
            
            // Validate district ID
            if (!is_numeric($district_id) || $district_id <= 0) {
                throw new Exception('Invalid district ID');
            }
            
            $city_model = new City();
            $cities = $city_model->getByDistrict($district_id);
            
            $response = [
                'success' => true,
                'cities' => $cities,
                'message' => 'Cities retrieved successfully'
            ];
            break;
            
        default:
            throw new Exception('Invalid action');
    }
    
} catch (Exception $e) {
    $response = [
        'success' => false,
        'data' => [],
        'message' => $e->getMessage()
    ];
}

echo json_encode($response);
?>
