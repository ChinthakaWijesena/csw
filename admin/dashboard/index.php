<?php
/**
 * Admin Dashboard
 */

require_once __DIR__ . '/../../config/config.php';

// Require admin login
require_admin();

$user_model = new User();
$property_model = new Property();

// Get dashboard statistics
$user_stats = $user_model->getStats();
$property_stats = $property_model->getStats();

// Get recent activities
$recent_users = $user_model->getAll(1, 5);
$recent_properties = $property_model->getAll(1, 5, 'pending');

// Get recent payments
$recent_payments = $database->fetchAll(
    "SELECT rp.*, p.title as property_title, u1.name as customer_name, u2.name as owner_name 
     FROM rent_payments rp 
     JOIN properties p ON rp.property_id = p.id 
     JOIN users u1 ON rp.customer_id = u1.id 
     JOIN users u2 ON rp.owner_id = u2.id 
     ORDER BY rp.created_at DESC 
     LIMIT 10"
);

// Get monthly revenue data for chart
$monthly_revenue = $database->fetchAll(
    "SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as month,
        SUM(amount) as total_revenue,
        SUM(commission_amount) as total_commission
     FROM rent_payments 
     WHERE payment_status = 'completed' 
     AND created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
     GROUP BY DATE_FORMAT(created_at, '%Y-%m')
     ORDER BY month ASC"
);

// Get property type distribution
$property_types = $database->fetchAll(
    "SELECT property_type, COUNT(*) as count 
     FROM properties 
     WHERE is_verified = 1 
     GROUP BY property_type"
);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo APP_NAME; ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Custom CSS -->
    <style>
        .admin-sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .admin-sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            border-radius: 0.5rem;
            margin: 0.25rem 0;
        }
        .admin-sidebar .nav-link:hover,
        .admin-sidebar .nav-link.active {
            color: white;
            background-color: rgba(255, 255, 255, 0.1);
        }
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 1rem;
            padding: 1.5rem;
            margin-bottom: 1rem;
        }
        .stat-card i {
            font-size: 2.5rem;
            opacity: 0.8;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Admin Sidebar -->
            <div class="col-md-3 col-lg-2 px-0 admin-sidebar">
                <div class="p-3">
                    <h4 class="text-white mb-4">
                        <i class="fas fa-cog me-2"></i>Admin Panel
                    </h4>
                    
                    <ul class="nav nav-pills flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" href="index.php">
                                <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="users.php">
                                <i class="fas fa-users me-2"></i>Users
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="properties.php">
                                <i class="fas fa-home me-2"></i>Properties
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="bookings.php">
                                <i class="fas fa-calendar me-2"></i>Bookings
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="payments.php">
                                <i class="fas fa-credit-card me-2"></i>Payments
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="reports.php">
                                <i class="fas fa-chart-bar me-2"></i>Reports
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="settings.php">
                                <i class="fas fa-cog me-2"></i>Settings
                            </a>
                        </li>
                        <li class="nav-item mt-3">
                            <a class="nav-link" href="../../frontend/index.php">
                                <i class="fas fa-external-link-alt me-2"></i>View Site
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="../../frontend/logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i>Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-9 col-lg-10">
                <div class="p-4">
                    <!-- Header -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h1 class="h3">Admin Dashboard</h1>
                        <div class="text-muted">
                            Welcome back, <?php echo htmlspecialchars($_SESSION['name']); ?>
                        </div>
                    </div>
                    
                    <!-- Statistics Cards -->
                    <div class="row mb-4">
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="stat-card">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h3 class="mb-0"><?php echo number_format($user_stats['customer'] + $user_stats['owner']); ?></h3>
                                        <p class="mb-0">Total Users</p>
                                    </div>
                                    <i class="fas fa-users"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="stat-card">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h3 class="mb-0"><?php echo number_format($property_stats['total']); ?></h3>
                                        <p class="mb-0">Total Properties</p>
                                    </div>
                                    <i class="fas fa-home"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="stat-card">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h3 class="mb-0"><?php echo number_format($property_stats['verified']); ?></h3>
                                        <p class="mb-0">Verified Properties</p>
                                    </div>
                                    <i class="fas fa-check-circle"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="stat-card">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h3 class="mb-0">$<?php echo number_format(array_sum(array_column($recent_payments, 'amount')), 0); ?></h3>
                                        <p class="mb-0">Total Revenue</p>
                                    </div>
                                    <i class="fas fa-dollar-sign"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Charts Row -->
                    <div class="row mb-4">
                        <div class="col-lg-8 mb-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">Monthly Revenue</h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="revenueChart" height="100"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4 mb-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">Property Types</h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="propertyTypesChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Recent Activities -->
                    <div class="row">
                        <div class="col-lg-6 mb-4">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">Recent Users</h5>
                                    <a href="users.php" class="btn btn-sm btn-outline-primary">View All</a>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($recent_users)): ?>
                                        <div class="text-center py-3">
                                            <i class="fas fa-users fa-2x text-muted mb-2"></i>
                                            <p class="text-muted">No users yet</p>
                                        </div>
                                    <?php else: ?>
                                        <?php foreach ($recent_users as $user): ?>
                                            <div class="d-flex align-items-center mb-3">
                                                <div class="flex-shrink-0">
                                                    <div class="avatar-placeholder bg-primary text-white rounded-circle" style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                                                        <i class="fas fa-user"></i>
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1 ms-3">
                                                    <h6 class="mb-1"><?php echo htmlspecialchars($user['name']); ?></h6>
                                                    <small class="text-muted">
                                                        <?php echo htmlspecialchars($user['phone']); ?> • 
                                                        <?php echo ucfirst($user['user_type']); ?>
                                                    </small>
                                                </div>
                                                <div class="flex-shrink-0">
                                                    <span class="badge bg-<?php echo $user['is_verified'] ? 'success' : 'warning'; ?>">
                                                        <?php echo $user['is_verified'] ? 'Verified' : 'Pending'; ?>
                                                    </span>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-6 mb-4">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">Pending Properties</h5>
                                    <a href="properties.php?status=pending" class="btn btn-sm btn-outline-primary">View All</a>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($recent_properties)): ?>
                                        <div class="text-center py-3">
                                            <i class="fas fa-home fa-2x text-muted mb-2"></i>
                                            <p class="text-muted">No pending properties</p>
                                        </div>
                                    <?php else: ?>
                                        <?php foreach ($recent_properties as $property): ?>
                                            <div class="d-flex align-items-center mb-3">
                                                <div class="flex-shrink-0">
                                                    <img src="../../frontend/images/placeholder-property.jpg" class="rounded" width="50" height="50" alt="Property">
                                                </div>
                                                <div class="flex-grow-1 ms-3">
                                                    <h6 class="mb-1"><?php echo htmlspecialchars($property['title']); ?></h6>
                                                    <small class="text-muted">
                                                        $<?php echo number_format($property['monthly_rent']); ?>/month • 
                                                        <?php echo htmlspecialchars($property['city'] . ', ' . $property['state']); ?>
                                                    </small>
                                                </div>
                                                <div class="flex-shrink-0">
                                                    <div class="btn-group btn-group-sm">
                                                        <button class="btn btn-success" onclick="verifyProperty(<?php echo $property['id']; ?>)">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                        <button class="btn btn-danger" onclick="rejectProperty(<?php echo $property['id']; ?>)">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Recent Payments -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">Recent Payments</h5>
                                    <a href="payments.php" class="btn btn-sm btn-outline-primary">View All</a>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($recent_payments)): ?>
                                        <div class="text-center py-3">
                                            <i class="fas fa-credit-card fa-2x text-muted mb-2"></i>
                                            <p class="text-muted">No payments yet</p>
                                        </div>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>Property</th>
                                                        <th>Customer</th>
                                                        <th>Owner</th>
                                                        <th>Amount</th>
                                                        <th>Commission</th>
                                                        <th>Status</th>
                                                        <th>Date</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($recent_payments as $payment): ?>
                                                        <tr>
                                                            <td><?php echo htmlspecialchars($payment['property_title']); ?></td>
                                                            <td><?php echo htmlspecialchars($payment['customer_name']); ?></td>
                                                            <td><?php echo htmlspecialchars($payment['owner_name']); ?></td>
                                                            <td>$<?php echo number_format($payment['amount'], 2); ?></td>
                                                            <td>$<?php echo number_format($payment['commission_amount'], 2); ?></td>
                                                            <td>
                                                                <span class="badge bg-<?php echo $payment['payment_status'] === 'completed' ? 'success' : ($payment['payment_status'] === 'pending' ? 'warning' : 'danger'); ?>">
                                                                    <?php echo ucfirst($payment['payment_status']); ?>
                                                                </span>
                                                            </td>
                                                            <td><?php echo format_date($payment['created_at']); ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Revenue Chart
        const revenueCtx = document.getElementById('revenueChart').getContext('2d');
        const revenueChart = new Chart(revenueCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($monthly_revenue, 'month')); ?>,
                datasets: [{
                    label: 'Total Revenue',
                    data: <?php echo json_encode(array_column($monthly_revenue, 'total_revenue')); ?>,
                    borderColor: 'rgb(75, 192, 192)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    tension: 0.1
                }, {
                    label: 'Commission',
                    data: <?php echo json_encode(array_column($monthly_revenue, 'total_commission')); ?>,
                    borderColor: 'rgb(255, 99, 132)',
                    backgroundColor: 'rgba(255, 99, 132, 0.2)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '$' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });
        
        // Property Types Chart
        const typesCtx = document.getElementById('propertyTypesChart').getContext('2d');
        const typesChart = new Chart(typesCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_column($property_types, 'property_type')); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($property_types, 'count')); ?>,
                    backgroundColor: [
                        '#FF6384',
                        '#36A2EB',
                        '#FFCE56',
                        '#4BC0C0',
                        '#9966FF'
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
        
        // Property verification functions
        function verifyProperty(propertyId) {
            if (confirm('Are you sure you want to verify this property?')) {
                fetch(`/api/admin/verify-property.php`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        property_id: propertyId,
                        action: 'verify'
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred');
                });
            }
        }
        
        function rejectProperty(propertyId) {
            const reason = prompt('Please provide a reason for rejection:');
            if (reason) {
                fetch(`/api/admin/verify-property.php`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        property_id: propertyId,
                        action: 'reject',
                        reason: reason
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred');
                });
            }
        }
    </script>
</body>
</html>
