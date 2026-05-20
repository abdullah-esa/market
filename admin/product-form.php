<?php
// admin/product-form.php (UPDATED with image handling)
require_once '../config/database.php';

if (!isAdmin()) {
    redirect('../login.php');
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$product = null;

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $product = $stmt->fetch();
    if (!$product) {
        redirect('products.php');
    }
}

$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

// Handle image upload
$upload_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = (float)$_POST['price'];
    $stock = (int)$_POST['stock'];
    $category_id = $_POST['category_id'] ? (int)$_POST['category_id'] : null;

    // Handle image URL or file upload
    $image_url = '';

    // Option 1: URL input
    if (!empty($_POST['image_url'])) {
        $image_url = trim($_POST['image_url']);
    }

    // Option 2: File upload (if enabled)
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/products/';

        // Create directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_extension = strtolower(pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        if (in_array($file_extension, $allowed_extensions)) {
            $filename = time() . '_' . uniqid() . '.' . $file_extension;
            $upload_path = $upload_dir . $filename;

            if (move_uploaded_file($_FILES['product_image']['tmp_name'], $upload_path)) {
                $image_url = 'uploads/products/' . $filename; // Relative path
            } else {
                $upload_error = "Failed to upload image";
            }
        } else {
            $upload_error = "Invalid file type. Allowed: " . implode(', ', $allowed_extensions);
        }
    }

    // If no image URL and no uploaded file, keep existing image
    if (empty($image_url) && $id) {
        $image_url = $product['image_url'];
    }

    if ($id) {
        // Update
        $stmt = $pdo->prepare("UPDATE products SET name=?, description=?, price=?, stock=?, category_id=?, image_url=? WHERE id=?");
        $stmt->execute([$name, $description, $price, $stock, $category_id, $image_url, $id]);
        $_SESSION['message'] = "Product updated successfully";
    } else {
        // Insert
        $stmt = $pdo->prepare("INSERT INTO products (name, description, price, stock, category_id, image_url) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $description, $price, $stock, $category_id, $image_url]);
        $_SESSION['message'] = "Product added successfully";
    }

    redirect('products.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $id ? 'Edit' : 'Add'; ?> Product - Admin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<?php include '../includes/admin-navbar.php'; ?>

<div class="container mt-4">
<h2><?php echo $id ? 'Edit' : 'Add'; ?> Product</h2>

<?php if ($upload_error): ?>
<div class="alert alert-danger"><?php echo $upload_error; ?></div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data" class="mt-4">
<div class="row">
<div class="col-md-6">
<div class="mb-3">
<label for="name" class="form-label">Product Name *</label>
<input type="text" class="form-control" id="name" name="name"
value="<?php echo htmlspecialchars($product['name'] ?? ''); ?>" required>
</div>

<div class="mb-3">
<label for="category_id" class="form-label">Category</label>
<select class="form-select" id="category_id" name="category_id">
<option value="">Select Category</option>
<?php foreach ($categories as $category): ?>
<option value="<?php echo $category['id']; ?>"
<?php echo isset($product) && $product['category_id'] == $category['id'] ? 'selected' : ''; ?>>
<?php echo htmlspecialchars($category['name']); ?>
</option>
<?php endforeach; ?>
</select>
</div>

<div class="mb-3">
<label for="price" class="form-label">Price *</label>
<input type="number" step="0.01" class="form-control" id="price" name="price"
value="<?php echo $product['price'] ?? ''; ?>" required>
</div>

<div class="mb-3">
<label for="stock" class="form-label">Stock *</label>
<input type="number" class="form-control" id="stock" name="stock"
value="<?php echo $product['stock'] ?? '0'; ?>" required>
</div>
</div>

<div class="col-md-6">
<div class="mb-3">
<label for="image_url" class="form-label">Image URL (Option 1)</label>
<input type="url" class="form-control" id="image_url" name="image_url"
value="<?php echo htmlspecialchars($product['image_url'] ?? ''); ?>"
placeholder="https://example.com/image.jpg">
<small class="text-muted">Enter a direct image URL (must be publicly accessible)</small>
</div>

<div class="mb-3">
<label for="product_image" class="form-label">Or Upload Image (Option 2)</label>
<input type="file" class="form-control" id="product_image" name="product_image"
accept="image/jpeg,image/png,image/gif,image/webp">
<small class="text-muted">Max size: 5MB. Allowed: JPG, PNG, GIF, WEBP</small>
</div>

<div class="mb-3">
<label class="form-label">Image Preview</label>
<div id="imagePreview" class="border rounded p-3 text-center" style="min-height: 200px;">
<?php if (isset($product['image_url']) && $product['image_url']): ?>
<img src="<?php echo htmlspecialchars($product['image_url']); ?>"
style="max-width: 100%; max-height: 200px;"
alt="Current image">
<?php else: ?>
<div class="text-muted">No image preview available</div>
<?php endif; ?>
</div>
</div>
</div>
</div>

<div class="mb-3">
<label for="description" class="form-label">Description</label>
<textarea class="form-control" id="description" name="description" rows="5"><?php
echo htmlspecialchars($product['description'] ?? '');
?></textarea>
</div>

<button type="submit" class="btn btn-primary">Save Product</button>
<a href="products.php" class="btn btn-secondary">Cancel</a>
</form>
</div>

<script>
// Live image preview
const imageUrlInput = document.getElementById('image_url');
const fileInput = document.getElementById('product_image');
const previewDiv = document.getElementById('imagePreview');

function updatePreview(url) {
    if (url) {
        previewDiv.innerHTML = `<img src="${url}" style="max-width: 100%; max-height: 200px;" alt="Preview" onerror="this.onerror=null; this.parentElement.innerHTML='<div class=\"text-danger\">⚠️ Failed to load image. Check URL or file.</div>'">`;
    } else {
        previewDiv.innerHTML = '<div class="text-muted">No image preview available</div>';
    }
}

imageUrlInput.addEventListener('input', function() {
    updatePreview(this.value);
});

fileInput.addEventListener('change', function() {
    if (this.files && this.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            updatePreview(e.target.result);
        };
        reader.readAsDataURL(this.files[0]);
    }
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
