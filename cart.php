<?php
// ============================================
// FILE: cart.php
// ============================================
require_once 'config/database.php';

// Initialize cart in session if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Handle cart actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $product_id = (int)$_POST['product_id'];

        switch ($_POST['action']) {
            case 'add':
                if (isset($_SESSION['cart'][$product_id])) {
                    $_SESSION['cart'][$product_id]++;
                } else {
                    $_SESSION['cart'][$product_id] = 1;
                }
                break;

            case 'update':
                $quantity = (int)$_POST['quantity'];
                if ($quantity > 0) {
                    $_SESSION['cart'][$product_id] = $quantity;
                } else {
                    unset($_SESSION['cart'][$product_id]);
                }
                break;

            case 'remove':
                unset($_SESSION['cart'][$product_id]);
                break;
        }

        // AJAX request?
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
            echo json_encode(['success' => true, 'cart_count' => array_sum($_SESSION['cart'])]);
            exit();
        }

        redirect('cart.php');
    }
}

// Get cart items
$cart_items = [];
$subtotal = 0;

if (!empty($_SESSION['cart'])) {
    $ids = array_keys($_SESSION['cart']);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id IN ($placeholders) AND stock > 0");
    $stmt->execute($ids);
    $products = $stmt->fetchAll();

    foreach ($products as $product) {
        $quantity = $_SESSION['cart'][$product['id']];
        $total = $product['price'] * $quantity;
        $cart_items[] = [
            'product' => $product,
            'quantity' => $quantity,
            'total' => $total
        ];
        $subtotal += $total;
    }
}

$shipping = 5.00;
$tax = $subtotal * 0.10; // 10% tax
$total = $subtotal + $shipping + $tax;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Shopping Cart - Marketplace</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'includes/navbar.php'; ?>

<div class="container mt-4">
<h2>Shopping Cart</h2>

<?php if (empty($cart_items)): ?>
<div class="alert alert-info">
Your cart is empty. <a href="index.php">Continue shopping</a>
</div>
<?php else: ?>
<div class="row">
<div class="col-md-8">
<div class="table-responsive">
<table class="table table-bordered">
<thead>
<tr>
<th>Product</th>
<th>Price</th>
<th>Quantity</th>
<th>Total</th>
<th>Action</th>
</tr>
</thead>
<tbody>
<?php foreach ($cart_items as $item): ?>
<tr>
<td>
<img src="<?php echo htmlspecialchars($item['product']['image_url'] ?: 'https://via.placeholder.com/50'); ?>"
alt="" style="width: 50px; height: 50px; object-fit: cover;">
<?php echo htmlspecialchars($item['product']['name']); ?>
</td>
<td>$<?php echo number_format($item['product']['price'], 2); ?></td>
<td>
<form method="POST" class="d-flex align-items-center update-cart-form">
<input type="hidden" name="action" value="update">
<input type="hidden" name="product_id" value="<?php echo $item['product']['id']; ?>">
<input type="number" name="quantity" value="<?php echo $item['quantity']; ?>"
min="1" max="<?php echo $item['product']['stock']; ?>"
class="form-control form-control-sm" style="width: 70px;">
<button type="submit" class="btn btn-sm btn-secondary ms-2">Update</button>
</form>
</td>
<td>$<?php echo number_format($item['total'], 2); ?></td>
<td>
<form method="POST">
<input type="hidden" name="action" value="remove">
<input type="hidden" name="product_id" value="<?php echo $item['product']['id']; ?>">
<button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Remove this item?')">
<i class="fas fa-trash"></i>
</button>
</form>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</div>

<div class="col-md-4">
<div class="card">
<div class="card-header">
<h5>Order Summary</h5>
</div>
<div class="card-body">
<div class="d-flex justify-content-between mb-2">
<span>Subtotal:</span>
<span>$<?php echo number_format($subtotal, 2); ?></span>
</div>
<div class="d-flex justify-content-between mb-2">
<span>Shipping:</span>
<span>$<?php echo number_format($shipping, 2); ?></span>
</div>
<div class="d-flex justify-content-between mb-2">
<span>Tax (10%):</span>
<span>$<?php echo number_format($tax, 2); ?></span>
</div>
<hr>
<div class="d-flex justify-content-between mb-3">
<strong>Total:</strong>
<strong class="text-primary">$<?php echo number_format($total, 2); ?></strong>
</div>
<?php if (isLoggedIn() && $_SESSION['role'] === 'customer'): ?>
<a href="checkout.php" class="btn btn-success w-100">Proceed to Checkout</a>
<?php elseif (!isLoggedIn()): ?>
<div class="alert alert-warning">
Please <a href="login.php">login</a> to checkout
</div>
<?php else: ?>
<div class="alert alert-danger">
Only customers can checkout
</div>
<?php endif; ?>
</div>
</div>
</div>
</div>
<?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/main.js"></script>
</body>
</html>
