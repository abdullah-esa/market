<?php
session_start();

// ============ AIVEN DATABASE CONFIGURATION ============
// !!! IMPORTANT: Replace these with your actual Aiven credentials !!!
$host = 'mysql-backend-collage-abd10esa-e122.j.aivencloud.com';     // Your Aiven host (from console)
$port = 11743;                           // Your Aiven port (not 3306)
$dbname = 'marketplace_db';                   // Your database name
$username = 'avnadmin';                  // Your username
$password = 'AVNS_pfhRqQ0k_yotNUoN_RK';             // Your password

// SSL Certificate path - make sure this file exists
$ssl_ca = __DIR__ . '/ssl/ca.pem';

// For debugging - remove after connection works
error_log("Attempting to connect to Aiven at $host:$port");

try {
    // Create DSN with port
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";

    // SSL Options - TRY DIFFERENT COMBINATIONS

    // OPTION 1: With SSL verification (most secure)
    $options = [
        PDO::MYSQL_ATTR_SSL_CA => $ssl_ca,
        PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => true,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT => 30,
        PDO::ATTR_PERSISTENT => false,
    ];

    // OPTION 2: If Option 1 fails, try with verification disabled (uncomment below)
    /*
     *  $options = [
     *      PDO::MYSQL_ATTR_SSL_CA => $ssl_ca,
     *      PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
     *      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
     *      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
     *      PDO::ATTR_TIMEOUT => 30,
     *  ];
     */

    // OPTION 3: If SSL still fails, try without SSL (uncomment below - NOT RECOMMENDED)
    /*
     *  $options = [
     *      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
     *      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
     *  ];
     */

    error_log("Connecting with DSN: " . str_replace($password, '***', $dsn));

    // Create connection
    $pdo = new PDO($dsn, $username, $password, $options);

    // Test connection
    $pdo->query("SELECT 1");
    error_log("Successfully connected to Aiven database");

    // Set session variables (for Aiven)
    $pdo->exec("SET SESSION sql_mode = ''");
    $pdo->exec("SET time_zone = '+00:00'");

} catch(PDOException $e) {
    // Log detailed error
    error_log("Aiven Database Connection Error: " . $e->getMessage());
    error_log("Error Code: " . $e->getCode());

    // Show user-friendly message
    die("<div style='padding:20px; font-family:Arial;'>
    <h2>Database Connection Error</h2>
    <p>Unable to connect to the database. Please try again later.</p>
    <p><small>Error: " . htmlspecialchars($e->getMessage()) . "</small></p>
    <p><small>Check <strong>debug-aiven.php</strong> for detailed troubleshooting.</small></p>
    </div>");
}

// Helper functions
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function redirect($url) {
    header("Location: $url");
    exit();
}

function getCartItemCount() {
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    return array_sum($_SESSION['cart']);
}
?>
