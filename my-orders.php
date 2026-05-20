<?php
// ============================================
// FILE: my-orders.php
// ============================================
require_once 'config/database.php';

if (!isLoggedIn() || $_SESSION['role'] !== 'customer') {
    redirect('login.php');
}

$stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$orders = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Orders - Marketplace</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'includes/navbar.php'; ?>

<div class="container mt-4">
<h2>My Orders</h2>

<?php if (empty($orders)): ?>
<div class="alert alert-info">
You haven't placed any orders yet. <a href="index.php">Start shopping</a>
</div>
<?php else: ?>
<div class="table-responsive">
<table class="table table-bordered">
<thead>
<tr>
<th>Order Number</th>
<th>Date</th>
<th>Total</th>
<th>Status</th>
<th>Action</th>
</tr>
</thead>
<tbody>
<?php foreach ($orders as $order): ?>
<tr>
<td><?php echo $order['order_number']; ?></td>
<td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
<td>$<?php echo number_format($order['total_amount'], 2); ?></td>
<td>
<span class="badge bg-<?php
echo $order['status'] == 'delivered' ? 'success' :
($order['status'] == 'shipped' ? 'info' : 'warning');
?>">
<?php echo ucfirst($order['status']); ?>
</span>
</td>
<td>
<a href="order-details.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-primary">View</a>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
<?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
