<?php
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();
$isAdminPage = true;

// Get payment statistics
global $mysqli;
$payment_stats = $mysqli->query('
    SELECT 
        payment_method,
        payment_status,
        COUNT(*) as count,
        SUM(total_price) as total
    FROM bookings
    WHERE payment_status IN ("paid", "pending", "unpaid", "failed")
    GROUP BY payment_method, payment_status
    ORDER BY payment_method, count DESC
')->fetch_all(MYSQLI_ASSOC);

// Get all bookings with payment info
$all_payments = $mysqli->query('
    SELECT 
        b.id,
        b.booking_date,
        b.start_time,
        b.total_price,
        b.payment_method,
        b.payment_status,
        b.status,
        u.name as user_name,
        u.email as user_email,
        c.name as court_name
    FROM bookings b
    JOIN users u ON b.user_id = u.id
    JOIN courts c ON b.court_id = c.id
    ORDER BY b.created_at DESC
    LIMIT 100
')->fetch_all(MYSQLI_ASSOC);

// Calculate totals
$total_revenue = 0;
$paid_revenue = 0;
$pending_revenue = 0;
foreach ($all_payments as $payment) {
    $total_revenue += $payment['total_price'];
    if ($payment['payment_status'] === 'paid') {
        $paid_revenue += $payment['total_price'];
    } elseif ($payment['payment_status'] === 'pending') {
        $pending_revenue += $payment['total_price'];
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-body d-flex flex-column flex-md-row justify-content-between align-items-start gap-3">
                <div>
                    <h2 class="card-title">💳 Quản lý thanh toán</h2>
                    <p class="text-muted mb-0">Theo dõi tất cả giao dịch và tình trạng thanh toán</p>
                </div>
                <a href="dashboard.php" class="btn btn-outline-secondary">← Quay lại Dashboard</a>
            </div>
        </div>
    </div>
</div>

<!-- Payment Statistics -->
<div class="row gy-3 mb-4">
    <div class="col-md-3">
        <div class="card shadow-sm border-start border-4 border-success">
            <div class="card-body">
                <h6 class="text-uppercase text-muted mb-1">
                    <i class="fas fa-check-circle"></i> Đã thanh toán
                </h6>
                <p class="display-6 mb-0" style="color: #198754;">
                    <?php echo number_format($paid_revenue); ?> ₫
                </p>
                <small class="text-muted">Doanh thu hoàn tất</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-start border-4 border-warning">
            <div class="card-body">
                <h6 class="text-uppercase text-muted mb-1">
                    <i class="fas fa-clock"></i> Đang chờ
                </h6>
                <p class="display-6 mb-0" style="color: #ffc107;">
                    <?php echo number_format($pending_revenue); ?> ₫
                </p>
                <small class="text-muted">Chờ xác nhận</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-start border-4 border-danger">
            <div class="card-body">
                <h6 class="text-uppercase text-muted mb-1">
                    <i class="fas fa-exclamation-triangle"></i> Chưa thanh toán
                </h6>
                <p class="display-6 mb-0" style="color: #dc3545;">
                    <?php echo number_format($total_revenue - $paid_revenue - $pending_revenue); ?> ₫
                </p>
                <small class="text-muted">Còn nợ khách</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-start border-4 border-primary">
            <div class="card-body">
                <h6 class="text-uppercase text-muted mb-1">
                    <i class="fas fa-chart-line"></i> Tổng cộng
                </h6>
                <p class="display-6 mb-0" style="color: #0d6efd;">
                    <?php echo number_format($total_revenue); ?> ₫
                </p>
                <small class="text-muted">Tất cả giao dịch</small>
            </div>
        </div>
    </div>
</div>

<!-- Payment Method Breakdown -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-body">
                <h5 class="card-title mb-3">📊 Phân tích theo phương thức thanh toán</h5>
                <div class="table-responsive">
                    <table class="table table-striped align-middle">
                        <thead>
                            <tr>
                                <th>Phương thức</th>
                                <th>Tình trạng</th>
                                <th>Số đơn</th>
                                <th>Doanh thu</th>
                                <th>Tỷ lệ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($payment_stats as $stat): 
                                $percentage = ($stat['total'] / $total_revenue * 100);
                                $method_display = [
                                    'cash' => '💵 Tiền mặt',
                                    'momo' => '🟣 MoMo',
                                    'vnpay' => '🔵 MB Bank'
                                ][$stat['payment_method']] ?? ucfirst($stat['payment_method']);
                                
                                $status_badge = [
                                    'paid' => '<span class="badge bg-success"><i class="fas fa-check"></i> Đã thanh toán</span>',
                                    'pending' => '<span class="badge bg-warning"><i class="fas fa-clock"></i> Chờ xử lý</span>',
                                    'unpaid' => '<span class="badge bg-danger"><i class="fas fa-times"></i> Chưa thanh toán</span>',
                                    'failed' => '<span class="badge bg-dark"><i class="fas fa-exclamation"></i> Thất bại</span>'
                                ][$stat['payment_status']] ?? '<span class="badge bg-secondary">' . ucfirst($stat['payment_status']) . '</span>';
                            ?>
                                <tr>
                                    <td><strong><?php echo $method_display; ?></strong></td>
                                    <td><?php echo $status_badge; ?></td>
                                    <td><?php echo $stat['count']; ?> đơn</td>
                                    <td><strong><?php echo number_format($stat['total']); ?> ₫</strong></td>
                                    <td>
                                        <div class="progress" style="height: 25px;">
                                            <div class="progress-bar" style="width: <?php echo $percentage; ?>%; background: linear-gradient(90deg, #0d6efd, #6610f2);">
                                                <?php echo number_format($percentage, 1); ?>%
                                            </div>
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

<!-- Recent Payments Table -->
<div class="row">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-body">
                <h5 class="card-title mb-3">📋 Giao dịch gần đây</h5>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Khách hàng</th>
                                <th>Sân</th>
                                <th>Ngày đặt</th>
                                <th>Giá</th>
                                <th>Phương thức</th>
                                <th>Tình trạng thanh toán</th>
                                <th>Trạng thái đơn</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($all_payments as $payment): 
                                $payment_badge_color = [
                                    'paid' => 'success',
                                    'pending' => 'warning',
                                    'unpaid' => 'danger',
                                    'failed' => 'dark'
                                ][$payment['payment_status']] ?? 'secondary';
                                
                                $method_icon = [
                                    'cash' => '💵',
                                    'momo' => '🟣',
                                    'vnpay' => '🔵'
                                ][$payment['payment_method']] ?? '💳';
                            ?>
                                <tr>
                                    <td><strong>#<?php echo $payment['id']; ?></strong></td>
                                    <td>
                                        <strong><?php echo escape($payment['user_name']); ?></strong><br>
                                        <small class="text-muted"><?php echo escape($payment['user_email']); ?></small>
                                    </td>
                                    <td><?php echo escape($payment['court_name']); ?></td>
                                    <td><?php echo escape($payment['booking_date']); ?></td>
                                    <td><strong><?php echo number_format($payment['total_price']); ?> ₫</strong></td>
                                    <td><?php echo $method_icon; ?> <?php echo ucfirst(escape($payment['payment_method'])); ?></td>
                                    <td>
                                        <?php 
                                        $status_text = [
                                            'paid' => '✓ Đã thanh toán',
                                            'pending' => '⏳ Chờ xử lý',
                                            'unpaid' => '❌ Chưa thanh toán',
                                            'failed' => '✗ Thất bại'
                                        ][$payment['payment_status']] ?? ucfirst($payment['payment_status']);
                                        ?>
                                        <span class="badge bg-<?php echo $payment_badge_color; ?>">
                                            <?php echo $status_text; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo $payment['status'] === 'confirmed' ? 'success' : ($payment['status'] === 'cancelled' ? 'danger' : 'warning'); ?>">
                                            <?php echo ucfirst(escape($payment['status'])); ?>
                                        </span>
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

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// You can add payment method pie chart here later
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
