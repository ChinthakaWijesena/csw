<?php
/**
 * Helper Functions
 * Additional utility functions not already defined in config.php
 */

/**
 * Validate email address
 */
if (!function_exists('validate_email')) {
    function validate_email($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
}

/**
 * Format phone number to 07XXXXXXXX format
 */
if (!function_exists('format_phone')) {
    function format_phone($phone) {
        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Validate with new regex pattern for 07XXXXXXXX format
        if (preg_match('/^[0]{1}[7]{1}[01245678]{1}[0-9]{7}$/', $phone)) {
            // Return 07XXXXXXXX format as-is
            return $phone;
        }
        
        // If it's 9 digits starting with 7, add 0 prefix
        if (strlen($phone) === 9 && substr($phone, 0, 1) === '7') {
            return '0' . $phone;
        }
        
        // If it's 12 digits with country code, convert to 07XXXXXXXX
        if (strlen($phone) === 12 && substr($phone, 0, 2) === '94') {
            return '0' . substr($phone, 2);
        }
        
        return $phone;
    }
}

/**
 * Generate random string
 */
if (!function_exists('generate_random_string')) {
    function generate_random_string($length = 32) {
        return bin2hex(random_bytes($length / 2));
    }
}

/**
 * Hash password
 */
if (!function_exists('hash_password')) {
    function hash_password($password) {
        return password_hash($password, PASSWORD_DEFAULT);
    }
}

/**
 * Verify password
 */
if (!function_exists('verify_password')) {
    function verify_password($password, $hash) {
        return password_verify($password, $hash);
    }
}

/**
 * Get current user ID
 */
if (!function_exists('get_current_user_id')) {
    function get_current_user_id() {
        return $_SESSION['user_id'] ?? null;
    }
}

/**
 * Get current user type
 */
if (!function_exists('get_current_user_type')) {
    function get_current_user_type() {
        return $_SESSION['user_type'] ?? null;
    }
}

/**
 * Check if user has specific role
 */
if (!function_exists('has_role')) {
    function has_role($role) {
        return get_current_user_type() === $role;
    }
}

/**
 * Check if user is admin
 */
if (!function_exists('is_admin')) {
    function is_admin() {
        return has_role('admin');
    }
}

/**
 * Check if user is property owner
 */
if (!function_exists('is_owner')) {
    function is_owner() {
        return has_role('owner');
    }
}

/**
 * Check if user is customer
 */
if (!function_exists('is_customer')) {
    function is_customer() {
        return has_role('customer');
    }
}

/**
 * Redirect to login page
 */
if (!function_exists('redirect_to_login')) {
    function redirect_to_login() {
        header('Location: ' . APP_URL . '/frontend/login.php');
        exit;
    }
}

/**
 * Redirect to admin login page
 */
if (!function_exists('redirect_to_admin_login')) {
    function redirect_to_admin_login() {
        header('Location: ' . APP_URL . '/frontend/adminlogin.php');
        exit;
    }
}

/**
 * Redirect to dashboard based on user type
 */
if (!function_exists('redirect_to_dashboard')) {
    function redirect_to_dashboard() {
        $user_type = get_current_user_type();
        
        switch ($user_type) {
            case 'admin':
                header('Location: ' . APP_URL . '/frontend/admin/dashboard/index.php');
                break;
            case 'owner':
                header('Location: ' . APP_URL . '/frontend/owner/dashboard/index.php');
                break;
            case 'customer':
                header('Location: ' . APP_URL . '/frontend/dashboard.php');
                break;
            default:
                header('Location: ' . APP_URL . '/frontend/index.php');
        }
        exit;
    }
}

/**
 * Log activity
 */
if (!function_exists('log_activity')) {
    function log_activity($action, $description, $target_user_id = null, $target_property_id = null) {
        global $database;
        
        try {
            $database->query(
                "INSERT INTO admin_logs (admin_id, action, description, target_user_id, target_property_id, ip_address) 
                 VALUES (?, ?, ?, ?, ?, ?)",
                [
                    get_current_user_id(),
                    $action,
                    $description,
                    $target_user_id,
                    $target_property_id,
                    $_SERVER['REMOTE_ADDR'] ?? null
                ]
            );
        } catch (Exception $e) {
            error_log("Failed to log activity: " . $e->getMessage());
        }
    }
}

/**
 * Send email notification
 */
if (!function_exists('send_email')) {
    function send_email($to, $subject, $message, $headers = []) {
        $default_headers = [
            'From: ' . APP_NAME . ' <noreply@' . parse_url(APP_URL, PHP_URL_HOST) . '>',
            'Reply-To: noreply@' . parse_url(APP_URL, PHP_URL_HOST),
            'X-Mailer: PHP/' . phpversion(),
            'Content-Type: text/html; charset=UTF-8'
        ];
        
        $all_headers = array_merge($default_headers, $headers);
        
        return mail($to, $subject, $message, implode("\r\n", $all_headers));
    }
}

/**
 * Get time ago string
 */
if (!function_exists('time_ago')) {
    function time_ago($datetime) {
        $time = time() - strtotime($datetime);
        
        if ($time < 60) return 'just now';
        if ($time < 3600) return floor($time/60) . ' minutes ago';
        if ($time < 86400) return floor($time/3600) . ' hours ago';
        if ($time < 2592000) return floor($time/86400) . ' days ago';
        if ($time < 31536000) return floor($time/2592000) . ' months ago';
        
        return floor($time/31536000) . ' years ago';
    }
}

/**
 * Validate CSRF token
 */
if (!function_exists('validate_csrf_token')) {
    function validate_csrf_token($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
}

/**
 * Clean old sessions
 */
if (!function_exists('clean_old_sessions')) {
    function clean_old_sessions() {
        global $database;
        
        try {
            $database->query(
                "DELETE FROM user_sessions WHERE expires_at < NOW()"
            );
        } catch (Exception $e) {
            error_log("Failed to clean old sessions: " . $e->getMessage());
        }
    }
}

/**
 * Get client IP address
 */
if (!function_exists('get_client_ip')) {
    function get_client_ip() {
        $ip_keys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
}

/**
 * Debug logging
 */
if (!function_exists('debug_log')) {
    function debug_log($message, $data = null) {
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            $log_message = '[' . date('Y-m-d H:i:s') . '] ' . $message;
            if ($data !== null) {
                $log_message .= ' | Data: ' . json_encode($data);
            }
            error_log($log_message);
        }
    }
}

/**
 * Get file extension
 */
if (!function_exists('get_file_extension')) {
    function get_file_extension($filename) {
        return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    }
}

/**
 * Validate file type
 */
if (!function_exists('validate_file_type')) {
    function validate_file_type($filename, $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp']) {
        $extension = get_file_extension($filename);
        return in_array($extension, $allowed_types);
    }
}

/**
 * Generate unique filename
 */
if (!function_exists('generate_unique_filename')) {
    function generate_unique_filename($original_filename) {
        $extension = get_file_extension($original_filename);
        return uniqid() . '_' . time() . '.' . $extension;
    }
}

/**
 * Create directory if it doesn't exist
 */
if (!function_exists('ensure_directory_exists')) {
    function ensure_directory_exists($path) {
        if (!file_exists($path)) {
            return mkdir($path, 0755, true);
        }
        return true;
    }
}

/**
 * Delete file safely
 */
if (!function_exists('safe_delete_file')) {
    function safe_delete_file($filepath) {
        if (file_exists($filepath)) {
            return unlink($filepath);
        }
        return true;
    }
}

/**
 * Get pagination info
 */
if (!function_exists('get_pagination_info')) {
    function get_pagination_info($page, $per_page, $total) {
        $total_pages = ceil($total / $per_page);
        $offset = ($page - 1) * $per_page;
        
        return [
            'page' => $page,
            'per_page' => $per_page,
            'total' => $total,
            'total_pages' => $total_pages,
            'offset' => $offset,
            'has_next' => $page < $total_pages,
            'has_prev' => $page > 1
        ];
    }
}

/**
 * Escape HTML output
 */
if (!function_exists('e')) {
    function e($string) {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }
}

/**
 * Check if string is JSON
 */
if (!function_exists('is_json')) {
    function is_json($string) {
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }
}

/**
 * Get setting value
 */
if (!function_exists('get_setting')) {
    function get_setting($key, $default = null) {
        global $database;
        
        try {
            $setting = $database->fetch(
                "SELECT setting_value FROM settings WHERE setting_key = ?",
                [$key]
            );
            
            return $setting ? $setting['setting_value'] : $default;
        } catch (Exception $e) {
            error_log("Failed to get setting: " . $e->getMessage());
            return $default;
        }
    }
}

/**
 * Set setting value
 */
if (!function_exists('set_setting')) {
    function set_setting($key, $value) {
        global $database;
        
        try {
            $database->query(
                "INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) 
                 ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)",
                [$key, $value]
            );
            return true;
        } catch (Exception $e) {
            error_log("Failed to set setting: " . $e->getMessage());
            return false;
        }
    }
}
?>