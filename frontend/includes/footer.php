<?php
/**
 * Reusable Footer Component
 * Include this file in all pages that need footer
 */
?>

<!-- Bootstrap Footer -->
<footer class="bg-white text-dark py-4 mt-auto border-top">
    <div class="container">
        <div class="row">
            <!-- Company Info -->
            <div class="col-md-4 mb-3">
                <h5 class="text-dark mb-3">
                    <i class="fas fa-home me-2"></i><?php echo APP_NAME; ?>
                </h5>
                <p class="text-dark">
                    Find your perfect rental home with our secure, transparent platform. 
                    Connect with verified property owners and discover your next home.
                </p>
            </div>

            <!-- Quick Links -->
            <div class="col-md-2 mb-3">
                <h6 class="text-dark mb-3">Quick Links</h6>
                <ul class="list-unstyled">
                    <li><a href="index.php" class="text-dark text-decoration-none">Home</a></li>
                    <li><a href="search.php" class="text-dark text-decoration-none">Search Properties</a></li>
                    <li><a href="about.php" class="text-dark text-decoration-none">About Us</a></li>
                    <li><a href="register.php" class="text-dark text-decoration-none">Register</a></li>
                </ul>
            </div>

            <!-- For Property Owners -->
            <div class="col-md-2 mb-3">
                <h6 class="text-dark mb-3">For Owners</h6>
                <ul class="list-unstyled">
                    <li><a href="add-property.php" class="text-dark text-decoration-none">Add Property</a></li>
                    <li><a href="owner/properties.php" class="text-dark text-decoration-none">My Properties</a></li>
                    <li><a href="owner/bought.php" class="text-dark text-decoration-none">Bookings</a></li>
                    <li><a href="owner/payments.php" class="text-dark text-decoration-none">Payments</a></li>
                </ul>
            </div>

            <!-- Support -->
            <div class="col-md-2 mb-3">
                <h6 class="text-dark mb-3">Support</h6>
                <ul class="list-unstyled">
                    <li><a href="contact.php" class="text-dark text-decoration-none">Contact Us</a></li>
                    <li><a href="help.php" class="text-dark text-decoration-none">Help Center</a></li>
                    <li><a href="privacy.php" class="text-dark text-decoration-none">Privacy Policy</a></li>
                    <li><a href="terms.php" class="text-dark text-decoration-none">Terms of Service</a></li>
                </ul>
            </div>

            <!-- Contact Info -->
            <div class="col-md-2 mb-3">
                <h6 class="text-dark mb-3">Contact</h6>
                <ul class="list-unstyled">
                    <li class="text-dark"><i class="fas fa-phone me-2"></i>011 234 5678</li>
                    <li class="text-dark"><i class="fas fa-envelope me-2"></i>info@rentingplace.com</li>
                    <li class="text-dark"><i class="fas fa-map-marker-alt me-2"></i>Colombo, Sri Lanka</li>
                </ul>
            </div>
        </div>

        <hr class="my-4 border-secondary">

        <!-- Bottom Footer -->
        <div class="row align-items-center">
            <div class="col-md-6">
                <p class="text-dark mb-0">
                    &copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?>. All rights reserved.
                </p>
            </div>
            <div class="col-md-6 text-md-end">
                <div class="d-flex justify-content-md-end gap-3">
                    <a href="#" class="text-dark"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="text-dark"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="text-dark"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="text-dark"><i class="fab fa-linkedin-in"></i></a>
                </div>
            </div>
        </div>
    </div>
</footer>
