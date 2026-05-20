<?php
// debug-aiven.php - Full diagnostic tool
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Aiven MySQL Connection Debugger</h1>";

// ============ YOUR AIVEN CREDENTIALS (UPDATE THESE) ============
$host = 'mysql-backend-collage-abd10esa-e122.j.aivencloud.com';     // REPLACE with your actual host
$port = 11743;                           // REPLACE with your actual port
$dbname = 'marketplace_db';                   // REPLACE with your database name
$username = 'avnadmin';                  // REPLACE with your username
$password = 'AVNS_pfhRqQ0k_yotNUoN_RK';             // REPLACE with your password

$ssl_ca = __DIR__ . '/config/ssl/ca.pem';
// ================================================================

echo "<h2>1. Checking SSL Certificate</h2>";
if (file_exists($ssl_ca)) {
    echo "✅ CA Certificate found at: $ssl_ca<br>";
    echo "File size: " . filesize($ssl_ca) . " bytes<br>";
} else {
    echo "❌ CA Certificate NOT found at: $ssl_ca<br>";
    echo "Current directory: " . __DIR__ . "<br>";

    // List what's in the config/ssl directory
    $ssl_dir = __DIR__ . '/config/ssl';
    if (is_dir($ssl_dir)) {
        echo "Contents of config/ssl/:<br>";
        $files = scandir($ssl_dir);
        foreach ($files as $file) {
            if ($file != '.' && $file != '..') {
                echo " - $file<br>";
            }
        }
    } else {
        echo "❌ config/ssl directory does not exist!<br>";
    }
    exit();
}

echo "<h2>2. Testing Network Connection to Aiven</h2>";
$connection = @fsockopen($host, $port, $errno, $errstr, 10);
if ($connection) {
    echo "✅ Successfully reached $host on port $port<br>";
    fclose($connection);
} else {
    echo "❌ Cannot reach $host:$port<br>";
    echo "Error: $errstr ($errno)<br>";
    echo "Check if host and port are correct from Aiven console<br>";
    exit();
}

echo "<h2>3. Attempting MySQL Connection with SSL</h2>";
try {
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";

    $options = [
        PDO::MYSQL_ATTR_SSL_CA => $ssl_ca,
        PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,  // Set to false for testing
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 30,
    ];

    echo "Attempting connection...<br>";
    $pdo = new PDO($dsn, $username, $password, $options);
    echo "✅ PDO Object created successfully!<br>";

    // Test query
    $stmt = $pdo->query("SELECT VERSION() as version, NOW() as time, DATABASE() as db");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    echo "<div style='background: #d4edda; padding: 10px; margin: 10px 0; border-radius: 5px;'>";
    echo "✅ CONNECTION SUCCESSFUL!<br>";
    echo "MySQL Version: " . $result['version'] . "<br>";
    echo "Server Time: " . $result['time'] . "<br>";
    echo "Current Database: " . $result['db'] . "<br>";

    // Check SSL status
    $stmt = $pdo->query("SHOW STATUS LIKE 'Ssl_cipher'");
    $ssl_status = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($ssl_status && $ssl_status['Value']) {
        echo "🔒 SSL Active - Cipher: " . $ssl_status['Value'] . "<br>";
    } else {
        echo "⚠️ SSL might not be active<br>";
    }
    echo "</div>";

} catch (PDOException $e) {
    echo "<div style='background: #f8d7da; padding: 10px; margin: 10px 0; border-radius: 5px; color: #721c24;'>";
    echo "❌ CONNECTION FAILED!<br>";
    echo "Error Code: " . $e->getCode() . "<br>";
    echo "Error Message: " . $e->getMessage() . "<br>";

    // Common error solutions
    $msg = $e->getMessage();
    if (strpos($msg, 'SSL') !== false) {
        echo "<br><strong>SSL Issue Detected!</strong><br>";
        echo "Try these solutions:<br>";
        echo "1. Download fresh CA certificate from Aiven console<br>";
        echo "2. Try with SSL verification disabled for testing<br>";
        echo "3. Check if your PHP has OpenSSL enabled<br>";
    } elseif (strpos($msg, 'Access denied') !== false) {
        echo "<br><strong>Authentication Issue!</strong><br>";
        echo "Check username and password in Aiven console<br>";
    } elseif (strpos($msg, 'Unknown database') !== false) {
        echo "<br><strong>Database Issue!</strong><br>";
        echo "Database '$dbname' does not exist. Check Aiven console for correct database name<br>";
    }
    echo "</div>";
}

echo "<h2>4. PHP Information</h2>";
echo "PHP Version: " . phpversion() . "<br>";
echo "OpenSSL Loaded: " . (extension_loaded('openssl') ? 'Yes' : 'No') . "<br>";
echo "PDO MySQL Loaded: " . (extension_loaded('pdo_mysql') ? 'Yes' : 'No') . "<br>";

echo "<h2>5. Alternative Connection Methods to Try</h2>";

echo "<h3>Method A: Without SSL (for testing only)</h3>";
try {
    $pdo_test = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $username, $password);
    echo "✅ Connection works WITHOUT SSL!<br>";
    echo "This means SSL configuration is the issue.<br>";
} catch (Exception $e) {
    echo "❌ Connection failed even without SSL: " . $e->getMessage() . "<br>";
}

echo "<h3>Method B: MySQLi with SSL</h3>";
$mysqli = mysqli_init();
if ($mysqli->ssl_set(null, null, $ssl_ca, null, null)) {
    echo "SSL set configured for MySQLi<br>";
    $mysqli->real_connect($host, $username, $password, $dbname, $port);
    if ($mysqli->connect_error) {
        echo "❌ MySQLi failed: " . $mysqli->connect_error . "<br>";
    } else {
        echo "✅ MySQLi connection successful!<br>";
        $result = $mysqli->query("SELECT VERSION() as version");
        $row = $result->fetch_assoc();
        echo "Version: " . $row['version'] . "<br>";
        $mysqli->close();
    }
} else {
    echo "❌ Failed to set SSL for MySQLi<br>";
}
?>
