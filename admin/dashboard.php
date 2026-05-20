<?php
// ============================================
// FILE: admin/dashboard.php
// ============================================
require_once '../config/database.php';

if (!isAdmin()) {
    redirect('../login.php');
}

// Get stats
$total_products = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$total_orders = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$total_revenue = $pdo->query("SELECT SUM(total_amount) FROM orders WHERE status != 'cancelled'")->fetchColumn();
$pending_orders = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard - Marketplace</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<?php include '../includes/admin-navbar.php'; ?>

<div class="container mt-4">
<h2>Admin Dashboard</h2>

<div class="row mt-4">
<div class="col-md-3 mb-3">
<div class="card text-white bg-primary">
<div class="card-body">
<h5 class="card-title">Total Products</h5>
<h2><?php echo $total_products; ?></h2>
</div>
</div>
</div>

<div class="col-md-3 mb-3">
<div class="card text-white bg-success">
<div class="card-body">
<h5 class="card-title">Total Orders</h5>
<h2><?php echo $total_orders; ?></h2>
</div>
</div>
</div>

<div class="col-md-3 mb-3">
<div class="card text-white bg-warning">
<div class="card-body">
<h5 class="card-title">Total Revenue</h5>
<h2>$<?php echo number_format($total_revenue ?? 0, 2); ?></h2>
</div>
</div>
</div>

<div class="col-md-3 mb-3">
<div class="card text-white bg-danger">
<div class="card-body">
<h5 class="card-title">Pending Orders</h5>
<h2><?php echo $pending_orders; ?></h2>
</div>
</div>
</div>
</div>

<div class="row mt-4">
<div class="col-md-6">
<div class="card">
<div class="card-header">
<h5>Quick Actions</h5>
</div>
<div class="card-body">
<a href="products.php" class="btn btn-primary m-1">Manage Products</a>
<a href="categories.php" class="btn btn-secondary m-1">Manage Categories</a>
<a href="orders.php" class="btn btn-info m-1">View Orders</a>
</div>
</div>
</div>

<div class="col-md-6">
<div class="card">
<div class="card-header">
<h5>Recent Orders</h5>
</div>
<div class="card-body">
<?php
$recent_orders = $pdo->query("SELECT o.*, u.name FROM orders o
JOIN users u ON o.user_id = u.id
ORDER BY o.created_at DESC LIMIT 5")->fetchAll();
?>
<?php if (empty($recent_orders)): ?>
<p>No orders yet</p>
<?php else: ?>
<div class="list-group">
<?php foreach ($recent_orders as $order): ?>
<div class="list-group-item">
<div class="d-flex justify-content-between">
<div>
<strong><?php echo htmlspecialchars($order['order_number']); ?></strong><br>
<small><?php echo htmlspecialchars($order['name']); ?></small>
</div>
<div class="text-end">
<strong>$<?php echo number_format($order['total_amount'], 2); ?></strong><br>
<span class="badge bg-<?php
echo $order['status'] == 'delivered' ? 'success' :
($order['status'] == 'shipped' ? 'info' : 'warning');
?>">
<?php echo ucfirst($order['status']); ?>
</span>
</div>
</div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>
</div>
</div>
</div>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
