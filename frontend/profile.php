<?php
/**
 * User Profile Page - Booking.com Design System
 */

require_once __DIR__ . '/../config/config.php';

// Require login
require_login();

$user_model = new User();

// Get user data
$user = $user_model->getById($_SESSION['user_id']);

// Check if user exists
if (!$user) {
    // User not found, redirect to login
    session_destroy();
    redirect(APP_URL . '/frontend/login.php');
}

$error_message = '';
$success_message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_profile') {
        $name = sanitize_input($_POST['name'] ?? '');
        $email = sanitize_input($_POST['email'] ?? '');
        $phone = sanitize_input($_POST['phone'] ?? '');
        
        if (empty($name)) {
            $error_message = 'Name is required.';
        } elseif (empty($phone)) {
            $error_message = 'Sri Lankan phone number is required.';
        } elseif (!validate_phone($phone)) {
            $error_message = 'Please enter a valid Sri Lankan phone number in 07XXXXXXXX format.';
        } else {
            try {
                // Check if email is already taken by another user
                if (!empty($email)) {
                    $existing_user = $database->fetch(
                        "SELECT id FROM users WHERE email = ? AND id != ?",
                        [$email, $_SESSION['user_id']]
                    );
                    
                    if ($existing_user) {
                        $error_message = 'Email address is already taken by another user.';
                    }
                }
                
                if (empty($error_message)) {
                    // Update user profile
                    $result = $database->query(
                        "UPDATE users SET name = ?, email = ?, phone = ?, updated_at = NOW() WHERE id = ?",
                        [$name, $email, $phone, $_SESSION['user_id']]
                    );
                    
                    // Check if update was successful
                    $rows_affected = $result->rowCount();
                    
                    if ($rows_affected > 0) {
                        // Update session variables
                        $_SESSION['name'] = $name;
                        $_SESSION['phone'] = $phone;
                        
                        // Refresh user data
                        $user = $user_model->getById($_SESSION['user_id']);
                        
                        $success_message = 'Profile updated successfully!';
                    } else {
                        $error_message = 'Failed to update profile. Please try again.';
                    }
                }
            } catch (Exception $e) {
                $error_message = 'Error updating profile: ' . $e->getMessage();
            }
        }
    } elseif ($action === 'change_password') {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if (empty($current_password)) {
            $error_message = 'Current password is required.';
        } elseif (empty($new_password)) {
            $error_message = 'New password is required.';
        } elseif (strlen($new_password) < 6) {
            $error_message = 'New password must be at least 6 characters long.';
        } elseif ($new_password !== $confirm_password) {
            $error_message = 'New password and confirm password do not match.';
        } else {
            try {
                // Verify current password
                $user_data = $database->fetch(
                    "SELECT password FROM users WHERE id = ?",
                    [$_SESSION['user_id']]
                );
                
                if (!$user_data || !password_verify($current_password, $user_data['password'])) {
                    $error_message = 'Current password is incorrect.';
                } else {
                    // Update password
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $database->query(
                        "UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?",
                        [$hashed_password, $_SESSION['user_id']]
                    );
                    
                    $success_message = 'Password changed successfully!';
                }
            } catch (Exception $e) {
                $error_message = 'Error changing password: ' . $e->getMessage();
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
    <title>Profile - <?php echo APP_NAME; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <!-- Bootstrap Header -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light border-bottom">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="index.php">
                <i class="fas fa-home me-2 text-primary"></i>
                <?php echo APP_NAME; ?>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="search.php">Search Properties</a>
                    </li>
                    <?php if ($user['user_type'] === 'owner'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="my-properties.php">My Properties</a>
                        </li>
                    <?php endif; ?>
                </ul>
                
                <div class="navbar-nav">
                    <div class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user me-2"></i>
                            <?php echo htmlspecialchars($user['name'] ?? ''); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="dashboard.php">
                                <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                            </a></li>
                            <li><a class="dropdown-item active" href="profile.php">
                                <i class="fas fa-user me-2"></i> Profile
                            </a></li>
                            <?php if ($user['user_type'] === 'owner'): ?>
                                <li><a class="dropdown-item" href="my-properties.php">
                                    <i class="fas fa-building me-2"></i> My Properties
                                </a></li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i> Logout
                            </a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Profile Content -->
    <main class="py-4">
        <div class="container">
            <!-- Profile Header -->
            <section class="mb-4">
                <div class="card">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                                    <i class="fas fa-user fa-2x"></i>
                                </div>
                            </div>
                            <div class="col">
                                <h1 class="h3 mb-2"><?php echo htmlspecialchars($user['name'] ?? ''); ?></h1>
                                <p class="mb-2">
                                    <span class="badge bg-<?php echo $user['user_type'] === 'owner' ? 'success' : 'info'; ?>">
                                        <?php echo ucfirst($user['user_type']); ?>
                                    </span>
                                </p>
                                <p class="text-muted mb-1">
                                    <i class="fas fa-phone me-2"></i> <?php echo htmlspecialchars($user['phone'] ?? ''); ?>
                                </p>
                                <?php if (!empty($user['email'])): ?>
                                    <p class="text-muted mb-0">
                                        <i class="fas fa-envelope me-2"></i> <?php echo htmlspecialchars($user['email'] ?? ''); ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Profile Forms -->
            <section class="mb-4">
                <div class="row">
                    <!-- Update Profile Form -->
                    <div class="col-lg-6 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="h5 mb-0">
                                    <i class="fas fa-user-edit me-2"></i> Update Profile
                                </h3>
                            </div>
                            <div class="card-body">
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

                                <form method="POST" action="">
                                    <input type="hidden" name="action" value="update_profile">
                                    
                                    <div class="mb-3">
                                        <label for="name" class="form-label">Full Name</label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="fas fa-user"></i>
                                            </span>
                                            <input type="text" class="form-control" id="name" name="name" 
                                                   value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>" required>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="email" class="form-label">Email Address</label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="fas fa-envelope"></i>
                                            </span>
                                            <input type="email" class="form-control" id="email" name="email" 
                                                   value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" 
                                                   placeholder="Enter your email address">
                                        </div>
                                        <div class="form-text">
                                            Email is optional but recommended for notifications
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="phone" class="form-label">Phone Number</label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="fas fa-phone"></i>
                                            </span>
                                            <input type="tel" class="form-control" id="phone" name="phone" 
                                                   value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" 
                                                   placeholder="0712345678" required>
                                        </div>
                                        <div class="form-text">
                                            Sri Lankan phone number in 07XXXXXXXX format
                                        </div>
                                    </div>

                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save me-2"></i> Update Profile
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Change Password Form -->
                    <div class="col-lg-6 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="h5 mb-0">
                                    <i class="fas fa-lock me-2"></i> Change Password
                                </h3>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="">
                                    <input type="hidden" name="action" value="change_password">
                                    
                                    <div class="mb-3">
                                        <label for="current_password" class="form-label">Current Password</label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="fas fa-lock"></i>
                                            </span>
                                            <input type="password" class="form-control" id="current_password" 
                                                   name="current_password" required>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="new_password" class="form-label">New Password</label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="fas fa-key"></i>
                                            </span>
                                            <input type="password" class="form-control" id="new_password" 
                                                   name="new_password" required>
                                        </div>
                                        <div class="form-text">
                                            Password must be at least 6 characters long
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="fas fa-key"></i>
                                            </span>
                                            <input type="password" class="form-control" id="confirm_password" 
                                                   name="confirm_password" required>
                                        </div>
                                    </div>

                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save me-2"></i> Change Password
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Account Information -->
            <section class="mb-4">
                <div class="card">
                    <div class="card-header">
                        <h3 class="h5 mb-0">
                            <i class="fas fa-info-circle me-2"></i> Account Information
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="fw-bold">User ID:</span>
                                    <span class="text-muted"><?php echo $user['id']; ?></span>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="fw-bold">Account Type:</span>
                                    <span class="badge bg-<?php echo $user['user_type'] === 'owner' ? 'success' : 'info'; ?>">
                                        <?php echo ucfirst($user['user_type']); ?>
                                    </span>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="fw-bold">Member Since:</span>
                                    <span class="text-muted"><?php echo date('F j, Y', strtotime($user['created_at'])); ?></span>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="fw-bold">Last Updated:</span>
                                    <span class="text-muted"><?php echo date('F j, Y g:i A', strtotime($user['updated_at'])); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </main>

    <!-- Include Footer -->
    <?php include 'includes/footer.php'; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
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
        
        // Password confirmation validation
        document.getElementById('confirm_password').addEventListener('input', function(e) {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = e.target.value;
            
            if (confirmPassword && newPassword !== confirmPassword) {
                e.target.setCustomValidity('Passwords do not match');
            } else {
                e.target.setCustomValidity('');
            }
        });
        
        // New password validation
        document.getElementById('new_password').addEventListener('input', function(e) {
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (confirmPassword && e.target.value !== confirmPassword) {
                document.getElementById('confirm_password').setCustomValidity('Passwords do not match');
            } else {
                document.getElementById('confirm_password').setCustomValidity('');
            }
        });
    </script>
</body>
</html>
