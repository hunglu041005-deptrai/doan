<?php
require_once __DIR__ . '/db.php';

echo "<h2>Thiết lập Database cho Shop</h2>";

try {
    // Read and execute the migration file
    $sql = file_get_contents(__DIR__ . '/database/migrations/create_shop_tables.sql');
    
    // Split SQL statements
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            if ($mysqli->query($statement)) {
                echo "<p>✅ Executed: " . substr($statement, 0, 50) . "...</p>";
            } else {
                echo "<p>❌ Error: " . $mysqli->error . "</p>";
                echo "<p>Statement: " . substr($statement, 0, 100) . "...</p>";
            }
        }
    }
    
    echo "<hr>";
    echo "<h3>Kiểm tra kết quả:</h3>";
    
    // Check tables
    $tables = ['product_categories', 'products', 'product_variants', 'orders', 'order_items'];
    foreach ($tables as $table) {
        $result = $mysqli->query("SHOW TABLES LIKE '$table'");
        if ($result->num_rows > 0) {
            echo "<p>✅ Bảng '$table' đã được tạo</p>";
        } else {
            echo "<p>❌ Bảng '$table' chưa được tạo</p>";
        }
    }
    
    // Check sample data
    $categories = $mysqli->query("SELECT COUNT(*) as count FROM product_categories")->fetch_assoc()['count'];
    $products = $mysqli->query("SELECT COUNT(*) as count FROM products")->fetch_assoc()['count'];
    
    echo "<p>📊 Số danh mục: $categories</p>";
    echo "<p>📦 Số sản phẩm: $products</p>";
    
    echo "<hr>";
    echo "<h3>Hoàn tất!</h3>";
    echo "<p>✅ Database shop đã được thiết lập thành công</p>";
    echo "<p><a href='admin/shop.php' class='btn btn-primary'>Vào trang quản lý Shop</a></p>";
    echo "<p><a href='equipment.php' class='btn btn-success'>Xem trang Shop khách hàng</a></p>";
    
} catch (Exception $e) {
    echo "<p>❌ Lỗi: " . $e->getMessage() . "</p>";
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Shop Database</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .btn { display: inline-block; padding: 10px 20px; margin: 5px; text-decoration: none; border-radius: 5px; }
        .btn-primary { background: #007bff; color: white; }
        .btn-success { background: #28a745; color: white; }
    </style>
</head>
<body>
</body>
</html>