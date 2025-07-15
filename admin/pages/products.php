<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include auth first (which handles session_start())
include '../../includes/auth.php';
include '../../includes/database.php';

// Function to ensure proper JSON encoding of colors
function getColorOptionsJson() {
    return json_encode(['Black', 'Brown'], JSON_UNESCAPED_UNICODE);
}

function showAlert($msg, $type = 'success')
{
    return "<div class='alert alert-{$type} alert-dismissible fade show' role='alert'>
                {$msg}
                <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
            </div>";
}

include '../../includes/header_ad.php';

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

// Handle session messages
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}

// Handle GET messages
if (empty($message) && $_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['msg'])) {
    if ($_GET['msg'] === 'success') $message = showAlert('Product saved successfully!');
    if ($_GET['msg'] === 'deleted') $message = showAlert('Product deleted successfully!');
}

// Get all categories with hierarchy information
$categories_result = $conn->query("
    SELECT 
        c1.category_id,
        c1.name,
        c1.parent_id,
        c2.name as parent_name
    FROM categories c1 
    LEFT JOIN categories c2 ON c1.parent_id = c2.category_id 
    ORDER BY c1.parent_id ASC, c1.name ASC
");
$all_categories = $categories_result ? $categories_result->fetch_all(MYSQLI_ASSOC) : [];

// Separate parent and child categories
$parent_categories = [];
$child_categories = [];
foreach ($all_categories as $cat) {
    if ($cat['parent_id'] === NULL) {
        $parent_categories[] = $cat;
    } else {
        $child_categories[$cat['parent_id']][] = $cat;
    }
}

function shortenUrl($url)
{
    // Nếu URL chứa Google Drive
    if (strpos($url, 'drive.google.com') !== false) {
        if (preg_match('/\/d\/(.*?)\//', $url, $matches)) {
            return 'https://drive.google.com/uc?id=' . $matches[1];
        }
    }
    // Nếu URL chứa Dropbox
    if (strpos($url, 'dropbox.com') !== false) {
        return str_replace('?dl=0', '?raw=1', $url);
    }
    return $url;
}

// Handle form submission
if (isset($_POST['submit'])) {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = floatval($_POST['price']);
    $original_price = floatval($_POST['original_price']);
    $discount_percent = intval($_POST['discount_percent']);
    $stock_quantity = intval($_POST['stock_quantity']);
    $material = trim($_POST['material']);
    $image_url = shortenUrl(trim($_POST['image_url']));
    $category_id = intval($_POST['category_id']);
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;

    // Process color options from form input - Only allow Black and Brown
    $color_options = json_encode(['Black', 'Brown'], JSON_UNESCAPED_UNICODE);
    if (!empty($_POST['color_options'])) {
        error_log("DEBUG: color_options received: " . $_POST['color_options']);
        try {
            $colors = json_decode($_POST['color_options'], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($colors)) {
                // Validate that only Black and Brown are allowed
                $allowed_colors = ['Black', 'Brown'];
                $valid_colors = array_intersect($colors, $allowed_colors);
                
                // Only accept if exactly these 2 colors are present
                if (count($valid_colors) === 2 && 
                    in_array('Black', $valid_colors) && 
                    in_array('Brown', $valid_colors)) {
                    $color_options = json_encode(['Black', 'Brown'], JSON_UNESCAPED_UNICODE);
                    error_log("DEBUG: color_options validated and set to default");
                } else {
                    error_log("DEBUG: Invalid colors detected, using default");
                    $color_options = json_encode(['Black', 'Brown'], JSON_UNESCAPED_UNICODE);
                }
            } else {
                error_log("DEBUG: JSON decode failed, using default");
                $color_options = json_encode(['Black', 'Brown'], JSON_UNESCAPED_UNICODE);
            }
        } catch (Exception $e) {
            // Keep default colors if parsing fails
            error_log("Error processing color_options: " . $e->getMessage());
            $color_options = json_encode(['Black', 'Brown'], JSON_UNESCAPED_UNICODE);
        }
    } else {
        error_log("DEBUG: color_options POST field is empty, using default");
    }
    
    error_log("DEBUG: Final color_options before database: " . $color_options);

    // Process size_stock - Only allow Black and Brown colors
    $size_stock_json = '{}';
    if (!empty($_POST['size_stock'])) {
        try {
            $sizeData = json_decode($_POST['size_stock'], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($sizeData)) {
                // Only process for Black and Brown colors - no custom colors allowed
                $colors_for_stock = ['Black', 'Brown'];
                
                $validSizeStock = [];
                
                // Process only Black and Brown colors
                foreach ($colors_for_stock as $color) {
                    $validSizeStock[$color] = [];
                    if (isset($sizeData[$color]) && is_array($sizeData[$color])) {
                        foreach ($sizeData[$color] as $size => $quantity) {
                            $validSizeStock[$color][$size] = (int)$quantity;
                        }
                    }
                }
                
                $size_stock_json = json_encode($validSizeStock, JSON_UNESCAPED_UNICODE);
            }
        } catch (Exception $e) {
            error_log("Error processing size_stock: " . $e->getMessage());
            $size_stock_json = json_encode(['Black' => [], 'Brown' => []], JSON_UNESCAPED_UNICODE);
        }
    }

    // Xử lý image_urls
    $image_urls = null;
    if (!empty($_POST['image_urls'])) {
        error_log("DEBUG: image_urls received: " . $_POST['image_urls']);
        try {
            $decoded = json_decode($_POST['image_urls'], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $decoded = array_map('shortenUrl', array_filter($decoded));
                $image_urls = !empty($decoded) ? json_encode($decoded) : null;
                error_log("DEBUG: image_urls processed: " . ($image_urls ?: 'NULL'));
            } else {
                error_log("DEBUG: JSON decode failed or not array");
            }
        } catch (Exception $e) {
            error_log("DEBUG: Exception processing image_urls: " . $e->getMessage());
            $image_urls = null;
        }
    } else {
        error_log("DEBUG: image_urls POST field is empty or not set");
    }

    $edit_id = isset($_POST['product_id']) && $_POST['product_id'] !== '' ? (int)$_POST['product_id'] : null;

    try {
        $conn->begin_transaction();

        if (empty($name)) throw new Exception('Product name cannot be empty');
        if ($price <= 0) throw new Exception('Price must be greater than 0');
        if ($original_price <= 0) throw new Exception('Original price must be greater than 0');
        if ($stock_quantity < 0) throw new Exception('Stock quantity cannot be negative');

        // Check for duplicate product name
        $stmt = $conn->prepare("SELECT product_id FROM products WHERE name = ? AND (product_id != ? OR ? IS NULL)");
        $stmt->bind_param("sii", $name, $edit_id, $edit_id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            throw new Exception('Product name already exists!');
        }

        if ($edit_id) {
           $stmt = $conn->prepare("UPDATE products SET 
    name = ?, description = ?, price = ?, original_price = ?, 
    discount_percent = ?, stock_quantity = ?, color_options = CAST(? AS JSON),
    size_stock = CAST(? AS JSON), material = ?, image_url = ?, image_urls = CAST(? AS JSON),
    category_id = ?, is_featured = ? 
    WHERE product_id = ?");

            $stmt->bind_param(
                "ssddiisssssiii",
                $name,
                $description,
                $price,
                $original_price,
                $discount_percent,
                $stock_quantity,
                $color_options,
                $size_stock_json,
                $material,
                $image_url,
                $image_urls,
                $category_id,
                $is_featured,
                $edit_id
            );
        } else {
           $stmt = $conn->prepare("INSERT INTO products (
    name, description, price, original_price, discount_percent,
    stock_quantity, color_options, size_stock, sold_quantity, material, 
    image_url, image_urls, category_id, is_featured
) VALUES (?, ?, ?, ?, ?, ?, CAST(? AS JSON), CAST(? AS JSON), 0, ?, ?, CAST(? AS JSON), ?, ?)");

            $stmt->bind_param(
                "ssddiisssssii",
                $name,
                $description,
                $price,
                $original_price,
                $discount_percent,
                $stock_quantity,
                $color_options,
                $size_stock_json,
                $material,
                $image_url,
                $image_urls,
                $category_id,
                $is_featured
            );
        }

        if (!$stmt->execute()) {
            throw new Exception('Cannot save product: ' . $stmt->error);
        }

        $conn->commit();
        header('Location: ' . $_SERVER['PHP_SELF'] . '?msg=success');
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        $message = showAlert($e->getMessage(), 'danger');
    }
}

// Handle delete
if (isset($_POST['delete'])) {
    $id = (int)$_POST['delete'];

    try {
        $conn->begin_transaction();

        $stmt = $conn->prepare("SELECT product_id FROM products WHERE product_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows === 0) {
            throw new Exception('Product does not exist');
        }

        $stmt = $conn->prepare("DELETE FROM products WHERE product_id = ?");
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

// Fetch all products with category names (including parent category info)
$result = $conn->query("
    SELECT 
        p.*, 
        c1.name as category_name,
        c2.name as parent_category_name
    FROM products p 
    LEFT JOIN categories c1 ON p.category_id = c1.category_id 
    LEFT JOIN categories c2 ON c1.parent_id = c2.category_id
    ORDER BY p.product_id DESC
");
$products = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <title>Product Management</title>
    <style>
        .product-image {
            width: 140px;
            height: 120px;
            object-fit: cover;
        }

        .url-preview {
            display: inline-block;
            margin: 5px;
            position: relative;
        }

        .url-preview img {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .url-preview .remove-url {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #dc3545;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            text-align: center;
            line-height: 20px;
            cursor: pointer;
            font-size: 12px;
        }

        .preview-container {
            margin-top: 10px;
        }
    </style>
</head>

<body>
    <div class="container-fluid px-2" style="margin-top: 110px;">
        <?= $message ?>
        <div class="card mb-4">
            <div class="card-header bg-primary" ><strong>Add / Edit Product</strong></div>
            <div class="card-body">
                <form method="POST" id="productForm">
                    <input type="hidden" name="product_id" id="edit_id">

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label>Product Name</label>
                            <input type="text" name="name" id="name" class="form-control" placeholder="Tên sản phẩm" required>
                        </div>
                        <div class="col-md-2">
                            <label>Original Price</label>
                            <input type="number" name="original_price" id="original_price" class="form-control" placeholder="Giá gốc > 500.000" min="500000" step="1000" required>
                        </div>
                        <div class="col-md-2">
                            <label>Discount %</label>
                            <input type="number" name="discount_percent" id="discount_percent" class="form-control" min="0" max="100" placeholder="0-100" onchange="calculatePrice()">
                        </div>
                        <div class="col-md-2">
                            <label>Final Price</label>
                            <input type="number" name="price" id="price" placeholder="Giá cuối tự tính" class="form-control" readonly>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label>Description</label>
                            <textarea name="description" id="description" class="form-control" placeholder="Mô tả sản phẩm" rows="3"></textarea>
                        </div>
                        <div class="col-md-2">
                            <label>Color Options</label>
                            <input type="text" name="color_options_display" id="color_options" class="form-control" value='["Black", "Brown"]' placeholder='["Black", "Brown"]' required>
                            <small class="text-muted">Fixed:["Black", "Brown"]</small>
                        </div>
                        <div class="col-md-2">
                            <label>Parent Category</label>
                            <select id="parent_category" class="form-select" onchange="loadChildCategories()">
                                <option value="">Parent category</option>
                                <?php foreach ($parent_categories as $cat): ?>
                                    <option value="<?= $cat['category_id'] ?>">
                                        <?= htmlspecialchars($cat['name'] ?? '') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label>Sub Category <span class="text-danger">*</span></label>
                            <select name="category_id" id="category_id" class="form-select" required disabled>
                                <option value="">Sub category</option>
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label>Material</label>
                            <input type="text" name="material" placeholder="Chất liệu gì" id="material" class="form-control">
                        </div>
                       
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-10">
                            <label>Colors and Sizes</label>
                            <div class="border rounded p-3">
                                <div class="mb-2">
                                    <small class="text-muted">Sizes will be generated based on Color Options above</small>
                                </div>
                                <div id="colorSizeContainer">
                                    <!-- Color sections will be generated dynamically -->
                                </div>
                            </div>
                        </div>
                         <div class="col-md-2">
                            <label>Total Quantity</label>
                            <input type="number" name="stock_quantity" id="stock_quantity" min="0" max="1000" placeholder="0-1000" class="form-control" readonly>
                        </div>
                    </div>

                    <!-- Templates for size inputs based on category -->
                    <template id="shoeSizesTemplate">
                        <div class="row">
                            <div class="col">
                                <label>Size 38</label>
                                <input type="number" class="form-control size-input" data-size="38" min="0" max="1000" value="0" oninput="updateSizeStock(this)">
                            </div>
                            <div class="col">
                                <label>Size 39</label>
                                <input type="number" class="form-control size-input" data-size="39" min="0" max="1000" value="0" oninput="updateSizeStock(this)">
                            </div>
                            <div class="col">
                                <label>Size 40</label>
                                <input type="number" class="form-control size-input" data-size="40" min="0" max="1000" value="0" oninput="updateSizeStock(this)">
                            </div>
                            <div class="col">
                                <label>Size 41</label>
                                <input type="number" class="form-control size-input" data-size="41" min="0" max="1000" value="0" oninput="updateSizeStock(this)">
                            </div>
                            <div class="col">
                                <label>Size 42</label>
                                <input type="number" class="form-control size-input" data-size="42" min="0" max="1000" value="0" oninput="updateSizeStock(this)">
                            </div>
                        </div>
                    </template>

                    <template id="bagSizesTemplate">
                        <div class="row">
                            <div class="col">
                                <label>Size 25</label>
                                <input type="number" class="form-control size-input" max="1000" data-size="25" min="0" value="0" oninput="updateSizeStock(this)">
                            </div>
                            <div class="col">
                                <label>Size 27</label>
                                <input type="number" class="form-control size-input" max="1000" data-size="27" min="0" value="0" oninput="updateSizeStock(this)">
                            </div>
                            <div class="col">
                                <label>Size 29</label>
                                <input type="number" class="form-control size-input" max="1000" data-size="29" min="0" value="0" oninput="updateSizeStock(this)">
                            </div>
                        </div>
                    </template>

                    <template id="beltSizesTemplate">
                        <div class="row">
                            <div class="col">
                                <label>Size M</label>
                                <input type="number" class="form-control size-input" max="1000" data-size="M" min="0" value="0" oninput="updateSizeStock(this)">
                            </div>
                            <div class="col">
                                <label>Size L</label>
                                <input type="number" class="form-control size-input" max="1000" data-size="L" min="0" value="0" oninput="updateSizeStock(this)">
                            </div>
                            <div class="col">
                                <label>Size XL</label>
                                <input type="number" class="form-control size-input" max="1000" data-size="XL" min="0" value="0" oninput="updateSizeStock(this)">
                            </div>
                        </div>
                    </template>

                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label>Main Image URL</label>
                            <input type="url" name="image_url" id="image_url" class="form-control" onchange="validateAndPreviewMainImage(this)">
                            <div id="mainImagePreview" class="preview-container"></div>
                        </div>
                        <div class="col-md-7">
                            <label>Additional Image URLs (Max 3)</label>
                            <input type="url" class="form-control" id="additional_url" onchange="handleAdditionalImage(this)" placeholder="Paste image URL here">
                            <div id="additionalImagesPreview" class="preview-container"></div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-check" style="margin-top: 30px;">
                                <label class="form-check-label">Featured Product</label>
                                <input type="checkbox" name="is_featured" id="is_featured" class="form-check-input" value="1">
                            </div>
                        </div>
                    </div>

                    <!-- Hidden inputs for form data -->
                    <input type="hidden" name="size_stock" id="size_stock_data">
                    <input type="hidden" name="image_urls" id="image_urls">

                    <button type="submit" name="submit" class="btn btn-primary">Save Product</button>
                    <button type="button" onclick="resetForm()" class="btn btn-secondary">Reset</button>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><strong>Product List</strong></div>
            <div class="card-body table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th width="5%">ID</th>
                            <th width="7%">Image</th>
                            <th width="18%">Product Name</th>
                            <th width="12%">Category</th>
                            <th width="10%">Material</th>
                            <th width="8%">Quantity</th>
                            <th width="10%">Original Price</th>
                            <th width="10%">Discount</th>
                            <th width="8%">Featured</th>
                            <th width="12%">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td class="text-center"><?= $product['product_id'] ?></td>
                                <td class="text-center">
                                    <?php if ($product['image_url']): ?>
                                        <img src="<?= htmlspecialchars($product['image_url']) ?>" class="product-image" alt="<?= htmlspecialchars($product['name']) ?>">
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($product['name']) ?></td>
                                <td>
                                    <?php if ($product['parent_category_name']): ?>
                                        <small class="text-muted"><?= htmlspecialchars($product['parent_category_name']) ?></small><br>
                                    <?php endif; ?>
                                    <strong><?= htmlspecialchars($product['category_name'] ?? 'No Category') ?></strong>
                                </td>
                                <td><?= htmlspecialchars($product['material'] ?? '') ?></td>
                                <td class="text-center"><?= $product['stock_quantity'] ?></td>
                                <td class="text-end"><?= number_format($product['original_price']) ?> đ</td>
                                <td class="text-center">
                                    <?php if ($product['discount_percent'] > 0): ?>
                                        <span class="badge bg-danger"><?= $product['discount_percent'] ?>%</span>
                                        <div class="text-danger mt-1">
                                            <?= number_format($product['price']) ?> đ
                                        </div>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">No discount</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($product['is_featured']): ?>
                                        <i class="bi bi-star-fill text-warning"></i>
                                    <?php else: ?>
                                        <i class="bi bi-star text-secondary"></i>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-warning" onclick='editProduct(<?= json_encode($product) ?>)'>Edit</button>
                                    <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this product?');">
                                        <input type="hidden" name="delete" value="<?= $product['product_id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="../../assets/js/ad_product.js"></script>
    <script>
        // Category data from PHP
        const childCategories = <?= json_encode($child_categories) ?>;
        
        function loadChildCategories() {
            const parentId = document.getElementById('parent_category').value;
            const childSelect = document.getElementById('category_id');
            
            // Clear child categories
            childSelect.innerHTML = '<option value="">Select sub category</option>';
            childSelect.disabled = true;
            
            if (parentId && childCategories[parentId]) {
                // Add child categories
                childCategories[parentId].forEach(function(category) {
                    const option = document.createElement('option');
                    option.value = category.category_id;
                    option.textContent = category.name;
                    childSelect.appendChild(option);
                });
                childSelect.disabled = false;
            }
        }
        
        // Initialize color sections when category changes
        document.getElementById('category_id').addEventListener('change', function() {
            initializeColorSections();
        });
        
        // Initialize color sections on page load
        document.addEventListener('DOMContentLoaded', function() {
            initializeColorSections();
        });
    </script>

</body>

</html>
<?php include '../../includes/footer.php'; ?>