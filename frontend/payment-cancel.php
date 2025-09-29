<?php
/**
 * Payment Cancel Page
 */

require_once __DIR__ . '/../config/config.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Cancelled - <?php echo APP_NAME; ?></title>
    
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
                        <!-- Cancel Icon -->
                        <div class="mb-4">
                            <div class="cancel-icon mx-auto">
                                <i class="fas fa-times-circle text-warning"></i>
                            </div>
                        </div>
                        
                        <!-- Cancel Message -->
                        <h2 class="card-title text-warning mb-3">Payment Cancelled</h2>
                        <p class="text-muted mb-4">Your payment has been cancelled. No charges have been made.</p>
                        
                        <!-- Information -->
                        <div class="alert alert-info mb-4">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>What happened?</strong><br>
                            The payment process was cancelled before completion. Your booking is still pending and you can try again.
                        </div>
                        
                        <!-- Action Buttons -->
                        <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                            <a href="index.php" class="btn btn-primary btn-lg">
                                <i class="fas fa-home me-2"></i>Back to Home
                            </a>
                            <a href="search.php" class="btn btn-outline-primary btn-lg">
                                <i class="fas fa-search me-2"></i>Search Properties
                            </a>
                        </div>
                        
                        <!-- Additional Info -->
                        <div class="mt-4">
                            <p class="text-muted small">
                                <i class="fas fa-question-circle me-1"></i>
                                Need help? Contact our support team for assistance.
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
        .cancel-icon {
            width: 80px;
            height: 80px;
            background: #fff3cd;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
        }
        
        .card {
            border-radius: 15px;
        }
    </style>
</body>
</html>
