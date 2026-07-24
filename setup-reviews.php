<?php
require_once __DIR__ . '/includes/functions.php';

// Chỉ admin mới được chạy
if (!isAdmin()) {
    die('Chỉ admin mới được thực hiện thao tác này!');
}

echo "<!DOCTYPE html>";
echo "<html><head><title>Thiết lập hệ thống đánh giá</title>";
echo "<meta charset='UTF-8'>";
echo "<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css' rel='stylesheet'>";
echo "</head><body class='bg-light'>";

echo "<div class='container mt-5'>";
echo "<div class='row justify-content-center'>";
echo "<div class='col-md-8'>";

echo "<div class='card shadow'>";
echo "<div class='card-header bg-primary text-white'>";
echo "<h3 class='mb-0'>⭐ Thiết lập hệ thống đánh giá</h3>";
echo "</div>";
echo "<div class='card-body'>";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['setup_reviews'])) {
    try {
        // Đọc và thực thi SQL
        $sql = file_get_contents(__DIR__ . '/database/migrations/create_reviews_table.sql');
        
        // Tách các câu lệnh SQL
        $statements = array_filter(array_map('trim', explode(';', $sql)));
        
        $success_count = 0;
        $error_count = 0;
        
        foreach ($statements as $statement) {
            if (empty($statement) || strpos($statement, '--') === 0) continue;
            
            try {
                if ($mysqli->query($statement)) {
                    $success_count++;
                    echo "<div class='alert alert-success'>✅ Thực thi thành công: " . substr($statement, 0, 50) . "...</div>";
                } else {
                    $error_count++;
                    echo "<div class='alert alert-warning'>⚠️ Bỏ qua (có thể đã tồn tại): " . substr($statement, 0, 50) . "...</div>";
                }
            } catch (Exception $e) {
                $error_count++;
                echo "<div class='alert alert-danger'>❌ Lỗi: " . $e->getMessage() . "</div>";
            }
        }
        
        // Thêm dữ liệu mẫu
        if ($success_count > 0) {
            echo "<hr><h5>Thêm dữ liệu đánh giá mẫu:</h5>";
            
            // Lấy sản phẩm và user để tạo review mẫu
            $products = $mysqli->query("SELECT id, name FROM products LIMIT 3")->fetch_all(MYSQLI_ASSOC);
            $users = $mysqli->query("SELECT id, name FROM users WHERE role != 'admin' LIMIT 2")->fetch_all(MYSQLI_ASSOC);
            
            if (!empty($products) && !empty($users)) {
                $sample_reviews = [
                    [
                        'rating' => 5,
                        'title' => 'Sản phẩm tuyệt vời!',
                        'comment' => 'Chất lượng rất tốt, đúng như mô tả. Giao hàng nhanh, đóng gói cẩn thận. Sẽ mua lại lần sau.',
                        'status' => 'approved'
                    ],
                    [
                        'rating' => 4,
                        'title' => 'Hài lòng với sản phẩm',
                        'comment' => 'Sản phẩm ổn, chất lượng tốt. Giá cả hợp lý. Chỉ có điều giao hàng hơi chậm một chút.',
                        'status' => 'approved'
                    ],
                    [
                        'rating' => 5,
                        'title' => 'Rất đáng mua!',
                        'comment' => 'Mình đã sử dụng được 2 tuần, cảm thấy rất hài lòng. Chất lượng vượt mong đợi với mức giá này.',
                        'status' => 'approved'
                    ],
                    [
                        'rating' => 3,
                        'title' => 'Tạm ổn',
                        'comment' => 'Sản phẩm bình thường, không có gì đặc biệt. Phù hợp với giá tiền.',
                        'status' => 'approved'
                    ]
                ];
                
                $review_count = 0;
                foreach ($products as $product) {
                    foreach ($users as $user) {
                        if ($review_count >= count($sample_reviews)) break;
                        
                        $review = $sample_reviews[$review_count];
                        $stmt = $mysqli->prepare("INSERT INTO product_reviews (product_id, user_id, rating, title, comment, status) VALUES (?, ?, ?, ?, ?, ?)");
                        $stmt->bind_param('iiisss', 
                            $product['id'], 
                            $user['id'], 
                            $review['rating'], 
                            $review['title'], 
                            $review['comment'], 
                            $review['status']
                        );
                        
                        if ($stmt->execute()) {
                            echo "<div class='alert alert-info'>📝 Thêm đánh giá mẫu cho sản phẩm '{$product['name']}' bởi '{$user['name']}'</div>";
                            $review_count++;
                        }
                        $stmt->close();
                    }
                }
            } else {
                echo "<div class='alert alert-warning'>⚠️ Không có sản phẩm hoặc user để tạo đánh giá mẫu</div>";
            }
        }
        
        echo "<div class='alert alert-success border-0 mt-4'>";
        echo "<h5>🎉 Hoàn tất thiết lập!</h5>";
        echo "<p>Hệ thống đánh giá đã được thiết lập thành công.</p>";
        echo "<div class='mt-3'>";
        echo "<a href='equipment.php' class='btn btn-success me-2'>🛍️ Xem trang shop</a>";
        echo "<a href='admin/shop.php' class='btn btn-primary me-2'>🛠️ Quản lý shop</a>";
        echo "<a href='admin/dashboard.php' class='btn btn-secondary'>📊 Dashboard</a>";
        echo "</div>";
        echo "</div>";
        
    } catch (Exception $e) {
        echo "<div class='alert alert-danger'>❌ Lỗi: " . $e->getMessage() . "</div>";
    }
} else {
    // Show setup form
    echo "<p>Thiết lập hệ thống đánh giá sản phẩm:</p>";
    echo "<ul>";
    echo "<li>Tạo bảng <code>product_reviews</code> để lưu đánh giá</li>";
    echo "<li>Tạo bảng <code>order_items</code> để track việc mua hàng</li>";
    echo "<li>Thêm cột <code>average_rating</code> và <code>review_count</code> vào bảng products</li>";
    echo "<li>Tạo trigger tự động cập nhật rating trung bình</li>";
    echo "<li>Thêm dữ liệu đánh giá mẫu</li>";
    echo "</ul>";
    
    echo "<form method='post'>";
    echo "<button type='submit' name='setup_reviews' class='btn btn-primary btn-lg'>";
    echo "<i class='fas fa-star me-2'></i>Thiết lập hệ thống đánh giá";
    echo "</button>";
    echo "</form>";
    
    echo "<div class='mt-4'>";
    echo "<a href='admin/dashboard.php' class='btn btn-secondary'>← Quay lại Dashboard</a>";
    echo "</div>";
}

echo "</div>";
echo "</div>";
echo "</div>";
echo "</div>";
echo "</div>";

echo "</body></html>";
?>