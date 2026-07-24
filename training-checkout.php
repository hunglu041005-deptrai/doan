<?php
require_once __DIR__ . '/includes/functions.php';

// Lấy thông tin đăng ký
$reg_id   = intval($_GET['reg']  ?? 0);
$reg_code = trim($_GET['code'] ?? '');

if (!$reg_id || !$reg_code) {
    header('Location: training.php');
    exit;
}

// Lấy thông tin từ DB
$stmt = $mysqli->prepare(
    'SELECT tr.*, c.name AS coach_name, c.specialty AS coach_specialty, c.avatar AS coach_avatar
     FROM training_registrations tr
     LEFT JOIN coaches c ON c.id = tr.coach_id
     WHERE tr.id = ? AND tr.student_code = ? LIMIT 1'
);
$stmt->bind_param('is', $reg_id, $reg_code);
$stmt->execute();
$reg = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$reg) {
    header('Location: training.php');
    exit;
}

// Nếu đã thanh toán rồi thì redirect sang trang xác nhận
if ($reg['status'] === 'active') {
    header('Location: training-confirm.php?code=' . urlencode($reg_code));
    exit;
}

// Giá khóa học
$course_prices = [
    'beginner'     => 1800000,
    'intermediate' => 2800000,
    'advanced'     => 4500000,
];
$course_labels = [
    'beginner'     => 'Cơ bản (3 tháng)',
    'intermediate' => 'Trung cấp (4 tháng)',
    'advanced'     => 'Nâng cao (5 tháng)',
];
$price = $course_prices[$reg['course']] ?? 0;
$label = $course_labels[$reg['course']] ?? $reg['course'];

// ===== XỬ LÝ THANH TOÁN =====
$payment_done = false;
$payment_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_payment'])) {
    $method = $_POST['payment_method'] ?? 'cash';

    // Thêm cột nếu chưa có
    $mysqli->query('ALTER TABLE training_registrations ADD COLUMN IF NOT EXISTS payment_method VARCHAR(30) DEFAULT NULL');
    $mysqli->query('ALTER TABLE training_registrations ADD COLUMN IF NOT EXISTS payment_at DATETIME DEFAULT NULL');

    if ($method === 'cash') {
        // Tiền mặt: xác nhận ngay
        $upd = $mysqli->prepare('UPDATE training_registrations SET status="active", payment_method=?, payment_at=NOW() WHERE id=?');
        $upd->bind_param('si', $method, $reg_id);
        $upd->execute();
        $upd->close();

        if (!empty($_SESSION['user_id'])) {
            try {
                require_once __DIR__ . '/includes/notification-system.php';
                $ns = new NotificationSystem();
                $ns->notifyTrainingRegistered((int)$_SESSION['user_id'], $reg['student_code'], $label, $reg['coach_name'] ?? 'HLV');
            } catch (Exception $e) {}
        }
        $payment_done = true;
        $reg['status'] = 'active';
    } else {
        // MoMo / Bank: giữ pending, chờ webhook
        $upd = $mysqli->prepare('UPDATE training_registrations SET payment_method=? WHERE id=?');
        $upd->bind_param('si', $method, $reg_id);
        $upd->execute();
        $upd->close();
        // Không set active ngay - sẽ do webhook xử lý
        // Hiển thị trang chờ thanh toán
        $payment_done = false; // vẫn show form nhưng ở trạng thái "đang chờ"
        $waiting_transfer = true;
    }
}
$waiting_transfer = $waiting_transfer ?? false;

require_once __DIR__ . '/includes/header.php';
?>

<style>
.tc-page {
    background: #f8fafc;
    min-height: 100vh;
    padding: 2.5rem 0 4rem;
}

/* Breadcrumb */
.tc-breadcrumb {
    font-size: .82rem;
    color: #9ca3af;
    margin-bottom: 2rem;
    display: flex;
    align-items: center;
    gap: .4rem;
}
.tc-breadcrumb a { color: #6366f1; text-decoration: none; }

/* Steps */
.checkout-steps {
    display: flex;
    align-items: center;
    gap: 0;
    margin-bottom: 2.5rem;
    background: #fff;
    border-radius: 16px;
    padding: 1rem 1.5rem;
    box-shadow: 0 2px 12px rgba(0,0,0,.06);
}
.step-item {
    display: flex;
    align-items: center;
    gap: .6rem;
    flex: 1;
}
.step-dot {
    width: 32px; height: 32px;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-weight: 800; font-size: .85rem; flex-shrink: 0;
}
.step-dot.done    { background: #10b981; color: #fff; }
.step-dot.active  { background: linear-gradient(135deg,#6366f1,#8b5cf6); color: #fff; box-shadow: 0 4px 12px rgba(99,102,241,.4); }
.step-dot.pending { background: #e5e7eb; color: #9ca3af; }
.step-label { font-size: .8rem; font-weight: 700; color: #374151; }
.step-label.pending { color: #9ca3af; }
.step-line { flex-grow: 1; height: 2px; background: #e5e7eb; max-width: 48px; }
.step-line.done { background: #10b981; }

/* Card chung */
.tc-card {
    background: #fff;
    border-radius: 20px;
    box-shadow: 0 4px 20px rgba(0,0,0,.06);
    border: 1px solid #f0f0f0;
    overflow: hidden;
    margin-bottom: 1.5rem;
}
.tc-card-header {
    padding: 1.2rem 1.5rem;
    border-bottom: 1px solid #f3f4f6;
    font-weight: 800;
    color: #111827;
    display: flex;
    align-items: center;
    gap: .6rem;
}
.tc-card-header i { color: #6366f1; }
.tc-card-body { padding: 1.5rem; }

/* Tóm tắt đăng ký */
.reg-summary-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: .6rem 0;
    border-bottom: 1px solid #f3f4f6;
    font-size: .9rem;
}
.reg-summary-row:last-child { border: none; }
.reg-summary-row .label { color: #6b7280; }
.reg-summary-row .value { font-weight: 700; color: #111827; }

/* Coach mini card */
.coach-mini {
    display: flex;
    align-items: center;
    gap: .8rem;
    background: linear-gradient(135deg,#f8f4ff,#ede9fe);
    border-radius: 12px;
    padding: .8rem 1rem;
    margin-bottom: 1rem;
    border: 1px solid rgba(99,102,241,.15);
}
.coach-mini img,
.coach-mini .coach-icon {
    width: 48px; height: 48px; border-radius: 50%; object-fit: cover;
    border: 2px solid rgba(99,102,241,.3);
    flex-shrink: 0;
}
.coach-mini .coach-icon {
    background: linear-gradient(135deg,#302b63,#6366f1);
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-size: 1.1rem;
}

/* Payment methods */
.pay-method {
    border: 2px solid #e5e7eb;
    border-radius: 14px;
    padding: 1rem 1.2rem;
    cursor: pointer;
    transition: all .2s;
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: .8rem;
    user-select: none;
}
.pay-method:hover { border-color: #6366f1; background: #f8f7ff; }
.pay-method.selected { border-color: #6366f1; background: linear-gradient(135deg,#f8f7ff,#ede9fe); }
.pay-method input[type=radio] { accent-color: #6366f1; transform: scale(1.3); }
.pay-method-icon {
    width: 44px; height: 44px; border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.3rem; flex-shrink: 0;
}

/* Total box */
.total-box {
    background: linear-gradient(135deg,#0f0c29,#302b63);
    border-radius: 16px;
    padding: 1.2rem 1.5rem;
    color: #fff;
}
.total-line { display: flex; justify-content: space-between; margin-bottom: .4rem; font-size: .9rem; }
.total-line .tl { color: rgba(255,255,255,.6); }
.total-final { display: flex; justify-content: space-between; align-items: center; margin-top: .8rem; padding-top: .8rem; border-top: 1px solid rgba(255,255,255,.15); }
.total-final .amount { font-size: 1.6rem; font-weight: 900; color: #fbbf24; }

/* CTA button */
.btn-pay {
    background: linear-gradient(135deg,#fbbf24,#f59e0b);
    color: #111;
    border: none;
    border-radius: 14px;
    padding: 1rem 2rem;
    font-weight: 900;
    font-size: 1rem;
    width: 100%;
    cursor: pointer;
    box-shadow: 0 8px 25px rgba(251,191,36,.4);
    transition: all .2s;
    margin-top: 1rem;
}
.btn-pay:hover { transform: translateY(-2px); box-shadow: 0 12px 35px rgba(251,191,36,.5); }

/* Success state */
.success-banner {
    background: linear-gradient(135deg,#0f0c29,#302b63);
    border-radius: 20px;
    padding: 2.5rem;
    text-align: center;
    color: #fff;
    margin-bottom: 2rem;
}
.success-icon {
    width: 80px; height: 80px;
    background: linear-gradient(135deg,#10b981,#059669);
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    margin: 0 auto 1.2rem;
    box-shadow: 0 8px 25px rgba(16,185,129,.4);
    font-size: 2rem; color: #fff;
}

/* Student card */
.student-card {
    background: linear-gradient(135deg,#0f0c29 0%,#302b63 100%);
    border-radius: 18px; padding: 1.8rem; color: #fff;
    position: relative; overflow: hidden;
}
.student-card::before {
    content:''; position:absolute; top:-30px; right:-30px;
    width:150px; height:150px; background:rgba(251,191,36,.1); border-radius:50%;
}
.student-card::after {
    content:''; position:absolute; bottom:-40px; left:-20px;
    width:120px; height:120px; background:rgba(99,102,241,.1); border-radius:50%;
}
</style>

<div class="tc-page">
<div class="container-lg">

    <!-- Breadcrumb -->
    <div class="tc-breadcrumb">
        <a href="index.php"><i class="fas fa-home"></i></a>
        <i class="fas fa-chevron-right" style="font-size:.65rem;"></i>
        <a href="training.php">Khóa học</a>
        <i class="fas fa-chevron-right" style="font-size:.65rem;"></i>
        <span>Thanh toán</span>
    </div>

    <!-- Steps -->
    <div class="checkout-steps">
        <div class="step-item">
            <div class="step-dot done"><i class="fas fa-check"></i></div>
            <div class="step-label">Đăng ký</div>
        </div>
        <div class="step-line done"></div>
        <div class="step-item">
            <div class="step-dot <?php echo $payment_done ? 'done' : 'active'; ?>">
                <?php echo $payment_done ? '<i class="fas fa-check"></i>' : '2'; ?>
            </div>
            <div class="step-label">Thanh toán</div>
        </div>
        <div class="step-line <?php echo $payment_done ? 'done' : ''; ?>"></div>
        <div class="step-item">
            <div class="step-dot <?php echo $payment_done ? 'done' : 'pending'; ?>">
                <?php echo $payment_done ? '<i class="fas fa-check"></i>' : '3'; ?>
            </div>
            <div class="step-label <?php echo $payment_done ? '' : 'pending'; ?>">Xác nhận</div>
        </div>
    </div>

    <?php if ($payment_done): ?>
    <!-- ===== TRẠNG THÁI THANH TOÁN THÀNH CÔNG ===== -->
    <div class="success-banner">
        <div class="success-icon"><i class="fas fa-check"></i></div>
        <h3 class="fw-bold mb-1">Đăng ký thành công!</h3>
        <p style="color:rgba(255,255,255,.6);margin:0;">Thẻ học viên của bạn đã được kích hoạt</p>
    </div>

    <div class="row justify-content-center g-4">
        <div class="col-lg-6">
            <!-- Thẻ học viên -->
            <div id="student-card-print">
                <div class="student-card">
                    <div class="d-flex justify-content-between align-items-start mb-3" style="position:relative;z-index:1;">
                        <div>
                            <div style="font-size:.68rem;color:rgba(255,255,255,.5);text-transform:uppercase;letter-spacing:1px;">BadmintonPro Academy</div>
                            <div style="font-size:1.1rem;font-weight:800;color:#fbbf24;">THẺ HỌC VIÊN</div>
                        </div>
                        <div style="background:rgba(16,185,129,.2);border:1px solid rgba(16,185,129,.4);border-radius:8px;padding:4px 10px;font-size:.72rem;font-weight:700;color:#4ade80;">ACTIVE</div>
                    </div>
                    <div style="text-align:center;margin:1rem 0;position:relative;z-index:1;">
                        <div style="font-size:.68rem;color:rgba(255,255,255,.5);margin-bottom:.3rem;">MÃ HỌC VIÊN</div>
                        <div style="font-size:1.8rem;font-weight:900;letter-spacing:4px;color:#fff;font-family:monospace;"><?php echo escape($reg['student_code']); ?></div>
                    </div>
                    <div style="text-align:center;margin:1rem 0;position:relative;z-index:1;">
                        <div id="sc-qr" style="display:inline-block;background:#fff;padding:10px;border-radius:12px;"></div>
                        <div style="font-size:.68rem;color:rgba(255,255,255,.5);margin-top:.5rem;">Quét mã để xác nhận học viên</div>
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:.8rem;position:relative;z-index:1;">
                        <div style="background:rgba(255,255,255,.08);border-radius:10px;padding:.7rem;">
                            <div style="font-size:.62rem;color:rgba(255,255,255,.5);text-transform:uppercase;">Họ tên</div>
                            <div style="font-weight:700;font-size:.85rem;"><?php echo escape($reg['student_name']); ?></div>
                        </div>
                        <div style="background:rgba(255,255,255,.08);border-radius:10px;padding:.7rem;">
                            <div style="font-size:.62rem;color:rgba(255,255,255,.5);text-transform:uppercase;">Khóa học</div>
                            <div style="font-weight:700;font-size:.85rem;color:#fbbf24;"><?php echo escape($label); ?></div>
                        </div>
                        <div style="background:rgba(255,255,255,.08);border-radius:10px;padding:.7rem;">
                            <div style="font-size:.62rem;color:rgba(255,255,255,.5);text-transform:uppercase;">Ngày đăng ký</div>
                            <div style="font-weight:700;font-size:.85rem;"><?php echo date('d/m/Y', strtotime($reg['created_at'])); ?></div>
                        </div>
                        <div style="background:rgba(255,255,255,.08);border-radius:10px;padding:.7rem;">
                            <div style="font-size:.62rem;color:rgba(255,255,255,.5);text-transform:uppercase;">HLV kèm 1-1</div>
                            <div style="font-weight:700;font-size:.85rem;color:#a5b4fc;"><?php echo escape($reg['coach_name'] ?? 'Đang cập nhật'); ?></div>
                        </div>
                    </div>
                    <div style="margin-top:.8rem;background:rgba(99,102,241,.15);border:1px solid rgba(99,102,241,.3);border-radius:10px;padding:.7rem;position:relative;z-index:1;">
                        <div style="font-size:.62rem;color:rgba(255,255,255,.5);text-transform:uppercase;margin-bottom:.3rem;"><i class="fas fa-calendar-alt me-1"></i>Lịch học (3 ngày/tuần)</div>
                        <div style="font-weight:700;font-size:.85rem;color:#c7d2fe;"><?php echo escape($reg['schedule_days'] ?? ''); ?></div>
                        <div style="font-size:.75rem;color:rgba(255,255,255,.6);margin-top:.2rem;"><?php echo escape($reg['schedule_time'] ?? ''); ?></div>
                    </div>
                </div>
            </div>

            <div style="background:#fffbeb;border:1px solid #fde68a;border-radius:12px;padding:1rem;font-size:.82rem;color:#92400e;text-align:center;margin-top:1rem;">
                <i class="fas fa-info-circle me-2"></i>Xuất trình mã thẻ hoặc QR code cho nhân viên khi đến sân tập
            </div>

            <div class="d-flex gap-2 mt-3">
                <button onclick="window.print()" class="btn fw-bold flex-grow-1 py-2"
                        style="background:linear-gradient(135deg,#0f0c29,#302b63);color:#fff;border:none;border-radius:12px;">
                    <i class="fas fa-print me-2"></i>In thẻ học viên
                </button>
                <a href="training.php" class="btn fw-bold py-2 px-3"
                   style="background:#f3f4f6;color:#374151;border:none;border-radius:12px;">
                    Quay lại
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
    <script>
    new QRCode(document.getElementById('sc-qr'), {
        text: 'BADMINTONPRO-HV|<?php echo escape($reg['student_code']); ?>|<?php echo escape($reg['student_name']); ?>',
        width: 120, height: 120,
        colorDark: '#0f0c29', colorLight: '#ffffff',
        correctLevel: QRCode.CorrectLevel.H
    });
    </script>
    <style>
    @media print {
        body * { visibility: hidden; }
        #student-card-print, #student-card-print * { visibility: visible; }
        #student-card-print { position: fixed; top: 0; left: 0; width: 100%; padding: 2rem; }
    }
    </style>

    <?php else: ?>
    <!-- ===== FORM THANH TOÁN ===== -->
    <div class="row g-4">

        <!-- Cột trái: tóm tắt + payment -->
        <div class="col-lg-7">

            <!-- Tóm tắt đăng ký -->
            <div class="tc-card">
                <div class="tc-card-header"><i class="fas fa-clipboard-list"></i> Thông tin đăng ký</div>
                <div class="tc-card-body">
                    <!-- HLV mini card -->
                    <?php if ($reg['coach_name']): ?>
                    <div class="coach-mini">
                        <?php if (!empty($reg['coach_avatar']) && file_exists(__DIR__ . '/' . $reg['coach_avatar'])): ?>
                            <img src="<?php echo escape($reg['coach_avatar']); ?>" alt="HLV">
                        <?php else: ?>
                            <div class="coach-icon"><i class="fas fa-chalkboard-teacher"></i></div>
                        <?php endif; ?>
                        <div>
                            <div style="font-weight:800;color:#374151;"><?php echo escape($reg['coach_name']); ?></div>
                            <div style="font-size:.8rem;color:#6b7280;"><?php echo escape($reg['coach_specialty'] ?? 'Huấn luyện viên'); ?></div>
                        </div>
                        <div class="ms-auto" style="font-size:.75rem;background:#ede9fe;color:#6366f1;padding:3px 10px;border-radius:8px;font-weight:700;">HLV kèm 1-1</div>
                    </div>
                    <?php endif; ?>

                    <div class="reg-summary-row">
                        <span class="label">Học viên</span>
                        <span class="value"><?php echo escape($reg['student_name']); ?></span>
                    </div>
                    <div class="reg-summary-row">
                        <span class="label">Số điện thoại</span>
                        <span class="value"><?php echo escape($reg['phone']); ?></span>
                    </div>
                    <div class="reg-summary-row">
                        <span class="label">Khóa học</span>
                        <span class="value" style="color:#6366f1;"><?php echo escape($label); ?></span>
                    </div>
                    <div class="reg-summary-row">
                        <span class="label">Lịch học</span>
                        <span class="value"><?php echo escape($reg['schedule_days'] ?? ''); ?></span>
                    </div>
                    <div class="reg-summary-row">
                        <span class="label">Thời gian</span>
                        <span class="value"><?php echo escape($reg['schedule_time'] ?? ''); ?></span>
                    </div>
                    <div class="reg-summary-row">
                        <span class="label">Mã học viên</span>
                        <span class="value" style="font-family:monospace;color:#6366f1;"><?php echo escape($reg['student_code']); ?></span>
                    </div>
                </div>
            </div>

            <!-- Phương thức thanh toán -->
            <div class="tc-card">
                <div class="tc-card-header"><i class="fas fa-credit-card"></i> Phương thức thanh toán</div>
                <div class="tc-card-body">
                    <form method="POST" id="payForm">
                        <input type="hidden" name="confirm_payment" value="1">

                        <!-- Tiền mặt -->
                        <label class="pay-method selected" id="pm-cash" onclick="selectPayMethod('cash',this)">
                            <input type="radio" name="payment_method" value="cash" checked style="accent-color:#16a34a;transform:scale(1.2);">
                            <div class="pay-method-icon" style="background:#dcfce7;">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none"><rect x="2" y="6" width="20" height="13" rx="3" fill="#16a34a" opacity=".15"/><rect x="2" y="6" width="20" height="13" rx="3" stroke="#16a34a" stroke-width="1.8"/><path d="M6 13h4M6 16h3" stroke="#16a34a" stroke-width="1.8" stroke-linecap="round"/><circle cx="16" cy="13" r="2.5" fill="#16a34a"/></svg>
                            </div>
                            <div>
                                <div style="font-weight:700;">Thanh toán tiền mặt</div>
                                <small style="color:#6b7280;">Thanh toán trực tiếp tại cơ sở khi đến buổi học đầu tiên</small>
                            </div>
                        </label>

                        <!-- MoMo -->
                        <label class="pay-method" id="pm-momo" onclick="selectPayMethod('momo',this)">
                            <input type="radio" name="payment_method" value="momo" style="accent-color:#db2777;transform:scale(1.2);">
                            <div class="pay-method-icon" style="background:#fce7f3;">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none"><rect x="3" y="5" width="18" height="14" rx="3" fill="#db2777" opacity=".12"/><rect x="3" y="5" width="18" height="14" rx="3" stroke="#db2777" stroke-width="1.8"/><circle cx="8" cy="11" r="2" fill="#db2777"/><circle cx="12" cy="11" r="2" fill="#db2777"/><circle cx="16" cy="11" r="2" fill="#db2777"/></svg>
                            </div>
                            <div>
                                <div style="font-weight:700;">Ví MoMo</div>
                                <small style="color:#6b7280;">Chuyển tiền qua số điện thoại MoMo</small>
                            </div>
                        </label>

                        <!-- MoMo panel -->
                        <div id="momoPanel" style="display:none;background:#fdf2f8;border:1px solid #f9a8d4;border-radius:14px;padding:1.2rem;margin-bottom:.8rem;animation:fadeIn .25s ease;">
                            <div style="font-weight:700;font-size:.82rem;color:#be185d;margin-bottom:.8rem;display:flex;align-items:center;gap:.4rem;">
                                <i class="fas fa-mobile-alt"></i> Thông tin thanh toán MoMo
                            </div>
                            <div style="display:flex;gap:1rem;align-items:flex-start;">
                                <img id="momoQr"
                                     src="https://img.vietqr.io/image/MOMO-0968073500-qr_only.png?amount=<?php echo $price; ?>&addInfo=<?php echo urlencode($reg['student_code']); ?>&accountName=LU+DANG+HUNG"
                                     alt="QR MoMo"
                                     style="width:120px;height:120px;border-radius:10px;border:2px solid #f9a8d4;padding:3px;background:#fff;flex-shrink:0;">
                                <div style="display:grid;gap:.45rem;font-size:.85rem;flex:1;">
                                    <div style="display:flex;gap:.5rem;">
                                        <span style="color:#78716c;min-width:115px;">Số điện thoại</span>
                                        <strong style="font-family:monospace;color:#db2777;">0968073500</strong>
                                    </div>
                                    <div style="display:flex;gap:.5rem;">
                                        <span style="color:#78716c;min-width:115px;">Tên tài khoản</span>
                                        <strong>LU DANG HUNG</strong>
                                    </div>
                                    <div style="display:flex;gap:.5rem;">
                                        <span style="color:#78716c;min-width:115px;">Số tiền</span>
                                        <strong style="color:#db2777;"><?php echo number_format($price); ?>đ</strong>
                                    </div>
                                    <div style="display:flex;gap:.5rem;">
                                        <span style="color:#78716c;min-width:115px;">Nội dung CK</span>
                                        <strong style="font-family:monospace;color:#db2777;"><?php echo escape($reg['student_code']); ?></strong>
                                    </div>
                                </div>
                            </div>
                            <div style="margin-top:.7rem;background:#fce7f3;border-radius:8px;padding:.5rem .8rem;font-size:.78rem;color:#9d174d;">
                                <i class="fas fa-info-circle me-1"></i>
                                Mở app MoMo → Quét QR hoặc Chuyển tiền → Nhập SĐT → Điền đúng nội dung CK
                            </div>
                            <!-- Hướng dẫn tự động -->
                            <div style="margin-top:.85rem;padding:.65rem .9rem;background:#fce7f3;border-radius:10px;font-size:.82rem;color:#be185d;">
                                <i class="fas fa-magic me-1"></i> Hệ thống <strong>tự động xác nhận</strong> khi nhận được tiền MoMo.
                            </div>
                        </div>

                        <!-- MB Bank -->
                        <label class="pay-method" id="pm-vnpay" onclick="selectPayMethod('vnpay',this)">
                            <input type="radio" name="payment_method" value="vnpay" style="accent-color:#2563eb;transform:scale(1.2);">
                            <div class="pay-method-icon" style="background:#dbeafe;">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none"><path d="M3 21h18M5 21V10M19 21V10" stroke="#2563eb" stroke-width="1.8" stroke-linecap="round"/><path d="M2 10l10-7 10 7" stroke="#2563eb" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><rect x="9" y="14" width="6" height="7" rx="1" fill="#2563eb" opacity=".15" stroke="#2563eb" stroke-width="1.5"/></svg>
                            </div>
                            <div>
                                <div style="font-weight:700;">Chuyển khoản ngân hàng</div>
                                <small style="color:#6b7280;">MB Bank — xác nhận trong 30 phút</small>
                            </div>
                        </label>

                        <!-- Bank panel -->
                        <div id="bankPanel" style="display:none;background:#fffdf0;border:1px solid #fde68a;border-radius:14px;padding:1.2rem;margin-bottom:.8rem;animation:fadeIn .25s ease;">
                            <div style="font-weight:700;font-size:.82rem;color:#92400e;margin-bottom:.8rem;display:flex;align-items:center;gap:.4rem;">
                                <i class="fas fa-university"></i> Thông tin chuyển khoản ngân hàng
                            </div>
                            <div style="display:flex;gap:1rem;align-items:flex-start;">
                                <img id="bankQr"
                                     src="https://img.vietqr.io/image/MB-7369786789-qr_only.png?amount=<?php echo $price; ?>&addInfo=<?php echo urlencode($reg['student_code']); ?>&accountName=LU+DANG+HUNG"
                                     alt="QR MB Bank"
                                     style="width:120px;height:120px;border-radius:10px;border:2px solid #fde68a;padding:3px;background:#fff;flex-shrink:0;">
                                <div style="display:grid;gap:.45rem;font-size:.85rem;flex:1;">
                                    <div style="display:flex;gap:.5rem;">
                                        <span style="color:#78716c;min-width:115px;">Ngân hàng</span>
                                        <strong>MB Bank</strong>
                                    </div>
                                    <div style="display:flex;gap:.5rem;">
                                        <span style="color:#78716c;min-width:115px;">Số tài khoản</span>
                                        <strong style="font-family:monospace;color:#6366f1;">7369786789</strong>
                                    </div>
                                    <div style="display:flex;gap:.5rem;">
                                        <span style="color:#78716c;min-width:115px;">Chủ tài khoản</span>
                                        <strong>LU DANG HUNG</strong>
                                    </div>
                                    <div style="display:flex;gap:.5rem;">
                                        <span style="color:#78716c;min-width:115px;">Số tiền</span>
                                        <strong style="color:#6366f1;"><?php echo number_format($price); ?>đ</strong>
                                    </div>
                                    <div style="display:flex;gap:.5rem;">
                                        <span style="color:#78716c;min-width:115px;">Nội dung CK</span>
                                        <strong style="font-family:monospace;color:#6366f1;"><?php echo escape($reg['student_code']); ?></strong>
                                    </div>
                                </div>
                            </div>
                            <div style="margin-top:.7rem;background:#fef9c3;border-radius:8px;padding:.5rem .8rem;font-size:.78rem;color:#854d0e;">
                                <i class="fas fa-info-circle me-1"></i>
                                Ghi đúng nội dung chuyển khoản để được xác nhận tự động
                            </div>
                            <!-- Hướng dẫn tự động -->
                            <div style="margin-top:.85rem;padding:.65rem .9rem;background:#fef9c3;border-radius:10px;font-size:.82rem;color:#92400e;">
                                <i class="fas fa-magic me-1"></i> Hệ thống <strong>tự động xác nhận</strong> khi nhận được tiền về MB Bank.
                            </div>
                        </div>

                        <button type="submit" class="btn-pay" id="payBtn">
                            <i class="fas fa-check-circle me-2"></i>Xác nhận thanh toán
                        </button>

                        <!-- Waiting box khi đang chờ thanh toán chuyển khoản -->
                        <div id="trainingWaitingBox" style="display:none;"></div>

                        <div style="text-align:center;margin-top:.8rem;">
                            <small style="color:#9ca3af;font-size:.78rem;">
                                <i class="fas fa-shield-alt me-1"></i>
                                Thông tin của bạn được bảo mật. Đăng ký có hiệu lực ngay sau khi xác nhận.
                            </small>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Cột phải: tổng tiền -->
        <div class="col-lg-5">
            <div class="tc-card" style="position:sticky;top:80px;">
                <div class="tc-card-header"><i class="fas fa-receipt"></i> Tóm tắt đơn hàng</div>
                <div class="tc-card-body">

                    <!-- Course badge -->
                    <div style="background:linear-gradient(135deg,#f8f4ff,#ede9fe);border-radius:14px;padding:1.2rem;margin-bottom:1.2rem;border:1px solid rgba(99,102,241,.15);">
                        <div style="display:flex;align-items:center;gap:.8rem;">
                            <div style="width:44px;height:44px;background:linear-gradient(135deg,#6366f1,#8b5cf6);border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <i class="fas fa-graduation-cap" style="color:#fff;"></i>
                            </div>
                            <div>
                                <div style="font-weight:800;color:#374151;"><?php echo escape($label); ?></div>
                                <div style="font-size:.78rem;color:#6b7280;">
                                    <?php
                                    $durations = ['beginner'=>'3 tháng · 12 buổi','intermediate'=>'4 tháng · 16 buổi','advanced'=>'5 tháng · 20 buổi'];
                                    echo $durations[$reg['course']] ?? '';
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="total-box">
                        <div class="total-line">
                            <span class="tl">Học phí khóa học</span>
                            <span><?php echo number_format($price); ?>đ</span>
                        </div>
                        <div class="total-line">
                            <span class="tl">Phí HLV kèm 1-1</span>
                            <span style="color:#4ade80;">Bao gồm</span>
                        </div>
                        <div class="total-line">
                            <span class="tl">Phí đăng ký</span>
                            <span style="color:#4ade80;">Miễn phí</span>
                        </div>
                        <div class="total-final">
                            <span style="color:rgba(255,255,255,.7);font-weight:700;">Tổng thanh toán</span>
                            <span class="amount"><?php echo number_format($price); ?>đ</span>
                        </div>
                    </div>

                    <div style="margin-top:1rem;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:12px;padding:1rem;">
                        <div style="font-weight:700;color:#15803d;font-size:.85rem;margin-bottom:.5rem;">
                            <i class="fas fa-gift me-2"></i>Quyền lợi khóa học
                        </div>
                        <ul style="list-style:none;padding:0;margin:0;font-size:.8rem;color:#166534;">
                            <li class="py-1"><i class="fas fa-check me-2" style="color:#16a34a;"></i>HLV kèm 1-1 chuyên nghiệp</li>
                            <li class="py-1"><i class="fas fa-check me-2" style="color:#16a34a;"></i>Thẻ học viên + mã QR xác nhận</li>
                            <li class="py-1"><i class="fas fa-check me-2" style="color:#16a34a;"></i>Lịch học linh hoạt 3 buổi/tuần</li>
                            <li class="py-1"><i class="fas fa-check me-2" style="color:#16a34a;"></i>Hỗ trợ tư vấn 24/7</li>
                        </ul>
                    </div>

                    <div class="mt-3 text-center">
                        <a href="training.php" style="color:#9ca3af;font-size:.82rem;text-decoration:none;">
                            <i class="fas fa-arrow-left me-1"></i> Quay lại chỉnh sửa
                        </a>
                    </div>
                </div>
            </div>
        </div>

    </div>
    <?php endif; ?>

</div>
</div>

<script>
function selectPayMethod(method) {
    document.querySelectorAll('.pay-method').forEach(el => el.classList.remove('selected'));
    document.getElementById('pm-' + method)?.classList.add('selected');
    document.getElementById('momoPanel').style.display = method === 'momo'  ? 'block' : 'none';
    document.getElementById('bankPanel').style.display = method === 'vnpay' ? 'block' : 'none';
}

function updatePayBtn() {
    // Không cần nữa - nút luôn enable
}

// Xử lý submit form - nếu chuyển khoản thì intercept
document.getElementById('payForm')?.addEventListener('submit', function(e) {
    const method = document.querySelector('input[name="payment_method"]:checked')?.value || 'cash';
    if (method === 'cash') return; // submit bình thường

    // Chặn submit mặc định cho MoMo/Bank
    e.preventDefault();

    const btn = document.getElementById('payBtn');
    if (btn) {
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" style="width:14px;height:14px;"></span>Đang tạo đơn...';
        btn.disabled = true;
    }

    // Submit qua AJAX
    const formData = new FormData(this);
    fetch('', { method: 'POST', body: formData })
    .then(r => r.text())
    .then(() => {
        // Đổi nút thành đang chờ
        if (btn) {
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" style="width:14px;height:14px;border-width:2px;"></span>Đang chờ thanh toán...';
            btn.disabled = true;
        }

        const code = '<?php echo addslashes($reg['student_code']); ?>';

        if (typeof PaymentPolling !== 'undefined') {
            PaymentPolling.showWaitingBox('trainingWaitingBox', code);
            PaymentPolling.start('training_code=' + code, function() {
                PaymentPolling.showSuccessToast(
                    'Đăng ký khóa học thành công! Chào mừng bạn.',
                    'training.php',
                    2000
                );
            }, 'trainingWaitingBox');
        }
    })
    .catch(err => {
        if (btn) { btn.innerHTML = '<i class="fas fa-check-circle me-2"></i>Xác nhận thanh toán'; btn.disabled = false; }
    });
});

// Init
<?php if ($waiting_transfer): ?>
// Đang chờ thanh toán chuyển khoản từ lần submit trước
(function() {
    const code = '<?php echo addslashes($reg['student_code']); ?>';
    const btn  = document.getElementById('payBtn');
    if (btn) {
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" style="width:14px;height:14px;border-width:2px;"></span>Đang chờ thanh toán...';
        btn.disabled = true;
    }
    if (typeof PaymentPolling !== 'undefined') {
        PaymentPolling.showWaitingBox('trainingWaitingBox', code);
        PaymentPolling.start('training_code=' + code, function() {
            PaymentPolling.showSuccessToast('Đăng ký khóa học thành công!', 'training.php', 2000);
        }, 'trainingWaitingBox');
    }
})();
<?php endif; ?>
</script>

<style>
@keyframes fadeIn { from { opacity:0; transform:translateY(-6px); } to { opacity:1; transform:translateY(0); } }
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
<script src="assets/js/payment-polling.js"></script>
