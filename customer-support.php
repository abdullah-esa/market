<?php
// ============================================
// FILE: customer-support.php (WITH DATABASE SAVE)
// ============================================
require_once 'config/database.php';

// Only logged in users can access support page
if (!isLoggedIn()) {
    redirect('login.php');
}

// Handle support ticket submission
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $issue_type = $_POST['issue_type'] ?? '';
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $order_number = trim($_POST['order_number'] ?? '');

    // Validation
    if (empty($subject) || empty($message)) {
        $error_message = 'Please fill in all required fields.';
    } else {
        try {
            // Generate unique ticket number
            $ticket_number = 'TKT-' . time() . '-' . rand(1000, 9999);

            // Save to database
            $stmt = $pdo->prepare("INSERT INTO support_tickets (ticket_number, user_id, issue_type, subject, message, order_number, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, 'open', NOW())");
            $stmt->execute([$ticket_number, $_SESSION['user_id'], $issue_type, $subject, $message, $order_number]);

            $success_message = 'Your issue has been submitted successfully! Ticket #: ' . $ticket_number . ' We will respond within 24-48 hours.';

            // Clear form after successful submission
            $subject = $message = $order_number = '';
            $issue_type = '';
        } catch (PDOException $e) {
            $error_message = 'Failed to submit ticket. Please try again.';
            error_log("Support ticket error: " . $e->getMessage());
        }
    }
}

// Get user's recent orders for reference
$stmt = $pdo->prepare("SELECT order_number, total_amount, status, created_at FROM orders
WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$stmt->execute([$_SESSION['user_id']]);
$recent_orders = $stmt->fetchAll();

// Get user's recent tickets
$stmt = $pdo->prepare("SELECT * FROM support_tickets WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$stmt->execute([$_SESSION['user_id']]);
$my_tickets = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Customer Support - Marketplace</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="assets/css/style.css">
<style>
.support-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 60px 0;
    border-radius: 0 0 30px 30px;
    margin-bottom: 40px;
}
.issue-card {
    cursor: pointer;
    transition: all 0.3s ease;
    border: 2px solid transparent;
}
.issue-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
}
.issue-card.selected {
    border-color: #667eea;
    background-color: #f0f4ff;
}
.faq-item {
    border-bottom: 1px solid #dee2e6;
    padding: 15px 0;
}
.faq-question {
    font-weight: 600;
    cursor: pointer;
    margin: 0;
}
.faq-answer {
    display: none;
    padding-top: 10px;
    color: #6c757d;
}
.faq-question i {
    transition: transform 0.3s ease;
}
.faq-question.active i {
    transform: rotate(90deg);
}
.ticket-status {
    font-size: 0.75rem;
    padding: 3px 8px;
    border-radius: 20px;
}
</style>
</head>
<body>
<?php include 'includes/navbar.php'; ?>

<!-- Support Header -->
<div class="support-header">
<div class="container text-center">
<i class="fas fa-headset fa-3x mb-3"></i>
<h1 class="display-4">Customer Support</h1>
<p class="lead">We're here to help! Submit your issue and we'll get back to you quickly.</p>
</div>
</div>

<div class="container mb-5">
<div class="row">
<!-- Support Form Section -->
<div class="col-lg-7 mb-4">
<div class="card shadow-sm">
<div class="card-header bg-white">
<h4 class="mb-0">
<i class="fas fa-envelope text-primary me-2"></i>
Submit an Issue
</h4>
</div>
<div class="card-body">
<?php if ($success_message): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
<i class="fas fa-check-circle me-2"></i>
<?php echo $success_message; ?>
<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if ($error_message): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
<i class="fas fa-exclamation-triangle me-2"></i>
<?php echo $error_message; ?>
<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<form method="POST" id="supportForm">
<div class="mb-3">
<label class="form-label">Issue Type *</label>
<div class="row g-2">
<div class="col-md-4">
<div class="card issue-card text-center p-3" data-issue="order">
<i class="fas fa-truck fa-2x text-primary mb-2"></i>
<small>Order Issue</small>
</div>
</div>
<div class="col-md-4">
<div class="card issue-card text-center p-3" data-issue="payment">
<i class="fas fa-credit-card fa-2x text-success mb-2"></i>
<small>Payment Problem</small>
</div>
</div>
<div class="col-md-4">
<div class="card issue-card text-center p-3" data-issue="product">
<i class="fas fa-box fa-2x text-warning mb-2"></i>
<small>Product Issue</small>
</div>
</div>
<div class="col-md-4 mt-2">
<div class="card issue-card text-center p-3" data-issue="shipping">
<i class="fas fa-shipping-fast fa-2x text-info mb-2"></i>
<small>Shipping Delay</small>
</div>
</div>
<div class="col-md-4 mt-2">
<div class="card issue-card text-center p-3" data-issue="refund">
<i class="fas fa-undo-alt fa-2x text-danger mb-2"></i>
<small>Refund Request</small>
</div>
</div>
<div class="col-md-4 mt-2">
<div class="card issue-card text-center p-3" data-issue="other">
<i class="fas fa-question-circle fa-2x text-secondary mb-2"></i>
<small>Other</small>
</div>
</div>
</div>
<input type="hidden" name="issue_type" id="issue_type" value="">
</div>

<div class="mb-3">
<label for="order_number" class="form-label">Order Number (Optional)</label>
<input type="text" class="form-control" id="order_number" name="order_number"
placeholder="e.g., ORD-1234567890-1234" value="<?php echo htmlspecialchars($order_number ?? ''); ?>">
<small class="text-muted">If your issue is related to an order, please provide the order number.</small>
</div>

<div class="mb-3">
<label for="subject" class="form-label">Subject *</label>
<input type="text" class="form-control" id="subject" name="subject"
placeholder="Brief summary of your issue" required
value="<?php echo htmlspecialchars($subject ?? ''); ?>">
</div>

<div class="mb-3">
<label for="message" class="form-label">Message *</label>
<textarea class="form-control" id="message" name="message" rows="5"
placeholder="Please provide detailed information about your issue..." required><?php
echo htmlspecialchars($message ?? '');
?></textarea>
</div>

<button type="submit" class="btn btn-primary btn-lg w-100">
<i class="fas fa-paper-plane me-2"></i>
Submit Issue
</button>
</form>
</div>
</div>

<!-- My Recent Tickets -->
<?php if (!empty($my_tickets)): ?>
<div class="card shadow-sm mt-4">
<div class="card-header bg-white">
<h5 class="mb-0">
<i class="fas fa-ticket-alt text-primary me-2"></i>
My Recent Tickets
</h5>
</div>
<div class="card-body">
<div class="list-group">
<?php foreach ($my_tickets as $ticket): ?>
<div class="list-group-item">
<div class="d-flex justify-content-between align-items-center">
<div>
<strong><?php echo $ticket['ticket_number']; ?></strong>
<br>
<small class="text-muted"><?php echo htmlspecialchars($ticket['subject']); ?></small>
</div>
<div class="text-end">
<span class="badge bg-<?php
echo $ticket['status'] == 'resolved' ? 'success' :
($ticket['status'] == 'in_progress' ? 'info' :
($ticket['status'] == 'closed' ? 'secondary' : 'warning'));
?>">
<?php echo ucfirst($ticket['status']); ?>
</span>
<br>
<small class="text-muted"><?php echo date('M d', strtotime($ticket['created_at'])); ?></small>
</div>
</div>
</div>
<?php endforeach; ?>
</div>
</div>
</div>
<?php endif; ?>
</div>

<!-- FAQ & Recent Orders Section -->
<div class="col-lg-5">
<!-- Recent Orders Card -->
<div class="card shadow-sm mb-4">
<div class="card-header bg-white">
<h5 class="mb-0">
<i class="fas fa-history text-primary me-2"></i>
Your Recent Orders
</h5>
</div>
<div class="card-body">
<?php if (empty($recent_orders)): ?>
<div class="text-center text-muted py-3">
<i class="fas fa-shopping-bag fa-2x mb-2"></i>
<p>You haven't placed any orders yet.</p>
<a href="index.php" class="btn btn-sm btn-primary">Start Shopping</a>
</div>
<?php else: ?>
<div class="list-group">
<?php foreach ($recent_orders as $order): ?>
<div class="list-group-item">
<div class="d-flex justify-content-between align-items-center">
<div>
<strong><?php echo $order['order_number']; ?></strong>
<br>
<small class="text-muted"><?php echo date('M d, Y', strtotime($order['created_at'])); ?></small>
</div>
<div class="text-end">
<span class="badge bg-<?php
echo $order['status'] == 'delivered' ? 'success' :
($order['status'] == 'shipped' ? 'info' : 'warning');
?> mb-1">
<?php echo ucfirst($order['status']); ?>
</span>
<br>
<small>$<?php echo number_format($order['total_amount'], 2); ?></small>
</div>
</div>
<button class="btn btn-sm btn-outline-primary mt-2 w-100 copy-order"
data-order="<?php echo $order['order_number']; ?>">
<i class="fas fa-copy"></i> Copy Order Number
</button>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>
</div>
</div>

<!-- FAQ Section -->
<div class="card shadow-sm">
<div class="card-header bg-white">
<h5 class="mb-0">
<i class="fas fa-question-circle text-primary me-2"></i>
Frequently Asked Questions
</h5>
</div>
<div class="card-body">
<div class="faq-item">
<p class="faq-question" data-faq="1">
<i class="fas fa-chevron-right me-2"></i>
How long does shipping take?
</p>
<div class="faq-answer" id="faq-1">
Standard shipping takes 3-5 business days. Express shipping (where available) takes 1-2 business days. You'll receive a tracking number once your order ships.
</div>
</div>

<div class="faq-item">
<p class="faq-question" data-faq="2">
<i class="fas fa-chevron-right me-2"></i>
What is your return policy?
</p>
<div class="faq-answer" id="faq-2">
We accept returns within 30 days of delivery. Products must be unused and in original packaging. Return shipping is free for defective items.
</div>
</div>

<div class="faq-item">
<p class="faq-question" data-faq="3">
<i class="fas fa-chevron-right me-2"></i>
How do I track my order?
</p>
<div class="faq-answer" id="faq-3">
Go to "My Orders" in your account, click on the order number, and you'll see the tracking information once available. You'll also receive email updates.
</div>
</div>

<div class="faq-item">
<p class="faq-question" data-faq="4">
<i class="fas fa-chevron-right me-2"></i>
Can I cancel my order?
</p>
<div class="faq-answer" id="faq-4">
Orders can be cancelled within 1 hour of placement if not yet processed. Go to "My Orders" and click "Cancel Order" if available, or contact support.
</div>
</div>

<div class="faq-item">
<p class="faq-question" data-faq="5">
<i class="fas fa-chevron-right me-2"></i>
How do I get a refund?
</p>
<div class="faq-answer" id="faq-5">
Refunds are processed within 3-5 business days after we receive the returned item. The refund will be issued to your original payment method.
</div>
</div>
</div>
</div>
</div>
</div>
</div>

<!-- Footer -->
<footer class="bg-dark text-white mt-5 py-5">
<div class="container">
<div class="row justify-content-center text-center">
<div class="col-md-8">
<h3 class="mb-3">Marketplace</h3>
<p class="mb-4">Your trusted online shopping destination</p>

<div class="d-flex justify-content-center gap-4 mb-4">
<a href="https://github.com/YOUR_USERNAME" target="_blank" class="text-white text-decoration-none">
<i class="fab fa-github fa-2x mb-1"></i>
<div class="small">GitHub</div>
</a>
<a href="https://YOUR_PORTFOLIO_URL.com" target="_blank" class="text-white text-decoration-none">
<i class="fas fa-briefcase fa-2x mb-1"></i>
<div class="small">Portfolio</div>
</a>
</div>

<hr class="bg-light w-50 mx-auto">

<div class="small mt-3">
&copy; <?php echo date('Y'); ?> Marketplace. All rights reserved.
</div>
</div>
</div>
</div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Issue type selection
const issueCards = document.querySelectorAll('.issue-card');
const issueTypeInput = document.getElementById('issue_type');

issueCards.forEach(card => {
    card.addEventListener('click', function() {
        issueCards.forEach(c => c.classList.remove('selected'));
        this.classList.add('selected');
        issueTypeInput.value = this.dataset.issue;
    });
});

// Copy order number to clipboard
document.querySelectorAll('.copy-order').forEach(button => {
    button.addEventListener('click', function() {
        const orderNumber = this.dataset.order;
        navigator.clipboard.writeText(orderNumber).then(() => {
            const originalText = this.innerHTML;
            this.innerHTML = '<i class="fas fa-check"></i> Copied!';
        setTimeout(() => {
            this.innerHTML = originalText;
        }, 2000);
        });
        document.getElementById('order_number').value = orderNumber;
    });
});

// FAQ Accordion
document.querySelectorAll('.faq-question').forEach(question => {
    question.addEventListener('click', function() {
        const faqId = this.dataset.faq;
        const answer = document.getElementById(`faq-${faqId}`);

        if (answer.style.display === 'block') {
            answer.style.display = 'none';
    this.classList.remove('active');
        } else {
            answer.style.display = 'block';
    this.classList.add('active');
        }
    });
});

// Form validation
document.getElementById('supportForm').addEventListener('submit', function(e) {
    const subject = document.getElementById('subject').value.trim();
    const message = document.getElementById('message').value.trim();

    if (!subject || !message) {
        e.preventDefault();
        alert('Please fill in all required fields.');
    }
});
</script>
</body>
</html>
