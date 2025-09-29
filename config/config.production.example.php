<?php
/**
 * Production Configuration Example
 * Copy this to config.php and modify for production use
 */

// Development-only OTP settings (DO NOT USE IN PRODUCTION)
define('DEV_FIXED_OTP', '123456'); // Fixed OTP for development testing
define('DEV_FIXED_OTP_ENABLED', false); // Set to false in production

// OTP Bypass for development (DO NOT USE IN PRODUCTION)
define('OTP_BYPASS', false); // Set to false in production - bypasses OTP validation

// Debug mode (DO NOT USE IN PRODUCTION)
define('DEBUG_MODE', false); // Set to false in production

// Other production settings...
define('APP_URL', 'https://yourdomain.com'); // Update for production
define('SECURE_SESSION', true); // Keep true for production
define('SESSION_TIMEOUT', 2 * 60 * 60); // 2 hours for production (shorter than dev)

// Database settings for production
// Update database credentials for production environment

// SMS settings for production
// Update Vonage API credentials for production

// File upload settings for production
// Update upload paths and security settings

// Security settings for production
define('MAX_LOGIN_ATTEMPTS', 3); // Stricter for production
define('LOGIN_LOCKOUT_TIME', 30 * 60); // 30 minutes for production
?>
