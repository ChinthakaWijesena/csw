<?php
/**
 * Add Property API Endpoint
 * Handles property creation with image uploads
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../backend/models/Property.php';
require_once __DIR__ . '/../../backend/models/PropertyImage.php';
require_once __DIR__ . '/../../backend/models/PropertyType.php';

// Set JSON response header
header('Content-Type: application/json');

try {
    // Only allow POST requests
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        json_response(['success' => false, 'message' => 'Method not allowed'], 405);
    }

    // Check if user is logged in and is owner or admin
    if (!is_logged_in()) {
        json_response(['success' => false, 'message' => 'Authentication required'], 401);
    }

    if (!in_array($_SESSION['user_type'], ['owner', 'admin'])) {
        json_response(['success' => false, 'message' => 'Only property owners can add properties'], 403);
    }

    // Validate required fields
    $required_fields = ['title', 'description', 'property_type', 'monthly_rent', 'city', 'state'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            json_response(['success' => false, 'message' => "Field '{$field}' is required"], 400);
        }
    }

    // Sanitize input data
    $property_data = [
        'title' => sanitize_input($_POST['title']),
        'description' => sanitize_input($_POST['description']),
        'property_type' => sanitize_input($_POST['property_type']),
        'monthly_rent' => (float)$_POST['monthly_rent'],
        'security_deposit' => (float)($_POST['security_deposit'] ?? 0),
        'city' => sanitize_input($_POST['city']),
        'state' => sanitize_input($_POST['state']),
        'address' => sanitize_input($_POST['address'] ?? ''),
        'bedrooms' => (int)($_POST['bedrooms'] ?? 0),
        'bathrooms' => (int)($_POST['bathrooms'] ?? 0),
        'area' => (float)($_POST['area'] ?? 0),
        'is_furnished' => isset($_POST['is_furnished']) ? 1 : 0,
        'is_available' => isset($_POST['is_available']) ? 1 : 0,
        'is_verified' => $_SESSION['user_type'] === 'admin' ? 1 : 0,
        'is_approved' => $_SESSION['user_type'] === 'admin' ? 1 : 0,
        'owner_id' => $_SESSION['user_id']
    ];

    // Validate property type
    $property_type_model = new PropertyType();
    $property_type = $property_type_model->getByName($property_data['property_type']);
    if (!$property_type) {
        json_response(['success' => false, 'message' => 'Invalid property type'], 400);
    }

    // Validate rent amount
    if ($property_data['monthly_rent'] <= 0) {
        json_response(['success' => false, 'message' => 'Monthly rent must be greater than 0'], 400);
    }

    // Create property
    $property_model = new Property();
    $property_id = $property_model->create($property_data);

    if (!$property_id) {
        json_response(['success' => false, 'message' => 'Failed to create property'], 500);
    }

    // Handle image uploads
    $uploaded_images = [];
    if (isset($_FILES['property_images']) && !empty($_FILES['property_images']['name'][0])) {
        $uploaded_images = handleImageUploads($property_id, $_FILES['property_images']);
    }

    // Set primary image if uploaded
    if (!empty($uploaded_images)) {
        $property_model->update($property_id, ['primary_image' => $uploaded_images[0]]);
    }

    // Set appropriate message based on user type
    $message = $_SESSION['user_type'] === 'admin' 
        ? 'Property added successfully and is now visible to customers'
        : 'Property added successfully and is pending admin approval';

    json_response([
        'success' => true,
        'message' => $message,
        'data' => [
            'property_id' => $property_id,
            'uploaded_images' => count($uploaded_images),
            'requires_approval' => $_SESSION['user_type'] !== 'admin'
        ],
        'redirect' => '/frontend/owner/properties.php'
    ]);

} catch (Exception $e) {
    error_log("Add Property Error: " . $e->getMessage());
    json_response([
        'success' => false,
        'message' => 'An error occurred while adding property'
    ], 500);
}

/**
 * Handle image uploads
 */
function handleImageUploads($property_id, $files) {
    $uploaded_images = [];
    $upload_dir = __DIR__ . '/../../frontend/uploads/properties/' . $property_id . '/';
    
    // Create upload directory
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $max_size = 5 * 1024 * 1024; // 5MB
    $max_images = 15;

    $file_count = count($files['name']);
    if ($file_count > $max_images) {
        throw new Exception("Maximum {$max_images} images allowed");
    }

    for ($i = 0; $i < $file_count; $i++) {
        if ($files['error'][$i] === UPLOAD_ERR_OK) {
            $file_name = $files['name'][$i];
            $file_tmp = $files['tmp_name'][$i];
            $file_size = $files['size'][$i];
            $file_type = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

            // Validate file type
            if (!in_array($file_type, $allowed_types)) {
                throw new Exception("Invalid file type: {$file_name}");
            }

            // Validate file size
            if ($file_size > $max_size) {
                throw new Exception("File too large: {$file_name}");
            }

            // Generate unique filename
            $unique_name = uniqid() . '_' . time() . '.' . $file_type;
            $upload_path = $upload_dir . $unique_name;

            // Move uploaded file
            if (move_uploaded_file($file_tmp, $upload_path)) {
                // Save to database
                $property_image_model = new PropertyImage();
                $image_id = $property_image_model->create([
                    'property_id' => $property_id,
                    'image_path' => '/frontend/uploads/properties/' . $property_id . '/' . $unique_name,
                    'is_primary' => $i === 0 ? 1 : 0,
                    'sort_order' => $i + 1
                ]);

                if ($image_id) {
                    $uploaded_images[] = '/frontend/uploads/properties/' . $property_id . '/' . $unique_name;
                }
            }
        }
    }

    return $uploaded_images;
}
?>
