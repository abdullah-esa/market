<?php
// ============================================
// FILE: includes/admin-navbar.php (FIXED)
// ============================================
// Get open tickets count for badge - with proper error handling
$open_tickets_count = 0;
if (isset($pdo) && $pdo) {
    try {
        // Check if connection is alive
        $pdo->query("SELECT 1");

        // Check if support_tickets table exists
        $table_check = $pdo->query("SHOW TABLES LIKE 'support_tickets'");
        if ($table_check && $table_check->rowCount() > 0) {
            $stmt = $pdo->query("SELECT COUNT(*) FROM support_tickets WHERE status = 'open'");
            if ($stmt) {
                $open_tickets_count = $stmt->fetchColumn();
            }
        }
    } catch (PDOException $e) {
        // Silently fail - don't break the admin panel
        $open_tickets_count = 0;
    }
}
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
<div class="container-fluid">
<a class="navbar-brand" href="dashboard.php">Admin Panel</a>
<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNavbar">
<span class="navbar-toggler-icon"></span>
</button>
<div class="collapse navbar-collapse" id="adminNavbar">
<ul class="navbar-nav me-auto">
<li class="nav-item">
<a class="nav-link" href="dashboard.php">Dashboard</a>
</li>
<li class="nav-item">
<a class="nav-link" href="products.php">Products</a>
</li>
<li class="nav-item">
<a class="nav-link" href="categories.php">Categories</a>
</li>
<li class="nav-item">
<a class="nav-link" href="orders.php">Orders</a>
</li>
<li class="nav-item">
<a class="nav-link" href="support-tickets.php">
<i class="fas fa-ticket-alt"></i> Support Tickets
<?php if ($open_tickets_count > 0): ?>
<span class="badge bg-danger ms-1"><?php echo $open_tickets_count; ?></span>
<?php endif; ?>
</a>
</li>
</ul>
<ul class="navbar-nav">
<li class="nav-item dropdown">
<a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
<?php echo htmlspecialchars($_SESSION['user_name']); ?>
</a>
<ul class="dropdown-menu dropdown-menu-end">
<li><a class="dropdown-item" href="../index.php">View Store</a></li>
<li><hr class="dropdown-divider"></li>
<li><a class="dropdown-item logout-btn" href="../logout.php">Logout</a></li>
</ul>
</li>
</ul>
</div>
</div>
</nav>
