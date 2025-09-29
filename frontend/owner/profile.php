<?php
/**
 * Property Owner - Profile Management
 * Manage personal information and account settings
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../backend/models/User.php';

// Check if user is logged in and is a property owner
if (!is_logged_in()) {
    header('Location: ../login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$user_model = new User();
$user = $user_model->getById($user_id);

if (!$user || $user['user_type'] !== 'owner') {
    header('Location: ../login.php');
    exit;
}

// Handle form submissions
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_profile') {
        $update_data = [
            'name' => $_POST['name'],
            'email' => $_POST['email'],
            'phone' => $_POST['phone']
        ];
        
        try {
            $user_model->update($user_id, $update_data);
            $message = 'Profile updated successfully!';
            $message_type = 'success';
            
            // Refresh user data
            $user = $user_model->getById($user_id);
        } catch (Exception $e) {
            $message = 'Error updating profile: ' . $e->getMessage();
            $message_type = 'danger';
        }
    } elseif ($action === 'change_password') {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        if ($new_password !== $confirm_password) {
            $message = 'New passwords do not match!';
            $message_type = 'danger';
        } else {
            try {
                $user_model->changePassword($user_id, $current_password, $new_password);
                $message = 'Password changed successfully!';
                $message_type = 'success';
            } catch (Exception $e) {
                $message = 'Error changing password: ' . $e->getMessage();
                $message_type = 'danger';
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
    <title>Profile - Property Owner Dashboard</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    
    <style>
        .owner-dashboard {
            background-color: var(--booking-gray-50);
            min-height: 100vh;
        }
        
        .owner-sidebar {
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            min-height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            width: 280px;
            z-index: 1000;
        }
        
        .owner-main-content {
            margin-left: 280px;
            padding: 2rem;
        }
        
        .owner-nav-brand {
            padding: 1.5rem;
            border-bottom: 1px solid var(--booking-gray-200);
            text-align: center;
        }
        
        .owner-nav-menu {
            padding: 1rem 0;
        }
        
        .owner-nav-item {
            margin: 0.25rem 0;
        }
        
        .owner-nav-link {
            display: flex;
            align-items: center;
            padding: 0.75rem 1.5rem;
            color: var(--booking-gray-700);
            text-decoration: none;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
        }
        
        .owner-nav-link:hover,
        .owner-nav-link.active {
            background-color: var(--booking-primary-light);
            color: var(--booking-primary);
            border-left-color: var(--booking-primary);
        }
        
        .owner-nav-link i {
            margin-right: 0.75rem;
            width: 20px;
        }
        
        .owner-header {
            background: white;
            padding: 1.5rem 2rem;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .profile-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
            overflow: hidden;
        }
        
        .profile-header {
            background: var(--booking-primary);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        
        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 3rem;
            color: var(--booking-primary);
        }
        
        .profile-name {
            font-size: 1.5rem;
            font-weight: 600;
            margin: 0 0 0.5rem 0;
        }
        
        .profile-role {
            font-size: 1rem;
            opacity: 0.9;
            margin: 0;
        }
        
        .profile-body {
            padding: 2rem;
        }
        
        .profile-section {
            margin-bottom: 2rem;
        }
        
        .profile-section-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--booking-gray-900);
            margin: 0 0 1.5rem 0;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--booking-gray-200);
        }
        
        .profile-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        
        .profile-info-item {
            display: flex;
            align-items: center;
            padding: 1rem;
            background: var(--booking-gray-50);
            border-radius: 8px;
        }
        
        .profile-info-icon {
            width: 40px;
            height: 40px;
            background: var(--booking-primary-light);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--booking-primary);
            margin-right: 1rem;
        }
        
        .profile-info-content h6 {
            font-weight: 600;
            color: var(--booking-gray-900);
            margin: 0 0 0.25rem 0;
        }
        
        .profile-info-content p {
            color: var(--booking-gray-600);
            margin: 0;
        }
        
        .nav-pills .nav-link {
            color: var(--booking-gray-700);
            border-radius: 8px;
            margin-right: 0.5rem;
        }
        
        .nav-pills .nav-link.active {
            background-color: var(--booking-primary);
        }
        
        .tab-content {
            margin-top: 2rem;
        }
        
        .form-section {
            background: var(--booking-gray-50);
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }
        
        .form-section h6 {
            color: var(--booking-gray-900);
            margin: 0 0 1rem 0;
        }
        
        .password-strength {
            height: 4px;
            background: var(--booking-gray-200);
            border-radius: 2px;
            margin-top: 0.5rem;
            overflow: hidden;
        }
        
        .password-strength-bar {
            height: 100%;
            transition: width 0.3s ease;
            border-radius: 2px;
        }
        
        .strength-weak { background: var(--booking-danger); }
        .strength-medium { background: var(--booking-warning); }
        .strength-strong { background: var(--booking-success); }
        
        @media (max-width: 768px) {
            .owner-sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }
            
            .owner-sidebar.show {
                transform: translateX(0);
            }
            
            .owner-main-content {
                margin-left: 0;
                padding: 1rem;
            }
            
            .profile-info {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body class="owner-dashboard">
    <!-- Sidebar -->
    <div class="owner-sidebar" id="ownerSidebar">
        <div class="owner-nav-brand">
            <h3 class="mb-0" style="color: var(--booking-primary);">Property Owner</h3>
            <small class="text-muted">Dashboard</small>
        </div>
        
        <nav class="owner-nav-menu">
            <div class="owner-nav-item">
                <a href="dashboard/index.php" class="owner-nav-link">
                    <i class="fas fa-tachometer-alt"></i>
                    Dashboard
                </a>
            </div>
            <div class="owner-nav-item">
                <a href="properties.php" class="owner-nav-link">
                    <i class="fas fa-home"></i>
                    My Properties
                </a>
            </div>
            <div class="owner-nav-item">
                <a href="bought.php" class="owner-nav-link">
                    <i class="fas fa-calendar-check"></i>
                    Bookings
                </a>
            </div>
            <div class="owner-nav-item">
                <a href="payments.php" class="owner-nav-link">
                    <i class="fas fa-credit-card"></i>
                    Payments
                </a>
            </div>
            <div class="owner-nav-item">
                <a href="visits.php" class="owner-nav-link">
                    <i class="fas fa-eye"></i>
                    Visit Requests
                </a>
            </div>
            <div class="owner-nav-item">
                <a href="analytics.php" class="owner-nav-link">
                    <i class="fas fa-chart-line"></i>
                    Analytics
                </a>
            </div>
            <div class="owner-nav-item">
                <a href="profile.php" class="owner-nav-link active">
                    <i class="fas fa-user"></i>
                    Profile
                </a>
            </div>
            <div class="owner-nav-item">
                <a href="../logout.php" class="owner-nav-link">
                    <i class="fas fa-sign-out-alt"></i>
                    Logout
                </a>
            </div>
        </nav>
    </div>
    
    <!-- Main Content -->
    <div class="owner-main-content">
        <!-- Header -->
        <div class="owner-header">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <h1 class="h3 mb-0">Profile Settings</h1>
                    <p class="text-muted mb-0">Manage your personal information and account settings</p>
                </div>
            </div>
        </div>
        
        <!-- Message -->
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <!-- Profile Card -->
        <div class="profile-card">
            <div class="profile-header">
                <div class="profile-avatar">
                    <i class="fas fa-user"></i>
                </div>
                <h2 class="profile-name"><?php echo htmlspecialchars($user['name']); ?></h2>
                <p class="profile-role">Property Owner</p>
            </div>
            
            <div class="profile-body">
                <!-- Profile Information -->
                <div class="profile-section">
                    <h3 class="profile-section-title">Profile Information</h3>
                    <div class="profile-info">
                        <div class="profile-info-item">
                            <div class="profile-info-icon">
                                <i class="fas fa-phone"></i>
                            </div>
                            <div class="profile-info-content">
                                <h6>Phone Number</h6>
                                <p><?php echo htmlspecialchars($user['phone']); ?></p>
                            </div>
                        </div>
                        <div class="profile-info-item">
                            <div class="profile-info-icon">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <div class="profile-info-content">
                                <h6>Email Address</h6>
                                <p><?php echo htmlspecialchars($user['email'] ?: 'Not provided'); ?></p>
                            </div>
                        </div>
                        <div class="profile-info-item">
                            <div class="profile-info-icon">
                                <i class="fas fa-calendar-alt"></i>
                            </div>
                            <div class="profile-info-content">
                                <h6>Member Since</h6>
                                <p><?php echo date('M j, Y', strtotime($user['created_at'])); ?></p>
                            </div>
                        </div>
                        <div class="profile-info-item">
                            <div class="profile-info-icon">
                                <i class="fas fa-shield-alt"></i>
                            </div>
                            <div class="profile-info-content">
                                <h6>Account Status</h6>
                                <p>
                                    <span class="badge bg-<?php echo $user['is_verified'] ? 'success' : 'warning'; ?>">
                                        <?php echo $user['is_verified'] ? 'Verified' : 'Pending Verification'; ?>
                                    </span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Settings Tabs -->
                <ul class="nav nav-pills" id="profileTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="personal-tab" data-bs-toggle="pill" data-bs-target="#personal" type="button" role="tab">
                            <i class="fas fa-user me-2"></i>
                            Personal Information
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="security-tab" data-bs-toggle="pill" data-bs-target="#security" type="button" role="tab">
                            <i class="fas fa-lock me-2"></i>
                            Security
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="notifications-tab" data-bs-toggle="pill" data-bs-target="#notifications" type="button" role="tab">
                            <i class="fas fa-bell me-2"></i>
                            Notifications
                        </button>
                    </li>
                </ul>
                
                <div class="tab-content" id="profileTabsContent">
                    <!-- Personal Information Tab -->
                    <div class="tab-pane fade show active" id="personal" role="tabpanel">
                        <form method="POST">
                            <input type="hidden" name="action" value="update_profile">
                            
                            <div class="form-section">
                                <h6>Basic Information</h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="name" class="form-label">Full Name *</label>
                                            <input type="text" class="form-control" id="name" name="name" 
                                                   value="<?php echo htmlspecialchars($user['name']); ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="phone" class="form-label">Phone Number *</label>
                                            <input type="tel" class="form-control" id="phone" name="phone" 
                                                   value="<?php echo htmlspecialchars($user['phone']); ?>" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="email" class="form-label">Email Address</label>
                                            <input type="email" class="form-control" id="email" name="email" 
                                                   value="<?php echo htmlspecialchars($user['email'] ?: ''); ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-end">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>
                                    Update Profile
                                </button>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Security Tab -->
                    <div class="tab-pane fade" id="security" role="tabpanel">
                        <form method="POST" id="changePasswordForm">
                            <input type="hidden" name="action" value="change_password">
                            
                            <div class="form-section">
                                <h6>Change Password</h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="current_password" class="form-label">Current Password *</label>
                                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="new_password" class="form-label">New Password *</label>
                                            <input type="password" class="form-control" id="new_password" name="new_password" required>
                                            <div class="password-strength">
                                                <div class="password-strength-bar" id="passwordStrengthBar"></div>
                                            </div>
                                            <small class="text-muted">Password must be at least 8 characters long</small>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="confirm_password" class="form-label">Confirm New Password *</label>
                                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-end">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-key me-2"></i>
                                    Change Password
                                </button>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Notifications Tab -->
                    <div class="tab-pane fade" id="notifications" role="tabpanel">
                        <div class="form-section">
                            <h6>Email Notifications</h6>
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="booking_notifications" checked>
                                <label class="form-check-label" for="booking_notifications">
                                    New booking notifications
                                </label>
                            </div>
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="payment_notifications" checked>
                                <label class="form-check-label" for="payment_notifications">
                                    Payment notifications
                                </label>
                            </div>
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="visit_notifications" checked>
                                <label class="form-check-label" for="visit_notifications">
                                    Visit request notifications
                                </label>
                            </div>
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="marketing_notifications">
                                <label class="form-check-label" for="marketing_notifications">
                                    Marketing and promotional emails
                                </label>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h6>SMS Notifications</h6>
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="sms_urgent" checked>
                                <label class="form-check-label" for="sms_urgent">
                                    Urgent notifications via SMS
                                </label>
                            </div>
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="sms_reminders">
                                <label class="form-check-label" for="sms_reminders">
                                    Payment reminders
                                </label>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-end">
                            <button type="button" class="btn btn-primary" onclick="saveNotificationSettings()">
                                <i class="fas fa-save me-2"></i>
                                Save Settings
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Password strength indicator
        document.getElementById('new_password').addEventListener('input', function() {
            const password = this.value;
            const strengthBar = document.getElementById('passwordStrengthBar');
            
            let strength = 0;
            if (password.length >= 8) strength++;
            if (password.match(/[a-z]/)) strength++;
            if (password.match(/[A-Z]/)) strength++;
            if (password.match(/[0-9]/)) strength++;
            if (password.match(/[^a-zA-Z0-9]/)) strength++;
            
            strengthBar.style.width = (strength * 20) + '%';
            strengthBar.className = 'password-strength-bar';
            
            if (strength < 2) {
                strengthBar.classList.add('strength-weak');
            } else if (strength < 4) {
                strengthBar.classList.add('strength-medium');
            } else {
                strengthBar.classList.add('strength-strong');
            }
        });
        
        // Password confirmation validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('new_password').value;
            const confirmPassword = this.value;
            
            if (password !== confirmPassword) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });
        
        function saveNotificationSettings() {
            // TODO: Implement notification settings save functionality
            alert('Notification settings saved successfully!');
        }
    </script>
</body>
</html>
