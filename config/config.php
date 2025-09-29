<?php
/**
 * Main Configuration File
 * Renting Place Finder Application
 */

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include helper functions
require_once __DIR__ . '/../backend/helpers/functions.php';

// Include error handler
require_once __DIR__ . '/../backend/helpers/error_handler.php';

// Define application constants
define('APP_NAME', 'Renting Place Finder');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'http://localhost/csw');
define('APP_PATH', __DIR__ . '/..');

// Security settings
define('SECURE_SESSION', true);
define('SESSION_TIMEOUT', 24 * 60 * 60); // 24 hours
define('OTP_EXPIRY', 10 * 60); // 10 minutes
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 15 * 60); // 15 minutes

// Development-only OTP settings (DO NOT USE IN PRODUCTION)
define('DEV_FIXED_OTP', '123456'); // Fixed OTP for development testing
define('DEV_FIXED_OTP_ENABLED', true); // Set to false in production

// OTP Bypass for development (DO NOT USE IN PRODUCTION)
define('OTP_BYPASS', true); // Set to false in production - bypasses OTP validation

// File upload settings
define('UPLOAD_PATH', APP_PATH . '/assets/uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'webp']);

// SMS Configuration - Vonage API
define('SMS_PROVIDER', 'vonage');
define('VONAGE_API_KEY', 'e2a3b2dc');
define('VONAGE_API_SECRET', '1VqPSEzbe586lAPfUqfed9hBX19v4dO179bgFU2FCfwDHHpOaT');
define('VONAGE_FROM_NUMBER', 'RentingPlace'); // Brand name for SMS sender

// Payment Configuration
define('PAYMENT_PROVIDER', 'stripe'); // stripe, payhere, manual
define('STRIPE_PUBLISHABLE_KEY', 'pk_test_your_stripe_publishable_key');
define('STRIPE_SECRET_KEY', 'sk_test_your_stripe_secret_key');
define('STRIPE_WEBHOOK_SECRET', 'whsec_your_webhook_secret');

// Commission and pricing
define('SEARCH_ACCESS_PRICE', 500.00); // LKR
define('COMMISSION_PERCENTAGE', 5.00);
define('CURRENCY', 'LKR');

// Email configuration (for notifications)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your_email@gmail.com');
define('SMTP_PASSWORD', 'your_app_password');
define('FROM_EMAIL', 'noreply@rentingplace.com');
define('FROM_NAME', 'Renting Place Finder');

// Error reporting (disable in production)
define('DEBUG_MODE', true);
if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Timezone
date_default_timezone_set('UTC');

// Include database configuration
require_once __DIR__ . '/database.php';

// Utility functions
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

function validate_phone($phone) {
    // Remove all non-numeric characters
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    // Accept only 07XXXXXXXX format - validate with new regex pattern
    if (preg_match('/^[0]{1}[7]{1}[01245678]{1}[0-9]{7}$/', $phone)) {
        return $phone;
    }
    
    return false;
}

function generate_token($length = 32) {
    return bin2hex(random_bytes($length));
}

function is_logged_in() {
    return isset($_SESSION['user_id']) && isset($_SESSION['session_token']);
}

function require_login() {
    if (!is_logged_in()) {
        header('Location: ' . APP_URL . '/frontend/login.php');
        exit();
    }
}

function require_admin() {
    require_login();
    if ($_SESSION['user_type'] !== 'admin') {
        header('Location: ' . APP_URL . '/frontend/index.php');
        exit();
    }
}

function require_owner() {
    require_login();
    if (!in_array($_SESSION['user_type'], ['owner', 'admin'])) {
        header('Location: ' . APP_URL . '/frontend/index.php');
        exit();
    }
}

function require_customer() {
    require_login();
    if (!in_array($_SESSION['user_type'], ['customer', 'admin'])) {
        header('Location: ' . APP_URL . '/frontend/index.php');
        exit();
    }
}

function format_currency($amount, $currency = CURRENCY) {
    return '$' . number_format($amount, 2);
}

function format_date($date, $format = 'Y-m-d H:i:s') {
    return date($format, strtotime($date));
}

function redirect($url) {
    header('Location: ' . $url);
    exit();
}

function json_response($data, $status_code = 200) {
    http_response_code($status_code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
}

// CSRF Protection
function generate_csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = generate_token();
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Rate limiting
function check_rate_limit($key, $max_attempts = 5, $time_window = 300) {
    $cache_file = sys_get_temp_dir() . '/rate_limit_' . md5($key);
    
    if (file_exists($cache_file)) {
        $data = json_decode(file_get_contents($cache_file), true);
        if (time() - $data['first_attempt'] < $time_window) {
            if ($data['attempts'] >= $max_attempts) {
                return false;
            }
            $data['attempts']++;
        } else {
            $data = ['attempts' => 1, 'first_attempt' => time()];
        }
    } else {
        $data = ['attempts' => 1, 'first_attempt' => time()];
    }
    
    file_put_contents($cache_file, json_encode($data));
    return true;
}

// Auto-load classes
spl_autoload_register(function ($class_name) {
    $directories = [
        APP_PATH . '/backend/models/',
        APP_PATH . '/backend/controllers/',
        APP_PATH . '/api/'
    ];
    
    foreach ($directories as $directory) {
        $file = $directory . $class_name . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// Create upload directory if it doesn't exist
if (!file_exists(UPLOAD_PATH)) {
    mkdir(UPLOAD_PATH, 0755, true);
    mkdir(UPLOAD_PATH . 'properties/', 0755, true);
    mkdir(UPLOAD_PATH . 'users/', 0755, true);
}
?>
