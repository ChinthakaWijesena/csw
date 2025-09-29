<?php
/**
 * Admin - Reports & Analytics
 */

require_once __DIR__ . '/../../config/config.php';

// Require admin login
require_admin();

$payment_model = new Payment();
$booking_model = new Booking();

// Date range for analytics (defaults: last 90 days)
$date_from = $_GET['from'] ?? date('Y-m-d', strtotime('-90 days'));
$date_to = $_GET['to'] ?? date('Y-m-d');

// Gather stats with safe fallbacks
try { $payment_stats = $payment_model->getStats(); } catch (Exception $e) { $payment_stats = []; }
try { $booking_stats = $booking_model->getStats(); } catch (Exception $e) { $booking_stats = []; }

// Monthly revenue trend (12 months)
try { $monthly_trends = $payment_model->getMonthlyTrends(12); } catch (Exception $e) { $monthly_trends = []; }

// Payment method distribution
try { $method_distribution = $payment_model->getPaymentMethodDistribution(); } catch (Exception $e) { $method_distribution = []; }

// Recent lists
try { $recent_payments = $payment_model->getRecentPayments(30); } catch (Exception $e) { $recent_payments = []; }
try { $recent_bookings = $booking_model->getRecentBookings(30); } catch (Exception $e) { $recent_bookings = []; }

// Normalize stats
$total_revenue = (float)($payment_stats['total_revenue'] ?? 0);
$total_commission = (float)($payment_stats['total_commission'] ?? 0);
$total_payouts = (float)($payment_stats['total_payouts'] ?? 0);
$total_payments = (int)($payment_stats['total'] ?? 0);
$completed_payments = (int)($payment_stats['completed'] ?? 0);
$pending_payments = (int)($payment_stats['pending'] ?? 0);

$total_bookings = (int)($booking_stats['total'] ?? 0);
$active_bookings = (int)($booking_stats['active'] ?? 0);
$terminated_bookings = (int)($booking_stats['terminated'] ?? 0);
$avg_rent = (float)($booking_stats['avg_rent'] ?? 0);

// Chart data
$months = array_keys($monthly_trends);
$revenues = array_values($monthly_trends);

// Payment method chart data
$method_labels = array_keys($method_distribution);
$method_counts = array_values($method_distribution);

?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Reports - <?php echo APP_NAME; ?></title>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
	<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
	<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
	<div class="container-fluid">
		<div class="row">
			<?php $active_menu = 'reports'; include __DIR__ . '/_sidebar.php'; ?>

			<div class="col-md-9 col-lg-10">
				<div class="p-4">
					<div class="d-flex justify-content-between align-items-center mb-4">
						<h1 class="h3 mb-0">Reports & Analytics</h1>
						<form method="get" class="d-flex gap-2 align-items-center">
							<input type="date" name="from" class="form-control" value="<?php echo htmlspecialchars($date_from); ?>">
							<input type="date" name="to" class="form-control" value="<?php echo htmlspecialchars($date_to); ?>">
							<button class="btn btn-outline-primary" type="submit"><i class="fas fa-filter me-2"></i>Apply</button>
						</form>
					</div>

					<!-- KPI Cards -->
					<div class="row g-3 mb-4">
						<div class="col-md-3">
							<div class="card text-white bg-success">
								<div class="card-body">
									<div class="d-flex justify-content-between align-items-center">
										<div>
											<h4 class="mb-0">LKR <?php echo number_format($total_revenue, 0); ?></h4>
											<small>Total Revenue</small>
										</div>
										<i class="fas fa-coins fa-2x"></i>
									</div>
								</div>
							</div>
						</div>
						<div class="col-md-3">
							<div class="card text-white bg-primary">
								<div class="card-body">
									<div class="d-flex justify-content-between align-items-center">
										<div>
											<h4 class="mb-0">LKR <?php echo number_format($total_payouts, 0); ?></h4>
											<small>Owner Payouts</small>
										</div>
										<i class="fas fa-hand-holding-usd fa-2x"></i>
									</div>
								</div>
							</div>
						</div>
						<div class="col-md-3">
							<div class="card text-white bg-warning">
								<div class="card-body">
									<div class="d-flex justify-content-between align-items-center">
										<div>
											<h4 class="mb-0"><?php echo number_format($total_payments); ?></h4>
											<small>Total Payments</small>
										</div>
										<i class="fas fa-credit-card fa-2x"></i>
									</div>
								</div>
							</div>
						</div>
						<div class="col-md-3">
							<div class="card text-white bg-info">
								<div class="card-body">
									<div class="d-flex justify-content-between align-items-center">
										<div>
											<h4 class="mb-0"><?php echo number_format($total_bookings); ?></h4>
											<small>Total Bookings</small>
										</div>
										<i class="fas fa-calendar-check fa-2x"></i>
									</div>
								</div>
							</div>
						</div>
					</div>

					<!-- Charts -->
					<div class="row mb-4">
						<div class="col-lg-8 mb-4">
							<div class="card">
								<div class="card-header d-flex justify-content-between align-items-center">
									<h5 class="mb-0">Monthly Revenue (Last 12 months)</h5>
								</div>
								<div class="card-body">
									<canvas id="revenueChart" height="100"></canvas>
								</div>
							</div>
						</div>
						<div class="col-lg-4 mb-4">
							<div class="card">
								<div class="card-header d-flex justify-content-between align-items-center">
									<h5 class="mb-0">Payment Methods</h5>
								</div>
								<div class="card-body">
									<canvas id="methodChart"></canvas>
								</div>
							</div>
						</div>
					</div>

					<!-- Recent Activity -->
					<div class="row">
						<div class="col-lg-6 mb-4">
							<div class="card">
								<div class="card-header d-flex justify-content-between align-items-center">
									<h5 class="mb-0">Recent Payments</h5>
									<a href="payments.php" class="btn btn-sm btn-outline-primary">View All</a>
								</div>
								<div class="card-body">
									<?php if (empty($recent_payments)): ?>
										<div class="text-center py-3 text-muted">No recent payments</div>
									<?php else: ?>
										<div class="table-responsive">
											<table class="table table-sm table-hover">
												<thead>
													<tr>
														<th>Property</th>
														<th>Customer</th>
														<th>Amount</th>
														<th>Status</th>
														<th>Date</th>
													</tr>
												</thead>
												<tbody>
													<?php foreach ($recent_payments as $p): ?>
														<tr>
															<td><?php echo htmlspecialchars($p['property_title'] ?? ''); ?></td>
															<td><?php echo htmlspecialchars($p['customer_name'] ?? ''); ?></td>
															<td><?php echo 'LKR ' . number_format((float)($p['amount'] ?? 0)); ?></td>
															<td><?php echo htmlspecialchars(ucfirst($p['payment_status'] ?? '')); ?></td>
															<td><?php echo isset($p['created_at']) ? format_date($p['created_at']) : ''; ?></td>
														</tr>
													<?php endforeach; ?>
												</tbody>
											</table>
										</div>
									<?php endif; ?>
								</div>
							</div>
						</div>
						<div class="col-lg-6 mb-4">
							<div class="card">
								<div class="card-header d-flex justify-content-between align-items-center">
									<h5 class="mb-0">Recent Bookings</h5>
									<a href="bookings.php" class="btn btn-sm btn-outline-primary">View All</a>
								</div>
								<div class="card-body">
									<?php if (empty($recent_bookings)): ?>
										<div class="text-center py-3 text-muted">No recent bookings</div>
									<?php else: ?>
										<div class="table-responsive">
											<table class="table table-sm table-hover">
												<thead>
													<tr>
														<th>Property</th>
														<th>Customer</th>
														<th>Rent</th>
														<th>Status</th>
														<th>Date</th>
													</tr>
												</thead>
												<tbody>
													<?php foreach ($recent_bookings as $b): ?>
														<tr>
															<td><?php echo htmlspecialchars($b['property_title'] ?? ''); ?></td>
															<td><?php echo htmlspecialchars($b['customer_name'] ?? ''); ?></td>
															<td><?php echo $b['monthly_rent'] !== null ? ('LKR ' . number_format((float)$b['monthly_rent'])) : '-'; ?></td>
															<td><?php echo htmlspecialchars(ucfirst($b['status'] ?? '')); ?></td>
															<td><?php echo isset($b['created_at']) ? format_date($b['created_at']) : ''; ?></td>
														</tr>
													<?php endforeach; ?>
												</tbody>
											</table>
										</div>
									<?php endif; ?>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>

	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
	<script>
		// Revenue chart
		(function(){
			var ctx = document.getElementById('revenueChart');
			if (!ctx) return;
			var chart = new Chart(ctx, {
				type: 'line',
				data: {
					labels: <?php echo json_encode(array_values($months)); ?>,
					datasets: [{
						label: 'Revenue (LKR)',
						data: <?php echo json_encode(array_values($revenues)); ?>,
						borderColor: 'rgb(54, 162, 235)',
						backgroundColor: 'rgba(54, 162, 235, 0.2)',
						tension: 0.2,
						fill: true
					}]
				},
				options: {
					responsive: true,
					maintainAspectRatio: false,
					scales: { y: { beginAtZero: true } }
				}
			});
		})();

		// Payment method chart
		(function(){
			var ctx = document.getElementById('methodChart');
			if (!ctx) return;
			new Chart(ctx, {
				type: 'doughnut',
				data: {
					labels: <?php echo json_encode(array_values($method_labels)); ?>,
					datasets: [{
						data: <?php echo json_encode(array_values($method_counts)); ?>,
						backgroundColor: ['#36A2EB','#FF6384','#FFCE56','#4BC0C0','#9966FF','#8BC34A']
					}]
				},
				options: {
					plugins: { legend: { position: 'bottom' } },
					maintainAspectRatio: false,
					responsive: true
				}
			});
		})();
	</script>
</body>
</html>


