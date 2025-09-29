<?php
/**
 * Property Search Page
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../backend/models/Property.php';
require_once __DIR__ . '/../backend/models/PropertyType.php';
require_once __DIR__ . '/../backend/models/Province.php';
require_once __DIR__ . '/../backend/models/District.php';
require_once __DIR__ . '/../backend/models/City.php';

// Get search parameters
$filters = [
    'province' => sanitize_input($_GET['province'] ?? ''),
    'district' => sanitize_input($_GET['district'] ?? ''),
    'city' => sanitize_input($_GET['city'] ?? ''),
    'state' => sanitize_input($_GET['state'] ?? ''),
    'property_type' => sanitize_input($_GET['property_type'] ?? ''),
    'min_price' => sanitize_input($_GET['min_price'] ?? ''),
    'max_price' => sanitize_input($_GET['max_price'] ?? ''),
    'bedrooms' => sanitize_input($_GET['bedrooms'] ?? ''),
    'bathrooms' => sanitize_input($_GET['bathrooms'] ?? '')
];

// Remove empty filters
$filters = array_filter($filters, function($value) {
    return $value !== '';
});

// Pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 12;

// Initialize error tracking
$database_errors = [];
$properties = [];
$property_types = [];
$provinces = [];

// Search properties
try {
    $property_model = new Property();
    $properties = $property_model->search($filters, $page, $limit);
} catch (Exception $e) {
    $database_errors[] = "Error searching properties: " . $e->getMessage();
    error_log("Property search error: " . $e->getMessage());
}

// Get property types for search form
try {
    $property_type_model = new PropertyType();
    $property_types = $property_type_model->getAllActive();
} catch (Exception $e) {
    $database_errors[] = "Error loading property types: " . $e->getMessage();
    error_log("Property type loading error: " . $e->getMessage());
}

// Get location data for search form
try {
    $province_model = new Province();
    $provinces = $province_model->getAllActive();
} catch (Exception $e) {
    $database_errors[] = "Error loading provinces: " . $e->getMessage();
    error_log("Province loading error: " . $e->getMessage());
}

// Load districts if province is selected
$districts = [];
$cities = [];
$province_name = '';
$district_name = '';
$city_name = '';

if (!empty($filters['province'])) {
    // Get province name for searching
    try {
        $province = $province_model->getById($filters['province']);
        if ($province) {
            $province_name = $province['name'];
            $filters['state'] = $province_name; // Use state field for province search
        }
    } catch (Exception $e) {
        $database_errors[] = "Error loading province details: " . $e->getMessage();
        error_log("Province details error: " . $e->getMessage());
    }
    
    try {
        $district_model = new District();
        $districts = $district_model->getByProvince($filters['province']);
    } catch (Exception $e) {
        $database_errors[] = "Error loading districts: " . $e->getMessage();
        error_log("District loading error: " . $e->getMessage());
        $districts = [];
    }
    
    // Load cities if district is selected
    if (!empty($filters['district'])) {
        try {
            $district = $district_model->getById($filters['district']);
            if ($district) {
                $district_name = $district['name'];
                $filters['city'] = $district_name; // Use city field for district search
            }
        } catch (Exception $e) {
            $database_errors[] = "Error loading district details: " . $e->getMessage();
            error_log("District details error: " . $e->getMessage());
        }
        
        try {
            $city_model = new City();
            $cities = $city_model->getByDistrict($filters['district']);
        } catch (Exception $e) {
            $database_errors[] = "Error loading cities: " . $e->getMessage();
            error_log("City loading error: " . $e->getMessage());
            $cities = [];
        }
    }
}

if (!empty($filters['city']) && is_numeric($filters['city'])) {
    try {
        $city_model = new City();
        $city = $city_model->getById($filters['city']);
        if ($city) {
            $city_name = $city['name'];
            $filters['city'] = $city_name; // Replace ID with name for searching
        }
    } catch (Exception $e) {
        $database_errors[] = "Error loading city details: " . $e->getMessage();
        error_log("City details error: " . $e->getMessage());
    }
}

// Get total count for pagination
$total_properties = 0;
$total_pages = 0;
try {
    $total_properties = $property_model->getSearchCount($filters);
    $total_pages = ceil($total_properties / $limit);
} catch (Exception $e) {
    $database_errors[] = "Error getting search count: " . $e->getMessage();
    error_log("Search count error: " . $e->getMessage());
}

// Get property statistics for filters
$stats = [];
try {
    $stats = $property_model->getStats();
} catch (Exception $e) {
    $database_errors[] = "Error loading property statistics: " . $e->getMessage();
    error_log("Property statistics error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Properties - <?php echo APP_NAME; ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <!-- Design Improvements -->
    <link href="css/design-improvements.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">
                <i class="fas fa-home me-2"></i><?php echo APP_NAME; ?>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="search.php">Search Properties</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="about.php">About</a>
                    </li>
                </ul>
                
                <ul class="navbar-nav">
                    <?php if (is_logged_in()): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user me-1"></i><?php echo $_SESSION['name']; ?>
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="dashboard.php">Dashboard</a></li>
                                <?php if ($_SESSION['user_type'] === 'owner'): ?>
                                    <li><a class="dropdown-item" href="my-properties.php">My Properties</a></li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php">Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="btn btn-outline-light ms-2" href="register.php">Register</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Search Section -->
    <section class="search-section py-5 mt-5 bg-light">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <h2 class="text-center mb-4">Find Your Perfect Rental</h2>
                </div>
            </div>
            
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <div class="card shadow-lg border-0">
                        <div class="card-body p-4">
                            <form method="GET" action="search.php" id="searchForm">
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <label for="province" class="form-label">Province</label>
                                        <select class="form-select" id="province" name="province">
                                            <option value="">Select Province</option>
                                            <?php foreach ($provinces as $province): ?>
                                                <option value="<?php echo htmlspecialchars($province['id']); ?>" 
                                                        <?php echo ($filters['province'] ?? '') == $province['id'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($province['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="district" class="form-label">District</label>
                                        <select class="form-select" id="district" name="district" <?php echo empty($districts) ? 'disabled' : ''; ?>>
                                            <option value="">Select District</option>
                                            <?php foreach ($districts as $district): ?>
                                                <option value="<?php echo htmlspecialchars($district['id']); ?>" 
                                                        <?php echo ($filters['district'] ?? '') == $district['id'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($district['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="city" class="form-label">City</label>
                                        <select class="form-select" id="city" name="city" <?php echo empty($cities) ? 'disabled' : ''; ?>>
                                            <option value="">Select City</option>
                                            <?php foreach ($cities as $city): ?>
                                                <option value="<?php echo htmlspecialchars($city['id']); ?>" 
                                                        <?php echo ($filters['city'] ?? '') == $city['id'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($city['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="property_type" class="form-label">Property Type</label>
                                        <select class="form-select" id="property_type" name="property_type">
                                            <option value="">Any Type</option>
                                            <?php foreach ($property_types as $type): ?>
                                                <option value="<?php echo htmlspecialchars($type['type_name']); ?>" 
                                                        <?php echo ($filters['property_type'] ?? '') === $type['type_name'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars(ucfirst($type['type_name'])); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="bedrooms" class="form-label">Bedrooms</label>
                                        <select class="form-select" id="bedrooms" name="bedrooms">
                                            <option value="">Any</option>
                                            <option value="1" <?php echo ($filters['bedrooms'] ?? '') === '1' ? 'selected' : ''; ?>>1+</option>
                                            <option value="2" <?php echo ($filters['bedrooms'] ?? '') === '2' ? 'selected' : ''; ?>>2+</option>
                                            <option value="3" <?php echo ($filters['bedrooms'] ?? '') === '3' ? 'selected' : ''; ?>>3+</option>
                                            <option value="4" <?php echo ($filters['bedrooms'] ?? '') === '4' ? 'selected' : ''; ?>>4+</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="min_price" class="form-label">Min Price (LKR)</label>
                                        <input type="number" class="form-control" id="min_price" name="min_price" 
                                               value="<?php echo htmlspecialchars($filters['min_price'] ?? ''); ?>" 
                                               placeholder="Min rent in LKR">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="max_price" class="form-label">Max Price (LKR)</label>
                                        <input type="number" class="form-control" id="max_price" name="max_price" 
                                               value="<?php echo htmlspecialchars($filters['max_price'] ?? ''); ?>" 
                                               placeholder="Max rent in LKR">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="bathrooms" class="form-label">Bathrooms</label>
                                        <select class="form-select" id="bathrooms" name="bathrooms">
                                            <option value="">Any</option>
                                            <option value="1" <?php echo ($filters['bathrooms'] ?? '') === '1' ? 'selected' : ''; ?>>1+</option>
                                            <option value="2" <?php echo ($filters['bathrooms'] ?? '') === '2' ? 'selected' : ''; ?>>2+</option>
                                            <option value="3" <?php echo ($filters['bathrooms'] ?? '') === '3' ? 'selected' : ''; ?>>3+</option>
                                        </select>
                                    </div>
                                    <div class="col-12 text-center">
                                        <button type="submit" class="btn btn-primary btn-lg px-5 col-3">
                                            <i class="fas fa-search me-2"></i>Search
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary btn-lg ms-2 col-3" onclick="clearFilters()">
                                            <i class="fas fa-times me-2"></i>Reset
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Results Section -->
    <section class="results-section py-5">
        <div class="container">
            <!-- Database Error Display -->
            <?php if (!empty($database_errors)): ?>
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="alert alert-danger" role="alert">
                            <h5 class="alert-heading">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                Database Connection Issues
                            </h5>
                            <p class="mb-2">We encountered some issues while loading the search data:</p>
                            <ul class="mb-0">
                                <?php foreach ($database_errors as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                            <hr>
                            <p class="mb-0">
                                <small>
                                    <i class="fas fa-info-circle me-1"></i>
                                    Some features may not be available. Please try refreshing the page or contact support if the issue persists.
                                </small>
                            </p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="row">
                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h3>
                            <?php if (!empty($filters)): ?>
                                Search Results
                                <small class="text-muted">(<?php echo number_format($total_properties); ?>)</small>
                            <?php else: ?>
                                All Properties
                                <small class="text-muted">(<?php echo number_format($total_properties); ?> properties available)</small>
                            <?php endif; ?>
                        </h3>
                        
                        <div class="d-flex gap-2">
                            <select class="form-select" id="sortBy" onchange="sortProperties()">
                                <option value="newest">Newest First</option>
                                <option value="price_low">Price: Low to High</option>
                                <option value="price_high">Price: High to Low</option>
                                <option value="area_large">Area: Largest First</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php if (empty($properties)): ?>
                <div class="row">
                    <div class="col-12">
                        <div class="text-center py-5">
                            <?php if (!empty($database_errors)): ?>
                                <i class="fas fa-database fa-3x text-warning mb-3"></i>
                                <h4>Unable to Load Properties</h4>
                                <p class="text-muted">We're experiencing technical difficulties. Please try again later.</p>
                                <a href="search.php" class="btn btn-primary">Try Again</a>
                            <?php else: ?>
                                <i class="fas fa-search fa-3x text-muted mb-3"></i>
                                <h4>No Properties Found</h4>
                                <p class="text-muted">Try adjusting your search criteria or browse all properties.</p>
                                <a href="search.php" class="btn btn-primary">View All Properties</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="row" id="propertiesContainer">
                    <?php foreach ($properties as $property): ?>
                        <?php 
                        try {
                            // Validate property data
                            $property_id = $property['id'] ?? 0;
                            $property_title = $property['title'] ?? 'Untitled Property';
                            $property_type = $property['property_type'] ?? 'Property';
                            $property_city = $property['city'] ?? 'Unknown';
                            $property_state = $property['state'] ?? 'Unknown';
                            $property_description = $property['description'] ?? 'No description available';
                            $property_monthly_rent = $property['monthly_rent'] ?? 0;
                            $property_area_sqft = $property['area_sqft'] ?? 0;
                            $property_bedrooms = $property['bedrooms'] ?? 0;
                            $property_bathrooms = $property['bathrooms'] ?? 0;
                            $property_created_at = $property['created_at'] ?? 'now';
                        ?>
                            <div class="col-lg-4 col-md-6 mb-4 property-item" 
                                 data-price="<?php echo $property_monthly_rent; ?>"
                                 data-area="<?php echo $property_area_sqft; ?>"
                                 data-date="<?php echo strtotime($property_created_at); ?>">
                                <div class="card property-card h-100 shadow-sm">
                                    <div class="property-image">
                                        <?php if (!empty($property['primary_image'])): ?>
                                            <img src="<?php echo htmlspecialchars($property['primary_image']); ?>" 
                                                 class="card-img-top" 
                                                 alt="<?php echo htmlspecialchars($property_title); ?>"
                                                 style="height: 250px; object-fit: cover;"
                                                 onerror="this.src='images/placeholder-property.svg'">
                                        <?php else: ?>
                                            <img src="images/placeholder-property.svg" 
                                                 class="card-img-top" 
                                                 alt="<?php echo htmlspecialchars($property_title); ?>"
                                                 style="height: 250px; object-fit: cover;">
                                        <?php endif; ?>
                                        <div class="property-badge">
                                            <span class="badge bg-primary"><?php echo ucfirst($property_type); ?></span>
                                        </div>
                                    </div>
                                    <div class="card-body d-flex flex-column">
                                        <h5 class="card-title"><?php echo htmlspecialchars($property_title); ?></h5>
                                        <p class="card-text text-muted">
                                            <i class="fas fa-map-marker-alt me-1"></i>
                                            <?php echo htmlspecialchars($property_city . ', ' . $property_state); ?>
                                        </p>
                                        <p class="card-text">
                                            <?php echo htmlspecialchars(substr($property_description, 0, 100)) . '...'; ?>
                                        </p>
                                        <div class="property-details mt-auto">
                                            <div class="row text-center">
                                                <?php if ($property_bedrooms > 0): ?>
                                                    <div class="col-4">
                                                        <small class="text-muted">Bedrooms</small>
                                                        <div class="fw-bold"><?php echo $property_bedrooms; ?></div>
                                                    </div>
                                                <?php endif; ?>
                                                <?php if ($property_bathrooms > 0): ?>
                                                    <div class="col-4">
                                                        <small class="text-muted">Bathrooms</small>
                                                        <div class="fw-bold"><?php echo $property_bathrooms; ?></div>
                                                    </div>
                                                <?php endif; ?>
                                                <?php if ($property_area_sqft > 0): ?>
                                                    <div class="col-4">
                                                        <small class="text-muted">Area</small>
                                                        <div class="fw-bold"><?php echo number_format($property_area_sqft); ?> sqft</div>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="rent-price text-center mt-3">
                                                <h4 class="text-primary fw-bold">LKR <?php echo number_format($property_monthly_rent); ?>/month</h4>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-footer bg-transparent">
                                        <a href="property-details.php?id=<?php echo $property_id; ?>" class="btn btn-primary w-100">
                                            View Details
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php 
                        } catch (Exception $e) {
                            // Log the error and display a fallback card
                            error_log("Property display error: " . $e->getMessage());
                        ?>
                            <div class="col-lg-4 col-md-6 mb-4">
                                <div class="card h-100 shadow-sm border-warning">
                                    <div class="card-body text-center">
                                        <i class="fas fa-exclamation-triangle text-warning fa-2x mb-3"></i>
                                        <h6 class="card-title text-warning">Property Data Error</h6>
                                        <p class="card-text text-muted small">
                                            Unable to display this property due to data issues.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        <?php } ?>
                    <?php endforeach; ?>
                </div>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="row mt-5">
                        <div class="col-12">
                            <nav aria-label="Properties pagination">
                                <ul class="pagination justify-content-center">
                                    <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">
                                                <i class="fas fa-chevron-left"></i> Previous
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <?php if ($page < $total_pages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">
                                                Next <i class="fas fa-chevron-right"></i>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-white py-5">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-4">
                    <h5><?php echo APP_NAME; ?></h5>
                    <p class="text-muted">Connecting tenants with verified property owners for a seamless rental experience.</p>
                </div>
                <div class="col-lg-2 col-md-6 mb-4">
                    <h6>Quick Links</h6>
                    <ul class="list-unstyled">
                        <li><a href="index.php" class="text-muted">Home</a></li>
                        <li><a href="search.php" class="text-muted">Search</a></li>
                        <li><a href="about.php" class="text-muted">About</a></li>
                        <li><a href="contact.php" class="text-muted">Contact</a></li>
                    </ul>
                </div>
                <div class="col-lg-2 col-md-6 mb-4">
                    <h6>For Users</h6>
                    <ul class="list-unstyled">
                        <li><a href="register.php" class="text-muted">Register</a></li>
                        <li><a href="login.php" class="text-muted">Login</a></li>
                        <li><a href="help.php" class="text-muted">Help</a></li>
                        <li><a href="privacy.php" class="text-muted">Privacy</a></li>
                    </ul>
                </div>
                <div class="col-lg-4 mb-4">
                    <h6>Contact Info</h6>
                    <p class="text-muted">
                        <i class="fas fa-phone me-2"></i>+1 (555) 123-4567<br>
                        <i class="fas fa-envelope me-2"></i>info@rentingplace.com<br>
                        <i class="fas fa-map-marker-alt me-2"></i>123 Main St, City, State 12345
                    </p>
                </div>
            </div>
            <hr class="my-4">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="mb-0">&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?>. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <a href="#" class="text-muted me-3"><i class="fab fa-facebook"></i></a>
                    <a href="#" class="text-muted me-3"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="text-muted me-3"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="text-muted"><i class="fab fa-linkedin"></i></a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Location loading functionality
        function loadDistricts(provinceId) {
            const districtSelect = document.getElementById('district');
            const citySelect = document.getElementById('city');
            
            // Reset dependent dropdowns
            districtSelect.innerHTML = '<option value="">Select District</option>';
            citySelect.innerHTML = '<option value="">Select City</option>';
            citySelect.disabled = true;
            
            if (!provinceId) {
                districtSelect.disabled = true;
                return;
            }
            
            districtSelect.disabled = false;
            
            fetch('../api/location/get_locations.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'districts',
                    province_id: provinceId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    data.districts.forEach(district => {
                        const option = document.createElement('option');
                        option.value = district.id;
                        option.textContent = district.name;
                        districtSelect.appendChild(option);
                    });
                }
            })
            .catch(error => {
                console.error('Error loading districts:', error);
            });
        }
        
        function loadCities(districtId) {
            const citySelect = document.getElementById('city');
            
            // Reset city dropdown
            citySelect.innerHTML = '<option value="">Select City</option>';
            
            if (!districtId) {
                citySelect.disabled = true;
                return;
            }
            
            citySelect.disabled = false;
            
            fetch('../api/location/get_locations.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'cities',
                    district_id: districtId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    data.cities.forEach(city => {
                        const option = document.createElement('option');
                        option.value = city.id;
                        option.textContent = city.name;
                        citySelect.appendChild(option);
                    });
                }
            })
            .catch(error => {
                console.error('Error loading cities:', error);
            });
        }
        
        // Initialize location dropdowns
        document.addEventListener('DOMContentLoaded', function() {
            const provinceSelect = document.getElementById('province');
            const districtSelect = document.getElementById('district');
            
            if (provinceSelect) {
                provinceSelect.addEventListener('change', function() {
                    loadDistricts(this.value);
                });
            }
            
            if (districtSelect) {
                districtSelect.addEventListener('change', function() {
                    loadCities(this.value);
                });
            }
        });
        
        // Clear filters function
        function clearFilters() {
            document.getElementById('searchForm').reset();
            window.location.href = 'search.php';
        }
        
        // Sort properties function
        function sortProperties() {
            const sortBy = document.getElementById('sortBy').value;
            const container = document.getElementById('propertiesContainer');
            const properties = Array.from(container.children);
            
            properties.sort((a, b) => {
                switch (sortBy) {
                    case 'price_low':
                        return parseFloat(a.dataset.price) - parseFloat(b.dataset.price);
                    case 'price_high':
                        return parseFloat(b.dataset.price) - parseFloat(a.dataset.price);
                    case 'area_large':
                        return parseFloat(b.dataset.area) - parseFloat(a.dataset.area);
                    case 'newest':
                    default:
                        return parseFloat(b.dataset.date) - parseFloat(a.dataset.date);
                }
            });
            
            // Clear container and append sorted properties
            container.innerHTML = '';
            properties.forEach(property => container.appendChild(property));
        }
        
        // Auto-submit form on filter change (excluding location dropdowns)
        document.querySelectorAll('#searchForm select:not(#province):not(#district):not(#city), #searchForm input[type="number"]').forEach(element => {
            element.addEventListener('change', function() {
                // Add a small delay to prevent too many requests
                setTimeout(() => {
                    document.getElementById('searchForm').submit();
                }, 500);
            });
        });
    </script>
</body>
</html>
