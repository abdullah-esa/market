<?php
// ============================================
// FILE: index.php (WITH INDIVIDUAL FILTER REMOVAL)
// ============================================
require_once 'config/database.php';

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Search and filter
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$min_price = isset($_GET['min_price']) ? (float)$_GET['min_price'] : 0;
$max_price = isset($_GET['max_price']) ? (float)$_GET['max_price'] : 0;

// Build query
$sql = "SELECT p.*, c.name as category_name FROM products p
LEFT JOIN categories c ON p.category_id = c.id
WHERE p.stock > 0";
$countSql = "SELECT COUNT(*) as total FROM products p WHERE p.stock > 0";
$params = [];

if ($search) {
    $sql .= " AND p.name LIKE :search";
    $countSql .= " AND name LIKE :search";
    $params[':search'] = "%$search%";
}

if ($category) {
    $sql .= " AND p.category_id = :category";
    $countSql .= " AND category_id = :category";
    $params[':category'] = $category;
}

if ($min_price > 0) {
    $sql .= " AND p.price >= :min_price";
    $countSql .= " AND price >= :min_price";
    $params[':min_price'] = $min_price;
}

if ($max_price > 0) {
    $sql .= " AND p.price <= :max_price";
    $countSql .= " AND price <= :max_price";
    $params[':max_price'] = $max_price;
}

$sql .= " ORDER BY p.created_at DESC LIMIT :limit OFFSET :offset";
$params[':limit'] = $limit;
$params[':offset'] = $offset;

// Get total products for pagination
$stmt = $pdo->prepare($countSql);
foreach ($params as $key => $value) {
    if ($key != ':limit' && $key != ':offset') {
        $stmt->bindValue($key, $value);
    }
}
$stmt->execute();
$totalProducts = $stmt->fetch()['total'];
$totalPages = ceil($totalProducts / $limit);

// Get products
$stmt = $pdo->prepare($sql);
foreach ($params as $key => $value) {
    if ($key == ':limit' || $key == ':offset') {
        $stmt->bindValue($key, $value, PDO::PARAM_INT);
    } else {
        $stmt->bindValue($key, $value);
    }
}
$stmt->execute();
$products = $stmt->fetchAll();

// Get categories for filter
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

// Get category name for display
$category_name = '';
if ($category > 0) {
    $stmt = $pdo->prepare("SELECT name FROM categories WHERE id = ?");
    $stmt->execute([$category]);
    $cat = $stmt->fetch();
    $category_name = $cat ? $cat['name'] : '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Marketplace - Home</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'includes/navbar.php'; ?>

<div class="container mt-4">
<div class="row">
<!-- Sidebar with filters -->
<div class="col-md-3">
<div class="card mb-4">
<div class="card-header">
<h5>Categories</h5>
</div>
<div class="card-body">
<div class="list-group">
<a href="index.php" class="list-group-item list-group-item-action <?php echo !$category && !$min_price && !$max_price && !$search ? 'active' : ''; ?>">
All Products
</a>
<?php foreach ($categories as $cat): ?>
<a href="index.php?category=<?php echo $cat['id']; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?><?php echo $min_price ? '&min_price='.$min_price : ''; ?><?php echo $max_price ? '&max_price='.$max_price : ''; ?>"
class="list-group-item list-group-item-action <?php echo $category == $cat['id'] ? 'active' : ''; ?>">
<?php echo htmlspecialchars($cat['name']); ?>
</a>
<?php endforeach; ?>
</div>
</div>
</div>

<!-- Price Filter Card -->
<div class="card mb-4">
<div class="card-header">
<h5>Filter by Price</h5>
</div>
<div class="card-body">
<form method="GET" action="index.php">
<?php if ($search): ?>
<input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
<?php endif; ?>
<?php if ($category): ?>
<input type="hidden" name="category" value="<?php echo $category; ?>">
<?php endif; ?>
<div class="mb-3">
<label class="form-label">Min Price ($)</label>
<input type="number" name="min_price" class="form-control" placeholder="Min" value="<?php echo $min_price ?: ''; ?>" step="1">
</div>
<div class="mb-3">
<label class="form-label">Max Price ($)</label>
<input type="number" name="max_price" class="form-control" placeholder="Max" value="<?php echo $max_price ?: ''; ?>" step="1">
</div>
<button type="submit" class="btn btn-primary w-100">Apply Filter</button>
</form>
</div>
</div>
</div>

<!-- Product grid -->
<div class="col-md-9">
<div class="row mb-3">
<div class="col">
<form method="GET" class="d-flex">
<input type="text" name="search" class="form-control me-2" placeholder="Search products..." value="<?php echo htmlspecialchars($search); ?>">
<?php if ($category): ?>
<input type="hidden" name="category" value="<?php echo $category; ?>">
<?php endif; ?>
<?php if ($min_price): ?>
<input type="hidden" name="min_price" value="<?php echo $min_price; ?>">
<?php endif; ?>
<?php if ($max_price): ?>
<input type="hidden" name="max_price" value="<?php echo $max_price; ?>">
<?php endif; ?>
<button type="submit" class="btn btn-primary">Search</button>
</form>
</div>
</div>

<!-- Active Filters Display with Individual Removal -->
<?php if ($min_price > 0 || $max_price > 0 || $category > 0 || $search): ?>
<div class="row mb-3">
<div class="col">
<div class="alert alert-info mb-0">
<strong>Active Filters:</strong>
<div class="d-flex flex-wrap gap-2 mt-2">
<?php if ($search): ?>
<a href="?<?php
$params = [];
if ($category) $params[] = "category=$category";
if ($min_price) $params[] = "min_price=$min_price";
if ($max_price) $params[] = "max_price=$max_price";
echo implode('&', $params);
?>" class="badge bg-primary text-decoration-none d-inline-flex align-items-center gap-1" style="font-size: 14px;">
Search: <?php echo htmlspecialchars($search); ?>
<i class="fas fa-times-circle ms-1"></i>
</a>
<?php endif; ?>

<?php if ($category): ?>
<a href="?<?php
$params = [];
if ($search) $params[] = "search=" . urlencode($search);
if ($min_price) $params[] = "min_price=$min_price";
if ($max_price) $params[] = "max_price=$max_price";
echo implode('&', $params);
?>" class="badge bg-primary text-decoration-none d-inline-flex align-items-center gap-1" style="font-size: 14px;">
Category: <?php echo htmlspecialchars($category_name); ?>
<i class="fas fa-times-circle ms-1"></i>
</a>
<?php endif; ?>

<?php if ($min_price > 0): ?>
<a href="?<?php
$params = [];
if ($search) $params[] = "search=" . urlencode($search);
if ($category) $params[] = "category=$category";
if ($max_price) $params[] = "max_price=$max_price";
echo implode('&', $params);
?>" class="badge bg-primary text-decoration-none d-inline-flex align-items-center gap-1" style="font-size: 14px;">
Min Price: $<?php echo number_format($min_price, 2); ?>
<i class="fas fa-times-circle ms-1"></i>
</a>
<?php endif; ?>

<?php if ($max_price > 0): ?>
<a href="?<?php
$params = [];
if ($search) $params[] = "search=" . urlencode($search);
if ($category) $params[] = "category=$category";
if ($min_price) $params[] = "min_price=$min_price";
echo implode('&', $params);
?>" class="badge bg-primary text-decoration-none d-inline-flex align-items-center gap-1" style="font-size: 14px;">
Max Price: $<?php echo number_format($max_price, 2); ?>
<i class="fas fa-times-circle ms-1"></i>
</a>
<?php endif; ?>

<a href="index.php" class="badge bg-danger text-decoration-none d-inline-flex align-items-center gap-1" style="font-size: 14px;">
Clear All Filters
<i class="fas fa-trash-alt ms-1"></i>
</a>
</div>
</div>
</div>
</div>
<?php endif; ?>

<div class="row">
<?php if (empty($products)): ?>
<div class="col-12">
<div class="alert alert-info">No products found.</div>
</div>
<?php else: ?>
<?php foreach ($products as $product): ?>
<div class="col-md-4 mb-4">
<div class="card h-100 product-card">
<img src="<?php
$image_url = $product['image_url'];
if (empty($image_url)) {
    $image_url = 'https://via.placeholder.com/300x200?text=No+Image';
} elseif (strpos($image_url, 'http') !== 0 && strpos($image_url, 'uploads/') === 0) {
    $image_url = '../' . $image_url;
}
echo htmlspecialchars($image_url);
?>"
class="card-img-top" alt="<?php echo htmlspecialchars($product['name']); ?>"
onerror="this.src='https://via.placeholder.com/300x200?text=Image+Not+Found'">
<div class="card-body">
<h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
<p class="card-text text-muted small"><?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?></p>
<p class="card-text"><?php echo htmlspecialchars(substr($product['description'], 0, 100)); ?>...</p>
<div class="d-flex justify-content-between align-items-center">
<span class="h5 text-primary">$<?php echo number_format($product['price'], 2); ?></span>
<?php if (isLoggedIn() && $_SESSION['role'] === 'customer'): ?>
<button class="btn btn-sm btn-success add-to-cart" data-product-id="<?php echo $product['id']; ?>">
<i class="fas fa-cart-plus"></i> Add
</button>
<?php endif; ?>
<a href="product.php?id=<?php echo $product['id']; ?>" class="btn btn-sm btn-outline-primary">View</a>
</div>
</div>
</div>
</div>
<?php endforeach; ?>
<?php endif; ?>
</div>

<!-- Pagination -->
<?php if ($totalPages > 1): ?>
<nav aria-label="Page navigation">
<ul class="pagination justify-content-center">
<?php for ($i = 1; $i <= $totalPages; $i++): ?>
<li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
<a class="page-link" href="?page=<?php echo $i; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?><?php echo $category ? '&category='.$category : ''; ?><?php echo $min_price ? '&min_price='.$min_price : ''; ?><?php echo $max_price ? '&max_price='.$max_price : ''; ?>">
<?php echo $i; ?>
</a>
</li>
<?php endfor; ?>
</ul>
</nav>
<?php endif; ?>
</div>
</div>
</div>

<!-- Improved Centered Footer -->
<footer class="bg-dark text-white mt-5 py-5">
<div class="container">
<div class="row justify-content-center text-center">
<div class="col-md-8">
<h3 class="mb-3">Marketplace</h3>
<p class="mb-4">Your trusted online shopping destination</p>

<div class="d-flex justify-content-center gap-4 mb-4">
<a href="https://github.com/YOUR_USERNAME" target="_blank" class="text-white text-decoration-none">
<i class="fab fa-github fa-2x mb-1"></i>
<div class="small">GitHub</div>
</a>
<a href="https://YOUR_PORTFOLIO_URL.com" target="_blank" class="text-white text-decoration-none">
<i class="fas fa-briefcase fa-2x mb-1"></i>
<div class="small">Portfolio</div>
</a>
</div>

<hr class="bg-light w-50 mx-auto">

<div class="small mt-3">
&copy; <?php echo date('Y'); ?> Marketplace. All rights reserved.
</div>
</div>
</div>
</div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/main.js"></script>
</body>
</html>
