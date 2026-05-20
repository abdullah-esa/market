<?php
// setup-admin.php - Run this once to set admin password
require_once 'config/database.php';

// If you already have an admin user, update password
// If not, create a new admin user

$admin_email = 'admin@example.com';
$admin_password = 'Admin123!'; // Change this to your desired password
$hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);

// Check if admin exists
$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$admin_email]);

if ($stmt->fetch()) {
    // Update existing admin
    $update = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
    $update->execute([$hashed_password, $admin_email]);
    echo "✅ Admin password updated successfully!<br>";
} else {
    // Create new admin
    $insert = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'admin')");
    $insert->execute(['Administrator', $admin_email, $hashed_password]);
    echo "✅ Admin user created successfully!<br>";
}

echo "<br><strong>Admin Login Credentials:</strong><br>";
echo "Email: " . $admin_email . "<br>";
echo "Password: " . $admin_password . "<br>";
echo "<br><a href='login.php'>Go to Login Page</a>";
?>
