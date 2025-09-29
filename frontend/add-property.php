<?php
/**
 * Add Property Page
 * Standalone page for property owners to add new properties
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../backend/models/User.php';
require_once __DIR__ . '/../backend/models/Property.php';
require_once __DIR__ . '/../backend/models/PropertyType.php';
require_once __DIR__ . '/../backend/models/PropertyImage.php';
require_once __DIR__ . '/../backend/models/Province.php';
require_once __DIR__ . '/../backend/models/District.php';
require_once __DIR__ . '/../backend/models/City.php';

// Check if user is logged in and is a property owner
if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$user_model = new User();
$user = $user_model->getById($user_id);

if (!$user || $user['user_type'] !== 'owner') {
    header('Location: login.php');
    exit;
}

// Initialize models
$property_model = new Property();
$property_type_model = new PropertyType();
$property_image_model = new PropertyImage();
$province_model = new Province();
$district_model = new District();
$city_model = new City();

// Get data from database
$property_types = $property_type_model->getAllActive();
$provinces = $province_model->getAllActive();

// Handle form submission
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $property_data = [
        'owner_id' => $user_id,
        'title' => $_POST['title'],
        'description' => $_POST['description'],
        'property_type' => $_POST['property_type'],
        'bedrooms' => $_POST['bedrooms'] ?: null,
        'bathrooms' => $_POST['bathrooms'] ?: null,
        'area_sqft' => $_POST['area_sqft'] ?: null,
        'monthly_rent' => $_POST['monthly_rent'],
        'security_deposit' => $_POST['security_deposit'] ?: null,
        'address' => $_POST['address'],
        'city' => $_POST['city_name'] ?? $_POST['city'], // Use city_name from dropdown or fallback to city input
        'state' => $_POST['province_name'] ?? $_POST['state'], // Use province_name from dropdown or fallback to state input
        'zip_code' => $_POST['zip_code'],
        'latitude' => $_POST['latitude'] ?: null,
        'longitude' => $_POST['longitude'] ?: null,
        'is_available' => isset($_POST['is_available']) ? 1 : 0,
        'is_verified' => 0
    ];
    
    try {
        $property_id = $property_model->create($property_data);
        
        // Handle image uploads
        $uploaded_images = [];
        if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
            $uploaded_images = handleImageUploads($property_id, $_FILES['images']);
        }
        
        if (empty($uploaded_images)) {
            $message = 'Property added successfully, but no images were uploaded. Please add at least one image. Your property is pending admin approval.';
            $message_type = 'warning';
        } else {
            $message = 'Property added successfully with ' . count($uploaded_images) . ' image(s)! Your property is pending admin approval and will be visible to customers once approved.';
            $message_type = 'success';
        }
        
        // Clear form data after successful submission
        $_POST = [];
    } catch (Exception $e) {
        $message = 'Error adding property: ' . $e->getMessage();
        $message_type = 'danger';
    }
}

/**
 * Handle image uploads
 */
function handleImageUploads($property_id, $files) {
    global $property_image_model;
    
    $uploaded_images = [];
    $upload_dir = __DIR__ . '/uploads/properties/';
    $max_images = 15;
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    $max_file_size = 5 * 1024 * 1024; // 5MB
    
    // Create upload directory if it doesn't exist
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $file_count = count($files['name']);
    
    // Validate number of images
    if ($file_count > $max_images) {
        throw new Exception("Maximum {$max_images} images allowed. You uploaded {$file_count} images.");
    }
    
    for ($i = 0; $i < $file_count; $i++) {
        if ($files['error'][$i] === UPLOAD_ERR_OK) {
            $file_name = $files['name'][$i];
            $file_tmp = $files['tmp_name'][$i];
            $file_size = $files['size'][$i];
            $file_type = $files['type'][$i];
            
            // Validate file type
            if (!in_array($file_type, $allowed_types)) {
                throw new Exception("Invalid file type for {$file_name}. Only JPEG, PNG, GIF, and WebP images are allowed.");
            }
            
            // Validate file size
            if ($file_size > $max_file_size) {
                throw new Exception("File {$file_name} is too large. Maximum size is 5MB.");
            }
            
            // Generate unique filename
            $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
            $unique_filename = $property_id . '_' . time() . '_' . $i . '.' . $file_extension;
            $file_path = $upload_dir . $unique_filename;
            
            // Move uploaded file
            if (move_uploaded_file($file_tmp, $file_path)) {
                // Save to database
                $image_data = [
                    'property_id' => $property_id,
                    'image_path' => 'uploads/properties/' . $unique_filename,
                    'is_primary' => $i === 0 ? 1 : 0 // First image is primary
                ];
                
                $image_id = $property_image_model->create($image_data);
                $uploaded_images[] = $image_id;
            } else {
                throw new Exception("Failed to upload {$file_name}");
            }
        } else {
            throw new Exception("Upload error for file " . ($i + 1) . ": " . getUploadErrorMessage($files['error'][$i]));
        }
    }
    
    return $uploaded_images;
}

/**
 * Get upload error message
 */
function getUploadErrorMessage($error_code) {
    switch ($error_code) {
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            return 'File is too large';
        case UPLOAD_ERR_PARTIAL:
            return 'File was only partially uploaded';
        case UPLOAD_ERR_NO_FILE:
            return 'No file was uploaded';
        case UPLOAD_ERR_NO_TMP_DIR:
            return 'Missing temporary folder';
        case UPLOAD_ERR_CANT_WRITE:
            return 'Failed to write file to disk';
        case UPLOAD_ERR_EXTENSION:
            return 'File upload stopped by extension';
        default:
            return 'Unknown upload error';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Property - Renting Place Finder</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    
    <style>
        .add-property-page {
            background-color: var(--booking-gray-50);
            min-height: 100vh;
            padding: 2rem 0;
        }
        
        .add-property-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 0 1rem;
        }
        
        .add-property-header {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
            text-align: center;
        }
        
        .add-property-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--booking-gray-900);
            margin: 0 0 0.5rem 0;
        }
        
        .add-property-subtitle {
            color: var(--booking-gray-600);
            margin: 0;
        }
        
        .add-property-form {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .form-section {
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid var(--booking-gray-200);
        }
        
        .form-section:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }
        
        .form-section-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--booking-gray-900);
            margin: 0 0 1.5rem 0;
            display: flex;
            align-items: center;
        }
        
        .form-section-title i {
            margin-right: 0.5rem;
            color: var(--booking-primary);
        }
        
        .form-floating {
            margin-bottom: 1rem;
        }
        
        .form-floating .form-control {
            height: calc(3.5rem + 2px);
            padding: 1rem 0.75rem;
        }
        
        .form-floating label {
            padding: 1rem 0.75rem;
        }
        
        .form-check {
            margin-bottom: 1rem;
        }
        
        .form-check-input:checked {
            background-color: var(--booking-primary);
            border-color: var(--booking-primary);
        }
        
        .btn-submit {
            background: var(--booking-primary);
            border: none;
            padding: 0.75rem 2rem;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .btn-submit:hover {
            background: var(--booking-primary-dark);
            transform: translateY(-2px);
        }
        
        .btn-cancel {
            background: var(--booking-gray-200);
            color: var(--booking-gray-700);
            border: none;
            padding: 0.75rem 2rem;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 8px;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-cancel:hover {
            background: var(--booking-gray-300);
            color: var(--booking-gray-800);
            transform: translateY(-2px);
        }
        
        .required-field {
            color: var(--booking-danger);
        }
        
        .help-text {
            font-size: 0.875rem;
            color: var(--booking-gray-600);
            margin-top: 0.25rem;
        }
        
        .back-link {
            color: var(--booking-primary);
            text-decoration: none;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .back-link:hover {
            color: var(--booking-primary-dark);
        }
        
        .back-link i {
            margin-right: 0.5rem;
        }
        
        .property-type-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .property-type-option {
            position: relative;
        }
        
        .property-type-option input[type="radio"] {
            position: absolute;
            opacity: 0;
        }
        
        .property-type-option label {
            display: block;
            padding: 1rem;
            border: 2px solid var(--booking-gray-200);
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: white;
        }
        
        .property-type-option input[type="radio"]:checked + label {
            border-color: var(--booking-primary);
            background: var(--booking-primary-light);
            color: var(--booking-primary);
        }
        
        .property-type-option label i {
            display: block;
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }
        
        .property-type-option label span {
            font-weight: 500;
        }
        
        .image-upload-container {
            margin-bottom: 1rem;
        }
        
        .image-upload-area {
            border: 2px dashed var(--booking-gray-300);
            border-radius: 12px;
            padding: 2rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: var(--booking-gray-50);
        }
        
        .image-upload-area:hover {
            border-color: var(--booking-primary);
            background: var(--booking-primary-light);
        }
        
        .image-upload-area.dragover {
            border-color: var(--booking-primary);
            background: var(--booking-primary-light);
            transform: scale(1.02);
        }
        
        .image-upload-content i {
            font-size: 3rem;
            color: var(--booking-primary);
            margin-bottom: 1rem;
        }
        
        .image-upload-content h5 {
            color: var(--booking-gray-900);
            margin: 0 0 0.5rem 0;
        }
        
        .image-upload-content p {
            color: var(--booking-gray-600);
            margin: 0.25rem 0;
        }
        
        .image-preview-container {
            margin-top: 1.5rem;
            padding: 1rem;
            background: white;
            border-radius: 8px;
            border: 1px solid var(--booking-gray-200);
        }
        
        .image-preview-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid var(--booking-gray-200);
        }
        
        .image-preview-header h6 {
            margin: 0;
            color: var(--booking-gray-900);
        }
        
        .image-preview-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 1rem;
        }
        
        .image-preview-item {
            position: relative;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        
        .image-preview-item:hover {
            transform: scale(1.05);
        }
        
        .image-preview-item img {
            width: 100%;
            height: 120px;
            object-fit: cover;
            display: block;
        }
        
        .image-preview-actions {
            position: absolute;
            top: 0;
            right: 0;
            background: rgba(0,0,0,0.7);
            padding: 0.25rem;
            border-radius: 0 0 0 8px;
        }
        
        .image-preview-actions button {
            background: none;
            border: none;
            color: white;
            padding: 0.25rem;
            cursor: pointer;
            border-radius: 4px;
            transition: background 0.3s ease;
        }
        
        .image-preview-actions button:hover {
            background: rgba(255,255,255,0.2);
        }
        
        .image-preview-primary {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: var(--booking-primary);
            color: white;
            padding: 0.25rem;
            text-align: center;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .image-preview-item.primary {
            border: 2px solid var(--booking-primary);
        }
        
        .image-upload-error {
            color: var(--booking-danger);
            font-size: 0.875rem;
            margin-top: 0.5rem;
        }
        
        @media (max-width: 768px) {
            .add-property-page {
                padding: 1rem 0;
            }
            
            .add-property-header {
                padding: 1.5rem;
            }
            
            .add-property-form {
                padding: 1.5rem;
            }
            
            .add-property-title {
                font-size: 1.5rem;
            }
            
            .property-type-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body class="add-property-page">
    <div class="add-property-container">
        <!-- Back Link -->
        <a href="owner/properties.php" class="back-link">
            <i class="fas fa-arrow-left"></i>
            Back to My Properties
        </a>
        
        <!-- Header -->
        <div class="add-property-header">
            <h1 class="add-property-title">Add New Property</h1>
            <p class="add-property-subtitle">List your property and start earning rental income</p>
        </div>
        
        <!-- Message -->
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <!-- Form -->
        <div class="add-property-form">
            <form method="POST" id="addPropertyForm" enctype="multipart/form-data">
                <!-- Basic Information -->
                <div class="form-section">
                    <h3 class="form-section-title">
                        <i class="fas fa-info-circle"></i>
                        Basic Information
                    </h3>
                    
                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-floating">
                                <input type="text" class="form-control" id="title" name="title" 
                                       value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>" 
                                       placeholder="Enter property title" required>
                                <label for="title">Property Title <span class="required-field">*</span></label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-floating">
                                <select class="form-select" id="property_type" name="property_type" required>
                                    <option value="">Select Type</option>
                                    <?php foreach ($property_types as $type): ?>
                                        <option value="<?php echo htmlspecialchars($type['type_key']); ?>" 
                                                <?php echo ($_POST['property_type'] ?? '') === $type['type_key'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($type['type_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <label for="property_type">Property Type <span class="required-field">*</span></label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-floating">
                        <textarea class="form-control" id="description" name="description" 
                                  placeholder="Describe your property" style="height: 120px" required><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                        <label for="description">Property Description <span class="required-field">*</span></label>
                    </div>
                </div>
                
                <!-- Property Details -->
                <div class="form-section">
                    <h3 class="form-section-title">
                        <i class="fas fa-home"></i>
                        Property Details
                    </h3>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-floating">
                                <input type="number" class="form-control" id="bedrooms" name="bedrooms" 
                                       value="<?php echo htmlspecialchars($_POST['bedrooms'] ?? ''); ?>" 
                                       placeholder="Number of bedrooms" min="0">
                                <label for="bedrooms">Bedrooms</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-floating">
                                <input type="number" class="form-control" id="bathrooms" name="bathrooms" 
                                       value="<?php echo htmlspecialchars($_POST['bathrooms'] ?? ''); ?>" 
                                       placeholder="Number of bathrooms" min="0" step="0.5">
                                <label for="bathrooms">Bathrooms</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-floating">
                                <input type="number" class="form-control" id="area_sqft" name="area_sqft" 
                                       value="<?php echo htmlspecialchars($_POST['area_sqft'] ?? ''); ?>" 
                                       placeholder="Area in square feet" min="0">
                                <label for="area_sqft">Area (sqft)</label>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Pricing -->
                <div class="form-section">
                    <h3 class="form-section-title">
                        <i class="fas fa-dollar-sign"></i>
                        Pricing
                    </h3>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-floating">
                                <input type="number" class="form-control" id="monthly_rent" name="monthly_rent" 
                                       value="<?php echo htmlspecialchars($_POST['monthly_rent'] ?? ''); ?>" 
                                       placeholder="Monthly rent amount" min="0" step="0.01" required>
                                <label for="monthly_rent">Monthly Rent (LKR) <span class="required-field">*</span></label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating">
                                <input type="number" class="form-control" id="security_deposit" name="security_deposit" 
                                       value="<?php echo htmlspecialchars($_POST['security_deposit'] ?? ''); ?>" 
                                       placeholder="Security deposit amount" min="0" step="0.01">
                                <label for="security_deposit">Security Deposit (LKR)</label>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Location -->
                <div class="form-section">
                    <h3 class="form-section-title">
                        <i class="fas fa-map-marker-alt"></i>
                        Location
                    </h3>
                    
                    <div class="form-floating">
                        <textarea class="form-control" id="address" name="address" 
                                  placeholder="Full address" style="height: 80px" required><?php echo htmlspecialchars($_POST['address'] ?? ''); ?></textarea>
                        <label for="address">Full Address <span class="required-field">*</span></label>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-floating">
                                <select class="form-select" id="province" name="province_id" required>
                                    <option value="">Select Province</option>
                                    <?php foreach ($provinces as $province): ?>
                                        <option value="<?php echo $province['id']; ?>"
                                                <?php echo ($_POST['province_id'] ?? '') == $province['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($province['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <label for="province">Province <span class="required-field">*</span></label>
                                <input type="hidden" id="province_name" name="province_name">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-floating">
                                <select class="form-select" id="district" name="district_id" required disabled>
                                    <option value="">Select District</option>
                                </select>
                                <label for="district">District <span class="required-field">*</span></label>
                                <input type="hidden" id="district_name" name="district_name">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-floating">
                                <select class="form-select" id="city" name="city_id" required disabled>
                                    <option value="">Select City</option>
                                </select>
                                <label for="city">City <span class="required-field">*</span></label>
                                <input type="hidden" id="city_name" name="city_name">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-floating">
                                <input type="text" class="form-control" id="zip_code" name="zip_code" 
                                       value="<?php echo htmlspecialchars($_POST['zip_code'] ?? ''); ?>" 
                                       placeholder="ZIP Code">
                                <label for="zip_code">ZIP Code</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-floating">
                                <input type="text" class="form-control" id="country" name="country" 
                                       value="Sri Lanka" readonly>
                                <label for="country">Country</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-floating">
                                <input type="number" class="form-control" id="latitude" name="latitude" 
                                       value="<?php echo htmlspecialchars($_POST['latitude'] ?? ''); ?>" 
                                       placeholder="Latitude" step="any">
                                <label for="latitude">Latitude (Optional)</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-floating">
                                <input type="number" class="form-control" id="longitude" name="longitude" 
                                       value="<?php echo htmlspecialchars($_POST['longitude'] ?? ''); ?>" 
                                       placeholder="Longitude" step="any">
                                <label for="longitude">Longitude (Optional)</label>
                            </div>
                        </div>
                    </div>
                    <div class="help-text">
                        <i class="fas fa-info-circle me-1"></i>
                        Latitude and longitude help customers find your property on the map. You can get these coordinates from Google Maps.
                    </div>
                </div>
                
                <!-- Images -->
                <div class="form-section">
                    <h3 class="form-section-title">
                        <i class="fas fa-images"></i>
                        Property Images
                    </h3>
                    
                    <div class="image-upload-container">
                        <div class="image-upload-area" id="imageUploadArea">
                            <div class="image-upload-content">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <h5>Upload Property Images</h5>
                                <p>Drag and drop images here or click to browse</p>
                                <p class="text-muted">Minimum 1 image, maximum 15 images</p>
                                <p class="text-muted">Supported formats: JPEG, PNG, GIF, WebP (Max 5MB each)</p>
                            </div>
                            <input type="file" id="imageInput" name="images[]" multiple accept="image/*" style="display: none;">
                        </div>
                        
                        <div class="image-preview-container" id="imagePreviewContainer" style="display: none;">
                            <div class="image-preview-header">
                                <h6>Selected Images (<span id="imageCount">0</span>/15)</h6>
                                <button type="button" class="btn btn-sm btn-outline-danger" id="clearAllImages">Clear All</button>
                            </div>
                            <div class="image-preview-grid" id="imagePreviewGrid">
                                <!-- Preview images will be added here -->
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Availability -->
                <div class="form-section">
                    <h3 class="form-section-title">
                        <i class="fas fa-calendar-check"></i>
                        Availability
                    </h3>
                    
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="is_available" name="is_available" 
                               <?php echo isset($_POST['is_available']) ? 'checked' : 'checked'; ?>>
                        <label class="form-check-label" for="is_available">
                            Make this property available for booking
                        </label>
                    </div>
                    <div class="help-text">
                        <i class="fas fa-info-circle me-1"></i>
                        Uncheck this if you want to add the property but keep it unavailable for now.
                    </div>
                </div>
                
                <!-- Submit Buttons -->
                <div class="d-flex justify-content-between align-items-center mt-4">
                    <a href="owner/properties.php" class="btn-cancel">
                        <i class="fas fa-times me-2"></i>
                        Cancel
                    </a>
                    <button type="submit" class="btn btn-submit">
                        <i class="fas fa-plus me-2"></i>
                        Add Property
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Form validation
        document.getElementById('addPropertyForm').addEventListener('submit', function(e) {
            const monthlyRent = document.getElementById('monthly_rent').value;
            const securityDeposit = document.getElementById('security_deposit').value;
            
            if (monthlyRent && parseFloat(monthlyRent) <= 0) {
                e.preventDefault();
                alert('Monthly rent must be greater than 0');
                return;
            }
            
            if (securityDeposit && parseFloat(securityDeposit) < 0) {
                e.preventDefault();
                alert('Security deposit cannot be negative');
                return;
            }
        });
        
        // Auto-format currency inputs
        document.getElementById('monthly_rent').addEventListener('blur', function() {
            if (this.value) {
                this.value = parseFloat(this.value).toFixed(2);
            }
        });
        
        document.getElementById('security_deposit').addEventListener('blur', function() {
            if (this.value) {
                this.value = parseFloat(this.value).toFixed(2);
            }
        });
        
        // Character counter for description
        const description = document.getElementById('description');
        const maxLength = 1000;
        
        description.addEventListener('input', function() {
            const remaining = maxLength - this.value.length;
            let helpText = this.parentNode.querySelector('.help-text');
            
            if (!helpText) {
                helpText = document.createElement('div');
                helpText.className = 'help-text';
                this.parentNode.appendChild(helpText);
            }
            
            helpText.textContent = `${remaining} characters remaining`;
            
            if (remaining < 50) {
                helpText.style.color = 'var(--booking-warning)';
            } else if (remaining < 100) {
                helpText.style.color = 'var(--booking-gray-600)';
            } else {
                helpText.style.color = 'var(--booking-gray-600)';
            }
        });
        
        // Image upload functionality
        const imageUploadArea = document.getElementById('imageUploadArea');
        const imageInput = document.getElementById('imageInput');
        const imagePreviewContainer = document.getElementById('imagePreviewContainer');
        const imagePreviewGrid = document.getElementById('imagePreviewGrid');
        const imageCount = document.getElementById('imageCount');
        const clearAllImages = document.getElementById('clearAllImages');
        
        let selectedImages = [];
        const maxImages = 15;
        const maxFileSize = 5 * 1024 * 1024; // 5MB
        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        
        // Click to upload
        imageUploadArea.addEventListener('click', () => {
            imageInput.click();
        });
        
        // File input change
        imageInput.addEventListener('change', handleFileSelect);
        
        // Drag and drop
        imageUploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            imageUploadArea.classList.add('dragover');
        });
        
        imageUploadArea.addEventListener('dragleave', () => {
            imageUploadArea.classList.remove('dragover');
        });
        
        imageUploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            imageUploadArea.classList.remove('dragover');
            const files = e.dataTransfer.files;
            handleFiles(files);
        });
        
        // Clear all images
        clearAllImages.addEventListener('click', () => {
            selectedImages = [];
            imageInput.value = '';
            updateImagePreview();
        });
        
        function handleFileSelect(e) {
            const files = e.target.files;
            handleFiles(files);
        }
        
        function handleFiles(files) {
            const newImages = Array.from(files);
            
            // Validate total count
            if (selectedImages.length + newImages.length > maxImages) {
                alert(`Maximum ${maxImages} images allowed. You selected ${selectedImages.length + newImages.length} images.`);
                return;
            }
            
            // Validate each file
            for (let file of newImages) {
                if (!allowedTypes.includes(file.type)) {
                    alert(`Invalid file type: ${file.name}. Only JPEG, PNG, GIF, and WebP images are allowed.`);
                    continue;
                }
                
                if (file.size > maxFileSize) {
                    alert(`File too large: ${file.name}. Maximum size is 5MB.`);
                    continue;
                }
                
                selectedImages.push(file);
            }
            
            updateImagePreview();
        }
        
        function updateImagePreview() {
            imageCount.textContent = selectedImages.length;
            
            if (selectedImages.length > 0) {
                imagePreviewContainer.style.display = 'block';
                imagePreviewGrid.innerHTML = '';
                
                selectedImages.forEach((file, index) => {
                    const previewItem = createImagePreview(file, index);
                    imagePreviewGrid.appendChild(previewItem);
                });
            } else {
                imagePreviewContainer.style.display = 'none';
            }
        }
        
        function createImagePreview(file, index) {
            const previewItem = document.createElement('div');
            previewItem.className = `image-preview-item ${index === 0 ? 'primary' : ''}`;
            
            const img = document.createElement('img');
            img.src = URL.createObjectURL(file);
            img.alt = file.name;
            
            const actions = document.createElement('div');
            actions.className = 'image-preview-actions';
            
            const removeBtn = document.createElement('button');
            removeBtn.innerHTML = '<i class="fas fa-times"></i>';
            removeBtn.title = 'Remove image';
            removeBtn.addEventListener('click', () => removeImage(index));
            
            const primaryBtn = document.createElement('button');
            primaryBtn.innerHTML = '<i class="fas fa-star"></i>';
            primaryBtn.title = 'Set as primary';
            primaryBtn.addEventListener('click', () => setPrimary(index));
            
            actions.appendChild(removeBtn);
            if (index !== 0) {
                actions.appendChild(primaryBtn);
            }
            
            const primaryLabel = document.createElement('div');
            primaryLabel.className = 'image-preview-primary';
            primaryLabel.textContent = index === 0 ? 'Primary' : '';
            
            previewItem.appendChild(img);
            previewItem.appendChild(actions);
            previewItem.appendChild(primaryLabel);
            
            return previewItem;
        }
        
        function removeImage(index) {
            selectedImages.splice(index, 1);
            updateImagePreview();
        }
        
        function setPrimary(index) {
            // Move the selected image to the beginning
            const primaryImage = selectedImages.splice(index, 1)[0];
            selectedImages.unshift(primaryImage);
            updateImagePreview();
        }
        
        // Location dropdown functionality
        const provinceSelect = document.getElementById('province');
        const districtSelect = document.getElementById('district');
        const citySelect = document.getElementById('city');
        const provinceNameInput = document.getElementById('province_name');
        const districtNameInput = document.getElementById('district_name');
        const cityNameInput = document.getElementById('city_name');
        
        // Province change handler
        provinceSelect.addEventListener('change', function() {
            const provinceId = this.value;
            const selectedOption = this.options[this.selectedIndex];
            
            // Clear dependent dropdowns
            districtSelect.innerHTML = '<option value="">Select District</option>';
            citySelect.innerHTML = '<option value="">Select City</option>';
            districtSelect.disabled = true;
            citySelect.disabled = true;
            
            // Set province name
            if (provinceId) {
                provinceNameInput.value = selectedOption.text;
                loadDistricts(provinceId);
            } else {
                provinceNameInput.value = '';
            }
        });
        
        // District change handler
        districtSelect.addEventListener('change', function() {
            const districtId = this.value;
            const selectedOption = this.options[this.selectedIndex];
            
            // Clear city dropdown
            citySelect.innerHTML = '<option value="">Select City</option>';
            citySelect.disabled = true;
            
            // Set district name
            if (districtId) {
                districtNameInput.value = selectedOption.text;
                loadCities(districtId);
            } else {
                districtNameInput.value = '';
            }
        });
        
        // City change handler
        citySelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            cityNameInput.value = selectedOption.text;
        });
        
        // Load districts function
        function loadDistricts(provinceId) {
            fetch(`../api/location/get_locations.php?action=districts&province_id=${provinceId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        districtSelect.innerHTML = '<option value="">Select District</option>';
                        data.data.forEach(district => {
                            const option = document.createElement('option');
                            option.value = district.id;
                            option.textContent = district.name;
                            districtSelect.appendChild(option);
                        });
                        districtSelect.disabled = false;
                    } else {
                        console.error('Error loading districts:', data.message);
                    }
                })
                .catch(error => {
                    console.error('Error loading districts:', error);
                });
        }
        
        // Load cities function
        function loadCities(districtId) {
            fetch(`../api/location/get_locations.php?action=cities&district_id=${districtId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        citySelect.innerHTML = '<option value="">Select City</option>';
                        data.data.forEach(city => {
                            const option = document.createElement('option');
                            option.value = city.id;
                            option.textContent = city.name;
                            citySelect.appendChild(option);
                        });
                        citySelect.disabled = false;
                    } else {
                        console.error('Error loading cities:', data.message);
                    }
                })
                .catch(error => {
                    console.error('Error loading cities:', error);
                });
        }
        
        // Form validation
        document.getElementById('addPropertyForm').addEventListener('submit', function(e) {
            if (selectedImages.length === 0) {
                e.preventDefault();
                alert('Please upload at least one image for the property.');
                return;
            }
            
            if (selectedImages.length > maxImages) {
                e.preventDefault();
                alert(`Maximum ${maxImages} images allowed.`);
                return;
            }
            
            // Validate location selection
            if (!provinceSelect.value || !districtSelect.value || !citySelect.value) {
                e.preventDefault();
                alert('Please select Province, District, and City.');
                return;
            }
        });
    </script>
</body>
</html>
