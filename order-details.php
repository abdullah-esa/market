<?php
// ============================================
// FILE: order-details.php
// ============================================
require_once 'config/database.php';

if (!isLoggedIn() || $_SESSION['role'] !== 'customer') {
    redirect('login.php');
}

$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
$stmt->execute([$order_id, $_SESSION['user_id']]);
$order = $stmt->fetch();

if (!$order) {
    redirect('my-orders.php');
}

$stmt = $pdo->prepare("SELECT oi.*, p.name, p.image_url FROM order_items oi
JOIN products p ON oi.product_id = p.id
WHERE oi.order_id = ?");
$stmt->execute([$order_id]);
$items = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Order Details - Marketplace</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'includes/navbar.php'; ?>

<div class="container mt-4">
<div class="card">
<div class="card-header">
<h5>Order #<?php echo $order['order_number']; ?></h5>
</div>
<div class="card-body">
<div class="row">
<div class="col-md-6">
<p><strong>Order Date:</strong> <?php echo date('F j, Y, g:i a', strtotime($order['created_at'])); ?></p>
<p><strong>Status:</strong>
<span class="badge bg-<?php
echo $order['status'] == 'delivered' ? 'success' :
($order['status'] == 'shipped' ? 'info' : 'warning');
?>">
<?php echo ucfirst($order['status']); ?>
</span>
</p>
</div>
<div class="col-md-6">
<p><strong>Shipping Address:</strong></p>
<p><?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></p>
<p><strong>Payment Method:</strong> <?php echo strtoupper($order['payment_method']); ?></p>
</div>
</div>

<h6 class="mt-4">Items Ordered:</h6>
<div class="table-responsive">
<table class="table table-bordered">
<thead>
<tr>
<th>Product</th>
<th>Quantity</th>
<th>Price</th>
<th>Total</th>
</tr>
</thead>
<tbody>
<?php foreach ($items as $item): ?>
<tr>
<td><?php echo htmlspecialchars($item['name']); ?></td>
<td><?php echo $item['quantity']; ?></td>
<td>$<?php echo number_format($item['price_at_time'], 2); ?></td>
<td>$<?php echo number_format($item['quantity'] * $item['price_at_time'], 2); ?></td>
</tr>
<?php endforeach; ?>
</tbody>
<tfoot>
<tr>
<td colspan="3" class="text-end"><strong>Total:</strong></td>
<td><strong>$<?php echo number_format($order['total_amount'], 2); ?></strong></td>
</tr>
</tfoot>
</table>
</div>

<a href="my-orders.php" class="btn btn-secondary">Back to Orders</a>
</div>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
