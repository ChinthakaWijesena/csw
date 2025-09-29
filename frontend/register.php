<?php
/**
 * Registration Page with SMS OTP Authentication
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../api/otp/OTPService.php';
require_once __DIR__ . '/../backend/models/User.php';

// Redirect if already logged in
if (is_logged_in()) {
    redirect(APP_URL . '/frontend/dashboard.php');
}

$error_message = '';
$success_message = '';

// Handle change details action
if (isset($_GET['action']) && $_GET['action'] === 'change_details') {
    // Clear registration session variables to go back to registration form
    unset($_SESSION['register_phone']);
    unset($_SESSION['register_name']);
    unset($_SESSION['register_email']);
    unset($_SESSION['register_user_type']);
    redirect(APP_URL . '/frontend/register.php');
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'send_otp') {
        $phone = sanitize_input($_POST['phone'] ?? '');
        $name = sanitize_input($_POST['name'] ?? '');
        $email = sanitize_input($_POST['email'] ?? '');
        $user_type = sanitize_input($_POST['user_type'] ?? 'customer');
        
        if (empty($phone) || empty($name)) {
            $error_message = 'Sri Lankan phone number and name are required.';
        } elseif (!validate_phone($phone)) {
            $error_message = 'Please enter a valid Sri Lankan phone number in 07XXXXXXXX format.';
        } else {
            // Check if user already exists
            $user_model = new User();
            if ($user_model->exists($phone)) {
                $error_message = 'An account with this phone number already exists. Please login instead.';
            } else {
                try {
                    $otp_service = new OTPService();
                    $result = $otp_service->sendOTP($phone, $otp_service->generateOTP());
                    
                    if ($result['success']) {
                        // Store the formatted phone number that was used for OTP
                        $_SESSION['register_phone'] = $result['formatted_phone'] ?? $phone;
                        $_SESSION['register_name'] = $name;
                        $_SESSION['register_email'] = $email;
                        $_SESSION['register_user_type'] = $user_type;
                        $success_message = 'OTP sent successfully to your phone number.';
                    } else {
                        $error_message = $result['message'] ?? 'Failed to send OTP.';
                    }
                } catch (Exception $e) {
                    $error_message = 'Error: ' . $e->getMessage();
                }
            }
        }
    } elseif ($action === 'verify_otp') {
        $phone = $_SESSION['register_phone'] ?? '';
        $name = $_SESSION['register_name'] ?? '';
        $email = $_SESSION['register_email'] ?? '';
        $user_type = $_SESSION['register_user_type'] ?? 'customer';
        $otp_code = sanitize_input($_POST['otp_code'] ?? '');
        
        if (empty($phone) || empty($name) || empty($otp_code)) {
            $error_message = 'All fields are required.';
        } else {
            try {
                $otp_service = new OTPService();
                
                if ($otp_service->verifyOTP($phone, $otp_code)) {
                    // Create new user
                    $user_model = new User();
                    $user_id = $user_model->create([
                        'phone' => $phone,
                        'name' => $name,
                        'email' => $email ?: null,
                        'user_type' => $user_type,
                        'is_verified' => 1
                    ]);
                    
                    // Create session
                    $session_token = generate_token();
                    $expires_at = date('Y-m-d H:i:s', time() + SESSION_TIMEOUT);
                    
                    $database->query(
                        "INSERT INTO user_sessions (user_id, session_token, expires_at) VALUES (?, ?, ?)",
                        [$user_id, $session_token, $expires_at]
                    );
                    
                    // Set session variables
                    $_SESSION['user_id'] = $user_id;
                    $_SESSION['user_type'] = $user_type;
                    $_SESSION['name'] = $name;
                    $_SESSION['phone'] = $phone;
                    $_SESSION['session_token'] = $session_token;
                    
                    // Clean up registration session
                    unset($_SESSION['register_phone']);
                    unset($_SESSION['register_name']);
                    unset($_SESSION['register_email']);
                    unset($_SESSION['register_user_type']);
                    
                    // Redirect based on user type
                    if ($user_type === 'admin') {
                        redirect(APP_URL . '/admin/dashboard/index.php');
                    } else {
                        redirect(APP_URL . '/frontend/index.php');
                    }
                } else {
                    $error_message = 'Invalid OTP code. Please try again.';
                }
            } catch (Exception $e) {
                $error_message = 'Error: ' . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - <?php echo APP_NAME; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body class="d-flex flex-column min-vh-100">
    <!-- Include Navbar -->
    <?php include 'includes/navbar.php'; ?>

    <!-- Registration Section -->
    <div class="container-fluid bg-light min-vh-100 d-flex align-items-center">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-6 col-md-8 col-sm-10">
                    <div class="card shadow-lg">
                        <div class="card-body p-5">
                            <div class="text-center mb-4">
                                <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                                    <i class="fas fa-user-plus fa-2x"></i>
                                </div>
                                <h2 class="card-title">Create Account</h2>
                                <p class="text-muted">Join our platform to find or list rental properties</p>
                            </div>

                            <?php if ($error_message): ?>
                                <div class="alert alert-danger d-flex align-items-center mb-4">
                                    <i class="fas fa-exclamation-circle me-2"></i>
                                    <?php echo htmlspecialchars($error_message); ?>
                                </div>
                            <?php endif; ?>

                            <?php if ($success_message): ?>
                                <div class="alert alert-success d-flex align-items-center mb-4">
                                    <i class="fas fa-check-circle me-2"></i>
                                    <?php echo htmlspecialchars($success_message); ?>
                                </div>
                            <?php endif; ?>

                            <?php if (!isset($_SESSION['register_phone'])): ?>
                                <!-- Registration Form -->
                                <form method="POST" action="">
                                    <input type="hidden" name="action" value="send_otp">
                                    
                                    <div class="mb-3">
                                        <label for="user_type" class="form-label">I want to:</label>
                                        <select class="form-select" id="user_type" name="user_type" required>
                                            <option value="customer">Find rental properties</option>
                                            <option value="owner">List my properties for rent</option>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="name" class="form-label">Full Name</label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="fas fa-user"></i>
                                            </span>
                                            <input type="text" class="form-control" id="name" name="name" 
                                                   placeholder="Enter your full name" required>
                                        </div>
                                    </div>
                                                
                                    <div class="mb-3">
                                        <label for="email" class="form-label">Email Address (Optional)</label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="fas fa-envelope"></i>
                                            </span>
                                            <input type="email" class="form-control" id="email" name="email" 
                                                   placeholder="Enter your email address">
                                        </div>
                                        <div class="form-text">
                                            Email is optional but recommended for notifications
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="phone" class="form-label">Sri Lankan Phone Number</label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="fas fa-phone"></i>
                                            </span>
                                            <input type="tel" class="form-control" id="phone" name="phone" 
                                                   placeholder="0712345678" required>
                                        </div>
                                        <div class="form-text">
                                            Enter your Sri Lankan phone number in 07XXXXXXXX format
                                        </div>
                                    </div>
                                                
                                    <div class="mb-3">
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input" id="terms" required>
                                            <label class="form-check-label" for="terms">
                                                I agree to the <a href="terms.php" target="_blank">Terms of Service</a> 
                                                and <a href="privacy.php" target="_blank">Privacy Policy</a>
                                            </label>
                                        </div>
                                    </div>
                                    
                                    <div class="d-grid gap-2">
                                        <button type="submit" class="btn btn-primary btn-lg">
                                            <i class="fas fa-paper-plane me-2"></i> Send OTP & Register
                                        </button>
                                    </div>
                                </form>
                            <?php else: ?>
                                <!-- OTP Verification Form -->
                                <form method="POST" action="">
                                    <input type="hidden" name="action" value="verify_otp">
                                    
                                    <div class="mb-3">
                                        <div class="alert alert-info d-flex align-items-center">
                                            <i class="fas fa-info-circle me-2"></i>
                                            OTP sent to: <strong><?php echo htmlspecialchars($_SESSION['register_phone']); ?></strong>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="otp_code" class="form-label">Enter OTP Code</label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="fas fa-key"></i>
                                            </span>
                                            <input type="text" class="form-control text-center" id="otp_code" name="otp_code" 
                                                   placeholder="123456" maxlength="6" required>
                                        </div>
                                        <div class="form-text">
                                            Enter the 6-digit code sent to your phone
                                        </div>
                                    </div>
                                    
                                    <div class="d-grid gap-2">
                                        <button type="submit" class="btn btn-primary btn-lg">
                                            <i class="fas fa-check me-2"></i> Verify & Complete Registration
                                        </button>
                                        
                                        <button type="button" class="btn btn-outline-secondary" onclick="resendOTP()">
                                            <i class="fas fa-redo me-2"></i> Resend OTP
                                        </button>
                                        
                                        <a href="register.php?action=change_details" class="btn btn-link">
                                            <i class="fas fa-arrow-left me-2"></i> Change Details
                                        </a>
                                    </div>
                                </form>
                            <?php endif; ?>

                            <hr class="my-4">
                            
                            <div class="text-center">
                                <p class="mb-0">Already have an account?</p>
                                <a href="login.php" class="btn btn-link">Login here</a>
                            </div>
                        </div>
                    </div>
                    
                    <?php if (DEBUG_MODE && DEV_FIXED_OTP_ENABLED): ?>
                        <div class="alert alert-warning mt-3">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>⚠️ DEVELOPMENT MODE:</strong> Use fixed OTP code <code><?php echo DEV_FIXED_OTP; ?></code> for all phone numbers.
                            <br><small class="text-danger"><strong>DO NOT DEPLOY THIS TO PRODUCTION!</strong></small>
                        </div>
                    <?php elseif (DEBUG_MODE): ?>
                        <div class="alert alert-info mt-3">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Development Mode:</strong> Debug logging enabled.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Include Footer -->
    <?php include 'includes/footer.php'; ?>

    <script>
        // Auto-format Sri Lankan phone number - only 07XXXXXXXX format
        const phoneInput = document.getElementById('phone');
        if (phoneInput) {
            phoneInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            
            // Handle Sri Lankan phone number formatting - only 07XXXXXXXX format
            if (value.length > 0) {
                if (value.startsWith('0')) {
                    // Local format starting with 0, keep as is (max 10 digits)
                    if (value.length <= 10) {
                        e.target.value = value;
                    } else {
                        e.target.value = value.substring(0, 10);
                    }
                } else if (value.startsWith('7')) {
                    // Mobile number without 0 prefix, add 0
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
        
        // Validate Sri Lankan phone number format - only 07XXXXXXXX format
        function validateSriLankanPhone(phone) {
            // Remove all non-numeric characters
            phone = phone.replace(/\D/g, '');
            
            // Check for valid Sri Lankan phone number pattern - only 07XXXXXXXX format
            const pattern = /^[0]{1}[7]{1}[01245678]{1}[0-9]{7}$/;
            
            return pattern.test(phone);
        }
        
        // Add form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const phoneInput = document.getElementById('phone');
            if (phoneInput) {
                const phone = phoneInput.value;
                
                if (!validateSriLankanPhone(phone)) {
                    e.preventDefault();
                    alert('Please enter a valid Sri Lankan phone number.\n\nValid format:\n• 07XXXXXXXX (mobile)');
                    phoneInput.focus();
                    return false;
                }
            }
        });
        
        // Auto-focus OTP input
        document.getElementById('otp_code').addEventListener('input', function(e) {
            if (e.target.value.length === 6) {
                // Auto-submit when 6 digits are entered
                setTimeout(() => {
                    e.target.form.submit();
                }, 500);
            }
        });
        
        // Resend OTP function
        function resendOTP() {
            if (confirm('Resend OTP to <?php echo $_SESSION['register_phone'] ?? ''; ?>?')) {
                // Create a form to resend OTP
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="send_otp">
                    <input type="hidden" name="phone" value="<?php echo $_SESSION['register_phone'] ?? ''; ?>">
                    <input type="hidden" name="name" value="<?php echo $_SESSION['register_name'] ?? ''; ?>">
                    <input type="hidden" name="email" value="<?php echo $_SESSION['register_email'] ?? ''; ?>">
                    <input type="hidden" name="user_type" value="<?php echo $_SESSION['register_user_type'] ?? 'customer'; ?>">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        // Countdown timer for OTP expiry
        let countdown = 600; // 10 minutes
        const timerElement = document.createElement('div');
        timerElement.className = 'text-center mt-3';
        timerElement.innerHTML = '<small class="text-muted">OTP expires in: <span id="countdown">10:00</span></small>';
        
        if (document.querySelector('form[method="POST"]')) {
            document.querySelector('form[method="POST"]').appendChild(timerElement);
            
            const countdownInterval = setInterval(() => {
                const minutes = Math.floor(countdown / 60);
                const seconds = countdown % 60;
                document.getElementById('countdown').textContent = 
                    `${minutes}:${seconds.toString().padStart(2, '0')}`;
                
                if (countdown <= 0) {
                    clearInterval(countdownInterval);
                    document.getElementById('countdown').textContent = 'Expired';
                    document.getElementById('countdown').className = 'text-danger';
                }
                countdown--;
            }, 1000);
        }
    </script>
</body>
</html>
