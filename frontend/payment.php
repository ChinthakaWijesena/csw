<?php
/**
 * Payment Page
 * Handles payment processing for bookings
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../backend/models/Booking.php';
require_once __DIR__ . '/../backend/models/Subscription.php';
require_once __DIR__ . '/../backend/models/PayHere.php';

// Check if user is logged in
if (!is_logged_in()) {
    redirect(APP_URL . '/frontend/login.php');
}

$booking_id = (int)($_GET['booking_id'] ?? 0);
$subscription_id = (int)($_GET['subscription_id'] ?? 0);

if (!$booking_id && !$subscription_id) {
    redirect(APP_URL . '/frontend/index.php');
}

$booking = null;
$subscription = null;
$property = null;
$payment_type = '';

if ($booking_id) {
    $booking_model = new Booking();
    $booking = $booking_model->getById($booking_id);
    
    if (!$booking) {
        redirect(APP_URL . '/frontend/index.php');
    }
    
    // Check if booking belongs to current user
    if ($booking['customer_id'] != $_SESSION['user_id']) {
        redirect(APP_URL . '/frontend/index.php');
    }
    
    $property = $booking;
    $payment_type = 'booking';
} elseif ($subscription_id) {
    $subscription_model = new Subscription();
    $subscription = $subscription_model->getById($subscription_id);
    
    if (!$subscription) {
        redirect(APP_URL . '/frontend/index.php');
    }
    
    // Check if subscription belongs to current user
    if ($subscription['customer_id'] != $_SESSION['user_id']) {
        redirect(APP_URL . '/frontend/index.php');
    }
    
    $property = $subscription;
    $payment_type = 'subscription';
}

$payhere = new PayHere();
$message = '';
$message_type = '';

// Handle payment initiation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['initiate_payment'])) {
    try {
        $amount = $_POST['amount'];
        $customer_name = $_POST['customer_name'];
        $customer_email = $_POST['customer_email'];
        $customer_phone = $_POST['customer_phone'];
        
        // Generate order ID
        if ($payment_type === 'booking') {
            $order_id = 'BK' . $booking_id . '_' . time();
            $item_name = 'Property Booking - ' . $property['property_title'];
        } else {
            $order_id = 'SUB' . $subscription_id . '_' . time();
            $item_name = 'Monthly Subscription - ' . $property['property_title'];
        }
        
        // Prepare payment data
        $payment_data = $payhere->generatePaymentData([
            'amount' => $amount,
            'order_id' => $order_id,
            'item_name' => $item_name,
            'customer_name' => $customer_name,
            'customer_email' => $customer_email,
            'customer_phone' => $customer_phone,
            'return_url' => APP_URL . '/frontend/payment-success.php',
            'cancel_url' => APP_URL . '/frontend/payment-cancel.php',
            'notify_url' => APP_URL . '/api/payment/notify.php'
        ]);
        
        // Create payment form
        $payment_form = $payhere->createPaymentForm($payment_data);
        
        $message = 'Payment form generated successfully. You will be redirected to PayHere.';
        $message_type = 'success';
        
    } catch (Exception $e) {
        $message = 'Error: ' . $e->getMessage();
        $message_type = 'danger';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment - <?php echo APP_NAME; ?></title>
    
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
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="card border-0 shadow-lg">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">
                            <i class="fas fa-credit-card me-2"></i>Payment
                        </h4>
                    </div>
                    <div class="card-body p-4">
                        <?php if ($message): ?>
                            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                                <?php echo htmlspecialchars($message); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Payment Summary -->
                        <div class="payment-summary bg-light p-3 rounded mb-4">
                            <h5 class="mb-3"><?php echo $payment_type === 'booking' ? 'Booking' : 'Subscription'; ?> Summary</h5>
                            <div class="row">
                                <div class="col-6">
                                    <p class="mb-2"><strong>Property:</strong></p>
                                    <?php if ($payment_type === 'booking'): ?>
                                        <p class="mb-2"><strong>Check-in:</strong></p>
                                        <p class="mb-2"><strong>Check-out:</strong></p>
                                        <p class="mb-2"><strong>Guests:</strong></p>
                                    <?php else: ?>
                                        <p class="mb-2"><strong>Start Date:</strong></p>
                                        <p class="mb-2"><strong>Next Payment:</strong></p>
                                        <p class="mb-2"><strong>Auto-renew:</strong></p>
                                    <?php endif; ?>
                                </div>
                                <div class="col-6">
                                    <p class="mb-2"><?php echo htmlspecialchars($property['property_title']); ?></p>
                                    <?php if ($payment_type === 'booking'): ?>
                                        <p class="mb-2"><?php echo date('M d, Y', strtotime($property['check_in_date'])); ?></p>
                                        <p class="mb-2"><?php echo date('M d, Y', strtotime($property['check_out_date'])); ?></p>
                                        <p class="mb-2"><?php echo $property['guests']; ?> guests</p>
                                    <?php else: ?>
                                        <p class="mb-2"><?php echo date('M d, Y', strtotime($property['start_date'])); ?></p>
                                        <p class="mb-2"><?php echo date('M d, Y', strtotime($property['next_payment_date'])); ?></p>
                                        <p class="mb-2"><?php echo $property['auto_renew'] ? 'Yes' : 'No'; ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Payment Form -->
                        <form method="POST" id="paymentForm">
                            <div class="mb-3">
                                <label for="amount" class="form-label">Amount (LKR) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="amount" name="amount" 
                                       value="<?php echo $property['monthly_amount']; ?>" 
                                       step="0.01" min="0" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="customer_name" class="form-label">Full Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="customer_name" name="customer_name" 
                                       value="<?php echo htmlspecialchars($_SESSION['user_name'] ?? ''); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="customer_email" class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" id="customer_email" name="customer_email" 
                                       value="<?php echo htmlspecialchars($_SESSION['user_email'] ?? ''); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="customer_phone" class="form-label">Phone <span class="text-danger">*</span></label>
                                <input type="tel" class="form-control" id="customer_phone" name="customer_phone" 
                                       value="<?php echo htmlspecialchars($_SESSION['user_phone'] ?? ''); ?>" required>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" name="initiate_payment" class="btn btn-primary btn-lg">
                                    <i class="fas fa-credit-card me-2"></i>Pay with PayHere
                                </button>
                                <?php if ($payment_type === 'booking'): ?>
                                    <a href="booking-details.php?id=<?php echo $booking_id; ?>" class="btn btn-outline-secondary">
                                        <i class="fas fa-arrow-left me-2"></i>Back to Booking
                                    </a>
                                <?php else: ?>
                                    <a href="subscription-details.php?id=<?php echo $subscription_id; ?>" class="btn btn-outline-secondary">
                                        <i class="fas fa-arrow-left me-2"></i>Back to Subscription
                                    </a>
                                <?php endif; ?>
                            </div>
                        </form>
                        
                        <!-- Payment Info -->
                        <div class="mt-4">
                            <div class="alert alert-info">
                                <h6><i class="fas fa-info-circle me-2"></i>Payment Information</h6>
                                <ul class="mb-0 small">
                                    <li>Secure payment processing by PayHere</li>
                                    <li>We accept all major credit/debit cards</li>
                                    <li>Your payment information is encrypted and secure</li>
                                    <li>You will receive a confirmation email after successful payment</li>
                                </ul>
                            </div>
                        </div>
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
        // Auto-submit payment form if payment data is available
        <?php if (isset($payment_form)): ?>
            document.addEventListener('DOMContentLoaded', function() {
                // Add the payment form to the page
                document.body.insertAdjacentHTML('beforeend', '<?php echo addslashes($payment_form); ?>');
                
                // Auto-submit the form
                setTimeout(function() {
                    document.getElementById('payhere-payment-form').submit();
                }, 2000);
            });
        <?php endif; ?>
    </script>
    
    <style>
        .card {
            border-radius: 15px;
        }
        
        .booking-summary {
            border: 1px solid #e9ecef;
        }
    </style>
</body>
</html>
