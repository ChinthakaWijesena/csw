<?php

/**
 * Login Page with SMS OTP Authentication
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../api/otp/OTPService.php';

// Redirect if already logged in
if (is_logged_in()) {
    redirect(APP_URL . '/frontend/dashboard.php');
}

$error_message = '';
$success_message = '';

// Handle change phone number action
if (isset($_GET['action']) && $_GET['action'] === 'change_phone') {
    // Clear login session variables to go back to phone input
    unset($_SESSION['login_phone']);
    unset($_SESSION['login_user_type']);
    redirect(APP_URL . '/frontend/login.php');
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'send_otp') {
        $phone = sanitize_input($_POST['phone'] ?? '');
        $user_type = 'customer'; // Only allow customers to login

        if (empty($phone)) {
            $error_message = 'Sri Lankan phone number is required.';
        } elseif (!validate_phone($phone)) {
            $error_message = 'Please enter a valid Sri Lankan phone number in 07XXXXXXXX format.';
        } else {
            try {
                $otp_service = new OTPService();
                $result = $otp_service->sendOTP($phone, $otp_service->generateOTP());

                if ($result['success']) {
                    $_SESSION['login_phone'] = $result['formatted_phone'] ?? $phone;
                    $_SESSION['login_user_type'] = $user_type;
                    $success_message = 'OTP Sent Successfully';
                } else {
                    $error_message = $result['message'] ?? 'Failed to send OTP.';
                }
            } catch (Exception $e) {
                $error_message = 'Error: ' . $e->getMessage();
            }
        }
    } elseif ($action === 'verify_otp') {
        $phone = $_SESSION['login_phone'] ?? '';
        $otp_code = sanitize_input($_POST['otp_code'] ?? '');
        $user_type = $_SESSION['login_user_type'] ?? 'customer';

        if (empty($phone) || empty($otp_code)) {
            $error_message = 'Phone number and OTP code are required.';
        } else {
            try {
                $otp_service = new OTPService();

                if ($otp_service->verifyOTP($phone, $otp_code)) {
                    // Check if user exists
                    $user_model = new User();
                    $user = $user_model->getByPhone($phone);

                    if (!$user) {
                        // Create new user
                        $user_id = $user_model->create([
                            'phone' => $phone,
                            'name' => 'User ' . substr($phone, -4), // Temporary name
                            'user_type' => $user_type,
                            'is_verified' => 1
                        ]);
                        $user = $user_model->getById($user_id);
                    }

                    // Create session
                    $session_token = generate_token();
                    $expires_at = date('Y-m-d H:i:s', time() + SESSION_TIMEOUT);

                    $database->query(
                        "INSERT INTO user_sessions (user_id, session_token, expires_at) VALUES (?, ?, ?)",
                        [$user['id'], $session_token, $expires_at]
                    );

                    // Set session variables
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_type'] = $user['user_type'];
                    $_SESSION['name'] = $user['name'];
                    $_SESSION['phone'] = $user['phone'];
                    $_SESSION['session_token'] = $session_token;

                    // Clean up login session
                    unset($_SESSION['login_phone']);
                    unset($_SESSION['login_user_type']);

                    // Redirect based on user type
                    if ($user['user_type'] === 'admin') {
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
    <title>Customer Login - <?php echo APP_NAME; ?></title>

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

    <!-- Login Section -->
    <div class="container-fluid bg-light vh-100 d-flex align-items-center justify-content-center overflow-auto">
        <div class="row justify-content-center w-100">
            <div class="col-md-6 col-lg-5 col-xl-4">
                    <div class="card shadow-lg border-0">
                        <div class="card-body p-5">
                            <!-- Login Header -->
                            <div class="mb-4">
                                <div class="d-flex align-items-center justify-content-center">
                                    <i class="fas fa-sign-in-alt fa-3x text-primary me-3"></i>
                                    <h2 class="fw-bold text-dark mb-0">Login</h2>
                                </div>
                            </div>

                            <!-- Alert Message -->
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
                            } elseif (isset($_SESSION['login_phone'])) {
                                $alert_message = $_SESSION['login_phone'];
                                $alert_type = 'alert-info';
                                $alert_icon = 'fa-info-circle';
                            }
                            ?>
                            
                            <?php if ($alert_message): ?>
                                <div class="alert <?php echo $alert_type; ?> d-flex align-items-center" role="alert">
                                    <i class="fas <?php echo $alert_icon; ?> me-2"></i>
                                    <?php if (isset($_SESSION['login_phone']) && !$error_message && !$success_message): ?>
                                        <strong class="d-block text-center w-100"><?php echo htmlspecialchars($alert_message); ?></strong>
                                    <?php else: ?>
                                        <?php echo htmlspecialchars($alert_message); ?>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <?php if (!isset($_SESSION['login_phone'])): ?>
                                <!-- Phone Number Form -->
                                <form method="POST" action="">
                                    <input type="hidden" name="action" value="send_otp">


                                    <div class="mb-3">
                                        <label for="phone" class="form-label fw-semibold">Enter Your Mobile Number</label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="fas fa-phone"></i>
                                            </span>
                                            <input type="tel" class="form-control" id="phone" name="phone"
                                                placeholder="0712345678" required>
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
                                <form method="POST" action="">
                                    <input type="hidden" name="action" value="verify_otp">


                                    <div class="mb-3">

                                        <!-- OTP Expiry Timer -->
                                        <div class=" mt-2">
                                            <small class="text-muted">
                                                <i class="fas fa-clock me-1"></i>
                                                OTP expires in: <span id="otp-countdown" class="fw-bold text-primary">10:00</span>
                                            </small>
                                        </div>
                                    </div>

                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-key"></i>
                                        </span>
                                        <input type="text" class="form-control text-center" id="otp_code" name="otp_code"
                                            placeholder="Enter OTP Code:- 123456" maxlength="6" required>
                                    </div>



                                    <div class="d-grid gap-2 mt-2">
                                      

                                        <button type="button" class="btn btn-secondary" onclick="resendOTP()">
                                            <i class="fas fa-redo me-2"></i> Resend OTP
                                        </button>

                                        <button type="button" class="btn btn-success" onclick="window.location.href='login.php?action=change_phone'">
                                            <i class="fas fa-arrow-left me-2"></i> Change Phone Number
                                        </button>
                                    </div>
                                </form>
                            <?php endif; ?>


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
            const phone = phoneInput.value;

            if (!validateSriLankanPhone(phone)) {
                e.preventDefault();
                alert('Please enter valid number.\n\nValid format:\n• 07XXXXXXXX (mobile)');
                phoneInput.focus();
                return false;
            }
        });

        // Auto-focus OTP input
        const otpInput = document.getElementById('otp_code');
        if (otpInput) {
            otpInput.addEventListener('input', function(e) {
                if (e.target.value.length === 6) {
                    // Auto-submit when 6 digits are entered
                    setTimeout(() => {
                        e.target.form.submit();
                    }, 500);
                }
            });
        }

        // Resend OTP function
        function resendOTP() {
            if (confirm('Resend OTP to <?php echo $_SESSION['login_phone'] ?? ''; ?>?')) {
                // Create a form to resend OTP
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="send_otp">
                    <input type="hidden" name="phone" value="<?php echo $_SESSION['login_phone'] ?? ''; ?>">
                    <input type="hidden" name="user_type" value="<?php echo $_SESSION['login_user_type'] ?? 'customer'; ?>">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Countdown timer for OTP expiry
        let countdown = 600; // 10 minutes (600 seconds)

        // Start countdown timer when OTP form is visible
        const otpCountdownElement = document.getElementById('otp-countdown');
        if (otpCountdownElement) {
            const countdownInterval = setInterval(() => {
                const minutes = Math.floor(countdown / 60);
                const seconds = countdown % 60;

                otpCountdownElement.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;

                if (countdown <= 0) {
                    clearInterval(countdownInterval);
                    otpCountdownElement.textContent = 'Expired';
                    otpCountdownElement.className = 'fw-bold text-danger';

                    // Disable the OTP input when expired
                    const otpInput = document.getElementById('otp_code');
                    if (otpInput) {
                        otpInput.disabled = true;
                        otpInput.placeholder = 'OTP has expired - Please resend';
                    }
                }
                countdown--;
            }, 1000);
        }
    </script>
</body>

</html>