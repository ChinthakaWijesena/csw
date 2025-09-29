-- =====================================================
-- INITIALIZE DEFAULT SETTINGS
-- =====================================================
-- This script initializes default system settings
-- =====================================================

USE `renting_place_finder`;

-- Clear existing settings (optional - uncomment if needed)
-- DELETE FROM system_settings;

-- =====================================================
-- GENERAL SETTINGS
-- =====================================================
INSERT INTO `system_settings` (`setting_key`, `setting_value`, `description`) VALUES
('app_name', 'Renting Place Finder', 'Application name'),
('app_description', 'Renting Place Finder - Find your perfect rental property', 'Application description'),
('app_email', 'admin@rentingplacefinder.com', 'Contact email address'),
('app_phone', '0713018095', 'Contact phone number'),
('app_address', 'Colombo, Sri Lanka', 'Application address'),
('commission_rate', '5.0', 'Commission rate percentage');

-- =====================================================
-- PAYMENT SETTINGS
-- =====================================================
INSERT INTO `system_settings` (`setting_key`, `setting_value`, `description`) VALUES
('stripe_public_key', '', 'Stripe publishable key'),
('stripe_secret_key', '', 'Stripe secret key'),
('payhere_merchant_id', '', 'PayHere merchant ID'),
('payhere_secret', '', 'PayHere secret key');

-- =====================================================
-- SMS SETTINGS
-- =====================================================
INSERT INTO `system_settings` (`setting_key`, `setting_value`, `description`) VALUES
('sms_enabled', '1', 'Enable SMS notifications'),
('vonage_api_key', 'e2a3b2dc', 'Vonage API key'),
('vonage_api_secret', '1VqPSEzbe586lAPfUqfed9hBX19v4dO179bgFU2FCfwDHHpOaT', 'Vonage API secret'),
('vonage_from_number', 'RentingPlace', 'Vonage sender number');

-- =====================================================
-- EMAIL SETTINGS
-- =====================================================
INSERT INTO `system_settings` (`setting_key`, `setting_value`, `description`) VALUES
('email_enabled', '1', 'Enable email notifications'),
('smtp_host', 'smtp.gmail.com', 'SMTP host'),
('smtp_port', '587', 'SMTP port'),
('smtp_username', '', 'SMTP username'),
('smtp_password', '', 'SMTP password'),
('smtp_encryption', 'tls', 'SMTP encryption');

-- =====================================================
-- SECURITY SETTINGS
-- =====================================================
INSERT INTO `system_settings` (`setting_key`, `setting_value`, `description`) VALUES
('session_timeout', '86400', 'Session timeout in seconds'),
('max_login_attempts', '5', 'Maximum login attempts'),
('password_min_length', '8', 'Minimum password length'),
('require_2fa', '1', 'Require two-factor authentication'),
('otp_bypass', '1', 'OTP bypass for development');

-- =====================================================
-- VERIFICATION
-- =====================================================
SELECT 'Settings initialized successfully!' as message;
SELECT COUNT(*) as total_settings FROM system_settings;
SELECT setting_key, setting_value FROM system_settings ORDER BY setting_key;
