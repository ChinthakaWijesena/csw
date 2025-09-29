<?php
/**
 * Insert Admin User - Simple Approach
 */

require_once __DIR__ . '/config/config.php';

echo "<h2>Insert Admin User</h2>";

try {
    // Use the existing database connection
    global $database;
    
    // First, let's check what's in the database
    echo "<h3>1. Check Current Users</h3>";
    $result = $database->query("SELECT * FROM users");
    $users = $result->fetchAll();
    
    echo "Found " . count($users) . " users in database:<br>";
    foreach ($users as $user) {
        echo "- ID: {$user['id']}, Name: {$user['name']}, Phone: {$user['phone']}, Type: {$user['user_type']}<br>";
    }
    
    // Check if admin user exists
    echo "<h3>2. Check Admin User</h3>";
    $admin_phone = '0713018095';
    
    // Validate phone number format
    $phone_regex = '/^[0]{1}[7]{1}[01245678]{1}[0-9]{7}$/';
    if (!preg_match($phone_regex, $admin_phone)) {
        echo "❌ Invalid phone number format. Expected format: 07XXXXXXXX<br>";
        throw new Exception("Invalid phone number format");
    }
    echo "✅ Phone number format validated<br>";
    
    $result = $database->query("SELECT * FROM users WHERE phone = ?", [$admin_phone]);
    $admin_user = $result->fetch();
    
    if ($admin_user) {
        echo "✅ Admin user exists:<br>";
        echo "- Name: " . $admin_user['name'] . "<br>";
        echo "- Phone: " . $admin_user['phone'] . "<br>";
        echo "- User Type: " . $admin_user['user_type'] . "<br>";
        
        if ($admin_user['user_type'] !== 'admin') {
            echo "Updating user type to admin...<br>";
            $database->query("UPDATE users SET user_type = 'admin' WHERE phone = ?", [$admin_phone]);
            echo "✅ User type updated to admin<br>";
        }
    } else {
        echo "❌ Admin user not found. Creating...<br>";
        
        // Try to insert admin user
        try {
            $sql = "INSERT INTO users (phone, name, email, user_type, is_verified, is_active) VALUES (?, ?, ?, ?, ?, ?)";
            $params = [
                $admin_phone,
                'Chinthaka Sandaruwan',
                'chinthakasw000@gmail.com',
                'admin',
                1,
                1
            ];
            
            $database->query($sql, $params);
            echo "✅ Admin user created successfully<br>";
        } catch (Exception $e) {
            echo "❌ Failed to create admin user: " . $e->getMessage() . "<br>";
            
            // Try alternative approach - check if user exists with different phone format
            echo "Checking for user with different phone formats...<br>";
            $formats = ['0713018095'];
            
            foreach ($formats as $format) {
                $result = $database->query("SELECT * FROM users WHERE phone = ?", [$format]);
                $user = $result->fetch();
                if ($user) {
                    echo "Found user with phone format '{$format}': {$user['name']} ({$user['user_type']})<br>";
                    
                    // Update to admin
                    $database->query("UPDATE users SET user_type = 'admin', phone = ? WHERE phone = ?", [$admin_phone, $format]);
                    echo "✅ Updated user to admin with correct phone format<br>";
                    break;
                }
            }
        }
    }
    
    // Final verification
    echo "<h3>3. Final Verification</h3>";
    $result = $database->query("SELECT * FROM users WHERE phone = ?", [$admin_phone]);
    $admin_user = $result->fetch();
    
    if ($admin_user && $admin_user['user_type'] === 'admin') {
        echo "✅ Admin user is ready for login!<br>";
        echo "- Name: " . $admin_user['name'] . "<br>";
        echo "- Phone: " . $admin_user['phone'] . "<br>";
        echo "- Email: " . $admin_user['email'] . "<br>";
        echo "- User Type: " . $admin_user['user_type'] . "<br>";
        echo "- Is Verified: " . ($admin_user['is_verified'] ? 'Yes' : 'No') . "<br>";
        echo "- Is Active: " . ($admin_user['is_active'] ? 'Yes' : 'No') . "<br>";
    } else {
        echo "❌ Admin user setup incomplete<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}
?>
