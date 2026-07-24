<?php
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$isAdminPage = true;
$counts = getBookingCounts();
$courtCount = getCourtCount();
$recentBookings = getRecentBookings(5);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid mt-4">
    <!-- Welcome Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-lg" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <div class="card-body text-white py-5">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h1 class="display-5 fw-bold mb-3">
                                <i class="fas fa-tachometer-alt me-3"></i>Admin Dashboard
                            </h1>
                            <p class="lead mb-0 opacity-90">
                                Xin chào, <strong><?php echo htmlspecialchars($_SESSION['name']); ?></strong>! 
                                Chào mừng bạn đến với bảng điều khiển quản trị.
                            </p>
                            <small class="opacity-75">
                                <i class="fas fa-shield-alt me-1"></i>
                                Tài khoản admin chỉ có thể truy cập khu vực quản trị
                            </small>
                        </div>
                        <div class="col-md-4 text-end">
                            <div class="d-none d-md-block">
                                <i class="fas fa-chart-line" style="font-size: 4rem; opacity: 0.3;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row g-4 mb-5">
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm h-100 hover-lift">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-warning bg-opacity-10 rounded-3 p-3">
                                <i class="fas fa-hourglass-half text-warning fa-2x"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="text-muted small text-uppercase fw-bold">Đơn chờ xử lý</div>
                            <div class="h2 mb-0 fw-bold text-warning"><?php echo $counts['pending'] ?? 0; ?></div>
                            <div class="small text-muted">
                                <i class="fas fa-arrow-up text-success me-1"></i>Cần xử lý
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm h-100 hover-lift">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-success bg-opacity-10 rounded-3 p-3">
                                <i class="fas fa-check-circle text-success fa-2x"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="text-muted small text-uppercase fw-bold">Đơn xác nhận</div>
                            <div class="h2 mb-0 fw-bold text-success"><?php echo $counts['confirmed'] ?? 0; ?></div>
                            <div class="small text-muted">
                                <i class="fas fa-check text-success me-1"></i>Đã xác nhận
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm h-100 hover-lift">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-danger bg-opacity-10 rounded-3 p-3">
                                <i class="fas fa-times-circle text-danger fa-2x"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="text-muted small text-uppercase fw-bold">Đơn hủy</div>
                            <div class="h2 mb-0 fw-bold text-danger"><?php echo $counts['cancelled'] ?? 0; ?></div>
                            <div class="small text-muted">
                                <i class="fas fa-times text-danger me-1"></i>Đã hủy
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm h-100 hover-lift">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-primary bg-opacity-10 rounded-3 p-3">
                                <i class="fas fa-badminton text-primary fa-2x"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="text-muted small text-uppercase fw-bold">Tổng số sân</div>
                            <div class="h2 mb-0 fw-bold text-primary"><?php echo $courtCount; ?></div>
                            <div class="small text-muted">
                                <i class="fas fa-map-marker-alt text-primary me-1"></i>Đang hoạt động
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row mb-5">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-0 py-3">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-bolt text-primary me-2"></i>Thao tác nhanh
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-lg-2 col-md-4 col-6">
                            <a href="courts.php" class="btn btn-outline-primary w-100 py-3 hover-lift">
                                <i class="fas fa-badminton fa-2x mb-2 d-block"></i>
                                <small class="fw-bold">Quản lý sân</small>
                            </a>
                        </div>
                        <div class="col-lg-2 col-md-4 col-6">
                            <a href="bookings.php" class="btn btn-outline-success w-100 py-3 hover-lift">
                                <i class="fas fa-calendar-check fa-2x mb-2 d-block"></i>
                                <small class="fw-bold">Đặt sân</small>
                            </a>
                        </div>
                        <div class="col-lg-2 col-md-4 col-6">
                            <a href="shop-overview.php" class="btn btn-outline-warning w-100 py-3 hover-lift">
                                <i class="fas fa-chart-line fa-2x mb-2 d-block"></i>
                                <small class="fw-bold">Tổng quan Shop</small>
                            </a>
                        </div>
                        <div class="col-lg-2 col-md-4 col-6">
                            <a href="reviews.php" class="btn btn-outline-info w-100 py-3 hover-lift">
                                <i class="fas fa-star fa-2x mb-2 d-block"></i>
                                <small class="fw-bold">Đánh giá</small>
                            </a>
                        </div>
                        <div class="col-lg-2 col-md-4 col-6">
                            <a href="payments.php" class="btn btn-outline-secondary w-100 py-3 hover-lift">
                                <i class="fas fa-credit-card fa-2x mb-2 d-block"></i>
                                <small class="fw-bold">Thanh toán</small>
                            </a>
                        </div>
                        <div class="col-lg-2 col-md-4 col-6">
                            <a href="memberships.php" class="btn btn-outline-success w-100 py-3 hover-lift" style="border-color:#10b981;color:#10b981;">
                                <i class="fas fa-id-card fa-2x mb-2 d-block"></i>
                                <small class="fw-bold">Hội viên</small>
                            </a>
                        </div>
                        <div class="col-lg-2 col-md-4 col-6">
                            <a href="memberships.php?tab=plans" class="btn btn-outline-success w-100 py-3 hover-lift" style="border-color:#6366f1;color:#6366f1;">
                                <i class="fas fa-tags fa-2x mb-2 d-block"></i>
                                <small class="fw-bold">Gói hội viên</small>
                            </a>
                        </div>
                        <div class="col-lg-2 col-md-4 col-6">
                            <a href="logout.php" class="btn btn-outline-danger w-100 py-3 hover-lift">
                                <i class="fas fa-sign-out-alt fa-2x mb-2 d-block"></i>
                                <small class="fw-bold">Đăng xuất</small>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Bookings -->
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-0 py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-history text-primary me-2"></i>Booking gần đây
                        </h5>
                        <a href="bookings.php" class="btn btn-sm btn-outline-primary">
                            Xem tất cả <i class="fas fa-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (empty($recentBookings)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                            <h6 class="text-muted">Chưa có booking nào</h6>
                            <p class="text-muted small">Các booking mới sẽ hiển thị ở đây</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="border-0 fw-bold">#</th>
                                        <th class="border-0 fw-bold">Người đặt</th>
                                        <th class="border-0 fw-bold">Sân</th>
                                        <th class="border-0 fw-bold">Ngày</th>
                                        <th class="border-0 fw-bold">Giờ</th>
                                        <th class="border-0 fw-bold">Trạng thái</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentBookings as $booking): ?>
                                        <tr>
                                            <td class="fw-bold text-primary">#<?php echo $booking['id']; ?></td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="bg-primary bg-opacity-10 rounded-circle p-2 me-2">
                                                        <i class="fas fa-user text-primary"></i>
                                                    </div>
                                                    <span class="fw-medium"><?php echo htmlspecialchars($booking['user_name']); ?></span>
                                                </div>
                                            </td>
                                            <td><?php echo htmlspecialchars($booking['court_name']); ?></td>
                                            <td>
                                                <span class="badge bg-light text-dark">
                                                    <?php echo date('d/m/Y', strtotime($booking['booking_date'])); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    <?php echo substr($booking['start_time'], 0, 5); ?> - 
                                                    <?php echo substr($booking['end_time'], 0, 5); ?>
                                                </small>
                                            </td>
                                            <td>
                                                <?php
                                                $status_colors = [
                                                    'confirmed' => 'success',
                                                    'pending' => 'warning',
                                                    'cancelled' => 'danger'
                                                ];
                                                $status_labels = [
                                                    'confirmed' => 'Đã xác nhận',
                                                    'pending' => 'Chờ xử lý',
                                                    'cancelled' => 'Đã hủy'
                                                ];
                                                $color = $status_colors[$booking['status']] ?? 'secondary';
                                                $label = $status_labels[$booking['status']] ?? ucfirst($booking['status']);
                                                ?>
                                                <span class="badge bg-<?php echo $color; ?>">
                                                    <?php echo $label; ?>
                                                </span>
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
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>