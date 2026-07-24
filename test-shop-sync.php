<?php
require_once __DIR__ . '/includes/functions.php';

echo "<!DOCTYPE html>";
echo "<html><head><title>Test Shop Sync - Real Time</title>";
echo "<meta charset='UTF-8'>";
echo "<meta name='viewport' content='width=device-width, initial-scale=1.0'>";
echo "<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css' rel='stylesheet'>";
echo "<style>";
echo "body { font-family: Arial, sans-serif; margin: 20px; background: #f8f9fa; }";
echo ".test-card { background: white; border-radius: 10px; padding: 20px; margin: 15px 0; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }";
echo ".status-badge { padding: 5px 10px; border-radius: 20px; font-size: 12px; font-weight: bold; }";
echo ".success { background: #d4edda; color: #155724; }";
echo ".error { background: #f8d7da; color: #721c24; }";
echo ".info { background: #d1ecf1; color: #0c5460; }";
echo "table { width: 100%; border-collapse: collapse; margin: 15px 0; }";
echo "th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }";
echo "th { background-color: #f2f2f2; font-weight: bold; }";
echo ".btn { padding: 8px 16px; margin: 5px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-block; }";
echo ".btn-primary { background: #007bff; color: white; }";
echo ".btn-success { background: #28a745; color: white; }";
echo ".btn-warning { background: #ffc107; color: black; }";
echo ".btn-danger { background: #dc3545; color: white; }";
echo ".btn:hover { opacity: 0.8; }";
echo ".refresh-btn { position: fixed; top: 20px; right: 20px; z-index: 1000; }";
echo "</style>";
echo "</head><body>";

echo "<div class='container-fluid'>";
echo "<div class='row'>";
echo "<div class='col-12'>";

echo "<div class='test-card'>";
echo "<h1 class='text-center mb-4'>🛍️ Test Shop Sync - Real Time</h1>";
echo "<p class='text-center text-muted'>Kiểm tra đồng bộ giữa Admin Shop và trang khách hàng</p>";

// Auto refresh button
echo "<button class='btn btn-primary refresh-btn' onclick='location.reload()'>🔄 Refresh</button>";

echo "</div>";

// Test database connection and tables
echo "<div class='test-card'>";
echo "<h3>📊 Trạng thái Database</h3>";

try {
    // Check tables exist
    $tables_check = [
        'product_categories' => $mysqli->query("SHOW TABLES LIKE 'product_categories'")->num_rows > 0,
        'products' => $mysqli->query("SHOW TABLES LIKE 'products'")->num_rows > 0,
        'orders' => $mysqli->query("SHOW TABLES LIKE 'orders'")->num_rows > 0
    ];
    
    echo "<div class='row'>";
    foreach ($tables_check as $table => $exists) {
        echo "<div class='col-md-4'>";
        echo "<div class='status-badge " . ($exists ? 'success' : 'error') . "'>";
        echo ($exists ? '✅' : '❌') . " $table";
        echo "</div>";
        echo "</div>";
    }
    echo "</div>";
    
    if (array_sum($tables_check) < 3) {
        echo "<div class='alert alert-warning mt-3'>";
        echo "<strong>⚠️ Thiếu bảng!</strong> ";
        echo "<a href='admin/setup-shop.php' class='btn btn-warning'>Chạy Setup Shop</a>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='status-badge error'>❌ Lỗi database: " . $e->getMessage() . "</div>";
}

echo "</div>";

// Get current data
echo "<div class='test-card'>";
echo "<h3>📦 Dữ liệu hiện tại</h3>";

try {
    // Categories
    $categories = $mysqli->query('SELECT * FROM product_categories ORDER BY id DESC LIMIT 10');
    echo "<h5>Danh mục sản phẩm:</h5>";
    if ($categories && $categories->num_rows > 0) {
        echo "<table class='table table-striped'>";
        echo "<tr><th>ID</th><th>Tên</th><th>Slug</th><th>Trạng thái</th><th>Ngày tạo</th></tr>";
        while ($cat = $categories->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $cat['id'] . "</td>";
            echo "<td>" . htmlspecialchars($cat['name']) . "</td>";
            echo "<td><code>" . htmlspecialchars($cat['slug']) . "</code></td>";
            echo "<td><span class='status-badge " . ($cat['status'] ? 'success' : 'error') . "'>" . ($cat['status'] ? 'Hoạt động' : 'Ẩn') . "</span></td>";
            echo "<td>" . date('d/m/Y H:i', strtotime($cat['created_at'])) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<div class='status-badge info'>ℹ️ Chưa có danh mục nào</div>";
    }
    
    // Products
    $products = $mysqli->query('SELECT p.*, c.name as category_name FROM products p LEFT JOIN product_categories c ON p.category_id = c.id ORDER BY p.id DESC LIMIT 10');
    echo "<h5 class='mt-4'>Sản phẩm:</h5>";
    if ($products && $products->num_rows > 0) {
        echo "<table class='table table-striped'>";
        echo "<tr><th>ID</th><th>Tên</th><th>Danh mục</th><th>Giá</th><th>Tồn kho</th><th>Trạng thái</th><th>Nổi bật</th><th>Ngày tạo</th></tr>";
        while ($product = $products->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $product['id'] . "</td>";
            echo "<td>" . htmlspecialchars($product['name']) . "</td>";
            echo "<td>" . htmlspecialchars($product['category_name']) . "</td>";
            echo "<td>" . number_format($product['price']) . "đ</td>";
            echo "<td>" . $product['stock_quantity'] . "</td>";
            echo "<td><span class='status-badge " . ($product['status'] ? 'success' : 'error') . "'>" . ($product['status'] ? 'Hoạt động' : 'Ẩn') . "</span></td>";
            echo "<td>" . ($product['featured'] ? '⭐' : '-') . "</td>";
            echo "<td>" . date('d/m/Y H:i', strtotime($product['created_at'])) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<div class='status-badge info'>ℹ️ Chưa có sản phẩm nào</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='status-badge error'>❌ Lỗi: " . $e->getMessage() . "</div>";
}

echo "</div>";

// Test links
echo "<div class='test-card'>";
echo "<h3>🔗 Links Test</h3>";
echo "<div class='row'>";

$links = [
    ['🛠️ Admin Shop', 'admin/shop.php', 'Thêm/Sửa/Xóa sản phẩm', 'btn-primary'],
    ['🛍️ Trang Shop khách hàng', 'equipment.php', 'Xem sản phẩm từ database', 'btn-success'],
    ['📊 Admin Dashboard', 'admin/dashboard.php', 'Quản lý tổng quan', 'btn-warning'],
    ['⚙️ Setup Shop', 'admin/setup-shop.php', 'Thiết lập database', 'btn-danger']
];

foreach ($links as $link) {
    echo "<div class='col-md-6 mb-3'>";
    echo "<div class='card h-100'>";
    echo "<div class='card-body text-center'>";
    echo "<h6>" . $link[0] . "</h6>";
    echo "<p class='text-muted small'>" . $link[2] . "</p>";
    echo "<a href='" . $link[1] . "' target='_blank' class='btn " . $link[3] . "'>Mở trang</a>";
    echo "</div>";
    echo "</div>";
    echo "</div>";
}

echo "</div>";
echo "</div>";

// Test instructions
echo "<div class='test-card'>";
echo "<h3>📋 Hướng dẫn Test</h3>";
echo "<div class='row'>";
echo "<div class='col-md-6'>";
echo "<h5>🔄 Test Sync (Đồng bộ):</h5>";
echo "<ol>";
echo "<li>Mở <strong>Admin Shop</strong> và <strong>Trang khách hàng</strong> trong 2 tab riêng</li>";
echo "<li><strong>Thêm sản phẩm mới</strong> trong admin → Refresh trang khách hàng → Sản phẩm xuất hiện</li>";
echo "<li><strong>Sửa sản phẩm</strong> (tên, giá, mô tả) → Refresh → Thông tin đã thay đổi</li>";
echo "<li><strong>Xóa sản phẩm</strong> → Refresh → Sản phẩm biến mất</li>";
echo "<li><strong>Thêm/sửa danh mục</strong> → Refresh → Danh mục cập nhật</li>";
echo "</ol>";
echo "</div>";
echo "<div class='col-md-6'>";
echo "<h5>✅ Chức năng cần test:</h5>";
echo "<ul>";
echo "<li>✏️ <strong>Sửa danh mục:</strong> Click nút sửa → Form tự động điền</li>";
echo "<li>🗑️ <strong>Xóa danh mục:</strong> Chỉ xóa được khi không có sản phẩm</li>";
echo "<li>✏️ <strong>Sửa sản phẩm:</strong> Click nút sửa → Chuyển tab → Form điền sẵn</li>";
echo "<li>🗑️ <strong>Xóa sản phẩm:</strong> Xác nhận trước khi xóa</li>";
echo "<li>📦 <strong>Cập nhật tồn kho:</strong> Thay đổi số lượng trực tiếp</li>";
echo "<li>🛒 <strong>Giỏ hàng:</strong> Thêm sản phẩm vào giỏ từ trang khách hàng</li>";
echo "</ul>";
echo "</div>";
echo "</div>";
echo "</div>";

// Performance info
echo "<div class='test-card'>";
echo "<h3>⚡ Thông tin hệ thống</h3>";
echo "<div class='row'>";
echo "<div class='col-md-3'>";
echo "<div class='status-badge info'>🕐 " . date('d/m/Y H:i:s') . "</div>";
echo "</div>";
echo "<div class='col-md-3'>";
echo "<div class='status-badge success'>🔄 Auto-refresh: 30s</div>";
echo "</div>";
echo "<div class='col-md-3'>";
$db_status = $mysqli->ping() ? 'Kết nối OK' : 'Lỗi kết nối';
echo "<div class='status-badge " . ($mysqli->ping() ? 'success' : 'error') . "'>🔌 " . $db_status . "</div>";
echo "</div>";
echo "<div class='col-md-3'>";
echo "<div class='status-badge info'>📊 PHP " . phpversion() . "</div>";
echo "</div>";
echo "</div>";
echo "</div>";

echo "</div>"; // col-12
echo "</div>"; // row
echo "</div>"; // container-fluid

// Auto refresh script
echo "<script>";
echo "// Auto refresh every 30 seconds";
echo "setTimeout(function() { location.reload(); }, 30000);";
echo "// Add timestamp to show last update";
echo "document.title = 'Shop Sync Test - ' + new Date().toLocaleTimeString();";
echo "</script>";

echo "</body></html>";
?>