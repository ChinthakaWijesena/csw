<?php

/**
 * Reusable Navbar Component
 * Include this file in all pages that need navigation
 */

// Ensure session is started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Get current page for active state
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!-- Bootstrap Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top">
    <div class="container">
        <!-- Brand/Logo -->
        <a class="navbar-brand d-flex align-items-center" href="<?php echo APP_URL; ?>/frontend/index.php">
            <i class="fas fa-home me-2"></i>
            <?php echo APP_NAME; ?>
        </a>

        <!-- Mobile Toggle Button -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Navigation Menu -->
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                

                <!-- Search Properties -->
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page === 'search.php') ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/frontend/search.php">
                        <i class="fas fa-search me-1"></i>Search Properties
                    </a>
                </li>

                <!-- Wishlist -->
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page === 'wishlist.php') ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/frontend/wishlist.php" id="wishlist-nav-link">
                      
                        <i class="fas fa-heart me-1"></i>Wishlist
                    </a>
                </li>

                <!-- About -->
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page === 'about.php') ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/frontend/about.php">
                        <i class="fas fa-info-circle me-1"></i>About
                    </a>
                </li>

                <!-- Owner Dashboard -->
                <?php if (is_logged_in() && $_SESSION['user_type'] === 'owner'): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo (strpos($_SERVER['REQUEST_URI'] ?? '', '/frontend/owner/dashboard/index.php') !== false) ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/frontend/owner/dashboard/index.php">
                        <i class="fas fa-tachometer-alt me-1"></i>Owner Dashboard
                    </a>
                </li>
                <?php endif; ?>

                <!-- Admin Dashboard -->
                <?php if (is_logged_in() && $_SESSION['user_type'] === 'admin'): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo (strpos($_SERVER['REQUEST_URI'] ?? '', '/admin/dashboard/index.php') !== false) ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/admin/dashboard/index.php">
                        <i class="fas fa-tachometer-alt me-1"></i>Admin Dashboard
                    </a>
                </li>
                <?php endif; ?>

               

                <!-- Customer Menu (if logged in as customer) -->
                <?php if (is_logged_in() && $_SESSION['user_type'] === 'customer'): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_page === 'my-subscriptions.php') ? 'active' : ''; ?>" href="my-subscriptions.php">
                            <i class="fas fa-calendar-check me-1"></i>My Subscriptions
                        </a>
                    </li>
                <?php endif; ?>
            </ul>

            <!-- Right Side Menu -->
            <div class="d-flex">
                <?php if (is_logged_in()): ?>
                    <!-- User Dropdown -->
                    <div class="dropdown">
                        <button class="btn btn-outline-light dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user me-1"></i>
                            <?php echo htmlspecialchars($_SESSION['name'] ?? 'User'); ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <!-- Dashboard -->
                            <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/frontend/dashboard.php">
                                    <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                                </a></li>

                            <!-- Profile -->
                            <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/frontend/profile.php">
                                    <i class="fas fa-user-edit me-2"></i>Profile
                                </a></li>

                            <!-- Admin Menu (if admin) -->
                            <?php if ($_SESSION['user_type'] === 'admin'): ?>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li>
                                    <h6 class="dropdown-header">Admin Panel</h6>
                                </li>
                                <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/admin/dashboard/index.php">
                                        <i class="fas fa-cogs me-2"></i>Admin Dashboard
                                    </a></li>
                                <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/admin/properties/index.php">
                                        <i class="fas fa-building me-2"></i>Manage Properties
                                    </a></li>
                                <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/admin/users/index.php">
                                        <i class="fas fa-users me-2"></i>Manage Users
                                    </a></li>
                                <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/admin/bookings/index.php">
                                        <i class="fas fa-calendar me-2"></i>Manage Bookings
                                    </a></li>
                                <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/admin/payments/index.php">
                                        <i class="fas fa-credit-card me-2"></i>Manage Payments
                                    </a></li>
                                <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/admin/reports/index.php">
                                        <i class="fas fa-chart-bar me-2"></i>Reports
                                    </a></li>
                                <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/admin/settings/index.php">
                                        <i class="fas fa-cog me-2"></i>Settings
                                    </a></li>
                            <?php endif; ?>

                            <!-- Logout -->
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li><a class="dropdown-item text-danger" href="<?php echo APP_URL; ?>/frontend/logout.php">
                                    <i class="fas fa-sign-out-alt me-2"></i>Logout
                                </a></li>
                        </ul>
                    </div>
                <?php else: ?>
                    <!-- Login/Register Buttons -->
                     <!-- im a proprty owner -->
                     <div class="d-flex gap-2">
                     <a href="<?php echo APP_URL; ?>/frontend/owner/login.php" class="btn btn-outline-light">
                            <i class="fas fa-sign-in-alt me-1"></i>Owner Login
                        </a>
                  
                        <!-- customer login -->
                        <a href="<?php echo APP_URL; ?>/frontend/login.php" class="btn btn-outline-light">
                            <i class="fas fa-sign-in-alt me-1"></i>Login
                        </a>
                        <!-- customer register -->
                        <a href="<?php echo APP_URL; ?>/frontend/register.php" class="btn btn-light">
                            <i class="fas fa-user-plus me-1"></i>Register
                        </a>
                    </div>
                <?php endif; ?>

               
            </div>
        </div>
    </div>
</nav>
