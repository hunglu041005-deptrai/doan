<?php
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();
$isAdminPage = true;

// Tạo bảng nếu chưa có
$mysqli->query("CREATE TABLE IF NOT EXISTS payment_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_id VARCHAR(100),
    gateway VARCHAR(50),
    amount INT,
    description TEXT,
    order_type VARCHAR(30),
    order_id VARCHAR(50),
    status VARCHAR(20) DEFAULT 'processed',
    raw_data TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$transactions = $mysqli->query(
    "SELECT * FROM payment_transactions ORDER BY created_at DESC LIMIT 100"
)->fetch_all(MYSQLI_ASSOC);

$totalAmount  = array_sum(array_column(array_filter($transactions, fn($t)=>$t['status']==='processed'), 'amount'));

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid mt-4">
<div class="row mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-lg" style="background:linear-gradient(135deg,#059669,#10b981);">
            <div class="card-body text-white py-4">
                <h1 class="fw-bold mb-1"><i class="fas fa-exchange-alt me-3"></i>Lịch sử giao dịch thanh toán</h1>
                <p class="mb-0 opacity-75">Giao dịch nhận qua webhook SePay — tự động xác nhận đơn hàng.</p>
            </div>
        </div>
    </div>
</div>

<!-- Hướng dẫn cài đặt SePay -->
<div class="card border-0 shadow-sm mb-4" style="border-left:4px solid #f59e0b!important;">
    <div class="card-body">
        <h6 class="fw-bold"><i class="fas fa-cog text-warning me-2"></i>Cài đặt SePay Webhook</h6>
        <ol class="mb-0 small text-muted">
            <li>Đăng ký tại <a href="https://sepay.vn" target="_blank">sepay.vn</a> (miễn phí)</li>
            <li>Vào <strong>Cài đặt → Webhook</strong> → Thêm URL: <code><?php echo (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST']; ?>/badminton_booking/api/payment-webhook.php</code></li>
            <li>Sao chép <strong>Webhook Secret</strong> từ SePay → điền vào <code>api/payment-webhook.php</code> dòng <code>SEPAY_WEBHOOK_SECRET</code></li>
            <li>Kết nối tài khoản MB Bank <strong>7369786789</strong></li>
            <li>Test: Chuyển khoản với nội dung <code>BK5</code> → đơn booking #5 sẽ tự xác nhận</li>
        </ol>
    </div>
</div>

<!-- Stats -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center">
                <div class="bg-success bg-opacity-10 rounded-3 p-3 me-3"><i class="fas fa-check-circle text-success fa-2x"></i></div>
                <div><div class="text-muted small fw-bold text-uppercase">Giao dịch thành công</div>
                <div class="h2 fw-bold mb-0 text-success"><?php echo count(array_filter($transactions, fn($t)=>$t['status']==='processed')); ?></div></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center">
                <div class="bg-primary bg-opacity-10 rounded-3 p-3 me-3"><i class="fas fa-coins text-primary fa-2x"></i></div>
                <div><div class="text-muted small fw-bold text-uppercase">Tổng đã nhận</div>
                <div class="h2 fw-bold mb-0 text-primary" style="font-size:1.3rem;"><?php echo number_format($totalAmount); ?>đ</div></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center">
                <div class="bg-danger bg-opacity-10 rounded-3 p-3 me-3"><i class="fas fa-times-circle text-danger fa-2x"></i></div>
                <div><div class="text-muted small fw-bold text-uppercase">Thất bại</div>
                <div class="h2 fw-bold mb-0 text-danger"><?php echo count(array_filter($transactions, fn($t)=>$t['status']==='failed')); ?></div></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center">
                <div class="bg-info bg-opacity-10 rounded-3 p-3 me-3"><i class="fas fa-list text-info fa-2x"></i></div>
                <div><div class="text-muted small fw-bold text-uppercase">Tổng giao dịch</div>
                <div class="h2 fw-bold mb-0 text-info"><?php echo count($transactions); ?></div></div>
            </div>
        </div>
    </div>
</div>

<!-- Bảng giao dịch -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom py-3">
        <h6 class="mb-0 fw-bold"><i class="fas fa-list text-primary me-2"></i>Giao dịch gần nhất (100)</h6>
    </div>
    <div class="card-body p-0">
        <?php if (empty($transactions)): ?>
        <div class="text-center py-5 text-muted">
            <i class="fas fa-inbox fa-3x mb-3 d-block opacity-25"></i>
            Chưa có giao dịch nào. Webhook chưa được kích hoạt.
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4 small text-muted fw-bold text-uppercase">Thời gian</th>
                        <th class="small text-muted fw-bold text-uppercase">Giao dịch</th>
                        <th class="small text-muted fw-bold text-uppercase">Số tiền</th>
                        <th class="small text-muted fw-bold text-uppercase">Loại đơn</th>
                        <th class="small text-muted fw-bold text-uppercase">Mã đơn</th>
                        <th class="small text-muted fw-bold text-uppercase">Nội dung CK</th>
                        <th class="small text-muted fw-bold text-uppercase">Trạng thái</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($transactions as $t):
                    $typeLabels = ['booking'=>'🏸 Đặt sân','order'=>'🛒 Shop','training'=>'📚 Khóa học','membership'=>'🎫 Hội viên'];
                    $typeLabel  = $typeLabels[$t['order_type']] ?? $t['order_type'];
                ?>
                <tr>
                    <td class="ps-4"><small class="text-muted"><?php echo date('d/m/Y H:i', strtotime($t['created_at'])); ?></small></td>
                    <td><small class="font-monospace text-muted"><?php echo escape(substr($t['transaction_id'],0,20)); ?></small></td>
                    <td><strong class="text-success"><?php echo number_format($t['amount']); ?>đ</strong></td>
                    <td><span class="badge bg-light text-dark"><?php echo $typeLabel; ?></span></td>
                    <td><code class="text-primary"><?php echo escape($t['order_id']); ?></code></td>
                    <td><small class="text-muted"><?php echo escape(substr($t['description'],0,40)); ?><?php if(strlen($t['description'])>40) echo '...'; ?></small></td>
                    <td>
                        <?php if ($t['status'] === 'processed'): ?>
                            <span class="badge bg-success rounded-pill">✅ Thành công</span>
                        <?php else: ?>
                            <span class="badge bg-danger rounded-pill">❌ Thất bại</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
