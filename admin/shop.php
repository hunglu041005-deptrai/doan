<?php
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();
$isAdminPage = true;

// Check if shop tables exist
$tables_exist = true;
$required_tables = ['product_categories', 'products'];

foreach ($required_tables as $table) {
    $result = $mysqli->query("SHOW TABLES LIKE '$table'");
    if (!$result || $result->num_rows == 0) {
        $tables_exist = false;
        break;
    }
}

// Redirect to setup if tables don't exist
if (!$tables_exist) {
    header('Location: setup-shop.php');
    exit;
}

$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action']) {
            switch ($_POST['action']) {
                case 'add_category':
                    $name = trim($_POST['category_name'] ?? '');
                    $slug = strtolower(str_replace(' ', '-', $name));
                    $description = trim($_POST['category_description'] ?? '');
                    
                    if ($name) {
                        $stmt = $mysqli->prepare('INSERT INTO product_categories (name, slug, description) VALUES (?, ?, ?)');
                        $stmt->bind_param('sss', $name, $slug, $description);
                        if ($stmt->execute()) {
                            $message = 'Thêm danh mục thành công!';
                        } else {
                            $error = 'Lỗi khi thêm danh mục: ' . $mysqli->error;
                        }
                        $stmt->close();
                    }
                    break;
                    
                case 'edit_category':
                    $id = intval($_POST['category_id'] ?? 0);
                    $name = trim($_POST['category_name'] ?? '');
                    $slug = strtolower(str_replace(' ', '-', $name));
                    $description = trim($_POST['category_description'] ?? '');
                    
                    if ($id && $name) {
                        $stmt = $mysqli->prepare('UPDATE product_categories SET name = ?, slug = ?, description = ? WHERE id = ?');
                        $stmt->bind_param('sssi', $name, $slug, $description, $id);
                        if ($stmt->execute()) {
                            $message = 'Cập nhật danh mục thành công!';
                        } else {
                            $error = 'Lỗi khi cập nhật danh mục: ' . $mysqli->error;
                        }
                        $stmt->close();
                    }
                    break;
                    
                case 'delete_category':
                    $id = intval($_POST['category_id'] ?? 0);
                    if ($id) {
                        // Kiểm tra xem có sản phẩm nào trong danh mục này không
                        $check = $mysqli->prepare('SELECT COUNT(*) as count FROM products WHERE category_id = ?');
                        $check->bind_param('i', $id);
                        $check->execute();
                        $result = $check->get_result();
                        $count = $result->fetch_assoc()['count'];
                        $check->close();
                        
                        if ($count > 0) {
                            $error = 'Không thể xóa danh mục vì còn có sản phẩm bên trong!';
                        } else {
                            $stmt = $mysqli->prepare('DELETE FROM product_categories WHERE id = ?');
                            $stmt->bind_param('i', $id);
                            if ($stmt->execute()) {
                                $message = 'Xóa danh mục thành công!';
                            }
                            $stmt->close();
                        }
                    }
                    break;
                    
                case 'add_product':
                    $category_id = intval($_POST['category_id'] ?? 0);
                    $name = trim($_POST['product_name'] ?? '');
                    $slug = strtolower(str_replace(' ', '-', $name));
                    
                    // Tạo slug unique
                    $base_slug = $slug;
                    $counter = 1;
                    $check_stmt = $mysqli->prepare('SELECT id FROM products WHERE slug = ?');
                    $check_stmt->bind_param('s', $slug);
                    $check_stmt->execute();
                    while ($check_stmt->get_result()->num_rows > 0) {
                        $slug = $base_slug . '-' . $counter;
                        $counter++;
                        $check_stmt->bind_param('s', $slug);
                        $check_stmt->execute();
                    }
                    $check_stmt->close();
                    
                    $description = trim($_POST['product_description'] ?? '');
                    $brand = trim($_POST['brand'] ?? '');
                    $price = intval($_POST['price'] ?? 0);
                    $sale_price = !empty($_POST['sale_price']) ? intval($_POST['sale_price']) : NULL;
                    $sku = trim($_POST['sku'] ?? '');
                    // Tạo SKU tự động nếu để trống
                    if (empty($sku)) {
                        $sku = 'SKU-' . time() . '-' . rand(1000, 9999);
                    }
                    
                    // Kiểm tra SKU unique
                    $base_sku = $sku;
                    $counter = 1;
                    $check_sku_stmt = $mysqli->prepare('SELECT id FROM products WHERE sku = ?');
                    $check_sku_stmt->bind_param('s', $sku);
                    $check_sku_stmt->execute();
                    while ($check_sku_stmt->get_result()->num_rows > 0) {
                        $sku = $base_sku . '-' . $counter;
                        $counter++;
                        $check_sku_stmt->bind_param('s', $sku);
                        $check_sku_stmt->execute();
                    }
                    $check_sku_stmt->close();
                    
                    $stock = intval($_POST['stock_quantity'] ?? 0);
                    $image = trim($_POST['image'] ?? '');
                    $featured = isset($_POST['featured']) ? 1 : 0;
                    
                    if ($name && $category_id && $price) {
                        $stmt = $mysqli->prepare('INSERT INTO products (category_id, name, slug, description, brand, price, sale_price, sku, stock_quantity, image, featured) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
                        $stmt->bind_param('issssiisisi', $category_id, $name, $slug, $description, $brand, $price, $sale_price, $sku, $stock, $image, $featured);
                        if ($stmt->execute()) {
                            $message = 'Thêm sản phẩm thành công!';
                        } else {
                            $error = 'Lỗi khi thêm sản phẩm: ' . $mysqli->error;
                        }
                        $stmt->close();
                    }
                    break;
                    
                case 'edit_product':
                    $id = intval($_POST['product_id'] ?? 0);
                    $category_id = intval($_POST['category_id'] ?? 0);
                    $name = trim($_POST['product_name'] ?? '');
                    $slug = strtolower(str_replace(' ', '-', $name));
                    $description = trim($_POST['product_description'] ?? '');
                    $brand = trim($_POST['brand'] ?? '');
                    $price = intval($_POST['price'] ?? 0);
                    $sale_price = !empty($_POST['sale_price']) ? intval($_POST['sale_price']) : NULL;
                    $sku = trim($_POST['sku'] ?? '');
                    // Tạo SKU tự động nếu để trống
                    if (empty($sku)) {
                        $sku = 'SKU-' . time() . '-' . rand(1000, 9999);
                    }
                    
                    // Kiểm tra SKU unique (trừ sản phẩm hiện tại)
                    $base_sku = $sku;
                    $counter = 1;
                    $check_sku_stmt = $mysqli->prepare('SELECT id FROM products WHERE sku = ? AND id != ?');
                    $check_sku_stmt->bind_param('si', $sku, $id);
                    $check_sku_stmt->execute();
                    while ($check_sku_stmt->get_result()->num_rows > 0) {
                        $sku = $base_sku . '-' . $counter;
                        $counter++;
                        $check_sku_stmt->bind_param('si', $sku, $id);
                        $check_sku_stmt->execute();
                    }
                    $check_sku_stmt->close();
                    
                    $stock = intval($_POST['stock_quantity'] ?? 0);
                    $image = trim($_POST['image'] ?? '');
                    $featured = isset($_POST['featured']) ? 1 : 0;
                    
                    if ($id && $name && $category_id && $price) {
                        $stmt = $mysqli->prepare('UPDATE products SET category_id = ?, name = ?, slug = ?, description = ?, brand = ?, price = ?, sale_price = ?, sku = ?, stock_quantity = ?, image = ?, featured = ? WHERE id = ?');
                        $stmt->bind_param('issssiisisii', $category_id, $name, $slug, $description, $brand, $price, $sale_price, $sku, $stock, $image, $featured, $id);
                        if ($stmt->execute()) {
                            $message = 'Cập nhật sản phẩm thành công!';
                        } else {
                            $error = 'Lỗi khi cập nhật sản phẩm: ' . $mysqli->error;
                        }
                        $stmt->close();
                    }
                    break;
                    
                case 'delete_product':
                    $product_id = intval($_POST['product_id'] ?? 0);
                    if ($product_id) {
                        // Lấy thông tin sản phẩm trước khi xóa
                        $check = $mysqli->prepare('SELECT name FROM products WHERE id = ?');
                        $check->bind_param('i', $product_id);
                        $check->execute();
                        $result = $check->get_result();
                        $product_info = $result->fetch_assoc();
                        $check->close();
                        
                        if ($product_info) {
                            $stmt = $mysqli->prepare('DELETE FROM products WHERE id = ?');
                            $stmt->bind_param('i', $product_id);
                            if ($stmt->execute()) {
                                $message = 'Xóa sản phẩm "' . htmlspecialchars($product_info['name']) . '" thành công!';
                            } else {
                                $error = 'Lỗi khi xóa sản phẩm: ' . $mysqli->error;
                            }
                            $stmt->close();
                        } else {
                            $error = 'Không tìm thấy sản phẩm để xóa!';
                        }
                    }
                    break;
                    
                case 'update_stock':
                    $product_id = intval($_POST['product_id'] ?? 0);
                    $new_stock = intval($_POST['new_stock'] ?? 0);
                    if ($product_id) {
                        $stmt = $mysqli->prepare('UPDATE products SET stock_quantity = ? WHERE id = ?');
                        $stmt->bind_param('ii', $new_stock, $product_id);
                        if ($stmt->execute()) {
                            $message = 'Cập nhật tồn kho thành công!';
                        }
                        $stmt->close();
                    }
                    break;
            }
        }
    }
}

// Get categories
try {
    $categories_result = $mysqli->query('SELECT * FROM product_categories ORDER BY sort_order, name');
    $categories = $categories_result ? $categories_result->fetch_all(MYSQLI_ASSOC) : [];
} catch (Exception $e) {
    $categories = [];
}

// Get products with category info
try {
    $products_result = $mysqli->query('
        SELECT p.*, c.name as category_name 
        FROM products p 
        LEFT JOIN product_categories c ON p.category_id = c.id 
        ORDER BY p.created_at DESC
    ');
    $products = $products_result ? $products_result->fetch_all(MYSQLI_ASSOC) : [];
} catch (Exception $e) {
    $products = [];
}

// Get shop statistics
$stats = [];
try {
    $stats_result = $mysqli->query('SELECT COUNT(*) as count FROM products');
    $stats['total_products'] = $stats_result ? $stats_result->fetch_assoc()['count'] : 0;

    $stats_result = $mysqli->query('SELECT COUNT(*) as count FROM product_categories');
    $stats['total_categories'] = $stats_result ? $stats_result->fetch_assoc()['count'] : 0;

    $stats_result = $mysqli->query('SELECT COUNT(*) as count FROM products WHERE stock_quantity <= 5');
    $stats['low_stock'] = $stats_result ? $stats_result->fetch_assoc()['count'] : 0;

    $stats_result = $mysqli->query('SELECT COUNT(*) as count FROM products WHERE featured = 1');
    $stats['featured_products'] = $stats_result ? $stats_result->fetch_assoc()['count'] : 0;
} catch (Exception $e) {
    $stats['total_products'] = 0;
    $stats['total_categories'] = 0;
    $stats['low_stock'] = 0;
    $stats['featured_products'] = 0;
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm mb-4">
                <div class="card-body d-flex flex-column flex-md-row justify-content-between align-items-start gap-3">
                    <div>
                        <h2 class="card-title">🛍️ Quản lý Shop</h2>
                        <p class="text-muted mb-0">Quản lý sản phẩm, danh mục và đơn hàng</p>
                    </div>
                    <div>
                        <a href="dashboard.php" class="btn btn-outline-secondary me-2">← Dashboard</a>
                        <a href="shop-orders.php" class="btn btn-success">📦 Đơn hàng</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?php echo htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (empty($categories) && empty($products)): ?>
        <div class="alert alert-warning">
            <h5><i class="fas fa-exclamation-triangle me-2"></i>Chưa có dữ liệu shop</h5>
            <p class="mb-2">Có vẻ như bảng shop chưa được tạo hoặc chưa có dữ liệu.</p>
            <a href="../run-shop-migration.php" class="btn btn-warning">
                <i class="fas fa-database me-2"></i>Chạy thiết lập Shop
            </a>
        </div>
    <?php endif; ?>

    <!-- Statistics -->
    <div class="row gy-3 mb-4">
        <div class="col-md-3">
            <div class="card shadow-sm border-start border-4 border-primary">
                <div class="card-body">
                    <h6 class="text-uppercase text-muted mb-1">
                        <i class="fas fa-box"></i> Tổng sản phẩm
                    </h6>
                    <p class="display-6 mb-0"><?php echo $stats['total_products']; ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-start border-4 border-info">
                <div class="card-body">
                    <h6 class="text-uppercase text-muted mb-1">
                        <i class="fas fa-tags"></i> Danh mục
                    </h6>
                    <p class="display-6 mb-0"><?php echo $stats['total_categories']; ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-start border-4 border-warning">
                <div class="card-body">
                    <h6 class="text-uppercase text-muted mb-1">
                        <i class="fas fa-exclamation-triangle"></i> Sắp hết hàng
                    </h6>
                    <p class="display-6 mb-0"><?php echo $stats['low_stock']; ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-start border-4 border-success">
                <div class="card-body">
                    <h6 class="text-uppercase text-muted mb-1">
                        <i class="fas fa-star"></i> Sản phẩm nổi bật
                    </h6>
                    <p class="display-6 mb-0"><?php echo $stats['featured_products']; ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabs -->
    <ul class="nav nav-tabs mb-4" id="shopTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="products-tab" data-bs-toggle="tab" data-bs-target="#products" type="button" role="tab">
                <i class="fas fa-box me-2"></i>Sản phẩm
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="categories-tab" data-bs-toggle="tab" data-bs-target="#categories" type="button" role="tab">
                <i class="fas fa-tags me-2"></i>Danh mục
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="add-product-tab" data-bs-toggle="tab" data-bs-target="#add-product" type="button" role="tab">
                <i class="fas fa-plus me-2"></i>Thêm sản phẩm
            </button>
        </li>
    </ul>

    <div class="tab-content" id="shopTabsContent">
        <!-- Products Tab -->
        <div class="tab-pane fade show active" id="products" role="tabpanel">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title mb-3">Danh sách sản phẩm</h5>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Hình ảnh</th>
                                    <th>Tên sản phẩm</th>
                                    <th>Danh mục</th>
                                    <th>Giá</th>
                                    <th>Tồn kho</th>
                                    <th>Trạng thái</th>
                                    <th>Hành động</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($products as $product): ?>
                                    <tr>
                                        <td>
                                            <img src="<?php echo htmlspecialchars($product['image']); ?>" 
                                                 alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                                 class="rounded" style="width: 50px; height: 50px; object-fit: cover;">
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($product['name']); ?></strong><br>
                                            <small class="text-muted">SKU: <?php echo htmlspecialchars($product['sku']); ?></small>
                                            <?php if ($product['featured']): ?>
                                                <span class="badge bg-warning text-dark ms-1">Nổi bật</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($product['category_name']); ?></td>
                                        <td>
                                            <?php if ($product['sale_price']): ?>
                                                <span class="text-danger fw-bold"><?php echo number_format($product['sale_price']); ?>đ</span><br>
                                                <small class="text-muted text-decoration-line-through"><?php echo number_format($product['price']); ?>đ</small>
                                            <?php else: ?>
                                                <span class="fw-bold"><?php echo number_format($product['price']); ?>đ</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <form method="post" class="d-flex align-items-center" style="max-width: 120px;">
                                                <input type="hidden" name="action" value="update_stock">
                                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                                <input type="number" name="new_stock" value="<?php echo $product['stock_quantity']; ?>" 
                                                       class="form-control form-control-sm me-1" min="0" style="width: 70px;">
                                                <button type="submit" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-save"></i>
                                                </button>
                                            </form>
                                            <?php if ($product['stock_quantity'] <= 5): ?>
                                                <small class="text-warning">⚠️ Sắp hết</small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($product['status']): ?>
                                                <span class="badge bg-success">Hoạt động</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Ẩn</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <button class="btn btn-outline-primary editProductBtn" 
                                                        data-id="<?php echo $product['id']; ?>"
                                                        data-category="<?php echo $product['category_id']; ?>"
                                                        data-name="<?php echo htmlspecialchars($product['name']); ?>"
                                                        data-description="<?php echo htmlspecialchars($product['description']); ?>"
                                                        data-brand="<?php echo htmlspecialchars($product['brand']); ?>"
                                                        data-price="<?php echo $product['price']; ?>"
                                                        data-sale-price="<?php echo $product['sale_price']; ?>"
                                                        data-sku="<?php echo htmlspecialchars($product['sku']); ?>"
                                                        data-stock="<?php echo $product['stock_quantity']; ?>"
                                                        data-image="<?php echo htmlspecialchars($product['image']); ?>"
                                                        data-featured="<?php echo $product['featured']; ?>"
                                                        title="Sửa">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <form method="post" class="d-inline" onsubmit="return confirm('Bạn có chắc muốn xóa sản phẩm này?');">
                                                    <input type="hidden" name="action" value="delete_product">
                                                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                                    <button type="submit" class="btn btn-outline-danger" title="Xóa">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Categories Tab -->
        <div class="tab-pane fade" id="categories" role="tabpanel">
            <div class="row">
                <div class="col-md-4">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title" id="categoryFormTitle">Thêm danh mục mới</h5>
                            <form method="post" id="categoryForm">
                                <input type="hidden" name="action" value="add_category" id="categoryAction">
                                <input type="hidden" name="category_id" value="" id="categoryId">
                                <div class="mb-3">
                                    <label class="form-label">Tên danh mục</label>
                                    <input type="text" name="category_name" id="categoryName" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Mô tả</label>
                                    <textarea name="category_description" id="categoryDescription" class="form-control" rows="3"></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary w-100" id="categorySubmitBtn">
                                    <i class="fas fa-plus me-2"></i>Thêm danh mục
                                </button>
                                <button type="button" class="btn btn-secondary w-100 mt-2" id="cancelCategoryEdit" style="display: none;" onclick="resetCategoryForm()">
                                    <i class="fas fa-times me-2"></i>Hủy chỉnh sửa
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-md-8">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title">Danh sách danh mục</h5>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Tên</th>
                                            <th>Slug</th>
                                            <th>Mô tả</th>
                                            <th>Trạng thái</th>
                                            <th>Hành động</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($categories as $category): ?>
                                            <tr>
                                                <td><strong><?php echo htmlspecialchars($category['name']); ?></strong></td>
                                                <td><code><?php echo htmlspecialchars($category['slug']); ?></code></td>
                                                <td><?php echo htmlspecialchars(substr($category['description'], 0, 50)); ?>...</td>
                                                <td>
                                                    <?php if ($category['status']): ?>
                                                        <span class="badge bg-success">Hoạt động</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">Ẩn</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <button class="btn btn-outline-primary editCategoryBtn"
                                                                data-id="<?php echo $category['id']; ?>"
                                                                data-name="<?php echo htmlspecialchars($category['name']); ?>"
                                                                data-description="<?php echo htmlspecialchars($category['description']); ?>"
                                                                title="Sửa">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <form method="post" class="d-inline" onsubmit="return confirm('Bạn có chắc muốn xóa danh mục này?');">
                                                            <input type="hidden" name="action" value="delete_category">
                                                            <input type="hidden" name="category_id" value="<?php echo $category['id']; ?>">
                                                            <button type="submit" class="btn btn-outline-danger" title="Xóa">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add Product Tab -->
        <div class="tab-pane fade" id="add-product" role="tabpanel">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title mb-3" id="productFormTitle">Thêm sản phẩm mới</h5>
                    <form method="post" id="productForm">
                        <input type="hidden" name="action" value="add_product" id="productAction">
                        <input type="hidden" name="product_id" value="" id="productId">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Tên sản phẩm *</label>
                                    <input type="text" name="product_name" id="productName" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Danh mục *</label>
                                    <select name="category_id" id="productCategory" class="form-select" required>
                                        <option value="">Chọn danh mục</option>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?php echo $category['id']; ?>">
                                                <?php echo htmlspecialchars($category['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Thương hiệu</label>
                                    <input type="text" name="brand" id="productBrand" class="form-control" placeholder="Yonex, Victor, Lining...">
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Giá gốc *</label>
                                            <input type="number" name="price" id="productPrice" class="form-control" min="0" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Giá khuyến mãi</label>
                                            <input type="number" name="sale_price" id="productSalePrice" class="form-control" min="0">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">SKU</label>
                                    <input type="text" name="sku" id="productSku" class="form-control" placeholder="Mã sản phẩm">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Số lượng tồn kho *</label>
                                    <input type="number" name="stock_quantity" id="productStock" class="form-control" min="0" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">URL hình ảnh</label>
                                    <input type="url" name="image" id="productImage" class="form-control" placeholder="https://...">
                                </div>
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="featured" id="productFeatured">
                                        <label class="form-check-label" for="productFeatured">
                                            Sản phẩm nổi bật
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Mô tả sản phẩm</label>
                            <textarea name="product_description" id="productDescription" class="form-control" rows="4" 
                                      placeholder="Mô tả chi tiết về sản phẩm..."></textarea>
                        </div>
                        <button type="submit" class="btn btn-success" id="productSubmitBtn">
                            <i class="fas fa-plus me-2"></i>Thêm sản phẩm
                        </button>
                        <button type="button" class="btn btn-secondary ms-2" id="cancelProductEdit" style="display: none;" onclick="resetProductForm()">
                            <i class="fas fa-times me-2"></i>Hủy chỉnh sửa
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<script>
// Category Edit Functions
document.querySelectorAll('.editCategoryBtn').forEach(button => {
    button.addEventListener('click', function() {
        const id = this.dataset.id;
        const name = this.dataset.name;
        const description = this.dataset.description;
        
        document.getElementById('categoryFormTitle').textContent = 'Sửa danh mục';
        document.getElementById('categoryAction').value = 'edit_category';
        document.getElementById('categoryId').value = id;
        document.getElementById('categoryName').value = name;
        document.getElementById('categoryDescription').value = description;
        document.getElementById('categorySubmitBtn').innerHTML = '<i class="fas fa-save me-2"></i>Cập nhật danh mục';
        document.getElementById('cancelCategoryEdit').style.display = 'block';
        
        // Scroll to form
        document.getElementById('categoryForm').scrollIntoView({ behavior: 'smooth' });
    });
});

function resetCategoryForm() {
    document.getElementById('categoryFormTitle').textContent = 'Thêm danh mục mới';
    document.getElementById('categoryAction').value = 'add_category';
    document.getElementById('categoryId').value = '';
    document.getElementById('categoryName').value = '';
    document.getElementById('categoryDescription').value = '';
    document.getElementById('categorySubmitBtn').innerHTML = '<i class="fas fa-plus me-2"></i>Thêm danh mục';
    document.getElementById('cancelCategoryEdit').style.display = 'none';
}

// Product Edit Functions
document.querySelectorAll('.editProductBtn').forEach(button => {
    button.addEventListener('click', function() {
        const data = this.dataset;
        
        document.getElementById('productFormTitle').textContent = 'Sửa sản phẩm';
        document.getElementById('productAction').value = 'edit_product';
        document.getElementById('productId').value = data.id;
        
        // Fill form fields
        document.getElementById('productCategory').value = data.category;
        document.getElementById('productName').value = data.name;
        document.getElementById('productDescription').value = data.description;
        document.getElementById('productBrand').value = data.brand;
        document.getElementById('productPrice').value = data.price;
        document.getElementById('productSalePrice').value = data.salePrice || '';
        document.getElementById('productSku').value = data.sku;
        document.getElementById('productStock').value = data.stock;
        document.getElementById('productImage').value = data.image;
        document.getElementById('productFeatured').checked = data.featured == '1';
        
        document.getElementById('productSubmitBtn').innerHTML = '<i class="fas fa-save me-2"></i>Cập nhật sản phẩm';
        document.getElementById('cancelProductEdit').style.display = 'inline-block';
        
        // Switch to add product tab and scroll
        const addProductTab = new bootstrap.Tab(document.getElementById('add-product-tab'));
        addProductTab.show();
        
        setTimeout(() => {
            document.getElementById('productForm').scrollIntoView({ behavior: 'smooth' });
        }, 100);
    });
});

function resetProductForm() {
    document.getElementById('productFormTitle').textContent = 'Thêm sản phẩm mới';
    document.getElementById('productAction').value = 'add_product';
    document.getElementById('productId').value = '';
    
    // Reset all form fields
    document.getElementById('productForm').reset();
    
    document.getElementById('productSubmitBtn').innerHTML = '<i class="fas fa-plus me-2"></i>Thêm sản phẩm';
    document.getElementById('cancelProductEdit').style.display = 'none';
}

// Auto-hide alerts after 5 seconds
setTimeout(function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        if (alert.classList.contains('alert-success') || alert.classList.contains('alert-danger')) {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        }
    });
}, 5000);

// Confirm delete actions
document.querySelectorAll('form[onsubmit*="confirm"]').forEach(form => {
    form.addEventListener('submit', function(e) {
        const action = this.querySelector('input[name="action"]').value;
        let message = 'Bạn có chắc muốn thực hiện hành động này?';
        
        if (action === 'delete_product') {
            message = 'Bạn có chắc muốn xóa sản phẩm này?\nHành động này không thể hoàn tác!';
        } else if (action === 'delete_category') {
            message = 'Bạn có chắc muốn xóa danh mục này?\nLưu ý: Không thể xóa nếu còn sản phẩm trong danh mục!';
        }
        
        if (!confirm(message)) {
            e.preventDefault();
            return false;
        }
    });
});

// Add loading state to buttons
document.querySelectorAll('form').forEach(form => {
    form.addEventListener('submit', function() {
        const submitBtn = this.querySelector('button[type="submit"]');
        if (submitBtn && !submitBtn.disabled) {
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<div class="spinner-border spinner-border-sm me-2"></div>Đang xử lý...';
            submitBtn.disabled = true;
            
            // Re-enable after 3 seconds in case of error
            setTimeout(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }, 3000);
        }
    });
});

// Real-time validation
document.getElementById('productName')?.addEventListener('input', function() {
    const value = this.value.trim();
    if (value.length < 3) {
        this.setCustomValidity('Tên sản phẩm phải có ít nhất 3 ký tự');
    } else {
        this.setCustomValidity('');
    }
});

document.getElementById('productPrice')?.addEventListener('input', function() {
    const value = parseInt(this.value);
    if (value < 1000) {
        this.setCustomValidity('Giá sản phẩm phải từ 1,000đ trở lên');
    } else {
        this.setCustomValidity('');
    }
});

document.getElementById('productStock')?.addEventListener('input', function() {
    const value = parseInt(this.value);
    if (value < 0) {
        this.setCustomValidity('Số lượng tồn kho không thể âm');
    } else {
        this.setCustomValidity('');
    }
});

// Auto-generate SKU
document.getElementById('productName')?.addEventListener('blur', function() {
    const skuField = document.getElementById('productSku');
    if (skuField && !skuField.value.trim()) {
        const name = this.value.trim();
        if (name) {
            // Generate SKU from product name
            const sku = name
                .toUpperCase()
                .replace(/[^A-Z0-9\s]/g, '')
                .replace(/\s+/g, '')
                .substring(0, 10) + 
                Math.random().toString(36).substring(2, 5).toUpperCase();
            skuField.value = sku;
        }
    }
});

// Image preview
document.getElementById('productImage')?.addEventListener('input', function() {
    const url = this.value.trim();
    const preview = document.getElementById('imagePreview');
    
    if (!preview) {
        const previewDiv = document.createElement('div');
        previewDiv.id = 'imagePreview';
        previewDiv.className = 'mt-2';
        this.parentNode.appendChild(previewDiv);
    }
    
    if (url && (url.startsWith('http://') || url.startsWith('https://'))) {
        document.getElementById('imagePreview').innerHTML = 
            `<img src="${url}" alt="Preview" style="max-width: 100px; max-height: 100px; object-fit: cover; border-radius: 5px;" 
             onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
             <div style="display: none; color: #dc3545; font-size: 12px;">❌ Không thể tải ảnh</div>`;
    } else {
        document.getElementById('imagePreview').innerHTML = '';
    }
});
</script>