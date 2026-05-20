<?php
// ============================================
// FILE: admin/categories.php
// ============================================
require_once '../config/database.php';

if (!isAdmin()) {
    redirect('../login.php');
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];

    // Check if category has products
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
    $stmt->execute([$id]);
    if ($stmt->fetchColumn() > 0) {
        $_SESSION['error'] = "Cannot delete category with products. Reassign products first.";
    } else {
        $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->execute([$id]);
        $_SESSION['message'] = "Category deleted successfully";
    }
    redirect('categories.php');
}

// Handle add/edit via modal (simplified - using separate page)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_category'])) {
    $id = (int)$_POST['category_id'];
    $name = trim($_POST['name']);
    $slug = strtolower(trim(str_replace(' ', '-', $name)));
    $description = trim($_POST['description']);

    if ($id) {
        $stmt = $pdo->prepare("UPDATE categories SET name=?, slug=?, description=? WHERE id=?");
        $stmt->execute([$name, $slug, $description, $id]);
        $_SESSION['message'] = "Category updated successfully";
    } else {
        $stmt = $pdo->prepare("INSERT INTO categories (name, slug, description) VALUES (?, ?, ?)");
        $stmt->execute([$name, $slug, $description]);
        $_SESSION['message'] = "Category added successfully";
    }
    redirect('categories.php');
}

$categories = $pdo->query("SELECT c.*, COUNT(p.id) as product_count
FROM categories c
LEFT JOIN products p ON c.id = p.category_id
GROUP BY c.id
ORDER BY c.name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Categories - Admin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<?php include '../includes/admin-navbar.php'; ?>

<div class="container mt-4">
<div class="d-flex justify-content-between align-items-center mb-3">
<h2>Manage Categories</h2>
<button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#categoryModal">
<i class="fas fa-plus"></i> Add Category
</button>
</div>

<?php if (isset($_SESSION['message'])): ?>
<div class="alert alert-success"><?php echo $_SESSION['message']; unset($_SESSION['message']); ?></div>
<?php endif; ?>
<?php if (isset($_SESSION['error'])): ?>
<div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
<?php endif; ?>

<div class="table-responsive">
<table class="table table-bordered">
<thead>
<tr>
<th>ID</th>
<th>Name</th>
<th>Slug</th>
<th>Description</th>
<th>Products</th>
<th>Actions</th>
</tr>
</thead>
<tbody>
<?php foreach ($categories as $category): ?>
<tr>
<td><?php echo $category['id']; ?></td>
<td><?php echo htmlspecialchars($category['name']); ?></td>
<td><?php echo htmlspecialchars($category['slug']); ?></td>
<td><?php echo htmlspecialchars(substr($category['description'], 0, 50)); ?></td>
<td><?php echo $category['product_count']; ?></td>
<td>
<button class="btn btn-sm btn-warning edit-category"
data-id="<?php echo $category['id']; ?>"
data-name="<?php echo htmlspecialchars($category['name']); ?>"
data-description="<?php echo htmlspecialchars($category['description']); ?>">
<i class="fas fa-edit"></i>
</button>
<a href="?delete=<?php echo $category['id']; ?>" class="btn btn-sm btn-danger"
onclick="return confirm('Are you sure?')">
<i class="fas fa-trash"></i>
</a>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</div>

<!-- Category Modal -->
<div class="modal fade" id="categoryModal" tabindex="-1">
<div class="modal-dialog">
<div class="modal-content">
<form method="POST">
<div class="modal-header">
<h5 class="modal-title">Add/Edit Category</h5>
<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>
<div class="modal-body">
<input type="hidden" name="category_id" id="category_id">
<div class="mb-3">
<label for="name" class="form-label">Category Name</label>
<input type="text" class="form-control" id="name" name="name" required>
</div>
<div class="mb-3">
<label for="description" class="form-label">Description</label>
<textarea class="form-control" id="description" name="description" rows="3"></textarea>
</div>
</div>
<div class="modal-footer">
<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
<button type="submit" name="save_category" class="btn btn-primary">Save Category</button>
</div>
</form>
</div>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Edit category
document.querySelectorAll('.edit-category').forEach(btn => {
    btn.addEventListener('click', function() {
        document.getElementById('category_id').value = this.dataset.id;
        document.getElementById('name').value = this.dataset.name;
        document.getElementById('description').value = this.dataset.description;
        new bootstrap.Modal(document.getElementById('categoryModal')).show();
    });
});
</script>
</body>
</html>
