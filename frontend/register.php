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
        $first_name = sanitize_input($_POST['first_name'] ?? '');
        $last_name = sanitize_input($_POST['last_name'] ?? '');
        $name = trim(($first_name . ' ' . $last_name));
        $email = sanitize_input($_POST['email'] ?? '');
        // Force all registrations to customer user type
        $user_type = 'customer';
        
        if (empty($phone) || empty($first_name) || empty($last_name)) {
            $error_message = 'Sri Lankan phone number, first name and last name are required.';
        } elseif (!validate_phone($phone)) {
            $error_message = 'Please enter a valid Sri Lankan phone number in 07XXXXXXXX format.';
        } else {
            // Check if user already exists
            $user_model = new User();
            if ($user_model->exists($phone)) {
                $error_message = 'Account Exists.';
            } else {
                try {
                    $otp_service = new OTPService();
                    $result = $otp_service->sendOTP($phone, $otp_service->generateOTP());
                    
                    if ($result['success']) {
                        // Store the formatted phone number that was used for OTP
                        $_SESSION['register_phone'] = $result['formatted_phone'] ?? $phone;
                        $_SESSION['register_name'] = $name;
                        $_SESSION['register_first_name'] = $first_name;
                        $_SESSION['register_last_name'] = $last_name;
                        $_SESSION['register_email'] = $email;
                        $_SESSION['register_user_type'] = 'customer';
                        $success_message = 'OTP Sent Successfully.';
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
        // Force all registrations to customer user type
        $user_type = 'customer';
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
                    $error_message = 'Invalid OTP';
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
<body class="d-flex flex-column min-vh-100 overflow-hidden">
    <!-- Include Navbar -->
    <?php include 'includes/navbar.php'; ?>

    <!-- Registration Section -->
    <div class="container-fluid bg-light min-vh-100 d-flex py-4">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-6 col-md-8 col-sm-10 my-3">
                    <div class="card shadow-lg">
                        <div class="card-body p-5">
                            <div class="text-center mb-3">
                                <div class="d-flex align-items-center justify-content-center">
                                    <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center me-2" style="width: 44px; height: 44px;">
                                        <i class="fas fa-user-plus fa-lg"></i>
                                    </div>
                                    <h4 class="card-title mb-0">Create Account</h4>
                                </div>
                            </div>

                            <?php 
                            $alert_message = '';
                            $alert_type = '';
                            $alert_icon = '';
                            if ($error_message) {
                                $alert_message = $error_message;
                                $alert_type = 'alert-danger';
                                $alert_icon = 'fa-exclamation-circle';
                            } elseif ($success_message) {
                                $alert_message = $success_message;
                                $alert_type = 'alert-success';
                                $alert_icon = 'fa-check-circle';
                            }
                            ?>
                            <?php if ($alert_message): ?>
                                <div class="alert <?php echo $alert_type; ?> d-flex align-items-center mb-4" role="alert">
                                    <i class="fas <?php echo $alert_icon; ?> me-2"></i>
                                    <?php echo htmlspecialchars($alert_message); ?>
                                </div>
                            <?php endif; ?>

                            <?php if (!isset($_SESSION['register_phone'])): ?>
                                <!-- Registration Form -->
                                <form method="POST" action="">
                                    <input type="hidden" name="action" value="send_otp">
                                    
                                    <!-- User type selection removed: only customers can register here -->
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Name</label>
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <div class="input-group">
                                                    <span class="input-group-text">
                                                        <i class="fas fa-user"></i>
                                                    </span>
                                                    <input type="text" class="form-control" id="first_name" name="first_name" 
                                                           placeholder="First name" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="input-group">
                                                    <span class="input-group-text">
                                                        <i class="fas fa-user"></i>
                                                    </span>
                                                    <input type="text" class="form-control" id="last_name" name="last_name" 
                                                           placeholder="Last name" required>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                                
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label for="email" class="form-label">Email Address (Optional)</label>
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="fas fa-envelope"></i>
                                                </span>
                                                <input type="email" class="form-control" id="email" name="email" 
                                                       placeholder="Enter your email address">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="phone" class="form-label">Mobile Number</label>
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="fas fa-phone"></i>
                                                </span>
                                                <input type="tel" class="form-control" id="phone" name="phone" 
                                                       placeholder="Enter OTP Code:- 0712345678" required>
                                            </div>
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
                                            <i class="fas fa-paper-plane me-2"></i> Send OTP
                                        </button>
                                    </div>
                                </form>
                            <?php else: ?>

                                <!-- OTP Verification Form -->
                                <form method="POST" action="">
                                    <input type="hidden" name="action" value="verify_otp">
                                    <div class="mb-2">
                                        <small class="text-muted">OTP expires in: <span id="otp-countdown" class="fw-bold text-primary">10:00</span></small>
                                    </div>
                                    <div class="mb-3">
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="fas fa-key"></i>
                                            </span>
                                            <input type="text" class="form-control text-center" id="otp_code" name="otp_code" 
                                                   placeholder="Enter OTP Code:- 123456" maxlength="6" required>
                                        </div>
                                   
                                    </div>
                                    
                                    <div class="d-grid gap-2">
                                        
                                        
                                        <button type="button" class="btn btn-outline-secondary" onclick="resendOTP()">
                                            <i class="fas fa-redo me-2"></i> Resend OTP
                                        </button>
                                        
                                        <button type="button" class="btn btn-success" onclick="window.location.href='register.php?action=change_details'">
                                            <i class="fas fa-arrow-left me-2"></i> Change Details
                                        </button>
                                    </div>
                                </form>
                            <?php endif; ?>

                            <hr class="my-4">

                        </div>
                    </div>
                    
                   
                </div>
            </div>
        </div>
    </div>


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
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="send_otp">
                    <input type="hidden" name="phone" value="<?php echo $_SESSION['register_phone'] ?? ''; ?>">
                    <input type="hidden" name="first_name" value="<?php echo $_SESSION['register_first_name'] ?? ''; ?>">
                    <input type="hidden" name="last_name" value="<?php echo $_SESSION['register_last_name'] ?? ''; ?>">
                    <input type="hidden" name="email" value="<?php echo $_SESSION['register_email'] ?? ''; ?>">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        // Countdown timer for OTP expiry (single label above input)
        let countdown = 600; // 10 minutes
        const otpCountdownEl = document.getElementById('otp-countdown');
        if (otpCountdownEl) {
            const countdownInterval = setInterval(() => {
                const minutes = Math.floor(countdown / 60);
                const seconds = countdown % 60;
                otpCountdownEl.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;
                if (countdown <= 0) {
                    clearInterval(countdownInterval);
                    otpCountdownEl.textContent = 'Expired';
                    otpCountdownEl.className = 'fw-bold text-danger';
                }
                countdown--;
            }, 1000);
        }
    </script>
</body>
</html>
