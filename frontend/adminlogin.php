<?php
/**
 * Admin Login Page - OTP System
 */

session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../backend/models/User.php';
require_once __DIR__ . '/../api/otp/OTPService.php';

// Redirect if already logged in as admin
if (is_logged_in() && $_SESSION['user_type'] === 'admin') {
    header('Location: admin/dashboard/index.php');
    exit;
}

$error_message = '';
$success_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'send_otp') {
        // Send OTP
        $phone = trim($_POST['phone'] ?? '');
        
        if (empty($phone)) {
            $error_message = 'Please enter your phone number.';
        } else {
            // Validate phone number
            if (!validate_phone($phone)) {
                $error_message = 'Please enter a valid Sri Lankan phone number.';
            } else {
                // Format phone number
                $formatted_phone = format_phone_number($phone);
                
                if (!$formatted_phone) {
                    $error_message = 'Please enter a valid phone number in 07XXXXXXXX format.';
                } else {
                    try {
                        $user_model = new User();
                        $user = $user_model->getByPhone($formatted_phone);
                        
                        if ($user && $user['user_type'] === 'admin') {
                            // Send OTP
                            $otp_service = new OTPService();
                            $result = $otp_service->sendOTP($formatted_phone, $otp_service->generateOTP());
                            
                            if ($result['success']) {
                                $_SESSION['admin_phone'] = $result['formatted_phone'];
                                $success_message = 'OTP sent successfully to your phone.';
                            } else {
                                $error_message = $result['message'];
                            }
                        } else {
                            if (!$user) {
                                $error_message = 'Admin user not found. Please run the database setup script first.';
                            } else {
                                $error_message = 'Access denied. Admin privileges required.';
                            }
                        }
                    } catch (Exception $e) {
                        error_log("Admin OTP send error: " . $e->getMessage());
                        $error_message = 'Failed to send OTP. Please try again.';
                    }
                }
            }
        }
    } elseif ($action === 'verify_otp') {
        // Verify OTP
        $otp = trim($_POST['otp'] ?? '');
        
        if (empty($otp)) {
            $error_message = 'Please enter the OTP code.';
        } else {
            try {
                // Check if OTP bypass is enabled for development
                if (OTP_BYPASS && DEBUG_MODE) {
                    error_log("⚠️  DEVELOPMENT MODE: OTP validation bypassed for admin login");
                    error_log("⚠️  WARNING: OTP_BYPASS is enabled - this should be disabled in production!");
                    
                    // Get admin user details
                    $user_model = new User();
                    $user = $user_model->getByPhone($_SESSION['admin_phone']);
                    
                    if ($user) {
                        // Create session token
                        $session_token = generate_token();
                        $expires_at = date('Y-m-d H:i:s', time() + SESSION_TIMEOUT);
                        
                        $database->query(
                            "INSERT INTO user_sessions (user_id, session_token, expires_at) VALUES (?, ?, ?)",
                            [$user['id'], $session_token, $expires_at]
                        );
                        
                        // Login successful - OTP bypassed in development
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['name'] = $user['name'];
                        $_SESSION['phone'] = $user['phone'];
                        $_SESSION['email'] = $user['email'];
                        $_SESSION['user_type'] = $user['user_type'];
                        $_SESSION['session_token'] = $session_token;
                        
                        // Clear OTP session
                        unset($_SESSION['admin_phone']);
                        
                        // Redirect to admin dashboard
                        header('Location: admin/dashboard/index.php');
                        exit;
                    } else {
                        $error_message = 'Admin user not found.';
                    }
                } else {
                    // Normal OTP verification (production mode)
                    $otp_service = new OTPService();
                    $result = $otp_service->verifyOTP($_SESSION['admin_phone'], $otp);
                    
                    if ($result === true) {
                        // Get admin user details
                        $user_model = new User();
                        $user = $user_model->getByPhone($_SESSION['admin_phone']);
                        
                        if ($user) {
                            // Create session token
                            $session_token = generate_token();
                            $expires_at = date('Y-m-d H:i:s', time() + SESSION_TIMEOUT);
                            
                            $database->query(
                                "INSERT INTO user_sessions (user_id, session_token, expires_at) VALUES (?, ?, ?)",
                                [$user['id'], $session_token, $expires_at]
                            );
                            
                            // Login successful
                            $_SESSION['user_id'] = $user['id'];
                            $_SESSION['name'] = $user['name'];
                            $_SESSION['phone'] = $user['phone'];
                            $_SESSION['email'] = $user['email'];
                            $_SESSION['user_type'] = $user['user_type'];
                            $_SESSION['session_token'] = $session_token;
                            
                            // Clear OTP session
                            unset($_SESSION['admin_phone']);
                            
                            // Redirect to admin dashboard
                            header('Location: admin/dashboard/index.php');
                            exit;
                        } else {
                            $error_message = 'Admin user not found.';
                        }
                    } else {
                        $error_message = 'Invalid OTP code. Please try again.';
                    }
                }
            } catch (Exception $e) {
                error_log("Admin login error: " . $e->getMessage());
                $error_message = 'Login failed. Please try again.';
            }
        }
    }
}

// Function to format phone number - accepts 07XXXXXXXX format only
function format_phone_number($phone) {
    // Remove all non-digit characters
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    // Accept 07XXXXXXXX format (local) - validate with new regex pattern
    if (preg_match('/^[0]{1}[7]{1}[01245678]{1}[0-9]{7}$/', $phone)) {
        return $phone;
    }
    
    // Convert 947XXXXXXXX to 07XXXXXXXX format
    if (preg_match('/^947[0-9]{8}$/', $phone)) {
        return '0' . substr($phone, 2);
    }
    
    return false; // Invalid format
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - <?php echo APP_NAME; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body class="d-flex flex-column min-vh-100">
    <!-- Include Navbar -->
    <?php include 'includes/navbar.php'; ?>
    <!-- Admin Login Container -->
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card shadow">
                    <div class="card-body p-4">
                        <div class="text-center mb-4">
                            <div class="mb-3">
                                <i class="fas fa-shield-alt fa-3x text-primary"></i>
                            </div>
                            <h2 class="card-title">Admin Login</h2>
                            <p class="text-muted">Access the administrative dashboard with OTP verification</p>
                        </div>
                        
                        <?php if ($error_message): ?>
                            <div class="alert alert-danger d-flex align-items-center" role="alert">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                <?php echo htmlspecialchars($error_message); ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($success_message): ?>
                            <div class="alert alert-success d-flex align-items-center" role="alert">
                                <i class="fas fa-check-circle me-2"></i>
                                <?php echo htmlspecialchars($success_message); ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!isset($_SESSION['admin_phone'])): ?>
                            <!-- Phone Number Form -->
                            <form method="POST" action="">
                                <input type="hidden" name="action" value="send_otp">
                                
                                <div class="mb-3">
                                    <label for="phone" class="form-label">
                                        <i class="fas fa-phone me-1"></i> Admin Phone Number
                                    </label>
                                    <input 
                                        type="tel" 
                                        id="phone" 
                                        name="phone" 
                                        class="form-control" 
                                        placeholder="0713018095"
                                        value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>"
                                        required
                                    >
                                    <div class="form-text">
                                        Enter your Sri Lankan phone number in 07XXXXXXXX format
                                    </div>
                                </div>
                                
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="fas fa-paper-plane me-2"></i> Send OTP
                                    </button>
                                </div>
                            </form>
                        <?php else: ?>
                            <!-- OTP Verification Form -->
                            <?php if (OTP_BYPASS && DEBUG_MODE): ?>
                                <div class="alert alert-warning d-flex align-items-center mb-3" role="alert">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    <div>
                                        <strong>Development Mode:</strong> OTP validation is bypassed. Any OTP will be accepted.
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <form method="POST" action="">
                                <input type="hidden" name="action" value="verify_otp">
                                
                                <div class="mb-3">
                                    <div class="alert alert-info d-flex align-items-center" role="alert">
                                        <i class="fas fa-info-circle me-2"></i>
                                        <div>
                                            OTP sent to: <strong><?php echo htmlspecialchars($_SESSION['admin_phone']); ?></strong>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="otp" class="form-label">
                                        <i class="fas fa-key me-1"></i> Enter OTP Code
                                    </label>
                                    <input 
                                        type="text" 
                                        id="otp" 
                                        name="otp" 
                                        class="form-control text-center" 
                                        placeholder="Enter 6-digit OTP code"
                                        maxlength="6"
                                        required
                                    >
                                    <div class="form-text">
                                        Enter the 6-digit code sent to your phone
                                    </div>
                                </div>
                                
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="fas fa-sign-in-alt me-2"></i> Verify & Login
                                    </button>
                                </div>
                            </form>
                        <?php endif; ?>
                        
                        <hr class="my-4">
                        
                        <div class="text-center">
                            <a href="login.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i> Back to User Login
                            </a>
                        </div>
                        
                        <?php if (DEBUG_MODE && DEV_FIXED_OTP_ENABLED): ?>
                            <div class="alert alert-warning d-flex align-items-center mt-3" role="alert">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <div>
                                    <strong>⚠️ DEVELOPMENT MODE:</strong> Use fixed OTP code <code><?php echo DEV_FIXED_OTP; ?></code> for admin login.
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Phone number formatting - only allow 07XXXXXXXX format
        const phoneInput = document.getElementById('phone');
        if (phoneInput) {
            phoneInput.addEventListener('input', function(e) {
                let value = e.target.value.replace(/[^0-9]/g, '');
                
                // Only allow 07XXXXXXXX format
                if (value.length > 0) {
                    if (value.startsWith('0')) {
                        // Format: 07XXXXXXXX
                        if (value.length <= 10) {
                            e.target.value = value;
                        } else {
                            e.target.value = value.substring(0, 10);
                        }
                    } else if (value.startsWith('7')) {
                        // Auto-add 0 prefix
                        e.target.value = '0' + value;
                    } else if (value.startsWith('94')) {
                        // Convert 94 to 07 format
                        e.target.value = '0' + value.substring(2);
                    } else if (value.length > 0) {
                        // Clear invalid input
                        e.target.value = '';
                    }
                }
            });
        }
        
        // OTP input formatting
        const otpInput = document.getElementById('otp');
        if (otpInput) {
            otpInput.addEventListener('input', function(e) {
                // Only allow numbers and limit to 6 digits
                e.target.value = e.target.value.replace(/\D/g, '').substring(0, 6);
            });
        }
        
        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const phone = document.getElementById('phone');
            const otp = document.getElementById('otp');
            
            if (phone && !phone.value.trim()) {
                e.preventDefault();
                alert('Please enter your phone number.');
                return;
            }
            
            if (otp && !otp.value.trim()) {
                e.preventDefault();
                alert('Please enter the OTP code.');
                return;
            }
            
            // Basic phone validation - accepts only 07XXXXXXXX format
            if (phone) {
                const phoneRegex = /^[0]{1}[7]{1}[01245678]{1}[0-9]{7}$/;
                if (!phoneRegex.test(phone.value)) {
                    e.preventDefault();
                    alert('Please enter a valid phone number in 07XXXXXXXX format.');
                    return;
                }
            }
        });
    </script>

    <!-- Include Footer -->
    <?php include 'includes/footer.php'; ?>
</body>
</html>
