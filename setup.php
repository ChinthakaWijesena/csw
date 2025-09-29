<?php
/**
 * Setup Script for Renting Place Finder
 * Run this script to set up the database and initial configuration
 */

// Check if setup is already completed
if (file_exists('config/setup_complete.flag')) {
    die('Setup has already been completed. Delete config/setup_complete.flag to run setup again.');
}

$step = $_GET['step'] ?? 1;
$error = '';
$success = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($step == 2) {
        // Database setup
        try {
            $host = $_POST['db_host'] ?? 'localhost';
            $dbname = $_POST['db_name'] ?? 'renting_place_finder';
            $username = $_POST['db_username'] ?? 'root';
            $password = $_POST['db_password'] ?? '';
            
            // Test database connection
            $dsn = "mysql:host={$host};charset=utf8mb4";
            $pdo = new PDO($dsn, $username, $password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Create database if it doesn't exist
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbname}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("USE `{$dbname}`");
            
            // Import schema
            $schema = file_get_contents('database/schema.sql');
            $statements = explode(';', $schema);
            
            foreach ($statements as $statement) {
                $statement = trim($statement);
                if (!empty($statement)) {
                    $pdo->exec($statement);
                }
            }
            
            // Update database configuration
            $config_content = file_get_contents('config/database.php');
            $config_content = str_replace("private \$host = 'localhost';", "private \$host = '{$host}';", $config_content);
            $config_content = str_replace("private \$db_name = 'renting_place_finder';", "private \$db_name = '{$dbname}';", $config_content);
            $config_content = str_replace("private \$username = 'root';", "private \$username = '{$username}';", $config_content);
            $config_content = str_replace("private \$password = '';", "private \$password = '{$password}';", $config_content);
            file_put_contents('config/database.php', $config_content);
            
            $success = 'Database setup completed successfully!';
            $step = 3;
            
        } catch (Exception $e) {
            $error = 'Database setup failed: ' . $e->getMessage();
        }
    } elseif ($step == 3) {
        // Application configuration
        $app_url = $_POST['app_url'] ?? 'http://localhost/gov_project';
        $debug_mode = isset($_POST['debug_mode']) ? 'true' : 'false';
        $sms_provider = $_POST['sms_provider'] ?? 'test';
        $payment_provider = $_POST['payment_provider'] ?? 'manual';
        
        // Update configuration
        $config_content = file_get_contents('config/config.php');
        $config_content = str_replace("define('APP_URL', 'http://localhost/gov_project');", "define('APP_URL', '{$app_url}');", $config_content);
        $config_content = str_replace("define('DEBUG_MODE', true);", "define('DEBUG_MODE', {$debug_mode});", $config_content);
        $config_content = str_replace("define('SMS_PROVIDER', 'vonage');", "define('SMS_PROVIDER', '{$sms_provider}');", $config_content);
        $config_content = str_replace("define('PAYMENT_PROVIDER', 'stripe');", "define('PAYMENT_PROVIDER', '{$payment_provider}');", $config_content);
        file_put_contents('config/config.php', $config_content);
        
        // Create upload directories
        if (!file_exists('assets/uploads')) {
            mkdir('assets/uploads', 0755, true);
            mkdir('assets/uploads/properties', 0755, true);
            mkdir('assets/uploads/users', 0755, true);
        }
        
        // Import sample data if requested
        if (isset($_POST['import_sample_data'])) {
            try {
                require_once 'config/config.php';
                $sample_data = file_get_contents('database/sample_data.sql');
                $statements = explode(';', $sample_data);
                
                foreach ($statements as $statement) {
                    $statement = trim($statement);
                    if (!empty($statement)) {
                        $database->query($statement);
                    }
                }
                $success .= ' Sample data imported successfully!';
            } catch (Exception $e) {
                $error .= ' Sample data import failed: ' . $e->getMessage();
            }
        }
        
        // Create setup complete flag
        file_put_contents('config/setup_complete.flag', date('Y-m-d H:i:s'));
        
        $success = 'Setup completed successfully! You can now access your application.';
        $step = 4;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup - Renting Place Finder</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h2 class="mb-0">
                            <i class="fas fa-cog me-2"></i>Renting Place Finder Setup
                        </h2>
                    </div>
                    <div class="card-body">
                        <!-- Progress Steps -->
                        <div class="mb-4">
                            <div class="progress mb-3" style="height: 8px;">
                                <div class="progress-bar" style="width: <?php echo ($step / 4) * 100; ?>%"></div>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="badge bg-<?php echo $step >= 1 ? 'primary' : 'secondary'; ?>">1. Welcome</span>
                                <span class="badge bg-<?php echo $step >= 2 ? 'primary' : 'secondary'; ?>">2. Database</span>
                                <span class="badge bg-<?php echo $step >= 3 ? 'primary' : 'secondary'; ?>">3. Configuration</span>
                                <span class="badge bg-<?php echo $step >= 4 ? 'primary' : 'secondary'; ?>">4. Complete</span>
                            </div>
                        </div>

                        <?php if ($error): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($success): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($step == 1): ?>
                            <!-- Step 1: Welcome -->
                            <div class="text-center mb-4">
                                <i class="fas fa-home fa-4x text-primary mb-3"></i>
                                <h3>Welcome to Renting Place Finder</h3>
                                <p class="text-muted">Let's set up your rental platform in a few simple steps.</p>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <div class="card h-100">
                                        <div class="card-body text-center">
                                            <i class="fas fa-database fa-2x text-primary mb-2"></i>
                                            <h5>Database Setup</h5>
                                            <p class="text-muted">Configure MySQL database connection</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="card h-100">
                                        <div class="card-body text-center">
                                            <i class="fas fa-cog fa-2x text-primary mb-2"></i>
                                            <h5>Configuration</h5>
                                            <p class="text-muted">Set up application settings</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="alert alert-info">
                                <h5><i class="fas fa-info-circle me-2"></i>Prerequisites</h5>
                                <ul class="mb-0">
                                    <li>PHP 7.4 or higher</li>
                                    <li>MySQL 5.7 or higher</li>
                                    <li>Web server (Apache/Nginx)</li>
                                    <li>SSL certificate (for production)</li>
                                </ul>
                            </div>
                            
                            <div class="text-center">
                                <a href="?step=2" class="btn btn-primary btn-lg">
                                    <i class="fas fa-arrow-right me-2"></i>Start Setup
                                </a>
                            </div>

                        <?php elseif ($step == 2): ?>
                            <!-- Step 2: Database Configuration -->
                            <h3>Database Configuration</h3>
                            <p class="text-muted">Enter your MySQL database credentials.</p>
                            
                            <form method="POST">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="db_host" class="form-label">Database Host</label>
                                        <input type="text" class="form-control" id="db_host" name="db_host" value="localhost" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="db_name" class="form-label">Database Name</label>
                                        <input type="text" class="form-control" id="db_name" name="db_name" value="renting_place_finder" required>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="db_username" class="form-label">Username</label>
                                        <input type="text" class="form-control" id="db_username" name="db_username" value="root" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="db_password" class="form-label">Password</label>
                                        <input type="password" class="form-control" id="db_password" name="db_password">
                                    </div>
                                </div>
                                
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    The database will be created automatically if it doesn't exist.
                                </div>
                                
                                <div class="d-flex justify-content-between">
                                    <a href="?step=1" class="btn btn-outline-secondary">
                                        <i class="fas fa-arrow-left me-2"></i>Back
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-arrow-right me-2"></i>Continue
                                    </button>
                                </div>
                            </form>

                        <?php elseif ($step == 3): ?>
                            <!-- Step 3: Application Configuration -->
                            <h3>Application Configuration</h3>
                            <p class="text-muted">Configure your application settings.</p>
                            
                            <form method="POST">
                                <div class="mb-3">
                                    <label for="app_url" class="form-label">Application URL</label>
                                    <input type="url" class="form-control" id="app_url" name="app_url" value="http://localhost/gov_project" required>
                                    <div class="form-text">The full URL where your application will be accessible.</div>
                                </div>
                                
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="debug_mode" name="debug_mode" checked>
                                        <label class="form-check-label" for="debug_mode">
                                            Enable Debug Mode
                                        </label>
                                        <div class="form-text">Enable for development, disable for production.</div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="sms_provider" class="form-label">SMS Provider</label>
                                        <select class="form-select" id="sms_provider" name="sms_provider">
                                            <option value="test">Test Mode (Development)</option>
                                            <option value="vonage">Vonage</option>
                                            <option value="payhere">PayHere SMS</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="payment_provider" class="form-label">Payment Provider</label>
                                        <select class="form-select" id="payment_provider" name="payment_provider">
                                            <option value="manual">Manual Bank Transfer</option>
                                            <option value="stripe">Stripe</option>
                                            <option value="payhere">PayHere</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="import_sample_data" name="import_sample_data" checked>
                                        <label class="form-check-label" for="import_sample_data">
                                            Import Sample Data
                                        </label>
                                        <div class="form-text">Import sample users, properties, and data for testing.</div>
                                    </div>
                                </div>
                                
                                <div class="d-flex justify-content-between">
                                    <a href="?step=2" class="btn btn-outline-secondary">
                                        <i class="fas fa-arrow-left me-2"></i>Back
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-check me-2"></i>Complete Setup
                                    </button>
                                </div>
                            </form>

                        <?php elseif ($step == 4): ?>
                            <!-- Step 4: Setup Complete -->
                            <div class="text-center">
                                <i class="fas fa-check-circle fa-4x text-success mb-3"></i>
                                <h3>Setup Complete!</h3>
                                <p class="text-muted">Your Renting Place Finder application is ready to use.</p>
                            </div>
                            
                            <div class="alert alert-success">
                                <h5><i class="fas fa-info-circle me-2"></i>Default Admin Account</h5>
                                <p class="mb-0">
                                    <strong>Phone:</strong> +1234567890<br>
                                    <strong>OTP:</strong> 111111 (in test mode)
                                </p>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <div class="card">
                                        <div class="card-body text-center">
                                            <i class="fas fa-globe fa-2x text-primary mb-2"></i>
                                            <h5>Visit Your Site</h5>
                                            <p class="text-muted">Access your application</p>
                                            <a href="frontend/index.php" class="btn btn-primary">Go to Site</a>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="card">
                                        <div class="card-body text-center">
                                            <i class="fas fa-cog fa-2x text-primary mb-2"></i>
                                            <h5>Admin Panel</h5>
                                            <p class="text-muted">Manage your application</p>
                                            <a href="admin/dashboard/index.php" class="btn btn-outline-primary">Admin Panel</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="alert alert-warning">
                                <h5><i class="fas fa-exclamation-triangle me-2"></i>Important Security Notes</h5>
                                <ul class="mb-0">
                                    <li>Delete this setup.php file after installation</li>
                                    <li>Configure SSL certificate for production</li>
                                    <li>Update SMS and payment provider credentials</li>
                                    <li>Set DEBUG_MODE to false in production</li>
                                </ul>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
