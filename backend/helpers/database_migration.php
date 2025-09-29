<?php
/**
 * Database Migration Helper
 * Ensures all required tables and columns exist
 */

/**
 * Check if table exists
 */
function table_exists($table_name) {
    global $database;
    
    try {
        $result = $database->query("SHOW TABLES LIKE '$table_name'");
        return $result->rowCount() > 0;
    } catch (Exception $e) {
        error_log("Error checking table existence: " . $e->getMessage());
        return false;
    }
}

/**
 * Check if column exists in table
 */
function column_exists($table_name, $column_name) {
    global $database;
    
    try {
        $result = $database->query("SHOW COLUMNS FROM `$table_name` LIKE '$column_name'");
        return $result->rowCount() > 0;
    } catch (Exception $e) {
        error_log("Error checking column existence: " . $e->getMessage());
        return false;
    }
}

/**
 * Run database migration
 */
function run_database_migration() {
    global $database;
    
    try {
        // Check if migration has been run
        if (!table_exists('migrations')) {
            // Create migrations table
            $database->query("
                CREATE TABLE `migrations` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `migration` varchar(255) NOT NULL,
                    `batch` int(11) NOT NULL,
                    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }
        
        // Check if all required tables exist
        $required_tables = [
            'users', 'otp_verifications', 'user_sessions', 'properties', 
            'property_images', 'property_amenities', 'property_views', 'user_favorites',
            'property_inquiries', 'property_types', 'provinces', 'districts', 
            'cities', 'rental_bookings', 'property_sales', 'rent_payments', 
            'sale_payments', 'subscriptions', 'visit_requests', 'admin_logs',
            'system_settings', 'notifications', 'property_availability'
        ];
        
        $missing_tables = [];
        foreach ($required_tables as $table) {
            if (!table_exists($table)) {
                $missing_tables[] = $table;
            }
        }
        
        if (!empty($missing_tables)) {
            // Attempt to create critical missing tables used by the app runtime
            foreach ($missing_tables as $tbl) {
                switch ($tbl) {
                    case 'subscriptions':
                        $database->query("CREATE TABLE IF NOT EXISTS `subscriptions` (
                            `id` INT(11) NOT NULL AUTO_INCREMENT,
                            `property_id` INT(11) NOT NULL,
                            `customer_id` INT(11) NOT NULL,
                            `owner_id` INT(11) NOT NULL,
                            `monthly_amount` DECIMAL(12,2) NOT NULL,
                            `start_date` DATE NOT NULL,
                            `next_payment_date` DATE NOT NULL,
                            `last_payment_date` DATETIME NULL DEFAULT NULL,
                            `status` ENUM('active','cancelled','paused') NOT NULL DEFAULT 'active',
                            `payment_method` VARCHAR(50) DEFAULT 'payhere',
                            `auto_renew` TINYINT(1) NOT NULL DEFAULT 1,
                            `cancellation_reason` TEXT NULL,
                            `cancelled_at` DATETIME NULL DEFAULT NULL,
                            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                            PRIMARY KEY (`id`),
                            INDEX `idx_customer` (`customer_id`),
                            INDEX `idx_owner` (`owner_id`),
                            INDEX `idx_property` (`property_id`)
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
                        break;
                    case 'rent_payments':
                        $database->query("CREATE TABLE IF NOT EXISTS `rent_payments` (
                            `id` INT(11) NOT NULL AUTO_INCREMENT,
                            `booking_id` INT(11) NULL DEFAULT NULL,
                            `subscription_id` INT(11) NULL DEFAULT NULL,
                            `customer_id` INT(11) NOT NULL,
                            `property_id` INT(11) NOT NULL,
                            `owner_id` INT(11) NOT NULL,
                            `amount` DECIMAL(12,2) NOT NULL,
                            `payment_method` VARCHAR(50) DEFAULT NULL,
                            `payment_status` ENUM('pending','completed','failed','refunded') NOT NULL DEFAULT 'pending',
                            `payment_reference` VARCHAR(255) NULL DEFAULT NULL,
                            `due_date` DATE NOT NULL,
                            `paid_date` DATE NULL DEFAULT NULL,
                            `commission_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                            `owner_payout_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                            `is_recurring` TINYINT(1) NOT NULL DEFAULT 0,
                            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                            PRIMARY KEY (`id`),
                            INDEX `idx_owner` (`owner_id`),
                            INDEX `idx_customer` (`customer_id`),
                            INDEX `idx_property` (`property_id`)
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
                        break;
                    default:
                        // leave others for full SQL import; they are not critical for current flows
                        break;
                }
            }

            // Re-check critical tables existence; if still missing, abort migration
            $critical_tables = ['subscriptions'];
            foreach ($critical_tables as $ct) {
                if (!table_exists($ct)) {
                    if (!isset($_SERVER['REQUEST_URI']) || strpos($_SERVER['REQUEST_URI'], '/api/') === false) {
                        error_log("Critical table missing after creation attempt: {$ct}");
                    }
                    return false;
                }
            }
        }
        
        // Check for missing columns in existing tables
        $column_checks = [
            'properties' => ['price', 'offer_type', 'furnishing', 'property_status', 'featured_property', 'availability_status'],
            'rent_payments' => ['subscription_id', 'commission_amount', 'owner_payout_amount'],
            'users' => ['user_type', 'is_verified', 'is_active']
        ];
        
        foreach ($column_checks as $table => $columns) {
            foreach ($columns as $column) {
                if (!column_exists($table, $column)) {
                    error_log("Missing column $column in table $table");
                    return false;
                }
            }
        }
        
        // Check if admin user exists
        $admin_user = $database->fetch(
            "SELECT id FROM users WHERE user_type = 'admin' LIMIT 1"
        );
        
        if (!$admin_user) {
            // Create default admin user
            $admin_password = hash_password('admin123');
            $database->query(
                "INSERT INTO users (phone, name, email, user_type, is_verified, is_active) 
                 VALUES (?, ?, ?, 'admin', 1, 1)",
                ['0713018095', 'Chinthaka Sandaruwan', 'admin@rentingplace.com']
            );
            
            error_log("Default admin user created");
        }
        
        // Check if property types exist
        $property_types_count = $database->fetch(
            "SELECT COUNT(*) as count FROM property_types"
        );
        
        if ($property_types_count['count'] == 0) {
            // Insert default property types
            $default_types = [
                ['type_key' => 'apartment', 'type_name' => 'Apartment', 'sort_order' => 1],
                ['type_key' => 'house', 'type_name' => 'House', 'sort_order' => 2],
                ['type_key' => 'condo', 'type_name' => 'Condo', 'sort_order' => 3],
                ['type_key' => 'studio', 'type_name' => 'Studio', 'sort_order' => 4],
                ['type_key' => 'room', 'type_name' => 'Room', 'sort_order' => 5],
                ['type_key' => 'villa', 'type_name' => 'Villa', 'sort_order' => 6],
                ['type_key' => 'townhouse', 'type_name' => 'Townhouse', 'sort_order' => 7],
                ['type_key' => 'penthouse', 'type_name' => 'Penthouse', 'sort_order' => 8],
                ['type_key' => 'commercial', 'type_name' => 'Commercial', 'sort_order' => 9],
                ['type_key' => 'land', 'type_name' => 'Land', 'sort_order' => 10]
            ];
            
            foreach ($default_types as $type) {
                $database->query(
                    "INSERT INTO property_types (type_key, type_name, sort_order, is_active) 
                     VALUES (?, ?, ?, 1)",
                    [$type['type_key'], $type['type_name'], $type['sort_order']]
                );
            }
            
            error_log("Default property types created");
        }
        
        // Check if provinces exist
        $provinces_count = $database->fetch(
            "SELECT COUNT(*) as count FROM provinces"
        );
        
        if ($provinces_count['count'] == 0) {
            // Insert Sri Lankan provinces
            $provinces = [
                ['name' => 'Western Province', 'code' => 'WP', 'sort_order' => 1],
                ['name' => 'Central Province', 'code' => 'CP', 'sort_order' => 2],
                ['name' => 'Southern Province', 'code' => 'SP', 'sort_order' => 3],
                ['name' => 'Northern Province', 'code' => 'NP', 'sort_order' => 4],
                ['name' => 'Eastern Province', 'code' => 'EP', 'sort_order' => 5],
                ['name' => 'North Western Province', 'code' => 'NWP', 'sort_order' => 6],
                ['name' => 'North Central Province', 'code' => 'NCP', 'sort_order' => 7],
                ['name' => 'Sabaragamuwa Province', 'code' => 'SBP', 'sort_order' => 8],
                ['name' => 'Uva Province', 'code' => 'UP', 'sort_order' => 9]
            ];
            
            foreach ($provinces as $province) {
                $database->query(
                    "INSERT INTO provinces (name, code, sort_order, is_active) 
                     VALUES (?, ?, ?, 1)",
                    [$province['name'], $province['code'], $province['sort_order']]
                );
            }
            
            error_log("Sri Lankan provinces created");
        }
        
        return true;
        
    } catch (Exception $e) {
        // Only log if not in API context
        if (!isset($_SERVER['REQUEST_URI']) || strpos($_SERVER['REQUEST_URI'], '/api/') === false) {
            error_log("Database migration failed: " . $e->getMessage());
        }
        return false;
    }
}

/**
 * Initialize database
 */
function init_database() {
    // Run migration
    if (!run_database_migration()) {
        // Only log if not in API context
        if (!isset($_SERVER['REQUEST_URI']) || strpos($_SERVER['REQUEST_URI'], '/api/') === false) {
            error_log("Database migration failed");
        }
        return false;
    }
    
    // Clean old sessions
    clean_old_sessions();
    
    return true;
}
?>
