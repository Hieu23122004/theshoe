<?php
require_once '../../includes/auth.php';
require_once '../../includes/database.php';
$message = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create_user':
                $fullname = trim($_POST['fullname']);
                $email = trim($_POST['email']);
                $password = $_POST['password'];
                $phone = trim($_POST['phone']);
                $address = trim($_POST['address']);
                $role = $_POST['role'];
                if (!empty($fullname) && !empty($email) && !empty($password)) {
                    $check_stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
                    $check_stmt->bind_param("s", $email);
                    $check_stmt->execute();
                    $result = $check_stmt->get_result();
                    if ($result->num_rows == 0) {
                        $password_hash = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $conn->prepare("INSERT INTO users (fullname, email, password_hash, phone, address, role) VALUES (?, ?, ?, ?, ?, ?)");
                        $stmt->bind_param("ssssss", $fullname, $email, $password_hash, $phone, $address, $role);
                        if ($stmt->execute()) {
                            $message = 'User created successfully.';
                        } else {
                            $error = 'Failed to create user.';
                        }
                    } else {
                        $error = 'Email already exists.';
                    }
                } else {
                    $error = 'Full name, email, and password are required.';
                }
                break;
            case 'edit_user':
                $user_id = $_POST['user_id'];
                $fullname = trim($_POST['fullname']);
                $email = trim($_POST['email']);
                $phone = trim($_POST['phone']);
                $address = trim($_POST['address']);
                $role = $_POST['role'];
                if (!empty($fullname) && !empty($email)) {
                    $check_stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
                    $check_stmt->bind_param("si", $email, $user_id);
                    $check_stmt->execute();
                    $result = $check_stmt->get_result();
                    if ($result->num_rows == 0) {
                        $stmt = $conn->prepare("UPDATE users SET fullname = ?, email = ?, phone = ?, address = ?, role = ? WHERE user_id = ?");
                        $stmt->bind_param("sssssi", $fullname, $email, $phone, $address, $role, $user_id);
                        if ($stmt->execute()) {
                            $message = 'User updated successfully.';
                        } else {
                            $error = 'Failed to update user.';
                        }
                    } else {
                        $error = 'Email already exists for another user.';
                    }
                } else {
                    $error = 'Full name and email are required.';
                }
                break;
            case 'reset_password':
                $user_id = $_POST['user_id'];
                $new_password = $_POST['new_password'];
                if (!empty($new_password)) {
                    $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?");
                    $stmt->bind_param("si", $password_hash, $user_id);
                    if ($stmt->execute()) {
                        $message = 'Password reset successfully.';
                    } else {
                        $error = 'Failed to reset password.';
                    }
                } else {
                    $error = 'New password is required.';
                }
                break;
            case 'delete_user':
                $user_id = $_POST['user_id'];
                if ($user_id != $_SESSION['user']['user_id']) {
                    $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
                    $stmt->bind_param("i", $user_id);
                    if ($stmt->execute()) {
                        $message = 'User deleted successfully.';
                    } else {
                        $error = 'Failed to delete user.';
                    }
                } else {
                    $error = 'Cannot delete your own account.';
                }
                break;
        }
    }
}
$users_result = $conn->query("SELECT user_id, fullname, email, phone, address, role, created_at FROM users ORDER BY created_at DESC");
$users = $users_result->fetch_all(MYSQLI_ASSOC);
include '../../includes/header_ad.php';
?>
<div class="container-fluid px-2" style="margin-top: 110px;">
    <!-- Messages -->
    <?php if ($message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <!-- Add/Edit User Card -->
    <div class="card mb-4">
        <div class="card-header bg-primary"><strong>Add / Edit User</strong></div>
        <div class="card-body">
            <form method="POST" id="userForm">
                <input type="hidden" name="action" id="user_action" value="create_user">
                <input type="hidden" name="user_id" id="edit_user_id">
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Full Name *</label>
                        <input type="text" class="form-control" name="fullname" id="fullname" placeholder="Enter full name" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Email *</label>
                        <input type="email" class="form-control" name="email" id="email" placeholder="Enter email address" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Phone</label>
                        <input type="text" class="form-control" name="phone" id="phone" placeholder="Enter phone number">
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4" id="password_field">
                        <label class="form-label">Password *</label>
                        <input type="password" class="form-control" name="password" id="password" placeholder="Enter password" minlength="6" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Role</label>
                        <select class="form-select" name="role" id="role" required>
                            <option value="customer">Customer</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Address</label>
                        <textarea class="form-control" name="address" id="address" rows="1" placeholder="Enter address"></textarea>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Save User</button>
                <button type="button" onclick="resetForm()" class="btn btn-secondary">Reset</button>
            </form>
        </div>
    </div>
    <!-- Users List Card -->
    <div class="card">
        <div class="card-header"><strong>User List</strong></div>
        <div class="card-body table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th width="5%">ID</th>
                        <th width="12%">Full Name</th>
                        <th width="20%">Email</th>
                        <th width="15%">Phone</th>
                        <th width="10%">Role</th>
                        <th width="15%">Created At</th>
                        <th width="15%">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td class="text-center"><?= $user['user_id'] ?></td>
                            <td><?= htmlspecialchars($user['fullname']) ?></td>
                            <td><?= htmlspecialchars($user['email']) ?></td>
                            <td><?= htmlspecialchars($user['phone']) ?></td>
                            <td class="text-center">
                                <span class="badge <?= $user['role'] === 'admin' ? 'bg-danger' : 'bg-primary' ?>">
                                    <?= ucfirst($user['role']) ?>
                                </span>
                            </td>
                            <td class="text-center"><?= date('M d, Y', strtotime($user['created_at'])) ?></td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-warning" onclick='editUser(<?= json_encode($user) ?>)' title="Edit User">
                                    Edit
                                </button>
                                <button class="btn btn-sm btn-outline-warning" onclick="resetPassword(<?= $user['user_id'] ?>, '<?= htmlspecialchars($user['fullname']) ?>')" title="Reset Password">
                                    Reset
                                </button>
                                <?php if ($user['user_id'] != $_SESSION['user']['user_id']): ?>
                                    <form method="POST" class="d-inline" onsubmit="return deleteUserWithAlert('<?= htmlspecialchars($user['fullname']) ?>');">
                                        <input type="hidden" name="action" value="delete_user">
                                        <input type="hidden" name="user_id" value="<?= $user['user_id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-danger" title="Delete User">
                                            Delete
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<!-- Reset Password Modal -->
<div class="modal fade" id="resetPasswordModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Reset Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="reset_password">
                    <input type="hidden" name="user_id" id="reset_user_id">
                    <p>Reset password for: <strong id="reset_user_name"></strong></p>
                    <div class="mb-3">
                        <label for="new_password" class="form-label">New Password</label>
                        <input type="password" class="form-control" name="new_password" required minlength="6">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">Reset Password</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Bootstrap JavaScript -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- Custom JavaScript -->
<script src="../../assets/js/ad_account.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(function(alert) {
            setTimeout(function() {
                if (alert && alert.classList.contains('show')) {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }
            }, 1000);
        });
    });
</script>
</div>
<?php include '../../includes/footer.php'; ?>