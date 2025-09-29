<?php
/**
 * OTP Service for SMS Authentication using Vonage API
 * Supports only Vonage SMS service
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use Vonage\Client;
use Vonage\Client\Credentials\Basic;
use Vonage\SMS\Message\SMS;

class OTPService {
    private $client;
    private $from_number;
    
    public function __construct() {
        // Initialize Vonage client with credentials
        $basic = new Basic(VONAGE_API_KEY, VONAGE_API_SECRET);
        $this->client = new Client($basic);
        $this->from_number = VONAGE_FROM_NUMBER;
    }
    
    /**
     * Generate a 6-digit OTP code
     */
    public function generateOTP() {
        return str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }
    
    /**
     * Send OTP via Vonage SMS
     */
    public function sendOTP($phone, $otp_code) {
        $original_phone = $phone;
        $phone = $this->formatPhoneNumber($phone);
        
        if (!$phone) {
            throw new Exception('Invalid phone number format');
        }
        
        // Development-only fixed OTP (DO NOT USE IN PRODUCTION)
        if (DEV_FIXED_OTP_ENABLED && DEBUG_MODE) {
            error_log("⚠️  DEVELOPMENT MODE: Using fixed OTP " . DEV_FIXED_OTP . " for all phone numbers");
            error_log("⚠️  WARNING: This is for testing only. DO NOT deploy to production!");
            
            // Save the fixed OTP to database
            $this->saveOTPToDatabase($phone, DEV_FIXED_OTP);
            
            return [
                'success' => true,
                'message' => 'Development OTP generated (Fixed: ' . DEV_FIXED_OTP . ')',
                'formatted_phone' => $phone,
                'development_mode' => true,
                'fixed_otp' => DEV_FIXED_OTP,
                'warning' => 'DEVELOPMENT MODE - DO NOT USE IN PRODUCTION'
            ];
        }
        
        // Debug logging for development
        if (DEBUG_MODE) {
            error_log("Vonage OTP Send Debug:");
            error_log("Original phone: " . $original_phone);
            error_log("Formatted phone: " . $phone);
            error_log("OTP code: " . $otp_code);
        }
        
        try {
            // Create SMS message
            $message = "Your " . APP_NAME . " OTP code is: {$otp_code}. Valid for 10 minutes.";
            
            // Send SMS using Vonage client
            $response = $this->client->sms()->send(
                new SMS($phone, $this->from_number, $message)
            );
            
            $message_result = $response->current();
            
            if ($message_result->getStatus() == 0) {
                // SMS sent successfully, save OTP to database
                $this->saveOTPToDatabase($phone, $otp_code);
                
                if (DEBUG_MODE) {
                    error_log("Vonage SMS sent successfully. Message ID: " . $message_result->getMessageId());
                }
                
                return [
                    'success' => true,
                    'message' => 'OTP sent successfully via SMS',
                    'formatted_phone' => $phone,
                    'message_id' => $message_result->getMessageId()
                ];
            } else {
                $error_message = 'Failed to send OTP. Status: ' . $message_result->getStatus();
                if (DEBUG_MODE) {
                    error_log("Vonage SMS failed: " . $error_message);
                }
                throw new Exception($error_message);
            }
            
        } catch (Exception $e) {
            if (DEBUG_MODE) {
                error_log("Vonage SMS error: " . $e->getMessage());
                
                // In development mode, if Vonage fails, save OTP anyway for testing
                if (strpos($e->getMessage(), 'Bad Credentials') !== false || 
                    strpos($e->getMessage(), 'Invalid credentials') !== false) {
                    
                    error_log("Vonage credentials issue detected. Saving OTP for development testing.");
                    $this->saveOTPToDatabase($phone, $otp_code);
                    
                    return [
                        'success' => true,
                        'message' => 'OTP saved for development (Vonage credentials issue)',
                        'formatted_phone' => $phone,
                        'development_mode' => true,
                        'otp' => $otp_code
                    ];
                }
            }
            throw new Exception('Failed to send OTP: ' . $e->getMessage());
        }
    }
    
    /**
     * Save OTP to database
     */
    private function saveOTPToDatabase($phone, $otp_code) {
        global $database;
        
        // Delete any existing OTP for this phone
        $database->query(
            "DELETE FROM otp_verifications WHERE phone = ?",
            [$phone]
        );
        
        // Insert new OTP
        $expires_at = date('Y-m-d H:i:s', time() + OTP_EXPIRY);
        $database->query(
            "INSERT INTO otp_verifications (phone, otp_code, expires_at) VALUES (?, ?, ?)",
            [$phone, $otp_code, $expires_at]
        );
        
        if (DEBUG_MODE) {
            error_log("OTP saved to database for phone: " . $phone);
        }
    }
    
    /**
     * Verify OTP code
     */
    public function verifyOTP($phone, $otp_code) {
        global $database;
        
        $original_phone = $phone;
        $phone = $this->formatPhoneNumber($phone);
        
        // Development-only fixed OTP verification (DO NOT USE IN PRODUCTION)
        $test_otps = ['123456', '111111']; // Multiple test OTPs for development
        if (DEV_FIXED_OTP_ENABLED && DEBUG_MODE && in_array(trim($otp_code), $test_otps)) {
            error_log("⚠️  DEVELOPMENT MODE: Accepting fixed OTP " . DEV_FIXED_OTP . " for verification");
            error_log("⚠️  WARNING: This bypasses normal OTP verification!");
            
            // Check if there's a valid OTP record for this phone
            $otp = $database->fetch(
                "SELECT * FROM otp_verifications WHERE phone = ? AND otp_code = ?",
                [$phone, trim($otp_code)]
            );
            
            if ($otp) {
                // Mark OTP as verified
                $database->query(
                    "UPDATE otp_verifications SET is_verified = 1 WHERE id = ?",
                    [$otp['id']]
                );
                
                error_log("✅ Development OTP verified successfully for phone: " . $phone);
                return true;
            } else {
                // Create a test OTP record for development
                $expires_at = date('Y-m-d H:i:s', time() + OTP_EXPIRY);
                $database->query(
                    "INSERT INTO otp_verifications (phone, otp_code, expires_at) VALUES (?, ?, ?)",
                    [$phone, trim($otp_code), $expires_at]
                );
                
                // Mark it as verified
                $otp_id = $database->lastInsertId();
                $database->query(
                    "UPDATE otp_verifications SET is_verified = 1 WHERE id = ?",
                    [$otp_id]
                );
                
                error_log("✅ Development OTP created and verified for phone: " . $phone);
                return true;
            }
        }
        
        // Debug logging for development
        if (DEBUG_MODE) {
            error_log("OTP Verification Debug:");
            error_log("Original phone: " . $original_phone);
            error_log("Formatted phone: " . $phone);
            error_log("OTP code: " . $otp_code);
        }
        
        $otp = $database->fetch(
            "SELECT * FROM otp_verifications WHERE phone = ? AND otp_code = ? AND expires_at > NOW() AND is_verified = 0",
            [$phone, $otp_code]
        );
        
        if (DEBUG_MODE) {
            error_log("OTP query result: " . ($otp ? "Found" : "Not found"));
            if ($otp) {
                error_log("OTP details: " . print_r($otp, true));
            }
        }
        
        if ($otp) {
            // Mark OTP as verified
            $database->query(
                "UPDATE otp_verifications SET is_verified = 1 WHERE id = ?",
                [$otp['id']]
            );
            
            if (DEBUG_MODE) {
                error_log("OTP verified successfully for phone: " . $phone);
            }
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Format phone number to 07XXXXXXXX format (Sri Lankan)
     */
    private function formatPhoneNumber($phone) {
        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Accept 07XXXXXXXX format (local) - validate with new regex pattern
        if (preg_match('/^[0]{1}[7]{1}[01245678]{1}[0-9]{7}$/', $phone)) {
            return $phone;
        }
        
        // Convert 947XXXXXXXX to 07XXXXXXXX format
        if (preg_match('/^947[0-9]{8}$/', $phone)) {
            return '0' . substr($phone, 2);
        }
        
        return false;
    }
    
    /**
     * Clean up expired OTPs
     */
    public function cleanupExpiredOTPs() {
        global $database;
        
        $deleted = $database->query(
            "DELETE FROM otp_verifications WHERE expires_at < NOW()"
        );
        
        if (DEBUG_MODE) {
            error_log("Cleaned up expired OTPs. Rows deleted: " . $deleted->rowCount());
        }
        
        return $deleted->rowCount();
    }
    
    /**
     * Get OTP statistics (for admin purposes)
     */
    public function getOTPStats() {
        global $database;
        
        $stats = [];
        
        // Total OTPs sent today
        $stats['today'] = $database->fetch(
            "SELECT COUNT(*) as count FROM otp_verifications WHERE DATE(created_at) = CURDATE()"
        )['count'];
        
        // Total OTPs sent this month
        $stats['month'] = $database->fetch(
            "SELECT COUNT(*) as count FROM otp_verifications WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())"
        )['count'];
        
        // Verification rate
        $total_sent = $database->fetch(
            "SELECT COUNT(*) as count FROM otp_verifications WHERE DATE(created_at) = CURDATE()"
        )['count'];
        
        $verified_today = $database->fetch(
            "SELECT COUNT(*) as count FROM otp_verifications WHERE DATE(created_at) = CURDATE() AND is_verified = 1"
        )['count'];
        
        $stats['verification_rate'] = $total_sent > 0 ? round(($verified_today / $total_sent) * 100, 2) : 0;
        
        return $stats;
    }
}
?>