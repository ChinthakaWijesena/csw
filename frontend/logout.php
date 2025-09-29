<?php
/**
 * Logout Page
 */

require_once __DIR__ . '/../config/config.php';

// Check if user is logged in
if (is_logged_in()) {
    // Delete session from database
    if (isset($_SESSION['session_token'])) {
        $database->query(
            "DELETE FROM user_sessions WHERE session_token = ?",
            [$_SESSION['session_token']]
        );
    }
    
    // Clear all session data
    session_unset();
    session_destroy();
    
    // Start a new session for flash message
    session_start();
    $_SESSION['logout_message'] = 'You have been successfully logged out.';
}

// Redirect to home page
redirect(APP_URL . '/frontend/index.php');
?>
