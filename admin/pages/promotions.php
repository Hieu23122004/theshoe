<?php
include '../../includes/auth.php';
include '../../includes/database.php';
$message = '';
$edit_data = null;
// Xử lý lưu dữ liệu
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $slug = trim($_POST['slug']);
    $excerpt = trim($_POST['excerpt']);
    $content = $_POST['content'];
    $image_url = trim($_POST['image_url']);
    $is_published = isset($_POST['is_published']) ? 1 : 0;
    $edit_id = isset($_POST['post_id']) ? (int)$_POST['post_id'] : null;
    try {
        if (empty($title) || empty($slug) || empty($content)) {
            throw new Exception('Please fill in all required fields.');
        }
        // Kiểm tra trùng lặp slug
        $check_slug_sql = "SELECT post_id FROM promotions WHERE slug = ? AND post_id != ?";
        $check_stmt = $conn->prepare($check_slug_sql);
        $check_id = $edit_id ? $edit_id : 0;
        $check_stmt->bind_param("si", $slug, $check_id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        if ($result->num_rows > 0) {
            throw new Exception('Slug already exists. Please choose a different slug.');
        }
        $conn->begin_transaction();
        if ($edit_id) {
            $stmt = $conn->prepare("UPDATE promotions SET title=?, slug=?, excerpt=?, content=?, image_url=?, is_published=?, updated_at=NOW() WHERE post_id=?");
            $stmt->bind_param("ssssssi", $title, $slug, $excerpt, $content, $image_url, $is_published, $edit_id);
        } else {
            $stmt = $conn->prepare("INSERT INTO promotions (title, slug, excerpt, content, image_url, is_published) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssi", $title, $slug, $excerpt, $content, $image_url, $is_published);
        }
        $stmt->execute();
        $conn->commit();
        // Chuyển hướng để tránh resubmit
        $redirect_url = $edit_id ? "?edit=$edit_id&success=1" : "?success=1";
        header("Location: $redirect_url");
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        $message = "<div class='alert alert-danger alert-dismissible fade show' role='alert'>
            {$e->getMessage()}
            <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
        </div>";
    }
}
// Hiển thị thông báo thành công từ URL
if (isset($_GET['success'])) {
    $message = "<div class='alert alert-success alert-dismissible fade show' role='alert'>
        Article saved successfully.
        <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
    </div>";
}
// Xử lý xóa bài viết
if (isset($_GET['delete'])) {
    $delete_id = (int)$_GET['delete'];
    try {
        $stmt = $conn->prepare("DELETE FROM promotions WHERE post_id = ?");
        $stmt->bind_param("i", $delete_id);
        $stmt->execute();
        if ($stmt->affected_rows > 0) {
            header("Location: ?deleted=1");
        } else {
            $message = "<div class='alert alert-warning alert-dismissible fade show' role='alert'>
                Article not found.
                <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
            </div>";
        }
        exit();
    } catch (Exception $e) {
        $message = "<div class='alert alert-danger alert-dismissible fade show' role='alert'>
            Error deleting article: {$e->getMessage()}
            <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
        </div>";
    }
}
// Hiển thị thông báo xóa thành công
if (isset($_GET['deleted'])) {
    $message = "<div class='alert alert-success alert-dismissible fade show' role='alert'>
        Article deleted successfully.
        <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
    </div>";
}
// Nếu có ID sửa
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $res = $conn->query("SELECT * FROM promotions WHERE post_id = $id");
    $edit_data = $res->fetch_assoc();
}
$res = $conn->query("SELECT * FROM promotions ORDER BY created_at DESC");
$promotions = $res->fetch_all(MYSQLI_ASSOC);
include '../../includes/header_ad.php';
?>
<link rel="stylesheet" href="../../assets/css/new_promotions.css">
<div class="container-fluid px-2" style="margin-top:110px;">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var msg = <?= json_encode(strip_tags($message)) ?>;
        if (msg) {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: msg.includes('success') ? 'success' : (msg.includes('error') || msg.includes('danger') ? 'error' : 'info'),
                title: msg,
                showConfirmButton: false,
                timer: 2000,
                background: '#fff',
                color: '#8c7e71',
                customClass: {popup: 'swal2-toast-custom'}
            });
        }
    });
    </script>
    <style>
    .swal2-toast-custom {
        border-radius: 0.75rem !important;
        box-shadow: 0 0.2rem 1.5rem 0 rgba(140,126,113,0.12) !important;
        font-size: 1.1rem;
        width: 600px !important;
        height: 80px !important;
        padding: 1.5rem 2rem !important;
        text-align: left !important;
        display: flex !important;
        align-items: center !important;
        white-space: nowrap !important;
        overflow: hidden !important;
        text-overflow: ellipsis !important;
    }
    </style>
    <div class="card mb-4">
        <div class="card-header" style="background-color: #8c7e71;"><strong><?= $edit_data ? 'Edit Article' : 'Add New Article' ?></strong></div>
        <div class="card-body">
            <form method="POST">
                <?php if ($edit_data): ?>
                    <input type="hidden" name="post_id" value="<?= $edit_data['post_id'] ?>">
                <?php endif; ?>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Title</label>
                        <input type="text" class="form-control" name="title" id="title" required value="<?= htmlspecialchars($edit_data['title'] ?? '') ?>" placeholder="Nhập tiêu đề bài viết">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Slug</label>
                        <input type="text" class="form-control" name="slug" id="slug" required value="<?= htmlspecialchars($edit_data['slug'] ?? '') ?>" placeholder="Tự động tạo hoặc nhập slug tùy chỉnh">
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-9">
                        <label class="form-label">Excerpt</label>
                        <textarea class="form-control" name="excerpt" rows="3" placeholder="Tóm tắt ngắn cho bài viết (không bắt buộc)"><?= htmlspecialchars($edit_data['excerpt'] ?? '') ?></textarea>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Featured Image URL</label>
                        <input type="text" class="form-control" name="image_url" id="image_url" value="<?= htmlspecialchars($edit_data['image_url'] ?? '') ?>" onchange="validateAndPreviewImage(this)" placeholder="Dán link ảnh đại diện">
                        <div id="imagePreview" class="mt-3"></div>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Content</label>
                    <textarea id="editor" class="form-control" name="content" rows="8" placeholder="Nhập nội dung bài viết tại đây dạng HTML"><?= htmlspecialchars($edit_data['content'] ?? '') ?></textarea>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="row">
                            <div class="col-12 mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="is_published" id="is_published" <?= isset($edit_data['is_published']) && $edit_data['is_published'] ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="is_published">
                                        <strong>Publish Article</strong>
                                    </label>
                                    <small class="d-block text-muted">Check to make this article visible to visitors</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <button class="btn" style="background-color: #8c7e71;" type="submit">Save</button>
                <button type="reset" class="btn btn-secondary">Reset</button>
                <?php if ($edit_data): ?>
                    <a href="?" class="btn btn-outline-secondary">Cancel</a>
                <?php endif; ?>
            </form>
        </div>
    </div>
    <div class="card">
        <div class="card-header"><strong>Article List</strong></div>
        <div class="card-body table-responsive">
            <table class="table table-bordered align-middle">
                <thead class="table-light">
                    <tr>
                        <th style="width: 5%;">ID</th>
                        <th style="width: 25%;">Title</th>
                        <th style="width: 20%;">Slug</th>
                        <th style="width: 5%;">Status</th>
                        <th style="width: 10%;">Created Date</th>
                        <th style="width: 10%;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($promotions as $post): ?>
                        <tr>
                            <td><?= $post['post_id'] ?></td>
                            <td><?= htmlspecialchars($post['title']) ?></td>
                            <td><?= htmlspecialchars($post['slug']) ?></td>
                            <td><?= $post['is_published'] ? '<span class="badge bg-success">Published</span>' : '<span class="badge bg-secondary">Draft</span>' ?></td>
                            <td><?= date('M d, Y H:i', strtotime($post['created_at'])) ?></td>
                            <td>
                                <a href="?edit=<?= $post['post_id'] ?>" class="btn btn-sm btn-warning">Edit</a>
                                <a href="?delete=<?= $post['post_id'] ?>" class="btn btn-sm btn-danger">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach ?>
                    <?php if (count($promotions) === 0): ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted">No articles found</td>
                        </tr>
                    <?php endif ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<!-- TinyMCE & Slugify -->
<script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
<script>
    tinymce.init({
        selector: '#editor',
        height: 400,
        plugins: 'link image code lists',
        toolbar: 'undo redo | bold italic underline | alignleft aligncenter alignright | bullist numlist | link image | code',
        menubar: false
    });
    // Tự động tạo slug từ title
    document.getElementById("title").addEventListener("input", function() {
        // Chỉ tự động tạo slug nếu đang thêm mới (không có edit_data)
        var isEditing = <?= $edit_data ? 'true' : 'false' ?>;
        if (!isEditing) {
            const text = this.value.toLowerCase()
                .normalize("NFD").replace(/[\u0300-\u036f]/g, "") // bỏ dấu
                .replace(/[^a-z0-9\s-]/g, "") // bỏ ký tự đặc biệt
                .replace(/\s+/g, '-') // thay space bằng -
                .replace(/-+/g, "-") // bỏ dấu - liên tiếp
                .replace(/^-+|-+$/g, ""); // bỏ dấu - ở đầu/cuối
            document.getElementById("slug").value = text;
        }
    });
    // Đã bỏ xác nhận trước khi xóa, xóa phát là xóa luôn
    // Xử lý và validate URL ảnh
    function validateAndPreviewImage(input) {
        const previewContainer = document.getElementById('imagePreview');
        const url = input.value.trim();
        // Clear previous preview
        previewContainer.innerHTML = '';
        if (!url) {
            return;
        }
        // Show loading state
        previewContainer.innerHTML = '<div class="loading-placeholder">Loading image preview...</div>';
        // Create image element to test URL
        const img = new Image();
        img.onload = function() {
            // Image loaded successfully
            previewContainer.innerHTML = `
            <div class="image-preview-container">
                <img src="${url}" alt="Featured image preview">
                <button type="button" class="image-remove-btn" onclick="clearImagePreview()" title="Remove image">×</button>
            </div>
        `;
        };
        img.onerror = function() {
            // Image failed to load
            previewContainer.innerHTML = `
            <div class="error-placeholder">
                <small>✗ Failed to load image. Please check the URL.</small>
            </div>
        `;
        };
        // Set source to trigger load/error events
        img.src = url;
    }
    // Xóa preview ảnh
    function clearImagePreview() {
        document.getElementById('image_url').value = '';
        document.getElementById('imagePreview').innerHTML = '';
    }
    // Tự động ẩn thông báo sau 3 giây
    document.addEventListener('DOMContentLoaded', function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(function(alert) {
            setTimeout(function() {
                if (alert && alert.classList.contains('show')) {
                    // Sử dụng Bootstrap's fade out effect
                    alert.classList.remove('show');
                    alert.classList.add('fade');
                    setTimeout(function() {
                        if (alert.parentNode) {
                            alert.parentNode.removeChild(alert);
                        }
                    }, 150); // Thời gian transition của Bootstrap
                }
            }, 1000); // 3 giây
        });
        // Load existing image preview if editing
        const imageUrlInput = document.getElementById('image_url');
        if (imageUrlInput.value) {
            validateAndPreviewImage(imageUrlInput);
        }
    });
</script>
<?php include '../../includes/footer.php'; ?>