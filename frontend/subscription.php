<?php
/**
 * Subscription Page
 * Handles monthly subscription creation for properties
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../backend/models/Property.php';
require_once __DIR__ . '/../backend/models/Subscription.php';

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

$subscription_model = new Subscription();
$message = '';
$message_type = '';

// Handle subscription creation
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_subscription'])) {
    try {
        $start_date = $_POST['start_date'];
        $monthly_amount = $_POST['monthly_amount'];
        $auto_renew = isset($_POST['auto_renew']) ? 1 : 0;
        
        // Validate dates
        if (strtotime($start_date) < strtotime('today')) {
            throw new Exception('Start date cannot be in the past');
        }
        
        // Calculate next payment date (1 month from start date)
        $next_payment_date = date('Y-m-d', strtotime($start_date . ' +1 month'));
        
        // Create subscription
        $subscription_id = $subscription_model->create([
            'property_id' => $property_id,
            'customer_id' => $_SESSION['user_id'],
            'owner_id' => $property['owner_id'],
            'monthly_amount' => $monthly_amount,
            'start_date' => $start_date,
            'next_payment_date' => $next_payment_date,
            'status' => 'active',
            'payment_method' => 'payhere',
            'auto_renew' => $auto_renew
        ]);
        
        // Redirect to payment page for first payment
        redirect(APP_URL . '/frontend/payment.php?subscription_id=' . $subscription_id);
        
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
    <title>Subscribe to <?php echo htmlspecialchars($property['title']); ?> - <?php echo APP_NAME; ?></title>
    
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
                                <p class="mb-2"><strong>Security Deposit:</strong> LKR <?php echo number_format($property['security_deposit']); ?></p>
                                <p class="mb-2"><strong>Available:</strong> <?php echo isset($property['is_available']) && $property['is_available'] ? 'Yes' : 'No'; ?></p>
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
            
            <!-- Subscription Form -->
            <div class="col-md-6">
                <div class="card border-0 shadow-lg">
                    <div class="card-header bg-success text-white">
                        <h4 class="mb-0">
                            <i class="fas fa-calendar-check me-2"></i>Monthly Subscription
                        </h4>
                    </div>
                    <div class="card-body">
                        <?php if ($message): ?>
                            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                                <?php echo htmlspecialchars($message); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" id="subscriptionForm">
                            <div class="mb-3">
                                <label for="start_date" class="form-label">Subscription Start Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="start_date" name="start_date" 
                                       value="<?php echo htmlspecialchars($_POST['start_date'] ?? date('Y-m-d')); ?>" 
                                       min="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="monthly_amount" class="form-label">Monthly Amount (LKR) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="monthly_amount" name="monthly_amount" 
                                       value="<?php echo htmlspecialchars($_POST['monthly_amount'] ?? $property['monthly_rent']); ?>" 
                                       step="0.01" min="0" required>
                                <div class="form-text">Default: LKR <?php echo number_format($property['monthly_rent']); ?></div>
                            </div>
                            
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="auto_renew" name="auto_renew" 
                                           <?php echo ($_POST['auto_renew'] ?? 'checked') ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="auto_renew">
                                        <strong>Auto-renew subscription</strong>
                                    </label>
                                    <div class="form-text">Automatically charge monthly payments</div>
                                </div>
                            </div>
                            
                            <!-- Subscription Summary -->
                            <div class="subscription-summary bg-light p-3 rounded mb-3">
                                <h6 class="mb-3">Subscription Summary</h6>
                                <div class="row">
                                    <div class="col-6">
                                        <p class="mb-1"><strong>Monthly Amount:</strong></p>
                                        <p class="mb-1"><strong>Start Date:</strong></p>
                                        <p class="mb-1"><strong>Next Payment:</strong></p>
                                        <p class="mb-1"><strong>Auto-renew:</strong></p>
                                    </div>
                                    <div class="col-6">
                                        <p class="mb-1">LKR <span id="summaryAmount"><?php echo number_format($property['monthly_rent']); ?></span></p>
                                        <p class="mb-1"><span id="summaryStartDate"><?php echo date('M d, Y'); ?></span></p>
                                        <p class="mb-1"><span id="summaryNextDate"><?php echo date('M d, Y', strtotime('+1 month')); ?></span></p>
                                        <p class="mb-1"><span id="summaryAutoRenew">Yes</span></p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" name="create_subscription" class="btn btn-success btn-lg">
                                    <i class="fas fa-credit-card me-2"></i>Start Monthly Subscription
                                </button>
                                <a href="property-details.php?id=<?php echo $property_id; ?>" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>Back to Property
                                </a>
                            </div>
                        </form>
                        
                        <!-- Subscription Benefits -->
                        <div class="mt-4">
                            <div class="alert alert-info">
                                <h6><i class="fas fa-info-circle me-2"></i>Subscription Benefits</h6>
                                <ul class="mb-0 small">
                                    <li>Secure monthly payments via PayHere</li>
                                    <li>Automatic payment processing</li>
                                    <li>Cancel anytime with 30 days notice</li>
                                    <li>Payment history and receipts</li>
                                    <li>24/7 customer support</li>
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
        document.addEventListener('DOMContentLoaded', function() {
            const startDateInput = document.getElementById('start_date');
            const monthlyAmountInput = document.getElementById('monthly_amount');
            const autoRenewCheckbox = document.getElementById('auto_renew');
            const summaryAmount = document.getElementById('summaryAmount');
            const summaryStartDate = document.getElementById('summaryStartDate');
            const summaryNextDate = document.getElementById('summaryNextDate');
            const summaryAutoRenew = document.getElementById('summaryAutoRenew');
            
            function updateSummary() {
                const startDate = new Date(startDateInput.value);
                const nextDate = new Date(startDate);
                nextDate.setMonth(nextDate.getMonth() + 1);
                
                summaryAmount.textContent = parseFloat(monthlyAmountInput.value).toLocaleString();
                summaryStartDate.textContent = startDate.toLocaleDateString('en-US', { 
                    year: 'numeric', month: 'short', day: 'numeric' 
                });
                summaryNextDate.textContent = nextDate.toLocaleDateString('en-US', { 
                    year: 'numeric', month: 'short', day: 'numeric' 
                });
                summaryAutoRenew.textContent = autoRenewCheckbox.checked ? 'Yes' : 'No';
            }
            
            startDateInput.addEventListener('change', updateSummary);
            monthlyAmountInput.addEventListener('input', updateSummary);
            autoRenewCheckbox.addEventListener('change', updateSummary);
        });
    </script>
    
    <style>
        .card {
            border-radius: 15px;
        }
        
        .property-price, .subscription-summary {
            border: 1px solid #e9ecef;
        }
    </style>
</body>
</html>
