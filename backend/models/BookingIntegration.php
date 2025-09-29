<?php
/**
 * Booking.com Integration Model
 * Handles Booking.com link generation and tracking
 */

require_once __DIR__ . '/../../config/config.php';

class BookingIntegration {
    private $db;
    private $base_url = 'https://www.booking.com';
    private $currency = 'LKR';
    private $utm_source = 'our-site';
    private $utm_medium = 'referral';
    private $utm_campaign = 'booking_link';
    
    public function __construct() {
        global $database;
        $this->db = $database;
    }
    
    /**
     * Generate Booking.com URL with proper parameters
     */
    public function generateBookingUrl($property_id = null, $property_name = null, $location = null) {
        $params = [
            'selected_currency' => $this->currency,
            'utm_source' => $this->utm_source,
            'utm_medium' => $this->utm_medium,
            'utm_campaign' => $this->utm_campaign
        ];
        
        // If property has specific Booking.com link, use it
        if ($property_id) {
            $property_link = $this->getPropertyBookingLink($property_id);
            if ($property_link) {
                $url = $property_link;
            } else {
                // Generate search URL with property details
                $url = $this->base_url;
                if ($property_name && $location) {
                    $params['ss'] = urlencode($property_name . ' ' . $location);
                }
            }
        } else {
            $url = $this->base_url;
        }
        
        // Add parameters
        $query_string = http_build_query($params);
        return $url . (strpos($url, '?') !== false ? '&' : '?') . $query_string;
    }
    
    /**
     * Get property-specific Booking.com link from database
     */
    private function getPropertyBookingLink($property_id) {
        $sql = "SELECT booking_com_link FROM properties WHERE id = ? AND booking_com_link IS NOT NULL AND booking_com_link != ''";
        $result = $this->db->fetch($sql, [$property_id]);
        return $result ? $result['booking_com_link'] : null;
    }
    
    /**
     * Generate ARIA label for accessibility
     */
    public function generateAriaLabel($property_name = null, $location = null) {
        if ($property_name) {
            return "Book {$property_name} on Booking.com in LKR (opens in a new tab)";
        } else {
            return "Book on Booking.com in LKR (opens in a new tab)";
        }
    }
    
    /**
     * Track Booking.com click for analytics
     */
    public function trackBookingClick($property_id = null, $source_location = 'unknown', $property_name = null) {
        // Log to database for analytics
        $sql = "INSERT INTO booking_clicks (property_id, source_location, property_name, clicked_at, ip_address, user_agent) 
                VALUES (?, ?, ?, NOW(), ?, ?)";
        
        $params = [
            $property_id,
            $source_location,
            $property_name,
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ];
        
        try {
            $this->db->query($sql, $params);
        } catch (Exception $e) {
            error_log("Failed to track booking click: " . $e->getMessage());
        }
    }
    
    /**
     * Generate JavaScript tracking code
     */
    public function generateTrackingCode($property_id = null, $source_location = 'unknown', $property_name = null) {
        $tracking_data = [
            'event' => 'booking_external_click',
            'properties' => [
                'propertyId' => $property_id,
                'sourceLocation' => $source_location,
                'propertyName' => $property_name,
                'timestamp' => time()
            ]
        ];
        
        return json_encode($tracking_data);
    }
    
    /**
     * Get Booking.com click statistics
     */
    public function getClickStats($days = 30) {
        $sql = "SELECT 
                    COUNT(*) as total_clicks,
                    COUNT(DISTINCT property_id) as unique_properties,
                    source_location,
                    DATE(clicked_at) as click_date
                FROM booking_clicks 
                WHERE clicked_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY source_location, DATE(clicked_at)
                ORDER BY click_date DESC, total_clicks DESC";
        
        return $this->db->fetchAll($sql, [$days]);
    }
    
    /**
     * Update property with Booking.com link
     */
    public function updatePropertyBookingLink($property_id, $booking_link) {
        $sql = "UPDATE properties SET booking_com_link = ? WHERE id = ?";
        return $this->db->query($sql, [$booking_link, $property_id]);
    }
    
    /**
     * Get properties with Booking.com links
     */
    public function getPropertiesWithBookingLinks() {
        $sql = "SELECT id, title, city, state, booking_com_link 
                FROM properties 
                WHERE booking_com_link IS NOT NULL AND booking_com_link != ''
                ORDER BY title";
        return $this->db->fetchAll($sql);
    }
}
?>
