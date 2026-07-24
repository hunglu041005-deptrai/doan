<?php
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();
$isAdminPage = true;

$message = '';
$error = '';

// Check if shop tables exist
function checkShopTables($mysqli) {
    $tables = ['product_categories', 'products', 'orders'];
    $existing = [];
    
    foreach ($tables as $table) {
        $result = $mysqli->query("SHOW TABLES LIKE '$table'");
        if ($result && $result->num_rows > 0) {
            $existing[] = $table;
        }
    }
    
    return $existing;
}

// Create shop tables if requested
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_tables'])) {
    try {
        // Create product_categories table
        $sql1 = "CREATE TABLE IF NOT EXISTS product_categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            slug VARCHAR(100) NOT NULL UNIQUE,
            description TEXT,
            image VARCHAR(255),
            status TINYINT NOT NULL DEFAULT 1,
            sort_order INT NOT NULL DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        if ($mysqli->query($sql1)) {
            $message .= "✅ Tạo bảng product_categories thành công<br>";
        }

        // Create products table
        $sql2 = "CREATE TABLE IF NOT EXISTS products (
            id INT AUTO_INCREMENT PRIMARY KEY,
            category_id INT NOT NULL,
            name VARCHAR(200) NOT NULL,
            slug VARCHAR(200) NOT NULL UNIQUE,
            description TEXT,
            short_description TEXT,
            brand VARCHAR(100),
            price DECIMAL(10,0) NOT NULL,
            sale_price DECIMAL(10,0) DEFAULT NULL,
            sku VARCHAR(100) UNIQUE,
            stock_quantity INT NOT NULL DEFAULT 0,
            image VARCHAR(255),
            status TINYINT NOT NULL DEFAULT 1,
            featured TINYINT NOT NULL DEFAULT 0,
            rating DECIMAL(3,2) DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (category_id) REFERENCES product_categories(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        if ($mysqli->query($sql2)) {
            $message .= "✅ Tạo bảng products thành công<br>";
        }

        // Create orders table
        $sql3 = "CREATE TABLE IF NOT EXISTS orders (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            order_number VARCHAR(50) NOT NULL UNIQUE,
            status ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') NOT NULL DEFAULT 'pending',
            payment_status ENUM('pending', 'paid', 'failed', 'refunded') NOT NULL DEFAULT 'pending',
            total_amount DECIMAL(10,0) NOT NULL,
            customer_name VARCHAR(100) NOT NULL,
            customer_email VARCHAR(150) NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        if ($mysqli->query($sql3)) {
            $message .= "✅ Tạo bảng orders thành công<br>";
        }

        // Add sample data
        $categories = [
            ['Vợt cầu lông', 'vot-cau-long', 'Vợt cầu lông chính hãng'],
            ['Giày thể thao', 'giay-the-thao', 'Giày cầu lông chuyên dụng'],
            ['Quần áo', 'quan-ao', 'Quần áo thể thao cầu lông'],
            ['Phụ kiện', 'phu-kien', 'Túi đựng vợt và phụ kiện khác']
        ];

        foreach ($categories as $cat) {
            $check = $mysqli->query("SELECT id FROM product_categories WHERE slug = '{$cat[1]}'");
            if ($check->num_rows == 0) {
                $stmt = $mysqli->prepare("INSERT INTO product_categories (name, slug, description) VALUES (?, ?, ?)");
                $stmt->bind_param('sss', $cat[0], $cat[1], $cat[2]);
                $stmt->execute();
                $stmt->close();
            }
        }

        $message .= "✅ Thêm dữ liệu mẫu thành công<br>";
        $message .= "<strong>🎉 Thiết lập shop hoàn tất!</strong>";

    } catch (Exception $e) {
        $error = "Lỗi: " . $e->getMessage();
    }
}

$existing_tables = checkShopTables($mysqli);
$setup_complete = count($existing_tables) >= 3;

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid mt-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-lg" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                <div class="card-body text-white py-5">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h1 class="display-5 fw-bold mb-3">
                                <i class="fas fa-store me-3"></i>Thiết lập Shop
                            </h1>
                            <p class="lead mb-0 opacity-90">
                                Kiểm tra và tạo các bảng cần thiết cho hệ thống shop
                            </p>
                        </div>
                        <div class="col-md-4 text-end">
                            <div class="d-none d-md-block">
                                <i class="fas fa-cogs" style="font-size: 4rem; opacity: 0.3;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="row mb-4">
            <div class="col-12">
                <div class="alert alert-success border-0 shadow-sm" role="alert">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-check-circle fa-2x text-success"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h5 class="alert-heading mb-2">Thành công!</h5>
                            <div><?php echo $message; ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="row mb-4">
            <div class="col-12">
                <div class="alert alert-danger border-0 shadow-sm" role="alert">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-exclamation-triangle fa-2x text-danger"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h5 class="alert-heading mb-2">Có lỗi xảy ra!</h5>
                            <div><?php echo htmlspecialchars($error); ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Status Panel -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent border-0 py-3">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-database text-primary me-2"></i>Trạng thái bảng Shop
                    </h5>
                </div>
                <div class="card-body">
                    <?php
                    $tables_info = [
                        'product_categories' => [
                            'name' => 'Danh mục sản phẩm',
                            'icon' => 'fas fa-tags',
                            'color' => 'primary'
                        ],
                        'products' => [
                            'name' => 'Sản phẩm',
                            'icon' => 'fas fa-box',
                            'color' => 'success'
                        ],
                        'orders' => [
                            'name' => 'Đơn hàng',
                            'icon' => 'fas fa-shopping-cart',
                            'color' => 'warning'
                        ]
                    ];
                    ?>

                    <div class="row g-3">
                        <?php foreach ($tables_info as $table => $info): ?>
                            <div class="col-md-4">
                                <div class="card border-0 bg-light h-100">
                                    <div class="card-body text-center p-4">
                                        <div class="mb-3">
                                            <div class="bg-<?php echo $info['color']; ?> bg-opacity-10 rounded-circle p-3 d-inline-flex">
                                                <i class="<?php echo $info['icon']; ?> text-<?php echo $info['color']; ?> fa-2x"></i>
                                            </div>
                                        </div>
                                        <h6 class="fw-bold mb-2"><?php echo $info['name']; ?></h6>
                                        <small class="text-muted d-block mb-3"><?php echo $table; ?></small>
                                        
                                        <?php if (in_array($table, $existing_tables)): ?>
                                            <span class="badge bg-success px-3 py-2">
                                                <i class="fas fa-check me-1"></i>Đã tạo
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-danger px-3 py-2">
                                                <i class="fas fa-times me-1"></i>Chưa tạo
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <?php if (!$setup_complete): ?>
                        <div class="text-center mt-4">
                            <form method="post">
                                <button type="submit" name="create_tables" class="btn btn-primary btn-lg px-5 py-3 hover-lift">
                                    <i class="fas fa-magic me-2"></i>Tạo bảng Shop
                                </button>
                            </form>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-success border-0 mt-4">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-check-circle fa-2x text-success me-3"></i>
                                <div>
                                    <h6 class="mb-1">Tất cả bảng đã được tạo!</h6>
                                    <small class="text-muted">Hệ thống shop đã sẵn sàng để sử dụng</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="text-center mt-4">
                            <a href="shop.php" class="btn btn-success btn-lg px-5 py-3 hover-lift me-3">
                                <i class="fas fa-shopping-cart me-2"></i>Vào trang quản lý Shop
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Guide Panel -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent border-0 py-3">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-lightbulb text-warning me-2"></i>Hướng dẫn
                    </h5>
                </div>
                <div class="card-body">
                    <div class="step-guide">
                        <div class="step-item d-flex mb-3">
                            <div class="step-number bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 30px; height: 30px; font-size: 14px;">1</div>
                            <div class="step-content">
                                <h6 class="mb-1">Kiểm tra trạng thái</h6>
                                <small class="text-muted">Xem các bảng đã được tạo chưa</small>
                            </div>
                        </div>
                        
                        <div class="step-item d-flex mb-3">
                            <div class="step-number bg-success text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 30px; height: 30px; font-size: 14px;">2</div>
                            <div class="step-content">
                                <h6 class="mb-1">Tạo bảng</h6>
                                <small class="text-muted">Click "Tạo bảng Shop" nếu chưa có</small>
                            </div>
                        </div>
                        
                        <div class="step-item d-flex mb-3">
                            <div class="step-number bg-warning text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 30px; height: 30px; font-size: 14px;">3</div>
                            <div class="step-content">
                                <h6 class="mb-1">Quản lý Shop</h6>
                                <small class="text-muted">Vào trang quản lý để thêm sản phẩm</small>
                            </div>
                        </div>
                        
                        <div class="step-item d-flex">
                            <div class="step-number bg-info text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 30px; height: 30px; font-size: 14px;">4</div>
                            <div class="step-content">
                                <h6 class="mb-1">Hoàn tất</h6>
                                <small class="text-muted">Thêm danh mục và sản phẩm theo nhu cầu</small>
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <div class="quick-links">
                        <h6 class="fw-bold mb-3">
                            <i class="fas fa-link text-primary me-2"></i>Links hữu ích
                        </h6>
                        <div class="d-grid gap-2">
                            <a href="dashboard.php" class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-arrow-left me-2"></i>Quay lại Dashboard
                            </a>
                            <?php if ($setup_complete): ?>
                                <a href="shop.php" class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-store me-2"></i>Quản lý Shop
                                </a>
                            <?php endif; ?>
                            <a href="../equipment.php" target="_blank" class="btn btn-outline-info btn-sm">
                                <i class="fas fa-external-link-alt me-2"></i>Xem trang Shop khách hàng
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.hover-lift {
    transition: all 0.3s ease;
}

.hover-lift:hover {
    transform: translateY(-5px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
}

.card {
    border-radius: 15px;
}

.btn {
    border-radius: 10px;
}

.badge {
    border-radius: 8px;
}

.step-number {
    font-weight: bold;
    flex-shrink: 0;
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>