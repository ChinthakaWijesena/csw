-- =====================================================
-- SAMPLE DATA FOR COMPREHENSIVE PROPERTY MANAGEMENT SYSTEM
-- =====================================================

USE `comprehensive_property_system`;

-- =====================================================
-- TABLE: users
-- =====================================================
INSERT INTO `users` (`phone`, `name`, `email`, `user_type`, `is_verified`, `is_active`)
VALUES
('0712345678', 'John Doe', 'john@example.com', 'owner', 1, 1),
('0723456789', 'Jane Smith', 'jane@example.com', 'customer', 1, 1),
('0734567890', 'Admin User', 'admin@example.com', 'admin', 1, 1),
('0745678901', 'Agent One', 'agent1@example.com', 'agent', 1, 1),
('0756789012', 'Customer Two', 'cust2@example.com', 'customer', 0, 1);

-- =====================================================
-- TABLE: provinces
-- =====================================================
INSERT INTO `provinces` (`name`, `code`, `is_active`, `sort_order`)
VALUES
('Western Province', 'WP', 1, 1),
('Central Province', 'CP', 1, 2),
('Southern Province', 'SP', 1, 3),
('Northern Province', 'NP', 1, 4),
('Eastern Province', 'EP', 1, 5);

-- =====================================================
-- TABLE: districts
-- =====================================================
INSERT INTO `districts` (`province_id`, `name`, `code`, `is_active`, `sort_order`)
VALUES
(1, 'Colombo', 'CL', 1, 1),
(1, 'Gampaha', 'GA', 1, 2),
(2, 'Kandy', 'KD', 1, 1),
(2, 'Matale', 'MT', 1, 2),
(3, 'Galle', 'GL', 1, 1);

-- =====================================================
-- TABLE: cities
-- =====================================================
INSERT INTO `cities` (`district_id`, `name`, `code`, `is_active`, `sort_order`)
VALUES
(1, 'Colombo 1', 'CL1', 1, 1),
(1, 'Colombo 2', 'CL2', 1, 2),
(2, 'Negombo', 'NG', 1, 1),
(3, 'Kandy City', 'KD1', 1, 1),
(5, 'Galle City', 'GL1', 1, 1);

-- =====================================================
-- TABLE: property_types
-- =====================================================
INSERT INTO `property_types` (`name`, `description`, `is_active`)
VALUES
('Apartment', 'Residential apartment', 1),
('House', 'Independent house', 1),
('Villa', 'Luxury villa', 1),
('Land', 'Vacant land', 1),
('Office', 'Commercial office space', 1);

-- =====================================================
-- TABLE: properties
-- =====================================================
INSERT INTO `properties` (
    `owner_id`, `title`, `description`, `property_type_id`, `status`,
    `price`, `rent`, `security_deposit`, `area_sqft`, `bedrooms`,
    `bathrooms`, `floor_number`, `total_floors`, `furnished`, `parking`,
    `year_built`, `latitude`, `longitude`, `city_id`, `district_id`, `province_id`, `is_featured`
)
VALUES
(1, 'Luxury Apartment in Colombo', 'A modern 3-bedroom apartment in Colombo city.', 1, 'sale', 12000000, NULL, NULL, 1500, 3, 2, 5, 10, 1, 2, 2018, 6.9271, 79.8612, 1, 1, 1, 1),
(1, 'Family House in Gampaha', 'Spacious 4-bedroom house with garden.', 2, 'sale', 18000000, NULL, NULL, 2500, 4, 3, NULL, NULL, 1, 2, 2015, 7.0840, 79.8795, 3, 2, 1, 0),
(4, 'Beachside Villa in Galle', 'Luxury villa with sea view.', 3, 'sale', 35000000, NULL, NULL, 3000, 5, 4, 2, 2, 1, 3, 2020, 6.0535, 80.2200, 5, 5, 3, 1),
(1, 'Office Space in Kandy', 'Commercial office space in city center.', 5, 'rent', NULL, 150000, 50000, 1200, NULL, 2, 3, 5, 0, 2, 2010, 7.2906, 80.6337, 4, 3, 2, 0),
(4, 'Vacant Land in Matale', 'Land suitable for residential construction.', 4, 'sale', 5000000, NULL, NULL, 5000, NULL, NULL, NULL, NULL, 0, 0, NULL, 7.4700, 80.6230, 4, 4, 2, 0);

-- =====================================================
-- TABLE: property_images
-- =====================================================
INSERT INTO `property_images` (`property_id`, `image_url`, `is_primary`)
VALUES
(1, 'images/colombo_apartment_1.jpg', 1),
(1, 'images/colombo_apartment_2.jpg', 0),
(2, 'images/gampaha_house_1.jpg', 1),
(3, 'images/galle_villa_1.jpg', 1),
(4, 'images/kandy_office_1.jpg', 1);

-- =====================================================
-- TABLE: property_amenities
-- =====================================================
INSERT INTO `property_amenities` (`property_id`, `amenity`)
VALUES
(1, 'Swimming Pool'),
(1, 'Gym'),
(2, 'Garden'),
(3, 'Sea View'),
(4, 'Elevator');

-- =====================================================
-- TABLE: property_inquiries
-- =====================================================
INSERT INTO `property_inquiries` (`property_id`, `user_id`, `message`, `contact_number`, `email`)
VALUES
(1, 2, 'Is this apartment still available?', '0723456789', 'jane@example.com'),
(2, 5, 'Can I schedule a visit this weekend?', '0756789012', 'cust2@example.com'),
(3, 2, 'Is the villa furnished?', '0723456789', 'jane@example.com'),
(4, 5, 'What is the monthly rent including maintenance?', '0756789012', 'cust2@example.com'),
(5, 2, 'Can I make an offer for the land?', '0723456789', 'jane@example.com');
