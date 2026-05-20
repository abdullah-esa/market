<?php
// ============================================
// FILE: product.php (WITH RELATED PRODUCTS)
// ============================================
require_once 'config/database.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $pdo->prepare("SELECT p.*, c.name as category_name FROM products p
LEFT JOIN categories c ON p.category_id = c.id
WHERE p.id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) {
    redirect('index.php');
}

// Get related products (same category, excluding current product)
$related_products = [];
if ($product['category_id']) {
    $stmt = $pdo->prepare("SELECT p.*, c.name as category_name FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.category_id = ? AND p.id != ? AND p.stock > 0
    ORDER BY p.created_at DESC LIMIT 4");
    $stmt->execute([$product['category_id'], $id]);
    $related_products = $stmt->fetchAll();
}

// If no related products in same category, get latest products
if (empty($related_products)) {
    $stmt = $pdo->prepare("SELECT p.*, c.name as category_name FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.id != ? AND p.stock > 0
    ORDER BY p.created_at DESC LIMIT 4");
    $stmt->execute([$id]);
    $related_products = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo htmlspecialchars($product['name']); ?> - Marketplace</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'includes/navbar.php'; ?>

<div class="container mt-4">
<div class="row">
<div class="col-md-6">
<img src="<?php
$image_url = $product['image_url'];
if (empty($image_url)) {
    $image_url = 'https://via.placeholder.com/500x400?text=No+Image';
} elseif (strpos($image_url, 'http') !== 0 && strpos($image_url, 'uploads/') === 0) {
    $image_url = '../' . $image_url;
}
echo htmlspecialchars($image_url);
?>"
class="img-fluid rounded w-100"
alt="<?php echo htmlspecialchars($product['name']); ?>"
style="height: 400px; object-fit: cover;"
onerror="this.src='https://via.placeholder.com/500x400?text=Image+Not+Found'">
</div>
<div class="col-md-6">
<nav aria-label="breadcrumb">
<ol class="breadcrumb">
<li class="breadcrumb-item"><a href="index.php">Home</a></li>
<li class="breadcrumb-item active"><?php echo htmlspecialchars($product['name']); ?></li>
</ol>
</nav>

<h1><?php echo htmlspecialchars($product['name']); ?></h1>
<p class="text-muted">Category: <?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?></p>

<div class="mb-3">
<span class="h2 text-primary">$<?php echo number_format($product['price'], 2); ?></span>
<?php if ($product['stock'] > 0): ?>
<span class="badge bg-success ms-2">In Stock (<?php echo $product['stock']; ?> available)</span>
<?php else: ?>
<span class="badge bg-danger ms-2">Out of Stock</span>
<?php endif; ?>
</div>

<div class="mb-4">
<h5>Description</h5>
<p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
</div>

<?php if ($product['stock'] > 0 && isLoggedIn() && $_SESSION['role'] === 'customer'): ?>
<form method="POST" action="cart.php">
<input type="hidden" name="action" value="add">
<input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
<div class="row g-3 align-items-center">
<div class="col-auto">
<label for="quantity" class="col-form-label">Quantity:</label>
</div>
<div class="col-auto">
<input type="number" id="quantity" name="quantity" class="form-control"
value="1" min="1" max="<?php echo $product['stock']; ?>" style="width: 80px;">
</div>
<div class="col-auto">
<button type="submit" class="btn btn-primary btn-lg">
<i class="fas fa-cart-plus"></i> Add to Cart
</button>
</div>
</div>
</form>
<?php elseif (!isLoggedIn()): ?>
<div class="alert alert-info">
Please <a href="login.php">login</a> to purchase this product.
</div>
<?php elseif ($_SESSION['role'] !== 'customer'): ?>
<div class="alert alert-warning">
Only customers can purchase products.
</div>
<?php endif; ?>

<div class="mt-4">
<a href="index.php" class="btn btn-secondary">Continue Shopping</a>
</div>
</div>
</div>

<!-- Related Products Section -->
<?php if (!empty($related_products)): ?>
<div class="row mt-5">
<div class="col-12">
<hr>
<h3 class="mb-4">Related Products</h3>
</div>
<?php foreach ($related_products as $related): ?>
<div class="col-md-3 mb-4">
<div class="card h-100 product-card">
<img src="<?php
$rel_url = $related['image_url'];
if (empty($rel_url)) {
    $rel_url = 'https://via.placeholder.com/300x200?text=No+Image';
} elseif (strpos($rel_url, 'http') !== 0 && strpos($rel_url, 'uploads/') === 0) {
    $rel_url = '../' . $rel_url;
}
echo htmlspecialchars($rel_url);
?>"
class="card-img-top" alt="<?php echo htmlspecialchars($related['name']); ?>"
style="height: 150px; object-fit: cover;"
onerror="this.src='https://via.placeholder.com/300x200?text=No+Image'">
<div class="card-body">
<h6 class="card-title"><?php echo htmlspecialchars($related['name']); ?></h6>
<p class="card-text text-primary fw-bold">$<?php echo number_format($related['price'], 2); ?></p>
<a href="product.php?id=<?php echo $related['id']; ?>" class="btn btn-sm btn-outline-primary">View Product</a>
</div>
</div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/main.js"></script>
</body>
</html>
