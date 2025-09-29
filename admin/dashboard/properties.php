<?php
/**
 * Admin - Manage Properties (Thin CRUD)
 */

require_once __DIR__ . '/../../config/config.php';

// Require admin login
require_admin();

// Flash helpers
if (!isset($_SESSION['flash'])) {
    $_SESSION['flash'] = [];
}
function set_flash($type, $message) {
    $_SESSION['flash'][$type] = $message;
}
function get_flash($type) {
    if (!empty($_SESSION['flash'][$type])) {
        $msg = $_SESSION['flash'][$type];
        unset($_SESSION['flash'][$type]);
        return $msg;
    }
    return null;
}

// Sanitize a string to be safe for use as a single folder path segment
if (!function_exists('sanitize_folder_segment')) {
    function sanitize_folder_segment($name) {
        $name = strtolower($name ?? '');
        // Replace any non letter/number with hyphen
        $name = preg_replace('/[^a-z0-9]+/i', '-', $name);
        // Trim hyphens from ends
        $name = trim($name, '-');
        // Fallback if empty
        if ($name === '') {
            $name = 'owner';
        }
        // Limit length to avoid filesystem issues
        return substr($name, 0, 60);
    }
}

// Preload selects
$owners = $database->fetchAll(
    "SELECT id, name FROM users WHERE user_type = 'owner' AND is_active = 1 ORDER BY name"
);
$property_types = $database->fetchAll(
    "SELECT id, name FROM property_types WHERE is_active = 1 ORDER BY name"
);
$provinces = $database->fetchAll(
    "SELECT id, name FROM provinces WHERE is_active = 1 ORDER BY sort_order, name"
);
$districts = $database->fetchAll(
    "SELECT id, province_id, name FROM districts WHERE is_active = 1 ORDER BY sort_order, name"
);
$cities = $database->fetchAll(
    "SELECT id, district_id, name FROM cities WHERE is_active = 1 ORDER BY sort_order, name"
);

// Map owners by id for quick lookup of owner name
$ownersById = [];
foreach ($owners as $o) {
    $ownersById[(int)$o['id']] = $o['name'];
}

// Ensure upload directory exists
$properties_upload_dir = rtrim(UPLOAD_PATH, '/\\') . '/properties/';
ensure_directory_exists($properties_upload_dir);

// Handle actions
$action = $_POST['action'] ?? $_GET['action'] ?? null;

try {
    if ($action === 'create') {
        // Validate images: min 1, max 15
        $hasFiles = isset($_FILES['images']) && is_array($_FILES['images']['name']) && !empty($_FILES['images']['name'][0]);
        if (!$hasFiles) {
            set_flash('error', 'Please upload at least one image (max 15).');
            header('Location: properties.php');
            exit;
        }
        $upload_names = array_filter($_FILES['images']['name'] ?? [], fn($n) => $n !== '');
        if (count($upload_names) > 15) {
            set_flash('error', 'You can upload a maximum of 15 images.');
            header('Location: properties.php');
            exit;
        }
        // Prepare owner-specific folder
        $ownerIdForFolder = (int)($_POST['owner_id'] ?? 0);
        $ownerNameForFolder = $ownersById[$ownerIdForFolder] ?? 'owner';
        $ownerFolder = $ownerIdForFolder . '_' . sanitize_folder_segment($ownerNameForFolder);
        $owner_upload_dir = $properties_upload_dir . $ownerFolder . '/';
        ensure_directory_exists($owner_upload_dir);
        $database->query(
            "INSERT INTO properties (
                owner_id, title, description, property_type_id, status,
                price, rent, security_deposit, area_sqft, bedrooms,
                bathrooms, floor_number, total_floors, furnished, parking,
                year_built, latitude, longitude, city_id, district_id, province_id, is_featured
            ) VALUES (
                ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?, ?
            )",
            [
                (int)($_POST['owner_id'] ?? 0),
                trim($_POST['title'] ?? ''),
                trim($_POST['description'] ?? ''),
                $_POST['property_type_id'] !== '' ? (int)$_POST['property_type_id'] : null,
                in_array($_POST['status'] ?? 'rent', ['sale','rent']) ? $_POST['status'] : 'rent',
                $_POST['price'] !== '' ? (float)$_POST['price'] : null,
                $_POST['rent'] !== '' ? (float)$_POST['rent'] : null,
                $_POST['security_deposit'] !== '' ? (float)$_POST['security_deposit'] : null,
                $_POST['area_sqft'] !== '' ? (float)$_POST['area_sqft'] : null,
                $_POST['bedrooms'] !== '' ? (int)$_POST['bedrooms'] : null,
                $_POST['bathrooms'] !== '' ? (int)$_POST['bathrooms'] : null,
                $_POST['floor_number'] !== '' ? (int)$_POST['floor_number'] : null,
                $_POST['total_floors'] !== '' ? (int)$_POST['total_floors'] : null,
                isset($_POST['furnished']) ? 1 : 0,
                $_POST['parking'] !== '' ? (int)$_POST['parking'] : 0,
                $_POST['year_built'] !== '' ? $_POST['year_built'] : null,
                $_POST['latitude'] !== '' ? (float)$_POST['latitude'] : null,
                $_POST['longitude'] !== '' ? (float)$_POST['longitude'] : null,
                (int)($_POST['city_id'] ?? 0),
                (int)($_POST['district_id'] ?? 0),
                (int)($_POST['province_id'] ?? 0),
                isset($_POST['is_featured']) ? 1 : 0,
            ]
        );
        $new_property_id = (int)$database->lastInsertId();

        // Upload images and insert records
        $totalUploaded = 0;
        $filenames = $_FILES['images']['name'] ?? [];
        $tmp_files = $_FILES['images']['tmp_name'] ?? [];
        $errors = $_FILES['images']['error'] ?? [];
        $sizes = $_FILES['images']['size'] ?? [];

        for ($i = 0; $i < count($filenames); $i++) {
            if (empty($filenames[$i])) continue;
            if ($errors[$i] !== UPLOAD_ERR_OK) continue;
            if ($sizes[$i] > MAX_FILE_SIZE) continue;
            if (!validate_file_type($filenames[$i], ALLOWED_IMAGE_TYPES)) continue;

            $unique = generate_unique_filename($filenames[$i]);
            $dest_path = $owner_upload_dir . $unique;
            if (move_uploaded_file($tmp_files[$i], $dest_path)) {
                $public_path = 'assets/uploads/properties/' . $ownerFolder . '/' . $unique;
                $isPrimary = $totalUploaded === 0 ? 1 : 0;
                $database->query(
                    'INSERT INTO property_images (property_id, image_url, is_primary, created_at) VALUES (?, ?, ?, NOW())',
                    [$new_property_id, $public_path, $isPrimary]
                );
                $totalUploaded++;
            }
        }
        if ($totalUploaded < 1) {
            set_flash('error', 'Failed to save images. Please try again.');
            header('Location: properties.php');
            exit;
        }
        set_flash('success', 'Property added successfully.');
        header('Location: properties.php');
        exit;
    }

    if ($action === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        $database->query(
            "UPDATE properties SET 
                owner_id = ?, title = ?, description = ?, property_type_id = ?, status = ?,
                price = ?, rent = ?, security_deposit = ?, area_sqft = ?, bedrooms = ?,
                bathrooms = ?, floor_number = ?, total_floors = ?, furnished = ?, parking = ?,
                year_built = ?, latitude = ?, longitude = ?, city_id = ?, district_id = ?, province_id = ?, is_featured = ?
             WHERE id = ?",
            [
                (int)($_POST['owner_id'] ?? 0),
                trim($_POST['title'] ?? ''),
                trim($_POST['description'] ?? ''),
                $_POST['property_type_id'] !== '' ? (int)$_POST['property_type_id'] : null,
                in_array($_POST['status'] ?? 'rent', ['sale','rent']) ? $_POST['status'] : 'rent',
                $_POST['price'] !== '' ? (float)$_POST['price'] : null,
                $_POST['rent'] !== '' ? (float)$_POST['rent'] : null,
                $_POST['security_deposit'] !== '' ? (float)$_POST['security_deposit'] : null,
                $_POST['area_sqft'] !== '' ? (float)$_POST['area_sqft'] : null,
                $_POST['bedrooms'] !== '' ? (int)$_POST['bedrooms'] : null,
                $_POST['bathrooms'] !== '' ? (int)$_POST['bathrooms'] : null,
                $_POST['floor_number'] !== '' ? (int)$_POST['floor_number'] : null,
                $_POST['total_floors'] !== '' ? (int)$_POST['total_floors'] : null,
                isset($_POST['furnished']) ? 1 : 0,
                $_POST['parking'] !== '' ? (int)$_POST['parking'] : 0,
                $_POST['year_built'] !== '' ? $_POST['year_built'] : null,
                $_POST['latitude'] !== '' ? (float)$_POST['latitude'] : null,
                $_POST['longitude'] !== '' ? (float)$_POST['longitude'] : null,
                (int)($_POST['city_id'] ?? 0),
                (int)($_POST['district_id'] ?? 0),
                (int)($_POST['province_id'] ?? 0),
                isset($_POST['is_featured']) ? 1 : 0,
                $id,
            ]
        );

        // Handle additional image uploads (optional)
        if (isset($_FILES['images']) && is_array($_FILES['images']['name']) && !empty($_FILES['images']['name'][0])) {
            $current = $database->fetch('SELECT COUNT(*) AS c FROM property_images WHERE property_id = ?', [$id]);
            $currentCount = (int)($current['c'] ?? 0);
            $remaining = max(0, 15 - $currentCount);
            if ($remaining <= 0) {
                set_flash('error', 'This property already has the maximum of 15 images.');
                header('Location: properties.php?edit=' . $id);
                exit;
            }

            // Prepare owner-specific folder for update as well
            $ownerIdForFolder = (int)($_POST['owner_id'] ?? 0);
            $ownerNameForFolder = $ownersById[$ownerIdForFolder] ?? 'owner';
            $ownerFolder = $ownerIdForFolder . '_' . sanitize_folder_segment($ownerNameForFolder);
            $owner_upload_dir = $properties_upload_dir . $ownerFolder . '/';
            ensure_directory_exists($owner_upload_dir);

            $filenames = $_FILES['images']['name'] ?? [];
            $tmp_files = $_FILES['images']['tmp_name'] ?? [];
            $errors = $_FILES['images']['error'] ?? [];
            $sizes = $_FILES['images']['size'] ?? [];
            $added = 0;
            for ($i = 0; $i < count($filenames) && $added < $remaining; $i++) {
                if (empty($filenames[$i])) continue;
                if ($errors[$i] !== UPLOAD_ERR_OK) continue;
                if ($sizes[$i] > MAX_FILE_SIZE) continue;
                if (!validate_file_type($filenames[$i], ALLOWED_IMAGE_TYPES)) continue;
                $unique = generate_unique_filename($filenames[$i]);
                $dest_path = $owner_upload_dir . $unique;
                if (move_uploaded_file($tmp_files[$i], $dest_path)) {
                    $public_path = 'assets/uploads/properties/' . $ownerFolder . '/' . $unique;
                    // Only set primary if none exists
                    $hasPrimary = $database->fetch('SELECT id FROM property_images WHERE property_id = ? AND is_primary = 1 LIMIT 1', [$id]);
                    $isPrimary = $hasPrimary ? 0 : 1;
                    $database->query(
                        'INSERT INTO property_images (property_id, image_url, is_primary, created_at) VALUES (?, ?, ?, NOW())',
                        [$id, $public_path, $isPrimary]
                    );
                    $added++;
                }
            }
            if ($added === 0) {
                set_flash('error', 'No images were added (invalid type/size or upload error).');
                header('Location: properties.php?edit=' . $id);
                exit;
            }
        }
        set_flash('success', 'Property updated successfully.');
        header('Location: properties.php');
        exit;
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
        $database->query("DELETE FROM properties WHERE id = ?", [$id]);
        set_flash('success', 'Property deleted.');
        header('Location: properties.php');
        exit;
    }
} catch (Exception $e) {
    set_flash('error', 'Operation failed.');
    header('Location: properties.php');
    exit;
}

// Fetch properties list
$properties = $database->fetchAll(
    "SELECT 
        p.*, 
        u.name AS owner_name, 
        c.name AS city_name, 
        d.name AS district_name,
        pr.name AS province_name,
        pt.name AS type_name
     FROM properties p
     JOIN users u ON u.id = p.owner_id
     LEFT JOIN cities c ON c.id = p.city_id
     LEFT JOIN districts d ON d.id = p.district_id
     LEFT JOIN provinces pr ON pr.id = p.province_id
     LEFT JOIN property_types pt ON pt.id = p.property_type_id
     ORDER BY p.created_at DESC"
);

// If editing
$edit_id = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$edit_property = null;
if ($edit_id) {
    $edit_property = $database->fetch("SELECT * FROM properties WHERE id = ?", [$edit_id]);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Properties - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <!-- Bootstrap 5 CSS -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-light">
    <div class="container-fluid">
        <div class="row">
            <!-- Admin Sidebar -->
            <?php $active_menu = 'properties'; include __DIR__ . '/_sidebar.php'; ?>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10">
                <div class="p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h1 class="h3 mb-0">Manage Properties</h1>
                    </div>

                    <?php if ($msg = get_flash('success')): ?>
                        <div class="alert alert-success"><?php echo htmlspecialchars($msg); ?></div>
                    <?php endif; ?>
                    <?php if ($msg = get_flash('error')): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($msg); ?></div>
                    <?php endif; ?>

                    <div class="card mb-4">
            <div class="card-header">
                <?php echo $edit_property ? 'Edit Property' : 'Add New Property'; ?>
            </div>
            <div class="card-body">
                <form method="post" class="row g-3" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="<?php echo $edit_property ? 'update' : 'create'; ?>">
                    <?php if ($edit_property): ?>
                        <input type="hidden" name="id" value="<?php echo (int)$edit_property['id']; ?>">
                    <?php endif; ?>

                    <div class="col-md-4">
                        <label class="form-label">Owner</label>
                        <select name="owner_id" class="form-select" required>
                            <option value="">Select owner</option>
                            <?php foreach ($owners as $o): ?>
                                <option value="<?php echo (int)$o['id']; ?>" <?php echo ($edit_property && $edit_property['owner_id'] == $o['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($o['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Title</label>
                        <input type="text" name="title" class="form-control" required value="<?php echo htmlspecialchars($edit_property['title'] ?? ''); ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Property Type</label>
                        <select name="property_type_id" class="form-select">
                            <option value="">None</option>
                            <?php foreach ($property_types as $t): ?>
                                <option value="<?php echo (int)$t['id']; ?>" <?php echo ($edit_property && (int)($edit_property['property_type_id'] ?? 0) === (int)$t['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($t['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Description</label>
                        <textarea name="description" rows="3" class="form-control"><?php echo htmlspecialchars($edit_property['description'] ?? ''); ?></textarea>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Property Images <small class="text-muted">(min 1, max 15; jpg, jpeg, png, gif, webp; up to <?php echo (int)(MAX_FILE_SIZE/1024/1024); ?>MB each)</small></label>
                        <input type="file" name="images[]" class="form-control" accept="image/*" multiple>
                        <div class="mt-2" id="imagePreview" style="display:flex;gap:8px;flex-wrap:wrap;"></div>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <?php $status = $edit_property['status'] ?? 'rent'; ?>
                            <option value="rent" <?php echo $status==='rent'?'selected':''; ?>>Rent</option>
                            <option value="sale" <?php echo $status==='sale'?'selected':''; ?>>Sale</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Price (sale)</label>
                        <input type="number" step="0.01" name="price" class="form-control" value="<?php echo htmlspecialchars($edit_property['price'] ?? ''); ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Rent (monthly)</label>
                        <input type="number" step="0.01" name="rent" class="form-control" value="<?php echo htmlspecialchars($edit_property['rent'] ?? ''); ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Deposit</label>
                        <input type="number" step="0.01" name="security_deposit" class="form-control" value="<?php echo htmlspecialchars($edit_property['security_deposit'] ?? ''); ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Bedrooms</label>
                        <input type="number" name="bedrooms" class="form-control" value="<?php echo htmlspecialchars($edit_property['bedrooms'] ?? ''); ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Bathrooms</label>
                        <input type="number" name="bathrooms" class="form-control" value="<?php echo htmlspecialchars($edit_property['bathrooms'] ?? ''); ?>">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Area (sqft)</label>
                        <input type="number" step="0.01" name="area_sqft" class="form-control" value="<?php echo htmlspecialchars($edit_property['area_sqft'] ?? ''); ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Floor #</label>
                        <input type="number" name="floor_number" class="form-control" value="<?php echo htmlspecialchars($edit_property['floor_number'] ?? ''); ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Total Floors</label>
                        <input type="number" name="total_floors" class="form-control" value="<?php echo htmlspecialchars($edit_property['total_floors'] ?? ''); ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Parking</label>
                        <input type="number" name="parking" class="form-control" value="<?php echo htmlspecialchars($edit_property['parking'] ?? ''); ?>">
                    </div>
                    <div class="col-md-2 d-flex align-items-center">
                        <div class="form-check mt-4">
                            <input class="form-check-input" type="checkbox" name="furnished" id="furnished" <?php echo !empty($edit_property['furnished']) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="furnished">Furnished</label>
                        </div>
                    </div>
                    <div class="col-md-2 d-flex align-items-center">
                        <div class="form-check mt-4">
                            <input class="form-check-input" type="checkbox" name="is_featured" id="is_featured" <?php echo !empty($edit_property['is_featured']) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="is_featured">Featured</label>
                        </div>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Year Built</label>
                        <input type="number" name="year_built" class="form-control" value="<?php echo htmlspecialchars($edit_property['year_built'] ?? ''); ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Latitude</label>
                        <input type="text" name="latitude" class="form-control" value="<?php echo htmlspecialchars($edit_property['latitude'] ?? ''); ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Longitude</label>
                        <input type="text" name="longitude" class="form-control" value="<?php echo htmlspecialchars($edit_property['longitude'] ?? ''); ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Province</label>
                        <select name="province_id" class="form-select" required>
                            <option value="">Select province</option>
                            <?php foreach ($provinces as $p): ?>
                                <option value="<?php echo (int)$p['id']; ?>" <?php echo ($edit_property && $edit_property['province_id'] == $p['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($p['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">District</label>
                        <select name="district_id" class="form-select" required>
                            <option value="">Select district</option>
                            <?php foreach ($districts as $d): ?>
                                <option value="<?php echo (int)$d['id']; ?>" <?php echo ($edit_property && $edit_property['district_id'] == $d['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($d['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">City</label>
                        <select name="city_id" class="form-select" required>
                            <option value="">Select city</option>
                            <?php foreach ($cities as $c): ?>
                                <option value="<?php echo (int)$c['id']; ?>" <?php echo ($edit_property && $edit_property['city_id'] == $c['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($c['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-12 d-flex gap-2">
                        <button type="submit" class="btn btn-primary"><?php echo $edit_property ? 'Update' : 'Save'; ?></button>
                        <?php if ($edit_property): ?>
                            <form method="post" onsubmit="return confirm('Delete this property?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo (int)$edit_property['id']; ?>">
                                <button type="submit" class="btn btn-danger">Delete</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
                    </div>

                    <div class="card">
            <div class="card-header">All Properties</div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped align-middle">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Title</th>
                                <th>Owner</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Rent</th>
                                <th>City</th>
                                <th>Featured</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($properties as $p): ?>
                                <tr>
                                    <td><?php echo (int)$p['id']; ?></td>
                                    <td><?php echo htmlspecialchars($p['title']); ?></td>
                                    <td><?php echo htmlspecialchars($p['owner_name']); ?></td>
                                    <td><?php echo htmlspecialchars($p['type_name'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars(ucfirst($p['status'])); ?></td>
                                    <td><?php echo $p['rent'] !== null ? 'LKR ' . number_format($p['rent']) : '-'; ?></td>
                                    <td><?php echo htmlspecialchars($p['city_name'] ?? ''); ?></td>
                                    <td><?php echo !empty($p['is_featured']) ? 'Yes' : 'No'; ?></td>
                                    <td>
                                        <a class="btn btn-sm btn-outline-primary" href="properties.php?edit=<?php echo (int)$p['id']; ?>">Edit</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Client-side preview for selected images (before form submit)
        (function() {
            var fileInput = document.querySelector('input[name="images[]"]');
            var preview = document.getElementById('imagePreview');
            if (!fileInput || !preview) return;

            var ALLOWED = ['image/jpeg','image/png','image/gif','image/webp'];
            var MAX_SIZE = <?php echo (int)MAX_FILE_SIZE; ?>; // bytes
            var MAX_FILES = 15;

            function clearPreview() {
                while (preview.firstChild) preview.removeChild(preview.firstChild);
            }

            function addThumb(src, name) {
                var wrap = document.createElement('div');
                wrap.style.width = '100px';
                wrap.style.height = '100px';
                wrap.style.border = '1px solid #ddd';
                wrap.style.borderRadius = '6px';
                wrap.style.overflow = 'hidden';
                wrap.style.display = 'flex';
                wrap.style.alignItems = 'center';
                wrap.style.justifyContent = 'center';
                wrap.title = name || '';

                var img = document.createElement('img');
                img.src = src;
                img.alt = name || '';
                img.style.maxWidth = '100%';
                img.style.maxHeight = '100%';
                wrap.appendChild(img);
                preview.appendChild(wrap);
            }

            fileInput.addEventListener('change', function(e) {
                var files = Array.prototype.slice.call(e.target.files || []);
                clearPreview();

                if (files.length > MAX_FILES) {
                    files = files.slice(0, MAX_FILES);
                }

                files.forEach(function(file) {
                    if (ALLOWED.indexOf(file.type) === -1) return;
                    if (file.size > MAX_SIZE) return;

                    var reader = new FileReader();
                    reader.onload = function(evt) {
                        addThumb(evt.target.result, file.name);
                    };
                    reader.readAsDataURL(file);
                });
            });
        })();
        // You can progressively enhance with AJAX for dependent province/district/city
    </script>
</body>
</html>


