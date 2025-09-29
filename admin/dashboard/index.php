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
$recent_properties = $property_model->search([], 1, 5);

// Get recent payments
// Recent payments: adapt to available schema using property_sales
$recent_payments = $database->fetchAll(
    "SELECT 
        ps.id,
        p.title AS property_title,
        u_buyer.name AS customer_name,
        u_owner.name AS owner_name,
        ps.sale_price AS amount,
        (ps.sale_price * ? / 100.0) AS commission_amount,
        'completed' AS payment_status,
        ps.sale_date AS created_at
     FROM property_sales ps
     JOIN properties p ON ps.property_id = p.id
     JOIN users u_buyer ON ps.buyer_id = u_buyer.id
     JOIN users u_owner ON p.owner_id = u_owner.id
     ORDER BY ps.sale_date DESC
     LIMIT 10",
    [defined('COMMISSION_PERCENTAGE') ? COMMISSION_PERCENTAGE : 5.0]
);

// Get monthly revenue data for chart
$monthly_revenue = $database->fetchAll(
    "SELECT 
        DATE_FORMAT(ps.sale_date, '%Y-%m') AS month,
        SUM(ps.sale_price) AS total_revenue,
        SUM(ps.sale_price * ? / 100.0) AS total_commission
     FROM property_sales ps
     WHERE ps.sale_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
     GROUP BY DATE_FORMAT(ps.sale_date, '%Y-%m')
     ORDER BY month ASC",
    [defined('COMMISSION_PERCENTAGE') ? COMMISSION_PERCENTAGE : 5.0]
);

// Get property type distribution
$property_types = $database->fetchAll(
    "SELECT pt.name AS property_type, COUNT(*) AS count
     FROM properties p
     JOIN property_types pt ON pt.id = p.property_type_id
     GROUP BY pt.name"
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
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Admin Sidebar -->
            <?php $active_menu = 'dashboard'; include __DIR__ . '/_sidebar.php'; ?>
            
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
                            <div class="card bg-primary text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h3 class="mb-0"><?php echo number_format($user_stats['customers'] + $user_stats['owners']); ?></h3>
                                            <p class="mb-0">Total Users</p>
                                        </div>
                                        <i class="fas fa-users fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="card bg-success text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h3 class="mb-0"><?php echo number_format($property_stats['total']); ?></h3>
                                            <p class="mb-0">Total Properties</p>
                                        </div>
                                        <i class="fas fa-home fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="card bg-info text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h3 class="mb-0"><?php echo number_format($property_stats['verified']); ?></h3>
                                            <p class="mb-0">Verified Properties</p>
                                        </div>
                                        <i class="fas fa-check-circle fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="card bg-warning text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h3 class="mb-0">$<?php echo number_format(array_sum(array_column($recent_payments, 'amount')), 0); ?></h3>
                                            <p class="mb-0">Total Revenue</p>
                                        </div>
                                        <i class="fas fa-dollar-sign fa-2x"></i>
                                    </div>
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
