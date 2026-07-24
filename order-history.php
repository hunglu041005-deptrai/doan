<?php
require_once __DIR__ . '/includes/functions.php';

// Chặn admin truy cập trang web thường
blockAdminFromPublic();

// Kiểm tra đăng nhập
if (!isLoggedIn()) {
    header('Location: login.php?redirect=order-history.php');
    exit;
}

// Lấy danh sách đơn hàng của user
$orders = [];
try {
    $stmt = $mysqli->prepare('
        SELECT o.*, COUNT(oi.id) as item_count
        FROM orders o
        LEFT JOIN order_items oi ON o.id = oi.order_id
        WHERE o.user_id = ?
        GROUP BY o.id
        ORDER BY o.created_at DESC
    ');
    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $orders = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} catch (Exception $e) {
    // Bảng chưa tồn tại
    $orders = [];
}

// Lấy chi tiết đơn hàng nếu có yêu cầu
$order_detail = null;
if (isset($_GET['order_id'])) {
    $order_id = intval($_GET['order_id']);
    try {
        // Lấy thông tin đơn hàng
        $stmt = $mysqli->prepare('SELECT * FROM orders WHERE id = ? AND user_id = ?');
        $stmt->bind_param('ii', $order_id, $_SESSION['user_id']);
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

require_once __DIR__ . '/includes/header.php';
?>

<!-- Order History Page -->
<section class="bg-primary text-white py-3">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1 class="h4 mb-0">
                    <i class="fas fa-history me-2"></i>Lịch sử đơn hàng
                </h1>
            </div>
            <div class="col-md-4 text-md-end">
                <a href="equipment.php" class="btn btn-light btn-sm">
                    <i class="fas fa-shopping-bag me-2"></i>Tiếp tục mua sắm
                </a>
            </div>
        </div>
    </div>
</section>

<section class="py-4">
    <div class="container">
        <?php if ($order_detail): ?>
            <!-- Chi tiết đơn hàng -->
            <div class="row">
                <div class="col-12">
                    <div class="d-flex align-items-center mb-4">
                        <a href="order-history.php" class="btn btn-outline-secondary me-3">
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
                                        <i class="fas fa-box me-2"></i>Sản phẩm đã đặt
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <?php foreach ($order_detail['items'] as $item): ?>
                                        <div class="d-flex align-items-center py-3 <?php echo $item !== end($order_detail['items']) ? 'border-bottom' : ''; ?>">
                                            <div class="flex-grow-1">
                                                <h6 class="mb-1"><?php echo htmlspecialchars($item['product_name']); ?></h6>
                                                <div class="d-flex justify-content-between">
                                                    <span class="text-muted">Số lượng: <?php echo $item['quantity']; ?></span>
                                                    <span class="text-muted">Đơn giá: <?php echo number_format($item['product_price']); ?>đ</span>
                                                </div>
                                            </div>
                                            <div class="text-end">
                                                <strong><?php echo number_format($item['subtotal']); ?>đ</strong>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                    
                                    <div class="d-flex justify-content-between mt-3 pt-3 border-top">
                                        <strong>Tổng cộng:</strong>
                                        <strong class="text-danger"><?php echo number_format($order_detail['total_amount']); ?>đ</strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Thông tin giao hàng -->
                        <div class="col-lg-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">
                                        <i class="fas fa-info-circle me-2"></i>Thông tin đơn hàng
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <strong>Trạng thái:</strong>
                                        <span class="badge bg-<?php 
                                            echo $order_detail['status'] === 'pending' ? 'warning' : 
                                                ($order_detail['status'] === 'confirmed' ? 'info' : 
                                                ($order_detail['status'] === 'shipping' ? 'primary' : 
                                                ($order_detail['status'] === 'delivered' ? 'success' : 'secondary')));
                                        ?> ms-2">
                                            <?php 
                                            $status_text = [
                                                'pending' => 'Đang xử lý',
                                                'confirmed' => 'Đã xác nhận',
                                                'shipping' => 'Đang giao',
                                                'delivered' => 'Đã giao',
                                                'cancelled' => 'Đã hủy'
                                            ];
                                            echo $status_text[$order_detail['status']] ?? $order_detail['status'];
                                            ?>
                                        </span>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <strong>Ngày đặt:</strong><br>
                                        <span class="text-muted"><?php echo date('d/m/Y H:i', strtotime($order_detail['created_at'])); ?></span>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <strong>Thanh toán:</strong><br>
                                        <span class="text-muted">Thanh toán khi nhận hàng (COD)</span>
                                    </div>
                                    
                                    <hr>
                                    
                                    <div class="mb-3">
                                        <strong>Người nhận:</strong><br>
                                        <span class="text-muted"><?php echo htmlspecialchars($order_detail['customer_name'] ?? $order_detail['shipping_name'] ?? ''); ?></span>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <strong>Số điện thoại:</strong><br>
                                        <span class="text-muted"><?php echo htmlspecialchars($order_detail['customer_phone'] ?? $order_detail['shipping_phone'] ?? ''); ?></span>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <strong>Địa chỉ:</strong><br>
                                        <span class="text-muted"><?php echo nl2br(htmlspecialchars($order_detail['shipping_address'] ?? '')); ?></span>
                                    </div>
                                    
                                    <?php
                                    $orderNote = $order_detail['notes'] ?? $order_detail['order_note'] ?? '';
                                    if ($orderNote): ?>
                                        <div class="mb-3">
                                            <strong>Ghi chú:</strong><br>
                                            <span class="text-muted"><?php echo nl2br(htmlspecialchars($orderNote)); ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- Actions -->
                            <?php if ($order_detail['status'] === 'pending'): ?>
                                <div class="card mt-3">
                                    <div class="card-body text-center">
                                        <p class="text-muted small mb-3">Bạn có thể hủy đơn hàng trong vòng 30 phút sau khi đặt</p>
                                        <button class="btn btn-outline-danger btn-sm" onclick="cancelOrder(<?php echo $order_detail['id']; ?>)">
                                            <i class="fas fa-times me-2"></i>Hủy đơn hàng
                                        </button>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Danh sách đơn hàng -->
            <div class="row">
                <div class="col-12">
                    <?php if (empty($orders)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-shopping-bag fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">Bạn chưa có đơn hàng nào</h5>
                            <p class="text-muted">Hãy khám phá các sản phẩm tuyệt vời của chúng tôi!</p>
                            <a href="equipment.php" class="btn btn-primary">
                                <i class="fas fa-shopping-cart me-2"></i>Mua sắm ngay
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="row g-3">
                            <?php foreach ($orders as $order): ?>
                                <div class="col-12">
                                    <div class="card">
                                        <div class="card-body">
                                            <div class="row align-items-center">
                                                <div class="col-md-3">
                                                    <h6 class="mb-1">Đơn hàng #<?php echo htmlspecialchars($order['order_number']); ?></h6>
                                                    <small class="text-muted"><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></small>
                                                </div>
                                                <div class="col-md-2">
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
                                                </div>
                                                <div class="col-md-2">
                                                    <small class="text-muted"><?php echo $order['item_count']; ?> sản phẩm</small>
                                                </div>
                                                <div class="col-md-3">
                                                    <strong class="text-danger"><?php echo number_format($order['total_amount']); ?>đ</strong>
                                                </div>
                                                <div class="col-md-2 text-end">
                                                    <a href="order-history.php?order_id=<?php echo $order['id']; ?>" class="btn btn-outline-primary btn-sm">
                                                        <i class="fas fa-eye me-1"></i>Xem
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

<script>
function cancelOrder(orderId) {
    if (confirm('Bạn có chắc muốn hủy đơn hàng này?')) {
        // Implement cancel order functionality
        fetch('cancel-order.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                order_id: orderId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Đơn hàng đã được hủy thành công!');
                location.reload();
            } else {
                alert(data.message || 'Có lỗi xảy ra. Vui lòng thử lại.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Có lỗi xảy ra. Vui lòng thử lại.');
        });
    }
}
</script>

<style>
.card {
    border-radius: 10px;
    border: none;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
}

.card:hover {
    box-shadow: 0 4px 15px rgba(0,0,0,0.15);
}

.card-header {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-radius: 10px 10px 0 0;
    border-bottom: 1px solid #dee2e6;
}

.badge {
    font-size: 0.75rem;
    padding: 0.375rem 0.75rem;
}

.btn-outline-primary:hover {
    transform: translateY(-1px);
}

@media (max-width: 768px) {
    .card-body .row > div {
        margin-bottom: 0.5rem;
    }
    
    .text-end {
        text-align: left !important;
    }
}
</style>