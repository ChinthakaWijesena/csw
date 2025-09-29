<?php

/**
 * Home Page - Renting Place Finder
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../backend/models/Property.php';
require_once __DIR__ . '/../backend/models/PropertyType.php';
require_once __DIR__ . '/../backend/models/Province.php';
require_once __DIR__ . '/../backend/models/District.php';
require_once __DIR__ . '/../backend/models/City.php';

// Get featured properties
$property_model = new Property();
$featured_properties = $property_model->search([], 1, 6);

// Get property statistics
$stats = $property_model->getStats();

// Get property types for search form
$property_type_model = new PropertyType();
$property_types = $property_type_model->getAllActive();

// Get location data for search form
$province_model = new Province();
$provinces = $province_model->getAllActive();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Find Your Perfect Rental</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Main JavaScript (Merged) -->
    <script src="js/main.js"></script>

    <!-- Note: External AJAX controller is loaded above. 
         The inline scripts below provide additional functionality. -->

    <!-- Enhanced AJAX Styles -->
    <style>
        /* Enhanced AJAX Loading States */
        .form-select.loading {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12' width='12' height='12' fill='none' stroke='%23666'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath d='m5.8 3.6-.4.4'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 0.75rem center;
            background-size: 16px 12px;
            opacity: 0.7;
        }

        .form-select.is-valid {
            border-color: #198754;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 8 8'%3e%3cpath fill='%23198754' d='m2.3 6.73.94-.94 1.89 1.89 3.78-3.78.94.94-4.72 4.72z'/%3e%3c/svg%3e");
        }

        .form-select.is-invalid {
            border-color: #dc3545;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12' width='12' height='12' fill='none' stroke='%23dc3545'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath d='m5.8 3.6-.4.4'/%3e%3c/svg%3e");
        }

        /* Search History Dropdown */
        .dropdown-menu {
            border: none;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            border-radius: 10px;
            padding: 10px 0;
        }

        .dropdown-item {
            padding: 10px 20px;
            transition: all 0.2s ease;
        }

        .dropdown-item:hover {
            background-color: #f8f9fa;
            transform: translateX(5px);
        }

        .dropdown-item.text-danger:hover {
            background-color: #f8d7da;
        }

        /* Enhanced Button States */
        .btn.loading {
            position: relative;
            color: transparent;
        }

        .btn.loading::after {
            content: '';
            position: absolute;
            width: 16px;
            height: 16px;
            top: 50%;
            left: 50%;
            margin-left: -8px;
            margin-top: -8px;
            border: 2px solid transparent;
            border-top-color: #ffffff;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {

            .form-select,
            .btn {
                font-size: 0.9rem;
            }
        }

        /* Scrolling Animations */
        .fade-in {
            opacity: 0;
            transform: translateY(30px);
            transition: all 0.8s ease-out;
        }

        .fade-in.visible {
            opacity: 1;
            transform: translateY(0);
        }

        .slide-in-left {
            opacity: 0;
            transform: translateX(-50px);
            transition: all 0.8s ease-out;
        }

        .slide-in-left.visible {
            opacity: 1;
            transform: translateX(0);
        }

        .slide-in-right {
            opacity: 0;
            transform: translateX(50px);
            transition: all 0.8s ease-out;
        }

        .slide-in-right.visible {
            opacity: 1;
            transform: translateX(0);
        }

        .scale-in {
            opacity: 0;
            transform: scale(0.8);
            transition: all 0.8s ease-out;
        }

        .scale-in.visible {
            opacity: 1;
            transform: scale(1);
        }

        .bounce-in {
            opacity: 0;
            transform: translateY(50px) scale(0.8);
            transition: all 0.8s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        }

        .bounce-in.visible {
            opacity: 1;
            transform: translateY(0) scale(1);
        }

        .rotate-in {
            opacity: 0;
            transform: rotate(-10deg) scale(0.8);
            transition: all 0.8s ease-out;
        }

        .rotate-in.visible {
            opacity: 1;
            transform: rotate(0deg) scale(1);
        }

        /* Staggered animations */
        .stagger-1 { transition-delay: 0.1s; }
        .stagger-2 { transition-delay: 0.2s; }
        .stagger-3 { transition-delay: 0.3s; }
        .stagger-4 { transition-delay: 0.4s; }
        .stagger-5 { transition-delay: 0.5s; }
        .stagger-6 { transition-delay: 0.6s; }

        /* Parallax effect */
        .parallax {
            transform: translateZ(0);
            will-change: transform;
        }

        /* Smooth scroll behavior */
        html {
            scroll-behavior: smooth;
        }

        /* Loading animation for sections */
        .section-loading {
            position: relative;
            overflow: hidden;
        }

        .section-loading::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            animation: loading-shimmer 2s infinite;
        }

        @keyframes loading-shimmer {
            0% { left: -100%; }
            100% { left: 100%; }
        }

     
    </style>
</head>

<body>
    <?php include 'includes/navbar.php'; ?>

    <!-- Hero Section -->
    <section class="bg-primary text-white py-2 py-md-3 py-lg-4">
        <div class="container">
            <div class="row justify-content-center text-center">
                <div class="col-12 col-xl-10 col-lg-11">

                    <!-- Responsive Title -->
                    <h1 class="display-6 display-md-5 display-lg-4 display-xl-3 fw-bold mb-2 mb-md-3 mb-lg-4 fade-in stagger-1">
                        Find Your Perfect Rental Home
                    </h1>

                    <!-- Responsive Subtitle -->
                    <p class="lead fs-6 fs-md-5 fs-lg-5 mb-3 mb-md-4 mb-lg-4 px-2 px-md-0 fade-in stagger-2">
                        Connect with verified property owners and discover your next home with our secure, transparent rental platform.
                    </p>

                    <!-- Search Bar -->
                    <div class="card shadow-lg mx-2 mx-md-0 bounce-in stagger-3">
                        <div class="card-body p-2 p-md-3 p-lg-4">
                            <form id="searchForm" class="row g-2 g-md-3 g-lg-4">

                                <!-- Province -->
                                <div class="col-12 col-sm-6 col-md-3 col-lg-2">
                                    <label for="province" class="form-label fw-semibold fs-6 mb-1 mb-md-2">Province</label>
                                    <select class="form-select form-select-sm form-select-md" id="province" name="province">
                                        <option value="">Select Province</option>
                                        <?php foreach ($provinces as $province): ?>
                                            <option value="<?php echo htmlspecialchars($province['id']); ?>">
                                                <?php echo htmlspecialchars($province['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <!-- District -->
                                <div class="col-12 col-sm-6 col-md-3 col-lg-2">
                                    <label for="district" class="form-label fw-semibold fs-6 mb-1 mb-md-2">District</label>
                                    <select class="form-select form-select-sm form-select-md" id="district" name="district" disabled>
                                        <option value="">Select District</option>
                                    </select>
                                </div>

                                <!-- City -->
                                <div class="col-12 col-sm-6 col-md-3 col-lg-2">
                                    <label for="city" class="form-label fw-semibold fs-6 mb-1 mb-md-2">City</label>
                                    <select class="form-select form-select-sm form-select-md" id="city" name="city" disabled>
                                        <option value="">Select City</option>
                                    </select>
                                </div>

                                <!-- Property Type -->
                                <div class="col-12 col-sm-6 col-md-3 col-lg-2">
                                    <label for="property_type" class="form-label fw-semibold fs-6 mb-1 mb-md-2">Property Type</label>
                                    <select class="form-select form-select-sm form-select-md" id="property_type" name="property_type">
                                        <option value="">Any Type</option>
                                        <?php foreach ($property_types as $type): ?>
                                            <option value="<?php echo htmlspecialchars($type['type_name']); ?>">
                                                <?php echo htmlspecialchars(ucfirst($type['type_name'])); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <!-- Search Button -->
                                <div class="col-12 col-sm-6 col-md-12 col-lg-4 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary btn-sm btn-md w-100 px-2 px-md-3 py-1 py-md-2 d-flex align-items-center justify-content-center">
                                        <i class="fas fa-search me-2"></i>
                                        <span class="d-none d-sm-inline">Search </span>
                                        <span class="d-inline d-sm-none">Search</span>
                                    </button>
                                </div>

                            </form>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </section>


    <!-- Search Results Section -->
    <section id="searchResults" class="py-5" style="display: none;">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="h3 mb-0">Search Results</h2>
                <button type="button" class="btn btn-outline-secondary" onclick="clearSearch()">
                    <i class="fas fa-times me-1"></i>Reset
                </button>
            </div>
            <div id="searchResultsContent">
                <!-- Search results will be loaded here -->
            </div>
        </div>
    </section>

    <!-- Statistics -->
    <section class="py-5 bg-light">
        <div class="container">
            <div class="row g-4">
                <div class="col-md-3 col-sm-6">
                    <div class="card text-center h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <div class="text-primary mb-3">
                                <i class="fas fa-home fa-3x"></i>
                            </div>
                            <h3 class="card-title text-primary"><?php echo number_format($stats['total']); ?></h3>
                            <p class="card-text text-muted">Total Properties</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="card text-center h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <div class="text-success mb-3">
                                <i class="fas fa-check-circle fa-3x"></i>
                            </div>
                            <h3 class="card-title text-success"><?php echo number_format($stats['verified']); ?></h3>
                            <p class="card-text text-muted">Verified Properties</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="card text-center h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <div class="text-info mb-3">
                                <i class="fas fa-users fa-3x"></i>
                            </div>
                            <h3 class="card-title text-info"><?php echo number_format($stats['available']); ?></h3>
                            <p class="card-text text-muted">Available Now</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="card text-center h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <div class="text-warning mb-3">
                                <i class="fas fa-dollar-sign fa-3x"></i>
                            </div>
                            <h3 class="card-title text-warning">LKR <?php echo number_format($stats['avg_rent'], 0); ?></h3>
                            <p class="card-text text-muted">Average Rent</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Properties -->
    <section class="py-5">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-5 fw-bold">Featured Properties</h2>
                <p class="lead text-muted">Discover amazing rental properties in your area</p>
            </div>

            <?php if (empty($featured_properties)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-home fa-5x text-muted mb-4"></i>
                    <h3 class="text-muted">No properties available</h3>
                    <p class="text-muted">Check back soon for new listings!</p>
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($featured_properties as $property): ?>
                        <div class="col-lg-4 col-md-6">
                            <div class="card h-100 shadow-sm">
                                <div class="position-relative">
                                    <?php if (!empty($property['primary_image'])): ?>
                                        <img src="<?php echo htmlspecialchars($property['primary_image']); ?>"
                                            class="card-img-top"
                                            alt="<?php echo htmlspecialchars($property['title']); ?>"
                                            style="height: 250px; object-fit: cover;"
                                            onerror="this.src='images/placeholder-property.svg'">
                                    <?php else: ?>
                                        <img src="images/placeholder-property.svg"
                                            class="card-img-top"
                                            alt="<?php echo htmlspecialchars($property['title']); ?>"
                                            style="height: 250px; object-fit: cover;">
                                    <?php endif; ?>
                                    <span class="badge bg-primary position-absolute top-0 end-0 m-3">
                                        <?php echo ucfirst($property['property_type']); ?>
                                    </span>
                                </div>
                                <div class="card-body d-flex flex-column">
                                    <h5 class="card-title"><?php echo htmlspecialchars($property['title']); ?></h5>
                                    <p class="card-text text-muted">
                                        <i class="fas fa-map-marker-alt me-1"></i>
                                        <?php echo htmlspecialchars($property['city'] . ', ' . $property['state']); ?>
                                    </p>
                                    <div class="d-flex flex-wrap gap-2 mb-3">
                                        <?php if ($property['bedrooms']): ?>
                                            <span class="badge bg-light text-dark">
                                                <i class="fas fa-bed me-1"></i><?php echo $property['bedrooms']; ?> bed
                                            </span>
                                        <?php endif; ?>
                                        <?php if ($property['bathrooms']): ?>
                                            <span class="badge bg-light text-dark">
                                                <i class="fas fa-bath me-1"></i><?php echo $property['bathrooms']; ?> bath
                                            </span>
                                        <?php endif; ?>
                                        <?php if ($property['area_sqft']): ?>
                                            <span class="badge bg-light text-dark">
                                                <i class="fas fa-ruler-combined me-1"></i><?php echo number_format($property['area_sqft']); ?> sqft
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div>
                                            <span class="h4 text-primary">LKR <?php echo number_format($property['monthly_rent']); ?></span>
                                            <small class="text-muted">/month</small>
                                        </div>
                                        <div class="text-end">
                                            <div class="d-flex align-items-center">
                                                <span class="badge bg-warning text-dark me-1">4.5</span>
                                                <small class="text-muted">Excellent</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mt-auto">
                                        <div class="d-grid gap-2">
                                            <a href="property-details.php?id=<?php echo $property['id']; ?>" class="btn btn-primary">
                                                <i class="fas fa-eye me-1"></i>View Details
                                            </a>
                                            <a href="subscription.php?property_id=<?php echo $property['id']; ?>" class="btn btn-success">
                                                <i class="fas fa-shopping-cart me-1"></i>Subscribe Now
                                            </a>
                                            <button type="button" class="btn btn-outline-danger"
                                                onclick="toggleWishlist(<?php echo $property['id']; ?>)"
                                                data-property-id="<?php echo $property['id']; ?>">
                                                <i class="far fa-heart me-1"></i>Add to Wishlist
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="text-center mt-5">
                    <a href="search.php" class="btn btn-primary btn-lg">
                        View All Properties
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-5 bg-light">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-5 fw-bold fade-in">Why Choose Our Platform?</h2>
                <p class="lead text-muted fade-in stagger-1">Discover the benefits that make us the preferred choice for property rentals</p>
            </div>

            <div class="row g-4">
                <div class="col-lg-4 col-md-6">
                    <div class="card h-100 border-0 shadow-sm slide-in-left stagger-1 float-animation">
                        <div class="card-body text-center p-4">
                            <div class="text-primary mb-3">
                                <i class="fas fa-shield-alt fa-3x"></i>
                            </div>
                            <h5 class="card-title">Verified Properties</h5>
                            <p class="card-text text-muted">All properties are verified by our team to ensure quality and authenticity.</p>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6">
                    <div class="card h-100 border-0 shadow-sm fade-in stagger-2 float-animation">
                        <div class="card-body text-center p-4">
                            <div class="text-success mb-3">
                                <i class="fas fa-mobile-alt fa-3x"></i>
                            </div>
                            <h5 class="card-title">SMS Authentication</h5>
                            <p class="card-text text-muted">Secure login with SMS OTP verification for enhanced security.</p>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6">
                    <div class="card h-100 border-0 shadow-sm slide-in-right stagger-3 float-animation">
                        <div class="card-body text-center p-4">
                            <div class="text-info mb-3">
                                <i class="fas fa-credit-card fa-3x"></i>
                            </div>
                            <h5 class="card-title">Secure Payments</h5>
                            <p class="card-text text-muted">Safe and secure payment processing with guaranteed payouts to owners.</p>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <div class="text-warning mb-3">
                                <i class="fas fa-calendar-check fa-3x"></i>
                            </div>
                            <h5 class="card-title">Easy Booking</h5>
                            <p class="card-text text-muted">Schedule property visits and manage bookings seamlessly.</p>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <div class="text-danger mb-3">
                                <i class="fas fa-headset fa-3x"></i>
                            </div>
                            <h5 class="card-title">24/7 Support</h5>
                            <p class="card-text text-muted">Round-the-clock customer support to help you with any queries.</p>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <div class="text-secondary mb-3">
                                <i class="fas fa-chart-line fa-3x"></i>
                            </div>
                            <h5 class="card-title">Transparent Reports</h5>
                            <p class="card-text text-muted">Detailed reports and analytics for owners and customers.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>


    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>


    <!-- Include Footer -->
    <?php include 'includes/footer.php'; ?>
</body>

</html>