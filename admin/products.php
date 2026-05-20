<?php
// ============================================
// FILE: admin/products.php
// ============================================
require_once '../config/database.php';

if (!isAdmin()) {
    redirect('../login.php');
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $_SESSION['message'] = "Product deleted successfully";
    redirect('products.php');
}

// Get all products with category names
$products = $pdo->query("SELECT p.*, c.name as category_name
FROM products p
LEFT JOIN categories c ON p.category_id = c.id
ORDER BY p.created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Products - Admin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<?php include '../includes/admin-navbar.php'; ?>

<div class="container mt-4">
<div class="d-flex justify-content-between align-items-center mb-3">
<h2>Manage Products</h2>
<a href="product-form.php" class="btn btn-primary">
<i class="fas fa-plus"></i> Add New Product
</a>
</div>

<?php if (isset($_SESSION['message'])): ?>
<div class="alert alert-success"><?php echo $_SESSION['message']; unset($_SESSION['message']); ?></div>
<?php endif; ?>

<div class="table-responsive">
<table class="table table-bordered table-hover">
<thead>
<tr>
<th>ID</th>
<th>Image</th>
<th>Name</th>
<th>Category</th>
<th>Price</th>
<th>Stock</th>
<th>Actions</th>
</tr>
</thead>
<tbody>
<?php foreach ($products as $product): ?>
<tr>
<td><?php echo $product['id']; ?></td>
<td>
<?php
$img_url = $product['image_url'];
if (empty($img_url)) {
    $img_url = 'https://via.placeholder.com/50?text=No+Img';
} elseif (strpos($img_url, 'uploads/') === 0) {
    $img_url = '../' . $img_url;
}
?>
<img src="<?php echo htmlspecialchars($img_url); ?>"
style="width: 50px; height: 50px; object-fit: cover;"
onerror="this.src='https://via.placeholder.com/50?text=Error'">
</td>
<td><?php echo htmlspecialchars($product['name']); ?></td>
<td><?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?></td>
<td>$<?php echo number_format($product['price'], 2); ?></td>
<td><?php echo $product['stock']; ?></td>
<td>
<a href="product-form.php?id=<?php echo $product['id']; ?>" class="btn btn-sm btn-warning">
<i class="fas fa-edit"></i>
</a>
<a href="?delete=<?php echo $product['id']; ?>" class="btn btn-sm btn-danger"
onclick="return confirm('Are you sure?')">
<i class="fas fa-trash"></i>
</a>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
