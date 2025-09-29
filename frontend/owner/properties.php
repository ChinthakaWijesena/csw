<?php
/**
 * Property Owner - Properties Management
 * Manage property listings, add new properties, edit existing ones
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../backend/models/User.php';
require_once __DIR__ . '/../../backend/models/Property.php';
require_once __DIR__ . '/../../backend/models/PropertyType.php';

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
$property_type_model = new PropertyType();

// Get property types from database
$property_types = $property_type_model->getAllActive();

// Handle form submissions
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_property') {
        $property_data = [
            'owner_id' => $user_id,
            'title' => $_POST['title'],
            'description' => $_POST['description'],
            'property_type' => $_POST['property_type'],
            'bedrooms' => $_POST['bedrooms'] ?: null,
            'bathrooms' => $_POST['bathrooms'] ?: null,
            'area_sqft' => $_POST['area_sqft'] ?: null,
            'monthly_rent' => $_POST['monthly_rent'],
            'security_deposit' => $_POST['security_deposit'] ?: null,
            'address' => $_POST['address'],
            'city' => $_POST['city'],
            'state' => $_POST['state'],
            'zip_code' => $_POST['zip_code'],
            'latitude' => $_POST['latitude'] ?: null,
            'longitude' => $_POST['longitude'] ?: null,
            'is_available' => isset($_POST['is_available']) ? 1 : 0,
            'is_verified' => 0
        ];
        
        try {
            $property_id = $property_model->create($property_data);
            $message = 'Property added successfully! It will be reviewed before going live.';
            $message_type = 'success';
        } catch (Exception $e) {
            $message = 'Error adding property: ' . $e->getMessage();
            $message_type = 'danger';
        }
    } elseif ($action === 'update_availability') {
        $property_id = $_POST['property_id'];
        $is_available = $_POST['is_available'] ? 1 : 0;
        
        try {
            $property_model->updateAvailability($property_id, $is_available);
            $message = 'Property availability updated successfully!';
            $message_type = 'success';
        } catch (Exception $e) {
            $message = 'Error updating property: ' . $e->getMessage();
            $message_type = 'danger';
        }
    } elseif ($action === 'delete_property') {
        $property_id = $_POST['property_id'];
        
        try {
            $property_model->delete($property_id);
            $message = 'Property deleted successfully!';
            $message_type = 'success';
        } catch (Exception $e) {
            $message = 'Error deleting property: ' . $e->getMessage();
            $message_type = 'danger';
        }
    }
}

// Get properties for this owner
$page = $_GET['page'] ?? 1;
$search = $_GET['search'] ?? '';
$filter_type = $_GET['filter_type'] ?? '';
$filter_status = $_GET['filter_status'] ?? '';

$properties = $property_model->getByOwner($user_id, $page, 20, $search, $filter_type, $filter_status);
$total_properties = $property_model->getCountByOwner($user_id, $search, $filter_type, $filter_status);
$total_pages = ceil($total_properties / 20);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Properties - Property Owner Dashboard</title>
    
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
        
        .property-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: transform 0.3s ease;
        }
        
        .property-card:hover {
            transform: translateY(-2px);
        }
        
        .property-image {
            height: 200px;
            background: var(--booking-gray-200);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--booking-gray-500);
        }
        
        .property-content {
            padding: 1.5rem;
        }
        
        .property-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--booking-gray-900);
            margin: 0 0 0.5rem 0;
        }
        
        .property-location {
            color: var(--booking-gray-600);
            font-size: 0.875rem;
            margin-bottom: 1rem;
        }
        
        .property-details {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .property-detail {
            display: flex;
            align-items: center;
            color: var(--booking-gray-600);
            font-size: 0.875rem;
        }
        
        .property-detail i {
            margin-right: 0.25rem;
            color: var(--booking-primary);
        }
        
        .property-price {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--booking-primary);
            margin-bottom: 1rem;
        }
        
        .property-status {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1rem;
        }
        
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .status-available {
            background: var(--booking-success-light);
            color: var(--booking-success);
        }
        
        .status-unavailable {
            background: var(--booking-danger-light);
            color: var(--booking-danger);
        }
        
        .status-pending {
            background: var(--booking-warning-light);
            color: var(--booking-warning);
        }
        
        .property-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .property-actions .btn {
            flex: 1;
        }
        
        .filter-section {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .add-property-btn {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: var(--booking-primary);
            color: white;
            border: none;
            box-shadow: 0 4px 20px rgba(0, 113, 194, 0.3);
            font-size: 1.5rem;
            z-index: 1000;
        }
        
        .add-property-btn:hover {
            background: var(--booking-primary-dark);
            transform: scale(1.1);
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
                <a href="properties.php" class="owner-nav-link active">
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
                    <h1 class="h3 mb-0">My Properties</h1>
                    <p class="text-muted mb-0">Manage your property listings</p>
                </div>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPropertyModal">
                    <i class="fas fa-plus me-2"></i>
                    Add Property
                </button>
            </div>
        </div>
        
        <!-- Message -->
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <!-- Filters -->
        <div class="filter-section">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label for="search" class="form-label">Search Properties</label>
                    <input type="text" class="form-control" id="search" name="search" 
                           value="<?php echo htmlspecialchars($search); ?>" 
                           placeholder="Search by title, city...">
                </div>
                <div class="col-md-3">
                    <label for="filter_type" class="form-label">Property Type</label>
                    <select class="form-select" id="filter_type" name="filter_type">
                        <option value="">All Types</option>
                        <?php foreach ($property_types as $type): ?>
                            <option value="<?php echo htmlspecialchars($type['type_key']); ?>" 
                                    <?php echo $filter_type === $type['type_key'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($type['type_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="filter_status" class="form-label">Status</label>
                    <select class="form-select" id="filter_status" name="filter_status">
                        <option value="">All Status</option>
                        <option value="available" <?php echo $filter_status === 'available' ? 'selected' : ''; ?>>Available</option>
                        <option value="unavailable" <?php echo $filter_status === 'unavailable' ? 'selected' : ''; ?>>Unavailable</option>
                        <option value="pending" <?php echo $filter_status === 'pending' ? 'selected' : ''; ?>>Pending Verification</option>
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
        
        <!-- Properties Grid -->
        <?php if (empty($properties)): ?>
            <div class="text-center py-5">
                <i class="fas fa-home fa-4x text-muted mb-4"></i>
                <h3 class="text-muted">No Properties Found</h3>
                <p class="text-muted">Start by adding your first property to get started.</p>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPropertyModal">
                    <i class="fas fa-plus me-2"></i>
                    Add Your First Property
                </button>
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($properties as $property): ?>
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="property-card">
                            <div class="property-image">
                                <i class="fas fa-home fa-3x"></i>
                            </div>
                            <div class="property-content">
                                <h5 class="property-title"><?php echo htmlspecialchars($property['title']); ?></h5>
                                <p class="property-location">
                                    <i class="fas fa-map-marker-alt me-1"></i>
                                    <?php echo htmlspecialchars($property['city'] . ', ' . $property['state']); ?>
                                </p>
                                
                                <div class="property-details">
                                    <?php if ($property['bedrooms']): ?>
                                        <div class="property-detail">
                                            <i class="fas fa-bed"></i>
                                            <?php echo $property['bedrooms']; ?> bed
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($property['bathrooms']): ?>
                                        <div class="property-detail">
                                            <i class="fas fa-bath"></i>
                                            <?php echo $property['bathrooms']; ?> bath
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($property['area_sqft']): ?>
                                        <div class="property-detail">
                                            <i class="fas fa-ruler-combined"></i>
                                            <?php echo number_format($property['area_sqft']); ?> sqft
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="property-price">LKR <?php echo number_format($property['monthly_rent']); ?>/mo</div>
                                
                                <div class="property-status">
                                    <?php if (!$property['is_verified']): ?>
                                        <span class="status-badge status-pending">Pending Verification</span>
                                    <?php elseif (!$property['is_approved']): ?>
                                        <span class="status-badge status-pending">Pending Admin Approval</span>
                                    <?php else: ?>
                                        <span class="status-badge status-<?php echo $property['is_available'] ? 'available' : 'unavailable'; ?>">
                                            <?php echo $property['is_available'] ? 'Available' : 'Unavailable'; ?>
                                        </span>
                                    <?php endif; ?>
                                    <small class="text-muted">
                                        <?php echo ucfirst($property['property_type']); ?>
                                    </small>
                                </div>
                                
                                <div class="property-actions">
                                    <button class="btn btn-outline-primary btn-sm" 
                                            onclick="editProperty(<?php echo $property['id']; ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-outline-<?php echo $property['is_available'] ? 'warning' : 'success'; ?> btn-sm"
                                            onclick="toggleAvailability(<?php echo $property['id']; ?>, <?php echo $property['is_available'] ? 'false' : 'true'; ?>)">
                                        <i class="fas fa-<?php echo $property['is_available'] ? 'pause' : 'play'; ?>"></i>
                                    </button>
                                    <button class="btn btn-outline-danger btn-sm" 
                                            onclick="deleteProperty(<?php echo $property['id']; ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <nav aria-label="Properties pagination">
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&filter_type=<?php echo urlencode($filter_type); ?>&filter_status=<?php echo urlencode($filter_status); ?>">Previous</a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&filter_type=<?php echo urlencode($filter_type); ?>&filter_status=<?php echo urlencode($filter_status); ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&filter_type=<?php echo urlencode($filter_type); ?>&filter_status=<?php echo urlencode($filter_status); ?>">Next</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    
    <!-- Add Property Modal -->
    <div class="modal fade" id="addPropertyModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Property</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="add_property">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label for="title" class="form-label">Property Title *</label>
                                    <input type="text" class="form-control" id="title" name="title" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="property_type" class="form-label">Property Type *</label>
                                    <select class="form-select" id="property_type" name="property_type" required>
                                        <option value="">Select Type</option>
                                        <?php foreach ($property_types as $type): ?>
                                            <option value="<?php echo htmlspecialchars($type['type_key']); ?>">
                                                <?php echo htmlspecialchars($type['type_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description *</label>
                            <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="bedrooms" class="form-label">Bedrooms</label>
                                    <input type="number" class="form-control" id="bedrooms" name="bedrooms" min="0">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="bathrooms" class="form-label">Bathrooms</label>
                                    <input type="number" class="form-control" id="bathrooms" name="bathrooms" min="0" step="0.5">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="area_sqft" class="form-label">Area (sqft)</label>
                                    <input type="number" class="form-control" id="area_sqft" name="area_sqft" min="0">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="monthly_rent" class="form-label">Monthly Rent (LKR) *</label>
                                    <input type="number" class="form-control" id="monthly_rent" name="monthly_rent" required min="0" step="0.01">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="security_deposit" class="form-label">Security Deposit (LKR)</label>
                                    <input type="number" class="form-control" id="security_deposit" name="security_deposit" min="0" step="0.01">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="address" class="form-label">Address *</label>
                            <textarea class="form-control" id="address" name="address" rows="2" required></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="city" class="form-label">City *</label>
                                    <input type="text" class="form-control" id="city" name="city" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="state" class="form-label">State/Province *</label>
                                    <input type="text" class="form-control" id="state" name="state" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="zip_code" class="form-label">ZIP Code *</label>
                                    <input type="text" class="form-control" id="zip_code" name="zip_code" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="latitude" class="form-label">Latitude</label>
                                    <input type="number" class="form-control" id="latitude" name="latitude" step="any">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="longitude" class="form-label">Longitude</label>
                                    <input type="number" class="form-control" id="longitude" name="longitude" step="any">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="is_available" name="is_available" checked>
                            <label class="form-check-label" for="is_available">
                                Make this property available for booking
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Property</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Floating Add Button -->
    <button class="add-property-btn" data-bs-toggle="modal" data-bs-target="#addPropertyModal">
        <i class="fas fa-plus"></i>
    </button>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function toggleAvailability(propertyId, makeAvailable) {
            if (confirm('Are you sure you want to ' + (makeAvailable ? 'make available' : 'make unavailable') + ' this property?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="update_availability">
                    <input type="hidden" name="property_id" value="${propertyId}">
                    <input type="hidden" name="is_available" value="${makeAvailable ? '1' : '0'}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function deleteProperty(propertyId) {
            if (confirm('Are you sure you want to delete this property? This action cannot be undone.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_property">
                    <input type="hidden" name="property_id" value="${propertyId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function editProperty(propertyId) {
            // TODO: Implement edit property functionality
            alert('Edit property functionality will be implemented soon!');
        }
    </script>
</body>
</html>
