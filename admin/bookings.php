<?php
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();
$isAdminPage = true;

$message = '';
$messageType = 'success';

// Xử lý cập nhật trạng thái (AJAX hoặc form POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['booking_id'], $_POST['status'])) {
        $booking_id = intval($_POST['booking_id']);
        $status = $_POST['status'];
        if (in_array($status, ['pending', 'confirmed', 'cancelled'], true)) {
            $stmt = $mysqli->prepare('UPDATE bookings SET status = ? WHERE id = ?');
            $stmt->bind_param('si', $status, $booking_id);
            $stmt->execute();
            $stmt->close();
            if (!empty($_POST['ajax'])) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true]);
                exit;
            }
            $message = 'Cập nhật trạng thái đơn #' . $booking_id . ' thành công.';
            $messageType = 'success';
        }
    }
}

// ---- Bộ lọc ----
$filter_status   = $_GET['status']   ?? '';
$filter_date     = $_GET['date']     ?? '';
$filter_court    = $_GET['court']    ?? '';
$filter_search   = trim($_GET['q']   ?? '');
$filter_payment  = $_GET['payment']  ?? '';
$filter_date_from = $_GET['date_from'] ?? '';
$filter_date_to   = $_GET['date_to']   ?? '';

// Query động với bộ lọc
$sql = 'SELECT b.*, u.name AS user_name, u.email AS user_email, u.phone AS user_phone,
               c.name AS court_name, c.location AS court_location
        FROM bookings b
        JOIN users u ON b.user_id = u.id
        JOIN courts c ON b.court_id = c.id
        WHERE 1=1';
$params = [];
$types  = '';

if ($filter_status !== '') {
    $sql .= ' AND b.status = ?';
    $params[] = $filter_status;
    $types .= 's';
}
if ($filter_court !== '') {
    $sql .= ' AND b.court_id = ?';
    $params[] = intval($filter_court);
    $types .= 'i';
}
if ($filter_payment !== '') {
    $sql .= ' AND b.payment_status = ?';
    $params[] = $filter_payment;
    $types .= 's';
}
if ($filter_date_from !== '') {
    $sql .= ' AND b.booking_date >= ?';
    $params[] = $filter_date_from;
    $types .= 's';
}
if ($filter_date_to !== '') {
    $sql .= ' AND b.booking_date <= ?';
    $params[] = $filter_date_to;
    $types .= 's';
}
if ($filter_search !== '') {
    $sql .= ' AND (u.name LIKE ? OR u.email LIKE ? OR c.name LIKE ? OR b.id = ?)';
    $like = '%' . $filter_search . '%';
    $params[] = $like; $params[] = $like; $params[] = $like;
    $params[] = intval($filter_search);
    $types .= 'sssi';
}

$sql .= ' ORDER BY b.booking_date DESC, b.start_time DESC';

$stmt = $mysqli->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$bookings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Thống kê tổng hợp (không bị ảnh hưởng bởi filter)
$stats = [];
$res = $mysqli->query('SELECT status, COUNT(*) AS cnt, COALESCE(SUM(total_price),0) AS rev FROM bookings GROUP BY status');
while ($row = $res->fetch_assoc()) {
    $stats[$row['status']] = $row;
}
$totalBookings  = array_sum(array_column($stats, 'cnt'));
$totalRevenue   = $stats['confirmed']['rev'] ?? 0;
$pendingCount   = $stats['pending']['cnt']   ?? 0;
$confirmedCount = $stats['confirmed']['cnt'] ?? 0;
$cancelledCount = $stats['cancelled']['cnt'] ?? 0;

// Danh sách sân để filter
$allCourts = $mysqli->query('SELECT id, name FROM courts ORDER BY name ASC')->fetch_all(MYSQLI_ASSOC);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid mt-4">

<!-- Page Header -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-lg" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);">
            <div class="card-body text-white py-4">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h1 class="fw-bold mb-1">
                            <i class="fas fa-calendar-check me-3"></i>Quản lý lịch đặt sân
                        </h1>
                        <p class="mb-0 opacity-90">Theo dõi và quản lý toàn bộ đơn đặt sân của khách hàng.</p>
                    </div>
                    <div class="col-md-4 text-end d-none d-md-block">
                        <i class="fas fa-table-tennis" style="font-size: 4rem; opacity: 0.25;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($message): ?>
<div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show shadow-sm" role="alert">
    <i class="fas fa-check-circle me-2"></i><?php echo escape($message); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Thống kê tổng quan -->
<div class="row g-4 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm h-100 stat-card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="stat-icon bg-primary bg-opacity-10 rounded-3 p-3 me-3">
                        <i class="fas fa-calendar-alt text-primary fa-2x"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-bold text-uppercase">Tổng đơn</div>
                        <div class="h2 fw-bold mb-0 text-primary"><?php echo number_format($totalBookings); ?></div>
                        <div class="small text-muted">Tất cả đơn đặt sân</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm h-100 stat-card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="stat-icon bg-warning bg-opacity-10 rounded-3 p-3 me-3">
                        <i class="fas fa-hourglass-half text-warning fa-2x"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-bold text-uppercase">Chờ xử lý</div>
                        <div class="h2 fw-bold mb-0 text-warning"><?php echo number_format($pendingCount); ?></div>
                        <div class="small text-muted">Cần xác nhận</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm h-100 stat-card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="stat-icon bg-success bg-opacity-10 rounded-3 p-3 me-3">
                        <i class="fas fa-check-circle text-success fa-2x"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-bold text-uppercase">Đã xác nhận</div>
                        <div class="h2 fw-bold mb-0 text-success"><?php echo number_format($confirmedCount); ?></div>
                        <div class="small text-muted">Doanh thu: <?php echo number_format($totalRevenue); ?> đ</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm h-100 stat-card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="stat-icon bg-danger bg-opacity-10 rounded-3 p-3 me-3">
                        <i class="fas fa-times-circle text-danger fa-2x"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-bold text-uppercase">Đã hủy</div>
                        <div class="h2 fw-bold mb-0 text-danger"><?php echo number_format($cancelledCount); ?></div>
                        <div class="small text-muted">Đơn bị hủy</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bộ lọc & Tìm kiếm -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-bottom py-3">
        <h6 class="mb-0 fw-bold"><i class="fas fa-filter text-primary me-2"></i>Bộ lọc tìm kiếm</h6>
    </div>
    <div class="card-body">
        <form method="get" class="row g-3" id="filterForm">
            <div class="col-lg-3 col-md-6">
                <label class="form-label small fw-semibold text-muted">Tìm kiếm</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" name="q" class="form-control border-start-0"
                           placeholder="Tên, email, sân, mã đơn..."
                           value="<?php echo escape($filter_search); ?>">
                </div>
            </div>
            <div class="col-lg-2 col-md-6">
                <label class="form-label small fw-semibold text-muted">Trạng thái đơn</label>
                <select name="status" class="form-select">
                    <option value="">Tất cả</option>
                    <option value="pending"   <?php echo $filter_status==='pending'   ? 'selected':''; ?>>⏳ Chờ xử lý</option>
                    <option value="confirmed" <?php echo $filter_status==='confirmed' ? 'selected':''; ?>>✅ Đã xác nhận</option>
                    <option value="cancelled" <?php echo $filter_status==='cancelled' ? 'selected':''; ?>>❌ Đã hủy</option>
                </select>
            </div>
            <div class="col-lg-2 col-md-6">
                <label class="form-label small fw-semibold text-muted">Sân</label>
                <select name="court" class="form-select">
                    <option value="">Tất cả sân</option>
                    <?php foreach ($allCourts as $c): ?>
                        <option value="<?php echo $c['id']; ?>" <?php echo $filter_court == $c['id'] ? 'selected' : ''; ?>>
                            <?php echo escape($c['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-lg-2 col-md-6">
                <label class="form-label small fw-semibold text-muted">Thanh toán</label>
                <select name="payment" class="form-select">
                    <option value="">Tất cả</option>
                    <option value="paid"    <?php echo $filter_payment==='paid'    ? 'selected':''; ?>>💳 Đã thanh toán</option>
                    <option value="pending" <?php echo $filter_payment==='pending' ? 'selected':''; ?>>⏳ Chờ thanh toán</option>
                    <option value="failed"  <?php echo $filter_payment==='failed'  ? 'selected':''; ?>>❌ Thất bại</option>
                </select>
            </div>
            <div class="col-lg-1 col-md-3">
                <label class="form-label small fw-semibold text-muted">Từ ngày</label>
                <input type="date" name="date_from" class="form-control" value="<?php echo escape($filter_date_from); ?>">
            </div>
            <div class="col-lg-1 col-md-3">
                <label class="form-label small fw-semibold text-muted">Đến ngày</label>
                <input type="date" name="date_to" class="form-control" value="<?php echo escape($filter_date_to); ?>">
            </div>
            <div class="col-lg-1 col-md-6 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-search"></i>
                </button>
                <a href="bookings.php" class="btn btn-outline-secondary w-100" title="Xóa bộ lọc">
                    <i class="fas fa-times"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Bảng danh sách đơn đặt -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom py-3">
        <div class="d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold">
                <i class="fas fa-list text-primary me-2"></i>
                Danh sách đơn đặt sân
                <span class="badge bg-primary ms-2"><?php echo count($bookings); ?></span>
            </h6>
            <div class="d-flex gap-2">
                <!-- Quick filter tabs -->
                <a href="bookings.php" class="btn btn-sm <?php echo $filter_status==='' ? 'btn-dark' : 'btn-outline-secondary'; ?>">Tất cả</a>
                <a href="bookings.php?status=pending"   class="btn btn-sm <?php echo $filter_status==='pending'   ? 'btn-warning' : 'btn-outline-warning'; ?>">Chờ xử lý</a>
                <a href="bookings.php?status=confirmed" class="btn btn-sm <?php echo $filter_status==='confirmed' ? 'btn-success' : 'btn-outline-success'; ?>">Xác nhận</a>
                <a href="bookings.php?status=cancelled" class="btn btn-sm <?php echo $filter_status==='cancelled' ? 'btn-danger'  : 'btn-outline-danger'; ?>">Đã hủy</a>
            </div>
        </div>
    </div>
    <div class="card-body p-0">
        <?php if (empty($bookings)): ?>
        <div class="text-center py-5">
            <i class="fas fa-calendar-times fa-3x text-muted mb-3 d-block"></i>
            <h6 class="text-muted">Không có đơn đặt sân nào</h6>
            <p class="text-muted small">Thử thay đổi bộ lọc hoặc tìm kiếm khác.</p>
            <a href="bookings.php" class="btn btn-sm btn-outline-primary mt-2">Xóa bộ lọc</a>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="bookingsTable">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4 fw-bold text-muted small text-uppercase">Mã đơn</th>
                        <th class="fw-bold text-muted small text-uppercase">Khách hàng</th>
                        <th class="fw-bold text-muted small text-uppercase">Sân</th>
                        <th class="fw-bold text-muted small text-uppercase">Ngày & Giờ</th>
                        <th class="fw-bold text-muted small text-uppercase">Tổng tiền</th>
                        <th class="fw-bold text-muted small text-uppercase">Thanh toán</th>
                        <th class="fw-bold text-muted small text-uppercase">Trạng thái</th>
                        <th class="fw-bold text-muted small text-uppercase text-center">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($bookings as $b):
                    $statusColor = ['confirmed'=>'success','cancelled'=>'danger','pending'=>'warning'][$b['status']] ?? 'secondary';
                    $statusLabel = ['confirmed'=>'Đã xác nhận','cancelled'=>'Đã hủy','pending'=>'Chờ xử lý'][$b['status']] ?? $b['status'];
                    $payColor    = ['paid'=>'success','pending'=>'warning','failed'=>'danger'][$b['payment_status']] ?? 'secondary';
                    $payLabel    = ['paid'=>'Đã thanh toán','pending'=>'Chờ TT','failed'=>'Thất bại'][$b['payment_status']] ?? $b['payment_status'];
                    $methodLabel = $b['payment_method'] ?? 'N/A';
                ?>
                <tr>
                    <td class="ps-4">
                        <span class="fw-bold text-primary">#<?php echo $b['id']; ?></span><br>
                        <small class="text-muted"><?php echo date('d/m H:i', strtotime($b['created_at'] ?? 'now')); ?></small>
                    </td>
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="avatar-circle bg-primary bg-opacity-10 me-2 rounded-circle d-flex align-items-center justify-content-center" style="width:36px;height:36px;flex-shrink:0;">
                                <i class="fas fa-user text-primary small"></i>
                            </div>
                            <div>
                                <div class="fw-semibold"><?php echo escape($b['user_name']); ?></div>
                                <small class="text-muted"><?php echo escape($b['user_email']); ?></small>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div class="fw-semibold"><?php echo escape($b['court_name']); ?></div>
                        <small class="text-muted"><i class="fas fa-map-marker-alt me-1"></i><?php echo escape($b['court_location'] ?? ''); ?></small>
                    </td>
                    <td>
                        <div class="fw-semibold"><?php echo date('d/m/Y', strtotime($b['booking_date'])); ?></div>
                        <small class="text-muted">
                            <i class="fas fa-clock me-1"></i>
                            <?php echo substr($b['start_time'],0,5); ?> – <?php echo substr($b['end_time'],0,5); ?>
                        </small>
                    </td>
                    <td>
                        <div class="fw-bold text-dark"><?php echo number_format($b['total_price']); ?> đ</div>
                        <?php if (!empty($b['discount_amount']) && $b['discount_amount'] > 0): ?>
                        <small class="text-success"><i class="fas fa-tag me-1"></i>-<?php echo number_format($b['discount_amount']); ?> đ</small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="badge bg-<?php echo $payColor; ?> rounded-pill"><?php echo $payLabel; ?></span><br>
                        <small class="text-muted mt-1"><?php echo escape($methodLabel); ?></small>
                    </td>
                    <td>
                        <span class="badge bg-<?php echo $statusColor; ?> rounded-pill fs-6px px-3 py-2" id="status-badge-<?php echo $b['id']; ?>">
                            <?php echo $statusLabel; ?>
                        </span>
                    </td>
                    <td class="text-center">
                        <div class="d-flex justify-content-center gap-1">
                            <button type="button" class="btn btn-sm btn-outline-primary"
                                    onclick="viewBooking(<?php echo htmlspecialchars(json_encode($b), ENT_QUOTES); ?>)"
                                    title="Xem chi tiết">
                                <i class="fas fa-eye"></i>
                            </button>
                            <?php if ($b['status'] === 'pending'): ?>
                            <button type="button" class="btn btn-sm btn-success"
                                    onclick="quickUpdate(<?php echo $b['id']; ?>,'confirmed')"
                                    title="Xác nhận ngay">
                                <i class="fas fa-check"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-danger"
                                    onclick="quickUpdate(<?php echo $b['id']; ?>,'cancelled')"
                                    title="Hủy đơn">
                                <i class="fas fa-times"></i>
                            </button>
                            <?php elseif ($b['status'] === 'confirmed'): ?>
                            <button type="button" class="btn btn-sm btn-outline-danger"
                                    onclick="quickUpdate(<?php echo $b['id']; ?>,'cancelled')"
                                    title="Hủy đơn">
                                <i class="fas fa-ban"></i>
                            </button>
                            <?php elseif ($b['status'] === 'cancelled'): ?>
                            <button type="button" class="btn btn-sm btn-outline-warning"
                                    onclick="quickUpdate(<?php echo $b['id']; ?>,'pending')"
                                    title="Khôi phục">
                                <i class="fas fa-undo"></i>
                            </button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
    <?php if (!empty($bookings)): ?>
    <div class="card-footer bg-white border-top py-3">
        <small class="text-muted">
            Hiển thị <strong><?php echo count($bookings); ?></strong> đơn đặt sân
            <?php if ($filter_status || $filter_search || $filter_court || $filter_payment || $filter_date_from): ?>
                (đã lọc) — <a href="bookings.php">Xóa bộ lọc</a>
            <?php endif; ?>
        </small>
    </div>
    <?php endif; ?>
</div>

</div><!-- /container-fluid -->

<!-- Modal xem chi tiết -->
<div class="modal fade" id="bookingDetailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-white border-0" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);">
                <h5 class="modal-title fw-bold">
                    <i class="fas fa-calendar-check me-2"></i>Chi tiết đơn đặt sân <span id="modal-booking-id"></span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-4">
                    <!-- Thông tin khách hàng -->
                    <div class="col-md-6">
                        <div class="info-section p-3 rounded-3 bg-light">
                            <h6 class="fw-bold text-primary mb-3"><i class="fas fa-user me-2"></i>Thông tin khách hàng</h6>
                            <table class="table table-sm table-borderless mb-0">
                                <tr><td class="text-muted fw-semibold" width="40%">Tên:</td><td id="m-user-name" class="fw-semibold"></td></tr>
                                <tr><td class="text-muted fw-semibold">Email:</td><td id="m-user-email"></td></tr>
                                <tr><td class="text-muted fw-semibold">Điện thoại:</td><td id="m-user-phone"></td></tr>
                            </table>
                        </div>
                    </div>
                    <!-- Thông tin sân -->
                    <div class="col-md-6">
                        <div class="info-section p-3 rounded-3 bg-light">
                            <h6 class="fw-bold text-success mb-3"><i class="fas fa-map-marker-alt me-2"></i>Thông tin sân</h6>
                            <table class="table table-sm table-borderless mb-0">
                                <tr><td class="text-muted fw-semibold" width="40%">Sân:</td><td id="m-court-name" class="fw-semibold"></td></tr>
                                <tr><td class="text-muted fw-semibold">Địa điểm:</td><td id="m-court-location"></td></tr>
                            </table>
                        </div>
                    </div>
                    <!-- Thông tin lịch -->
                    <div class="col-md-6">
                        <div class="info-section p-3 rounded-3 bg-light">
                            <h6 class="fw-bold text-warning mb-3"><i class="fas fa-clock me-2"></i>Thời gian đặt</h6>
                            <table class="table table-sm table-borderless mb-0">
                                <tr><td class="text-muted fw-semibold" width="40%">Ngày:</td><td id="m-date" class="fw-semibold"></td></tr>
                                <tr><td class="text-muted fw-semibold">Giờ:</td><td id="m-time" class="fw-semibold"></td></tr>
                                <tr><td class="text-muted fw-semibold">Thời lượng:</td><td id="m-duration"></td></tr>
                            </table>
                        </div>
                    </div>
                    <!-- Thông tin thanh toán -->
                    <div class="col-md-6">
                        <div class="info-section p-3 rounded-3 bg-light">
                            <h6 class="fw-bold text-info mb-3"><i class="fas fa-credit-card me-2"></i>Thanh toán</h6>
                            <table class="table table-sm table-borderless mb-0">
                                <tr><td class="text-muted fw-semibold" width="40%">Tổng tiền:</td><td id="m-price" class="fw-bold text-dark"></td></tr>
                                <tr><td class="text-muted fw-semibold">Giảm giá:</td><td id="m-discount" class="text-success"></td></tr>
                                <tr><td class="text-muted fw-semibold">Phương thức:</td><td id="m-method"></td></tr>
                                <tr><td class="text-muted fw-semibold">TT Thanh toán:</td><td id="m-pay-status"></td></tr>
                            </table>
                        </div>
                    </div>
                </div>
                <!-- Trạng thái & Ghi chú -->
                <div class="row g-3 mt-1">
                    <div class="col-12">
                        <div class="d-flex align-items-center gap-3 p-3 rounded-3 bg-light">
                            <span class="text-muted fw-semibold">Trạng thái đơn:</span>
                            <span id="m-status-badge" class="badge fs-6 px-3 py-2 rounded-pill"></span>
                        </div>
                    </div>
                    <div class="col-md-6" id="promo-row" style="display:none;">
                        <div class="p-3 rounded-3 bg-light">
                            <small class="text-muted fw-semibold"><i class="fas fa-tag me-1"></i>Mã khuyến mãi:</small>
                            <span id="m-promo" class="ms-2 badge bg-success"></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 bg-light rounded-bottom">
                <div class="d-flex gap-2 w-100 justify-content-between align-items-center">
                    <div class="d-flex gap-2" id="modal-action-buttons"></div>
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Đóng</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Toast thông báo -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 9999">
    <div id="actionToast" class="toast align-items-center text-white border-0" role="alert" aria-live="assertive">
        <div class="d-flex">
            <div class="toast-body fw-semibold" id="toastMsg"></div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>

<style>
.stat-card { transition: transform 0.2s ease, box-shadow 0.2s ease; }
.stat-card:hover { transform: translateY(-4px); box-shadow: 0 0.5rem 1.5rem rgba(0,0,0,.12) !important; }
.info-section { border: 1px solid rgba(0,0,0,.06); }
.table > :not(caption) > * > * { padding: 0.85rem 1rem; }
thead th { font-size: 0.78rem; letter-spacing: .05em; }
.badge.rounded-pill { letter-spacing: .02em; }
</style>

<script>
let currentBookingId = null;

function viewBooking(b) {
    currentBookingId = b.id;
    document.getElementById('modal-booking-id').textContent = '#' + b.id;
    document.getElementById('m-user-name').textContent     = b.user_name || '—';
    document.getElementById('m-user-email').textContent    = b.user_email || '—';
    document.getElementById('m-user-phone').textContent    = b.user_phone || '—';
    document.getElementById('m-court-name').textContent    = b.court_name || '—';
    document.getElementById('m-court-location').textContent = b.court_location || '—';

    // Định dạng ngày
    const d = new Date(b.booking_date);
    const days = ['Chủ nhật','Thứ hai','Thứ ba','Thứ tư','Thứ năm','Thứ sáu','Thứ bảy'];
    document.getElementById('m-date').textContent = days[d.getDay()] + ', ' +
        String(d.getDate()).padStart(2,'0') + '/' +
        String(d.getMonth()+1).padStart(2,'0') + '/' + d.getFullYear();

    const s = (b.start_time || '').substring(0, 5);
    const e = (b.end_time   || '').substring(0, 5);
    document.getElementById('m-time').textContent = s + ' – ' + e;

    // Tính thời lượng
    const [sh,sm] = (b.start_time||'0:0').split(':').map(Number);
    const [eh,em] = (b.end_time  ||'0:0').split(':').map(Number);
    const mins = (eh*60+em) - (sh*60+sm);
    document.getElementById('m-duration').textContent = mins > 0 ? (mins >= 60 ? Math.floor(mins/60) + ' giờ ' + (mins%60 ? mins%60 + ' phút' : '') : mins + ' phút') : '—';

    document.getElementById('m-price').textContent    = Number(b.total_price).toLocaleString('vi-VN') + ' đ';
    const disc = parseInt(b.discount_amount || 0);
    document.getElementById('m-discount').textContent = disc > 0 ? '-' + disc.toLocaleString('vi-VN') + ' đ' : 'Không có';
    document.getElementById('m-method').textContent   = b.payment_method  || '—';

    const payLabels = {paid:'Đã thanh toán', pending:'Chờ thanh toán', failed:'Thất bại'};
    const payColors = {paid:'success', pending:'warning', failed:'danger'};
    const ps = b.payment_status || 'pending';
    document.getElementById('m-pay-status').innerHTML =
        '<span class="badge bg-' + (payColors[ps]||'secondary') + '">' + (payLabels[ps]||ps) + '</span>';

    // Trạng thái đơn
    const sLabels = {confirmed:'Đã xác nhận', pending:'Chờ xử lý', cancelled:'Đã hủy'};
    const sColors  = {confirmed:'success', pending:'warning', cancelled:'danger'};
    const st = b.status || 'pending';
    const badge = document.getElementById('m-status-badge');
    badge.className = 'badge fs-6 px-3 py-2 rounded-pill bg-' + (sColors[st]||'secondary');
    badge.textContent = sLabels[st] || st;

    // Promo
    const promoRow = document.getElementById('promo-row');
    if (b.promo_applied) {
        promoRow.style.display = '';
        document.getElementById('m-promo').textContent = b.promo_applied;
    } else {
        promoRow.style.display = 'none';
    }

    // Action buttons
    const btns = document.getElementById('modal-action-buttons');
    btns.innerHTML = '';
    if (st === 'pending') {
        btns.innerHTML =
            '<button class="btn btn-success" onclick="quickUpdate(' + b.id + ',\'confirmed\',true)"><i class="fas fa-check me-1"></i>Xác nhận đơn</button>' +
            '<button class="btn btn-danger"  onclick="quickUpdate(' + b.id + ',\'cancelled\',true)"><i class="fas fa-times me-1"></i>Hủy đơn</button>';
    } else if (st === 'confirmed') {
        btns.innerHTML =
            '<button class="btn btn-outline-danger" onclick="quickUpdate(' + b.id + ',\'cancelled\',true)"><i class="fas fa-ban me-1"></i>Hủy đơn</button>';
    } else if (st === 'cancelled') {
        btns.innerHTML =
            '<button class="btn btn-outline-warning" onclick="quickUpdate(' + b.id + ',\'pending\',true)"><i class="fas fa-undo me-1"></i>Khôi phục</button>';
    }

    new bootstrap.Modal(document.getElementById('bookingDetailModal')).show();
}

function quickUpdate(id, status, fromModal = false) {
    const labels = {confirmed:'Xác nhận', cancelled:'Hủy', pending:'Khôi phục'};
    const confirmMsg = 'Bạn có chắc muốn ' + (labels[status]||status) + ' đơn #' + id + '?';
    if (!confirm(confirmMsg)) return;

    const fd = new FormData();
    fd.append('booking_id', id);
    fd.append('status', status);
    fd.append('ajax', '1');

    fetch('bookings.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                // Cập nhật badge trong table
                const badge = document.getElementById('status-badge-' + id);
                const sColors = {confirmed:'success', pending:'warning', cancelled:'danger'};
                const sLabels = {confirmed:'Đã xác nhận', pending:'Chờ xử lý', cancelled:'Đã hủy'};
                if (badge) {
                    badge.className = 'badge bg-' + (sColors[status]||'secondary') + ' rounded-pill fs-6px px-3 py-2';
                    badge.textContent = sLabels[status] || status;
                }

                // Toast
                const toast = document.getElementById('actionToast');
                const colors = {confirmed:'bg-success', cancelled:'bg-danger', pending:'bg-warning'};
                toast.className = 'toast align-items-center text-white border-0 ' + (colors[status]||'bg-secondary');
                document.getElementById('toastMsg').textContent = '✅ Đơn #' + id + ' đã cập nhật: ' + (sLabels[status]||status);
                bootstrap.Toast.getOrCreateInstance(toast, {delay: 3000}).show();

                if (fromModal) {
                    bootstrap.Modal.getInstance(document.getElementById('bookingDetailModal'))?.hide();
                    setTimeout(() => location.reload(), 1200);
                } else {
                    // Reload nhẹ chỉ row hiện tại sau 1s
                    setTimeout(() => location.reload(), 1200);
                }
            }
        })
        .catch(() => alert('Có lỗi xảy ra, vui lòng thử lại.'));
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
