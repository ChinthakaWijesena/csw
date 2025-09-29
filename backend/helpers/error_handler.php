<?php
/**
 * Error Handler
 * Centralized error handling for the application
 */

/**
 * Custom error handler
 */
function custom_error_handler($errno, $errstr, $errfile, $errline) {
    // Don't handle errors that are suppressed with @
    if (error_reporting() === 0) {
        return false;
    }
    
    $error_types = [
        E_ERROR => 'Fatal Error',
        E_WARNING => 'Warning',
        E_PARSE => 'Parse Error',
        E_NOTICE => 'Notice',
        E_CORE_ERROR => 'Core Error',
        E_CORE_WARNING => 'Core Warning',
        E_COMPILE_ERROR => 'Compile Error',
        E_COMPILE_WARNING => 'Compile Warning',
        E_USER_ERROR => 'User Error',
        E_USER_WARNING => 'User Warning',
        E_USER_NOTICE => 'User Notice',
        E_STRICT => 'Strict Notice',
        E_RECOVERABLE_ERROR => 'Recoverable Error',
        E_DEPRECATED => 'Deprecated',
        E_USER_DEPRECATED => 'User Deprecated'
    ];
    
    $error_type = $error_types[$errno] ?? 'Unknown Error';
    
    $error_message = sprintf(
        "[%s] %s in %s on line %d: %s",
        $error_type,
        $errstr,
        $errfile,
        $errline,
        $errstr
    );
    
    // Log the error
    error_log($error_message);
    
    // In development mode, display errors
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        echo "<div style='background: #f8d7da; color: #721c24; padding: 10px; margin: 10px; border: 1px solid #f5c6cb; border-radius: 4px;'>";
        echo "<strong>{$error_type}:</strong> {$errstr}<br>";
        echo "<strong>File:</strong> {$errfile}<br>";
        echo "<strong>Line:</strong> {$errline}";
        echo "</div>";
    }
    
    // Don't execute PHP internal error handler
    return true;
}

/**
 * Custom exception handler
 */
function custom_exception_handler($exception) {
    $error_message = sprintf(
        "[Uncaught Exception] %s in %s on line %d: %s",
        get_class($exception),
        $exception->getFile(),
        $exception->getLine(),
        $exception->getMessage()
    );
    
    // Log the exception
    error_log($error_message);
    
    // Log stack trace
    error_log("Stack trace:\n" . $exception->getTraceAsString());
    
    // In development mode, display the exception
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        echo "<div style='background: #f8d7da; color: #721c24; padding: 10px; margin: 10px; border: 1px solid #f5c6cb; border-radius: 4px;'>";
        echo "<strong>Uncaught Exception:</strong> " . get_class($exception) . "<br>";
        echo "<strong>Message:</strong> " . $exception->getMessage() . "<br>";
        echo "<strong>File:</strong> " . $exception->getFile() . "<br>";
        echo "<strong>Line:</strong> " . $exception->getLine() . "<br>";
        echo "<strong>Stack Trace:</strong><br><pre>" . $exception->getTraceAsString() . "</pre>";
        echo "</div>";
    } else {
        // In production, show a generic error message
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'An internal server error occurred. Please try again later.'
        ]);
    }
}

/**
 * Shutdown handler for fatal errors
 */
function custom_shutdown_handler() {
    $error = error_get_last();
    
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        $error_message = sprintf(
            "[Fatal Error] %s in %s on line %d: %s",
            $error['type'],
            $error['file'],
            $error['line'],
            $error['message']
        );
        
        // Log the fatal error
        error_log($error_message);
        
        // In development mode, display the error
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            echo "<div style='background: #f8d7da; color: #721c24; padding: 10px; margin: 10px; border: 1px solid #f5c6cb; border-radius: 4px;'>";
            echo "<strong>Fatal Error:</strong> {$error['message']}<br>";
            echo "<strong>File:</strong> {$error['file']}<br>";
            echo "<strong>Line:</strong> {$error['line']}";
            echo "</div>";
        } else {
            // In production, show a generic error message
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'An internal server error occurred. Please try again later.'
            ]);
        }
    }
}

/**
 * Initialize error handling
 */
function init_error_handling() {
    // Set custom error handler
    set_error_handler('custom_error_handler');
    
    // Set custom exception handler
    set_exception_handler('custom_exception_handler');
    
    // Set shutdown handler for fatal errors
    register_shutdown_function('custom_shutdown_handler');
    
    // Set error reporting level
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
    } else {
        error_reporting(E_ALL);
        ini_set('display_errors', 0);
        ini_set('log_errors', 1);
    }
}

/**
 * Log API errors
 */
function log_api_error($endpoint, $error, $data = []) {
    $log_message = sprintf(
        "[API Error] Endpoint: %s | Error: %s | Data: %s",
        $endpoint,
        $error,
        json_encode($data)
    );
    
    error_log($log_message);
}

/**
 * Log database errors
 */
function log_database_error($query, $error, $params = []) {
    $log_message = sprintf(
        "[Database Error] Query: %s | Error: %s | Params: %s",
        $query,
        $error,
        json_encode($params)
    );
    
    error_log($log_message);
}

/**
 * Log security events
 */
function log_security_event($event, $details = []) {
    $log_message = sprintf(
        "[Security Event] %s | Details: %s | IP: %s | User: %s",
        $event,
        json_encode($details),
        get_client_ip(),
        get_current_user_id() ?? 'anonymous'
    );
    
    error_log($log_message);
}

/**
 * Handle API errors consistently
 */
function handle_api_error($message, $code = 500, $details = []) {
    http_response_code($code);
    
    $response = [
        'success' => false,
        'message' => $message,
        'code' => $code
    ];
    
    if (defined('DEBUG_MODE') && DEBUG_MODE && !empty($details)) {
        $response['details'] = $details;
    }
    
    echo json_encode($response);
    exit;
}

/**
 * Handle validation errors
 */
function handle_validation_error($errors) {
    http_response_code(400);
    
    echo json_encode([
        'success' => false,
        'message' => 'Validation failed',
        'errors' => $errors
    ]);
    exit;
}

/**
 * Handle authentication errors
 */
function handle_auth_error($message = 'Authentication required') {
    http_response_code(401);
    
    echo json_encode([
        'success' => false,
        'message' => $message
    ]);
    exit;
}

/**
 * Handle authorization errors
 */
function handle_authorization_error($message = 'Access denied') {
    http_response_code(403);
    
    echo json_encode([
        'success' => false,
        'message' => $message
    ]);
    exit;
}

/**
 * Handle not found errors
 */
function handle_not_found_error($message = 'Resource not found') {
    http_response_code(404);
    
    echo json_encode([
        'success' => false,
        'message' => $message
    ]);
    exit;
}

/**
 * Handle rate limit errors
 */
function handle_rate_limit_error($message = 'Rate limit exceeded') {
    http_response_code(429);
    
    echo json_encode([
        'success' => false,
        'message' => $message
    ]);
    exit;
}

// Initialize error handling when this file is included
init_error_handling();
?>
