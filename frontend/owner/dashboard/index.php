<?php
/**
 * Property Owner Dashboard
 * Main dashboard for property owners to manage their properties and bookings
 */

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../backend/models/User.php';
require_once __DIR__ . '/../../../backend/models/Property.php';
require_once __DIR__ . '/../../../backend/models/Booking.php';
require_once __DIR__ . '/../../../backend/models/Payment.php';

// Check if user is logged in and is a property owner
if (!is_logged_in()) {
    header('Location: ../../login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$user_model = new User();
$user = $user_model->getById($user_id);

if (!$user || $user['user_type'] !== 'owner') {
    header('Location: ../../login.php');
    exit;
}

// Initialize models
$property_model = new Property();
$booking_model = new Booking();
$payment_model = new Payment();

// Get dashboard statistics
$stats = $user_model->getDashboardData($user_id);

// Get recent properties
$recent_properties = $property_model->getByOwner($user_id, 1, 5);

// Get recent bookings
$recent_bookings = $booking_model->getByOwner($user_id, 1, 5);

// Get recent payments
$recent_payments = $payment_model->getByOwner($user_id, 1, 5);

// Get pending visit requests
$visit_requests = $property_model->getPendingVisitRequests($user_id);

// Get monthly earnings for chart
$monthly_earnings = $payment_model->getMonthlyEarnings($user_id, 12);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Property Owner Dashboard - Renting Place Finder</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Custom CSS -->
    <!-- Design Improvements -->
    <link href="../../css/design-improvements.css" rel="stylesheet">
    
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
        
        .owner-welcome {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .owner-welcome-text h1 {
            font-size: 1.75rem;
            font-weight: 600;
            color: var(--booking-gray-900);
            margin: 0;
        }
        
        .owner-welcome-text p {
            color: var(--booking-gray-600);
            margin: 0.25rem 0 0 0;
        }
        
        .owner-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .owner-stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-left: 4px solid var(--booking-primary);
        }
        
        .owner-stat-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1rem;
        }
        
        .owner-stat-title {
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--booking-gray-600);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .owner-stat-icon {
            width: 40px;
            height: 40px;
            background: var(--booking-primary-light);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--booking-primary);
        }
        
        .owner-stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--booking-gray-900);
            margin: 0;
        }
        
        .owner-stat-change {
            font-size: 0.875rem;
            margin-top: 0.5rem;
        }
        
        .owner-stat-change.positive {
            color: var(--booking-success);
        }
        
        .owner-stat-change.negative {
            color: var(--booking-danger);
        }
        
        .owner-content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
        }
        
        .owner-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .owner-card-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--booking-gray-200);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .owner-card-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--booking-gray-900);
            margin: 0;
        }
        
        .owner-card-body {
            padding: 1.5rem;
        }
        
        .owner-list-item {
            display: flex;
            align-items: center;
            padding: 1rem 0;
            border-bottom: 1px solid var(--booking-gray-100);
        }
        
        .owner-list-item:last-child {
            border-bottom: none;
        }
        
        .owner-list-content {
            flex: 1;
        }
        
        .owner-list-title {
            font-weight: 600;
            color: var(--booking-gray-900);
            margin: 0 0 0.25rem 0;
        }
        
        .owner-list-subtitle {
            font-size: 0.875rem;
            color: var(--booking-gray-600);
            margin: 0;
        }
        
        .owner-list-meta {
            text-align: right;
        }
        
        .owner-list-price {
            font-weight: 600;
            color: var(--booking-primary);
            font-size: 1.125rem;
        }
        
        .owner-list-status {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-weight: 500;
        }
        
        .owner-list-status.active {
            background: var(--booking-success-light);
            color: var(--booking-success);
        }
        
        .owner-list-status.pending {
            background: var(--booking-warning-light);
            color: var(--booking-warning);
        }
        
        .owner-list-status.terminated {
            background: var(--booking-danger-light);
            color: var(--booking-danger);
        }
        
        .owner-chart-container {
            height: 300px;
            position: relative;
        }
        
        .owner-quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }
        
        .owner-quick-action {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
            text-decoration: none;
            color: var(--booking-gray-700);
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }
        
        .owner-quick-action:hover {
            color: var(--booking-primary);
            border-color: var(--booking-primary);
            transform: translateY(-2px);
        }
        
        .owner-quick-action i {
            font-size: 2rem;
            margin-bottom: 1rem;
            color: var(--booking-primary);
        }
        
        .owner-quick-action h4 {
            font-size: 1rem;
            font-weight: 600;
            margin: 0 0 0.5rem 0;
        }
        
        .owner-quick-action p {
            font-size: 0.875rem;
            margin: 0;
            color: var(--booking-gray-600);
        }
        
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
            
            .owner-content-grid {
                grid-template-columns: 1fr;
            }
            
            .owner-stats-grid {
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
                <a href="index.php" class="owner-nav-link active">
                    <i class="fas fa-tachometer-alt"></i>
                    Dashboard
                </a>
            </div>
            <div class="owner-nav-item">
                <a href="../properties.php" class="owner-nav-link">
                    <i class="fas fa-home"></i>
                    My Properties
                </a>
            </div>
            <div class="owner-nav-item">
                <a href="../bought.php" class="owner-nav-link">
                    <i class="fas fa-calendar-check"></i>
                    Bookings
                </a>
            </div>
            <div class="owner-nav-item">
                <a href="../payments.php" class="owner-nav-link">
                    <i class="fas fa-credit-card"></i>
                    Payments
                </a>
            </div>
            <div class="owner-nav-item">
                <a href="../visits.php" class="owner-nav-link">
                    <i class="fas fa-eye"></i>
                    Visit Requests
                </a>
            </div>
            <div class="owner-nav-item">
                <a href="../analytics.php" class="owner-nav-link">
                    <i class="fas fa-chart-line"></i>
                    Analytics
                </a>
            </div>
            <div class="owner-nav-item">
                <a href="../profile.php" class="owner-nav-link">
                    <i class="fas fa-user"></i>
                    Profile
                </a>
            </div>
            <div class="owner-nav-item">
                <a href="../../logout.php" class="owner-nav-link">
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
            <div class="owner-welcome">
                <div class="owner-welcome-text">
                    <h1>Welcome back, <?php echo htmlspecialchars($user['name']); ?>!</h1>
                    <p>Here's what's happening with your properties today.</p>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <button class="btn btn-outline-primary">
                        <i class="fas fa-plus me-2"></i>
                        Add Property
                    </button>
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="fas fa-bell"></i>
                            <span class="badge bg-danger ms-1"><?php echo count($visit_requests); ?></span>
                        </button>
                        <ul class="dropdown-menu">
                            <li><h6 class="dropdown-header">Visit Requests</h6></li>
                            <?php if (empty($visit_requests)): ?>
                                <li><span class="dropdown-item-text">No pending requests</span></li>
                            <?php else: ?>
                                <?php foreach ($visit_requests as $request): ?>
                                    <li><a class="dropdown-item" href="../visits.php">New request for Property #<?php echo $request['property_id']; ?></a></li>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Statistics -->
        <div class="owner-stats-grid">
            <div class="owner-stat-card">
                <div class="owner-stat-header">
                    <span class="owner-stat-title">Total Properties</span>
                    <div class="owner-stat-icon">
                        <i class="fas fa-home"></i>
                    </div>
                </div>
                <h3 class="owner-stat-value"><?php echo number_format($stats['properties']); ?></h3>
                <div class="owner-stat-change positive">
                    <i class="fas fa-arrow-up"></i> +2 this month
                </div>
            </div>
            
            <div class="owner-stat-card">
                <div class="owner-stat-header">
                    <span class="owner-stat-title">Active Bookings</span>
                    <div class="owner-stat-icon">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                </div>
                <h3 class="owner-stat-value"><?php echo number_format($stats['bookings']); ?></h3>
                <div class="owner-stat-change positive">
                    <i class="fas fa-arrow-up"></i> +5 this week
                </div>
            </div>
            
            <div class="owner-stat-card">
                <div class="owner-stat-header">
                    <span class="owner-stat-title">Total Earnings</span>
                    <div class="owner-stat-icon">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                </div>
                <h3 class="owner-stat-value">LKR <?php echo number_format($stats['total_earnings'], 2); ?></h3>
                <div class="owner-stat-change positive">
                    <i class="fas fa-arrow-up"></i> +12% this month
                </div>
            </div>
            
            <div class="owner-stat-card">
                <div class="owner-stat-header">
                    <span class="owner-stat-title">Occupancy Rate</span>
                    <div class="owner-stat-icon">
                        <i class="fas fa-percentage"></i>
                    </div>
                </div>
                <h3 class="owner-stat-value">85%</h3>
                <div class="owner-stat-change positive">
                    <i class="fas fa-arrow-up"></i> +3% this month
                </div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="owner-quick-actions mb-4">
            <a href="../properties.php?action=add" class="owner-quick-action">
                <i class="fas fa-plus-circle"></i>
                <h4>Add Property</h4>
                <p>List a new property for rent</p>
            </a>
            <a href="../bought.php" class="owner-quick-action">
                <i class="fas fa-calendar-alt"></i>
                <h4>Manage Bookings</h4>
                <p>View and manage all bookings</p>
            </a>
            <a href="../visits.php" class="owner-quick-action">
                <i class="fas fa-eye"></i>
                <h4>Visit Requests</h4>
                <p>Approve or reject visit requests</p>
            </a>
            <a href="../analytics.php" class="owner-quick-action">
                <i class="fas fa-chart-bar"></i>
                <h4>View Analytics</h4>
                <p>Track performance and earnings</p>
            </a>
        </div>
        
        <!-- Main Content Grid -->
        <div class="owner-content-grid">
            <!-- Left Column -->
            <div>
                <!-- Recent Properties -->
                <div class="owner-card mb-4">
                    <div class="owner-card-header">
                        <h3 class="owner-card-title">Recent Properties</h3>
                        <a href="../properties.php" class="btn btn-sm btn-outline-primary">View All</a>
                    </div>
                    <div class="owner-card-body">
                        <?php if (empty($recent_properties)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-home fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No properties yet. <a href="../properties.php?action=add">Add your first property</a></p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($recent_properties as $property): ?>
                                <div class="owner-list-item">
                                    <div class="owner-list-content">
                                        <h5 class="owner-list-title"><?php echo htmlspecialchars($property['title']); ?></h5>
                                        <p class="owner-list-subtitle"><?php echo ucfirst($property['property_type']); ?> • <?php echo htmlspecialchars($property['city']); ?></p>
                                    </div>
                                    <div class="owner-list-meta">
                                        <div class="owner-list-price">LKR <?php echo number_format($property['monthly_rent']); ?>/mo</div>
                                        <span class="owner-list-status <?php echo $property['is_available'] ? 'active' : 'pending'; ?>">
                                            <?php echo $property['is_available'] ? 'Available' : 'Unavailable'; ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Recent Bookings -->
                <div class="owner-card">
                    <div class="owner-card-header">
                        <h3 class="owner-card-title">Recent Bookings</h3>
                        <a href="../bought.php" class="btn btn-sm btn-outline-primary">View All</a>
                    </div>
                    <div class="owner-card-body">
                        <?php if (empty($recent_bookings)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-calendar-check fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No bookings yet</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($recent_bookings as $booking): ?>
                                <div class="owner-list-item">
                                    <div class="owner-list-content">
                                        <h5 class="owner-list-title">Property #<?php echo $booking['property_id']; ?></h5>
                                        <p class="owner-list-subtitle">Started: <?php echo date('M j, Y', strtotime($booking['start_date'])); ?></p>
                                    </div>
                                    <div class="owner-list-meta">
                                        <div class="owner-list-price">LKR <?php echo number_format($booking['monthly_rent']); ?>/mo</div>
                                        <span class="owner-list-status <?php echo $booking['status']; ?>">
                                            <?php echo ucfirst($booking['status']); ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Right Column -->
            <div>
                <!-- Earnings Chart -->
                <div class="owner-card mb-4">
                    <div class="owner-card-header">
                        <h3 class="owner-card-title">Monthly Earnings</h3>
                    </div>
                    <div class="owner-card-body">
                        <div class="owner-chart-container">
                            <canvas id="earningsChart"></canvas>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Payments -->
                <div class="owner-card">
                    <div class="owner-card-header">
                        <h3 class="owner-card-title">Recent Payments</h3>
                        <a href="../payments.php" class="btn btn-sm btn-outline-primary">View All</a>
                    </div>
                    <div class="owner-card-body">
                        <?php if (empty($recent_payments)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-credit-card fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No payments yet</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($recent_payments as $payment): ?>
                                <div class="owner-list-item">
                                    <div class="owner-list-content">
                                        <h5 class="owner-list-title">Payment #<?php echo $payment['id']; ?></h5>
                                        <p class="owner-list-subtitle"><?php echo date('M j, Y', strtotime($payment['created_at'])); ?></p>
                                    </div>
                                    <div class="owner-list-meta">
                                        <div class="owner-list-price">LKR <?php echo number_format($payment['owner_payout_amount']); ?></div>
                                        <span class="owner-list-status <?php echo $payment['payment_status']; ?>">
                                            <?php echo ucfirst($payment['payment_status']); ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Earnings Chart
        const earningsCtx = document.getElementById('earningsChart').getContext('2d');
        const earningsChart = new Chart(earningsCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_keys($monthly_earnings)); ?>,
                datasets: [{
                    label: 'Monthly Earnings (LKR)',
                    data: <?php echo json_encode(array_values($monthly_earnings)); ?>,
                    borderColor: 'var(--booking-primary)',
                    backgroundColor: 'rgba(0, 113, 194, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'LKR ' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });
        
        // Mobile sidebar toggle
        function toggleSidebar() {
            document.getElementById('ownerSidebar').classList.toggle('show');
        }
        
        // Add mobile menu button if needed
        if (window.innerWidth <= 768) {
            const header = document.querySelector('.owner-header');
            const toggleBtn = document.createElement('button');
            toggleBtn.innerHTML = '<i class="fas fa-bars"></i>';
            toggleBtn.className = 'btn btn-outline-secondary d-md-none';
            toggleBtn.onclick = toggleSidebar;
            header.querySelector('.d-flex').prepend(toggleBtn);
        }
    </script>
</body>
</html>
