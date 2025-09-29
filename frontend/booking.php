<?php
/**
 * Booking Page
 * Handles property booking process
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../backend/models/Property.php';
require_once __DIR__ . '/../backend/models/Booking.php';

// Check if user is logged in
if (!is_logged_in()) {
    redirect(APP_URL . '/frontend/login.php');
}

$property_id = (int)($_GET['property_id'] ?? 0);

if (!$property_id) {
    redirect(APP_URL . '/frontend/index.php');
}

$property_model = new Property();
$property = $property_model->getById($property_id);

if (!$property) {
    redirect(APP_URL . '/frontend/index.php');
}

$message = '';
$message_type = '';

// Handle booking submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_booking'])) {
    try {
        $check_in = $_POST['check_in_date'];
        $check_out = $_POST['check_out_date'];
        $guests = (int)$_POST['guests'];
        $total_amount = $_POST['total_amount'];
        
        // Validate dates
        if (strtotime($check_in) < strtotime('today')) {
            throw new Exception('Check-in date cannot be in the past');
        }
        
        if (strtotime($check_out) <= strtotime($check_in)) {
            throw new Exception('Check-out date must be after check-in date');
        }
        
        // Create booking
        $booking_model = new Booking();
        $booking_id = $booking_model->create([
            'property_id' => $property_id,
            'customer_id' => $_SESSION['user_id'],
            'owner_id' => $property['owner_id'],
            'check_in_date' => $check_in,
            'check_out_date' => $check_out,
            'guests' => $guests,
            'total_amount' => $total_amount,
            'status' => 'pending',
            'payment_status' => 'pending'
        ]);
        
        // Redirect to payment page
        redirect(APP_URL . '/frontend/payment.php?booking_id=' . $booking_id);
        
    } catch (Exception $e) {
        $message = 'Error: ' . $e->getMessage();
        $message_type = 'danger';
    }
}

// Calculate total amount
$nights = 1;
$total_amount = $property['monthly_rent'];
if (isset($_POST['check_in_date']) && isset($_POST['check_out_date'])) {
    $check_in = strtotime($_POST['check_in_date']);
    $check_out = strtotime($_POST['check_out_date']);
    $nights = max(1, ($check_out - $check_in) / (60 * 60 * 24));
    $total_amount = $property['monthly_rent'] * $nights;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book <?php echo htmlspecialchars($property['title']); ?> - <?php echo APP_NAME; ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold text-primary" href="index.php">
                <i class="fas fa-home me-2"></i><?php echo APP_NAME; ?>
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="logout.php">
                    <i class="fas fa-sign-out-alt me-1"></i>Logout
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container my-5">
        <div class="row">
            <!-- Property Details -->
            <div class="col-md-6">
                <div class="card border-0 shadow-lg">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">
                            <i class="fas fa-home me-2"></i>Property Details
                        </h4>
                    </div>
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($property['title']); ?></h5>
                        <p class="text-muted mb-3">
                            <i class="fas fa-map-marker-alt me-1"></i>
                            <?php echo htmlspecialchars($property['address'] . ', ' . $property['city'] . ', ' . $property['state']); ?>
                        </p>
                        
                        <div class="row mb-3">
                            <div class="col-6">
                                <p class="mb-2"><strong>Type:</strong> <?php echo ucfirst($property['property_type']); ?></p>
                                <p class="mb-2"><strong>Bedrooms:</strong> <?php echo $property['bedrooms']; ?></p>
                                <p class="mb-2"><strong>Bathrooms:</strong> <?php echo $property['bathrooms']; ?></p>
                            </div>
                            <div class="col-6">
                                <p class="mb-2"><strong>Area:</strong> <?php echo number_format($property['area_sqft']); ?> sqft</p>
                                <p class="mb-2"><strong>Furnished:</strong> <?php echo $property['is_furnished'] ? 'Yes' : 'No'; ?></p>
                                <p class="mb-2"><strong>Available:</strong> <?php echo $property['is_available'] ? 'Yes' : 'No'; ?></p>
                            </div>
                        </div>
                        
                        <div class="property-price bg-light p-3 rounded">
                            <h4 class="text-primary mb-0">
                                LKR <?php echo number_format($property['monthly_rent']); ?>
                                <small class="text-muted">/month</small>
                            </h4>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Booking Form -->
            <div class="col-md-6">
                <div class="card border-0 shadow-lg">
                    <div class="card-header bg-success text-white">
                        <h4 class="mb-0">
                            <i class="fas fa-calendar-check me-2"></i>Book This Property
                        </h4>
                    </div>
                    <div class="card-body">
                        <?php if ($message): ?>
                            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                                <?php echo htmlspecialchars($message); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" id="bookingForm">
                            <div class="mb-3">
                                <label for="check_in_date" class="form-label">Check-in Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="check_in_date" name="check_in_date" 
                                       value="<?php echo htmlspecialchars($_POST['check_in_date'] ?? ''); ?>" 
                                       min="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="check_out_date" class="form-label">Check-out Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="check_out_date" name="check_out_date" 
                                       value="<?php echo htmlspecialchars($_POST['check_out_date'] ?? ''); ?>" 
                                       min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="guests" class="form-label">Number of Guests <span class="text-danger">*</span></label>
                                <select class="form-select" id="guests" name="guests" required>
                                    <option value="">Select guests</option>
                                    <?php for ($i = 1; $i <= 10; $i++): ?>
                                        <option value="<?php echo $i; ?>" 
                                                <?php echo ($_POST['guests'] ?? '') == $i ? 'selected' : ''; ?>>
                                            <?php echo $i; ?> <?php echo $i == 1 ? 'guest' : 'guests'; ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            
                            <!-- Total Amount Display -->
                            <div class="total-amount bg-light p-3 rounded mb-3">
                                <div class="row">
                                    <div class="col-6">
                                        <p class="mb-1"><strong>Price per month:</strong></p>
                                        <p class="mb-1"><strong>Duration:</strong></p>
                                        <p class="mb-1"><strong>Total Amount:</strong></p>
                                    </div>
                                    <div class="col-6">
                                        <p class="mb-1">LKR <?php echo number_format($property['monthly_rent']); ?></p>
                                        <p class="mb-1"><span id="duration">1 month</span></p>
                                        <p class="mb-1"><strong>LKR <span id="totalAmount"><?php echo number_format($total_amount); ?></span></strong></p>
                                    </div>
                                </div>
                            </div>
                            
                            <input type="hidden" name="total_amount" id="totalAmountInput" value="<?php echo $total_amount; ?>">
                            
                            <div class="d-grid gap-2">
                                <button type="submit" name="create_booking" class="btn btn-success btn-lg">
                                    <i class="fas fa-credit-card me-2"></i>Proceed to Payment
                                </button>
                                <a href="property-details.php?id=<?php echo $property_id; ?>" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>Back to Property
                                </a>
                            </div>
                        </form>
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
        document.addEventListener('DOMContentLoaded', function() {
            const checkInInput = document.getElementById('check_in_date');
            const checkOutInput = document.getElementById('check_out_date');
            const guestsSelect = document.getElementById('guests');
            const durationSpan = document.getElementById('duration');
            const totalAmountSpan = document.getElementById('totalAmount');
            const totalAmountInput = document.getElementById('totalAmountInput');
            
            const monthlyRent = <?php echo $property['monthly_rent']; ?>;
            
            function calculateTotal() {
                const checkIn = new Date(checkInInput.value);
                const checkOut = new Date(checkOutInput.value);
                
                if (checkIn && checkOut && checkOut > checkIn) {
                    const diffTime = Math.abs(checkOut - checkIn);
                    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                    const months = Math.ceil(diffDays / 30);
                    
                    const total = monthlyRent * months;
                    
                    durationSpan.textContent = `${months} month${months > 1 ? 's' : ''}`;
                    totalAmountSpan.textContent = total.toLocaleString();
                    totalAmountInput.value = total;
                } else {
                    durationSpan.textContent = '1 month';
                    totalAmountSpan.textContent = monthlyRent.toLocaleString();
                    totalAmountInput.value = monthlyRent;
                }
            }
            
            checkInInput.addEventListener('change', function() {
                const minDate = new Date(this.value);
                minDate.setDate(minDate.getDate() + 1);
                checkOutInput.min = minDate.toISOString().split('T')[0];
                calculateTotal();
            });
            
            checkOutInput.addEventListener('change', calculateTotal);
            guestsSelect.addEventListener('change', calculateTotal);
        });
    </script>
    
    <style>
        .card {
            border-radius: 15px;
        }
        
        .property-price, .total-amount {
            border: 1px solid #e9ecef;
        }
    </style>
</body>
</html>
