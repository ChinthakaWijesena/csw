<?php
/**
 * Property Owner - Visit Requests Management
 * Manage property visit requests from potential tenants
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../backend/models/User.php';
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

// Initialize property model
$property_model = new Property();

// Handle form submissions
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'respond_to_visit') {
        $visit_id = $_POST['visit_id'];
        $response = $_POST['response'];
        $owner_response = $_POST['owner_response'] ?? '';
        
        try {
            $property_model->respondToVisitRequest($visit_id, $response, $owner_response);
            $message = 'Visit request ' . $response . ' successfully!';
            $message_type = 'success';
        } catch (Exception $e) {
            $message = 'Error responding to visit request: ' . $e->getMessage();
            $message_type = 'danger';
        }
    }
}

// Get filters
$page = $_GET['page'] ?? 1;
$search = $_GET['search'] ?? '';
$filter_status = $_GET['filter_status'] ?? '';
$filter_property = $_GET['filter_property'] ?? '';

// Get visit requests for this owner
$visit_requests = $property_model->getVisitRequestsByOwner($user_id, $page, 20, $search, $filter_status, $filter_property);
$total_requests = $property_model->getVisitRequestsCountByOwner($user_id, $search, $filter_status, $filter_property);
$total_pages = ceil($total_requests / 20);

// Get owner's properties for filter
$properties = $property_model->getByOwner($user_id, 1, 100);

// Get visit request statistics
$visit_stats = $property_model->getVisitRequestStats($user_id);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visit Requests - Property Owner Dashboard</title>
    
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
        
        .visit-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
            overflow: hidden;
        }
        
        .visit-header {
            background: var(--booking-gray-50);
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--booking-gray-200);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .visit-id {
            font-weight: 600;
            color: var(--booking-gray-900);
            margin: 0;
        }
        
        .visit-status {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .status-pending {
            background: var(--booking-warning-light);
            color: var(--booking-warning);
        }
        
        .status-approved {
            background: var(--booking-success-light);
            color: var(--booking-success);
        }
        
        .status-rejected {
            background: var(--booking-danger-light);
            color: var(--booking-danger);
        }
        
        .status-completed {
            background: var(--booking-primary-light);
            color: var(--booking-primary);
        }
        
        .status-cancelled {
            background: var(--booking-gray-light);
            color: var(--booking-gray-600);
        }
        
        .visit-body {
            padding: 1.5rem;
        }
        
        .visit-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .visit-detail {
            display: flex;
            align-items: center;
            color: var(--booking-gray-700);
        }
        
        .visit-detail i {
            margin-right: 0.5rem;
            color: var(--booking-primary);
            width: 16px;
        }
        
        .visit-detail-label {
            font-weight: 500;
            margin-right: 0.5rem;
        }
        
        .visit-notes {
            background: var(--booking-gray-50);
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }
        
        .visit-notes h6 {
            color: var(--booking-gray-900);
            margin: 0 0 0.5rem 0;
        }
        
        .visit-notes p {
            color: var(--booking-gray-700);
            margin: 0;
            font-style: italic;
        }
        
        .visit-actions {
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
        
        .urgent-badge {
            background: var(--booking-danger);
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 500;
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
            
            .visit-info {
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
                <a href="visits.php" class="owner-nav-link active">
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
                    <h1 class="h3 mb-0">Visit Requests</h1>
                    <p class="text-muted mb-0">Manage property visit requests from potential tenants</p>
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
                <h3 class="stat-value"><?php echo $visit_stats['total_requests']; ?></h3>
                <p class="stat-label">Total Requests</p>
            </div>
            <div class="stat-card">
                <h3 class="stat-value"><?php echo $visit_stats['pending_requests']; ?></h3>
                <p class="stat-label">Pending</p>
            </div>
            <div class="stat-card">
                <h3 class="stat-value"><?php echo $visit_stats['approved_requests']; ?></h3>
                <p class="stat-label">Approved</p>
            </div>
            <div class="stat-card">
                <h3 class="stat-value"><?php echo $visit_stats['completed_requests']; ?></h3>
                <p class="stat-label">Completed</p>
            </div>
        </div>
        
        <!-- Filters -->
        <div class="filter-section">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label for="search" class="form-label">Search Requests</label>
                    <input type="text" class="form-control" id="search" name="search" 
                           value="<?php echo htmlspecialchars($search); ?>" 
                           placeholder="Search by customer name, property...">
                </div>
                <div class="col-md-3">
                    <label for="filter_status" class="form-label">Status</label>
                    <select class="form-select" id="filter_status" name="filter_status">
                        <option value="">All Status</option>
                        <option value="pending" <?php echo $filter_status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="approved" <?php echo $filter_status === 'approved' ? 'selected' : ''; ?>>Approved</option>
                        <option value="rejected" <?php echo $filter_status === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                        <option value="completed" <?php echo $filter_status === 'completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="cancelled" <?php echo $filter_status === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
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
        
        <!-- Visit Requests List -->
        <?php if (empty($visit_requests)): ?>
            <div class="text-center py-5">
                <i class="fas fa-eye fa-4x text-muted mb-4"></i>
                <h3 class="text-muted">No Visit Requests Found</h3>
                <p class="text-muted">Visit requests for your properties will appear here.</p>
            </div>
        <?php else: ?>
            <?php foreach ($visit_requests as $request): ?>
                <div class="visit-card">
                    <div class="visit-header">
                        <div class="d-flex align-items-center justify-content-between w-100">
                            <h5 class="visit-id mb-0">
                                Visit Request #<?php echo $request['id']; ?>
                                <?php if ($request['status'] === 'pending' && strtotime($request['requested_date']) <= strtotime('+1 day')): ?>
                                    <span class="urgent-badge ms-2">URGENT</span>
                                <?php endif; ?>
                            </h5>
                            <span class="visit-status status-<?php echo $request['status']; ?>">
                                <?php echo ucfirst($request['status']); ?>
                            </span>
                        </div>
                    </div>
                    <div class="visit-body">
                        <div class="visit-info">
                            <div class="visit-detail">
                                <i class="fas fa-user"></i>
                                <span class="visit-detail-label">Customer:</span>
                                <span><?php echo htmlspecialchars($request['customer_name']); ?></span>
                            </div>
                            <div class="visit-detail">
                                <i class="fas fa-home"></i>
                                <span class="visit-detail-label">Property:</span>
                                <span><?php echo htmlspecialchars($request['property_title']); ?></span>
                            </div>
                            <div class="visit-detail">
                                <i class="fas fa-calendar-alt"></i>
                                <span class="visit-detail-label">Requested Date:</span>
                                <span><?php echo date('M j, Y', strtotime($request['requested_date'])); ?></span>
                            </div>
                            <div class="visit-detail">
                                <i class="fas fa-clock"></i>
                                <span class="visit-detail-label">Requested Time:</span>
                                <span><?php echo date('g:i A', strtotime($request['requested_time'])); ?></span>
                            </div>
                        </div>
                        
                        <?php if ($request['notes']): ?>
                            <div class="visit-notes">
                                <h6>Customer Notes:</h6>
                                <p><?php echo htmlspecialchars($request['notes']); ?></p>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($request['owner_response']): ?>
                            <div class="visit-notes">
                                <h6>Your Response:</h6>
                                <p><?php echo htmlspecialchars($request['owner_response']); ?></p>
                            </div>
                        <?php endif; ?>
                        
                        <div class="visit-actions">
                            <?php if ($request['status'] === 'pending'): ?>
                                <button class="btn btn-success btn-sm" 
                                        onclick="respondToVisit(<?php echo $request['id']; ?>, 'approved')">
                                    <i class="fas fa-check me-1"></i>
                                    Approve
                                </button>
                                <button class="btn btn-danger btn-sm" 
                                        onclick="respondToVisit(<?php echo $request['id']; ?>, 'rejected')">
                                    <i class="fas fa-times me-1"></i>
                                    Reject
                                </button>
                            <?php elseif ($request['status'] === 'approved'): ?>
                                <button class="btn btn-primary btn-sm" 
                                        onclick="markCompleted(<?php echo $request['id']; ?>)">
                                    <i class="fas fa-check-circle me-1"></i>
                                    Mark Completed
                                </button>
                            <?php endif; ?>
                            
                            <button class="btn btn-outline-primary btn-sm" 
                                    onclick="viewVisitDetails(<?php echo $request['id']; ?>)">
                                <i class="fas fa-eye me-1"></i>
                                View Details
                            </button>
                            
                            <a href="properties.php?id=<?php echo $request['property_id']; ?>" 
                               class="btn btn-outline-info btn-sm">
                                <i class="fas fa-home me-1"></i>
                                View Property
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <nav aria-label="Visit requests pagination">
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
    
    <!-- Respond to Visit Modal -->
    <div class="modal fade" id="respondModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="respondModalTitle">Respond to Visit Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="respondForm">
                    <input type="hidden" name="action" value="respond_to_visit">
                    <input type="hidden" name="visit_id" id="respondVisitId">
                    <input type="hidden" name="response" id="respondAction">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="owner_response" class="form-label">Your Response</label>
                            <textarea class="form-control" id="owner_response" name="owner_response" rows="3" 
                                      placeholder="Add any additional notes or instructions for the customer..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn" id="respondSubmitBtn">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function respondToVisit(visitId, action) {
            document.getElementById('respondVisitId').value = visitId;
            document.getElementById('respondAction').value = action;
            
            const modal = document.getElementById('respondModal');
            const title = document.getElementById('respondModalTitle');
            const submitBtn = document.getElementById('respondSubmitBtn');
            
            if (action === 'approved') {
                title.textContent = 'Approve Visit Request';
                submitBtn.textContent = 'Approve Visit';
                submitBtn.className = 'btn btn-success';
            } else {
                title.textContent = 'Reject Visit Request';
                submitBtn.textContent = 'Reject Visit';
                submitBtn.className = 'btn btn-danger';
            }
            
            const modalInstance = new bootstrap.Modal(modal);
            modalInstance.show();
        }
        
        function markCompleted(visitId) {
            if (confirm('Mark this visit as completed?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="respond_to_visit">
                    <input type="hidden" name="visit_id" value="${visitId}">
                    <input type="hidden" name="response" value="completed">
                    <input type="hidden" name="owner_response" value="Visit completed successfully">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function viewVisitDetails(visitId) {
            // TODO: Implement view visit details functionality
            alert('View visit details functionality will be implemented soon!');
        }
    </script>
</body>
</html>
