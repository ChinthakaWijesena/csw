<?php
/**
 * Initialize Settings Script
 * Run this script to set up default settings in the database
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/backend/controllers/SettingsController.php';

echo "<h2>Settings Initialization</h2>";

try {
    $controller = new SettingsController();
    
    echo "<h3>1. Initializing Default Settings</h3>";
    $result = $controller->initializeDefaults();
    
    if ($result['success']) {
        echo "✅ " . $result['message'] . "<br>";
    } else {
        echo "❌ " . $result['message'] . "<br>";
    }
    
    echo "<h3>2. Verifying Settings</h3>";
    $settings = $controller->getAllSettings();
    
    $categories = ['general', 'payment', 'sms', 'email', 'security'];
    $total_settings = 0;
    
    foreach ($categories as $category) {
        if (isset($settings[$category])) {
            $count = count($settings[$category]);
            $total_settings += $count;
            echo "✅ {$category}: {$count} settings<br>";
        }
    }
    
    echo "<h3>3. Settings Summary</h3>";
    echo "Total settings initialized: <strong>{$total_settings}</strong><br>";
    
    echo "<h3>4. Key Settings</h3>";
    $key_settings = [
        'app_name' => 'Application Name',
        'app_phone' => 'Contact Phone',
        'commission_rate' => 'Commission Rate',
        'sms_enabled' => 'SMS Enabled',
        'email_enabled' => 'Email Enabled'
    ];
    
    foreach ($key_settings as $key => $label) {
        $value = $controller->getAllSettings();
        $found = false;
        foreach ($value as $category => $settings) {
            if (isset($settings[$key])) {
                echo "✅ {$label}: {$settings[$key]['value']}<br>";
                $found = true;
                break;
            }
        }
        if (!$found) {
            echo "❌ {$label}: Not found<br>";
        }
    }
    
    echo "<h3>🎉 Settings initialization completed!</h3>";
    echo "<p>You can now access the admin settings page to manage your application configuration.</p>";
    
} catch (Exception $e) {
    echo "<h3>❌ Error</h3>";
    echo "Error: " . $e->getMessage() . "<br>";
    echo "Please check your database connection and try again.";
}
?>
