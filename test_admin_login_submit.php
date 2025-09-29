<?php
/**
 * Test Admin Login Form Submission
 */

session_start();
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/backend/models/User.php';
require_once __DIR__ . '/api/otp/OTPService.php';

echo "<h2>Test Admin Login Form Submission</h2>";

// Simulate the admin login process
echo "<h3>1. Simulate Admin Login Process</h3>";

// Step 1: Set admin phone in session (simulate OTP send)
$admin_phone = '0713018095';

// Validate phone number format
$phone_regex = '/^[0]{1}[7]{1}[01245678]{1}[0-9]{7}$/';
if (!preg_match($phone_regex, $admin_phone)) {
    echo "❌ Invalid phone number format. Expected format: 07XXXXXXXX<br>";
    throw new Exception("Invalid phone number format");
}
echo "✅ Phone number format validated<br>";

$_SESSION['admin_phone'] = $admin_phone;
echo "✅ Admin phone set in session: " . $_SESSION['admin_phone'] . "<br>";

// Step 2: Simulate OTP verification
$otp = '123456';
echo "✅ OTP to verify: " . $otp . "<br>";

// Step 3: Check OTP bypass
if (OTP_BYPASS && DEBUG_MODE) {
    echo "✅ OTP bypass is enabled<br>";
    
    // Get admin user details
    $user_model = new User();
    $user = $user_model->getByPhone($_SESSION['admin_phone']);
    
    if ($user) {
        echo "✅ Admin user found<br>";
        
        // Create session token
        $session_token = generate_token();
        $expires_at = date('Y-m-d H:i:s', time() + SESSION_TIMEOUT);
        
        $database->query(
            "INSERT INTO user_sessions (user_id, session_token, expires_at) VALUES (?, ?, ?)",
            [$user['id'], $session_token, $expires_at]
        );
        
        // Set session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['phone'] = $user['phone'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['user_type'] = $user['user_type'];
        $_SESSION['session_token'] = $session_token;
        
        echo "✅ Session variables set:<br>";
        echo "- user_id: " . $_SESSION['user_id'] . "<br>";
        echo "- name: " . $_SESSION['name'] . "<br>";
        echo "- phone: " . $_SESSION['phone'] . "<br>";
        echo "- email: " . $_SESSION['email'] . "<br>";
        echo "- user_type: " . $_SESSION['user_type'] . "<br>";
        
        // Clear OTP session
        unset($_SESSION['admin_phone']);
        echo "✅ Admin phone cleared from session<br>";
        
        // Test redirect
        echo "<h3>2. Test Redirect</h3>";
        $redirect_url = 'admin/dashboard/index.php';
        echo "Redirect URL: " . $redirect_url . "<br>";
        
        // Check if we can access the dashboard
        echo "<h3>3. Test Dashboard Access</h3>";
        if (is_logged_in() && $_SESSION['user_type'] === 'admin') {
            echo "✅ User is logged in as admin<br>";
            echo "✅ Dashboard access should work<br>";
            echo "<p><a href='frontend/admin/dashboard/index.php' target='_blank'>Test Dashboard Access</a></p>";
        } else {
            echo "❌ User is not logged in as admin<br>";
            echo "is_logged_in(): " . (is_logged_in() ? 'true' : 'false') . "<br>";
            echo "user_type: " . ($_SESSION['user_type'] ?? 'not set') . "<br>";
        }
        
    } else {
        echo "❌ Admin user not found<br>";
    }
} else {
    echo "❌ OTP bypass is disabled<br>";
}

echo "<h3>4. Current Session State</h3>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h3>5. Test Admin Login Page</h3>";
echo "<p><a href='frontend/adminlogin.php' target='_blank'>Open Admin Login Page</a></p>";
?>
