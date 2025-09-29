<?php
/**
 * Admin Users Management
 */

require_once __DIR__ . '/../../config/config.php';

// Require admin login
require_admin();

$user_model = new User();

// Handle form submissions
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                try {
                    $user_data = [
                        'name' => trim($_POST['name']),
                        'phone' => trim($_POST['phone']),
                        'email' => trim($_POST['email']) ?: null,
                        'user_type' => $_POST['user_type'],
                        'is_verified' => isset($_POST['is_verified']) ? 1 : 0,
                        'is_active' => isset($_POST['is_active']) ? 1 : 0
                    ];
                    
                    // Validate required fields
                    if (empty($user_data['name']) || empty($user_data['phone'])) {
                        throw new Exception('Name and phone are required');
                    }
                    
                    // Check if phone already exists
                    if ($user_model->exists($user_data['phone'])) {
                        throw new Exception('Phone number already exists');
                    }
                    
                    $user_id = $user_model->create($user_data);
                    if ($user_id) {
                        $message = 'User created successfully';
                        $message_type = 'success';
                    } else {
                        throw new Exception('Failed to create user');
                    }
                } catch (Exception $e) {
                    $message = $e->getMessage();
                    $message_type = 'danger';
                }
                break;
                
            case 'update':
                try {
                    $user_id = $_POST['user_id'];
                    $update_data = [
                        'name' => trim($_POST['name']),
                        'email' => trim($_POST['email']) ?: null,
                        'is_verified' => isset($_POST['is_verified']) ? 1 : 0,
                        'is_active' => isset($_POST['is_active']) ? 1 : 0
                    ];
                    
                    if (empty($update_data['name'])) {
                        throw new Exception('Name is required');
                    }
                    
                    if ($user_model->update($user_id, $update_data)) {
                        $message = 'User updated successfully';
                        $message_type = 'success';
                    } else {
                        throw new Exception('Failed to update user');
                    }
                } catch (Exception $e) {
                    $message = $e->getMessage();
                    $message_type = 'danger';
                }
                break;
                
            case 'delete':
                try {
                    $user_id = $_POST['user_id'];
                    if ($user_model->delete($user_id)) {
                        $message = 'User deleted successfully';
                        $message_type = 'success';
                    } else {
                        throw new Exception('Failed to delete user');
                    }
                } catch (Exception $e) {
                    $message = $e->getMessage();
                    $message_type = 'danger';
                }
                break;
        }
    }
}

// Get users with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$user_type_filter = $_GET['user_type'] ?? '';
$status_filter = $_GET['status'] ?? 'all';
$search_query = $_GET['search'] ?? '';

if (!empty($search_query)) {
    $users = $user_model->search($search_query, $user_type_filter, $page, $limit, $status_filter);
    $total_users = $user_model->getCount($user_type_filter, $status_filter);
} else {
    $users = $user_model->getAll($page, $limit, $user_type_filter, $status_filter);
    $total_users = $user_model->getCount($user_type_filter, $status_filter);
}

$total_pages = ceil($total_users / $limit);

// Get user for editing
$edit_user = null;
if (isset($_GET['edit'])) {
    $edit_user = $user_model->getById($_GET['edit']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users Management - <?php echo APP_NAME; ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Admin Sidebar -->
            <div class="col-md-3 col-lg-2 px-0 bg-dark">
                <div class="p-3">
                    <h4 class="text-white mb-4">
                        <i class="fas fa-cog me-2"></i>Admin Panel
                    </h4>
                    
                    <ul class="nav nav-pills flex-column">
                        <li class="nav-item">
                            <a class="nav-link text-white-50" href="index.php">
                                <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active text-white" href="users.php">
                                <i class="fas fa-users me-2"></i>Users
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white-50" href="properties.php">
                                <i class="fas fa-home me-2"></i>Properties
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white-50" href="bookings.php">
                                <i class="fas fa-calendar me-2"></i>Bookings
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white-50" href="payments.php">
                                <i class="fas fa-credit-card me-2"></i>Payments
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white-50" href="reports.php">
                                <i class="fas fa-chart-bar me-2"></i>Reports
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white-50" href="settings.php">
                                <i class="fas fa-cog me-2"></i>Settings
                            </a>
                        </li>
                        <li class="nav-item mt-3">
                            <a class="nav-link text-white-50" href="../../frontend/index.php">
                                <i class="fas fa-external-link-alt me-2"></i>View Site
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white-50" href="../../frontend/logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i>Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-9 col-lg-10">
                <div class="p-4">
                    <!-- Header -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h1 class="h3">Users Management</h1>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                            <i class="fas fa-plus me-2"></i>Add User
                        </button>
                    </div>
                    
                    <!-- Messages -->
                    <?php if ($message): ?>
                        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                            <?php echo htmlspecialchars($message); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Filters -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <form method="GET" class="row g-3">
                                <div class="col-md-4">
                                    <label for="search" class="form-label">Search</label>
                                    <input type="text" class="form-control" id="search" name="search" 
                                           value="<?php echo htmlspecialchars($search_query); ?>" 
                                           placeholder="Name, phone, or email">
                                </div>
                                <div class="col-md-3">
                                    <label for="user_type" class="form-label">User Type</label>
                                    <select class="form-select" id="user_type" name="user_type">
                                        <option value="">All Types</option>
                                        <option value="customer" <?php echo $user_type_filter === 'customer' ? 'selected' : ''; ?>>Customer</option>
                                        <option value="owner" <?php echo $user_type_filter === 'owner' ? 'selected' : ''; ?>>Owner</option>
                                        <option value="admin" <?php echo $user_type_filter === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="status" class="form-label">Status</label>
                                    <select class="form-select" id="status" name="status">
                                        <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All</option>
                                        <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                                        <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">&nbsp;</label>
                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-outline-primary">Filter</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Users Table -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Users List (<?php echo $total_users; ?> total)</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($users)): ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">No users found</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Name</th>
                                                <th>Phone</th>
                                                <th>Email</th>
                                                <th>Type</th>
                                                <th>Status</th>
                                                <th>Verified</th>
                                                <th>Created</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($users as $user): ?>
                                                <tr>
                                                    <td><?php echo $user['id']; ?></td>
                                                    <td><?php echo htmlspecialchars($user['name']); ?></td>
                                                    <td><?php echo htmlspecialchars($user['phone']); ?></td>
                                                    <td><?php echo htmlspecialchars($user['email'] ?? 'N/A'); ?></td>
                                                    <td>
                                                        <span class="badge bg-<?php echo $user['user_type'] === 'admin' ? 'danger' : ($user['user_type'] === 'owner' ? 'info' : 'primary'); ?>">
                                                            <?php echo ucfirst($user['user_type']); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-<?php echo $user['is_active'] ? 'success' : 'secondary'; ?>">
                                                            <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-<?php echo $user['is_verified'] ? 'success' : 'warning'; ?>">
                                                            <?php echo $user['is_verified'] ? 'Verified' : 'Pending'; ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo format_date($user['created_at']); ?></td>
                                                    <td>
                                                        <div class="btn-group btn-group-sm">
                                                            <button class="btn btn-outline-primary" 
                                                                    onclick="editUser(<?php echo htmlspecialchars(json_encode($user)); ?>)">
                                                                <i class="fas fa-edit"></i>
                                                            </button>
                                                            <button class="btn btn-outline-danger" 
                                                                    onclick="deleteUser(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['name']); ?>')">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                
                                <!-- Pagination -->
                                <?php if ($total_pages > 1): ?>
                                    <nav aria-label="Users pagination">
                                        <ul class="pagination justify-content-center">
                                            <?php if ($page > 1): ?>
                                                <li class="page-item">
                                                    <a class="page-link" href="?page=<?php echo $page - 1; ?>&user_type=<?php echo $user_type_filter; ?>&status=<?php echo $status_filter; ?>&search=<?php echo urlencode($search_query); ?>">Previous</a>
                                                </li>
                                            <?php endif; ?>
                                            
                                            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                                    <a class="page-link" href="?page=<?php echo $i; ?>&user_type=<?php echo $user_type_filter; ?>&status=<?php echo $status_filter; ?>&search=<?php echo urlencode($search_query); ?>"><?php echo $i; ?></a>
                                                </li>
                                            <?php endfor; ?>
                                            
                                            <?php if ($page < $total_pages): ?>
                                                <li class="page-item">
                                                    <a class="page-link" href="?page=<?php echo $page + 1; ?>&user_type=<?php echo $user_type_filter; ?>&status=<?php echo $status_filter; ?>&search=<?php echo urlencode($search_query); ?>">Next</a>
                                                </li>
                                            <?php endif; ?>
                                        </ul>
                                    </nav>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add User Modal -->
    <div class="modal fade" id="addUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="action" value="add">
                    <div class="modal-header">
                        <h5 class="modal-title">Add New User</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="add_name" class="form-label">Name *</label>
                            <input type="text" class="form-control" id="add_name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="add_phone" class="form-label">Phone *</label>
                            <input type="tel" class="form-control" id="add_phone" name="phone" required>
                        </div>
                        <div class="mb-3">
                            <label for="add_email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="add_email" name="email">
                        </div>
                        <div class="mb-3">
                            <label for="add_user_type" class="form-label">User Type *</label>
                            <select class="form-select" id="add_user_type" name="user_type" required>
                                <option value="customer">Customer</option>
                                <option value="owner">Owner</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="add_is_verified" name="is_verified">
                                <label class="form-check-label" for="add_is_verified">
                                    Verified
                                </label>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="add_is_active" name="is_active" checked>
                                <label class="form-check-label" for="add_is_active">
                                    Active
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div class="modal fade" id="editUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="user_id" id="edit_user_id">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit User</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="edit_name" class="form-label">Name *</label>
                            <input type="text" class="form-control" id="edit_name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_phone" class="form-label">Phone</label>
                            <input type="tel" class="form-control" id="edit_phone" name="phone" readonly>
                            <div class="form-text">Phone number cannot be changed</div>
                        </div>
                        <div class="mb-3">
                            <label for="edit_email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="edit_email" name="email">
                        </div>
                        <div class="mb-3">
                            <label for="edit_user_type" class="form-label">User Type</label>
                            <input type="text" class="form-control" id="edit_user_type" readonly>
                            <div class="form-text">User type cannot be changed</div>
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="edit_is_verified" name="is_verified">
                                <label class="form-check-label" for="edit_is_verified">
                                    Verified
                                </label>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="edit_is_active" name="is_active">
                                <label class="form-check-label" for="edit_is_active">
                                    Active
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete User Modal -->
    <div class="modal fade" id="deleteUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="user_id" id="delete_user_id">
                    <div class="modal-header">
                        <h5 class="modal-title">Delete User</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to delete user <strong id="delete_user_name"></strong>?</p>
                        <p class="text-danger">This action cannot be undone.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Delete User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function editUser(user) {
            document.getElementById('edit_user_id').value = user.id;
            document.getElementById('edit_name').value = user.name;
            document.getElementById('edit_phone').value = user.phone;
            document.getElementById('edit_email').value = user.email || '';
            document.getElementById('edit_user_type').value = user.user_type;
            document.getElementById('edit_is_verified').checked = user.is_verified == 1;
            document.getElementById('edit_is_active').checked = user.is_active == 1;
            
            new bootstrap.Modal(document.getElementById('editUserModal')).show();
        }
        
        function deleteUser(userId, userName) {
            document.getElementById('delete_user_id').value = userId;
            document.getElementById('delete_user_name').textContent = userName;
            
            new bootstrap.Modal(document.getElementById('deleteUserModal')).show();
        }
    </script>
</body>
</html>
