<?php
/**
 * API: Đặt sân định kỳ (Recurring Booking)
 *
 * POST { preview: 1, ... }  → Xem trước danh sách ngày, không lưu DB
 * POST { preview: 0, ... }  → Xác nhận tạo booking hàng loạt
 *
 * Params:
 *   court_id      INT
 *   start_time    string  "08:00"
 *   duration      INT     giờ
 *   start_date    string  "2026-08-01"
 *   end_date      string  "2026-08-31"
 *   days_of_week  array   [1,2,3,4,5,6,7]  (1=T2, 7=CN)
 *   payment_method string  cash|momo|vnpay
 *   notes         string
 *   preview       INT     1|0
 */
ob_start();
ini_set('display_errors', 0);
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/functions.php';

function rbError($msg, $code = 400) {
    ob_end_clean(); http_response_code($code);
    echo json_encode(['success' => false, 'error' => $msg]); exit;
}
function rbOk($data) {
    ob_end_clean(); echo json_encode($data); exit;
}

if (!isLoggedIn()) rbError('Bạn cần đăng nhập.', 401);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') rbError('Method not allowed.', 405);

$user_id        = (int)$_SESSION['user_id'];
$court_id       = (int)($_POST['court_id'] ?? 0);
$start_time     = trim($_POST['start_time'] ?? '');
$duration       = max(1, (int)($_POST['duration'] ?? 1));
$start_date     = trim($_POST['start_date'] ?? '');
$end_date       = trim($_POST['end_date'] ?? '');
$days_raw       = $_POST['days_of_week'] ?? [];
$payment_method = strtolower(trim($_POST['payment_method'] ?? 'cash'));
$notes          = trim($_POST['notes'] ?? '');
$preview        = (int)($_POST['preview'] ?? 1);

// ── Validate ──────────────────────────────────────────────────────────────
if (!$court_id)    rbError('Chưa chọn sân.');
if (!$start_time)  rbError('Chưa chọn giờ.');
if (!$start_date || !$end_date) rbError('Chưa chọn ngày bắt đầu / kết thúc.');
if (empty($days_raw)) rbError('Chưa chọn ngày trong tuần.');

$days = array_map('intval', (array)$days_raw);
$days = array_filter($days, fn($d) => $d >= 1 && $d <= 7);
if (empty($days)) rbError('Ngày trong tuần không hợp lệ.');

$dtStart = DateTime::createFromFormat('Y-m-d', $start_date);
$dtEnd   = DateTime::createFromFormat('Y-m-d', $end_date);
if (!$dtStart || !$dtEnd) rbError('Ngày không hợp lệ.');
if ($dtEnd < $dtStart)    rbError('Ngày kết thúc phải sau ngày bắt đầu.');

$diffDays = $dtStart->diff($dtEnd)->days;
if ($diffDays > 366) rbError('Khoảng thời gian tối đa là 1 năm.');

// Tính giờ kết thúc
$dtTime = DateTime::createFromFormat('H:i', $start_time);
if (!$dtTime) $dtTime = DateTime::createFromFormat('H:i:s', $start_time);
if (!$dtTime) rbError('Giờ không hợp lệ.');
$dtEndTime = clone $dtTime; $dtEndTime->modify("+{$duration} hour");
$start_time_full = $dtTime->format('H:i:00');
$end_time_full   = $dtEndTime->format('H:i:00');

$court = getCourtById($court_id);
if (!$court) rbError('Sân không tồn tại.');

// ── Tạo danh sách ngày ────────────────────────────────────────────────────
$datesAvailable  = [];
$datesConflict   = [];

$current = clone $dtStart;
while ($current <= $dtEnd) {
    $dow = (int)$current->format('N'); // 1=T2, 7=CN
    if (in_array($dow, $days)) {
        $dateStr = $current->format('Y-m-d');
        if (isSlotAvailable($court_id, $dateStr, $start_time_full, $end_time_full)) {
            $datesAvailable[] = $dateStr;
        } else {
            $datesConflict[]  = $dateStr;
        }
    }
    $current->modify('+1 day');
}

if (empty($datesAvailable) && empty($datesConflict)) {
    rbError('Không có ngày nào phù hợp trong khoảng thời gian đã chọn.');
}

// ── Tính giá ──────────────────────────────────────────────────────────────
$membership       = checkMemberBenefit($user_id);
$price_per_session = 0;
$use_member_price  = false;

if ($membership) {
    $calc = calcMemberBookingPrice($court['price_per_hour'], $duration, $membership);
    $price_per_session = $calc['price'];
    $use_member_price  = $calc['used_ticket'];
} else {
    $price_per_session = $court['price_per_hour'] * $duration;
}

$total_price = $price_per_session * count($datesAvailable);

// ── Preview → trả thông tin, không lưu DB ─────────────────────────────────
if ($preview) {
    $dow_labels = [1=>'T2',2=>'T3',3=>'T4',4=>'T5',5=>'T6',6=>'T7',7=>'CN'];
    rbOk([
        'success'            => true,
        'preview'            => true,
        'court_name'         => $court['name'],
        'start_time'         => $start_time_full,
        'end_time'           => $end_time_full,
        'price_per_session'  => $price_per_session,
        'dates_available'    => $datesAvailable,
        'dates_conflict'     => $datesConflict,
        'total_sessions'     => count($datesAvailable),
        'skipped_sessions'   => count($datesConflict),
        'total_price'        => $total_price,
        'days_label'         => implode(', ', array_map(fn($d) => $dow_labels[$d], $days)),
        'use_member_price'   => $use_member_price,
    ]);
}

// ── Confirm → lưu DB ─────────────────────────────────────────────────────
if (empty($datesAvailable)) {
    rbError('Tất cả khung giờ đã bị đặt. Không thể tạo booking định kỳ.');
}

// Tạo bảng recurring_groups nếu chưa có
$mysqli->query("CREATE TABLE IF NOT EXISTS recurring_groups (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    user_id        INT NOT NULL,
    court_id       INT NOT NULL,
    start_time     TIME NOT NULL,
    end_time       TIME NOT NULL,
    days_of_week   VARCHAR(20) NOT NULL,
    start_date     DATE NOT NULL,
    end_date       DATE NOT NULL,
    price_per_session INT NOT NULL DEFAULT 0,
    total_price    INT NOT NULL DEFAULT 0,
    payment_method VARCHAR(30) NOT NULL,
    payment_status VARCHAR(30) DEFAULT 'pending',
    status         VARCHAR(20) DEFAULT 'active',
    notes          TEXT,
    created_at     DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user  (user_id),
    INDEX idx_court (court_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Thêm cột recurring_group_id vào bookings nếu chưa có
$chk = $mysqli->query("SHOW COLUMNS FROM bookings LIKE 'recurring_group_id'");
if ($chk && $chk->num_rows === 0) {
    $mysqli->query("ALTER TABLE bookings ADD COLUMN recurring_group_id INT NULL");
}
$chkT = $mysqli->query("SHOW COLUMNS FROM bookings LIKE 'booking_type'");
if ($chkT && $chkT->num_rows === 0) {
    $mysqli->query("ALTER TABLE bookings ADD COLUMN booking_type VARCHAR(20) DEFAULT 'single'");
}
$chkP = $mysqli->query("SHOW COLUMNS FROM bookings LIKE 'parent_booking_id'");
if ($chkP && $chkP->num_rows === 0) {
    $mysqli->query("ALTER TABLE bookings ADD COLUMN parent_booking_id INT NULL");
}

$mysqli->begin_transaction();
try {
    $days_str       = implode(',', $days);
    $payment_status = ($payment_method === 'cash') ? 'unpaid' : 'pending';

    // Insert recurring_group
    $rg = $mysqli->prepare('INSERT INTO recurring_groups (user_id,court_id,start_time,end_time,days_of_week,start_date,end_date,price_per_session,total_price,payment_method,payment_status,notes) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)');
    $rg->bind_param('iisssssiisss', $user_id,$court_id,$start_time_full,$end_time_full,$days_str,$start_date,$end_date,$price_per_session,$total_price,$payment_method,$payment_status,$notes);
    $rg->execute();
    $group_id = $mysqli->insert_id;
    $rg->close();

    // Đảm bảo cột discount_amount tồn tại
    $chkD = $mysqli->query("SHOW COLUMNS FROM bookings LIKE 'discount_amount'");
    if ($chkD && $chkD->num_rows === 0) {
        $mysqli->query("ALTER TABLE bookings ADD COLUMN discount_amount INT NOT NULL DEFAULT 0");
        $mysqli->query("ALTER TABLE bookings ADD COLUMN promo_applied VARCHAR(150) DEFAULT NULL");
    }

    // Insert từng booking
    $booking_ids  = [];
    $first_id     = null;
    $bs = $mysqli->prepare(
        'INSERT INTO bookings (user_id,court_id,booking_date,start_time,end_time,total_price,payment_method,payment_status,status,notes,booking_type,recurring_group_id,discount_amount,promo_applied)
         VALUES (?,?,?,?,?,?,?,?,?,?,?,?,0,?)'
    );

    foreach ($datesAvailable as $date) {
        // Re-check slot (race condition guard)
        if (!isSlotAvailable($court_id, $date, $start_time_full, $end_time_full)) continue;

        $session_price = $price_per_session;
        $bt = 'recurring';
        $ps = $payment_status;
        $st = 'confirmed';
        $promo = '';

        $bs->bind_param('iissssissssiss',
            $user_id, $court_id, $date,
            $start_time_full, $end_time_full,
            $session_price, $payment_method, $ps, $st, $notes,
            $bt, $group_id, $promo
        );
        $bs->execute();
        $bid = $mysqli->insert_id;
        $booking_ids[] = $bid;
        if (!$first_id) $first_id = $bid;

        // Trừ vé hội viên nếu dùng
        if ($use_member_price && $membership) {
            useMemberTicket((int)$membership['id'], $user_id, $bid, 'Đặt sân định kỳ: '.$court['name'].' '.$date);
        }
    }
    $bs->close();

    if (empty($booking_ids)) {
        $mysqli->rollback();
        rbError('Không thể tạo booking — tất cả slot đã bị đặt trong khi xử lý.');
    }

    // Gửi notification cho booking đầu tiên
    if ($payment_method === 'cash' && $first_id) {
        try {
            require_once __DIR__ . '/../includes/notification-system.php';
            $ns = new NotificationSystem();
            $ns->notifyBookingConfirmed($first_id);
        } catch (Exception $e) {}
    }

    $mysqli->commit();

    $transfer_ref = 'DATSAN' . str_pad($group_id, 5, '0', STR_PAD_LEFT) . 'G';

    rbOk([
        'success'          => true,
        'group_id'         => $group_id,
        'booking_ids'      => $booking_ids,
        'first_booking_id' => $first_id,
        'total_sessions'   => count($booking_ids),
        'total_price'      => $price_per_session * count($booking_ids),
        'price_per_session'=> $price_per_session,
        'payment_method'   => $payment_method,
        'payment_status'   => $payment_status,
        'transfer_ref'     => $transfer_ref,
        'message'          => 'Đã tạo ' . count($booking_ids) . ' lịch đặt sân thành công!',
    ]);

} catch (Exception $e) {
    $mysqli->rollback();
    rbError('Lỗi hệ thống: ' . $e->getMessage(), 500);
}
