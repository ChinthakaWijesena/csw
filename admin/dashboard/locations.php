<?php

/**
 * Admin - Manage Locations (Provinces, Districts, Cities)
 */

require_once __DIR__ . '/../../config/config.php';

// Require admin login
require_admin();

// Flash helpers
if (!isset($_SESSION['flash'])) {
    $_SESSION['flash'] = [];
}
function set_flash($type, $message)
{
    $_SESSION['flash'][$type] = $message;
}
function get_flash($type)
{
    if (!empty($_SESSION['flash'][$type])) {
        $msg = $_SESSION['flash'][$type];
        unset($_SESSION['flash'][$type]);
        return $msg;
    }
    return null;
}

$scope = $_GET['scope'] ?? 'province'; // province | district | city
$action = $_POST['action'] ?? $_GET['action'] ?? null;

try {
    if ($scope === 'province') {
        if ($action === 'create') {
            $database->query(
                "INSERT INTO provinces (name, code, is_active, sort_order, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())",
                [trim($_POST['name'] ?? ''), trim($_POST['code'] ?? ''), isset($_POST['is_active']) ? 1 : 0, (int)($_POST['sort_order'] ?? 0)]
            );
            set_flash('success', 'Province added.');
            header('Location: locations.php?scope=province');
            exit;
        }
        if ($action === 'update') {
            $id = (int)($_POST['id'] ?? 0);
            $database->query(
                "UPDATE provinces SET name = ?, code = ?, is_active = ?, sort_order = ?, updated_at = NOW() WHERE id = ?",
                [trim($_POST['name'] ?? ''), trim($_POST['code'] ?? ''), isset($_POST['is_active']) ? 1 : 0, (int)($_POST['sort_order'] ?? 0), $id]
            );
            set_flash('success', 'Province updated.');
            header('Location: locations.php?scope=province');
            exit;
        }
        if ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            $database->query("DELETE FROM provinces WHERE id = ?", [$id]);
            set_flash('success', 'Province deleted.');
            header('Location: locations.php?scope=province');
            exit;
        }
    } elseif ($scope === 'district') {
        if ($action === 'create') {
            $database->query(
                "INSERT INTO districts (province_id, name, code, is_active, sort_order, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())",
                [(int)($_POST['province_id'] ?? 0), trim($_POST['name'] ?? ''), trim($_POST['code'] ?? ''), isset($_POST['is_active']) ? 1 : 0, (int)($_POST['sort_order'] ?? 0)]
            );
            set_flash('success', 'District added.');
            header('Location: locations.php?scope=district');
            exit;
        }
        if ($action === 'update') {
            $id = (int)($_POST['id'] ?? 0);
            $database->query(
                "UPDATE districts SET province_id = ?, name = ?, code = ?, is_active = ?, sort_order = ?, updated_at = NOW() WHERE id = ?",
                [(int)($_POST['province_id'] ?? 0), trim($_POST['name'] ?? ''), trim($_POST['code'] ?? ''), isset($_POST['is_active']) ? 1 : 0, (int)($_POST['sort_order'] ?? 0), $id]
            );
            set_flash('success', 'District updated.');
            header('Location: locations.php?scope=district');
            exit;
        }
        if ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            $database->query("DELETE FROM districts WHERE id = ?", [$id]);
            set_flash('success', 'District deleted.');
            header('Location: locations.php?scope=district');
            exit;
        }
    } elseif ($scope === 'city') {
        if ($action === 'create') {
            $database->query(
                "INSERT INTO cities (district_id, name, code, is_active, sort_order, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())",
                [(int)($_POST['district_id'] ?? 0), trim($_POST['name'] ?? ''), trim($_POST['code'] ?? ''), isset($_POST['is_active']) ? 1 : 0, (int)($_POST['sort_order'] ?? 0)]
            );
            set_flash('success', 'City added.');
            header('Location: locations.php?scope=city');
            exit;
        }
        if ($action === 'update') {
            $id = (int)($_POST['id'] ?? 0);
            $database->query(
                "UPDATE cities SET district_id = ?, name = ?, code = ?, is_active = ?, sort_order = ?, updated_at = NOW() WHERE id = ?",
                [(int)($_POST['district_id'] ?? 0), trim($_POST['name'] ?? ''), trim($_POST['code'] ?? ''), isset($_POST['is_active']) ? 1 : 0, (int)($_POST['sort_order'] ?? 0), $id]
            );
            set_flash('success', 'City updated.');
            header('Location: locations.php?scope=city');
            exit;
        }
        if ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            $database->query("DELETE FROM cities WHERE id = ?", [$id]);
            set_flash('success', 'City deleted.');
            header('Location: locations.php?scope=city');
            exit;
        }
    }
} catch (Exception $e) {
    set_flash('error', 'Operation failed.');
    header('Location: locations.php?scope=' . urlencode($scope));
    exit;
}

$provinces = $database->fetchAll("SELECT * FROM provinces ORDER BY sort_order, name");
$districts = $database->fetchAll("SELECT * FROM districts ORDER BY sort_order, name");
$cities = $database->fetchAll("SELECT * FROM cities ORDER BY sort_order, name");

// Edit target
$edit_id = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$edit = null;
if ($edit_id) {
    if ($scope === 'province') {
        $edit = $database->fetch("SELECT * FROM provinces WHERE id = ?", [$edit_id]);
    } elseif ($scope === 'district') {
        $edit = $database->fetch("SELECT * FROM districts WHERE id = ?", [$edit_id]);
    } else {
        $edit = $database->fetch("SELECT * FROM cities WHERE id = ?", [$edit_id]);
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Locations - <?php echo APP_NAME; ?></title>
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
            <?php $active_menu = 'locations'; include __DIR__ . '/_sidebar.php'; ?>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10">
                <div class="p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h1 class="h3 mb-0">Locations</h1>
                        <div class="btn-group">
                            <a class="btn btn-outline-primary <?php echo $scope === 'province' ? 'active' : ''; ?>" href="locations.php?scope=province">Provinces</a>
                            <a class="btn btn-outline-primary <?php echo $scope === 'district' ? 'active' : ''; ?>" href="locations.php?scope=district">Districts</a>
                            <a class="btn btn-outline-primary <?php echo $scope === 'city' ? 'active' : ''; ?>" href="locations.php?scope=city">Cities</a>
                        </div>
                    </div>

                    <?php if ($m = get_flash('success')): ?><div class="alert alert-success"><?php echo htmlspecialchars($m); ?></div><?php endif; ?>
                    <?php if ($m = get_flash('error')): ?><div class="alert alert-danger"><?php echo htmlspecialchars($m); ?></div><?php endif; ?>

                    <div class="card mb-4">
                        <div class="card-header"><?php echo $edit ? 'Edit' : 'Add New'; ?> <?php echo ucfirst($scope); ?></div>
                        <div class="card-body">
                            <form method="post" class="row g-3">
                                <input type="hidden" name="action" value="<?php echo $edit ? 'update' : 'create'; ?>">
                                <?php if ($edit): ?><input type="hidden" name="id" value="<?php echo (int)$edit['id']; ?>"><?php endif; ?>

                                <?php if ($scope === 'province'): ?>
                                    <div class="col-md-4">
                                        <label class="form-label">Name</label>
                                        <input type="text" name="name" class="form-control" required value="<?php echo htmlspecialchars($edit['name'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Code</label>
                                        <input type="text" name="code" class="form-control" required value="<?php echo htmlspecialchars($edit['code'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Sort</label>
                                        <input type="number" name="sort_order" class="form-control" value="<?php echo htmlspecialchars($edit['sort_order'] ?? '0'); ?>">
                                    </div>
                                    <div class="col-md-2 d-flex align-items-center">
                                        <div class="form-check mt-4">
                                            <input class="form-check-input" type="checkbox" name="is_active" id="is_active" <?php echo isset($edit['is_active']) ? ($edit['is_active'] ? 'checked' : '') : 'checked'; ?>>
                                            <label class="form-check-label" for="is_active">Active</label>
                                        </div>
                                    </div>
                                <?php elseif ($scope === 'district'): ?>
                                    <div class="col-md-4">
                                        <label class="form-label">Province</label>
                                        <select name="province_id" class="form-select" required>
                                            <option value="">Select</option>
                                            <?php foreach ($provinces as $p): ?>
                                                <option value="<?php echo (int)$p['id']; ?>" <?php echo ($edit && (int)$edit['province_id'] === (int)$p['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($p['name']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Name</label>
                                        <input type="text" name="name" class="form-control" required value="<?php echo htmlspecialchars($edit['name'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Code</label>
                                        <input type="text" name="code" class="form-control" required value="<?php echo htmlspecialchars($edit['code'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Sort</label>
                                        <input type="number" name="sort_order" class="form-control" value="<?php echo htmlspecialchars($edit['sort_order'] ?? '0'); ?>">
                                    </div>
                                    <div class="col-md-1 d-flex align-items-center">
                                        <div class="form-check mt-4">
                                            <input class="form-check-input" type="checkbox" name="is_active" id="is_active" <?php echo isset($edit['is_active']) ? ($edit['is_active'] ? 'checked' : '') : 'checked'; ?>>
                                            <label class="form-check-label" for="is_active">Active</label>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="col-md-4">
                                        <label class="form-label">District</label>
                                        <select name="district_id" class="form-select" required>
                                            <option value="">Select</option>
                                            <?php foreach ($districts as $d): ?>
                                                <option value="<?php echo (int)$d['id']; ?>" <?php echo ($edit && (int)$edit['district_id'] === (int)$d['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($d['name']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Name</label>
                                        <input type="text" name="name" class="form-control" required value="<?php echo htmlspecialchars($edit['name'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Code</label>
                                        <input type="text" name="code" class="form-control" required value="<?php echo htmlspecialchars($edit['code'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Sort</label>
                                        <input type="number" name="sort_order" class="form-control" value="<?php echo htmlspecialchars($edit['sort_order'] ?? '0'); ?>">
                                    </div>
                                    <div class="col-md-1 d-flex align-items-center">
                                        <div class="form-check mt-4">
                                            <input class="form-check-input" type="checkbox" name="is_active" id="is_active" <?php echo isset($edit['is_active']) ? ($edit['is_active'] ? 'checked' : '') : 'checked'; ?>>
                                            <label class="form-check-label" for="is_active">Active</label>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary"><?php echo $edit ? 'Update' : 'Save'; ?></button>
                                </div>
                            </form>
                            <?php if ($edit): ?>
                                <form method="post" class="mt-2 d-inline" onsubmit="return confirm('Delete this record?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo (int)$edit['id']; ?>">
                                    <button type="submit" class="btn btn-danger">Delete</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">All <?php echo ucfirst($scope); ?>s</div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped align-middle">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <?php if ($scope !== 'province'): ?><th><?php echo $scope === 'district' ? 'Province' : 'District'; ?></th><?php endif; ?>
                                            <th>Name</th>
                                            <th>Code</th>
                                            <th>Sort</th>
                                            <th>Active</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($scope === 'province'): ?>
                                            <?php foreach ($provinces as $p): ?>
                                                <tr>
                                                    <td><?php echo (int)$p['id']; ?></td>
                                                    <td><?php echo htmlspecialchars($p['name']); ?></td>
                                                    <td><?php echo htmlspecialchars($p['code']); ?></td>
                                                    <td><?php echo (int)$p['sort_order']; ?></td>
                                                    <td><?php echo $p['is_active'] ? 'Yes' : 'No'; ?></td>
                                                    <td><a class="btn btn-sm btn-outline-primary" href="locations.php?scope=province&edit=<?php echo (int)$p['id']; ?>">Edit</a></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php elseif ($scope === 'district'): ?>
                                            <?php foreach ($districts as $d): ?>
                                                <?php $pname = '';
                                                foreach ($provinces as $p) {
                                                    if ((int)$p['id'] === (int)$d['province_id']) {
                                                        $pname = $p['name'];
                                                        break;
                                                    }
                                                } ?>
                                                <tr>
                                                    <td><?php echo (int)$d['id']; ?></td>
                                                    <td><?php echo htmlspecialchars($pname); ?></td>
                                                    <td><?php echo htmlspecialchars($d['name']); ?></td>
                                                    <td><?php echo htmlspecialchars($d['code']); ?></td>
                                                    <td><?php echo (int)$d['sort_order']; ?></td>
                                                    <td><?php echo $d['is_active'] ? 'Yes' : 'No'; ?></td>
                                                    <td><a class="btn btn-sm btn-outline-primary" href="locations.php?scope=district&edit=<?php echo (int)$d['id']; ?>">Edit</a></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <?php foreach ($cities as $c): ?>
                                                <?php $dname = '';
                                                foreach ($districts as $d) {
                                                    if ((int)$d['id'] === (int)$c['district_id']) {
                                                        $dname = $d['name'];
                                                        break;
                                                    }
                                                } ?>
                                                <tr>
                                                    <td><?php echo (int)$c['id']; ?></td>
                                                    <td><?php echo htmlspecialchars($dname); ?></td>
                                                    <td><?php echo htmlspecialchars($c['name']); ?></td>
                                                    <td><?php echo htmlspecialchars($c['code']); ?></td>
                                                    <td><?php echo (int)$c['sort_order']; ?></td>
                                                    <td><?php echo $c['is_active'] ? 'Yes' : 'No'; ?></td>
                                                    <td><a class="btn btn-sm btn-outline-primary" href="locations.php?scope=city&edit=<?php echo (int)$c['id']; ?>">Edit</a></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
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