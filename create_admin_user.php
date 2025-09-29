<?php
/**
 * Create Admin User Script
 */

require_once __DIR__ . '/config/config.php';

echo "<h2>Create Admin User</h2>";

try {
    // Check if users table exists
    echo "<h3>1. Check Users Table</h3>";
    $result = $database->query("SHOW TABLES LIKE 'users'");
    $table_exists = $result->rowCount() > 0;
    
    if ($table_exists) {
        echo "✅ Users table exists<br>";
    } else {
        echo "❌ Users table does not exist<br>";
        echo "Creating users table...<br>";
        
        $create_table_sql = "
        CREATE TABLE IF NOT EXISTS `users` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `phone` varchar(20) NOT NULL,
            `name` varchar(100) NOT NULL,
            `email` varchar(100) DEFAULT NULL,
            `password` varchar(255) DEFAULT NULL,
            `user_type` enum('customer','owner','admin') NOT NULL DEFAULT 'customer',
            `is_verified` tinyint(1) NOT NULL DEFAULT 0,
            `is_active` tinyint(1) NOT NULL DEFAULT 1,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `phone` (`phone`),
            UNIQUE KEY `email` (`email`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        
        $database->query($create_table_sql);
        echo "✅ Users table created<br>";
    }
    
    // Check if admin user already exists
    echo "<h3>2. Check Existing Admin User</h3>";
    $admin_phone = '0713018095';
    
    // Validate phone number format
    $phone_regex = '/^[0]{1}[7]{1}[01245678]{1}[0-9]{7}$/';
    if (!preg_match($phone_regex, $admin_phone)) {
        echo "❌ Invalid phone number format. Expected format: 07XXXXXXXX<br>";
        throw new Exception("Invalid phone number format");
    }
    echo "✅ Phone number format validated<br>";
    
    $result = $database->query("SELECT * FROM users WHERE phone = ?", [$admin_phone]);
    $existing_user = $result->fetch();
    
    if ($existing_user) {
        echo "✅ Admin user already exists:<br>";
        echo "- Name: " . $existing_user['name'] . "<br>";
        echo "- Phone: " . $existing_user['phone'] . "<br>";
        echo "- Email: " . $existing_user['email'] . "<br>";
        echo "- User Type: " . $existing_user['user_type'] . "<br>";
        
        // Update user type to admin if needed
        if ($existing_user['user_type'] !== 'admin') {
            echo "Updating user type to admin...<br>";
            $database->query("UPDATE users SET user_type = 'admin' WHERE phone = ?", [$admin_phone]);
            echo "✅ User type updated to admin<br>";
        }
    } else {
        echo "❌ Admin user does not exist<br>";
        echo "Creating admin user...<br>";
        
        // Create admin user
        $insert_sql = "INSERT INTO users (phone, name, email, user_type, is_verified, is_active) VALUES (?, ?, ?, ?, ?, ?)";
        $params = [
            $admin_phone,
            'Chinthaka Sandaruwan',
            'chinthakasw000@gmail.com',
            'admin',
            1,
            1
        ];
        
        $database->query($insert_sql, $params);
        echo "✅ Admin user created successfully<br>";
    }
    
    // Verify admin user
    echo "<h3>3. Verify Admin User</h3>";
    $result = $database->query("SELECT * FROM users WHERE phone = ?", [$admin_phone]);
    $admin_user = $result->fetch();
    
    if ($admin_user) {
        echo "✅ Admin user verified:<br>";
        echo "- ID: " . $admin_user['id'] . "<br>";
        echo "- Name: " . $admin_user['name'] . "<br>";
        echo "- Phone: " . $admin_user['phone'] . "<br>";
        echo "- Email: " . $admin_user['email'] . "<br>";
        echo "- User Type: " . $admin_user['user_type'] . "<br>";
        echo "- Is Verified: " . ($admin_user['is_verified'] ? 'Yes' : 'No') . "<br>";
        echo "- Is Active: " . ($admin_user['is_active'] ? 'Yes' : 'No') . "<br>";
    } else {
        echo "❌ Admin user verification failed<br>";
    }
    
    // Test with User model
    echo "<h3>4. Test with User Model</h3>";
    require_once __DIR__ . '/backend/models/User.php';
    $user_model = new User();
    $user = $user_model->getByPhone($admin_phone);
    
    if ($user) {
        echo "✅ User model test successful:<br>";
        echo "- Name: " . $user['name'] . "<br>";
        echo "- User Type: " . $user['user_type'] . "<br>";
    } else {
        echo "❌ User model test failed<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
    echo "Stack trace: " . $e->getTraceAsString() . "<br>";
}
?>
