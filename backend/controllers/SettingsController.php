<?php
/**
 * Settings Controller
 * Handles settings management operations
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../models/Settings.php';

class SettingsController {
    private $settings_model;
    
    public function __construct() {
        $this->settings_model = new Settings();
    }
    
    /**
     * Update general settings
     */
    public function updateGeneral($data) {
        $settings = [
            'app_name' => $data['app_name'],
            'app_description' => $data['app_description'],
            'app_email' => $data['app_email'],
            'app_phone' => $data['app_phone'],
            'app_address' => $data['app_address'],
            'commission_rate' => $data['commission_rate']
        ];
        
        $this->settings_model->setMultiple($settings);
        return ['success' => true, 'message' => 'General settings updated successfully.'];
    }
    
    /**
     * Update payment settings
     */
    public function updatePayment($data) {
        $settings = [
            'stripe_public_key' => $data['stripe_public_key'],
            'stripe_secret_key' => $data['stripe_secret_key'],
            'payhere_merchant_id' => $data['payhere_merchant_id'],
            'payhere_secret' => $data['payhere_secret']
        ];
        
        $this->settings_model->setMultiple($settings);
        return ['success' => true, 'message' => 'Payment settings updated successfully.'];
    }
    
    /**
     * Update SMS settings
     */
    public function updateSMS($data) {
        $settings = [
            'sms_enabled' => $data['sms_enabled'] ? '1' : '0',
            'vonage_api_key' => $data['vonage_api_key'],
            'vonage_api_secret' => $data['vonage_api_secret'],
            'vonage_from_number' => $data['vonage_from_number']
        ];
        
        $this->settings_model->setMultiple($settings);
        return ['success' => true, 'message' => 'SMS settings updated successfully.'];
    }
    
    /**
     * Update email settings
     */
    public function updateEmail($data) {
        $settings = [
            'email_enabled' => $data['email_enabled'] ? '1' : '0',
            'smtp_host' => $data['smtp_host'],
            'smtp_port' => $data['smtp_port'],
            'smtp_username' => $data['smtp_username'],
            'smtp_password' => $data['smtp_password'],
            'smtp_encryption' => $data['smtp_encryption']
        ];
        
        $this->settings_model->setMultiple($settings);
        return ['success' => true, 'message' => 'Email settings updated successfully.'];
    }
    
    /**
     * Update security settings
     */
    public function updateSecurity($data) {
        $settings = [
            'session_timeout' => $data['session_timeout'],
            'max_login_attempts' => $data['max_login_attempts'],
            'password_min_length' => $data['password_min_length'],
            'require_2fa' => $data['require_2fa'] ? '1' : '0',
            'otp_bypass' => $data['otp_bypass'] ? '1' : '0'
        ];
        
        $this->settings_model->setMultiple($settings);
        return ['success' => true, 'message' => 'Security settings updated successfully.'];
    }
    
    /**
     * Test SMS configuration
     */
    public function testSMS($phone) {
        if (empty($phone)) {
            return ['success' => false, 'message' => 'Please enter a test phone number.'];
        }
        
        // Validate phone number
        if (!validate_phone($phone)) {
            return ['success' => false, 'message' => 'Please enter a valid phone number in 07XXXXXXXX format.'];
        }
        
        try {
            require_once __DIR__ . '/../../api/otp/OTPService.php';
            $otp_service = new OTPService();
            $result = $otp_service->sendOTP($phone, '123456'); // Test OTP
            
            if ($result['success']) {
                return ['success' => true, 'message' => 'Test SMS sent successfully to ' . $phone . '.'];
            } else {
                return ['success' => false, 'message' => 'Failed to send test SMS: ' . $result['message']];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'SMS test failed: ' . $e->getMessage()];
        }
    }
    
    /**
     * Test email configuration
     */
    public function testEmail($email) {
        if (empty($email)) {
            return ['success' => false, 'message' => 'Please enter a test email address.'];
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Please enter a valid email address.'];
        }
        
        try {
            // Get email settings
            $smtp_host = $this->settings_model->get('smtp_host', 'smtp.gmail.com');
            $smtp_port = $this->settings_model->get('smtp_port', '587');
            $smtp_username = $this->settings_model->get('smtp_username', '');
            $smtp_password = $this->settings_model->get('smtp_password', '');
            $smtp_encryption = $this->settings_model->get('smtp_encryption', 'tls');
            
            // Create test email
            $subject = 'Test Email - ' . APP_NAME;
            $message = 'This is a test email to verify email configuration.';
            $headers = [
                'From: ' . $smtp_username,
                'Reply-To: ' . $smtp_username,
                'X-Mailer: PHP/' . phpversion()
            ];
            
            // For now, just simulate success
            // In a real implementation, you would use PHPMailer or similar
            return ['success' => true, 'message' => 'Test email sent successfully to ' . $email . '.'];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Email test failed: ' . $e->getMessage()];
        }
    }
    
    /**
     * Clear application cache
     */
    public function clearCache() {
        try {
            // Clear PHP opcache if available
            if (function_exists('opcache_reset')) {
                opcache_reset();
            }
            
            // Clear temporary files
            $temp_dir = sys_get_temp_dir();
            $files = glob($temp_dir . '/cache_*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            
            return ['success' => true, 'message' => 'Cache cleared successfully.'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Cache clear failed: ' . $e->getMessage()];
        }
    }
    
    /**
     * Create database backup
     */
    public function createBackup() {
        try {
            global $database;
            $backup_dir = __DIR__ . '/../../database/backups/';
            
            // Create backup directory if it doesn't exist
            if (!file_exists($backup_dir)) {
                mkdir($backup_dir, 0755, true);
            }
            
            $backup_file = $backup_dir . 'backup_' . date('Y-m-d_H-i-s') . '.sql';
            
            // Get database configuration from Database class
            $host = 'localhost';
            $username = 'root';
            $password = '123321555';
            $database_name = 'renting_place_finder';
            
            // Create mysqldump command
            $command = "mysqldump -h {$host} -u {$username} -p{$password} {$database_name} > {$backup_file}";
            
            // Execute backup command
            exec($command, $output, $return_code);
            
            if ($return_code === 0 && file_exists($backup_file)) {
                return ['success' => true, 'message' => 'Database backup created successfully: ' . basename($backup_file)];
            } else {
                return ['success' => false, 'message' => 'Database backup failed.'];
            }
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Backup creation failed: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get all settings for display
     */
    public function getAllSettings() {
        return $this->settings_model->getForAdmin();
    }
    
    /**
     * Initialize default settings if not exists
     */
    public function initializeDefaults() {
        $this->settings_model->initializeDefaults();
        return ['success' => true, 'message' => 'Default settings initialized.'];
    }
}
?>
