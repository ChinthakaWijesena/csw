<?php
/**
 * Booking.com Click Tracking API
 * Handles tracking of Booking.com link clicks
 */

require_once __DIR__ . '/../../config/config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Handle form data (from sendBeacon)
    if (empty($input) && isset($_POST['tracking_data'])) {
        $input = json_decode($_POST['tracking_data'], true);
    }
    
    if (!$input || !isset($input['action'])) {
        throw new Exception('Invalid request data');
    }
    
    if ($input['action'] === 'track_booking_click') {
        $bookingIntegration = new BookingIntegration();
        
        $propertyId = $input['properties']['propertyId'] ?? null;
        $sourceLocation = $input['properties']['sourceLocation'] ?? 'unknown';
        $propertyName = $input['properties']['propertyName'] ?? null;
        
        // Track the click
        $bookingIntegration->trackBookingClick($propertyId, $sourceLocation, $propertyName);
        
        echo json_encode([
            'success' => true,
            'message' => 'Click tracked successfully'
        ]);
    } else {
        throw new Exception('Invalid action');
    }
    
} catch (Exception $e) {
    error_log("Booking tracking error: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to track click',
        'message' => $e->getMessage()
    ]);
}
?>
