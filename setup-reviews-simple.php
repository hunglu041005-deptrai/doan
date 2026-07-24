<?php
require_once __DIR__ . '/db.php';

// Simple setup without admin check
echo "<!DOCTYPE html>";
echo "<html><head><title>Setup Reviews System</title>";
echo "<meta charset='UTF-8'>";
echo "<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css' rel='stylesheet'>";
echo "</head><body class='bg-light'>";

echo "<div class='container mt-5'>";
echo "<div class='card shadow mx-auto' style='max-width: 600px;'>";
echo "<div class='card-header bg-primary text-white'>";
echo "<h3 class='mb-0'>🔧 Setup Reviews System</h3>";
echo "</div>";
echo "<div class='card-body'>";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['setup_reviews'])) {
    try {
        echo "<h5>Đang thiết lập hệ thống đánh giá...</h5>";
        
        // 1. Tạo bảng product_reviews
        $sql1 = "CREATE TABLE IF NOT EXISTS product_reviews (
            id INT AUTO_INCREMENT PRIMARY KEY,
            product_id INT NOT NULL,
            user_id INT NOT NULL,
            order_id INT DEFAULT NULL,
            rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
            title VARCHAR(200) DEFAULT NULL,
            comment TEXT,
            status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'approved',
            helpful_count INT NOT NULL DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_product_rating (product_id, rating),
            INDEX idx_user_reviews (user_id),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        if ($mysqli->query($sql1)) {
            echo "<div class='alert alert-success'>✅ Tạo bảng product_reviews thành công</div>";
        } else {
            echo "<div class='alert alert-warning'>⚠️ Bảng product_reviews: " . $mysqli->error . "</div>";
        }

        // 2. Thêm cột rating vào bảng products nếu chưa có
        $check_columns = $mysqli->query("SHOW COLUMNS FROM products LIKE 'average_rating'");
        if ($check_columns->num_rows == 0) {
            $sql3 = "ALTER TABLE products 
                    ADD COLUMN average_rating DECIMAL(3,2) DEFAULT 0,
                    ADD COLUMN review_count INT DEFAULT 0";
            
            if ($mysqli->query($sql3)) {
                echo "<div class='alert alert-success'>✅ Thêm cột rating vào bảng products thành công</div>";
            } else {
                echo "<div class='alert alert-warning'>⚠️ Cột rating: " . $mysqli->error . "</div>";
            }
        } else {
            echo "<div class='alert alert-info'>ℹ️ Cột rating đã tồn tại trong bảng products</div>";
        }

        // 3. Thêm dữ liệu đánh giá mẫu
        echo "<h5>Thêm dữ liệu đánh giá mẫu:</h5>";
        
        // Lấy sản phẩm và user
        $products = $mysqli->query("SELECT id, name FROM products LIMIT 3")->fetch_all(MYSQLI_ASSOC);
        $users = $mysqli->query("SELECT id, name FROM users WHERE role != 'admin' LIMIT 2")->fetch_all(MYSQLI_ASSOC);
        
        if (empty($users)) {
            // Tạo user demo nếu chưa có
            $demo_user_sql = "INSERT IGNORE INTO users (name, email, password, role, status) VALUES 
                ('Demo User', 'demo@test.com', '" . password_hash('123456', PASSWORD_DEFAULT) . "', 'user', 1)";
            $mysqli->query($demo_user_sql);
            $users = $mysqli->query("SELECT id, name FROM users WHERE role != 'admin' LIMIT 2")->fetch_all(MYSQLI_ASSOC);
            echo "<div class='alert alert-info'>ℹ️ Tạo user demo: demo@test.com / 123456</div>";
        }
        
        if (!empty($products) && !empty($users)) {
            $sample_reviews = [
                [5, 'Sản phẩm tuyệt vời!', 'Chất lượng rất tốt, đúng như mô tả. Giao hàng nhanh, đóng gói cẩn thận.'],
                [4, 'Hài lòng với sản phẩm', 'Sản phẩm ổn, chất lượng tốt. Giá cả hợp lý.'],
                [5, 'Rất đáng mua!', 'Mình đã sử dụng được 2 tuần, cảm thấy rất hài lòng.'],
                [3, 'Tạm ổn', 'Sản phẩm bình thường, không có gì đặc biệt.']
            ];
            
            $review_count = 0;
            foreach ($products as $product) {
                foreach ($users as $user) {
                    if ($review_count >= count($sample_reviews)) break;
                    
                    $review = $sample_reviews[$review_count];
                    
                    // Kiểm tra xem đã có review chưa
                    $check = $mysqli->prepare("SELECT id FROM product_reviews WHERE product_id = ? AND user_id = ?");
                    $check->bind_param('ii', $product['id'], $user['id']);
                    $check->execute();
                    $exists = $check->get_result()->num_rows > 0;
                    $check->close();
                    
                    if (!$exists) {
                        $stmt = $mysqli->prepare("INSERT INTO product_reviews (product_id, user_id, rating, title, comment, status) VALUES (?, ?, ?, ?, ?, 'approved')");
                        $stmt->bind_param('iiiss', $product['id'], $user['id'], $review[0], $review[1], $review[2]);
                        
                        if ($stmt->execute()) {
                            echo "<div class='alert alert-info'>📝 Thêm đánh giá cho '{$product['name']}' bởi '{$user['name']}'</div>";
                            $review_count++;
                        }
                        $stmt->close();
                    }
                }
            }
        }

        // 4. Cập nhật rating cho sản phẩm
        echo "<h5>Cập nhật rating cho sản phẩm:</h5>";
        $products_all = $mysqli->query("SELECT id, name FROM products")->fetch_all(MYSQLI_ASSOC);
        
        foreach ($products_all as $product) {
            // Tính rating trung bình
            $rating_query = $mysqli->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as count FROM product_reviews WHERE product_id = ? AND status = 'approved'");
            $rating_query->bind_param('i', $product['id']);
            $rating_query->execute();
            $rating_result = $rating_query->get_result()->fetch_assoc();
            $rating_query->close();
            
            $avg_rating = $rating_result['avg_rating'] ? round($rating_result['avg_rating'], 2) : 0;
            $review_count = $rating_result['count'];
            
            // Cập nhật vào bảng products
            $update = $mysqli->prepare("UPDATE products SET average_rating = ?, review_count = ? WHERE id = ?");
            $update->bind_param('dii', $avg_rating, $review_count, $product['id']);
            $update->execute();
            $update->close();
            
            if ($review_count > 0) {
                echo "<div class='alert alert-success'>⭐ Cập nhật rating cho '{$product['name']}': {$avg_rating}/5 ({$review_count} đánh giá)</div>";
            }
        }
        
        echo "<div class='alert alert-success border-0 mt-4'>";
        echo "<h5>🎉 Setup hoàn tất!</h5>";
        echo "<p>Hệ thống đánh giá đã được thiết lập thành công.</p>";
        echo "<div class='mt-3'>";
        echo "<a href='equipment.php' class='btn btn-success me-2'>🛍️ Xem shop</a>";
        echo "<a href='product-detail.php?id=1' class='btn btn-primary me-2'>📄 Chi tiết sản phẩm</a>";
        echo "<a href='login.php' class='btn btn-warning'>🔑 Đăng nhập</a>";
        echo "</div>";
        echo "</div>";
        
    } catch (Exception $e) {
        echo "<div class='alert alert-danger'>❌ Lỗi: " . $e->getMessage() . "</div>";
    }
} else {
    // Show form
    echo "<p><strong>Thiết lập hệ thống đánh giá sản phẩm</strong></p>";
    echo "<p>Script này sẽ:</p>";
    echo "<ul>";
    echo "<li>✅ Tạo bảng <code>product_reviews</code></li>";
    echo "<li>✅ Thêm cột <code>average_rating</code> và <code>review_count</code> vào bảng products</li>";
    echo "<li>✅ Thêm dữ liệu đánh giá mẫu</li>";
    echo "<li>✅ Cập nhật rating cho tất cả sản phẩm</li>";
    echo "<li>✅ Tạo user demo nếu cần: demo@test.com / 123456</li>";
    echo "</ul>";
    
    echo "<form method='post'>";
    echo "<button type='submit' name='setup_reviews' class='btn btn-primary btn-lg w-100'>";
    echo "<i class='fas fa-wrench me-2'></i>Thiết lập ngay";
    echo "</button>";
    echo "</form>";
    
    echo "<div class='mt-3'>";
    echo "<a href='index.php' class='btn btn-secondary'>← Trang chủ</a>";
    echo "</div>";
}

echo "</div>";
echo "</div>";
echo "</div>";

echo "</body></html>";
?>