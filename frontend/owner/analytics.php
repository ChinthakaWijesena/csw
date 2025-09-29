<?php
/**
 * Property Owner - Analytics & Reports
 * View detailed analytics and reports for property performance
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../backend/models/User.php';
require_once __DIR__ . '/../../backend/models/Property.php';
require_once __DIR__ . '/../../backend/models/Booking.php';
require_once __DIR__ . '/../../backend/models/Payment.php';

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

// Initialize models
$property_model = new Property();
$booking_model = new Booking();
$payment_model = new Payment();

// Get date range filters
$date_from = $_GET['date_from'] ?? date('Y-m-01'); // First day of current month
$date_to = $_GET['date_to'] ?? date('Y-m-d'); // Today

// Get analytics data
$analytics = [
    'overview' => $property_model->getOwnerAnalytics($user_id, $date_from, $date_to),
    'earnings' => $payment_model->getOwnerEarningsAnalytics($user_id, $date_from, $date_to),
    'properties' => $property_model->getPropertyPerformanceAnalytics($user_id, $date_from, $date_to),
    'bookings' => $booking_model->getOwnerBookingAnalytics($user_id, $date_from, $date_to)
];

// Get monthly data for charts
$monthly_earnings = $payment_model->getMonthlyEarnings($user_id, 12);
$monthly_bookings = $booking_model->getMonthlyBookings($user_id, 12);
$property_performance = $property_model->getPropertyPerformanceData($user_id);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics - Property Owner Dashboard</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        
        .analytics-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
            overflow: hidden;
        }
        
        .analytics-header {
            background: var(--booking-gray-50);
            padding: 1.5rem;
            border-bottom: 1px solid var(--booking-gray-200);
        }
        
        .analytics-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--booking-gray-900);
            margin: 0;
        }
        
        .analytics-body {
            padding: 1.5rem;
        }
        
        .chart-container {
            height: 300px;
            position: relative;
        }
        
        .metric-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
            margin-bottom: 1rem;
        }
        
        .metric-value {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--booking-primary);
            margin: 0;
        }
        
        .metric-label {
            color: var(--booking-gray-600);
            font-size: 0.875rem;
            margin: 0.5rem 0 0 0;
        }
        
        .metric-change {
            font-size: 0.875rem;
            margin-top: 0.5rem;
        }
        
        .metric-change.positive {
            color: var(--booking-success);
        }
        
        .metric-change.negative {
            color: var(--booking-danger);
        }
        
        .filter-section {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .property-performance-table {
            width: 100%;
        }
        
        .property-performance-table th,
        .property-performance-table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid var(--booking-gray-200);
        }
        
        .property-performance-table th {
            background: var(--booking-gray-50);
            font-weight: 600;
            color: var(--booking-gray-900);
        }
        
        .performance-bar {
            height: 8px;
            background: var(--booking-gray-200);
            border-radius: 4px;
            overflow: hidden;
        }
        
        .performance-fill {
            height: 100%;
            background: var(--booking-primary);
            border-radius: 4px;
            transition: width 0.3s ease;
        }
        
        .export-buttons {
            display: flex;
            gap: 0.5rem;
            justify-content: flex-end;
            margin-bottom: 1rem;
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
            
            .export-buttons {
                justify-content: center;
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
                <a href="analytics.php" class="owner-nav-link active">
                    <i class="fas fa-chart-line"></i>
                    Analytics
                </a>
            </div>
            <div class="owner-nav-item">
                <a href="profile.php" class="owner-nav-link">
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
                    <h1 class="h3 mb-0">Analytics & Reports</h1>
                    <p class="text-muted mb-0">Track your property performance and earnings</p>
                </div>
                <div class="export-buttons">
                    <button class="btn btn-outline-primary btn-sm" onclick="exportReport('pdf')">
                        <i class="fas fa-file-pdf me-1"></i>
                        Export PDF
                    </button>
                    <button class="btn btn-outline-success btn-sm" onclick="exportReport('excel')">
                        <i class="fas fa-file-excel me-1"></i>
                        Export Excel
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Date Range Filter -->
        <div class="filter-section">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label for="date_from" class="form-label">From Date</label>
                    <input type="date" class="form-control" id="date_from" name="date_from" 
                           value="<?php echo htmlspecialchars($date_from); ?>">
                </div>
                <div class="col-md-4">
                    <label for="date_to" class="form-label">To Date</label>
                    <input type="date" class="form-control" id="date_to" name="date_to" 
                           value="<?php echo htmlspecialchars($date_to); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter me-1"></i>
                            Apply Filter
                        </button>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Overview Metrics -->
        <div class="row mb-4">
            <div class="col-lg-3 col-md-6">
                <div class="metric-card">
                    <h3 class="metric-value">LKR <?php echo number_format($analytics['overview']['total_earnings'], 2); ?></h3>
                    <p class="metric-label">Total Earnings</p>
                    <div class="metric-change positive">
                        <i class="fas fa-arrow-up"></i> +<?php echo $analytics['overview']['earnings_growth']; ?>% vs last period
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="metric-card">
                    <h3 class="metric-value"><?php echo $analytics['overview']['total_bookings']; ?></h3>
                    <p class="metric-label">Total Bookings</p>
                    <div class="metric-change positive">
                        <i class="fas fa-arrow-up"></i> +<?php echo $analytics['overview']['bookings_growth']; ?>% vs last period
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="metric-card">
                    <h3 class="metric-value"><?php echo $analytics['overview']['occupancy_rate']; ?>%</h3>
                    <p class="metric-label">Occupancy Rate</p>
                    <div class="metric-change positive">
                        <i class="fas fa-arrow-up"></i> +<?php echo $analytics['overview']['occupancy_growth']; ?>% vs last period
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="metric-card">
                    <h3 class="metric-value"><?php echo $analytics['overview']['avg_rent']; ?></h3>
                    <p class="metric-label">Avg. Monthly Rent</p>
                    <div class="metric-change positive">
                        <i class="fas fa-arrow-up"></i> +<?php echo $analytics['overview']['rent_growth']; ?>% vs last period
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Charts Row -->
        <div class="row mb-4">
            <div class="col-lg-8">
                <div class="analytics-card">
                    <div class="analytics-header">
                        <h3 class="analytics-title">Monthly Earnings Trend</h3>
                    </div>
                    <div class="analytics-body">
                        <div class="chart-container">
                            <canvas id="earningsChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="analytics-card">
                    <div class="analytics-header">
                        <h3 class="analytics-title">Booking Status</h3>
                    </div>
                    <div class="analytics-body">
                        <div class="chart-container">
                            <canvas id="bookingStatusChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Property Performance -->
        <div class="analytics-card">
            <div class="analytics-header">
                <h3 class="analytics-title">Property Performance</h3>
            </div>
            <div class="analytics-body">
                <div class="table-responsive">
                    <table class="property-performance-table">
                        <thead>
                            <tr>
                                <th>Property</th>
                                <th>Type</th>
                                <th>Monthly Rent</th>
                                <th>Occupancy</th>
                                <th>Total Earnings</th>
                                <th>Performance</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($property_performance as $property): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($property['title']); ?></strong><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($property['city']); ?></small>
                                    </td>
                                    <td><?php echo ucfirst($property['property_type']); ?></td>
                                    <td>LKR <?php echo number_format($property['monthly_rent']); ?></td>
                                    <td><?php echo $property['occupancy_rate']; ?>%</td>
                                    <td>LKR <?php echo number_format($property['total_earnings'], 2); ?></td>
                                    <td>
                                        <div class="performance-bar">
                                            <div class="performance-fill" style="width: <?php echo $property['performance_score']; ?>%"></div>
                                        </div>
                                        <small class="text-muted"><?php echo $property['performance_score']; ?>%</small>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Additional Analytics -->
        <div class="row mb-4">
            <div class="col-lg-6">
                <div class="analytics-card">
                    <div class="analytics-header">
                        <h3 class="analytics-title">Monthly Bookings</h3>
                    </div>
                    <div class="analytics-body">
                        <div class="chart-container">
                            <canvas id="monthlyBookingsChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="analytics-card">
                    <div class="analytics-header">
                        <h3 class="analytics-title">Property Type Distribution</h3>
                    </div>
                    <div class="analytics-body">
                        <div class="chart-container">
                            <canvas id="propertyTypeChart"></canvas>
                        </div>
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
        
        // Booking Status Chart
        const bookingStatusCtx = document.getElementById('bookingStatusChart').getContext('2d');
        const bookingStatusChart = new Chart(bookingStatusCtx, {
            type: 'doughnut',
            data: {
                labels: ['Active', 'Terminated', 'Expired'],
                datasets: [{
                    data: [
                        <?php echo $analytics['bookings']['active_bookings']; ?>,
                        <?php echo $analytics['bookings']['terminated_bookings']; ?>,
                        <?php echo $analytics['bookings']['expired_bookings']; ?>
                    ],
                    backgroundColor: [
                        'var(--booking-success)',
                        'var(--booking-danger)',
                        'var(--booking-warning)'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
        
        // Monthly Bookings Chart
        const monthlyBookingsCtx = document.getElementById('monthlyBookingsChart').getContext('2d');
        const monthlyBookingsChart = new Chart(monthlyBookingsCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_keys($monthly_bookings)); ?>,
                datasets: [{
                    label: 'Bookings',
                    data: <?php echo json_encode(array_values($monthly_bookings)); ?>,
                    backgroundColor: 'var(--booking-primary)',
                    borderColor: 'var(--booking-primary-dark)',
                    borderWidth: 1
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
                        beginAtZero: true
                    }
                }
            }
        });
        
        // Property Type Chart
        const propertyTypeCtx = document.getElementById('propertyTypeChart').getContext('2d');
        const propertyTypeChart = new Chart(propertyTypeCtx, {
            type: 'pie',
            data: {
                labels: <?php echo json_encode(array_keys($analytics['properties']['type_distribution'])); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_values($analytics['properties']['type_distribution'])); ?>,
                    backgroundColor: [
                        'var(--booking-primary)',
                        'var(--booking-success)',
                        'var(--booking-warning)',
                        'var(--booking-danger)',
                        'var(--booking-info)'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
        
        function exportReport(format) {
            // TODO: Implement export functionality
            alert('Export ' + format.toUpperCase() + ' functionality will be implemented soon!');
        }
    </script>
</body>
</html>
