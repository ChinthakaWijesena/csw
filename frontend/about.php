<?php

/**
 * About Page - Property Rental Platform
 * Uses only Bootstrap classes for styling
 */

require_once '../config/config.php';
require_once '../backend/models/User.php';
require_once '../backend/models/Property.php';

// Get statistics
$user_model = new User();
$property_model = new Property();

$property_stats = $property_model->getStats();
$user_stats = $user_model->getStats();

$stats = [
    'total_properties' => $property_stats['total'],
    'verified_properties' => $property_stats['verified'],
    'total_users' => $user_stats['total'],
    'verified_users' => $user_stats['verified']
];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - <?php echo APP_NAME; ?></title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>

<body class="d-flex flex-column min-vh-100">
    <!-- Include Navbar -->
    <?php include 'includes/navbar.php'; ?>

    <!-- Hero Section -->
    <section class="bg-primary text-white py-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <h1 class="display-4 fw-bold mb-4">About <?php echo APP_NAME; ?></h1>
                    <p class="lead mb-4">Connecting tenants with verified property owners for a seamless, secure, and transparent rental experience.</p>
                    <div class="d-flex flex-wrap gap-3">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-shield-alt me-2"></i>
                            <span>100% Verified Properties</span>
                        </div>
                        <div class="d-flex align-items-center">
                            <i class="fas fa-lock me-2"></i>
                            <span>Secure Payments</span>
                        </div>
                        <div class="d-flex align-items-center">
                            <i class="fas fa-headset me-2"></i>
                            <span>24/7 Support</span>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 text-center">
                    <i class="fas fa-home display-1 opacity-75"></i>
                </div>
            </div>
        </div>
    </section>

    <!-- Statistics Section -->
    <section class="py-5 bg-light">
        <div class="container">
            <div class="row text-center">
                <div class="col-md-3 mb-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <i class="fas fa-building text-primary display-4 mb-3"></i>
                            <h3 class="fw-bold text-primary"><?php echo number_format($stats['total_properties']); ?></h3>
                            <p class="text-muted mb-0">Total Properties</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <i class="fas fa-check-circle text-success display-4 mb-3"></i>
                            <h3 class="fw-bold text-success"><?php echo number_format($stats['verified_properties']); ?></h3>
                            <p class="text-muted mb-0">Verified Properties</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <i class="fas fa-users text-info display-4 mb-3"></i>
                            <h3 class="fw-bold text-info"><?php echo number_format($stats['total_users']); ?></h3>
                            <p class="text-muted mb-0">Registered Users</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <i class="fas fa-user-check text-warning display-4 mb-3"></i>
                            <h3 class="fw-bold text-warning"><?php echo number_format($stats['verified_users']); ?></h3>
                            <p class="text-muted mb-0">Verified Users</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Mission & Vision Section -->
    <section class="py-5">
        <div class="container">
            <div class="row">
                <div class="col-lg-6 mb-5">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body p-5">
                            <div class="text-center mb-4">
                                <i class="fas fa-bullseye text-primary display-4"></i>
                            </div>
                            <h3 class="card-title text-center mb-4">Our Mission</h3>
                            <p class="card-text text-muted text-center">
                                To revolutionize the property rental market by providing a secure, transparent, and user-friendly platform that connects tenants with verified property owners, ensuring a seamless rental experience for all parties involved.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 mb-5">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body p-5">
                            <div class="text-center mb-4">
                                <i class="fas fa-eye text-success display-4"></i>
                            </div>
                            <h3 class="card-title text-center mb-4">Our Vision</h3>
                            <p class="card-text text-muted text-center">
                                To become the leading property rental platform in Sri Lanka, known for our commitment to quality, security, and customer satisfaction, while fostering a community of trusted property owners and satisfied tenants.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-5 bg-light">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-5 fw-bold mb-3">Why Choose Our Platform?</h2>
                <p class="lead text-muted">Discover the benefits that make us the preferred choice for property rentals</p>
            </div>

            <div class="row g-4">
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <i class="fas fa-shield-alt text-primary display-4 mb-3"></i>
                            <h5 class="card-title">Verified Properties</h5>
                            <p class="card-text text-muted">All properties are thoroughly verified by our team to ensure quality, authenticity, and compliance with safety standards.</p>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <i class="fas fa-lock text-success display-4 mb-3"></i>
                            <h5 class="card-title">Secure Payments</h5>
                            <p class="card-text text-muted">Your payments are processed through secure, encrypted channels with multiple payment options for your convenience.</p>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <i class="fas fa-search text-info display-4 mb-3"></i>
                            <h5 class="card-title">Advanced Search</h5>
                            <p class="card-text text-muted">Find your perfect home with our powerful search filters including location, price range, and property features.</p>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <i class="fas fa-headset text-warning display-4 mb-3"></i>
                            <h5 class="card-title">24/7 Support</h5>
                            <p class="card-text text-muted">Our dedicated support team is available around the clock to assist you with any questions or concerns.</p>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <i class="fas fa-mobile-alt text-danger display-4 mb-3"></i>
                            <h5 class="card-title">Mobile Friendly</h5>
                            <p class="card-text text-muted">Access our platform from any device with our responsive design that works perfectly on mobile, tablet, and desktop.</p>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <i class="fas fa-star text-primary display-4 mb-3"></i>
                            <h5 class="card-title">Quality Assurance</h5>
                            <p class="card-text text-muted">We maintain high standards through regular property inspections and user feedback to ensure quality service.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Team Section -->
    <section class="py-5">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-5 fw-bold mb-3">Meet Our Team</h2>
                <p class="lead text-muted">The dedicated professionals behind our platform</p>
            </div>

            <div class="row g-4">
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <div class="mb-3">
                                <i class="fas fa-user-circle text-primary display-1"></i>
                            </div>
                            <h5 class="card-title">Chinthaka Sandaruwan</h5>
                            <p class="text-muted mb-2">Founder & CEO</p>
                            <p class="card-text small text-muted">Visionary leader with over 10 years of experience in real estate and technology, passionate about revolutionizing the rental market.</p>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <div class="mb-3">
                                <i class="fas fa-user-circle text-success display-1"></i>
                            </div>
                            <h5 class="card-title">Development Team</h5>
                            <p class="text-muted mb-2">Technical Excellence</p>
                            <p class="card-text small text-muted">Our skilled developers work tirelessly to ensure the platform is secure, fast, and user-friendly with cutting-edge technology.</p>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <div class="mb-3">
                                <i class="fas fa-user-circle text-info display-1"></i>
                            </div>
                            <h5 class="card-title">Support Team</h5>
                            <p class="text-muted mb-2">Customer Success</p>
                            <p class="card-text small text-muted">Dedicated support professionals committed to providing exceptional customer service and resolving any issues promptly.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section class="py-5 bg-primary text-white">
        <div class="container">
            <div class="row">
                <div class="col-lg-8">
                    <h2 class="display-6 fw-bold mb-4">Get in Touch</h2>
                    <p class="lead mb-4">Have questions or need assistance? We're here to help!</p>

                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-phone me-3 fs-4"></i>
                                <div>
                                    <h6 class="mb-1">Phone</h6>
                                    <p class="mb-0">077 123 4567</p>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-envelope me-3 fs-4"></i>
                                <div>
                                    <h6 class="mb-1">Email</h6>
                                    <p class="mb-0">info@<?php echo strtolower(str_replace(' ', '', APP_NAME)); ?>.com</p>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-map-marker-alt me-3 fs-4"></i>
                                <div>
                                    <h6 class="mb-1">Address</h6>
                                    <p class="mb-0">Colombo, Sri Lanka</p>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-clock me-3 fs-4"></i>
                                <div>
                                    <h6 class="mb-1">Business Hours</h6>
                                    <p class="mb-0">24/7 Online Support</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 text-center">
                    <i class="fas fa-comments display-1 opacity-75"></i>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-white py-5">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-4">
                    <h5 class="fw-bold mb-3"><?php echo APP_NAME; ?></h5>
                    <p class="text-muted">Connecting tenants with verified property owners for a seamless rental experience.</p>
                    <div class="d-flex gap-3">
                        <a href="#" class="text-white"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="text-white"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-white"><i class="fab fa-linkedin-in"></i></a>
                        <a href="#" class="text-white"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>

                <div class="col-lg-2 col-md-6">
                    <h6 class="fw-bold mb-3">Quick Links</h6>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="index.php" class="text-muted text-decoration-none">Home</a></li>
                        <li class="mb-2"><a href="search.php" class="text-muted text-decoration-none">Search Properties</a></li>
                        <li class="mb-2"><a href="about.php" class="text-muted text-decoration-none">About</a></li>
                        <li class="mb-2"><a href="contact.php" class="text-muted text-decoration-none">Contact</a></li>
                    </ul>
                </div>

                <div class="col-lg-2 col-md-6">
                    <h6 class="fw-bold mb-3">For Users</h6>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="register.php" class="text-muted text-decoration-none">Register</a></li>
                        <li class="mb-2"><a href="login.php" class="text-muted text-decoration-none">Login</a></li>
                        <li class="mb-2"><a href="wishlist.php" class="text-muted text-decoration-none">Wishlist</a></li>
                        <li class="mb-2"><a href="profile.php" class="text-muted text-decoration-none">Profile</a></li>
                    </ul>
                </div>

                <div class="col-lg-2 col-md-6">
                    <h6 class="fw-bold mb-3">For Owners</h6>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="owner/dashboard/" class="text-muted text-decoration-none">Dashboard</a></li>
                        <li class="mb-2"><a href="add-property.php" class="text-muted text-decoration-none">Add Property</a></li>
                        <li class="mb-2"><a href="owner/properties.php" class="text-muted text-decoration-none">My Properties</a></li>
                        <li class="mb-2"><a href="owner/bought.php" class="text-muted text-decoration-none">Bookings</a></li>
                    </ul>
                </div>

                <div class="col-lg-2 col-md-6">
                    <h6 class="fw-bold mb-3">Support</h6>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="#" class="text-muted text-decoration-none">Help Center</a></li>
                        <li class="mb-2"><a href="#" class="text-muted text-decoration-none">Privacy Policy</a></li>
                        <li class="mb-2"><a href="#" class="text-muted text-decoration-none">Terms of Service</a></li>
                        <li class="mb-2"><a href="#" class="text-muted text-decoration-none">FAQ</a></li>
                    </ul>
                </div>
            </div>

            <hr class="my-4">

            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="text-muted mb-0">&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?>. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="text-muted mb-0">Made with <i class="fas fa-heart text-danger"></i> in Sri Lanka</p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Wishlist functionality -->
    <script>
        // Update wishlist count on page load
        function updateWishlistCount() {
            const wishlist = JSON.parse(localStorage.getItem('wishlist') || '[]');
            const countElement = document.getElementById('wishlist-count');
            if (countElement) {
                countElement.textContent = `(${wishlist.length})`;
            }
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            updateWishlistCount();
        });
    </script>
</body>

</html>