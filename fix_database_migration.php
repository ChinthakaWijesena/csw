<?php
/**
 * Fix Database Migration - Add Missing subscription_id Column
 */

require_once __DIR__ . '/config/config.php';

echo "🔧 Fixing Database Migration Issues...\n\n";

try {
    // Check if subscription_id column exists
    $result = $database->fetch("SHOW COLUMNS FROM rent_payments LIKE 'subscription_id'");
    
    if ($result) {
        echo "✅ subscription_id column already exists in rent_payments table\n";
    } else {
        echo "🔧 Adding subscription_id column to rent_payments table...\n";
        
        // Add the column
        $database->query("ALTER TABLE rent_payments ADD COLUMN subscription_id INT AFTER booking_id");
        echo "✅ Added subscription_id column\n";
        
        // Add foreign key constraint
        $database->query("ALTER TABLE rent_payments ADD CONSTRAINT fk_subscription_id FOREIGN KEY (subscription_id) REFERENCES subscriptions(id) ON DELETE SET NULL");
        echo "✅ Added foreign key constraint\n";
    }
    
    // Verify the column was added successfully
    $columns = $database->fetchAll("SHOW COLUMNS FROM rent_payments");
    $has_subscription_id = false;
    
    foreach ($columns as $column) {
        if ($column['Field'] === 'subscription_id') {
            $has_subscription_id = true;
            break;
        }
    }
    
    if ($has_subscription_id) {
        echo "✅ Database migration completed successfully!\n";
        echo "✅ subscription_id column is now available in rent_payments table\n";
    } else {
        echo "❌ Failed to add subscription_id column\n";
    }
    
    // Test database connection
    $test_result = $database->fetch("SELECT 1 as test");
    if ($test_result && $test_result['test'] == 1) {
        echo "✅ Database connection is working\n";
    } else {
        echo "❌ Database connection test failed\n";
    }
    
    echo "\n🎉 Database migration fix completed!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "🔧 Please check your database connection and try again\n";
}
?>
