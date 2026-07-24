<?php
require_once __DIR__ . '/includes/functions.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$bookings    = getUserBookings($_SESSION['user_id']);
$success_msg = $_SESSION['booking_success'] ?? '';
$error_msg   = $_SESSION['booking_error']   ?? '';
unset($_SESSION['booking_success'], $_SESSION['booking_error']);

require_once __DIR__ . '/includes/header.php';
?>

<style>
.history-wrap { max-width: 960px; margin: 2rem auto; padding: 0 1rem; }

.page-title {
    font-size: 1.8rem;
    font-weight: 700;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: .6rem;
}

.booking-card {
    background: #fff;
    border-radius: 14px;
    box-shadow: 0 4px 18px rgba(0,0,0,.07);
    margin-bottom: 1.2rem;
    overflow: hidden;
    transition: transform .2s, box-shadow .2s;
    border: 1px solid #f0f0f0;
}
.booking-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 28px rgba(0,0,0,.12);
}

.booking-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: .9rem 1.4rem;
    background: linear-gradient(135deg,#667eea,#764ba2);
    color: #fff;
}
.booking-id { font-weight: 700; font-size: 1rem; }
.booking-date-created { font-size: .8rem; opacity: .85; }

.booking-card-body {
    padding: 1.2rem 1.4rem;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
    gap: 1rem;
}

.info-item { display: flex; flex-direction: column; gap: .2rem; }
.info-label { font-size: .75rem; color: #888; text-transform: uppercase; letter-spacing: .5px; }
.info-value { font-weight: 600; color: #333; font-size: .95rem; }

.booking-card-footer {
    padding: .8rem 1.4rem;
    background: #fafafa;
    border-top: 1px solid #f0f0f0;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: .5rem;
}

.badge-status {
    padding: .35rem .9rem;
    border-radius: 20px;
    font-size: .8rem;
    font-weight: 600;
}
.badge-confirmed { background: #d4edda; color: #155724; }
.badge-pending   { background: #fff3cd; color: #856404; }
.badge-cancelled { background: #f8d7da; color: #721c24; }

.badge-payment {
    padding: .3rem .8rem;
    border-radius: 20px;
    font-size: .78rem;
    font-weight: 600;
}
.badge-paid   { background: #d4edda; color: #155724; }
.badge-unpaid { background: #f8d7da; color: #721c24; }
.badge-ppending { background: #fff3cd; color: #856404; }

.empty-state {
    text-align: center;
    padding: 4rem 1rem;
    color: #888;
}
.empty-state i { font-size: 4rem; margin-bottom: 1rem; opacity: .3; }
.empty-state h4 { font-weight: 600; margin-bottom: .5rem; }

.btn-pay {
    background: linear-gradient(135deg,#28a745,#20c997);
    color: #fff;
    border: none;
    border-radius: 8px;
    padding: .4rem 1rem;
    font-size: .85rem;
    font-weight: 600;
    cursor: pointer;
    transition: opacity .2s;
}
.btn-pay:hover { opacity: .85; color: #fff; }

.summary-bar {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
    margin-bottom: 1.5rem;
}
.summary-item {
    background: #fff;
    border-radius: 12px;
    padding: .8rem 1.4rem;
    box-shadow: 0 2px 10px rgba(0,0,0,.06);
    flex: 1;
    min-width: 130px;
    text-align: center;
}
.summary-item .num { font-size: 1.6rem; font-weight: 700; }
.summary-item .lbl { font-size: .78rem; color: #888; }
</style>

<div class="history-wrap">
    <div class="page-title">
        <i class="fas fa-history text-primary"></i>
        Lịch sử đặt sân
    </div>

    <?php if ($success_msg): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i><?php echo escape($success_msg); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <?php if ($error_msg): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i><?php echo escape($error_msg); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <?php if (empty($bookings)): ?>
    <div class="empty-state">
        <i class="fas fa-calendar-times d-block"></i>
        <h4>Bạn chưa có đặt sân nào</h4>
        <p>Hãy đặt sân ngay để trải nghiệm dịch vụ!</p>
        <a href="booking-online.php" class="btn btn-primary mt-2">
            <i class="fas fa-plus me-2"></i>Đặt sân ngay
        </a>
    </div>
    <?php else: ?>

    <!-- Summary bar -->
    <?php
    $total     = count($bookings);
    $confirmed = count(array_filter($bookings, fn($b) => $b['status'] === 'confirmed'));
    $upcoming  = count(array_filter($bookings, fn($b) => $b['status'] === 'confirmed' && $b['booking_date'] >= date('Y-m-d')));
    $spent     = array_sum(array_column(array_filter($bookings, fn($b) => $b['status'] === 'confirmed'), 'total_price'));
    ?>
    <div class="summary-bar">
        <div class="summary-item">
            <div class="num text-primary"><?php echo $total; ?></div>
            <div class="lbl">Tổng booking</div>
        </div>
        <div class="summary-item">
            <div class="num text-success"><?php echo $confirmed; ?></div>
            <div class="lbl">Đã xác nhận</div>
        </div>
        <div class="summary-item">
            <div class="num text-info"><?php echo $upcoming; ?></div>
            <div class="lbl">Sắp tới</div>
        </div>
        <div class="summary-item">
            <div class="num text-warning" style="font-size:1.1rem"><?php echo number_format($spent); ?>đ</div>
            <div class="lbl">Tổng chi tiêu</div>
        </div>
    </div>

    <!-- Booking cards -->
    <?php foreach ($bookings as $b):
        $statusClass  = ['confirmed'=>'badge-confirmed','pending'=>'badge-pending','cancelled'=>'badge-cancelled'][$b['status']] ?? 'badge-pending';
        $statusLabel  = ['confirmed'=>'✓ Đã xác nhận','pending'=>'⏳ Chờ xử lý','cancelled'=>'✗ Đã hủy'][$b['status']] ?? $b['status'];
        $payClass     = ['paid'=>'badge-paid','unpaid'=>'badge-unpaid','pending'=>'badge-ppending','failed'=>'badge-unpaid'][$b['payment_status']] ?? 'badge-ppending';
        $payLabel     = ['paid'=>'Đã thanh toán','unpaid'=>'Chưa thanh toán','pending'=>'Chờ xử lý','failed'=>'Thất bại'][$b['payment_status']] ?? $b['payment_status'];

        // Tính thời lượng
        $start = strtotime($b['booking_date'] . ' ' . $b['start_time']);
        $end   = strtotime($b['booking_date'] . ' ' . $b['end_time']);
        $dur   = max(0, ($end - $start) / 3600);
        $durLabel = $dur == intval($dur) ? intval($dur) . ' giờ' : number_format($dur, 1) . ' giờ';

        $bookingCode     = 'BK' . date('ymd', strtotime($b['created_at'])) . '-' . $b['id'];
        $isCancellable   = ($b['status'] !== 'cancelled') && (strtotime($b['booking_date'] . ' ' . $b['start_time']) > time());
        $originalPrice   = $b['total_price'] + ($b['discount_amount'] ?? 0);
        $phone           = $b['court_phone'] ?? '0968073500';
        $phoneDisp       = preg_replace('/(\d{4})(\d{3})(\d{3})/', '$1.$2.$3', $phone);

        // Encode data for JS
        $modalData = json_encode([
            'id'           => $b['id'],
            'code'         => $bookingCode,
            'status'       => $b['status'],
            'statusLabel'  => $statusLabel,
            'payStatus'    => $b['payment_status'],
            'payLabel'     => $payLabel,
            'payMethod'    => strtoupper($b['payment_method'] ?? ''),
            'courtName'    => $b['court_name'],
            'location'     => $b['location'] ?? '',
            'phone'        => $phone,
            'phoneDisp'    => $phoneDisp,
            'date'         => date('d/m/Y', strtotime($b['booking_date'])),
            'startTime'    => substr($b['start_time'], 0, 5),
            'endTime'      => substr($b['end_time'],   0, 5),
            'duration'     => $durLabel,
            'discount'     => (int)($b['discount_amount'] ?? 0),
            'promo'        => $b['promo_applied'] ?? '',
            'originalPrice'=> $originalPrice,
            'totalPrice'   => $b['total_price'],
            'notes'        => $b['notes'] ?? '',
            'cancellable'  => $isCancellable,
            'createdAt'    => date('d/m/Y H:i', strtotime($b['created_at'])),
        ]);
    ?>
    <div class="booking-card" style="cursor:pointer;" onclick="openBookingDetail(<?php echo htmlspecialchars($modalData, ENT_QUOTES); ?>)">
        <div class="booking-card-header">
            <span class="booking-id"><i class="fas fa-ticket-alt me-2"></i>Booking #<?php echo $b['id']; ?></span>
            <span class="booking-date-created">Đặt lúc: <?php echo date('d/m/Y H:i', strtotime($b['created_at'])); ?></span>
        </div>

        <div class="booking-card-body">
            <div class="info-item">
                <span class="info-label"><i class="fas fa-map-marker-alt me-1"></i>Sân</span>
                <span class="info-value"><?php echo escape($b['court_name']); ?></span>
                <small class="text-muted"><?php echo escape($b['location'] ?? ''); ?></small>
            </div>
            <div class="info-item">
                <span class="info-label"><i class="fas fa-calendar me-1"></i>Ngày</span>
                <span class="info-value"><?php echo date('d/m/Y', strtotime($b['booking_date'])); ?></span>
            </div>
            <div class="info-item">
                <span class="info-label"><i class="fas fa-clock me-1"></i>Giờ</span>
                <span class="info-value"><?php echo substr($b['start_time'],0,5); ?> – <?php echo substr($b['end_time'],0,5); ?></span>
            </div>
            <div class="info-item">
                <span class="info-label"><i class="fas fa-money-bill me-1"></i>Tổng tiền</span>
                <span class="info-value text-success"><?php echo number_format($b['total_price']); ?>đ</span>
                <small class="text-muted"><?php echo strtoupper($b['payment_method']); ?></small>
            </div>
        </div>

        <div class="booking-card-footer">
            <div class="d-flex gap-2 flex-wrap">
                <span class="badge-status <?php echo $statusClass; ?>"><?php echo $statusLabel; ?></span>
                <span class="badge-payment <?php echo $payClass; ?>"><?php echo $payLabel; ?></span>
            </div>
            <?php if (in_array($b['payment_status'], ['unpaid','failed']) && $b['status'] === 'confirmed'): ?>
            <button class="btn-pay" onclick="window.location.href='payment-processing.php?booking_id=<?php echo $b['id']; ?>&method=vnpay'">
                <i class="fas fa-credit-card me-1"></i>Thanh toán ngay
            </button>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>

    <div class="text-center mt-3">
        <a href="booking-online.php" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>Đặt sân mới
        </a>
    </div>

    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

<!-- ===== MODAL CHI TIẾT BOOKING ===== -->
<div class="modal fade" id="bookingDetailModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width:440px;">
        <div class="modal-content border-0 shadow-lg" style="border-radius:20px;overflow:hidden;">

            <!-- Header xanh lá -->
            <div id="bkDetailHeader" style="background:linear-gradient(135deg,#1a6b3c,#2e7d32);padding:1.3rem 1.5rem;color:#fff;display:flex;align-items:center;justify-content:space-between;">
                <div>
                    <div style="font-size:.78rem;opacity:.7;margin-bottom:.2rem;"><i class="fas fa-clipboard-list me-1"></i>Thông tin đặt sân</div>
                    <div id="bkCode" style="font-weight:800;font-size:1.1rem;letter-spacing:.5px;"></div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <!-- Body -->
            <div style="padding:1.3rem 1.5rem;max-height:70vh;overflow-y:auto;">

                <!-- Trạng thái -->
                <div style="display:flex;gap:.6rem;margin-bottom:1.2rem;flex-wrap:wrap;">
                    <span id="bkStatusBadge" style="border-radius:20px;padding:4px 14px;font-size:.8rem;font-weight:700;"></span>
                    <span id="bkPayBadge"    style="border-radius:20px;padding:4px 14px;font-size:.8rem;font-weight:700;"></span>
                </div>

                <!-- Thông tin sân -->
                <div style="background:#f0fdf4;border-radius:12px;padding:1rem 1.1rem;margin-bottom:1rem;">
                    <div style="font-weight:800;font-size:.95rem;color:#1a6b3c;margin-bottom:.6rem;">
                        <i class="fas fa-map-marker-alt me-2"></i><span id="bkCourtName"></span>
                    </div>
                    <div style="font-size:.85rem;color:#374151;margin-bottom:.35rem;">
                        <i class="fas fa-location-dot me-2 text-muted"></i><span id="bkLocation"></span>
                    </div>
                    <div style="font-size:.85rem;color:#374151;display:flex;align-items:center;justify-content:space-between;">
                        <span><i class="fas fa-phone me-2 text-muted"></i><span id="bkPhone"></span></span>
                        <a id="bkCallLink" href="#" style="background:#2e7d32;color:#fff;border-radius:20px;padding:3px 12px;font-size:.78rem;font-weight:700;text-decoration:none;">
                            <i class="fas fa-phone me-1"></i>Gọi
                        </a>
                    </div>
                </div>

                <!-- Thời gian -->
                <div style="background:#f8fafc;border-radius:12px;padding:.9rem 1.1rem;margin-bottom:1rem;">
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:.7rem;font-size:.85rem;">
                        <div>
                            <div style="color:#9ca3af;font-size:.72rem;margin-bottom:2px;">NGÀY</div>
                            <div style="font-weight:700;" id="bkDate"></div>
                        </div>
                        <div>
                            <div style="color:#9ca3af;font-size:.72rem;margin-bottom:2px;">KHUNG GIỜ</div>
                            <div style="font-weight:700;" id="bkTime"></div>
                        </div>
                        <div>
                            <div style="color:#9ca3af;font-size:.72rem;margin-bottom:2px;">THỜI LƯỢNG</div>
                            <div style="font-weight:700;" id="bkDuration"></div>
                        </div>
                        <div>
                            <div style="color:#9ca3af;font-size:.72rem;margin-bottom:2px;">THANH TOÁN</div>
                            <div style="font-weight:700;" id="bkPayMethod"></div>
                        </div>
                    </div>
                </div>

                <!-- Tổng tiền -->
                <div style="background:#f8fafc;border-radius:12px;padding:.9rem 1.1rem;margin-bottom:1rem;font-size:.88rem;">
                    <div style="display:flex;justify-content:space-between;padding:.3rem 0;color:#6b7280;">
                        <span>Giá gốc</span><span id="bkOriginal"></span>
                    </div>
                    <div id="bkDiscountRow" style="display:flex;justify-content:space-between;padding:.3rem 0;color:#10b981;">
                        <span id="bkPromoLabel">Khuyến mãi</span><span id="bkDiscount"></span>
                    </div>
                    <div style="display:flex;justify-content:space-between;padding:.5rem 0;border-top:1px solid #e5e7eb;margin-top:.3rem;font-weight:800;font-size:1rem;">
                        <span>Tổng tiền</span>
                        <span id="bkTotal" style="color:#1a6b3c;"></span>
                    </div>
                </div>

                <!-- Ghi chú -->
                <div id="bkNotesRow" style="background:#fffbeb;border-radius:10px;padding:.75rem 1rem;margin-bottom:1rem;font-size:.85rem;color:#92400e;display:none;">
                    <i class="fas fa-sticky-note me-2"></i><span id="bkNotes"></span>
                </div>

                <!-- Thời gian đặt -->
                <div style="font-size:.75rem;color:#9ca3af;text-align:center;margin-bottom:.5rem;">
                    Đặt lúc: <span id="bkCreatedAt"></span>
                </div>
            </div>

            <!-- Footer: Nút huỷ -->
            <div id="bkFooter" style="padding:1rem 1.5rem;border-top:1px solid #f3f4f6;">
                <button id="bkCancelBtn" onclick="cancelBooking()"
                        style="width:100%;background:linear-gradient(135deg,#ef4444,#dc2626);color:#fff;border:none;border-radius:12px;padding:.85rem;font-weight:800;font-size:.95rem;cursor:pointer;transition:opacity .2s;">
                    <i class="fas fa-times-circle me-2"></i>Huỷ đặt lịch
                </button>
            </div>

        </div>
    </div>
</div>

<script>
let _currentBookingId = null;

function openBookingDetail(d) {
    _currentBookingId = d.id;

    // Code + header
    document.getElementById('bkCode').textContent = '#' + d.code;

    // Trạng thái badges
    const statusColors = {
        confirmed: ['#d1fae5','#065f46'],
        pending:   ['#fef3c7','#92400e'],
        cancelled: ['#fee2e2','#991b1b'],
    };
    const [sBg, sFg] = statusColors[d.status] ?? ['#f3f4f6','#374151'];
    const sbEl = document.getElementById('bkStatusBadge');
    sbEl.textContent = d.statusLabel;
    sbEl.style.background = sBg; sbEl.style.color = sFg;

    const payColors = { paid:['#d1fae5','#065f46'], unpaid:['#fee2e2','#991b1b'], pending:['#fef3c7','#92400e'], failed:['#fee2e2','#991b1b'] };
    const [pBg, pFg] = payColors[d.payStatus] ?? ['#f3f4f6','#374151'];
    const pbEl = document.getElementById('bkPayBadge');
    pbEl.textContent = d.payLabel;
    pbEl.style.background = pBg; pbEl.style.color = pFg;

    // Thông tin sân
    document.getElementById('bkCourtName').textContent = d.courtName;
    document.getElementById('bkLocation').textContent  = d.location;
    document.getElementById('bkPhone').textContent     = d.phoneDisp;
    document.getElementById('bkCallLink').href         = 'tel:' + d.phone;

    // Thời gian
    document.getElementById('bkDate').textContent     = d.date;
    document.getElementById('bkTime').textContent     = d.startTime + ' – ' + d.endTime;
    document.getElementById('bkDuration').textContent = d.duration;
    document.getElementById('bkPayMethod').textContent= d.payMethod;

    // Giá
    document.getElementById('bkOriginal').textContent = parseInt(d.originalPrice).toLocaleString('vi-VN') + 'đ';
    document.getElementById('bkTotal').textContent    = parseInt(d.totalPrice).toLocaleString('vi-VN') + 'đ';

    const discRow = document.getElementById('bkDiscountRow');
    if (d.discount > 0) {
        discRow.style.display = 'flex';
        document.getElementById('bkDiscount').textContent = '-' + parseInt(d.discount).toLocaleString('vi-VN') + 'đ';
        if (d.promo) document.getElementById('bkPromoLabel').textContent = d.promo;
    } else {
        discRow.style.display = 'none';
    }

    // Ghi chú
    const notesRow = document.getElementById('bkNotesRow');
    if (d.notes) {
        notesRow.style.display = 'block';
        document.getElementById('bkNotes').textContent = d.notes;
    } else {
        notesRow.style.display = 'none';
    }

    // Thời gian đặt
    document.getElementById('bkCreatedAt').textContent = d.createdAt;

    // Nút huỷ
    const footer = document.getElementById('bkFooter');
    footer.style.display = d.cancellable && d.status !== 'cancelled' ? 'block' : 'none';

    new bootstrap.Modal(document.getElementById('bookingDetailModal')).show();
}

function cancelBooking() {
    if (!_currentBookingId) return;
    if (!confirm('Bạn có chắc muốn huỷ booking này?')) return;

    const btn = document.getElementById('bkCancelBtn');
    btn.innerHTML = '<div class="spinner-border spinner-border-sm me-2"></div>Đang huỷ...';
    btn.disabled = true;

    fetch('api/cancel-booking.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ booking_id: _currentBookingId })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('bookingDetailModal')).hide();
            location.reload();
        } else {
            alert(data.message || 'Không thể huỷ booking.');
            btn.innerHTML = '<i class="fas fa-times-circle me-2"></i>Huỷ đặt lịch';
            btn.disabled = false;
        }
    })
    .catch(() => {
        alert('Lỗi kết nối.');
        btn.innerHTML = '<i class="fas fa-times-circle me-2"></i>Huỷ đặt lịch';
        btn.disabled = false;
    });
}
</script>
