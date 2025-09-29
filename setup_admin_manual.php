<?php
/**
 * Manual Admin Setup Script
 * Run this script to create the admin user
 */

echo "<h1>Manual Admin User Setup</h1>";
echo "<p>This script will create the admin user for the application.</p>";

// Database connection details
$host = 'localhost';
$dbname = 'renting_place_finder';
$username = 'root';
$password = '123321555';

try {
    // Create database connection
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    echo "<h2>Step 1: Database Connection</h2>";
    echo "✅ Connected to database: {$dbname}<br>";
    
    // Check if users table exists
    echo "<h2>Step 2: Check Users Table</h2>";
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() == 0) {
        echo "❌ Users table does not exist. Creating...<br>";
        
        $create_sql = "
        CREATE TABLE `users` (
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
        
        $pdo->exec($create_sql);
        echo "✅ Users table created successfully<br>";
    } else {
        echo "✅ Users table exists<br>";
    }
    
    // Check current users
    echo "<h2>Step 3: Check Current Users</h2>";
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch();
    echo "Current users in database: " . $result['count'] . "<br>";
    
    // Create admin user
    echo "<h2>Step 4: Create Admin User</h2>";
    $admin_phone = '0713018095';
    $admin_name = 'Chinthaka Sandaruwan';
    $admin_email = 'chinthakasw000@gmail.com';
    
    // Validate phone number format
    $phone_regex = '/^[0]{1}[7]{1}[01245678]{1}[0-9]{7}$/';
    if (!preg_match($phone_regex, $admin_phone)) {
        echo "❌ Invalid phone number format. Expected format: 07XXXXXXXX<br>";
        throw new Exception("Invalid phone number format");
    }
    echo "✅ Phone number format validated<br>";
    
    // Check if admin user already exists
    $stmt = $pdo->prepare("SELECT * FROM users WHERE phone = ?");
    $stmt->execute([$admin_phone]);
    $existing_user = $stmt->fetch();
    
    if ($existing_user) {
        echo "✅ Admin user already exists:<br>";
        echo "- Name: " . $existing_user['name'] . "<br>";
        echo "- Phone: " . $existing_user['phone'] . "<br>";
        echo "- User Type: " . $existing_user['user_type'] . "<br>";
        
        if ($existing_user['user_type'] !== 'admin') {
            echo "Updating user type to admin...<br>";
            $stmt = $pdo->prepare("UPDATE users SET user_type = 'admin' WHERE phone = ?");
            $stmt->execute([$admin_phone]);
            echo "✅ User type updated to admin<br>";
        }
    } else {
        echo "Creating new admin user...<br>";
        
        $stmt = $pdo->prepare("INSERT INTO users (phone, name, email, user_type, is_verified, is_active) VALUES (?, ?, ?, ?, ?, ?)");
        $result = $stmt->execute([
            $admin_phone,
            $admin_name,
            $admin_email,
            'admin',
            1,
            1
        ]);
        
        if ($result) {
            echo "✅ Admin user created successfully<br>";
        } else {
            echo "❌ Failed to create admin user<br>";
        }
    }
    
    // Final verification
    echo "<h2>Step 5: Verification</h2>";
    $stmt = $pdo->prepare("SELECT * FROM users WHERE phone = ?");
    $stmt->execute([$admin_phone]);
    $admin_user = $stmt->fetch();
    
    if ($admin_user && $admin_user['user_type'] === 'admin') {
        echo "<h3>🎉 SUCCESS! Admin user is ready!</h3>";
        echo "<div style='background: #d4edda; padding: 15px; border: 1px solid #c3e6cb; border-radius: 5px; margin: 10px 0;'>";
        echo "<strong>Admin Login Details:</strong><br>";
        echo "📱 Phone: <strong>0713018095</strong><br>";
        echo "👤 Name: " . $admin_user['name'] . "<br>";
        echo "📧 Email: " . $admin_user['email'] . "<br>";
        echo "🔑 User Type: " . $admin_user['user_type'] . "<br>";
        echo "✅ Verified: " . ($admin_user['is_verified'] ? 'Yes' : 'No') . "<br>";
        echo "🟢 Active: " . ($admin_user['is_active'] ? 'Yes' : 'No') . "<br>";
        echo "</div>";
        
        echo "<p><strong>You can now login to the admin panel!</strong></p>";
        echo "<p><a href='frontend/adminlogin.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Admin Login</a></p>";
    } else {
        echo "❌ Admin user setup failed<br>";
    }
    
} catch (PDOException $e) {
    echo "<h3>❌ Database Error</h3>";
    echo "Error: " . $e->getMessage() . "<br>";
    echo "<p>Please check your database configuration and try again.</p>";
}
?>
