<?php
/**
 * Admin - Manage Payments
 */

require_once __DIR__ . '/../../config/config.php';

// Require admin login
require_admin();

$payment_model = new Payment();

// Messages
$message = '';
$message_type = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$action = $_POST['action'] ?? '';
	try {
		switch ($action) {
			case 'update_status':
				$id = (int)($_POST['id'] ?? 0);
				$status = $_POST['payment_status'] ?? '';
				if (!$id || $status === '') throw new Exception('Invalid request');
				if ($payment_model->update($id, ['payment_status' => $status])) {
					$message = 'Payment status updated';
					$message_type = 'success';
				} else {
					throw new Exception('Failed to update status');
				}
				break;
			case 'mark_completed':
				$id = (int)($_POST['id'] ?? 0);
				if (!$id) throw new Exception('Invalid payment');
				$paid_date = $_POST['paid_date'] ?? null;
				if ($payment_model->markCompleted($id, $paid_date)) {
					$message = 'Payment marked as completed';
					$message_type = 'success';
				} else {
					throw new Exception('Failed to mark completed');
				}
				break;
			case 'mark_failed':
				$id = (int)($_POST['id'] ?? 0);
				if (!$id) throw new Exception('Invalid payment');
				if ($payment_model->markFailed($id)) {
					$message = 'Payment marked as failed';
					$message_type = 'success';
				} else {
					throw new Exception('Failed to mark failed');
				}
				break;
			case 'refund':
				$id = (int)($_POST['id'] ?? 0);
				$amount = isset($_POST['refund_amount']) && $_POST['refund_amount'] !== '' ? (float)$_POST['refund_amount'] : null;
				if (!$id) throw new Exception('Invalid payment');
				if ($payment_model->refund($id, $amount)) {
					$message = 'Payment refunded';
					$message_type = 'success';
				} else {
					throw new Exception('Failed to refund');
				}
				break;
			case 'delete':
				$id = (int)($_POST['id'] ?? 0);
				if (!$id) throw new Exception('Invalid payment');
				if ($payment_model->delete($id)) {
					$message = 'Payment deleted';
					$message_type = 'success';
				} else {
					throw new Exception('Failed to delete payment');
				}
				break;
		}
	} catch (Exception $e) {
		$message = $e->getMessage();
		$message_type = 'danger';
	}
}

// Filters & pagination
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 20;
$status_filter = $_GET['status'] ?? '';
$search_query = trim($_GET['search'] ?? '');

if ($search_query !== '') {
	$payments = $payment_model->search($search_query, $status_filter ?: null, $page, $limit);
	$total_payments = $payment_model->getCount($status_filter ?: null);
} else {
	$payments = $payment_model->getAll($page, $limit, $status_filter ?: null);
	$total_payments = $payment_model->getCount($status_filter ?: null);
}

$total_pages = max(1, (int)ceil($total_payments / $limit));

?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Payments - <?php echo APP_NAME; ?></title>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
	<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body>
	<div class="container-fluid">
		<div class="row">
			<?php $active_menu = 'payments'; include __DIR__ . '/_sidebar.php'; ?>

			<div class="col-md-9 col-lg-10">
				<div class="p-4">
					<div class="d-flex justify-content-between align-items-center mb-4">
						<h1 class="h3 mb-0">Payments</h1>
					</div>

					<?php if ($message): ?>
						<div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
							<?php echo htmlspecialchars($message); ?>
							<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
						</div>
					<?php endif; ?>

					<div class="card mb-4">
						<div class="card-body">
							<form method="GET" class="row g-3">
								<div class="col-md-6">
									<label for="search" class="form-label">Search</label>
									<input type="text" class="form-control" id="search" name="search" value="<?php echo htmlspecialchars($search_query); ?>" placeholder="Customer, phone, email, property, city, owner, reference">
								</div>
								<div class="col-md-3">
									<label for="status" class="form-label">Status</label>
									<select class="form-select" id="status" name="status">
										<option value="">All</option>
										<?php foreach (['pending','completed','failed','refunded'] as $st): ?>
											<option value="<?php echo $st; ?>" <?php echo ($status_filter===$st)?'selected':''; ?>><?php echo ucfirst($st); ?></option>
										<?php endforeach; ?>
									</select>
								</div>
								<div class="col-md-3">
									<label class="form-label d-none d-md-block">&nbsp;</label>
									<div class="d-grid">
										<button type="submit" class="btn btn-outline-primary">
											<i class="fas fa-filter me-2"></i>Filter
										</button>
									</div>
								</div>
							</form>
						</div>
					</div>

					<div class="card">
						<div class="card-header d-flex justify-content-between align-items-center">
							<h5 class="mb-0">Payments List (<?php echo (int)$total_payments; ?> total)</h5>
						</div>
						<div class="card-body">
							<?php if (empty($payments)): ?>
								<div class="text-center py-4">
									<i class="fas fa-credit-card fa-3x text-muted mb-3"></i>
									<p class="text-muted">No payments found</p>
								</div>
							<?php else: ?>
								<div class="table-responsive">
									<table class="table table-hover align-middle">
										<thead>
											<tr>
												<th>ID</th>
												<th>Property</th>
												<th>Customer</th>
												<th>Owner</th>
												<th>Amount</th>
												<th>Commission</th>
												<th>Payout</th>
												<th>Method</th>
												<th>Status</th>
												<th>Due</th>
												<th>Paid</th>
												<th>Created</th>
												<th>Actions</th>
											</tr>
										</thead>
										<tbody>
											<?php foreach ($payments as $p): ?>
												<tr>
													<td><?php echo (int)$p['id']; ?></td>
													<td>
														<div class="fw-semibold"><?php echo htmlspecialchars($p['property_title'] ?? ''); ?></div>
														<small class="text-muted"><?php echo htmlspecialchars(($p['property_address'] ?? '') . (isset($p['property_city']) && $p['property_city'] !== '' ? ', ' . $p['property_city'] : '')); ?></small>
													</td>
													<td>
														<div class="fw-semibold"><?php echo htmlspecialchars($p['customer_name'] ?? ''); ?></div>
														<small class="text-muted"><?php echo htmlspecialchars($p['customer_phone'] ?? ''); ?></small>
													</td>
													<td><?php echo htmlspecialchars($p['owner_name'] ?? ''); ?></td>
													<td><?php echo 'LKR ' . number_format((float)($p['amount'] ?? 0), 2); ?></td>
													<td><?php echo 'LKR ' . number_format((float)($p['commission_amount'] ?? 0), 2); ?></td>
													<td><?php echo 'LKR ' . number_format((float)($p['owner_payout_amount'] ?? 0), 2); ?></td>
													<td><?php echo htmlspecialchars($p['payment_method'] ?? ''); ?></td>
													<td>
														<?php $st = $p['payment_status'] ?? 'pending';
														$cls = ($st==='completed')?'success':(($st==='pending')?'warning':(($st==='failed')?'danger':(($st==='refunded')?'secondary':'dark'))); ?>
														<span class="badge bg-<?php echo $cls; ?>"><?php echo ucfirst($st); ?></span>
													</td>
													<td><?php echo htmlspecialchars($p['due_date'] ?? '-'); ?></td>
													<td><?php echo htmlspecialchars($p['paid_date'] ?? '-'); ?></td>
													<td><?php echo isset($p['created_at']) ? format_date($p['created_at']) : ''; ?></td>
													<td>
														<div class="btn-group btn-group-sm" role="group">
															<button type="button" class="btn btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
																Update Status
															</button>
															<ul class="dropdown-menu">
																<?php foreach (['pending','completed','failed','refunded'] as $st2): ?>
																	<li>
																		<form method="post" class="px-3 py-1">
																			<input type="hidden" name="action" value="update_status">
																			<input type="hidden" name="id" value="<?php echo (int)$p['id']; ?>">
																			<input type="hidden" name="payment_status" value="<?php echo $st2; ?>">
																			<button type="submit" class="dropdown-item <?php echo (($p['payment_status'] ?? '') === $st2)?'active':''; ?>"><?php echo ucfirst($st2); ?></button>
																		</form>
																	</li>
																<?php endforeach; ?>
															</ul>
														</div>
														<form method="post" class="d-inline" onsubmit="return confirm('Mark this payment as completed?');">
															<input type="hidden" name="action" value="mark_completed">
															<input type="hidden" name="id" value="<?php echo (int)$p['id']; ?>">
															<button type="submit" class="btn btn-outline-success btn-sm ms-1">Complete</button>
														</form>
														<form method="post" class="d-inline" onsubmit="return confirm('Mark this payment as failed?');">
															<input type="hidden" name="action" value="mark_failed">
															<input type="hidden" name="id" value="<?php echo (int)$p['id']; ?>">
															<button type="submit" class="btn btn-outline-warning btn-sm ms-1">Fail</button>
														</form>
														<form method="post" class="d-inline" onsubmit="return confirm('Refund this payment?');">
															<input type="hidden" name="action" value="refund">
															<input type="hidden" name="id" value="<?php echo (int)$p['id']; ?>">
															<input type="number" step="0.01" min="0" name="refund_amount" class="form-control d-inline-block ms-1" style="width: 110px;" placeholder="Amount">
															<button type="submit" class="btn btn-outline-secondary btn-sm ms-1">Refund</button>
														</form>
														<form method="post" class="d-inline" onsubmit="return confirm('Delete payment #<?php echo (int)$p['id']; ?>? This cannot be undone.');">
															<input type="hidden" name="action" value="delete">
															<input type="hidden" name="id" value="<?php echo (int)$p['id']; ?>">
															<button type="submit" class="btn btn-outline-danger btn-sm ms-1">Delete</button>
														</form>
													</td>
												</tr>
											<?php endforeach; ?>
										</tbody>
									</table>
								</div>

								<?php if ($total_pages > 1): ?>
									<nav aria-label="Payments pagination">
										<ul class="pagination justify-content-center">
											<?php if ($page > 1): ?>
												<li class="page-item">
													<a class="page-link" href="?page=<?php echo $page - 1; ?>&status=<?php echo urlencode($status_filter); ?>&search=<?php echo urlencode($search_query); ?>">Previous</a>
												</li>
											<?php endif; ?>
											<?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
												<li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
													<a class="page-link" href="?page=<?php echo $i; ?>&status=<?php echo urlencode($status_filter); ?>&search=<?php echo urlencode($search_query); ?>"><?php echo $i; ?></a>
												</li>
											<?php endfor; ?>
											<?php if ($page < $total_pages): ?>
												<li class="page-item">
													<a class="page-link" href="?page=<?php echo $page + 1; ?>&status=<?php echo urlencode($status_filter); ?>&search=<?php echo urlencode($search_query); ?>">Next</a>
												</li>
											<?php endif; ?>
										</ul>
									</nav>
								<?php endif; ?>
							<?php endif; ?>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>

	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>


