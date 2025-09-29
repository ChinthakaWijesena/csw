<?php
/**
 * Test Admin User Script
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/backend/models/User.php';

echo "<h2>Admin User Test</h2>";

try {
    // Test database connection
    echo "<h3>1. Database Connection Test</h3>";
    global $database;
    if ($database) {
        echo "✅ Database connection successful<br>";
    } else {
        echo "❌ Database connection failed<br>";
        exit;
    }
    
    // Test User model
    echo "<h3>2. User Model Test</h3>";
    $user_model = new User();
    echo "✅ User model created successfully<br>";
    
    // Test admin user lookup
    echo "<h3>3. Admin User Lookup Test</h3>";
    $admin_phone = '0713018095';
    echo "Looking for admin user with phone: {$admin_phone}<br>";
    
    $user = $user_model->getByPhone($admin_phone);
    
    if ($user) {
        echo "✅ Admin user found!<br>";
        echo "Name: " . $user['name'] . "<br>";
        echo "Phone: " . $user['phone'] . "<br>";
        echo "Email: " . $user['email'] . "<br>";
        echo "User Type: " . $user['user_type'] . "<br>";
        echo "Is Verified: " . ($user['is_verified'] ? 'Yes' : 'No') . "<br>";
        echo "Is Active: " . ($user['is_active'] ? 'Yes' : 'No') . "<br>";
        
        if ($user['user_type'] === 'admin') {
            echo "✅ User has admin privileges<br>";
        } else {
            echo "❌ User does NOT have admin privileges<br>";
        }
    } else {
        echo "❌ Admin user NOT found<br>";
        
        // Check if any users exist
        echo "<h3>4. Check All Users</h3>";
        $all_users = $user_model->getAll(1, 10);
        if (empty($all_users)) {
            echo "❌ No users found in database<br>";
            echo "Database might not be set up. Please run the schema.sql file.<br>";
        } else {
            echo "Found " . count($all_users) . " users:<br>";
            foreach ($all_users as $u) {
                echo "- {$u['name']} ({$u['phone']}) - {$u['user_type']}<br>";
            }
        }
    }
    
    // Test phone number formatting
    echo "<h3>5. Phone Number Formatting Test</h3>";
    $test_phones = ['0713018095'];
    foreach ($test_phones as $phone) {
        $formatted = format_phone_number($phone);
        echo "Input: {$phone} → Formatted: {$formatted}<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
    echo "Stack trace: " . $e->getTraceAsString() . "<br>";
}

// Function to format phone number (updated with new regex pattern)
function format_phone_number($phone) {
    // Remove all non-digit characters
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    // Validate with new regex pattern for 07XXXXXXXX format
    if (preg_match('/^[0]{1}[7]{1}[01245678]{1}[0-9]{7}$/', $phone)) {
        // Return 07XXXXXXXX format as-is
        return $phone;
    }
    
    // Handle other Sri Lankan phone number formats
    if (strlen($phone) == 9 && substr($phone, 0, 1) == '7') {
        // Format: 7XXXXXXXX -> 07XXXXXXXX
        return '0' . $phone;
    } elseif (strlen($phone) == 12 && substr($phone, 0, 3) == '947') {
        // Format: 947XXXXXXXX -> 07XXXXXXXX
        return '0' . substr($phone, 2);
    } elseif (strlen($phone) == 13 && substr($phone, 0, 4) == '+947') {
        // Format: 947XXXXXXXX -> 07XXXXXXXX
        return '0' . substr($phone, 3);
    }
    
    return $phone; // Return as-is if no pattern matches
}
?>
