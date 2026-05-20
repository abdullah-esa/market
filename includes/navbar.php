<?php
// ============================================
// FILE: includes/navbar.php (WITH SUPPORT LINK)
// ============================================
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
<div class="container">
<a class="navbar-brand" href="index.php">Marketplace</a>
<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
<span class="navbar-toggler-icon"></span>
</button>
<div class="collapse navbar-collapse" id="navbarNav">
<ul class="navbar-nav ms-auto">
<li class="nav-item">
<a class="nav-link" href="index.php">Home</a>
</li>
<?php if (isLoggedIn()): ?>
<?php if ($_SESSION['role'] === 'admin'): ?>
<li class="nav-item">
<a class="nav-link" href="admin/dashboard.php">Admin Panel</a>
</li>
<?php else: ?>
<li class="nav-item">
<a class="nav-link" href="my-orders.php">My Orders</a>
</li>
<?php endif; ?>
<li class="nav-item">
<a class="nav-link" href="customer-support.php">
<i class="fas fa-headset"></i> Support
</a>
</li>
<li class="nav-item">
<a class="nav-link" href="cart.php">
<i class="fas fa-shopping-cart"></i>
Cart <span class="badge bg-danger cart-count"><?php echo getCartItemCount(); ?></span>
</a>
</li>
<li class="nav-item dropdown">
<a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
<i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION['user_name']); ?>
</a>
<ul class="dropdown-menu dropdown-menu-end">
<li><a class="dropdown-item logout-btn" href="logout.php">Logout</a></li>
</ul>
</li>
<?php else: ?>
<li class="nav-item">
<a class="nav-link" href="cart.php">
<i class="fas fa-shopping-cart"></i>
Cart <span class="badge bg-danger cart-count"><?php echo getCartItemCount(); ?></span>
</a>
</li>
<li class="nav-item">
<a class="nav-link" href="login.php">Login</a>
</li>
<li class="nav-item">
<a class="nav-link" href="register.php">Register</a>
</li>
<?php endif; ?>
</ul>
</div>
</div>
</nav>
