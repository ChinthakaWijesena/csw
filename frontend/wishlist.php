<?php
/**
 * Wishlist Page
 * Displays user's saved properties
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../backend/models/Property.php';

$property_model = new Property();
$wishlist_properties = [];

// Get wishlist from localStorage via JavaScript
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Wishlist - <?php echo APP_NAME; ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
</head>
<body>
    <?php include 'includes/navbar.php'; ?>


    <!-- Main Content -->
    <div class="container my-5">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-heart me-2"></i>My Wishlist</h2>
                    <a href="index.php" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i>Browse Properties
                    </a>
                </div>
                
                <!-- Wishlist Content -->
                <div id="wishlist-content">
                    <div class="text-center py-5" id="loading-message">
                        <i class="fas fa-spinner fa-spin fa-2x text-muted mb-3"></i>
                        <h4 class="text-muted">Loading your wishlist...</h4>
                    </div>
                    
                    <div class="text-center py-5" id="empty-wishlist" style="display: none;">
                        <i class="fas fa-heart-broken fa-3x text-muted mb-3"></i>
                        <h4 class="text-muted">Your wishlist is empty</h4>
                        <p class="text-muted">Start adding properties you love to your wishlist!</p>
                        <a href="index.php" class="btn btn-primary">
                            <i class="fas fa-search me-1"></i>Browse Properties
                        </a>
                    </div>
                    
                    <div id="wishlist-properties" style="display: none;">
                        <!-- Properties will be loaded here -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-light py-4 mt-5">
        <div class="container text-center">
            <p class="text-muted mb-0">&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?>. All rights reserved.</p>
        </div>
    </footer>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Wishlist functionality
        function getWishlist() {
            return JSON.parse(localStorage.getItem('wishlist') || '[]');
        }
        
        function removeFromWishlist(propertyId) {
            let wishlist = getWishlist();
            wishlist = wishlist.filter(id => id !== propertyId);
            localStorage.setItem('wishlist', JSON.stringify(wishlist));
            loadWishlistProperties();
        }
        
        function loadWishlistProperties() {
            const wishlist = getWishlist();
            const loadingMessage = document.getElementById('loading-message');
            const emptyWishlist = document.getElementById('empty-wishlist');
            const wishlistProperties = document.getElementById('wishlist-properties');
            
            if (wishlist.length === 0) {
                loadingMessage.style.display = 'none';
                emptyWishlist.style.display = 'block';
                wishlistProperties.style.display = 'none';
                return;
            }
            
            // Fetch property details for wishlist items
            fetch('../api/wishlist/get_properties.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    property_ids: wishlist
                })
            })
            .then(response => response.json())
            .then(data => {
                loadingMessage.style.display = 'none';
                
                if (data.success && data.properties.length > 0) {
                    emptyWishlist.style.display = 'none';
                    wishlistProperties.style.display = 'block';
                    displayProperties(data.properties);
                } else {
                    emptyWishlist.style.display = 'block';
                    wishlistProperties.style.display = 'none';
                }
            })
            .catch(error => {
                console.error('Error loading wishlist:', error);
                loadingMessage.style.display = 'none';
                emptyWishlist.style.display = 'block';
                wishlistProperties.style.display = 'none';
            });
        }
        
        function displayProperties(properties) {
            const container = document.getElementById('wishlist-properties');
            
            if (properties.length === 0) {
                container.innerHTML = '<div class="text-center py-5"><h4 class="text-muted">No properties found</h4></div>';
                return;
            }
            
            container.innerHTML = `
                <div class="row">
                    ${properties.map(property => `
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-img-top position-relative" style="height: 200px; overflow: hidden;">
                                    ${property.primary_image ? 
                                        `<img src="${property.primary_image}" alt="${property.title}" class="w-100 h-100" style="object-fit: cover;" onerror="this.src='images/placeholder-property.jpg'">` :
                                        `<img src="images/placeholder-property.jpg" alt="${property.title}" class="w-100 h-100" style="object-fit: cover;">`
                                    }
                                    <button type="button" class="btn btn-danger btn-sm position-absolute top-0 end-0 m-2" 
                                            onclick="removeFromWishlist(${property.id})" title="Remove from wishlist">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                                <div class="card-body d-flex flex-column">
                                    <h5 class="card-title">${property.title}</h5>
                                    <p class="text-muted small mb-3">
                                        <i class="fas fa-map-marker-alt me-1"></i>
                                        ${property.address}, ${property.city}, ${property.state}
                                    </p>
                                    
                                    <div class="property-details mb-3">
                                        <div class="row">
                                            <div class="col-6">
                                                <p class="mb-1"><strong>Type:</strong> ${property.property_type}</p>
                                                <p class="mb-1"><strong>Bedrooms:</strong> ${property.bedrooms}</p>
                                                <p class="mb-1"><strong>Bathrooms:</strong> ${property.bathrooms}</p>
                                            </div>
                                            <div class="col-6">
                                                <p class="mb-1"><strong>Area:</strong> ${property.area_sqft} sqft</p>
                                                <p class="mb-1"><strong>Security Deposit:</strong> LKR ${property.security_deposit}</p>
                                                <p class="mb-1"><strong>Available:</strong> ${property.is_available ? 'Yes' : 'No'}</p>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="property-price bg-light p-2 rounded mb-3">
                                        <h5 class="text-primary mb-0">
                                            LKR ${property.monthly_rent.toLocaleString()}
                                            <small class="text-muted">/month</small>
                                        </h5>
                                    </div>
                                    
                                    <div class="mt-auto">
                                        <div class="d-grid gap-2">
                                            <a href="property-details.php?id=${property.id}" class="btn btn-primary">
                                                <i class="fas fa-eye me-1"></i>View Details
                                            </a>
                                            <a href="subscription.php?property_id=${property.id}" class="btn btn-success">
                                                <i class="fas fa-shopping-cart me-1"></i>Subscribe Now
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `).join('')}
                </div>
            `;
        }
        
        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            loadWishlistProperties();
        });
    </script>
    
    <style>
        .card {
            border-radius: 15px;
            transition: transform 0.2s ease;
        }
        
        .card:hover {
            transform: translateY(-2px);
        }
        
        .property-details {
            background-color: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
        }
        
        .property-price {
            border: 1px solid #e9ecef;
        }
    </style>
</body>
</html>
