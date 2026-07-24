<?php
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();
$isAdminPage = true;

// Xử lý cập nhật trạng thái đơn hàng
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $order_id = intval($_POST['order_id'] ?? 0);
    $new_status = $_POST['status'] ?? '';
    
    if ($order_id && in_array($new_status, ['pending', 'confirmed', 'shipping', 'delivered', 'cancelled'])) {
        try {
            $stmt = $mysqli->prepare('UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?');
            $stmt->bind_param('si', $new_status, $order_id);
            if ($stmt->execute()) {
                $success_message = 'Cập nhật trạng thái đơn hàng thành công!';
            }
            $stmt->close();
        } catch (Exception $e) {
            $error_message = 'Có lỗi xảy ra: ' . $e->getMessage();
        }
    }
}

// Lấy danh sách đơn hàng
$orders = [];
$stats = ['total' => 0, 'pending' => 0, 'confirmed' => 0, 'shipping' => 0, 'delivered' => 0, 'cancelled' => 0];

try {
    // Lấy thống kê
    $stats_query = $mysqli->query('
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = "confirmed" THEN 1 ELSE 0 END) as confirmed,
            SUM(CASE WHEN status = "shipping" THEN 1 ELSE 0 END) as shipping,
            SUM(CASE WHEN status = "delivered" THEN 1 ELSE 0 END) as delivered,
            SUM(CASE WHEN status = "cancelled" THEN 1 ELSE 0 END) as cancelled
        FROM orders
    ');
    if ($stats_query) {
        $stats = $stats_query->fetch_assoc();
    }
    
    // Lấy danh sách đơn hàng
    $orders_query = $mysqli->query('
        SELECT o.*, u.name as user_name, u.email as user_email,
               COUNT(oi.id) as item_count,
               SUM(oi.quantity) as total_quantity
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.id
        LEFT JOIN order_items oi ON o.id = oi.order_id
        GROUP BY o.id
        ORDER BY o.created_at DESC
        LIMIT 50
    ');
    if ($orders_query) {
        $orders = $orders_query->fetch_all(MYSQLI_ASSOC);
    }
} catch (Exception $e) {
    $orders = [];
}

// Lấy chi tiết đơn hàng nếu có yêu cầu
$order_detail = null;
if (isset($_GET['order_id'])) {
    $order_id = intval($_GET['order_id']);
    try {
        // Lấy thông tin đơn hàng
        $stmt = $mysqli->prepare('
            SELECT o.*, u.name as user_name, u.email as user_email
            FROM orders o
            LEFT JOIN users u ON o.user_id = u.id
            WHERE o.id = ?
        ');
        $stmt->bind_param('i', $order_id);
        $stmt->execute();
        $order_detail = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if ($order_detail) {
            // Lấy items của đơn hàng
            $stmt = $mysqli->prepare('SELECT * FROM order_items WHERE order_id = ?');
            $stmt->bind_param('i', $order_id);
            $stmt->execute();
            $order_detail['items'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
        }
    } catch (Exception $e) {
        $order_detail = null;
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container mt-4">
    <?php if (isset($success_message)): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?php echo htmlspecialchars($success_message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?php echo htmlspecialchars($error_message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($order_detail): ?>
        <!-- Chi tiết đơn hàng -->
        <div class="row">
            <div class="col-12">
                <div class="d-flex align-items-center mb-4">
                    <a href="shop-orders.php" class="btn btn-outline-secondary me-3">
                        <i class="fas fa-arrow-left me-2"></i>Quay lại
                    </a>
                    <h4 class="mb-0">Chi tiết đơn hàng #<?php echo htmlspecialchars($order_detail['order_number']); ?></h4>
                </div>
                
                <div class="row g-4">
                    <!-- Thông tin đơn hàng -->
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-box me-2"></i>Sản phẩm trong đơn hàng
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Sản phẩm</th>
                                                <th>Đơn giá</th>
                                                <th>Số lượng</th>
                                                <th>Thành tiền</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($order_detail['items'] as $item): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                                                    <td><?php echo number_format($item['product_price']); ?>đ</td>
                                                    <td><?php echo $item['quantity']; ?></td>
                                                    <td><strong><?php echo number_format($item['subtotal']); ?>đ</strong></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                        <tfoot>
                                            <tr class="table-active">
                                                <th colspan="3">Tổng cộng:</th>
                                                <th class="text-danger"><?php echo number_format($order_detail['total_amount']); ?>đ</th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Thông tin khách hàng và trạng thái -->
                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-user me-2"></i>Thông tin khách hàng
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <strong>Khách hàng:</strong><br>
                                    <span><?php echo htmlspecialchars($order_detail['user_name']); ?></span><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($order_detail['user_email']); ?></small>
                                </div>
                                
                                <div class="mb-3">
                                    <strong>Người nhận:</strong><br>
                                    <span><?php echo htmlspecialchars($order_detail['shipping_name']); ?></span>
                                </div>
                                
                                <div class="mb-3">
                                    <strong>Số điện thoại:</strong><br>
                                    <span><?php echo htmlspecialchars($order_detail['shipping_phone']); ?></span>
                                </div>
                                
                                <div class="mb-3">
                                    <strong>Địa chỉ giao hàng:</strong><br>
                                    <span><?php echo nl2br(htmlspecialchars($order_detail['shipping_address'])); ?></span>
                                </div>
                                
                                <?php if ($order_detail['order_note']): ?>
                                    <div class="mb-3">
                                        <strong>Ghi chú:</strong><br>
                                        <span class="text-muted"><?php echo nl2br(htmlspecialchars($order_detail['order_note'])); ?></span>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="mb-3">
                                    <strong>Ngày đặt:</strong><br>
                                    <span><?php echo date('d/m/Y H:i', strtotime($order_detail['created_at'])); ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Cập nhật trạng thái -->
                        <div class="card mt-3">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-edit me-2"></i>Cập nhật trạng thái
                                </h5>
                            </div>
                            <div class="card-body">
                                <form method="post">
                                    <input type="hidden" name="order_id" value="<?php echo $order_detail['id']; ?>">
                                    <div class="mb-3">
                                        <label class="form-label">Trạng thái hiện tại:</label>
                                        <select name="status" class="form-select">
                                            <option value="pending" <?php echo $order_detail['status'] === 'pending' ? 'selected' : ''; ?>>Đang xử lý</option>
                                            <option value="confirmed" <?php echo $order_detail['status'] === 'confirmed' ? 'selected' : ''; ?>>Đã xác nhận</option>
                                            <option value="shipping" <?php echo $order_detail['status'] === 'shipping' ? 'selected' : ''; ?>>Đang giao hàng</option>
                                            <option value="delivered" <?php echo $order_detail['status'] === 'delivered' ? 'selected' : ''; ?>>Đã giao hàng</option>
                                            <option value="cancelled" <?php echo $order_detail['status'] === 'cancelled' ? 'selected' : ''; ?>>Đã hủy</option>
                                        </select>
                                    </div>
                                    <button type="submit" name="update_status" class="btn btn-primary w-100">
                                        <i class="fas fa-save me-2"></i>Cập nhật
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <!-- Danh sách đơn hàng -->
        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm mb-4">
                    <div class="card-body d-flex flex-column flex-md-row justify-content-between align-items-start gap-3">
                        <div>
                            <h2 class="card-title">📦 Quản lý đơn hàng</h2>
                            <p class="text-muted mb-0">Quản lý tất cả đơn hàng từ khách hàng</p>
                        </div>
                        <div>
                            <a href="dashboard.php" class="btn btn-outline-secondary me-2">← Dashboard</a>
                            <a href="shop.php" class="btn btn-primary">🛍️ Quản lý sản phẩm</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Thống kê -->
        <div class="row gy-3 mb-4">
            <div class="col-md-2">
                <div class="card shadow-sm border-start border-4 border-primary">
                    <div class="card-body">
                        <h6 class="text-uppercase text-muted mb-1">
                            <i class="fas fa-shopping-cart"></i> Tổng đơn
                        </h6>
                        <p class="display-6 mb-0"><?php echo $stats['total']; ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card shadow-sm border-start border-4 border-warning">
                    <div class="card-body">
                        <h6 class="text-uppercase text-muted mb-1">
                            <i class="fas fa-clock"></i> Chờ xử lý
                        </h6>
                        <p class="display-6 mb-0"><?php echo $stats['pending']; ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card shadow-sm border-start border-4 border-info">
                    <div class="card-body">
                        <h6 class="text-uppercase text-muted mb-1">
                            <i class="fas fa-check"></i> Đã xác nhận
                        </h6>
                        <p class="display-6 mb-0"><?php echo $stats['confirmed']; ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card shadow-sm border-start border-4 border-primary">
                    <div class="card-body">
                        <h6 class="text-uppercase text-muted mb-1">
                            <i class="fas fa-truck"></i> Đang giao
                        </h6>
                        <p class="display-6 mb-0"><?php echo $stats['shipping']; ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card shadow-sm border-start border-4 border-success">
                    <div class="card-body">
                        <h6 class="text-uppercase text-muted mb-1">
                            <i class="fas fa-check-circle"></i> Hoàn thành
                        </h6>
                        <p class="display-6 mb-0"><?php echo $stats['delivered']; ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card shadow-sm border-start border-4 border-danger">
                    <div class="card-body">
                        <h6 class="text-uppercase text-muted mb-1">
                            <i class="fas fa-times"></i> Đã hủy
                        </h6>
                        <p class="display-6 mb-0"><?php echo $stats['cancelled']; ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Danh sách đơn hàng -->
        <div class="card shadow-sm">
            <div class="card-body">
                <h5 class="card-title mb-3">Danh sách đơn hàng</h5>
                <?php if (empty($orders)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">Chưa có đơn hàng nào</h5>
                        <p class="text-muted">Đơn hàng sẽ hiển thị ở đây khi khách hàng đặt mua.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Mã đơn hàng</th>
                                    <th>Khách hàng</th>
                                    <th>Sản phẩm</th>
                                    <th>Tổng tiền</th>
                                    <th>Trạng thái</th>
                                    <th>Ngày đặt</th>
                                    <th>Hành động</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($order['order_number']); ?></strong>
                                        </td>
                                        <td>
                                            <div>
                                                <strong><?php echo htmlspecialchars($order['user_name']); ?></strong><br>
                                                <small class="text-muted"><?php echo htmlspecialchars($order['user_email']); ?></small>
                                            </div>
                                        </td>
                                        <td>
                                            <small class="text-muted"><?php echo $order['item_count']; ?> loại (<?php echo $order['total_quantity']; ?> sản phẩm)</small>
                                        </td>
                                        <td>
                                            <strong class="text-danger"><?php echo number_format($order['total_amount']); ?>đ</strong>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $order['status'] === 'pending' ? 'warning' : 
                                                    ($order['status'] === 'confirmed' ? 'info' : 
                                                    ($order['status'] === 'shipping' ? 'primary' : 
                                                    ($order['status'] === 'delivered' ? 'success' : 'secondary')));
                                            ?>">
                                                <?php 
                                                $status_text = [
                                                    'pending' => 'Đang xử lý',
                                                    'confirmed' => 'Đã xác nhận',
                                                    'shipping' => 'Đang giao',
                                                    'delivered' => 'Đã giao',
                                                    'cancelled' => 'Đã hủy'
                                                ];
                                                echo $status_text[$order['status']] ?? $order['status'];
                                                ?>
                                            </span>
                                        </td>
                                        <td>
                                            <small><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></small>
                                        </td>
                                        <td>
                                            <a href="shop-orders.php?order_id=<?php echo $order['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-eye me-1"></i>Xem
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<script>
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
</script>