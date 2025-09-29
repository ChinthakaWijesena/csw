-- =====================================================
-- SAMPLE DATA - MAXIMUM 10 RECORDS PER TABLE
-- =====================================================
-- Simplified sample data for testing and development
-- =====================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

USE `renting_place_finder`;

-- =====================================================
-- INSERT SAMPLE USERS (10 users)
-- =====================================================
INSERT INTO `users` (`phone`, `name`, `email`, `user_type`, `is_verified`, `is_active`) VALUES
('0713018095', 'Chinthaka Sandaruwan', 'chinthakasw000@gmail.com', 'admin', 1, 1),
('0771234567', 'John Silva', 'john.silva@email.com', 'owner', 1, 1),
('0772345678', 'Maria Fernando', 'maria.fernando@email.com', 'owner', 1, 1),
('0773456789', 'David Perera', 'david.perera@email.com', 'owner', 1, 1),
('0774567890', 'Sarah Rajapaksa', 'sarah.rajapaksa@email.com', 'owner', 1, 1),
('0775678901', 'Michael Jayawardena', 'michael.jayawardena@email.com', 'owner', 1, 1),
('0781111111', 'Alex Johnson', 'alex.johnson@email.com', 'customer', 1, 1),
('0782222222', 'Emma Wilson', 'emma.wilson@email.com', 'customer', 1, 1),
('0783333333', 'James Brown', 'james.brown@email.com', 'customer', 1, 1),
('0784444444', 'Lisa Davis', 'lisa.davis@email.com', 'customer', 1, 1);

-- =====================================================
-- INSERT SAMPLE PROPERTIES (10 properties)
-- =====================================================
INSERT INTO `properties` (`owner_id`, `title`, `description`, `property_type`, `bedrooms`, `bathrooms`, `area_sqft`, `monthly_rent`, `security_deposit`, `address`, `city`, `state`, `zip_code`, `latitude`, `longitude`, `is_available`, `is_verified`, `is_approved`) VALUES
(2, 'Modern 2BR Apartment in Colombo 7', 'Beautiful modern apartment in the heart of Colombo 7. Features 2 bedrooms, 2 bathrooms, modern kitchen, and balcony with city views.', 'apartment', 2, 2, 1200, 85000.00, 100000.00, '123/5, Cinnamon Gardens, Colombo 07', 'Colombo', 'Western Province', '00700', 6.9170, 79.8606, 1, 1, 1),
(3, 'Luxury 3BR House in Mount Lavinia', 'Spacious 3 bedroom house with garden in Mount Lavinia. Perfect for families. Features modern amenities, parking space, and walking distance to the beach.', 'house', 3, 3, 2000, 120000.00, 150000.00, '45/2, Galle Road, Mount Lavinia', 'Dehiwala-Mount Lavinia', 'Western Province', '10350', 6.8400, 79.8700, 1, 1, 1),
(4, 'Cozy Studio Apartment in Kotte', 'Compact studio apartment perfect for single professionals. Fully furnished with modern amenities. Close to public transport and shopping areas.', 'studio', 1, 1, 500, 45000.00, 50000.00, '78/3, Rajagiriya, Kotte', 'Sri Jayawardenepura Kotte', 'Western Province', '10107', 6.9100, 79.8900, 1, 1, 1),
(5, 'Executive Condo in Colombo 3', 'High-end condominium with premium amenities. Features 2 bedrooms, 2 bathrooms, modern kitchen, and access to swimming pool and gym.', 'condo', 2, 2, 1500, 95000.00, 120000.00, '12/8, Kollupitiya, Colombo 03', 'Colombo', 'Western Province', '00300', 6.9200, 79.8500, 1, 1, 1),
(6, 'Furnished Room in Shared House', 'Private room in a shared house with common areas. Perfect for students or young professionals. Includes utilities and WiFi.', 'room', 1, 1, 200, 25000.00, 30000.00, '34/2, Bambalapitiya, Colombo 04', 'Colombo', 'Western Province', '00400', 6.8900, 79.8600, 1, 1, 1),
(2, 'Family House in Negombo', 'Beautiful 4 bedroom family house near Negombo beach. Features large garden, modern kitchen, and spacious living areas.', 'house', 4, 3, 2500, 80000.00, 100000.00, '156/7, Lewis Place, Negombo', 'Negombo', 'Western Province', '11500', 7.2100, 79.8400, 1, 1, 1),
(3, 'Modern Apartment in Gampaha', 'Newly built 2 bedroom apartment in Gampaha town. Features modern design, parking space, and close to all amenities.', 'apartment', 2, 2, 1000, 55000.00, 70000.00, '89/4, Main Street, Gampaha', 'Gampaha', 'Western Province', '11000', 7.0900, 80.0200, 1, 1, 1),
(4, 'Beach House in Kalutara', 'Stunning beachfront house with direct beach access. Features 3 bedrooms, modern amenities, and breathtaking ocean views.', 'house', 3, 2, 1800, 100000.00, 125000.00, '23/1, Galle Road, Kalutara', 'Kalutara', 'Western Province', '12000', 6.5800, 79.9600, 1, 1, 1),
(5, 'Hill Station Apartment in Kandy', 'Beautiful apartment with hill station views in Kandy. Features 2 bedrooms, modern amenities, and close to the city center.', 'apartment', 2, 2, 1100, 65000.00, 80000.00, '45/2, Peradeniya Road, Kandy', 'Kandy', 'Central Province', '20000', 7.2900, 80.6400, 1, 1, 1),
(6, 'Colonial House in Galle Fort', 'Historic colonial house in the UNESCO World Heritage Galle Fort. Features 3 bedrooms, period furniture, and courtyard garden.', 'house', 3, 2, 1400, 75000.00, 90000.00, '12/3, Church Street, Galle Fort', 'Galle', 'Southern Province', '80000', 6.0300, 80.2200, 1, 1, 1);

-- =====================================================
-- INSERT SAMPLE PROPERTY IMAGES (10 images)
-- =====================================================
INSERT INTO `property_images` (`property_id`, `image_path`, `is_primary`) VALUES
(1, 'uploads/properties/1_1759001543_0.jpeg', 1),
(2, 'uploads/properties/1_1759001543_1.jpeg', 1),
(3, 'uploads/properties/1_1759001543_2.jpg', 1),
(4, 'uploads/properties/1_1759001543_0.jpeg', 1),
(5, 'uploads/properties/1_1759001543_1.jpeg', 1),
(6, 'uploads/properties/1_1759001543_2.jpg', 1),
(7, 'uploads/properties/1_1759001543_0.jpeg', 1),
(8, 'uploads/properties/1_1759001543_1.jpeg', 1),
(9, 'uploads/properties/1_1759001543_2.jpg', 1),
(10, 'uploads/properties/1_1759001543_0.jpeg', 1);

-- =====================================================
-- INSERT SAMPLE PROPERTY AMENITIES (10 amenities)
-- =====================================================
INSERT INTO `property_amenities` (`property_id`, `amenity_name`, `amenity_type`) VALUES
(1, 'Air Conditioning', 'basic'),
(1, 'WiFi', 'basic'),
(1, 'Parking', 'basic'),
(2, 'Garden', 'premium'),
(2, 'Parking', 'basic'),
(2, 'WiFi', 'basic'),
(3, 'WiFi', 'basic'),
(3, 'Furnished', 'basic'),
(4, 'Swimming Pool', 'premium'),
(4, 'Gym', 'premium');

-- =====================================================
-- INSERT SAMPLE USER FAVORITES (10 favorites)
-- =====================================================
INSERT INTO `user_favorites` (`user_id`, `property_id`) VALUES
(7, 1),
(7, 4),
(8, 2),
(8, 8),
(9, 3),
(9, 6),
(10, 5),
(10, 9),
(7, 7),
(8, 10);

-- =====================================================
-- INSERT SAMPLE VISIT REQUESTS (10 requests)
-- =====================================================
INSERT INTO `visit_requests` (`customer_id`, `property_id`, `requested_date`, `requested_time`, `status`, `notes`) VALUES
(7, 1, '2024-02-15', '10:00:00', 'pending', 'Would like to visit in the morning'),
(8, 2, '2024-02-16', '14:00:00', 'approved', 'Confirmed visit for afternoon'),
(9, 3, '2024-02-17', '11:00:00', 'pending', 'Interested in studio apartment'),
(10, 4, '2024-02-18', '15:00:00', 'approved', 'Looking for condo with amenities'),
(7, 5, '2024-02-19', '09:00:00', 'pending', 'Student looking for room'),
(8, 6, '2024-02-20', '16:00:00', 'approved', 'Family visit requested'),
(9, 7, '2024-02-21', '13:00:00', 'pending', 'Professional looking for apartment'),
(10, 8, '2024-02-22', '10:00:00', 'approved', 'Beach house visit confirmed'),
(7, 9, '2024-02-23', '14:00:00', 'pending', 'Hill station property interest'),
(8, 10, '2024-02-24', '11:00:00', 'approved', 'Historic property visit scheduled');

-- =====================================================
-- INSERT SAMPLE PROPERTY REVIEWS (10 reviews)
-- =====================================================
INSERT INTO `property_reviews` (`property_id`, `customer_id`, `rating`, `review_text`, `is_verified`) VALUES
(1, 7, 5, 'Excellent apartment with great amenities. The location is perfect and the owner is very responsive.', 1),
(2, 8, 4, 'Beautiful house with a lovely garden. Perfect for families. The beach is just a short walk away.', 1),
(3, 9, 5, 'Great studio apartment for the price. Everything is modern and well-maintained.', 1),
(4, 10, 5, 'Amazing condo with all the amenities you need. The pool and gym are fantastic.', 1),
(5, 7, 4, 'Good value for money. The shared facilities are clean and well-maintained.', 1),
(6, 8, 5, 'Perfect family house near the beach. Highly recommended for families.', 1),
(7, 9, 4, 'Modern apartment with good amenities. Close to all necessary facilities.', 1),
(8, 10, 5, 'Stunning beach house with amazing ocean views. Worth every penny.', 1),
(9, 7, 4, 'Nice apartment with hill station views. Good for a peaceful stay.', 1),
(10, 8, 5, 'Historic property with great character. Unique experience in Galle Fort.', 1);

-- =====================================================
-- INSERT SAMPLE NOTIFICATIONS (10 notifications)
-- =====================================================
INSERT INTO `notifications` (`user_id`, `title`, `message`, `type`) VALUES
(7, 'Visit Request Approved', 'Your visit request for Modern 2BR Apartment in Colombo 7 has been approved for Feb 15, 2024 at 10:00 AM.', 'success'),
(8, 'New Property Available', 'A new property matching your preferences is now available in your area.', 'info'),
(9, 'Visit Request Pending', 'Your visit request for Cozy Studio Apartment in Kotte is pending owner approval.', 'info'),
(10, 'Payment Reminder', 'Your monthly rent payment is due in 3 days.', 'warning'),
(7, 'Property Update', 'The property you favorited has been updated with new photos.', 'info'),
(8, 'Visit Confirmed', 'Your visit to Luxury 3BR House in Mount Lavinia has been confirmed.', 'success'),
(9, 'New Review', 'Someone left a review on a property you visited.', 'info'),
(10, 'Payment Received', 'Your rent payment has been successfully processed.', 'success'),
(7, 'Property Available', 'A property in your wishlist is now available for rent.', 'info'),
(8, 'Visit Reminder', 'You have a property visit scheduled for tomorrow at 2:00 PM.', 'info');

-- =====================================================
-- INSERT SAMPLE BOOKING CLICKS (10 clicks)
-- =====================================================
INSERT INTO `booking_clicks` (`property_id`, `source_location`, `property_name`, `ip_address`) VALUES
(1, 'search_results', 'Modern 2BR Apartment in Colombo 7', '192.168.1.100'),
(2, 'featured_properties', 'Luxury 3BR House in Mount Lavinia', '192.168.1.101'),
(3, 'search_results', 'Cozy Studio Apartment in Kotte', '192.168.1.102'),
(4, 'featured_properties', 'Executive Condo in Colombo 3', '192.168.1.103'),
(5, 'search_results', 'Furnished Room in Shared House', '192.168.1.104'),
(6, 'search_results', 'Family House in Negombo', '192.168.1.105'),
(7, 'featured_properties', 'Modern Apartment in Gampaha', '192.168.1.106'),
(8, 'search_results', 'Beach House in Kalutara', '192.168.1.107'),
(9, 'featured_properties', 'Hill Station Apartment in Kandy', '192.168.1.108'),
(10, 'search_results', 'Colonial House in Galle Fort', '192.168.1.109');

-- =====================================================
-- INSERT SAMPLE OTP VERIFICATIONS (5 verifications)
-- =====================================================
INSERT INTO `otp_verifications` (`phone`, `otp_code`, `is_verified`, `expires_at`) VALUES
('0771234567', '123456', 1, DATE_ADD(NOW(), INTERVAL 10 MINUTE)),
('0772345678', '234567', 1, DATE_ADD(NOW(), INTERVAL 10 MINUTE)),
('0781111111', '345678', 1, DATE_ADD(NOW(), INTERVAL 10 MINUTE)),
('0782222222', '456789', 0, DATE_ADD(NOW(), INTERVAL 5 MINUTE)),
('0783333333', '567890', 1, DATE_ADD(NOW(), INTERVAL 10 MINUTE));

-- =====================================================
-- INSERT SAMPLE USER SESSIONS (5 sessions)
-- =====================================================
INSERT INTO `user_sessions` (`user_id`, `session_token`, `expires_at`) VALUES
(1, 'admin_session_123456789', DATE_ADD(NOW(), INTERVAL 24 HOUR)),
(2, 'owner_session_234567890', DATE_ADD(NOW(), INTERVAL 24 HOUR)),
(7, 'customer_session_345678901', DATE_ADD(NOW(), INTERVAL 24 HOUR)),
(8, 'customer_session_456789012', DATE_ADD(NOW(), INTERVAL 24 HOUR)),
(9, 'customer_session_567890123', DATE_ADD(NOW(), INTERVAL 24 HOUR));

-- =====================================================
-- INSERT SAMPLE SEARCH ACCESS (5 access records)
-- =====================================================
INSERT INTO `search_access` (`customer_id`, `payment_status`, `payment_method`, `payment_reference`, `amount`, `access_expires_at`) VALUES
(7, 'paid', 'payhere', 'PH123456789', 500.00, DATE_ADD(NOW(), INTERVAL 30 DAY)),
(8, 'paid', 'stripe', 'stripe_123456789', 500.00, DATE_ADD(NOW(), INTERVAL 30 DAY)),
(9, 'pending', 'bank_transfer', 'BT987654321', 500.00, DATE_ADD(NOW(), INTERVAL 30 DAY)),
(10, 'paid', 'payhere', 'PH987654321', 500.00, DATE_ADD(NOW(), INTERVAL 30 DAY)),
(7, 'paid', 'stripe', 'stripe_987654321', 500.00, DATE_ADD(NOW(), INTERVAL 30 DAY));

-- =====================================================
-- INSERT SAMPLE RENTAL BOOKINGS (5 bookings)
-- =====================================================
INSERT INTO `rental_bookings` (`customer_id`, `property_id`, `start_date`, `end_date`, `monthly_rent`, `security_deposit`, `status`) VALUES
(7, 1, '2024-03-01', '2024-12-31', 85000.00, 100000.00, 'active'),
(8, 2, '2024-02-15', '2024-11-30', 120000.00, 150000.00, 'active'),
(9, 3, '2024-03-15', '2024-12-31', 45000.00, 50000.00, 'active'),
(10, 4, '2024-04-01', '2025-03-31', 95000.00, 120000.00, 'active'),
(7, 5, '2024-02-01', '2024-11-30', 25000.00, 30000.00, 'active');

-- =====================================================
-- INSERT SAMPLE SUBSCRIPTIONS (5 subscriptions)
-- =====================================================
INSERT INTO `subscriptions` (`property_id`, `customer_id`, `owner_id`, `monthly_amount`, `start_date`, `next_payment_date`, `last_payment_date`, `status`, `payment_method`) VALUES
(1, 7, 2, 85000.00, '2024-03-01', '2024-04-01', '2024-03-01', 'active', 'payhere'),
(2, 8, 3, 120000.00, '2024-02-15', '2024-03-15', '2024-02-15', 'active', 'stripe'),
(3, 9, 4, 45000.00, '2024-03-15', '2024-04-15', '2024-03-15', 'active', 'payhere'),
(4, 10, 5, 95000.00, '2024-04-01', '2024-05-01', '2024-04-01', 'active', 'stripe'),
(5, 7, 6, 25000.00, '2024-02-01', '2024-03-01', '2024-02-01', 'active', 'payhere');

-- =====================================================
-- INSERT SAMPLE RENT PAYMENTS (10 payments)
-- =====================================================
INSERT INTO `rent_payments` (`booking_id`, `subscription_id`, `customer_id`, `property_id`, `owner_id`, `amount`, `payment_method`, `payment_status`, `payment_reference`, `due_date`, `paid_date`, `commission_amount`, `owner_payout_amount`, `is_recurring`) VALUES
(1, 1, 7, 1, 2, 85000.00, 'payhere', 'completed', 'PH_PAY_001', '2024-03-01', '2024-03-01', 4250.00, 80750.00, 1),
(2, 2, 8, 2, 3, 120000.00, 'stripe', 'completed', 'STRIPE_PAY_001', '2024-02-15', '2024-02-15', 6000.00, 114000.00, 1),
(3, 3, 9, 3, 4, 45000.00, 'payhere', 'completed', 'PH_PAY_002', '2024-03-15', '2024-03-15', 2250.00, 42750.00, 1),
(4, 4, 10, 4, 5, 95000.00, 'stripe', 'completed', 'STRIPE_PAY_002', '2024-04-01', '2024-04-01', 4750.00, 90250.00, 1),
(5, 5, 7, 5, 6, 25000.00, 'payhere', 'completed', 'PH_PAY_003', '2024-02-01', '2024-02-01', 1250.00, 23750.00, 1),
(1, 1, 7, 1, 2, 85000.00, 'payhere', 'pending', 'PH_PAY_004', '2024-04-01', NULL, 4250.00, 80750.00, 1),
(2, 2, 8, 2, 3, 120000.00, 'stripe', 'pending', 'STRIPE_PAY_003', '2024-03-15', NULL, 6000.00, 114000.00, 1),
(3, 3, 9, 3, 4, 45000.00, 'payhere', 'pending', 'PH_PAY_005', '2024-04-15', NULL, 2250.00, 42750.00, 1),
(4, 4, 10, 4, 5, 95000.00, 'stripe', 'pending', 'STRIPE_PAY_004', '2024-05-01', NULL, 4750.00, 90250.00, 1),
(5, 5, 7, 5, 6, 25000.00, 'payhere', 'pending', 'PH_PAY_006', '2024-03-01', NULL, 1250.00, 23750.00, 1);

-- =====================================================
-- INSERT SAMPLE OWNER PAYOUTS (5 payouts)
-- =====================================================
INSERT INTO `owner_payouts` (`owner_id`, `payment_id`, `amount`, `status`, `payout_method`, `payout_reference`, `processed_at`) VALUES
(2, 1, 80750.00, 'processed', 'bank_transfer', 'BT_PAYOUT_001', '2024-03-02 10:00:00'),
(3, 2, 114000.00, 'processed', 'stripe', 'STRIPE_PAYOUT_001', '2024-02-16 10:00:00'),
(4, 3, 42750.00, 'processed', 'bank_transfer', 'BT_PAYOUT_002', '2024-03-16 10:00:00'),
(5, 4, 90250.00, 'processed', 'stripe', 'STRIPE_PAYOUT_002', '2024-04-02 10:00:00'),
(6, 5, 23750.00, 'pending', 'bank_transfer', 'BT_PAYOUT_003', NULL);

-- =====================================================
-- INSERT SAMPLE SYSTEM SETTINGS (5 settings)
-- =====================================================
INSERT INTO `system_settings` (`setting_key`, `setting_value`, `description`) VALUES
('site_name', 'Renting Place Finder', 'The name of the website'),
('commission_rate', '5.0', 'Commission rate percentage for the platform'),
('max_property_images', '10', 'Maximum number of images allowed per property'),
('search_results_per_page', '20', 'Number of search results to display per page'),
('maintenance_mode', '0', 'Whether the site is in maintenance mode (1=yes, 0=no)');

-- =====================================================
-- INSERT SAMPLE ADMIN LOGS (5 logs)
-- =====================================================
INSERT INTO `admin_logs` (`admin_id`, `action`, `description`, `target_user_id`, `target_property_id`, `ip_address`, `user_agent`) VALUES
(1, 'user_verified', 'Verified user account for John Silva', 2, NULL, '192.168.1.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'),
(1, 'property_approved', 'Approved property listing for Modern 2BR Apartment', NULL, 1, '192.168.1.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'),
(1, 'user_suspended', 'Suspended user account for policy violation', 3, NULL, '192.168.1.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'),
(1, 'property_rejected', 'Rejected property listing due to incomplete information', NULL, 2, '192.168.1.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'),
(1, 'payment_processed', 'Processed payment for subscription', 7, 1, '192.168.1.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');

-- =====================================================
-- INSERT SAMPLE PROPERTY AVAILABILITY (10 availability records)
-- =====================================================
INSERT INTO `property_availability` (`property_id`, `date`, `is_available`, `price_override`, `notes`) VALUES
(1, '2024-03-01', 0, 90000.00, 'Booked for special event'),
(1, '2024-03-15', 1, NULL, 'Available for viewing'),
(2, '2024-02-20', 0, NULL, 'Maintenance day'),
(2, '2024-03-10', 1, 125000.00, 'Premium pricing for peak season'),
(3, '2024-03-05', 1, NULL, 'Standard availability'),
(4, '2024-04-01', 0, NULL, 'Already booked'),
(5, '2024-02-25', 1, 30000.00, 'Special offer pricing'),
(6, '2024-03-20', 1, NULL, 'Available'),
(7, '2024-03-12', 0, NULL, 'Under renovation'),
(8, '2024-04-15', 1, 110000.00, 'Peak season pricing');

-- =====================================================
-- INSERT SAMPLE PROPERTY TYPES (8 types)
-- =====================================================
INSERT INTO `property_types` (`type_key`, `type_name`, `description`, `icon`, `is_active`, `sort_order`) VALUES
('apartment', 'Apartment', 'A self-contained housing unit in a building with multiple units', 'fas fa-building', 1, 1),
('house', 'House', 'A single-family dwelling with its own entrance and outdoor space', 'fas fa-home', 1, 2),
('condo', 'Condo', 'A privately owned individual unit in a multi-unit building', 'fas fa-city', 1, 3),
('studio', 'Studio', 'A small apartment with a combined living and sleeping area', 'fas fa-cube', 1, 4),
('room', 'Room', 'A single room for rent, often with shared common areas', 'fas fa-bed', 1, 5),
('villa', 'Villa', 'A large, luxurious house, often with extensive grounds', 'fas fa-home', 1, 6),
('townhouse', 'Townhouse', 'A multi-story house that shares walls with adjacent properties', 'fas fa-home', 1, 7),
('penthouse', 'Penthouse', 'An apartment on the top floor of a building', 'fas fa-building', 1, 8);

-- =====================================================
-- INSERT SAMPLE PROVINCES (9 provinces)
-- =====================================================
INSERT INTO `provinces` (`name`, `code`, `is_active`, `sort_order`) VALUES
('Western Province', 'WP', 1, 1),
('Central Province', 'CP', 1, 2),
('Southern Province', 'SP', 1, 3),
('Northern Province', 'NP', 1, 4),
('Eastern Province', 'EP', 1, 5),
('North Western Province', 'NWP', 1, 6),
('North Central Province', 'NCP', 1, 7),
('Uva Province', 'UP', 1, 8),
('Sabaragamuwa Province', 'SBP', 1, 9);

-- =====================================================
-- INSERT SAMPLE DISTRICTS (25 districts)
-- =====================================================
INSERT INTO `districts` (`province_id`, `name`, `code`, `is_active`, `sort_order`) VALUES
-- Western Province
(1, 'Colombo', 'CMB', 1, 1),
(1, 'Gampaha', 'GAM', 1, 2),
(1, 'Kalutara', 'KAL', 1, 3),
-- Central Province
(2, 'Kandy', 'KAN', 1, 1),
(2, 'Matale', 'MAT', 1, 2),
(2, 'Nuwara Eliya', 'NUE', 1, 3),
-- Southern Province
(3, 'Galle', 'GAL', 1, 1),
(3, 'Matara', 'MTR', 1, 2),
(3, 'Hambantota', 'HAM', 1, 3),
-- Northern Province
(4, 'Jaffna', 'JAF', 1, 1),
(4, 'Kilinochchi', 'KIL', 1, 2),
(4, 'Mannar', 'MAN', 1, 3),
(4, 'Mullaitivu', 'MUL', 1, 4),
(4, 'Vavuniya', 'VAV', 1, 5),
-- Eastern Province
(5, 'Batticaloa', 'BAT', 1, 1),
(5, 'Ampara', 'AMP', 1, 2),
(5, 'Trincomalee', 'TRI', 1, 3),
-- North Western Province
(6, 'Kurunegala', 'KUR', 1, 1),
(6, 'Puttalam', 'PUT', 1, 2),
-- North Central Province
(7, 'Anuradhapura', 'ANU', 1, 1),
(7, 'Polonnaruwa', 'POL', 1, 2),
-- Uva Province
(8, 'Badulla', 'BAD', 1, 1),
(8, 'Monaragala', 'MON', 1, 2),
-- Sabaragamuwa Province
(9, 'Ratnapura', 'RAT', 1, 1),
(9, 'Kegalle', 'KEG', 1, 2);

-- =====================================================
-- INSERT SAMPLE CITIES (25 cities)
-- =====================================================
INSERT INTO `cities` (`district_id`, `name`, `code`, `is_active`, `sort_order`) VALUES
-- Colombo District
(1, 'Colombo', 'CMB-01', 1, 1),
(1, 'Dehiwala-Mount Lavinia', 'CMB-02', 1, 2),
(1, 'Sri Jayawardenepura Kotte', 'CMB-03', 1, 3),
-- Gampaha District
(2, 'Negombo', 'GAM-01', 1, 1),
(2, 'Gampaha', 'GAM-02', 1, 2),
-- Kalutara District
(3, 'Kalutara', 'KAL-01', 1, 1),
(3, 'Panadura', 'KAL-02', 1, 2),
-- Kandy District
(4, 'Kandy', 'KAN-01', 1, 1),
(4, 'Peradeniya', 'KAN-02', 1, 2),
-- Matale District
(5, 'Matale', 'MAT-01', 1, 1),
-- Nuwara Eliya District
(6, 'Nuwara Eliya', 'NUE-01', 1, 1),
(6, 'Hatton', 'NUE-02', 1, 2),
-- Galle District
(7, 'Galle', 'GAL-01', 1, 1),
(7, 'Unawatuna', 'GAL-02', 1, 2),
-- Matara District
(8, 'Matara', 'MTR-01', 1, 1),
(8, 'Weligama', 'MTR-02', 1, 2),
-- Hambantota District
(9, 'Hambantota', 'HAM-01', 1, 1),
(9, 'Tangalle', 'HAM-02', 1, 2),
-- Jaffna District
(10, 'Jaffna', 'JAF-01', 1, 1),
-- Kilinochchi District
(11, 'Kilinochchi', 'KIL-01', 1, 1),
-- Mannar District
(12, 'Mannar', 'MAN-01', 1, 1),
-- Mullaitivu District
(13, 'Mullaitivu', 'MUL-01', 1, 1),
-- Vavuniya District
(14, 'Vavuniya', 'VAV-01', 1, 1),
-- Batticaloa District
(15, 'Batticaloa', 'BAT-01', 1, 1),
-- Ampara District
(16, 'Ampara', 'AMP-01', 1, 1),
-- Trincomalee District
(17, 'Trincomalee', 'TRI-01', 1, 1),
-- Kurunegala District
(18, 'Kurunegala', 'KUR-01', 1, 1),
-- Puttalam District
(19, 'Puttalam', 'PUT-01', 1, 1),
-- Anuradhapura District
(20, 'Anuradhapura', 'ANU-01', 1, 1),
-- Polonnaruwa District
(21, 'Polonnaruwa', 'POL-01', 1, 1),
-- Badulla District
(22, 'Badulla', 'BAD-01', 1, 1),
-- Monaragala District
(23, 'Monaragala', 'MON-01', 1, 1),
-- Ratnapura District
(24, 'Ratnapura', 'RAT-01', 1, 1),
-- Kegalle District
(25, 'Kegalle', 'KEG-01', 1, 1);

-- =====================================================
-- COMMIT TRANSACTION
-- =====================================================
COMMIT;
