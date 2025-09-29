<?php
/**
 * Admin - Manage Bookings
 */

require_once __DIR__ . '/../../config/config.php';

// Require admin login
require_admin();

$booking_model = new Booking();

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
				$status = $_POST['status'] ?? '';
				if (!$id || !$status) throw new Exception('Invalid request');
				if ($booking_model->updateStatus($id, $status)) {
					$message = 'Booking status updated';
					$message_type = 'success';
				} else {
					throw new Exception('Failed to update status');
				}
				break;
			case 'terminate':
				$id = (int)($_POST['id'] ?? 0);
				$end_date = $_POST['end_date'] ?? null;
				if (!$id) throw new Exception('Invalid booking');
				if ($booking_model->terminate($id, $end_date)) {
					$message = 'Booking terminated';
					$message_type = 'success';
				} else {
					throw new Exception('Failed to terminate booking');
				}
				break;
			case 'delete':
				$id = (int)($_POST['id'] ?? 0);
				if (!$id) throw new Exception('Invalid booking');
				if ($booking_model->delete($id)) {
					$message = 'Booking deleted';
					$message_type = 'success';
				} else {
					throw new Exception('Failed to delete booking');
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

if (!empty($search_query)) {
	$bookings = $booking_model->search($search_query, $status_filter ?: null, $page, $limit);
	// Without a dedicated count for search, keep pagination simple
	$total_bookings = $booking_model->getCount($status_filter ?: null);
} else {
	$bookings = $booking_model->getAll($page, $limit, $status_filter ?: null);
	$total_bookings = $booking_model->getCount($status_filter ?: null);
}

$total_pages = max(1, (int)ceil($total_bookings / $limit));

?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Bookings - <?php echo APP_NAME; ?></title>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
	<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body>
	<div class="container-fluid">
		<div class="row">
			<?php $active_menu = 'bookings'; include __DIR__ . '/_sidebar.php'; ?>

			<div class="col-md-9 col-lg-10">
				<div class="p-4">
					<div class="d-flex justify-content-between align-items-center mb-4">
						<h1 class="h3 mb-0">Bookings</h1>
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
									<input type="text" class="form-control" id="search" name="search" value="<?php echo htmlspecialchars($search_query); ?>" placeholder="Customer, phone, email, property, address, city, owner">
								</div>
								<div class="col-md-3">
									<label for="status" class="form-label">Status</label>
									<select class="form-select" id="status" name="status">
										<option value="">All</option>
										<?php foreach (['active','completed','terminated','expired','cancelled'] as $st): ?>
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
							<h5 class="mb-0">Bookings List (<?php echo (int)$total_bookings; ?> total)</h5>
						</div>
						<div class="card-body">
							<?php if (empty($bookings)): ?>
								<div class="text-center py-4">
									<i class="fas fa-calendar fa-3x text-muted mb-3"></i>
									<p class="text-muted">No bookings found</p>
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
												<th>Start</th>
												<th>End</th>
												<th>Rent</th>
												<th>Status</th>
												<th>Created</th>
												<th>Actions</th>
											</tr>
										</thead>
										<tbody>
											<?php foreach ($bookings as $b): ?>
												<tr>
													<td><?php echo (int)$b['id']; ?></td>
													<td>
														<div class="fw-semibold"><?php echo htmlspecialchars($b['property_title'] ?? ''); ?></div>
														<small class="text-muted"><?php echo htmlspecialchars(($b['property_address'] ?? '') . (isset($b['property_city']) && $b['property_city'] !== '' ? ', ' . $b['property_city'] : '')); ?></small>
													</td>
													<td>
														<div class="fw-semibold"><?php echo htmlspecialchars($b['customer_name'] ?? ''); ?></div>
														<small class="text-muted"><?php echo htmlspecialchars($b['customer_phone'] ?? ''); ?></small>
													</td>
													<td><?php echo htmlspecialchars($b['owner_name'] ?? ''); ?></td>
													<td><?php echo htmlspecialchars($b['start_date'] ?? ''); ?></td>
													<td><?php echo htmlspecialchars($b['end_date'] ?? '-'); ?></td>
													<td><?php echo $b['monthly_rent'] !== null ? 'LKR ' . number_format((float)$b['monthly_rent']) : '-'; ?></td>
													<td>
														<span class="badge bg-<?php echo ($b['status']==='active')?'success':(($b['status']==='pending')?'warning':(($b['status']==='completed')?'primary':(($b['status']==='terminated' || $b['status']==='cancelled')?'secondary':'dark'))); ?>">
															<?php echo htmlspecialchars(ucfirst($b['status'])); ?>
														</span>
													</td>
													<td><?php echo isset($b['created_at']) ? format_date($b['created_at']) : ''; ?></td>
													<td>
														<div class="btn-group btn-group-sm" role="group">
															<button type="button" class="btn btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
																Update Status
															</button>
															<ul class="dropdown-menu">
																<?php foreach (['active','completed','terminated','expired','cancelled'] as $st): ?>
																	<li>
																		<form method="post" class="px-3 py-1">
																			<input type="hidden" name="action" value="update_status">
																			<input type="hidden" name="id" value="<?php echo (int)$b['id']; ?>">
																			<input type="hidden" name="status" value="<?php echo $st; ?>">
																			<button type="submit" class="dropdown-item <?php echo ($b['status']===$st)?'active':''; ?>"><?php echo ucfirst($st); ?></button>
																		</form>
																	</li>
																<?php endforeach; ?>
															</ul>
														</div>
														<form method="post" class="d-inline" onsubmit="return confirm('Terminate this booking?');">
															<input type="hidden" name="action" value="terminate">
															<input type="hidden" name="id" value="<?php echo (int)$b['id']; ?>">
															<button type="submit" class="btn btn-outline-warning btn-sm ms-1">Terminate</button>
														</form>
														<form method="post" class="d-inline" onsubmit="return confirm('Delete booking #<?php echo (int)$b['id']; ?>? This cannot be undone.');">
															<input type="hidden" name="action" value="delete">
															<input type="hidden" name="id" value="<?php echo (int)$b['id']; ?>">
															<button type="submit" class="btn btn-outline-danger btn-sm ms-1">Delete</button>
														</form>
													</td>
												</tr>
											<?php endforeach; ?>
										</tbody>
									</table>
								</div>

								<?php if ($total_pages > 1): ?>
									<nav aria-label="Bookings pagination">
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


