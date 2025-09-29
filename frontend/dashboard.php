<?php
/**
 * User Dashboard - Booking.com Design System
 */

require_once __DIR__ . '/../config/config.php';

// Require login
require_login();

$user_model = new User();
$property_model = new Property();

// Get user dashboard data
$dashboard_data = $user_model->getDashboardData($_SESSION['user_id']);
$user = $dashboard_data['user'];

// Get recent activities based on user type
$recent_activities = [];

if ($user['user_type'] === 'owner') {
    // Get recent properties
    $recent_properties = $property_model->getByOwner($_SESSION['user_id'], 1, 5);
    
    // Get recent visit requests
    $recent_visits = $database->fetchAll(
        "SELECT vr.*, p.title as property_title, u.name as customer_name 
         FROM visit_requests vr 
         JOIN properties p ON vr.property_id = p.id 
         JOIN users u ON vr.customer_id = u.id 
         WHERE p.owner_id = ? 
         ORDER BY vr.created_at DESC 
         LIMIT 5",
        [$_SESSION['user_id']]
    );
    
    // Get recent payments
    $recent_payments = $database->fetchAll(
        "SELECT rp.*, p.title as property_title, u.name as customer_name 
         FROM rent_payments rp 
         JOIN properties p ON rp.property_id = p.id 
         JOIN users u ON rp.customer_id = u.id 
         WHERE rp.owner_id = ? 
         ORDER BY rp.created_at DESC 
         LIMIT 5",
        [$_SESSION['user_id']]
    );
    
} elseif ($user['user_type'] === 'customer') {
    // Get recent bookings
    $recent_bookings = $database->fetchAll(
        "SELECT rb.*, p.title as property_title, p.monthly_rent 
         FROM rental_bookings rb 
         JOIN properties p ON rb.property_id = p.id 
         WHERE rb.customer_id = ? 
         ORDER BY rb.created_at DESC 
         LIMIT 5",
        [$_SESSION['user_id']]
    );
    
    // Get recent visit requests
    $recent_visits = $database->fetchAll(
        "SELECT vr.*, p.title as property_title, u.name as owner_name 
         FROM visit_requests vr 
         JOIN properties p ON vr.property_id = p.id 
         JOIN users u ON p.owner_id = u.id 
         WHERE vr.customer_id = ? 
         ORDER BY vr.created_at DESC 
         LIMIT 5",
        [$_SESSION['user_id']]
    );
    
    // Get recent payments
    $recent_payments = $database->fetchAll(
        "SELECT rp.*, p.title as property_title 
         FROM rent_payments rp 
         JOIN properties p ON rp.property_id = p.id 
         WHERE rp.customer_id = ? 
         ORDER BY rp.created_at DESC 
         LIMIT 5",
        [$_SESSION['user_id']]
    );
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo APP_NAME; ?></title>
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Complete CSS (Booking.com Design System) -->
</head>
<body>
    <!-- Booking.com Style Header -->
    <header class="booking-header">
        <div class="container">
            <a href="index.php" class="booking-logo">
                <div class="booking-logo-icon">
                    <i class="fas fa-home"></i>
                </div>
                <?php echo APP_NAME; ?>
            </a>
            
            <nav class="booking-nav">
                <ul class="booking-nav-links">
                    <li><a href="index.php">Home</a></li>
                    <li><a href="search.php">Search Properties</a></li>
                    <?php if ($user['user_type'] === 'owner'): ?>
                        <li><a href="my-properties.php">My Properties</a></li>
                    <?php endif; ?>
                </ul>
                
                <div class="booking-user-menu">
                    <div class="booking-user-dropdown">
                        <button class="booking-btn booking-btn-outline">
                            <i class="fas fa-user"></i> <?php echo htmlspecialchars($user['name']); ?>
                        </button>
                        <div class="booking-dropdown-menu">
                            <a href="dashboard.php" class="booking-dropdown-item">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                            <a href="profile.php" class="booking-dropdown-item">
                                <i class="fas fa-user"></i> Profile
                            </a>
                            <?php if ($user['user_type'] === 'owner'): ?>
                                <a href="my-properties.php" class="booking-dropdown-item">
                                    <i class="fas fa-building"></i> My Properties
                                </a>
                            <?php endif; ?>
                            <hr class="booking-dropdown-divider">
                            <a href="logout.php" class="booking-dropdown-item">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a>
                        </div>
                    </div>
                </div>
            </nav>
        </div>
    </header>

    <!-- Dashboard Content -->
    <main class="booking-dashboard-main">
        <div class="container">
            <!-- Welcome Section -->
            <section class="booking-dashboard-welcome">
                <div class="booking-dashboard-welcome-card">
                    <div class="booking-dashboard-welcome-content">
                        <h1 class="booking-dashboard-welcome-title">
                            Welcome back, <?php echo htmlspecialchars($user['name']); ?>!
                        </h1>
                        <p class="booking-dashboard-welcome-subtitle">
                            <?php if ($user['user_type'] === 'owner'): ?>
                                Manage your properties and track your rental income.
                            <?php else: ?>
                                Find your perfect rental home and manage your bookings.
                            <?php endif; ?>
                        </p>
                    </div>
                    <div class="booking-dashboard-welcome-icon">
                        <i class="fas fa-<?php echo $user['user_type'] === 'owner' ? 'building' : 'home'; ?>"></i>
                    </div>
                </div>
            </section>

            <!-- Statistics Cards -->
            <section class="booking-dashboard-stats">
                <div class="booking-stats-grid">
                    <?php if ($user['user_type'] === 'owner'): ?>
                        <div class="booking-stat-card">
                            <div class="booking-stat-icon">
                                <i class="fas fa-home"></i>
                            </div>
                            <div class="booking-stat-content">
                                <div class="booking-stat-number"><?php echo $dashboard_data['properties']; ?></div>
                                <div class="booking-stat-label">Properties</div>
                            </div>
                        </div>
                        <div class="booking-stat-card">
                            <div class="booking-stat-icon">
                                <i class="fas fa-calendar-check"></i>
                            </div>
                            <div class="booking-stat-content">
                                <div class="booking-stat-number"><?php echo count($recent_visits); ?></div>
                                <div class="booking-stat-label">Visit Requests</div>
                            </div>
                        </div>
                        <div class="booking-stat-card">
                            <div class="booking-stat-icon">
                                <i class="fas fa-dollar-sign"></i>
                            </div>
                            <div class="booking-stat-content">
                                <div class="booking-stat-number">LKR <?php echo number_format($dashboard_data['total_earnings'], 0); ?></div>
                                <div class="booking-stat-label">Total Earnings</div>
                            </div>
                        </div>
                        <div class="booking-stat-card">
                            <div class="booking-stat-icon">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <div class="booking-stat-content">
                                <div class="booking-stat-number"><?php echo count($recent_payments); ?></div>
                                <div class="booking-stat-label">Recent Payments</div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="booking-stat-card">
                            <div class="booking-stat-icon">
                                <i class="fas fa-calendar"></i>
                            </div>
                            <div class="booking-stat-content">
                                <div class="booking-stat-number"><?php echo $dashboard_data['bookings']; ?></div>
                                <div class="booking-stat-label">Bookings</div>
                            </div>
                        </div>
                        <div class="booking-stat-card">
                            <div class="booking-stat-icon">
                                <i class="fas fa-eye"></i>
                            </div>
                            <div class="booking-stat-content">
                                <div class="booking-stat-number"><?php echo count($recent_visits); ?></div>
                                <div class="booking-stat-label">Visit Requests</div>
                            </div>
                        </div>
                        <div class="booking-stat-card">
                            <div class="booking-stat-icon">
                                <i class="fas fa-credit-card"></i>
                            </div>
                            <div class="booking-stat-content">
                                <div class="booking-stat-number">LKR <?php echo number_format($dashboard_data['payments'], 0); ?></div>
                                <div class="booking-stat-label">Total Paid</div>
                            </div>
                        </div>
                        <div class="booking-stat-card">
                            <div class="booking-stat-icon">
                                <i class="fas fa-search"></i>
                            </div>
                            <div class="booking-stat-content">
                                <div class="booking-stat-number"><?php echo count($recent_payments); ?></div>
                                <div class="booking-stat-label">Recent Activity</div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </section>

            <!-- Recent Activity -->
            <section class="booking-dashboard-activity">
                <div class="booking-activity-grid">
                    <?php if ($user['user_type'] === 'owner'): ?>
                        <!-- Owner Dashboard -->
                        <div class="booking-activity-card">
                            <div class="booking-activity-header">
                                <h3 class="booking-activity-title">Recent Properties</h3>
                                <a href="my-properties.php" class="booking-btn booking-btn-outline booking-btn-sm">View All</a>
                            </div>
                            <div class="booking-activity-content">
                                <?php if (empty($recent_properties)): ?>
                                    <div class="booking-empty-state">
                                        <div class="booking-empty-icon">
                                            <i class="fas fa-home"></i>
                                        </div>
                                        <h4 class="booking-empty-title">No properties yet</h4>
                                        <p class="booking-empty-subtitle">Start by adding your first property</p>
                                        <a href="add-property.php" class="booking-btn booking-btn-primary">Add Your First Property</a>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($recent_properties as $property): ?>
                                        <div class="booking-activity-item">
                                            <div class="booking-activity-item-icon">
                                                <i class="fas fa-home"></i>
                                            </div>
                                            <div class="booking-activity-item-content">
                                                <h4 class="booking-activity-item-title"><?php echo htmlspecialchars($property['title']); ?></h4>
                                                <p class="booking-activity-item-subtitle">LKR <?php echo number_format($property['monthly_rent']); ?>/month</p>
                                            </div>
                                            <div class="booking-activity-item-status">
                                                <span class="booking-badge booking-badge-<?php echo $property['is_verified'] ? 'success' : 'warning'; ?>">
                                                    <?php echo $property['is_verified'] ? 'Verified' : 'Pending'; ?>
                                                </span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="booking-activity-card">
                            <div class="booking-activity-header">
                                <h3 class="booking-activity-title">Recent Visit Requests</h3>
                                <a href="visit-requests.php" class="booking-btn booking-btn-outline booking-btn-sm">View All</a>
                            </div>
                            <div class="booking-activity-content">
                                <?php if (empty($recent_visits)): ?>
                                    <div class="booking-empty-state">
                                        <div class="booking-empty-icon">
                                            <i class="fas fa-calendar"></i>
                                        </div>
                                        <h4 class="booking-empty-title">No visit requests yet</h4>
                                        <p class="booking-empty-subtitle">Visit requests will appear here</p>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($recent_visits as $visit): ?>
                                        <div class="booking-activity-item">
                                            <div class="booking-activity-item-icon">
                                                <i class="fas fa-eye"></i>
                                            </div>
                                            <div class="booking-activity-item-content">
                                                <h4 class="booking-activity-item-title"><?php echo htmlspecialchars($visit['property_title']); ?></h4>
                                                <p class="booking-activity-item-subtitle">
                                                    Requested by <?php echo htmlspecialchars($visit['customer_name']); ?>
                                                    on <?php echo format_date($visit['requested_date']); ?>
                                                </p>
                                            </div>
                                            <div class="booking-activity-item-status">
                                                <span class="booking-badge booking-badge-<?php echo $visit['status'] === 'pending' ? 'warning' : ($visit['status'] === 'approved' ? 'success' : 'error'); ?>">
                                                    <?php echo ucfirst($visit['status']); ?>
                                                </span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- Customer Dashboard -->
                        <div class="booking-activity-card">
                            <div class="booking-activity-header">
                                <h3 class="booking-activity-title">Recent Bookings</h3>
                                <a href="my-bookings.php" class="booking-btn booking-btn-outline booking-btn-sm">View All</a>
                            </div>
                            <div class="booking-activity-content">
                                <?php if (empty($recent_bookings)): ?>
                                    <div class="booking-empty-state">
                                        <div class="booking-empty-icon">
                                            <i class="fas fa-calendar"></i>
                                        </div>
                                        <h4 class="booking-empty-title">No bookings yet</h4>
                                        <p class="booking-empty-subtitle">Start searching for your perfect home</p>
                                        <a href="search.php" class="booking-btn booking-btn-primary">Search Properties</a>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($recent_bookings as $booking): ?>
                                        <div class="booking-activity-item">
                                            <div class="booking-activity-item-icon">
                                                <i class="fas fa-home"></i>
                                            </div>
                                            <div class="booking-activity-item-content">
                                                <h4 class="booking-activity-item-title"><?php echo htmlspecialchars($booking['property_title']); ?></h4>
                                                <p class="booking-activity-item-subtitle">
                                                    Started <?php echo format_date($booking['start_date']); ?>
                                                    - LKR <?php echo number_format($booking['monthly_rent']); ?>/month
                                                </p>
                                            </div>
                                            <div class="booking-activity-item-status">
                                                <span class="booking-badge booking-badge-<?php echo $booking['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                                    <?php echo ucfirst($booking['status']); ?>
                                                </span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="booking-activity-card">
                            <div class="booking-activity-header">
                                <h3 class="booking-activity-title">Recent Visit Requests</h3>
                                <a href="my-visits.php" class="booking-btn booking-btn-outline booking-btn-sm">View All</a>
                            </div>
                            <div class="booking-activity-content">
                                <?php if (empty($recent_visits)): ?>
                                    <div class="booking-empty-state">
                                        <div class="booking-empty-icon">
                                            <i class="fas fa-eye"></i>
                                        </div>
                                        <h4 class="booking-empty-title">No visit requests yet</h4>
                                        <p class="booking-empty-subtitle">Request visits to properties you're interested in</p>
                                        <a href="search.php" class="booking-btn booking-btn-primary">Search Properties</a>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($recent_visits as $visit): ?>
                                        <div class="booking-activity-item">
                                            <div class="booking-activity-item-icon">
                                                <i class="fas fa-eye"></i>
                                            </div>
                                            <div class="booking-activity-item-content">
                                                <h4 class="booking-activity-item-title"><?php echo htmlspecialchars($visit['property_title']); ?></h4>
                                                <p class="booking-activity-item-subtitle">
                                                    Requested for <?php echo format_date($visit['requested_date']); ?>
                                                    at <?php echo date('g:i A', strtotime($visit['requested_time'])); ?>
                                                </p>
                                            </div>
                                            <div class="booking-activity-item-status">
                                                <span class="booking-badge booking-badge-<?php echo $visit['status'] === 'pending' ? 'warning' : ($visit['status'] === 'approved' ? 'success' : 'error'); ?>">
                                                    <?php echo ucfirst($visit['status']); ?>
                                                </span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </section>

            <!-- Quick Actions -->
            <section class="booking-dashboard-actions">
                <div class="booking-actions-card">
                    <div class="booking-actions-header">
                        <h3 class="booking-actions-title">Quick Actions</h3>
                    </div>
                    <div class="booking-actions-content">
                        <div class="booking-actions-grid">
                            <?php if ($user['user_type'] === 'owner'): ?>
                                <a href="add-property.php" class="booking-btn booking-btn-primary booking-btn-lg">
                                    <i class="fas fa-plus"></i> Add Property
                                </a>
                                <a href="my-properties.php" class="booking-btn booking-btn-outline booking-btn-lg">
                                    <i class="fas fa-home"></i> Manage Properties
                                </a>
                                <a href="visit-requests.php" class="booking-btn booking-btn-outline booking-btn-lg">
                                    <i class="fas fa-calendar"></i> Visit Requests
                                </a>
                                <a href="payments.php" class="booking-btn booking-btn-outline booking-btn-lg">
                                    <i class="fas fa-dollar-sign"></i> View Earnings
                                </a>
                            <?php else: ?>
                                <a href="search.php" class="booking-btn booking-btn-primary booking-btn-lg">
                                    <i class="fas fa-search"></i> Search Properties
                                </a>
                                <a href="my-bookings.php" class="booking-btn booking-btn-outline booking-btn-lg">
                                    <i class="fas fa-calendar"></i> My Bookings
                                </a>
                                <a href="my-visits.php" class="booking-btn booking-btn-outline booking-btn-lg">
                                    <i class="fas fa-eye"></i> Visit Requests
                                </a>
                                <a href="payments.php" class="booking-btn booking-btn-outline booking-btn-lg">
                                    <i class="fas fa-credit-card"></i> Payment History
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </main>

    <!-- Include Footer -->
    <?php include 'includes/footer.php'; ?>

    <!-- Custom JS -->
    <script src="js/main.js"></script>
</body>
</html>