<?php
/**
 * My Subscriptions Page
 * Customer subscription management
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../backend/models/Subscription.php';

// Check if user is logged in
if (!is_logged_in()) {
    redirect(APP_URL . '/frontend/login.php');
}

$subscription_model = new Subscription();
$subscriptions = $subscription_model->getByCustomer($_SESSION['user_id']);

$message = '';
$message_type = '';

// Handle subscription cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_subscription'])) {
    try {
        $subscription_id = (int)$_POST['subscription_id'];
        $reason = $_POST['cancellation_reason'] ?? '';
        
        $subscription_model->cancel($subscription_id, $reason);
        
        $message = 'Subscription cancelled successfully.';
        $message_type = 'success';
        
        // Refresh subscriptions
        $subscriptions = $subscription_model->getByCustomer($_SESSION['user_id']);
        
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
    <title>My Subscriptions - <?php echo APP_NAME; ?></title>
    
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
                <a class="nav-link" href="dashboard.php">
                    <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                </a>
                <a class="nav-link" href="logout.php">
                    <i class="fas fa-sign-out-alt me-1"></i>Logout
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container my-5">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-calendar-check me-2"></i>My Subscriptions</h2>
                    <a href="index.php" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i>Find Properties
                    </a>
                </div>
                
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if (empty($subscriptions)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                        <h4 class="text-muted">No Active Subscriptions</h4>
                        <p class="text-muted">You don't have any active subscriptions yet.</p>
                        <a href="index.php" class="btn btn-primary">
                            <i class="fas fa-search me-1"></i>Browse Properties
                        </a>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($subscriptions as $subscription): ?>
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-0 shadow-sm h-100">
                                    <div class="card-body">
                                        <h5 class="card-title"><?php echo htmlspecialchars($subscription['property_title']); ?></h5>
                                        <p class="text-muted small mb-3">
                                            <i class="fas fa-map-marker-alt me-1"></i>
                                            <?php echo htmlspecialchars($subscription['address'] . ', ' . $subscription['city']); ?>
                                        </p>
                                        
                                        <div class="subscription-details mb-3">
                                            <div class="row">
                                                <div class="col-6">
                                                    <p class="mb-1"><strong>Amount:</strong></p>
                                                    <p class="mb-1"><strong>Start Date:</strong></p>
                                                    <p class="mb-1"><strong>Next Payment:</strong></p>
                                                </div>
                                                <div class="col-6">
                                                    <p class="mb-1">LKR <?php echo number_format($subscription['monthly_amount']); ?></p>
                                                    <p class="mb-1"><?php echo date('M d, Y', strtotime($subscription['start_date'])); ?></p>
                                                    <p class="mb-1"><?php echo date('M d, Y', strtotime($subscription['next_payment_date'])); ?></p>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="subscription-status mb-3">
                                            <span class="badge bg-<?php echo $subscription['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                                <?php echo ucfirst($subscription['status']); ?>
                                            </span>
                                            <?php if ($subscription['auto_renew']): ?>
                                                <span class="badge bg-info ms-1">Auto-renew</span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="subscription-actions">
                                            <a href="subscription-details.php?id=<?php echo $subscription['id']; ?>" class="btn btn-outline-primary btn-sm">
                                                <i class="fas fa-eye me-1"></i>View Details
                                            </a>
                                            <?php if ($subscription['status'] === 'active'): ?>
                                                <button type="button" class="btn btn-outline-danger btn-sm" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#cancelModal<?php echo $subscription['id']; ?>">
                                                    <i class="fas fa-times me-1"></i>Cancel
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Cancel Modal -->
                            <div class="modal fade" id="cancelModal<?php echo $subscription['id']; ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Cancel Subscription</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <form method="POST">
                                            <div class="modal-body">
                                                <p>Are you sure you want to cancel this subscription?</p>
                                                <div class="mb-3">
                                                    <label for="reason<?php echo $subscription['id']; ?>" class="form-label">Reason for cancellation (optional)</label>
                                                    <textarea class="form-control" id="reason<?php echo $subscription['id']; ?>" 
                                                              name="cancellation_reason" rows="3" 
                                                              placeholder="Please let us know why you're cancelling..."></textarea>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Keep Subscription</button>
                                                <button type="submit" name="cancel_subscription" class="btn btn-danger">
                                                    <i class="fas fa-times me-1"></i>Cancel Subscription
                                                </button>
                                                <input type="hidden" name="subscription_id" value="<?php echo $subscription['id']; ?>">
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
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
        .card {
            border-radius: 15px;
            transition: transform 0.2s ease;
        }
        
        .card:hover {
            transform: translateY(-2px);
        }
        
        .subscription-details {
            background-color: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
        }
        
        .subscription-actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
    </style>
</body>
</html>
