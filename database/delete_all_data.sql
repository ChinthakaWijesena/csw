-- =====================================================
-- DELETE ALL DATA FROM RENTING PLACE FINDER DATABASE
-- =====================================================
-- This script deletes all data from all tables in the correct order
-- to respect foreign key constraints
-- =====================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

USE `renting_place_finder`;

-- =====================================================
-- DISABLE FOREIGN KEY CHECKS TEMPORARILY
-- =====================================================
SET FOREIGN_KEY_CHECKS = 0;

-- =====================================================
-- DELETE DATA FROM ALL TABLES
-- =====================================================
-- Order: Child tables first, then parent tables

-- Delete from child tables (no foreign key dependencies)
DELETE FROM `admin_logs`;
DELETE FROM `booking_clicks`;
DELETE FROM `notifications`;
DELETE FROM `user_favorites`;
DELETE FROM `property_availability`;
DELETE FROM `property_reviews`;
DELETE FROM `property_amenities`;
DELETE FROM `owner_payouts`;
DELETE FROM `rent_payments`;
DELETE FROM `subscriptions`;
DELETE FROM `rental_bookings`;
DELETE FROM `visit_requests`;
DELETE FROM `search_access`;
DELETE FROM `property_images`;
DELETE FROM `properties`;
DELETE FROM `user_sessions`;
DELETE FROM `otp_verifications`;
DELETE FROM `users`;

-- Delete from reference/lookup tables
DELETE FROM `cities`;
DELETE FROM `districts`;
DELETE FROM `provinces`;
DELETE FROM `property_types`;
DELETE FROM `system_settings`;

-- =====================================================
-- RESET AUTO_INCREMENT COUNTERS
-- =====================================================
-- Reset all AUTO_INCREMENT values to start from 1
ALTER TABLE `admin_logs` AUTO_INCREMENT = 1;
ALTER TABLE `booking_clicks` AUTO_INCREMENT = 1;
ALTER TABLE `notifications` AUTO_INCREMENT = 1;
ALTER TABLE `user_favorites` AUTO_INCREMENT = 1;
ALTER TABLE `property_availability` AUTO_INCREMENT = 1;
ALTER TABLE `property_reviews` AUTO_INCREMENT = 1;
ALTER TABLE `property_amenities` AUTO_INCREMENT = 1;
ALTER TABLE `owner_payouts` AUTO_INCREMENT = 1;
ALTER TABLE `rent_payments` AUTO_INCREMENT = 1;
ALTER TABLE `subscriptions` AUTO_INCREMENT = 1;
ALTER TABLE `rental_bookings` AUTO_INCREMENT = 1;
ALTER TABLE `visit_requests` AUTO_INCREMENT = 1;
ALTER TABLE `search_access` AUTO_INCREMENT = 1;
ALTER TABLE `property_images` AUTO_INCREMENT = 1;
ALTER TABLE `properties` AUTO_INCREMENT = 1;
ALTER TABLE `user_sessions` AUTO_INCREMENT = 1;
ALTER TABLE `otp_verifications` AUTO_INCREMENT = 1;
ALTER TABLE `users` AUTO_INCREMENT = 1;
ALTER TABLE `cities` AUTO_INCREMENT = 1;
ALTER TABLE `districts` AUTO_INCREMENT = 1;
ALTER TABLE `provinces` AUTO_INCREMENT = 1;
ALTER TABLE `property_types` AUTO_INCREMENT = 1;
ALTER TABLE `system_settings` AUTO_INCREMENT = 1;

-- =====================================================
-- RE-ENABLE FOREIGN KEY CHECKS
-- =====================================================
SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- VERIFY DELETION
-- =====================================================
-- Show count of records in each table (should all be 0)
SELECT 'users' as table_name, COUNT(*) as record_count FROM users
UNION ALL
SELECT 'properties', COUNT(*) FROM properties
UNION ALL
SELECT 'rental_bookings', COUNT(*) FROM rental_bookings
UNION ALL
SELECT 'subscriptions', COUNT(*) FROM subscriptions
UNION ALL
SELECT 'rent_payments', COUNT(*) FROM rent_payments
UNION ALL
SELECT 'visit_requests', COUNT(*) FROM visit_requests
UNION ALL
SELECT 'property_images', COUNT(*) FROM property_images
UNION ALL
SELECT 'search_access', COUNT(*) FROM search_access
UNION ALL
SELECT 'user_sessions', COUNT(*) FROM user_sessions
UNION ALL
SELECT 'otp_verifications', COUNT(*) FROM otp_verifications
UNION ALL
SELECT 'admin_logs', COUNT(*) FROM admin_logs
UNION ALL
SELECT 'notifications', COUNT(*) FROM notifications
UNION ALL
SELECT 'user_favorites', COUNT(*) FROM user_favorites
UNION ALL
SELECT 'property_reviews', COUNT(*) FROM property_reviews
UNION ALL
SELECT 'property_amenities', COUNT(*) FROM property_amenities
UNION ALL
SELECT 'owner_payouts', COUNT(*) FROM owner_payouts
UNION ALL
SELECT 'booking_clicks', COUNT(*) FROM booking_clicks
UNION ALL
SELECT 'property_availability', COUNT(*) FROM property_availability
UNION ALL
SELECT 'cities', COUNT(*) FROM cities
UNION ALL
SELECT 'districts', COUNT(*) FROM districts
UNION ALL
SELECT 'provinces', COUNT(*) FROM provinces
UNION ALL
SELECT 'property_types', COUNT(*) FROM property_types
UNION ALL
SELECT 'system_settings', COUNT(*) FROM system_settings;

-- =====================================================
-- COMMIT TRANSACTION
-- =====================================================
COMMIT;

-- =====================================================
-- SUCCESS MESSAGE
-- =====================================================
SELECT 'All data has been successfully deleted from the database!' as message;
