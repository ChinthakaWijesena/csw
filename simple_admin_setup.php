<?php
/**
 * Simple Admin User Setup
 */

// Database connection
$host = 'localhost';
$dbname = 'renting_place_finder';
$username = 'root';
$password = '123321555';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>Simple Admin Setup</h2>";
    
    // Define admin phone number
    $admin_phone = '0713018095';
    
    // Validate phone number format
    $phone_regex = '/^[0]{1}[7]{1}[01245678]{1}[0-9]{7}$/';
    if (!preg_match($phone_regex, $admin_phone)) {
        echo "❌ Invalid phone number format. Expected format: 07XXXXXXXX<br>";
        throw new Exception("Invalid phone number format");
    }
    echo "✅ Phone number format validated<br>";
    
    // Check if admin user exists
    $stmt = $pdo->prepare("SELECT * FROM users WHERE phone = ?");
    $stmt->execute([$admin_phone]);
    $user = $stmt->fetch();
    
    if ($user) {
        echo "✅ Admin user already exists:<br>";
        echo "- Name: " . $user['name'] . "<br>";
        echo "- Phone: " . $user['phone'] . "<br>";
        echo "- User Type: " . $user['user_type'] . "<br>";
        
        // Update to admin if needed
        if ($user['user_type'] !== 'admin') {
            $stmt = $pdo->prepare("UPDATE users SET user_type = 'admin' WHERE phone = ?");
            $stmt->execute([$admin_phone]);
            echo "✅ Updated user type to admin<br>";
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
    
    // Verify
    $stmt = $pdo->prepare("SELECT * FROM users WHERE phone = ?");
    $stmt->execute([$admin_phone]);
    $user = $stmt->fetch();
    
    if ($user && $user['user_type'] === 'admin') {
        echo "<h3>✅ Admin user is ready!</h3>";
        echo "- Name: " . $user['name'] . "<br>";
        echo "- Phone: " . $user['phone'] . "<br>";
        echo "- Email: " . $user['email'] . "<br>";
        echo "- User Type: " . $user['user_type'] . "<br>";
        echo "- Is Verified: " . ($user['is_verified'] ? 'Yes' : 'No') . "<br>";
        echo "- Is Active: " . ($user['is_active'] ? 'Yes' : 'No') . "<br>";
    } else {
        echo "❌ Admin user setup failed<br>";
    }
    
} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
}
?>
