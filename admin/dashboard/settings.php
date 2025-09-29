<?php
/**
 * Admin - Settings
 */

require_once __DIR__ . '/../../config/config.php';

// Require admin login
require_admin();

// Load current settings via model directly for initial render
$settingsModel = new Settings();
$grouped = $settingsModel->getForAdmin();

// Helper to read setting values safely
function sval($grouped, $key, $default = '') {
	foreach ($grouped as $g) {
		if (isset($g[$key]['value'])) return htmlspecialchars($g[$key]['value']);
	}
	return htmlspecialchars($default);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Settings - <?php echo APP_NAME; ?></title>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
	<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body>
	<div class="container-fluid">
		<div class="row">
			<?php $active_menu = 'settings'; include __DIR__ . '/_sidebar.php'; ?>

			<div class="col-md-9 col-lg-10">
				<div class="p-4">
					<div class="d-flex justify-content-between align-items-center mb-4">
						<h1 class="h3 mb-0">System Settings</h1>
						<button id="btnRefresh" class="btn btn-outline-secondary"><i class="fas fa-rotate me-2"></i>Refresh</button>
					</div>

					<div id="alertBox" class="d-none"></div>

					<ul class="nav nav-tabs" id="settingsTabs" role="tablist">
						<li class="nav-item" role="presentation">
							<button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-general" type="button" role="tab">General</button>
						</li>
						<li class="nav-item" role="presentation">
							<button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-payment" type="button" role="tab">Payment</button>
						</li>
						<li class="nav-item" role="presentation">
							<button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-sms" type="button" role="tab">SMS</button>
						</li>
						<li class="nav-item" role="presentation">
							<button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-email" type="button" role="tab">Email</button>
						</li>
						<li class="nav-item" role="presentation">
							<button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-security" type="button" role="tab">Security</button>
						</li>
					</ul>

					<div class="tab-content pt-3">
						<!-- General -->
						<div class="tab-pane fade show active" id="tab-general" role="tabpanel">
							<div class="card">
								<div class="card-header">General Settings</div>
								<div class="card-body">
									<form id="formGeneral" class="row g-3">
										<input type="hidden" name="action" value="update_general_settings">
										<div class="col-md-6">
											<label class="form-label">App Name</label>
											<input type="text" class="form-control" name="app_name" value="<?php echo sval($grouped, 'app_name', APP_NAME); ?>" required>
										</div>
										<div class="col-md-6">
											<label class="form-label">Commission Rate (%)</label>
											<input type="number" step="0.01" min="0" class="form-control" name="commission_rate" value="<?php echo sval($grouped, 'commission_rate', '5.0'); ?>" required>
										</div>
										<div class="col-12">
											<label class="form-label">Description</label>
											<textarea class="form-control" name="app_description" rows="2"><?php echo sval($grouped, 'app_description', ''); ?></textarea>
										</div>
										<div class="col-md-4">
											<label class="form-label">Email</label>
											<input type="email" class="form-control" name="app_email" value="<?php echo sval($grouped, 'app_email', ''); ?>" required>
										</div>
										<div class="col-md-4">
											<label class="form-label">Phone</label>
											<input type="tel" class="form-control" name="app_phone" value="<?php echo sval($grouped, 'app_phone', ''); ?>" required>
										</div>
										<div class="col-md-4">
											<label class="form-label">Address</label>
											<input type="text" class="form-control" name="app_address" value="<?php echo sval($grouped, 'app_address', ''); ?>">
										</div>
										<div class="col-12 d-flex gap-2">
											<button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Save</button>
											<button type="button" id="btnInitDefaults" class="btn btn-outline-secondary"><i class="fas fa-sliders me-2"></i>Initialize Defaults</button>
										</div>
									</form>
								</div>
							</div>
						</div>

						<!-- Payment -->
						<div class="tab-pane fade" id="tab-payment" role="tabpanel">
							<div class="card">
								<div class="card-header">Payment Settings</div>
								<div class="card-body">
									<form id="formPayment" class="row g-3">
										<input type="hidden" name="action" value="update_payment_settings">
										<div class="col-md-6">
											<label class="form-label">Stripe Public Key</label>
											<input type="text" class="form-control" name="stripe_public_key" value="<?php echo sval($grouped, 'stripe_public_key', ''); ?>">
										</div>
										<div class="col-md-6">
											<label class="form-label">Stripe Secret Key</label>
											<input type="password" class="form-control" name="stripe_secret_key" value="<?php echo sval($grouped, 'stripe_secret_key', ''); ?>">
										</div>
										<div class="col-md-6">
											<label class="form-label">PayHere Merchant ID</label>
											<input type="text" class="form-control" name="payhere_merchant_id" value="<?php echo sval($grouped, 'payhere_merchant_id', ''); ?>">
										</div>
										<div class="col-md-6">
											<label class="form-label">PayHere Secret</label>
											<input type="password" class="form-control" name="payhere_secret" value="<?php echo sval($grouped, 'payhere_secret', ''); ?>">
										</div>
										<div class="col-12">
											<button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Save</button>
										</div>
									</form>
								</div>
							</div>
						</div>

						<!-- SMS -->
						<div class="tab-pane fade" id="tab-sms" role="tabpanel">
							<div class="card">
								<div class="card-header">SMS Settings</div>
								<div class="card-body">
									<form id="formSMS" class="row g-3">
										<input type="hidden" name="action" value="update_sms_settings">
										<div class="col-md-4 d-flex align-items-center">
											<div class="form-check mt-4">
												<input class="form-check-input" type="checkbox" id="sms_enabled" name="sms_enabled" <?php echo (sval($grouped, 'sms_enabled', '1') === '1') ? 'checked' : ''; ?>>
												<label class="form-check-label" for="sms_enabled">Enable SMS</label>
											</div>
										</div>
										<div class="col-md-4">
											<label class="form-label">Vonage API Key</label>
											<input type="text" class="form-control" name="vonage_api_key" value="<?php echo sval($grouped, 'vonage_api_key', ''); ?>">
										</div>
										<div class="col-md-4">
											<label class="form-label">Vonage API Secret</label>
											<input type="password" class="form-control" name="vonage_api_secret" value="<?php echo sval($grouped, 'vonage_api_secret', ''); ?>">
										</div>
										<div class="col-md-6">
											<label class="form-label">From Number</label>
											<input type="text" class="form-control" name="vonage_from_number" value="<?php echo sval($grouped, 'vonage_from_number', ''); ?>">
										</div>
										<div class="col-md-6 d-flex align-items-end">
											<div class="input-group">
												<input type="tel" class="form-control" id="test_phone" placeholder="07XXXXXXXX">
												<button type="button" id="btnTestSMS" class="btn btn-outline-primary"><i class="fas fa-paper-plane me-2"></i>Test SMS</button>
											</div>
										</div>
										<div class="col-12">
											<button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Save</button>
										</div>
									</form>
								</div>
							</div>
						</div>

						<!-- Email -->
						<div class="tab-pane fade" id="tab-email" role="tabpanel">
							<div class="card">
								<div class="card-header">Email Settings</div>
								<div class="card-body">
									<form id="formEmail" class="row g-3">
										<input type="hidden" name="action" value="update_email_settings">
										<div class="col-md-3 d-flex align-items-center">
											<div class="form-check mt-4">
												<input class="form-check-input" type="checkbox" id="email_enabled" name="email_enabled" <?php echo (sval($grouped, 'email_enabled', '1') === '1') ? 'checked' : ''; ?>>
												<label class="form-check-label" for="email_enabled">Enable Email</label>
											</div>
										</div>
										<div class="col-md-3">
											<label class="form-label">SMTP Host</label>
											<input type="text" class="form-control" name="smtp_host" value="<?php echo sval($grouped, 'smtp_host', 'smtp.gmail.com'); ?>">
										</div>
										<div class="col-md-2">
											<label class="form-label">SMTP Port</label>
											<input type="number" class="form-control" name="smtp_port" value="<?php echo sval($grouped, 'smtp_port', '587'); ?>">
										</div>
										<div class="col-md-2">
											<label class="form-label">Encryption</label>
											<select class="form-select" name="smtp_encryption">
												<?php foreach (['tls','ssl','none'] as $enc): ?>
													<option value="<?php echo $enc; ?>" <?php echo (sval($grouped, 'smtp_encryption', 'tls') === $enc)?'selected':''; ?>><?php echo strtoupper($enc); ?></option>
												<?php endforeach; ?>
											</select>
										</div>
										<div class="col-md-4">
											<label class="form-label">SMTP Username</label>
											<input type="text" class="form-control" name="smtp_username" value="<?php echo sval($grouped, 'smtp_username', ''); ?>">
										</div>
										<div class="col-md-4">
											<label class="form-label">SMTP Password</label>
											<input type="password" class="form-control" name="smtp_password" value="<?php echo sval($grouped, 'smtp_password', ''); ?>">
										</div>
										<div class="col-md-4 d-flex align-items-end">
											<div class="input-group">
												<input type="email" class="form-control" id="test_email" placeholder="name@example.com">
												<button type="button" id="btnTestEmail" class="btn btn-outline-primary"><i class="fas fa-paper-plane me-2"></i>Test Email</button>
											</div>
										</div>
										<div class="col-12">
											<button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Save</button>
										</div>
									</form>
								</div>
							</div>
						</div>

						<!-- Security -->
						<div class="tab-pane fade" id="tab-security" role="tabpanel">
							<div class="card">
								<div class="card-header">Security Settings</div>
								<div class="card-body">
									<form id="formSecurity" class="row g-3">
										<input type="hidden" name="action" value="update_security_settings">
										<div class="col-md-4">
											<label class="form-label">Session Timeout (sec)</label>
											<input type="number" class="form-control" name="session_timeout" value="<?php echo sval($grouped, 'session_timeout', '3600'); ?>">
										</div>
										<div class="col-md-4">
											<label class="form-label">Max Login Attempts</label>
											<input type="number" class="form-control" name="max_login_attempts" value="<?php echo sval($grouped, 'max_login_attempts', '5'); ?>">
										</div>
										<div class="col-md-4">
											<label class="form-label">Password Min Length</label>
											<input type="number" class="form-control" name="password_min_length" value="<?php echo sval($grouped, 'password_min_length', '8'); ?>">
										</div>
										<div class="col-md-6">
											<div class="form-check mt-4">
												<input class="form-check-input" type="checkbox" id="require_2fa" name="require_2fa" <?php echo (sval($grouped, 'require_2fa', '1') === '1') ? 'checked' : ''; ?>>
												<label class="form-check-label" for="require_2fa">Require 2FA</label>
											</div>
										</div>
										<div class="col-md-6">
											<div class="form-check mt-4">
												<input class="form-check-input" type="checkbox" id="otp_bypass" name="otp_bypass" <?php echo (sval($grouped, 'otp_bypass', '0') === '1') ? 'checked' : ''; ?>>
												<label class="form-check-label" for="otp_bypass">OTP Bypass (dev)</label>
											</div>
										</div>
										<div class="col-12 d-flex gap-2">
											<button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Save</button>
											<button type="button" id="btnClearCache" class="btn btn-outline-warning"><i class="fas fa-broom me-2"></i>Clear Cache</button>
											<button type="button" id="btnBackup" class="btn btn-outline-secondary"><i class="fas fa-database me-2"></i>Backup DB</button>
										</div>
									</form>
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
		const alertBox = document.getElementById('alertBox');
		function showAlert(type, message){
			alertBox.className = 'alert alert-' + type;
			alertBox.textContent = message;
			alertBox.classList.remove('d-none');
			setTimeout(()=>{ alertBox.classList.add('d-none'); }, 4000);
		}

		function submitForm(form){
			const data = new FormData(form);
			const payload = {};
			for (const [k,v] of data.entries()){
				if (['sms_enabled','email_enabled','require_2fa','otp_bypass'].includes(k)) {
					payload[k] = v === 'on' ? 1 : 0;
				} else {
					payload[k] = v;
				}
			}
			fetch('../../api/settings/update.php', {
				method: 'POST',
				headers: { 'Content-Type': 'application/json' },
				body: JSON.stringify(payload)
			})
			.then(r => r.json())
			.then(res => {
				if (res.success) showAlert('success', res.message || 'Saved');
				else showAlert('danger', res.message || 'Failed');
			})
			.catch(() => showAlert('danger', 'Request failed'));
		}

		document.getElementById('formGeneral').addEventListener('submit', function(e){ e.preventDefault(); submitForm(this); });
		document.getElementById('formPayment').addEventListener('submit', function(e){ e.preventDefault(); submitForm(this); });
		document.getElementById('formSMS').addEventListener('submit', function(e){ e.preventDefault(); submitForm(this); });
		document.getElementById('formEmail').addEventListener('submit', function(e){ e.preventDefault(); submitForm(this); });
		document.getElementById('formSecurity').addEventListener('submit', function(e){ e.preventDefault(); submitForm(this); });

		document.getElementById('btnTestSMS').addEventListener('click', function(){
			const phone = document.getElementById('test_phone').value.trim();
			fetch('../../api/settings/update.php', {
				method: 'POST', headers: { 'Content-Type': 'application/json' },
				body: JSON.stringify({ action: 'test_sms', test_phone: phone })
			}).then(r=>r.json()).then(res=>{
				showAlert(res.success ? 'success' : 'danger', res.message);
			});
		});

		document.getElementById('btnTestEmail').addEventListener('click', function(){
			const email = document.getElementById('test_email').value.trim();
			fetch('../../api/settings/update.php', {
				method: 'POST', headers: { 'Content-Type': 'application/json' },
				body: JSON.stringify({ action: 'test_email', test_email: email })
			}).then(r=>r.json()).then(res=>{
				showAlert(res.success ? 'success' : 'danger', res.message);
			});
		});

		document.getElementById('btnClearCache').addEventListener('click', function(){
			fetch('../../api/settings/update.php', {
				method: 'POST', headers: { 'Content-Type': 'application/json' },
				body: JSON.stringify({ action: 'clear_cache' })
			}).then(r=>r.json()).then(res=>{
				showAlert(res.success ? 'success' : 'danger', res.message);
			});
		});

		document.getElementById('btnBackup').addEventListener('click', function(){
			fetch('../../api/settings/update.php', {
				method: 'POST', headers: { 'Content-Type': 'application/json' },
				body: JSON.stringify({ action: 'backup_database' })
			}).then(r=>r.json()).then(res=>{
				showAlert(res.success ? 'success' : 'danger', res.message);
			});
		});

		document.getElementById('btnInitDefaults').addEventListener('click', function(){
			fetch('../../api/settings/get.php')
				.then(()=>{
					// initialize via controller (simple approach through update endpoint is not present; could create one)
					fetch('../../api/settings/update.php', {
						method: 'POST', headers: { 'Content-Type': 'application/json' },
						body: JSON.stringify({ action: 'update_general_settings', app_name: '<?php echo addslashes(APP_NAME); ?>', app_description: '', app_email: 'admin@example.com', app_phone: '0710000000', app_address: 'Colombo', commission_rate: 5.0 })
					}).then(()=>{ showAlert('success', 'Defaults applied for general settings'); });
				});
		});

		document.getElementById('btnRefresh').addEventListener('click', function(){ location.reload(); });
	</script>
</body>
</html>


