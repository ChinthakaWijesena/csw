<?php
/**
 * Property Owner - Payments Management
 * Track earnings and payment history
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../backend/models/User.php';
require_once __DIR__ . '/../../backend/models/Payment.php';
require_once __DIR__ . '/../../backend/models/Property.php';

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
$payment_model = new Payment();
$property_model = new Property();

// Get filters
$page = $_GET['page'] ?? 1;
$search = $_GET['search'] ?? '';
$filter_status = $_GET['filter_status'] ?? '';
$filter_property = $_GET['filter_property'] ?? '';
$filter_date_from = $_GET['date_from'] ?? '';
$filter_date_to = $_GET['date_to'] ?? '';

// Get payments for this owner
$payments = $payment_model->getByOwner($user_id, $page, 20, $search, $filter_status, $filter_property, $filter_date_from, $filter_date_to);
$total_payments = $payment_model->getCountByOwner($user_id, $search, $filter_status, $filter_property, $filter_date_from, $filter_date_to);
$total_pages = ceil($total_payments / 20);

// Get owner's properties for filter
$properties = $property_model->getByOwner($user_id, 1, 100);

// Get payment statistics
$payment_stats = $payment_model->getOwnerStats($user_id);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payments - Property Owner Dashboard</title>
    
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
        
        .payment-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
            overflow: hidden;
        }
        
        .payment-header {
            background: var(--booking-gray-50);
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--booking-gray-200);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .payment-id {
            font-weight: 600;
            color: var(--booking-gray-900);
            margin: 0;
        }
        
        .payment-status {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .status-completed {
            background: var(--booking-success-light);
            color: var(--booking-success);
        }
        
        .status-pending {
            background: var(--booking-warning-light);
            color: var(--booking-warning);
        }
        
        .status-failed {
            background: var(--booking-danger-light);
            color: var(--booking-danger);
        }
        
        .status-refunded {
            background: var(--booking-gray-light);
            color: var(--booking-gray-600);
        }
        
        .payment-body {
            padding: 1.5rem;
        }
        
        .payment-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .payment-detail {
            display: flex;
            align-items: center;
            color: var(--booking-gray-700);
        }
        
        .payment-detail i {
            margin-right: 0.5rem;
            color: var(--booking-primary);
            width: 16px;
        }
        
        .payment-detail-label {
            font-weight: 500;
            margin-right: 0.5rem;
        }
        
        .payment-amounts {
            background: var(--booking-primary-light);
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }
        
        .payment-amounts-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 1rem;
        }
        
        .amount-item {
            text-align: center;
        }
        
        .amount-label {
            font-size: 0.875rem;
            color: var(--booking-gray-600);
            margin: 0 0 0.25rem 0;
        }
        
        .amount-value {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--booking-primary);
            margin: 0;
        }
        
        .payment-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .filter-section {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--booking-primary);
            margin: 0;
        }
        
        .stat-label {
            color: var(--booking-gray-600);
            font-size: 0.875rem;
            margin: 0.5rem 0 0 0;
        }
        
        .export-section {
            background: white;
            padding: 1rem;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
            text-align: center;
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
            
            .payment-info {
                grid-template-columns: 1fr;
            }
            
            .payment-amounts-grid {
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
                <a href="payments.php" class="owner-nav-link active">
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
                    <h1 class="h3 mb-0">Payments</h1>
                    <p class="text-muted mb-0">Track your earnings and payment history</p>
                </div>
            </div>
        </div>
        
        <!-- Statistics -->
        <div class="stats-cards">
            <div class="stat-card">
                <h3 class="stat-value">LKR <?php echo number_format($payment_stats['total_earnings'], 2); ?></h3>
                <p class="stat-label">Total Earnings</p>
            </div>
            <div class="stat-card">
                <h3 class="stat-value">LKR <?php echo number_format($payment_stats['monthly_earnings'], 2); ?></h3>
                <p class="stat-label">This Month</p>
            </div>
            <div class="stat-card">
                <h3 class="stat-value"><?php echo $payment_stats['completed_payments']; ?></h3>
                <p class="stat-label">Completed Payments</p>
            </div>
            <div class="stat-card">
                <h3 class="stat-value"><?php echo $payment_stats['pending_payments']; ?></h3>
                <p class="stat-label">Pending Payments</p>
            </div>
        </div>
        
        <!-- Export Section -->
        <div class="export-section">
            <h5 class="mb-3">Export Payment Data</h5>
            <div class="d-flex justify-content-center gap-2">
                <button class="btn btn-outline-primary" onclick="exportPayments('csv')">
                    <i class="fas fa-file-csv me-2"></i>
                    Export as CSV
                </button>
                <button class="btn btn-outline-success" onclick="exportPayments('excel')">
                    <i class="fas fa-file-excel me-2"></i>
                    Export as Excel
                </button>
                <button class="btn btn-outline-danger" onclick="exportPayments('pdf')">
                    <i class="fas fa-file-pdf me-2"></i>
                    Export as PDF
                </button>
            </div>
        </div>
        
        <!-- Filters -->
        <div class="filter-section">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label for="search" class="form-label">Search Payments</label>
                    <input type="text" class="form-control" id="search" name="search" 
                           value="<?php echo htmlspecialchars($search); ?>" 
                           placeholder="Search by payment ID, customer...">
                </div>
                <div class="col-md-2">
                    <label for="filter_status" class="form-label">Status</label>
                    <select class="form-select" id="filter_status" name="filter_status">
                        <option value="">All Status</option>
                        <option value="completed" <?php echo $filter_status === 'completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="pending" <?php echo $filter_status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="failed" <?php echo $filter_status === 'failed' ? 'selected' : ''; ?>>Failed</option>
                        <option value="refunded" <?php echo $filter_status === 'refunded' ? 'selected' : ''; ?>>Refunded</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="filter_property" class="form-label">Property</label>
                    <select class="form-select" id="filter_property" name="filter_property">
                        <option value="">All Properties</option>
                        <?php foreach ($properties as $property): ?>
                            <option value="<?php echo $property['id']; ?>" <?php echo $filter_property == $property['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($property['title']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="date_from" class="form-label">From Date</label>
                    <input type="date" class="form-control" id="date_from" name="date_from" 
                           value="<?php echo htmlspecialchars($filter_date_from); ?>">
                </div>
                <div class="col-md-2">
                    <label for="date_to" class="form-label">To Date</label>
                    <input type="date" class="form-control" id="date_to" name="date_to" 
                           value="<?php echo htmlspecialchars($filter_date_to); ?>">
                </div>
                <div class="col-md-1">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-outline-primary">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Payments List -->
        <?php if (empty($payments)): ?>
            <div class="text-center py-5">
                <i class="fas fa-credit-card fa-4x text-muted mb-4"></i>
                <h3 class="text-muted">No Payments Found</h3>
                <p class="text-muted">Payment records will appear here once bookings are made.</p>
            </div>
        <?php else: ?>
            <?php foreach ($payments as $payment): ?>
                <div class="payment-card">
                    <div class="payment-header">
                        <div class="d-flex align-items-center justify-content-between w-100">
                            <h5 class="payment-id mb-0">Payment #<?php echo $payment['id']; ?></h5>
                            <span class="payment-status status-<?php echo $payment['payment_status']; ?>">
                                <?php echo ucfirst($payment['payment_status']); ?>
                            </span>
                        </div>
                    </div>
                    <div class="payment-body">
                        <div class="payment-info">
                            <div class="payment-detail">
                                <i class="fas fa-user"></i>
                                <span class="payment-detail-label">Customer:</span>
                                <span><?php echo htmlspecialchars($payment['customer_name']); ?></span>
                            </div>
                            <div class="payment-detail">
                                <i class="fas fa-home"></i>
                                <span class="payment-detail-label">Property:</span>
                                <span><?php echo htmlspecialchars($payment['property_title']); ?></span>
                            </div>
                            <div class="payment-detail">
                                <i class="fas fa-calendar-alt"></i>
                                <span class="payment-detail-label">Due Date:</span>
                                <span><?php echo date('M j, Y', strtotime($payment['due_date'])); ?></span>
                            </div>
                            <div class="payment-detail">
                                <i class="fas fa-credit-card"></i>
                                <span class="payment-detail-label">Method:</span>
                                <span><?php echo ucfirst(str_replace('_', ' ', $payment['payment_method'])); ?></span>
                            </div>
                        </div>
                        
                        <div class="payment-amounts">
                            <div class="payment-amounts-grid">
                                <div class="amount-item">
                                    <p class="amount-label">Total Amount</p>
                                    <h5 class="amount-value">LKR <?php echo number_format($payment['amount'], 2); ?></h5>
                                </div>
                                <div class="amount-item">
                                    <p class="amount-label">Commission</p>
                                    <h5 class="amount-value">LKR <?php echo number_format($payment['commission_amount'], 2); ?></h5>
                                </div>
                                <div class="amount-item">
                                    <p class="amount-label">Your Payout</p>
                                    <h5 class="amount-value" style="color: var(--booking-success);">LKR <?php echo number_format($payment['owner_payout_amount'], 2); ?></h5>
                                </div>
                            </div>
                        </div>
                        
                        <div class="payment-actions">
                            <button class="btn btn-outline-primary btn-sm" 
                                    onclick="viewPaymentDetails(<?php echo $payment['id']; ?>)">
                                <i class="fas fa-eye me-1"></i>
                                View Details
                            </button>
                            
                            <?php if ($payment['payment_status'] === 'completed'): ?>
                                <button class="btn btn-outline-success btn-sm" 
                                        onclick="downloadReceipt(<?php echo $payment['id']; ?>)">
                                    <i class="fas fa-download me-1"></i>
                                    Download Receipt
                                </button>
                            <?php endif; ?>
                            
                            <a href="bought.php?payment_id=<?php echo $payment['id']; ?>" 
                               class="btn btn-outline-info btn-sm">
                                <i class="fas fa-calendar-check me-1"></i>
                                View Booking
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <nav aria-label="Payments pagination">
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&filter_status=<?php echo urlencode($filter_status); ?>&filter_property=<?php echo urlencode($filter_property); ?>&date_from=<?php echo urlencode($filter_date_from); ?>&date_to=<?php echo urlencode($filter_date_to); ?>">Previous</a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&filter_status=<?php echo urlencode($filter_status); ?>&filter_property=<?php echo urlencode($filter_property); ?>&date_from=<?php echo urlencode($filter_date_from); ?>&date_to=<?php echo urlencode($filter_date_to); ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&filter_status=<?php echo urlencode($filter_status); ?>&filter_property=<?php echo urlencode($filter_property); ?>&date_from=<?php echo urlencode($filter_date_from); ?>&date_to=<?php echo urlencode($filter_date_to); ?>">Next</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function viewPaymentDetails(paymentId) {
            // TODO: Implement view payment details functionality
            alert('View payment details functionality will be implemented soon!');
        }
        
        function downloadReceipt(paymentId) {
            // TODO: Implement download receipt functionality
            alert('Download receipt functionality will be implemented soon!');
        }
        
        function exportPayments(format) {
            // TODO: Implement export functionality
            alert('Export ' + format.toUpperCase() + ' functionality will be implemented soon!');
        }
    </script>
</body>
</html>
