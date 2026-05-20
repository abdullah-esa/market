<?php
// ============================================
// FILE: admin/orders.php (ALL ORDERS IN TABLE FORMAT)
// ============================================
require_once '../config/database.php';

if (!isAdmin()) {
    redirect('../login.php');
}

// Update order status
if (isset($_POST['update_status'])) {
    $order_id = (int)$_POST['order_id'];
    $status = $_POST['status'];

    $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->execute([$status, $order_id]);
    $_SESSION['message'] = "Order status updated";
    redirect('orders.php');
}

// Get all orders
$orders = $pdo->query("SELECT o.*, u.name as customer_name
FROM orders o
JOIN users u ON o.user_id = u.id
ORDER BY o.created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Orders - Admin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<?php include '../includes/admin-navbar.php'; ?>

<div class="container mt-4">
<h2>Manage Orders</h2>

<?php if (isset($_SESSION['message'])): ?>
<div class="alert alert-success"><?php echo $_SESSION['message']; unset($_SESSION['message']); ?></div>
<?php endif; ?>

<div class="table-responsive">
<table class="table table-bordered table-hover">
<thead class="table-dark">
<tr>
<th>Order #</th>
<th>Customer</th>
<th>Date</th>
<th>Total</th>
<th>Status</th>
<th>Payment</th>
<th>Action</th>
</tr>
</thead>
<tbody>
<?php foreach ($orders as $order): ?>
<tr>
<td><strong><?php echo $order['order_number']; ?></strong></td>
<td><?php echo htmlspecialchars($order['customer_name']); ?></td>
<td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
<td><strong>$<?php echo number_format($order['total_amount'], 2); ?></strong></td>
<td>
<?php
$status_class = '';
$status_text = ucfirst($order['status']);
if ($order['status'] == 'delivered') {
    $status_class = 'success';
} elseif ($order['status'] == 'shipped') {
    $status_class = 'info';
} elseif ($order['status'] == 'cancelled') {
    $status_class = 'danger';
} else {
    $status_class = 'warning';
}
?>
<span class="badge bg-<?php echo $status_class; ?>"><?php echo $status_text; ?></span>
</td>
<td><?php echo strtoupper($order['payment_method']); ?></td>
<td>
<button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#orderModal<?php echo $order['id']; ?>">
<i class="fas fa-eye"></i> View Details
</button>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>

<?php if (empty($orders)): ?>
<div class="alert alert-info">No orders found.</div>
<?php endif; ?>
</div>

<!-- Order Details Modals -->
<?php foreach ($orders as $order): ?>
<div class="modal fade" id="orderModal<?php echo $order['id']; ?>" tabindex="-1">
<div class="modal-dialog modal-lg">
<div class="modal-content">
<div class="modal-header">
<h5 class="modal-title">Order Details - <?php echo $order['order_number']; ?></h5>
<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>
<div class="modal-body">
<!-- Order Information -->
<div class="row mb-3">
<div class="col-md-6">
<p><strong>Customer:</strong> <?php echo htmlspecialchars($order['customer_name']); ?></p>
<p><strong>Order Date:</strong> <?php echo date('F j, Y, g:i a', strtotime($order['created_at'])); ?></p>
<p><strong>Payment Method:</strong> <?php echo strtoupper($order['payment_method']); ?></p>
</div>
<div class="col-md-6">
<p><strong>Status:</strong>
<span class="badge bg-<?php
echo $order['status'] == 'delivered' ? 'success' :
($order['status'] == 'shipped' ? 'info' :
($order['status'] == 'cancelled' ? 'danger' : 'warning'));
?>">
<?php echo ucfirst($order['status']); ?>
</span>
</p>
<p><strong>Order Total:</strong> <strong class="text-primary">$<?php echo number_format($order['total_amount'], 2); ?></strong></p>
</div>
</div>

<!-- Shipping Address -->
<div class="mb-3">
<p><strong>Shipping Address:</strong></p>
<div class="border p-2 bg-light rounded">
<?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?>
</div>
</div>

<!-- Order Items Table -->
<h6>Items Ordered:</h6>
<div class="table-responsive">
<table class="table table-sm table-bordered">
<thead class="table-light">
<tr>
<th>Product</th>
<th>Quantity</th>
<th>Price</th>
<th>Total</th>
</tr>
</thead>
<tbody>
<?php
$stmt = $pdo->prepare("SELECT oi.*, p.name FROM order_items oi
JOIN products p ON oi.product_id = p.id
WHERE oi.order_id = ?");
$stmt->execute([$order['id']]);
$items = $stmt->fetchAll();
foreach ($items as $item):
    ?>
    <tr>
    <td><?php echo htmlspecialchars($item['name']); ?></td>
    <td><?php echo $item['quantity']; ?></td>
    <td>$<?php echo number_format($item['price_at_time'], 2); ?></td>
    <td>$<?php echo number_format($item['quantity'] * $item['price_at_time'], 2); ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
    <tfoot class="table-secondary">
    <tr>
    <td colspan="3" class="text-end"><strong>Total:</strong></td>
    <td><strong>$<?php echo number_format($order['total_amount'], 2); ?></strong></td>
    </tr>
    </tfoot>
    </table>
    </div>

    <!-- Update Status Form -->
    <form method="POST" class="mt-3">
    <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
    <div class="row g-2 align-items-end">
    <div class="col-md-8">
    <label class="form-label">Update Order Status</label>
    <select name="status" class="form-select">
    <option value="pending" <?php echo $order['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
    <option value="shipped" <?php echo $order['status'] == 'shipped' ? 'selected' : ''; ?>>Shipped</option>
    <option value="delivered" <?php echo $order['status'] == 'delivered' ? 'selected' : ''; ?>>Delivered</option>
    <option value="cancelled" <?php echo $order['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
    </select>
    </div>
    <div class="col-md-4">
    <button type="submit" name="update_status" class="btn btn-primary w-100">Update Status</button>
    </div>
    </div>
    </form>
    </div>
    <div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
    </div>
    </div>
    </div>
    </div>
    <?php endforeach; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    </body>
    </html>
