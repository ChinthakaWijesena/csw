<?php
/**
 * Payment Success Page
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../backend/models/Payment.php';
require_once __DIR__ . '/../backend/models/Booking.php';

$order_id = $_GET['order_id'] ?? '';
$payment_id = $_GET['payment_id'] ?? '';

$payment = null;
$booking = null;

if ($order_id) {
    $payment_model = new Payment();
    $payment = $payment_model->getByTransactionId($order_id);
    
    if ($payment) {
        $booking_model = new Booking();
        $booking = $booking_model->getById($payment['booking_id']);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Success - <?php echo APP_NAME; ?></title>
    
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
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="card border-0 shadow-lg">
                    <div class="card-body text-center p-5">
                        <!-- Success Icon -->
                        <div class="mb-4">
                            <div class="success-icon mx-auto">
                                <i class="fas fa-check-circle text-success"></i>
                            </div>
                        </div>
                        
                        <!-- Success Message -->
                        <h2 class="card-title text-success mb-3">Payment Successful!</h2>
                        <p class="text-muted mb-4">Your payment has been processed successfully.</p>
                        
                        <?php if ($payment && $booking): ?>
                            <!-- Payment Details -->
                            <div class="payment-details bg-light p-4 rounded mb-4">
                                <h5 class="mb-3">Payment Details</h5>
                                <div class="row text-start">
                                    <div class="col-6">
                                        <p class="mb-2"><strong>Order ID:</strong></p>
                                        <p class="mb-2"><strong>Amount:</strong></p>
                                        <p class="mb-2"><strong>Property:</strong></p>
                                        <p class="mb-2"><strong>Status:</strong></p>
                                    </div>
                                    <div class="col-6">
                                        <p class="mb-2"><?php echo htmlspecialchars($payment['transaction_id']); ?></p>
                                        <p class="mb-2">LKR <?php echo number_format($payment['amount'], 2); ?></p>
                                        <p class="mb-2"><?php echo htmlspecialchars($booking['property_title']); ?></p>
                                        <p class="mb-2">
                                            <span class="badge bg-success"><?php echo ucfirst($payment['status']); ?></span>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Booking Details -->
                            <div class="booking-details bg-light p-4 rounded mb-4">
                                <h5 class="mb-3">Booking Details</h5>
                                <div class="row text-start">
                                    <div class="col-6">
                                        <p class="mb-2"><strong>Check-in:</strong></p>
                                        <p class="mb-2"><strong>Check-out:</strong></p>
                                        <p class="mb-2"><strong>Guests:</strong></p>
                                    </div>
                                    <div class="col-6">
                                        <p class="mb-2"><?php echo date('M d, Y', strtotime($booking['check_in_date'])); ?></p>
                                        <p class="mb-2"><?php echo date('M d, Y', strtotime($booking['check_out_date'])); ?></p>
                                        <p class="mb-2"><?php echo $booking['guests']; ?> guests</p>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Action Buttons -->
                        <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                            <a href="index.php" class="btn btn-primary btn-lg">
                                <i class="fas fa-home me-2"></i>Back to Home
                            </a>
                            <?php if ($booking): ?>
                                <a href="booking-details.php?id=<?php echo $booking['id']; ?>" class="btn btn-outline-primary btn-lg">
                                    <i class="fas fa-calendar me-2"></i>View Booking
                                </a>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Additional Info -->
                        <div class="mt-4">
                            <p class="text-muted small">
                                <i class="fas fa-info-circle me-1"></i>
                                A confirmation email has been sent to your registered email address.
                            </p>
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
    
    <style>
        .success-icon {
            width: 80px;
            height: 80px;
            background: #d4edda;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
        }
        
        .payment-details, .booking-details {
            border: 1px solid #e9ecef;
        }
        
        .card {
            border-radius: 15px;
        }
    </style>
</body>
</html>
