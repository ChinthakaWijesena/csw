<?php
/**
 * Property Owner - Bought Properties Management
 * Manage rental bookings for owned properties
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../backend/models/User.php';
require_once __DIR__ . '/../../backend/models/Booking.php';
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
$booking_model = new Booking();
$property_model = new Property();

// Handle form submissions
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'terminate_booking') {
        $booking_id = $_POST['booking_id'];
        $reason = $_POST['reason'] ?? '';
        
        try {
            $booking_model->terminate($booking_id, $reason);
            $message = 'Booking terminated successfully!';
            $message_type = 'success';
        } catch (Exception $e) {
            $message = 'Error terminating booking: ' . $e->getMessage();
            $message_type = 'danger';
        }
    } elseif ($action === 'update_status') {
        $booking_id = $_POST['booking_id'];
        $status = $_POST['status'];
        
        try {
            $booking_model->updateStatus($booking_id, $status);
            $message = 'Booking status updated successfully!';
            $message_type = 'success';
        } catch (Exception $e) {
            $message = 'Error updating booking: ' . $e->getMessage();
            $message_type = 'danger';
        }
    }
}

// Get filters
$page = $_GET['page'] ?? 1;
$search = $_GET['search'] ?? '';
$filter_status = $_GET['filter_status'] ?? '';
$filter_property = $_GET['filter_property'] ?? '';

// Get bookings for this owner
$bookings = $booking_model->getByOwner($user_id, $page, 20, $search, $filter_status, $filter_property);
$total_bookings = $booking_model->getCountByOwner($user_id, $search, $filter_status, $filter_property);
$total_pages = ceil($total_bookings / 20);

// Get owner's properties for filter
$properties = $property_model->getByOwner($user_id, 1, 100);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bookings - Property Owner Dashboard</title>
    
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
        
        .booking-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
            overflow: hidden;
        }
        
        .booking-header {
            background: var(--booking-gray-50);
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--booking-gray-200);
            display: flex;
            align-items: center;
            justify-content: between;
        }
        
        .booking-id {
            font-weight: 600;
            color: var(--booking-gray-900);
            margin: 0;
        }
        
        .booking-status {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .status-active {
            background: var(--booking-success-light);
            color: var(--booking-success);
        }
        
        .status-terminated {
            background: var(--booking-danger-light);
            color: var(--booking-danger);
        }
        
        .status-expired {
            background: var(--booking-warning-light);
            color: var(--booking-warning);
        }
        
        .booking-body {
            padding: 1.5rem;
        }
        
        .booking-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .booking-detail {
            display: flex;
            align-items: center;
            color: var(--booking-gray-700);
        }
        
        .booking-detail i {
            margin-right: 0.5rem;
            color: var(--booking-primary);
            width: 16px;
        }
        
        .booking-detail-label {
            font-weight: 500;
            margin-right: 0.5rem;
        }
        
        .booking-amount {
            background: var(--booking-primary-light);
            padding: 1rem;
            border-radius: 8px;
            text-align: center;
            margin-bottom: 1.5rem;
        }
        
        .booking-amount-label {
            font-size: 0.875rem;
            color: var(--booking-gray-600);
            margin: 0 0 0.25rem 0;
        }
        
        .booking-amount-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--booking-primary);
            margin: 0;
        }
        
        .booking-actions {
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
            
            .booking-info {
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
                <a href="bought.php" class="owner-nav-link active">
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
                    <h1 class="h3 mb-0">Bookings</h1>
                    <p class="text-muted mb-0">Manage rental bookings for your properties</p>
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
        
        <!-- Statistics -->
        <div class="stats-cards">
            <div class="stat-card">
                <h3 class="stat-value"><?php echo $total_bookings; ?></h3>
                <p class="stat-label">Total Bookings</p>
            </div>
            <div class="stat-card">
                <h3 class="stat-value"><?php echo count(array_filter($bookings, function($b) { return $b['status'] === 'active'; })); ?></h3>
                <p class="stat-label">Active Bookings</p>
            </div>
            <div class="stat-card">
                <h3 class="stat-value"><?php echo count(array_filter($bookings, function($b) { return $b['status'] === 'terminated'; })); ?></h3>
                <p class="stat-label">Terminated</p>
            </div>
            <div class="stat-card">
                <h3 class="stat-value"><?php echo count(array_filter($bookings, function($b) { return $b['status'] === 'expired'; })); ?></h3>
                <p class="stat-label">Expired</p>
            </div>
        </div>
        
        <!-- Filters -->
        <div class="filter-section">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label for="search" class="form-label">Search Bookings</label>
                    <input type="text" class="form-control" id="search" name="search" 
                           value="<?php echo htmlspecialchars($search); ?>" 
                           placeholder="Search by customer name, property...">
                </div>
                <div class="col-md-3">
                    <label for="filter_status" class="form-label">Status</label>
                    <select class="form-select" id="filter_status" name="filter_status">
                        <option value="">All Status</option>
                        <option value="active" <?php echo $filter_status === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="terminated" <?php echo $filter_status === 'terminated' ? 'selected' : ''; ?>>Terminated</option>
                        <option value="expired" <?php echo $filter_status === 'expired' ? 'selected' : ''; ?>>Expired</option>
                    </select>
                </div>
                <div class="col-md-3">
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
                    <label class="form-label">&nbsp;</label>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-outline-primary">
                            <i class="fas fa-search me-1"></i>
                            Filter
                        </button>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Bookings List -->
        <?php if (empty($bookings)): ?>
            <div class="text-center py-5">
                <i class="fas fa-calendar-check fa-4x text-muted mb-4"></i>
                <h3 class="text-muted">No Bookings Found</h3>
                <p class="text-muted">Bookings for your properties will appear here.</p>
            </div>
        <?php else: ?>
            <?php foreach ($bookings as $booking): ?>
                <div class="booking-card">
                    <div class="booking-header">
                        <div class="d-flex align-items-center justify-content-between w-100">
                            <h5 class="booking-id mb-0">Booking #<?php echo $booking['id']; ?></h5>
                            <span class="booking-status status-<?php echo $booking['status']; ?>">
                                <?php echo ucfirst($booking['status']); ?>
                            </span>
                        </div>
                    </div>
                    <div class="booking-body">
                        <div class="booking-info">
                            <div class="booking-detail">
                                <i class="fas fa-user"></i>
                                <span class="booking-detail-label">Customer:</span>
                                <span><?php echo htmlspecialchars($booking['customer_name']); ?></span>
                            </div>
                            <div class="booking-detail">
                                <i class="fas fa-home"></i>
                                <span class="booking-detail-label">Property:</span>
                                <span><?php echo htmlspecialchars($booking['property_title']); ?></span>
                            </div>
                            <div class="booking-detail">
                                <i class="fas fa-calendar-alt"></i>
                                <span class="booking-detail-label">Start Date:</span>
                                <span><?php echo date('M j, Y', strtotime($booking['start_date'])); ?></span>
                            </div>
                            <div class="booking-detail">
                                <i class="fas fa-calendar-times"></i>
                                <span class="booking-detail-label">End Date:</span>
                                <span><?php echo $booking['end_date'] ? date('M j, Y', strtotime($booking['end_date'])) : 'Ongoing'; ?></span>
                            </div>
                        </div>
                        
                        <div class="booking-amount">
                            <p class="booking-amount-label">Monthly Rent</p>
                            <h4 class="booking-amount-value">LKR <?php echo number_format($booking['monthly_rent']); ?></h4>
                        </div>
                        
                        <div class="booking-actions">
                            <?php if ($booking['status'] === 'active'): ?>
                                <button class="btn btn-outline-danger btn-sm" 
                                        onclick="terminateBooking(<?php echo $booking['id']; ?>)">
                                    <i class="fas fa-times me-1"></i>
                                    Terminate
                                </button>
                            <?php endif; ?>
                            
                            <button class="btn btn-outline-primary btn-sm" 
                                    onclick="viewBookingDetails(<?php echo $booking['id']; ?>)">
                                <i class="fas fa-eye me-1"></i>
                                View Details
                            </button>
                            
                            <a href="payments.php?booking_id=<?php echo $booking['id']; ?>" 
                               class="btn btn-outline-success btn-sm">
                                <i class="fas fa-credit-card me-1"></i>
                                Payments
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <nav aria-label="Bookings pagination">
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&filter_status=<?php echo urlencode($filter_status); ?>&filter_property=<?php echo urlencode($filter_property); ?>">Previous</a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&filter_status=<?php echo urlencode($filter_status); ?>&filter_property=<?php echo urlencode($filter_property); ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&filter_status=<?php echo urlencode($filter_status); ?>&filter_property=<?php echo urlencode($filter_property); ?>">Next</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    
    <!-- Terminate Booking Modal -->
    <div class="modal fade" id="terminateModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Terminate Booking</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="terminateForm">
                    <input type="hidden" name="action" value="terminate_booking">
                    <input type="hidden" name="booking_id" id="terminateBookingId">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="reason" class="form-label">Reason for Termination</label>
                            <textarea class="form-control" id="reason" name="reason" rows="3" required></textarea>
                        </div>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            This action cannot be undone. The customer will be notified of the termination.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Terminate Booking</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function terminateBooking(bookingId) {
            document.getElementById('terminateBookingId').value = bookingId;
            const modal = new bootstrap.Modal(document.getElementById('terminateModal'));
            modal.show();
        }
        
        function viewBookingDetails(bookingId) {
            // TODO: Implement view booking details functionality
            alert('View booking details functionality will be implemented soon!');
        }
    </script>
</body>
</html>
