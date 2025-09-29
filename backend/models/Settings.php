<?php
/**
 * Settings Model
 * Handles system settings database operations
 */

require_once __DIR__ . '/../../config/config.php';

class Settings {
    private $db;
    
    public function __construct() {
        global $database;
        $this->db = $database;
    }
    
    /**
     * Get all settings
     */
    public function getAll() {
        $sql = "SELECT setting_key, setting_value, description FROM system_settings ORDER BY setting_key";
        $results = $this->db->fetchAll($sql);
        
        $settings = [];
        foreach ($results as $row) {
            $settings[$row['setting_key']] = [
                'value' => $row['setting_value'],
                'description' => $row['description']
            ];
        }
        
        return $settings;
    }
    
    /**
     * Get a specific setting by key
     */
    public function get($key, $default = null) {
        $sql = "SELECT setting_value FROM system_settings WHERE setting_key = ?";
        $result = $this->db->fetch($sql, [$key]);
        
        return $result ? $result['setting_value'] : $default;
    }
    
    /**
     * Set a setting value
     */
    public function set($key, $value, $description = null) {
        // Check if setting exists
        $existing = $this->get($key);
        
        if ($existing !== null) {
            // Update existing setting
            $sql = "UPDATE system_settings SET setting_value = ?, description = COALESCE(?, description), updated_at = NOW() WHERE setting_key = ?";
            $this->db->query($sql, [$value, $description, $key]);
        } else {
            // Insert new setting
            $sql = "INSERT INTO system_settings (setting_key, setting_value, description) VALUES (?, ?, ?)";
            $this->db->query($sql, [$key, $value, $description]);
        }
        
        return true;
    }
    
    /**
     * Set multiple settings at once
     */
    public function setMultiple($settings) {
        foreach ($settings as $key => $data) {
            $value = is_array($data) ? $data['value'] : $data;
            $description = is_array($data) ? ($data['description'] ?? null) : null;
            $this->set($key, $value, $description);
        }
        
        return true;
    }
    
    /**
     * Delete a setting
     */
    public function delete($key) {
        $sql = "DELETE FROM system_settings WHERE setting_key = ?";
        $this->db->query($sql, [$key]);
        return true;
    }
    
    /**
     * Get settings by category
     */
    public function getByCategory($category) {
        $sql = "SELECT setting_key, setting_value, description FROM system_settings WHERE setting_key LIKE ? ORDER BY setting_key";
        $results = $this->db->fetchAll($sql, [$category . '%']);
        
        $settings = [];
        foreach ($results as $row) {
            $settings[$row['setting_key']] = [
                'value' => $row['setting_value'],
                'description' => $row['description']
            ];
        }
        
        return $settings;
    }
    
    /**
     * Initialize default settings
     */
    public function initializeDefaults() {
        $default_settings = [
            // General Settings
            'app_name' => ['value' => APP_NAME, 'description' => 'Application name'],
            'app_description' => ['value' => 'Renting Place Finder - Find your perfect rental property', 'description' => 'Application description'],
            'app_email' => ['value' => 'admin@rentingplacefinder.com', 'description' => 'Contact email address'],
            'app_phone' => ['value' => '0713018095', 'description' => 'Contact phone number'],
            'app_address' => ['value' => 'Colombo, Sri Lanka', 'description' => 'Application address'],
            'commission_rate' => ['value' => '5.0', 'description' => 'Commission rate percentage'],
            
            // Payment Settings
            'stripe_public_key' => ['value' => '', 'description' => 'Stripe publishable key'],
            'stripe_secret_key' => ['value' => '', 'description' => 'Stripe secret key'],
            'payhere_merchant_id' => ['value' => '', 'description' => 'PayHere merchant ID'],
            'payhere_secret' => ['value' => '', 'description' => 'PayHere secret key'],
            
            // SMS Settings
            'sms_enabled' => ['value' => '1', 'description' => 'Enable SMS notifications'],
            'vonage_api_key' => ['value' => VONAGE_API_KEY, 'description' => 'Vonage API key'],
            'vonage_api_secret' => ['value' => VONAGE_API_SECRET, 'description' => 'Vonage API secret'],
            'vonage_from_number' => ['value' => VONAGE_FROM_NUMBER, 'description' => 'Vonage sender number'],
            
            // Email Settings
            'email_enabled' => ['value' => '1', 'description' => 'Enable email notifications'],
            'smtp_host' => ['value' => 'smtp.gmail.com', 'description' => 'SMTP host'],
            'smtp_port' => ['value' => '587', 'description' => 'SMTP port'],
            'smtp_username' => ['value' => '', 'description' => 'SMTP username'],
            'smtp_password' => ['value' => '', 'description' => 'SMTP password'],
            'smtp_encryption' => ['value' => 'tls', 'description' => 'SMTP encryption'],
            
            // Security Settings
            'session_timeout' => ['value' => SESSION_TIMEOUT, 'description' => 'Session timeout in seconds'],
            'max_login_attempts' => ['value' => '5', 'description' => 'Maximum login attempts'],
            'password_min_length' => ['value' => '8', 'description' => 'Minimum password length'],
            'require_2fa' => ['value' => '1', 'description' => 'Require two-factor authentication'],
            'otp_bypass' => ['value' => OTP_BYPASS ? '1' : '0', 'description' => 'OTP bypass for development'],
        ];
        
        $this->setMultiple($default_settings);
        return true;
    }
    
    /**
     * Get settings for admin interface
     */
    public function getForAdmin() {
        $settings = $this->getAll();
        
        // Group settings by category
        $grouped = [
            'general' => [],
            'payment' => [],
            'sms' => [],
            'email' => [],
            'security' => []
        ];
        
        foreach ($settings as $key => $data) {
            if (strpos($key, 'app_') === 0 || $key === 'commission_rate') {
                $grouped['general'][$key] = $data;
            } elseif (strpos($key, 'stripe_') === 0 || strpos($key, 'payhere_') === 0) {
                $grouped['payment'][$key] = $data;
            } elseif (strpos($key, 'sms_') === 0 || strpos($key, 'vonage_') === 0) {
                $grouped['sms'][$key] = $data;
            } elseif (strpos($key, 'email_') === 0 || strpos($key, 'smtp_') === 0) {
                $grouped['email'][$key] = $data;
            } elseif (in_array($key, ['session_timeout', 'max_login_attempts', 'password_min_length', 'require_2fa', 'otp_bypass'])) {
                $grouped['security'][$key] = $data;
            }
        }
        
        return $grouped;
    }
}
?>
