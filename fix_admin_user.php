<?php
/**
 * Fix Admin User - Fresh Connection
 */

echo "<h2>Fix Admin User</h2>";

// Create fresh database connection
$host = 'localhost';
$dbname = 'renting_place_finder';
$username = 'root';
$password = '123321555';

try {
    // Create new PDO connection
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    
    echo "✅ Fresh database connection established<br>";
    
    // Check if users table exists
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
        echo "✅ Users table created<br>";
    } else {
        echo "✅ Users table exists<br>";
    }
    
    // Check current users
    $stmt = $pdo->query("SELECT * FROM users");
    $users = $stmt->fetchAll();
    echo "Found " . count($users) . " users in database<br>";
    
    // Check if admin user exists
    $admin_phone = '0713018095';
    
    // Validate phone number format
    $phone_regex = '/^[0]{1}[7]{1}[01245678]{1}[0-9]{7}$/';
    if (!preg_match($phone_regex, $admin_phone)) {
        echo "❌ Invalid phone number format. Expected format: 07XXXXXXXX<br>";
        throw new Exception("Invalid phone number format");
    }
    echo "✅ Phone number format validated<br>";
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE phone = ?");
    $stmt->execute([$admin_phone]);
    $admin_user = $stmt->fetch();
    
    if ($admin_user) {
        echo "✅ Admin user exists:<br>";
        echo "- Name: " . $admin_user['name'] . "<br>";
        echo "- Phone: " . $admin_user['phone'] . "<br>";
        echo "- User Type: " . $admin_user['user_type'] . "<br>";
        
        if ($admin_user['user_type'] !== 'admin') {
            echo "Updating user type to admin...<br>";
            $stmt = $pdo->prepare("UPDATE users SET user_type = 'admin' WHERE phone = ?");
            $stmt->execute([$admin_phone]);
            echo "✅ User type updated to admin<br>";
        }
    } else {
        echo "❌ Admin user not found. Creating...<br>";
        
        // Insert admin user
        $stmt = $pdo->prepare("INSERT INTO users (phone, name, email, user_type, is_verified, is_active) VALUES (?, ?, ?, ?, ?, ?)");
        $result = $stmt->execute([
            $admin_phone,
            'Chinthaka Sandaruwan',
            'chinthakasw000@gmail.com',
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
    $stmt = $pdo->prepare("SELECT * FROM users WHERE phone = ?");
    $stmt->execute([$admin_phone]);
    $admin_user = $stmt->fetch();
    
    if ($admin_user && $admin_user['user_type'] === 'admin') {
        echo "<h3>✅ Admin user is ready!</h3>";
        echo "- ID: " . $admin_user['id'] . "<br>";
        echo "- Name: " . $admin_user['name'] . "<br>";
        echo "- Phone: " . $admin_user['phone'] . "<br>";
        echo "- Email: " . $admin_user['email'] . "<br>";
        echo "- User Type: " . $admin_user['user_type'] . "<br>";
        echo "- Is Verified: " . ($admin_user['is_verified'] ? 'Yes' : 'No') . "<br>";
        echo "- Is Active: " . ($admin_user['is_active'] ? 'Yes' : 'No') . "<br>";
        
        echo "<h3>🎉 Admin login should now work!</h3>";
        echo "You can now login with phone number: <strong>0713018095</strong><br>";
    } else {
        echo "❌ Admin user setup failed<br>";
    }
    
} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
}
?>
