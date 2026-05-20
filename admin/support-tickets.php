<?php
// ============================================
// FILE: admin/support-tickets.php (WITH CONNECTION CHECK AT TOP ONLY)
// ============================================
require_once '../config/database.php';

if (!isAdmin()) {
    redirect('../login.php');
}

// ============ CONNECTION CHECK ADDED ============
// Check if support_tickets table exists, create if not
try {
    $table_check = $pdo->query("SHOW TABLES LIKE 'support_tickets'");
    if ($table_check->rowCount() == 0) {
        // Create the table if it doesn't exist
        $pdo->exec("CREATE TABLE IF NOT EXISTS support_tickets (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ticket_number VARCHAR(50) UNIQUE NOT NULL,
                   user_id INT NOT NULL,
                   issue_type VARCHAR(50),
                   subject VARCHAR(255) NOT NULL,
                   message TEXT NOT NULL,
                   order_number VARCHAR(50),
                   status ENUM('open', 'in_progress', 'resolved', 'closed') DEFAULT 'open',
                   admin_response TEXT,
                   created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                   updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                   FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )");
    }
} catch (PDOException $e) {
    // Table creation failed - log error but don't crash
    error_log("Support tickets table error: " . $e->getMessage());
}
// ============ END OF CONNECTION CHECK ============

// Update ticket status
if (isset($_POST['update_status'])) {
    $ticket_id = (int)$_POST['ticket_id'];
    $status = $_POST['status'];
    $admin_response = trim($_POST['admin_response'] ?? '');

    $stmt = $pdo->prepare("UPDATE support_tickets SET status = ?, admin_response = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$status, $admin_response, $ticket_id]);
    $_SESSION['message'] = "Ticket status updated successfully";
    redirect('support-tickets.php');
}

// Get all support tickets
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$sql = "SELECT t.*, u.name as customer_name, u.email as customer_email
FROM support_tickets t
JOIN users u ON t.user_id = u.id ";

if ($status_filter != 'all') {
    $sql .= "WHERE t.status = :status ";
}
$sql .= "ORDER BY t.created_at DESC";

$stmt = $pdo->prepare($sql);
if ($status_filter != 'all') {
    $stmt->execute([':status' => $status_filter]);
} else {
    $stmt->execute();
}
$tickets = $stmt->fetchAll();

// Get counts for dashboard
$open_count = $pdo->query("SELECT COUNT(*) FROM support_tickets WHERE status = 'open'")->fetchColumn();
$in_progress_count = $pdo->query("SELECT COUNT(*) FROM support_tickets WHERE status = 'in_progress'")->fetchColumn();
$resolved_count = $pdo->query("SELECT COUNT(*) FROM support_tickets WHERE status = 'resolved'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Support Tickets - Admin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<?php include '../includes/admin-navbar.php'; ?>

<div class="container mt-4">
<div class="d-flex justify-content-between align-items-center mb-3">
<h2>
<i class="fas fa-ticket-alt text-primary me-2"></i>
Customer Support Tickets
</h2>
</div>

<?php if (isset($_SESSION['message'])): ?>
<div class="alert alert-success"><?php echo $_SESSION['message']; unset($_SESSION['message']); ?></div>
<?php endif; ?>

<!-- Stats Cards -->
<div class="row mb-4">
<div class="col-md-3">
<div class="card text-white bg-warning">
<div class="card-body">
<h5 class="card-title">Open Tickets</h5>
<h2><?php echo $open_count; ?></h2>
<a href="?status=open" class="text-white">View All</a>
</div>
</div>
</div>
<div class="col-md-3">
<div class="card text-white bg-info">
<div class="card-body">
<h5 class="card-title">In Progress</h5>
<h2><?php echo $in_progress_count; ?></h2>
<a href="?status=in_progress" class="text-white">View All</a>
</div>
</div>
</div>
<div class="col-md-3">
<div class="card text-white bg-success">
<div class="card-body">
<h5 class="card-title">Resolved</h5>
<h2><?php echo $resolved_count; ?></h2>
<a href="?status=resolved" class="text-white">View All</a>
</div>
</div>
</div>
<div class="col-md-3">
<div class="card text-white bg-secondary">
<div class="card-body">
<h5 class="card-title">Total Tickets</h5>
<h2><?php echo count($tickets); ?></h2>
<a href="?status=all" class="text-white">View All</a>
</div>
</div>
</div>
</div>

<!-- Filter Tabs -->
<ul class="nav nav-tabs mb-3">
<li class="nav-item">
<a class="nav-link <?php echo $status_filter == 'all' ? 'active' : ''; ?>" href="?status=all">All Tickets</a>
</li>
<li class="nav-item">
<a class="nav-link <?php echo $status_filter == 'open' ? 'active' : ''; ?>" href="?status=open">Open</a>
</li>
<li class="nav-item">
<a class="nav-link <?php echo $status_filter == 'in_progress' ? 'active' : ''; ?>" href="?status=in_progress">In Progress</a>
</li>
<li class="nav-item">
<a class="nav-link <?php echo $status_filter == 'resolved' ? 'active' : ''; ?>" href="?status=resolved">Resolved</a>
</li>
<li class="nav-item">
<a class="nav-link <?php echo $status_filter == 'closed' ? 'active' : ''; ?>" href="?status=closed">Closed</a>
</li>
</ul>

<!-- Tickets Table -->
<div class="table-responsive">
<table class="table table-bordered table-hover">
<thead class="table-dark">
<tr>
<th>Ticket #</th>
<th>Customer</th>
<th>Issue Type</th>
<th>Subject</th>
<th>Order #</th>
<th>Status</th>
<th>Date</th>
<th>Action</th>
</tr>
</thead>
<tbody>
<?php if (empty($tickets)): ?>
<tr>
<td colspan="8" class="text-center">No tickets found</td>
</tr>
<?php else: ?>
<?php foreach ($tickets as $ticket): ?>
<tr>
<td><strong><?php echo $ticket['ticket_number']; ?></strong></td>
<td>
<?php echo htmlspecialchars($ticket['customer_name']); ?>
<br>
<small class="text-muted"><?php echo $ticket['customer_email']; ?></small>
</td>
<td>
<span class="badge bg-secondary">
<?php echo ucfirst($ticket['issue_type'] ?: 'General'); ?>
</span>
</td>
<td><?php echo htmlspecialchars(substr($ticket['subject'], 0, 40)); ?>...</td>
<td><?php echo $ticket['order_number'] ?: 'N/A'; ?></td>
<td>
<span class="badge bg-<?php
echo $ticket['status'] == 'resolved' ? 'success' :
($ticket['status'] == 'in_progress' ? 'info' :
($ticket['status'] == 'closed' ? 'secondary' : 'warning'));
?>">
<?php echo ucfirst($ticket['status']); ?>
</span>
</td>
<td><?php echo date('M d, Y', strtotime($ticket['created_at'])); ?></td>
<td>
<button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#ticketModal<?php echo $ticket['id']; ?>">
<i class="fas fa-eye"></i> View
</button>
</td>
</tr>

<!-- Ticket Details Modal -->
<div class="modal fade" id="ticketModal<?php echo $ticket['id']; ?>" tabindex="-1">
<div class="modal-dialog modal-lg">
<div class="modal-content">
<div class="modal-header">
<h5 class="modal-title">Ticket: <?php echo $ticket['ticket_number']; ?></h5>
<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>
<div class="modal-body">
<div class="row mb-3">
<div class="col-md-6">
<p><strong>Customer:</strong> <?php echo htmlspecialchars($ticket['customer_name']); ?></p>
<p><strong>Email:</strong> <?php echo $ticket['customer_email']; ?></p>
<p><strong>Issue Type:</strong> <?php echo ucfirst($ticket['issue_type'] ?: 'General'); ?></p>
</div>
<div class="col-md-6">
<p><strong>Order Number:</strong> <?php echo $ticket['order_number'] ?: 'N/A'; ?></p>
<p><strong>Status:</strong>
<span class="badge bg-<?php
echo $ticket['status'] == 'resolved' ? 'success' :
($ticket['status'] == 'in_progress' ? 'info' : 'warning');
?>">
<?php echo ucfirst($ticket['status']); ?>
</span>
</p>
<p><strong>Submitted:</strong> <?php echo date('F j, Y, g:i a', strtotime($ticket['created_at'])); ?></p>
</div>
</div>

<div class="mb-3">
<p><strong>Subject:</strong></p>
<div class="border p-2 bg-light rounded">
<?php echo htmlspecialchars($ticket['subject']); ?>
</div>
</div>

<div class="mb-3">
<p><strong>Customer Message:</strong></p>
<div class="border p-3 bg-light rounded">
<?php echo nl2br(htmlspecialchars($ticket['message'])); ?>
</div>
</div>

<?php if ($ticket['admin_response']): ?>
<div class="mb-3">
<p><strong>Admin Response:</strong></p>
<div class="border p-3 bg-info bg-opacity-10 rounded">
<?php echo nl2br(htmlspecialchars($ticket['admin_response'])); ?>
</div>
</div>
<?php endif; ?>

<form method="POST">
<input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
<div class="mb-3">
<label class="form-label">Admin Response</label>
<textarea name="admin_response" class="form-control" rows="3"
placeholder="Type your response here..."><?php echo htmlspecialchars($ticket['admin_response'] ?? ''); ?></textarea>
</div>
<div class="row g-2">
<div class="col-md-6">
<label class="form-label">Update Status</label>
<select name="status" class="form-select">
<option value="open" <?php echo $ticket['status'] == 'open' ? 'selected' : ''; ?>>Open</option>
<option value="in_progress" <?php echo $ticket['status'] == 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
<option value="resolved" <?php echo $ticket['status'] == 'resolved' ? 'selected' : ''; ?>>Resolved</option>
<option value="closed" <?php echo $ticket['status'] == 'closed' ? 'selected' : ''; ?>>Closed</option>
</select>
</div>
<div class="col-md-6">
<label class="form-label">&nbsp;</label>
<button type="submit" name="update_status" class="btn btn-primary w-100">Update Ticket</button>
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
<?php endif; ?>
</tbody>
</table>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
