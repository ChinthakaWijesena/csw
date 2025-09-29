<?php
/**
 * Property Details Page
 */

require_once __DIR__ . '/../config/config.php';

$property_id = (int)($_GET['id'] ?? 0);

if (!$property_id) {
    redirect(APP_URL . '/frontend/search.php');
}

$property_model = new Property();
$property = $property_model->getById($property_id);

if (!$property) {
    redirect(APP_URL . '/frontend/search.php');
}

// Get property images
$images = $property_model->getImages($property_id);

// Get primary image
$primary_image = null;
foreach ($images as $image) {
    if ($image['is_primary']) {
        $primary_image = $image;
        break;
    }
}
if (!$primary_image && !empty($images)) {
    $primary_image = $images[0];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($property['title']); ?> - <?php echo APP_NAME; ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <!-- Design Improvements -->
    <link href="css/design-improvements.css" rel="stylesheet">
</head>
<body>
   <?php include 'includes/navbar.php'; ?>

    <!-- Property Details -->
    <div class="container mt-5 pt-4">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                <li class="breadcrumb-item"><a href="search.php">Search Properties</a></li>
                <li class="breadcrumb-item active"><?php echo htmlspecialchars($property['title']); ?></li>
            </ol>
        </nav>

        <div class="row">
            <!-- Property Images -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-body p-0">
                        <?php if ($primary_image): ?>
                            <img src="<?php echo htmlspecialchars($primary_image['image_path']); ?>" 
                                 class="card-img-top" alt="<?php echo htmlspecialchars($property['title']); ?>"
                                 style="height: 400px; object-fit: cover;">
                        <?php else: ?>
                            <div class="bg-light d-flex align-items-center justify-content-center" style="height: 400px;">
                                <div class="text-center text-muted">
                                    <i class="fas fa-image fa-3x mb-3"></i>
                                    <p>No images available</p>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (count($images) > 1): ?>
                            <div class="p-3">
                                <div class="row g-2">
                                    <?php foreach ($images as $image): ?>
                                        <div class="col-3">
                                            <img src="<?php echo htmlspecialchars($image['image_path']); ?>" 
                                                 class="img-thumbnail" alt="Property image"
                                                 style="height: 80px; object-fit: cover; cursor: pointer;"
                                                 onclick="changeMainImage('<?php echo htmlspecialchars($image['image_path']); ?>')">
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Property Info -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-body">
                        <h1 class="h3 mb-3"><?php echo htmlspecialchars($property['title']); ?></h1>
                        
                        <div class="mb-3">
                            <span class="badge bg-primary fs-6"><?php echo ucfirst($property['property_type']); ?></span>
                        </div>
                        
                        <div class="mb-4">
                            <h2 class="text-primary fw-bold">LKR <?php echo number_format($property['monthly_rent']); ?>/month</h2>
                            <?php if ($property['security_deposit']): ?>
                                <p class="text-muted mb-0">Security Deposit: LKR <?php echo number_format($property['security_deposit']); ?></p>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-4">
                            <h5>Property Details</h5>
                            <div class="row text-center">
                                <?php if ($property['bedrooms']): ?>
                                    <div class="col-4">
                                        <div class="border rounded p-2">
                                            <i class="fas fa-bed fa-2x text-primary mb-2"></i>
                                            <div class="fw-bold"><?php echo $property['bedrooms']; ?></div>
                                            <small class="text-muted">Bedrooms</small>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                <?php if ($property['bathrooms']): ?>
                                    <div class="col-4">
                                        <div class="border rounded p-2">
                                            <i class="fas fa-bath fa-2x text-primary mb-2"></i>
                                            <div class="fw-bold"><?php echo $property['bathrooms']; ?></div>
                                            <small class="text-muted">Bathrooms</small>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                <?php if ($property['area_sqft']): ?>
                                    <div class="col-4">
                                        <div class="border rounded p-2">
                                            <i class="fas fa-ruler-combined fa-2x text-primary mb-2"></i>
                                            <div class="fw-bold"><?php echo number_format($property['area_sqft']); ?></div>
                                            <small class="text-muted">Sq Ft</small>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <h5>Location</h5>
                            <p class="text-muted">
                                <i class="fas fa-map-marker-alt me-2"></i>
                                <?php echo htmlspecialchars($property['address']); ?><br>
                                <?php echo htmlspecialchars($property['city'] . ', ' . $property['state'] . ' ' . $property['zip_code']); ?>
                            </p>
                        </div>
                        
                        <div class="mb-4">
                            <h5>Contact Owner</h5>
                            <p class="text-muted">
                                <i class="fas fa-user me-2"></i>
                                <?php echo htmlspecialchars($property['owner_name']); ?>
                            </p>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <?php if (is_logged_in() && $_SESSION['user_type'] === 'customer'): ?>
                                <button class="btn btn-primary btn-lg" data-bs-toggle="modal" data-bs-target="#visitModal">
                                    <i class="fas fa-calendar me-2"></i>Request Visit
                                </button>
                                <button class="btn btn-outline-primary" onclick="addToFavorites(<?php echo $property['id']; ?>)">
                                    <i class="fas fa-heart me-2"></i>Add to Favorites
                                </button>
                            <?php elseif (!is_logged_in()): ?>
                                <a href="login.php" class="btn btn-primary btn-lg">
                                    <i class="fas fa-sign-in-alt me-2"></i>Login to Request Visit
                                </a>
                            <?php endif; ?>
                            
                            
                            <a href="search.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Back to Search
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Property Description -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Description</h5>
                    </div>
                    <div class="card-body">
                        <p><?php echo nl2br(htmlspecialchars($property['description'])); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Visit Request Modal -->
    <?php if (is_logged_in() && $_SESSION['user_type'] === 'customer'): ?>
        <div class="modal fade" id="visitModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Request Property Visit</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form id="visitRequestForm">
                        <div class="modal-body">
                            <input type="hidden" id="propertyId" value="<?php echo $property['id']; ?>">
                            
                            <div class="mb-3">
                                <label for="visitDate" class="form-label">Preferred Date</label>
                                <input type="date" class="form-control" id="visitDate" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="visitTime" class="form-label">Preferred Time</label>
                                <select class="form-select" id="visitTime" required>
                                    <option value="">Select time</option>
                                    <option value="09:00">9:00 AM</option>
                                    <option value="10:00">10:00 AM</option>
                                    <option value="11:00">11:00 AM</option>
                                    <option value="12:00">12:00 PM</option>
                                    <option value="13:00">1:00 PM</option>
                                    <option value="14:00">2:00 PM</option>
                                    <option value="15:00">3:00 PM</option>
                                    <option value="16:00">4:00 PM</option>
                                    <option value="17:00">5:00 PM</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="visitNotes" class="form-label">Additional Notes (Optional)</label>
                                <textarea class="form-control" id="visitNotes" rows="3" placeholder="Any specific questions or requests..."></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Send Request</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Footer -->
    <footer class="bg-dark text-white py-5 mt-5">
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
    <!-- Custom JS -->
    <script src="js/main.js"></script>
    
    <script>
        // Set minimum date to today
        document.getElementById('visitDate').min = new Date().toISOString().split('T')[0];
        
        // Change main image function
        function changeMainImage(imagePath) {
            document.querySelector('.card-img-top').src = imagePath;
        }
        
        // Visit request form submission
        document.getElementById('visitRequestForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = {
                property_id: document.getElementById('propertyId').value,
                requested_date: document.getElementById('visitDate').value,
                requested_time: document.getElementById('visitTime').value,
                notes: document.getElementById('visitNotes').value
            };
            
            // Show loading
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Sending...';
            submitBtn.disabled = true;
            
            // Send request
            fetch('../api/visits/request.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(formData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Visit request sent successfully!');
                    bootstrap.Modal.getInstance(document.getElementById('visitModal')).hide();
                    this.reset();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            })
            .finally(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            });
        });
        
        // Add to favorites function
        function addToFavorites(propertyId) {
            // Show loading state
            const button = event.target;
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Adding...';
            button.disabled = true;
            
            fetch('../api/favorites/add.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ property_id: propertyId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Add to localStorage wishlist
                    let wishlist = JSON.parse(localStorage.getItem('wishlist') || '[]');
                    if (!wishlist.includes(propertyId)) {
                        wishlist.push(propertyId);
                        localStorage.setItem('wishlist', JSON.stringify(wishlist));
                        updateWishlistCount();
                    }
                    
                    // Show success message
                    showNotification('Added to favorites!', 'success');
                    
                    // Update button to show it's in favorites
                    button.innerHTML = '<i class="fas fa-heart me-2"></i>In Favorites';
                    button.classList.remove('btn-outline-primary');
                    button.classList.add('btn-success');
                } else {
                    showNotification('Error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('An error occurred. Please try again.', 'error');
            })
            .finally(() => {
                button.innerHTML = originalText;
                button.disabled = false;
            });
        }
        
        // Show notification function
        function showNotification(message, type = 'info') {
            // Create notification element
            const notification = document.createElement('div');
            notification.className = `alert alert-${type === 'success' ? 'success' : type === 'error' ? 'danger' : 'info'} alert-dismissible fade show position-fixed`;
            notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
            notification.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            // Add to page
            document.body.appendChild(notification);
            
            // Auto remove after 3 seconds
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.remove();
                }
            }, 3000);
        }
        
        // Wishlist functionality
        function updateWishlistCount() {
            let wishlist = JSON.parse(localStorage.getItem('wishlist') || '[]');
            const countElement = document.getElementById('wishlist-count');
            if (countElement) {
                countElement.textContent = `(${wishlist.length})`;
            }
        }
        
        // Check if property is in favorites and update button
        function checkFavoritesStatus(propertyId) {
            let wishlist = JSON.parse(localStorage.getItem('wishlist') || '[]');
            const favoriteButton = document.querySelector('button[onclick*="addToFavorites"]');
            
            if (favoriteButton && wishlist.includes(propertyId)) {
                favoriteButton.innerHTML = '<i class="fas fa-heart me-2"></i>In Favorites';
                favoriteButton.classList.remove('btn-outline-primary');
                favoriteButton.classList.add('btn-success');
                favoriteButton.setAttribute('onclick', `removeFromFavorites(${propertyId})`);
            }
        }
        
        // Remove from favorites function
        function removeFromFavorites(propertyId) {
            // Show loading state
            const button = event.target;
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Removing...';
            button.disabled = true;
            
            fetch('../api/favorites/remove.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ property_id: propertyId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Remove from localStorage wishlist
                    let wishlist = JSON.parse(localStorage.getItem('wishlist') || '[]');
                    wishlist = wishlist.filter(id => id !== propertyId);
                    localStorage.setItem('wishlist', JSON.stringify(wishlist));
                    updateWishlistCount();
                    
                    // Show success message
                    showNotification('Removed from favorites!', 'success');
                    
                    // Update button back to add to favorites
                    button.innerHTML = '<i class="fas fa-heart me-2"></i>Add to Favorites';
                    button.classList.remove('btn-success');
                    button.classList.add('btn-outline-primary');
                    button.setAttribute('onclick', `addToFavorites(${propertyId})`);
                } else {
                    showNotification('Error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('An error occurred. Please try again.', 'error');
            })
            .finally(() => {
                button.innerHTML = originalText;
                button.disabled = false;
            });
        }
        
        // Initialize wishlist count and favorites status on page load
        document.addEventListener('DOMContentLoaded', function() {
            updateWishlistCount();
            checkFavoritesStatus(<?php echo $property['id']; ?>);
        });
    </script>
</body>
</html>
