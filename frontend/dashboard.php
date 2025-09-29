<?php
// Customer Dashboard

require_once __DIR__ . '/../config/config.php';

// Redirect to login if not authenticated
if (!is_logged_in()) {
    redirect(APP_URL . '/frontend/login.php');
}

// Restrict to customer role; route others to their dashboards
$userType = $_SESSION['user_type'] ?? '';
if ($userType !== 'customer') {
    if ($userType === 'owner') {
        redirect(APP_URL . '/frontend/owner/dashboard/index.php');
    } elseif ($userType === 'admin') {
        redirect(APP_URL . '/admin/dashboard/index.php');
    } else {
        redirect(APP_URL . '/frontend/index.php');
    }
}

$userName = htmlspecialchars($_SESSION['name'] ?? 'Customer');
$userPhone = htmlspecialchars($_SESSION['phone'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo APP_NAME; ?></title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body class="d-flex flex-column min-vh-100">
    <?php include __DIR__ . '/includes/navbar.php'; ?>

    <main class="flex-grow-1 py-4">
        <div class="container">
            <div class="row g-4">
                <div class="col-12">
                    <div class="card shadow-sm border-0">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center">
                                <div class="me-3">
                                    <span class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center" style="width:56px;height:56px;">
                                        <i class="fas fa-user"></i>
                                    </span>
                                </div>
                                <div>
                                    <h4 class="mb-1">Welcome back, <?php echo $userName; ?>!</h4>
                                    <?php if ($userPhone): ?>
                                        <div class="text-muted small"><i class="fas fa-phone me-1"></i><?php echo $userPhone; ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="col-12">
                    <div class="row g-3">
                        <div class="col-12 col-sm-6 col-lg-3">
                            <a href="<?php echo APP_URL; ?>/frontend/search.php" class="text-decoration-none">
                                <div class="card h-100 border-0 shadow-sm">
                                    <div class="card-body d-flex align-items-center">
                                        <div class="me-3 text-primary"><i class="fas fa-search fa-lg"></i></div>
                                        <div>
                                            <div class="fw-semibold text-dark">Search Properties</div>
                                            <div class="text-muted small">Find places to rent</div>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>
                        <div class="col-12 col-sm-6 col-lg-3">
                            <a href="<?php echo APP_URL; ?>/frontend/wishlist.php" class="text-decoration-none">
                                <div class="card h-100 border-0 shadow-sm">
                                    <div class="card-body d-flex align-items-center">
                                        <div class="me-3 text-danger"><i class="fas fa-heart fa-lg"></i></div>
                                        <div>
                                            <div class="fw-semibold text-dark">Wishlist</div>
                                            <div class="text-muted small">Saved properties</div>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>
                        <div class="col-12 col-sm-6 col-lg-3">
                            <a href="<?php echo APP_URL; ?>/frontend/my-subscriptions.php" class="text-decoration-none">
                                <div class="card h-100 border-0 shadow-sm">
                                    <div class="card-body d-flex align-items-center">
                                        <div class="me-3 text-success"><i class="fas fa-calendar-check fa-lg"></i></div>
                                        <div>
                                            <div class="fw-semibold text-dark">My Subscriptions</div>
                                            <div class="text-muted small">Manage your plans</div>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>
                        <div class="col-12 col-sm-6 col-lg-3">
                            <a href="<?php echo APP_URL; ?>/frontend/profile.php" class="text-decoration-none">
                                <div class="card h-100 border-0 shadow-sm">
                                    <div class="card-body d-flex align-items-center">
                                        <div class="me-3 text-secondary"><i class="fas fa-user-cog fa-lg"></i></div>
                                        <div>
                                            <div class="fw-semibold text-dark">Profile</div>
                                            <div class="text-muted small">Update your details</div>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Helpful Links -->
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white border-0">
                            <h5 class="mb-0"><i class="fas fa-link me-2"></i>Helpful Links</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-flex flex-wrap gap-2">
                                <a class="btn btn-outline-primary" href="<?php echo APP_URL; ?>/frontend/about.php"><i class="fas fa-info-circle me-2"></i>About</a>
                                <a class="btn btn-outline-secondary" href="<?php echo APP_URL; ?>/frontend/index.php"><i class="fas fa-home me-2"></i>Home</a>
                                <a class="btn btn-outline-dark" href="<?php echo APP_URL; ?>/frontend/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include __DIR__ . '/includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

