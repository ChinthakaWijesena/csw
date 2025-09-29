<?php
/**
 * Admin - Manage Property Types
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

$action = $_POST['action'] ?? $_GET['action'] ?? null;

try {
    if ($action === 'create') {
        $database->query(
            "INSERT INTO property_types (name, description, is_active, created_at) VALUES (?, ?, ?, NOW())",
            [
                trim($_POST['name'] ?? ''),
                trim($_POST['description'] ?? ''),
                isset($_POST['is_active']) ? 1 : 0,
            ]
        );
        set_flash('success', 'Property type added.');
        header('Location: property-types.php');
        exit;
    }
    if ($action === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        $database->query(
            "UPDATE property_types SET name = ?, description = ?, is_active = ? WHERE id = ?",
            [
                trim($_POST['name'] ?? ''),
                trim($_POST['description'] ?? ''),
                isset($_POST['is_active']) ? 1 : 0,
                $id,
            ]
        );
        set_flash('success', 'Property type updated.');
        header('Location: property-types.php');
        exit;
    }
    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
        $database->query("DELETE FROM property_types WHERE id = ?", [$id]);
        set_flash('success', 'Property type deleted.');
        header('Location: property-types.php');
        exit;
    }
} catch (Exception $e) {
    set_flash('error', 'Operation failed.');
    header('Location: property-types.php');
    exit;
}

// Fetch list
$types = $database->fetchAll("SELECT * FROM property_types ORDER BY name");

// Edit target
$edit_id = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$edit = null;
if ($edit_id) {
    $edit = $database->fetch("SELECT * FROM property_types WHERE id = ?", [$edit_id]);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Property Types - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- boostrap icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
        <!-- Bootstrap 5 CSS -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { background:#f8f9fa; }
    </style>
    </head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Admin Sidebar -->
            <?php $active_menu = 'property_types'; include __DIR__ . '/_sidebar.php'; ?>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10">
                <div class="p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h1 class="h3 mb-0">Property Types</h1>
                    </div>

        <?php if ($m = get_flash('success')): ?><div class="alert alert-success"><?php echo htmlspecialchars($m); ?></div><?php endif; ?>
        <?php if ($m = get_flash('error')): ?><div class="alert alert-danger"><?php echo htmlspecialchars($m); ?></div><?php endif; ?>

        <div class="card mb-4">
            <div class="card-header"><?php echo $edit ? 'Edit Type' : 'Add New Type'; ?></div>
            <div class="card-body">
                <form method="post" class="row g-3">
                    <input type="hidden" name="action" value="<?php echo $edit ? 'update' : 'create'; ?>">
                    <?php if ($edit): ?><input type="hidden" name="id" value="<?php echo (int)$edit['id']; ?>"><?php endif; ?>

                    <div class="col-md-6">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" class="form-control" required value="<?php echo htmlspecialchars($edit['name'] ?? ''); ?>">
                    </div>
                    <div class="col-md-6 d-flex align-items-center">
                        <div class="form-check mt-4">
                            <input class="form-check-input" type="checkbox" name="is_active" id="is_active" <?php echo isset($edit['is_active']) ? ($edit['is_active'] ? 'checked' : '') : 'checked'; ?>>
                            <label class="form-check-label" for="is_active">Active</label>
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Description</label>
                        <textarea name="description" rows="2" class="form-control"><?php echo htmlspecialchars($edit['description'] ?? ''); ?></textarea>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary"><?php echo $edit ? 'Update' : 'Save'; ?></button>
                    </div>
                </form>
                <?php if ($edit): ?>
                    <form method="post" class="mt-2" onsubmit="return confirm('Delete this type?');">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?php echo (int)$edit['id']; ?>">
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-header">All Property Types</div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped align-middle">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Description</th>
                                <th>Active</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($types as $t): ?>
                                <tr>
                                    <td><?php echo (int)$t['id']; ?></td>
                                    <td><?php echo htmlspecialchars($t['name']); ?></td>
                                    <td><?php echo htmlspecialchars($t['description'] ?? ''); ?></td>
                                    <td><?php echo $t['is_active'] ? 'Yes' : 'No'; ?></td>
                                    <td>
                                        <a class="btn btn-sm btn-outline-primary" href="property-types.php?edit=<?php echo (int)$t['id']; ?>">Edit</a>
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
</body>
</html>


