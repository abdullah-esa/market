<?php
// ============================================
// FILE: checkout.php
// ============================================
require_once 'config/database.php';

if (!isLoggedIn() || $_SESSION['role'] !== 'customer') {
    redirect('login.php');
}

// Get cart
if (empty($_SESSION['cart'])) {
    redirect('cart.php');
}

// Get cart items
$cart_items = [];
$subtotal = 0;
$ids = array_keys($_SESSION['cart']);
$placeholders = implode(',', array_fill(0, count($ids), '?'));
$stmt = $pdo->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
$stmt->execute($ids);
$products = $stmt->fetchAll();

foreach ($products as $product) {
    $quantity = $_SESSION['cart'][$product['id']];
    if ($quantity > $product['stock']) {
        $_SESSION['error'] = "Not enough stock for {$product['name']}. Available: {$product['stock']}";
        redirect('cart.php');
    }
    $cart_items[] = [
        'product' => $product,
        'quantity' => $quantity,
        'total' => $product['price'] * $quantity
    ];
    $subtotal += $product['price'] * $quantity;
}

$shipping = 5.00;
$tax = $subtotal * 0.10;
$total = $subtotal + $shipping + $tax;

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $shipping_address = trim($_POST['shipping_address']);
    $payment_method = $_POST['payment_method'];

    if (empty($shipping_address)) {
        $error = 'Please enter shipping address';
    } else {
        try {
            $pdo->beginTransaction();

            // Generate order number
            $order_number = 'ORD-' . time() . '-' . rand(1000, 9999);

            // Create order
            $stmt = $pdo->prepare("INSERT INTO orders (user_id, order_number, total_amount, shipping_address, payment_method, status)
            VALUES (?, ?, ?, ?, ?, 'pending')");
            $stmt->execute([$_SESSION['user_id'], $order_number, $total, $shipping_address, $payment_method]);
            $order_id = $pdo->lastInsertId();

            // Create order items and update stock
            foreach ($cart_items as $item) {
                $stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price_at_time)
                VALUES (?, ?, ?, ?)");
                $stmt->execute([$order_id, $item['product']['id'], $item['quantity'], $item['product']['price']]);

                // Update stock
                $stmt = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
                $stmt->execute([$item['quantity'], $item['product']['id']]);
            }

            $pdo->commit();

            // Clear cart
            unset($_SESSION['cart']);

            redirect("order-confirmation.php?order=$order_number");

        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'Checkout failed: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Checkout - Marketplace</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'includes/navbar.php'; ?>

<div class="container mt-4">
<h2>Checkout</h2>

<?php if ($error): ?>
<div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<div class="row">
<div class="col-md-7">
<div class="card">
<div class="card-header">
<h5>Shipping Information</h5>
</div>
<div class="card-body">
<form method="POST">
<div class="mb-3">
<label for="shipping_address" class="form-label">Shipping Address</label>
<textarea class="form-control" id="shipping_address" name="shipping_address" rows="3" required></textarea>
</div>

<div class="mb-3">
<label class="form-label">Payment Method</label>
<div class="form-check">
<input class="form-check-input" type="radio" name="payment_method" id="cod" value="cod" checked>
<label class="form-check-label" for="cod">Cash on Delivery</label>
</div>
<div class="form-check">
<input class="form-check-input" type="radio" name="payment_method" id="card" value="card">
<label class="form-check-label" for="card">Credit Card (Demo)</label>
</div>
</div>

<button type="submit" class="btn btn-success">Place Order</button>
<a href="cart.php" class="btn btn-secondary">Back to Cart</a>
</form>
</div>
</div>
</div>

<div class="col-md-5">
<div class="card">
<div class="card-header">
<h5>Order Summary</h5>
</div>
<div class="card-body">
<?php foreach ($cart_items as $item): ?>
<div class="d-flex justify-content-between mb-2">
<span><?php echo htmlspecialchars($item['product']['name']); ?> x <?php echo $item['quantity']; ?></span>
<span>$<?php echo number_format($item['total'], 2); ?></span>
</div>
<?php endforeach; ?>
<hr>
<div class="d-flex justify-content-between mb-2">
<span>Subtotal:</span>
<span>$<?php echo number_format($subtotal, 2); ?></span>
</div>
<div class="d-flex justify-content-between mb-2">
<span>Shipping:</span>
<span>$<?php echo number_format($shipping, 2); ?></span>
</div>
<div class="d-flex justify-content-between mb-2">
<span>Tax:</span>
<span>$<?php echo number_format($tax, 2); ?></span>
</div>
<hr>
<div class="d-flex justify-content-between">
<strong>Total:</strong>
<strong class="text-primary">$<?php echo number_format($total, 2); ?></strong>
</div>
</div>
</div>
</div>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
