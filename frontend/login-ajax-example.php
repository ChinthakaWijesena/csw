<?php
/**
 * AJAX Login Page Example
 * Shows how to convert traditional login to AJAX
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../backend/models/User.php';
require_once __DIR__ . '/../api/otp/OTPService.php';

// Handle traditional form submission (fallback)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'send_otp') {
        $phone = sanitize_input($_POST['phone']);
        $user_type = sanitize_input($_POST['user_type']);
        
        // Validate phone number
        if (!validate_phone($phone)) {
            $error_message = 'Please enter a valid phone number in 07XXXXXXXX format';
        } else {
            // Check if user exists
            $user_model = new User();
            $user = $user_model->getByPhone($phone);
            
            if (!$user) {
                $error_message = 'User not found. Please register first.';
            } else {
                // Send OTP
                $otp_service = new OTPService();
                $otp_code = $otp_service->generateOTP();
                $result = $otp_service->sendOTP($phone, $otp_code);
                
                if ($result['success']) {
                    $_SESSION['login_phone'] = $result['formatted_phone'] ?? $phone;
                    $_SESSION['login_user_type'] = $user_type;
                    $success_message = 'OTP sent successfully to your phone number.';
                } else {
                    $error_message = $result['message'] ?? 'Failed to send OTP.';
                }
            }
        }
    } elseif ($action === 'verify_otp') {
        $phone = $_SESSION['login_phone'] ?? '';
        $otp_code = sanitize_input($_POST['otp_code']);
        
        if (empty($phone)) {
            $error_message = 'Please request OTP first.';
        } else {
            // Verify OTP
            $otp_service = new OTPService();
            $verification_result = $otp_service->verifyOTP($phone, $otp_code);
            
            if ($verification_result) {
                // Get user information
                $user_model = new User();
                $user = $user_model->getByPhone($phone);
                
                if ($user) {
                    // Create session
                    $session_token = generate_token();
                    $expires_at = date('Y-m-d H:i:s', time() + SESSION_TIMEOUT);
                    
                    // Store session in database
                    $database->query(
                        "INSERT INTO user_sessions (user_id, session_token, expires_at, ip_address) VALUES (?, ?, ?, ?)",
                        [$user['id'], $session_token, $expires_at, get_client_ip()]
                    );
                    
                    // Set session variables
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_type'] = $user['user_type'];
                    $_SESSION['session_token'] = $session_token;
                    $_SESSION['user_name'] = $user['name'];
                    $_SESSION['user_phone'] = $user['phone'];
                    $_SESSION['user_email'] = $user['email'];
                    
                    // Redirect based on user type
                    $redirect_url = '/frontend/index.php';
                    switch ($user['user_type']) {
                        case 'admin':
                            $redirect_url = '/frontend/admin/dashboard/index.php';
                            break;
                        case 'owner':
                            $redirect_url = '/frontend/owner/dashboard/index.php';
                            break;
                        case 'customer':
                            $redirect_url = '/frontend/dashboard.php';
                            break;
                    }
                    
                    header('Location: ' . $redirect_url);
                    exit;
                } else {
                    $error_message = 'User not found.';
                }
            } else {
                $error_message = 'Invalid OTP code.';
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
    <title>Login - Renting Place Finder</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    
    <!-- CSRF Token -->
    <meta name="csrf-token" content="<?php echo generate_csrf_token(); ?>">
</head>
<body>
    <div class="booking-login-container">
        <div class="booking-login-card">
            <div class="booking-login-header">
                <h2>Welcome Back</h2>
                <p>Sign in to your account</p>
            </div>

            <!-- Success/Error Messages -->
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $success_message; ?>
                </div>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <?php if (!isset($_SESSION['login_phone'])): ?>
                <!-- Phone Number Form -->
                <form id="loginForm" class="booking-login-form">
                    <input type="hidden" name="action" value="send_otp">
                    
                    <div class="booking-form-group">
                        <label for="user_type" class="booking-form-label">I am a:</label>
                        <select class="booking-form-input" id="user_type" name="user_type" required>
                            <option value="">Select User Type</option>
                            <option value="customer">Customer</option>
                            <option value="owner">Property Owner</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    
                    <div class="booking-form-group">
                        <label for="phone" class="booking-form-label">
                            <i class="fas fa-phone"></i> Phone Number
                        </label>
                        <input type="tel" class="booking-form-input" id="phone" name="phone" 
                               placeholder="0712345678" required>
                    </div>
                    
                    <div class="booking-form-group">
                        <button type="submit" class="booking-btn booking-btn-primary">
                            <i class="fas fa-paper-plane"></i>
                            Send OTP
                        </button>
                    </div>
                </form>
            <?php else: ?>
                <!-- OTP Verification Form -->
                <form id="otpForm" class="booking-login-form">
                    <input type="hidden" name="action" value="verify_otp">
                    <input type="hidden" name="phone" value="<?php echo $_SESSION['login_phone']; ?>">
                    
                    <div class="booking-form-group">
                        <div class="booking-alert booking-alert-info">
                            <i class="fas fa-info-circle"></i>
                            OTP sent to <?php echo $_SESSION['login_phone']; ?>
                        </div>
                    </div>
                    
                    <div class="booking-form-group">
                        <label for="otp_code" class="booking-form-label">
                            <i class="fas fa-key"></i> Enter OTP Code
                        </label>
                        <input type="text" class="booking-form-input" id="otp_code" name="otp_code" 
                               placeholder="123456" maxlength="6" required>
                    </div>
                    
                    <div class="booking-form-group">
                        <button type="submit" class="booking-btn booking-btn-primary">
                            <i class="fas fa-sign-in-alt"></i>
                            Verify & Login
                        </button>
                    </div>
                    
                    <div class="booking-form-group">
                        <button type="button" id="resendOTP" class="booking-btn booking-btn-secondary">
                            <i class="fas fa-redo"></i>
                            Resend OTP
                        </button>
                    </div>
                </form>
            <?php endif; ?>

            <!-- Development Mode Notice -->
            <?php if (defined('DEBUG_MODE') && DEBUG_MODE): ?>
                <div class="booking-alert booking-alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>Development Mode:</strong> OTP validation is bypassed. Use 123456 or 111111.
                </div>
            <?php endif; ?>

            <div class="booking-login-footer">
                <p>Don't have an account? <a href="register.php">Register here</a></p>
            </div>
        </div>
    </div>

    <!-- Loading Indicator -->
    <div class="global-loading" style="display: none;">
        <div class="spinner"></div>
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- AJAX Scripts -->
    <script src="js/ajax-utils.js"></script>
    <script src="js/auth-ajax.js"></script>

    <style>
        /* Loading Indicator Styles */
        .global-loading {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }

        .spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #007bff;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Form Loading State */
        .form-loading {
            opacity: 0.6;
            pointer-events: none;
        }

        /* Alert Animations */
        .alert {
            animation: slideDown 0.3s ease-out;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>

    <script>
        // Custom AJAX handling for this page
        $(document).ready(function() {
            // Handle form submission with custom logic
            $('#loginForm').on('submit', function(e) {
                e.preventDefault();
                
                const form = $(this);
                const formData = new FormData(this);
                
                // Show loading state
                form.addClass('form-loading');
                $('.global-loading').show();
                
                // Make AJAX request
                AjaxUtils.request('../api/otp/send.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    form.removeClass('form-loading');
                    $('.global-loading').hide();
                    
                    if (response.success) {
                        AjaxUtils.showSuccess(response.message);
                        // Reload page to show OTP form
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    } else {
                        AjaxUtils.showError(response.message);
                    }
                })
                .catch(error => {
                    form.removeClass('form-loading');
                    $('.global-loading').hide();
                    AjaxUtils.showError('Login failed. Please try again.');
                });
            });

            // Handle OTP verification
            $('#otpForm').on('submit', function(e) {
                e.preventDefault();
                
                const form = $(this);
                const formData = new FormData(this);
                
                // Show loading state
                form.addClass('form-loading');
                $('.global-loading').show();
                
                // Make AJAX request
                AjaxUtils.request('../api/otp/verify.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    form.removeClass('form-loading');
                    $('.global-loading').hide();
                    
                    if (response.success) {
                        AjaxUtils.showSuccess(response.message);
                        // Redirect to appropriate dashboard
                        setTimeout(() => {
                            window.location.href = response.redirect;
                        }, 1000);
                    } else {
                        AjaxUtils.showError(response.message);
                    }
                })
                .catch(error => {
                    form.removeClass('form-loading');
                    $('.global-loading').hide();
                    AjaxUtils.showError('OTP verification failed. Please try again.');
                });
            });

            // Handle resend OTP
            $('#resendOTP').on('click', function(e) {
                e.preventDefault();
                
                const button = $(this);
                const originalText = button.html();
                
                button.html('<i class="fas fa-spinner fa-spin"></i> Sending...');
                button.prop('disabled', true);
                
                AjaxUtils.request('../api/otp/resend.php', {
                    method: 'POST'
                })
                .then(response => {
                    button.html(originalText);
                    button.prop('disabled', false);
                    
                    if (response.success) {
                        AjaxUtils.showSuccess(response.message);
                    } else {
                        AjaxUtils.showError(response.message);
                    }
                })
                .catch(error => {
                    button.html(originalText);
                    button.prop('disabled', false);
                    AjaxUtils.showError('Failed to resend OTP. Please try again.');
                });
            });

            // Auto-focus OTP input
            $('#otp_code').focus();
            
            // Format phone number input - only 07XXXXXXXX format
            $('#phone').on('input', function() {
                let value = $(this).val().replace(/\D/g, '');
                
                // Handle 07XXXXXXXX format
                if (value.length > 0) {
                    if (value.startsWith('0')) {
                        // Format: 07XXXXXXXX (max 10 digits)
                        if (value.length <= 10) {
                            $(this).val(value);
                        } else {
                            $(this).val(value.substring(0, 10));
                        }
                    } else if (value.startsWith('7')) {
                        // Auto-add 0 prefix
                        $(this).val('0' + value);
                    } else if (value.startsWith('94')) {
                        // Convert 94 to 07 format
                        $(this).val('0' + value.substring(2));
                    } else if (value.length > 0) {
                        // Clear invalid input
                        $(this).val('');
                    }
                }
            });
        });
    </script>
</body>
</html>
