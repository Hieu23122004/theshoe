<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include '../../includes/auth.php';
include '../../includes/database.php';
include '../../includes/header_ad.php';

function showAlert($msg, $type = 'success')
{
    return "<div class='alert alert-{$type} alert-dismissible fade show' role='alert'>
                {$msg}
                <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
            </div>";
}

$hasError = false;
$message = '';

try {
    if (!$conn || $conn->connect_errno) {
        throw new Exception('Cannot connect to database: ' . $conn->connect_error);
    }
} catch (Exception $e) {
    $hasError = true;
    $message = "<div class='alert alert-danger'>{$e->getMessage()}</div>";
}


// Xử lý thông báo từ session
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}

// Xử lý thông báo từ GET nếu không có session message
if (empty($message) && $_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['msg'])) {
    if ($_GET['msg'] === 'success') $message = showAlert('Category saved successfully!');
    if ($_GET['msg'] === 'deleted') $message = showAlert('Category deleted successfully!');
}

if (isset($_POST['submit'])) {
    $name = trim($_POST['name']);
    $parent_id = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
    $edit_id = isset($_POST['category_id']) && $_POST['category_id'] !== '' ? (int)$_POST['category_id'] : null;

    try {
        $conn->begin_transaction();

        if (empty($name)) throw new Exception('Category name cannot be empty');

        $stmt = $conn->prepare("SELECT category_id FROM categories WHERE name = ? AND (category_id != ? OR ? IS NULL)");
        $stmt->bind_param("sii", $name, $edit_id, $edit_id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            throw new Exception('Category name already exists!');
        }

        if ($edit_id && $parent_id == $edit_id) {
            throw new Exception('A category cannot be its own parent!');
        }

        if ($parent_id !== null) {
            $stmt = $conn->prepare("SELECT category_id FROM categories WHERE category_id = ? AND parent_id IS NULL");
            $stmt->bind_param("i", $parent_id);
            $stmt->execute();
            if ($stmt->get_result()->num_rows === 0) {
                throw new Exception('Danh mục cha không hợp lệ!');
            }
        }

        if ($edit_id) {
            $stmt = $conn->prepare("UPDATE categories SET name = ?, parent_id = ? WHERE category_id = ?");
            $stmt->bind_param("sii", $name, $parent_id, $edit_id);
        } else {
            if ($parent_id === null) {
                $stmt = $conn->prepare("INSERT INTO categories (name, parent_id) VALUES (?, NULL)");
                $stmt->bind_param("s", $name);
            } else {
                $stmt = $conn->prepare("INSERT INTO categories (name, parent_id) VALUES (?, ?)");
                $stmt->bind_param("si", $name, $parent_id);
            }
        }

        if (!$stmt->execute()) {
            throw new Exception('Không thể lưu danh mục!');
        }

        $conn->commit();
        header('Location: ' . $_SERVER['PHP_SELF'] . '?msg=success');
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        $message = showAlert($e->getMessage(), 'danger');
    }
}

if (isset($_POST['delete'])) {
    $id = (int)$_POST['delete'];
    $force_delete = isset($_POST['force_delete']);

    try {
        $conn->begin_transaction();

        $stmt = $conn->prepare("SELECT category_id FROM categories WHERE category_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows === 0) {
            throw new Exception('Category does not exist');
        }

        if ($force_delete) {
            $stmt = $conn->prepare("DELETE FROM categories WHERE parent_id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
        } else {
            $stmt = $conn->prepare("SELECT COUNT(*) FROM categories WHERE parent_id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $count = $stmt->get_result()->fetch_row()[0];
            if ($count > 0) {
                throw new Exception('Cannot delete parent category with subcategories. Use force delete!');
            }
        }

        $stmt = $conn->prepare("DELETE FROM categories WHERE category_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();

        $conn->commit();
        header('Location: ' . $_SERVER['PHP_SELF'] . '?msg=deleted');
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        $message = showAlert($e->getMessage(), 'danger');
    }
}

$result = $conn->query("SELECT c.category_id, c.name, c.parent_id, p.name AS parent_name FROM categories c LEFT JOIN categories p ON c.parent_id = p.category_id ORDER BY c.category_id ASC");
$categories = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

$result2 = $conn->query("SELECT category_id, name FROM categories WHERE parent_id IS NULL ORDER BY name ASC");
$parents = $result2 ? $result2->fetch_all(MYSQLI_ASSOC) : [];
?>
<?php if (!defined('INCLUDED_HEADER')) {
} ?>
<div class="container-fluid px-2" style="margin-top: 110px;">
    <?= $message ?>
    <div class="card mb-4">
        <div class="card-header bg-primary"><strong>Add / Edit Category</strong></div>
        <div class="card-body">
            <form method="POST" id="categoryForm">
                <input type="hidden" name="category_id" id="edit_id">
                <div class="row mb-3">
                    <div class="col-md-3">
                        <label>Category Name</label>
                        <input type="text" name="name" id="name" placeholder="Tên loại sản phẩm mới" class="form-control" required>
                    </div>
                    <div class="col-md-3">
                        <label>Parent Category</label>
                        <select name="parent_id" id="parent_id" class="form-select">
                            <option value="">Create as parent category</option>
                            <?php foreach ($parents as $p) { ?>
                                <option value="<?= $p['category_id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="col-md-3" style="margin-top: 24px;">
                        <button type="submit" name="submit" class="btn btn-primary">Save</button>
                        <button type="button" onclick="resetForm()" class="btn btn-secondary">Reset</button>
                    </div>
            </form>
        </div>
    </div>
    <div class="card">
        <div class="card-header"><strong>Category List</strong></div>
        <div class="card-body table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Parent</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $cat): ?>
                        <tr <?= $cat['parent_id'] ? 'class="table-light"' : '' ?>>
                            <td><?= $cat['category_id'] ?></td>
                            <td><?= $cat['parent_id'] ? '└─ ' : '' ?><?= htmlspecialchars($cat['name']) ?></td>
                            <td><?= $cat['parent_id'] ? htmlspecialchars($cat['parent_name']) : '<span class="badge bg-primary">Parent Category</span>' ?></td>
                            <td>
                                <button class="btn btn-sm btn-warning" onclick='editCategory(<?= json_encode($cat) ?>)'>Edit</button>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="delete" value="<?= $cat['category_id'] ?>">
                                    <?php if (!$cat['parent_id']): ?>
                                        <input type="hidden" name="force_delete" value="1">
                                    <?php endif; ?>
                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete?')">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<script src="../assets/js/ad_categories.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<?php include '../../includes/footer.php'; ?>