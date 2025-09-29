<?php
/**
 * Debug Admin Login Script
 */

session_start();
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/backend/models/User.php';
require_once __DIR__ . '/api/otp/OTPService.php';

echo "<h2>Admin Login Debug</h2>";

// Test admin user lookup
$admin_phone = '0713018095';

// Validate phone number format
$phone_regex = '/^[0]{1}[7]{1}[01245678]{1}[0-9]{7}$/';
if (!preg_match($phone_regex, $admin_phone)) {
    echo "❌ Invalid phone number format. Expected format: 07XXXXXXXX<br>";
    throw new Exception("Invalid phone number format");
}
echo "✅ Phone number format validated<br>";

echo "<h3>1. Test Admin User Lookup</h3>";

try {
    $user_model = new User();
    $user = $user_model->getByPhone($admin_phone);
    
    if ($user) {
        echo "✅ Admin user found:<br>";
        echo "- ID: " . $user['id'] . "<br>";
        echo "- Name: " . $user['name'] . "<br>";
        echo "- Phone: " . $user['phone'] . "<br>";
        echo "- Email: " . $user['email'] . "<br>";
        echo "- User Type: " . $user['user_type'] . "<br>";
        echo "- Is Verified: " . ($user['is_verified'] ? 'Yes' : 'No') . "<br>";
        echo "- Is Active: " . ($user['is_active'] ? 'Yes' : 'No') . "<br>";
        
        if ($user['user_type'] === 'admin') {
            echo "✅ User has admin privileges<br>";
        } else {
            echo "❌ User does NOT have admin privileges<br>";
        }
    } else {
        echo "❌ Admin user not found<br>";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

// Test OTP bypass configuration
echo "<h3>2. Test OTP Bypass Configuration</h3>";
echo "OTP_BYPASS: " . (OTP_BYPASS ? 'true' : 'false') . "<br>";
echo "DEBUG_MODE: " . (DEBUG_MODE ? 'true' : 'false') . "<br>";
echo "Bypass Enabled: " . ((OTP_BYPASS && DEBUG_MODE) ? 'true' : 'false') . "<br>";

// Test session simulation
echo "<h3>3. Test Session Simulation</h3>";
$_SESSION['admin_phone'] = $admin_phone;
echo "Session admin_phone set: " . $_SESSION['admin_phone'] . "<br>";

// Test OTP verification logic
echo "<h3>4. Test OTP Verification Logic</h3>";
$test_otp = '123456';

if (OTP_BYPASS && DEBUG_MODE) {
    echo "✅ OTP bypass is enabled<br>";
    echo "✅ Any OTP will be accepted<br>";
    
    // Simulate the login logic
    if ($user && $user['user_type'] === 'admin') {
        echo "✅ Admin user validation passed<br>";
        echo "✅ Login would be successful<br>";
        
        // Show what session variables would be set
        echo "<h4>Session variables that would be set:</h4>";
        echo "- user_id: " . $user['id'] . "<br>";
        echo "- name: " . $user['name'] . "<br>";
        echo "- phone: " . $user['phone'] . "<br>";
        echo "- email: " . $user['email'] . "<br>";
        echo "- user_type: " . $user['user_type'] . "<br>";
    } else {
        echo "❌ Admin user validation failed<br>";
    }
} else {
    echo "❌ OTP bypass is disabled<br>";
    echo "❌ Normal OTP verification would be required<br>";
}

// Test redirect path
echo "<h3>5. Test Redirect Path</h3>";
$redirect_path = 'admin/dashboard/index.php';
echo "Redirect path: " . $redirect_path . "<br>";

// Check if dashboard file exists
$dashboard_path = __DIR__ . '/frontend/admin/dashboard/index.php';
if (file_exists($dashboard_path)) {
    echo "✅ Dashboard file exists<br>";
} else {
    echo "❌ Dashboard file not found at: " . $dashboard_path . "<br>";
}

// Test is_logged_in function
echo "<h3>6. Test is_logged_in Function</h3>";
if (function_exists('is_logged_in')) {
    echo "✅ is_logged_in function exists<br>";
    echo "Current login status: " . (is_logged_in() ? 'logged in' : 'not logged in') . "<br>";
} else {
    echo "❌ is_logged_in function not found<br>";
}

// Test session variables
echo "<h3>7. Current Session Variables</h3>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h3>8. Test Admin Login Form</h3>";
echo "<p><a href='frontend/adminlogin.php' target='_blank'>Open Admin Login Page</a></p>";
echo "<p><a href='frontend/admin/dashboard/index.php' target='_blank'>Open Admin Dashboard (Direct)</a></p>";
?>
