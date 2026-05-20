<?php
// debug-images.php
require_once 'config/database.php';

echo "<h1>Image URL Debugger</h1>";

$stmt = $pdo->query("SELECT id, name, image_url FROM products LIMIT 10");
$products = $stmt->fetchAll();

echo "<table border='1' cellpadding='10'>";
echo "<tr><th>ID</th><th>Product Name</th><th>Image URL in Database</th><th>Status</th><th>Preview</th></tr>";

foreach ($products as $product) {
    echo "<tr>";
    echo "<td>{$product['id']}</td>";
    echo "<td>" . htmlspecialchars($product['name']) . "</td>";
    echo "<td>" . htmlspecialchars($product['image_url'] ?: 'NULL') . "</td>";

    // Check if URL is valid
    if (empty($product['image_url'])) {
        echo "<td style='color:red'>❌ No image URL</td>";
        echo "<td>No image</td>";
    } else {
        // Check if URL is accessible
        $headers = @get_headers($product['image_url']);
        if ($headers && strpos($headers[0], '200')) {
            echo "<td style='color:green'>✅ URL accessible</td>";
            echo "<td><img src='{$product['image_url']}' style='max-width:100px; max-height:100px;'></td>";
        } else {
            echo "<td style='color:red'>❌ URL not accessible</td>";
            echo "<td>Broken image link</td>";
        }
    }
    echo "</tr>";
}
echo "</table>";

echo "<h2>Test different image URL formats:</h2>";
$test_urls = [
    'Placeholder' => 'https://via.placeholder.com/300x200?text=Test',
'Unsplash' => 'https://source.unsplash.com/300x200/?product',
'Local' => '/assets/images/product.jpg',
];

foreach ($test_urls as $type => $url) {
    echo "<strong>$type:</strong> <a href='$url' target='_blank'>$url</a><br>";
    echo "<img src='$url' style='max-width:100px; max-height:100px; margin:10px;'><br>";
}
?>
