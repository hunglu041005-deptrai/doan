<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/payment.php';
require_once __DIR__ . '/includes/email.php';

$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
          strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

// Kiểm tra đăng nhập
if (!isLoggedIn()) {
    if ($isAjax) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Bạn cần đăng nhập để đặt sân.', 'redirect' => 'login.php']);
        exit;
    }
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('index.php');
}

$court_id      = intval($_POST['court_id'] ?? 0);
$date          = $_POST['booking_date'] ?? '';
$start_time    = $_POST['start_time'] ?? '';
$duration      = intval($_POST['duration'] ?? 1);
$payment_method = strtolower($_POST['payment_method'] ?? 'cash');
$notes         = trim($_POST['notes'] ?? '');

$error = '';

$court = getCourtById($court_id);
if (!$court) $error = 'Sân không tồn tại.';

if (!$date || !$start_time || !$duration || !$payment_method) {
    $error = 'Vui lòng cung cấp đầy đủ thông tin đặt sân.';
}

if (!$error) {
    $startDateTime = DateTime::createFromFormat('Y-m-d H:i', $date . ' ' . $start_time);
    if (!$startDateTime) {
        // Thử format H:i:s
        $startDateTime = DateTime::createFromFormat('Y-m-d H:i:s', $date . ' ' . $start_time);
    }
    if (!$startDateTime) {
        $error = 'Ngày giờ không hợp lệ: ' . $date . ' ' . $start_time;
    } else {
        $endDateTime = clone $startDateTime;
        $endDateTime->modify("+{$duration} hour");
        $end_time_full   = $endDateTime->format('H:i:00');
        $start_time_full = $startDateTime->format('H:i:00');
    }
}

if (!$error && !isSlotAvailable($court_id, $date, $start_time_full, $end_time_full)) {
    $error = 'Khung giờ này đã được đặt. Vui lòng chọn khung giờ khác.';
}

if ($error) {
    if ($isAjax) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => $error]);
        exit;
    }
    $_SESSION['booking_error'] = $error;
    redirect("court.php?id={$court_id}&date={$date}");
}

// Lưu booking vào database
$user_id        = (int) $_SESSION['user_id'];

// ===== MEMBERSHIP BENEFIT: giá 80K cố định + trừ vé =====
$membership       = checkMemberBenefit($user_id);
$use_member_price = false;
$member_discount  = 0;

if ($membership) {
    $priceCalc        = calcMemberBookingPrice($court['price_per_hour'], $duration, $membership);
    $total_price      = $priceCalc['price'];
    $use_member_price = $priceCalc['used_ticket'];
    $member_discount  = $priceCalc['discount'];
} else {
    $total_price = $court['price_per_hour'] * $duration;
}

// ===== ƯU ĐÃI ĐẶC BIỆT (promotions) =====
$promo_applied    = '';
$promo_discount   = 0;
$original_price   = $total_price; // Giá trước khi áp ưu đãi

// Chỉ áp ưu đãi khi KHÔNG dùng giá hội viên
if (!$use_member_price) {
    // Tạo bảng nếu chưa có
    $mysqli->query("CREATE TABLE IF NOT EXISTS promotions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(150) NOT NULL,
        description VARCHAR(255),
        color_from VARCHAR(20) DEFAULT '#f472b6',
        color_to VARCHAR(20) DEFAULT '#ef4444',
        text_color VARCHAR(20) DEFAULT '#fff',
        discount_pct TINYINT DEFAULT 0,
        time_start TIME DEFAULT NULL,
        time_end TIME DEFAULT NULL,
        apply_weekend TINYINT DEFAULT 0,
        apply_newuser TINYINT DEFAULT 0,
        status TINYINT DEFAULT 1,
        sort_order INT DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $promos = $mysqli->query(
        'SELECT * FROM promotions WHERE status=1 AND discount_pct > 0 ORDER BY discount_pct DESC'
    );

    $bookingHour    = (int)date('H', strtotime($start_time_full));
    $bookingMinute  = (int)date('i', strtotime($start_time_full));
    $bookingTime    = $bookingHour * 60 + $bookingMinute; // phút trong ngày
    $dayOfWeek      = (int)date('N', strtotime($date));   // 1=Thứ 2, 7=CN
    $isWeekend      = in_array($dayOfWeek, [6, 7]);

    // Kiểm tra thành viên mới (chưa có booking nào trước đó)
    $prevStmt = $mysqli->prepare('SELECT COUNT(*) AS cnt FROM bookings WHERE user_id = ?');
    $prevStmt->bind_param('i', $user_id);
    $prevStmt->execute();
    $isNewUser = ($prevStmt->get_result()->fetch_assoc()['cnt'] ?? 1) === 0;
    $prevStmt->close();

    if ($promos) {
        while ($promo = $promos->fetch_assoc()) {
            $applicable = false;

            // Kiểm tra khung giờ
            if ($promo['time_start'] && $promo['time_end']) {
                $pStart = (int)substr($promo['time_start'], 0, 2) * 60 + (int)substr($promo['time_start'], 3, 2);
                $pEnd   = (int)substr($promo['time_end'],   0, 2) * 60 + (int)substr($promo['time_end'],   3, 2);
                if ($bookingTime >= $pStart && $bookingTime < $pEnd) {
                    $applicable = true;
                }
            } else {
                $applicable = true; // Không giới hạn giờ
            }

            // Kiểm tra điều kiện cuối tuần
            if ($promo['apply_weekend'] && !$isWeekend) {
                $applicable = false;
            }

            // Kiểm tra thành viên mới
            if ($promo['apply_newuser'] && !$isNewUser) {
                $applicable = false;
            }

            if ($applicable && $promo['discount_pct'] > 0) {
                $discount_pct   = min(100, (int)$promo['discount_pct']);
                $promo_discount = (int)round($original_price * $discount_pct / 100);
                $total_price    = max(0, $original_price - $promo_discount);
                $promo_applied  = $promo['title'];
                break; // Chỉ áp ưu đãi tốt nhất
            }
        }
    }
}

// Đảm bảo cột discount_amount và promo_applied tồn tại
$chkD = $mysqli->query("SHOW COLUMNS FROM bookings LIKE 'discount_amount'");
if ($chkD && $chkD->num_rows === 0) {
    $mysqli->query("ALTER TABLE bookings ADD COLUMN discount_amount INT NOT NULL DEFAULT 0");
}
$chkP = $mysqli->query("SHOW COLUMNS FROM bookings LIKE 'promo_applied'");
if ($chkP && $chkP->num_rows === 0) {
    $mysqli->query("ALTER TABLE bookings ADD COLUMN promo_applied VARCHAR(150) DEFAULT NULL");
}

$status         = 'confirmed';
$payment_status = ($payment_method === 'cash') ? 'unpaid' : 'pending';

$stmt = $mysqli->prepare(
    'INSERT INTO bookings (user_id, court_id, booking_date, start_time, end_time, total_price, payment_method, payment_status, status, notes, discount_amount, promo_applied)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
);
$stmt->bind_param('iisssissssis',
    $user_id, $court_id, $date,
    $start_time_full, $end_time_full,
    $total_price, $payment_method, $payment_status, $status, $notes,
    $promo_discount, $promo_applied
);

if (!$stmt->execute()) {
    $error = 'Lỗi khi lưu booking: ' . $stmt->error;
    $stmt->close();
    if ($isAjax) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $error]);
        exit;
    }
    $_SESSION['booking_error'] = $error;
    redirect("court.php?id={$court_id}&date={$date}");
}

$booking_id = $stmt->insert_id;
$stmt->close();

// ===== TRỪ VÉ HỘI VIÊN nếu áp giá thành viên =====
if ($use_member_price && $membership) {
    useMemberTicket(
        (int)$membership['id'],
        $user_id,
        $booking_id,
        'Đặt sân: ' . $court['name'] . ' ' . $date . ' ' . $start_time_full
    );
}

// Gửi thông báo đặt sân thành công - chỉ khi tiền mặt (cash), còn chuyển khoản chờ webhook xác nhận
if ($payment_method === 'cash') {
    try {
        require_once __DIR__ . '/includes/notification-system.php';
        $ns = new NotificationSystem();
        $ns->notifyBookingConfirmed($booking_id);
    } catch (Exception $e) { /* Không để lỗi ảnh hưởng flow */ }
}

// Xử lý theo phương thức thanh toán
// Nếu là AJAX request (từ booking-online.js), luôn trả JSON thành công
// Frontend đã xử lý QR/xác nhận chuyển khoản, backend chỉ cần lưu DB
if ($isAjax) {
    // Với momo/vnpay: payment_status = 'pending', cần chờ xác nhận thực từ SePay webhook
    // Với cash: payment_status = 'unpaid'
    $message = ($payment_method === 'cash')
        ? 'Đặt sân thành công! Vui lòng thanh toán khi đến sân.'
        : 'Đơn đặt sân đã tạo! Vui lòng chuyển khoản và nhấn "Kiểm tra thanh toán".';

    // Tạo nội dung chuyển khoản có mã booking để SePay parse
    $transfer_ref = 'DATSAN' . str_pad($booking_id, 5, '0', STR_PAD_LEFT);

    echo json_encode([
        'success'        => true,
        'booking_id'     => $booking_id,
        'message'        => $message,
        'court_name'     => $court['name'],
        'booking_date'   => $date,
        'start_time'     => $start_time_full,
        'end_time'       => $end_time_full,
        'total_price'    => $total_price,
        'original_price' => $original_price ?? $total_price,
        'discount_amount'=> $promo_discount ?? 0,
        'promo_applied'  => $promo_applied ?? '',
        'payment_method' => $payment_method,
        'transfer_ref'   => $transfer_ref,
        'needs_payment'  => in_array($payment_method, ['momo', 'vnpay']),
        'redirect'       => 'booking-history.php'
    ]);
    exit;
}

// Non-AJAX (direct form submit fallback)
if ($payment_method === 'vnpay' && class_exists('PaymentGateway')) {
    $vnpay_url = PaymentGateway::generateVNPayLink($booking_id, $total_price, 'Đặt sân - ' . $court['name'], $user_id);
    redirect($vnpay_url);

} elseif ($payment_method === 'momo' && class_exists('PaymentGateway')) {
    $_SESSION['momo_data'] = PaymentGateway::generateMoMoLink($booking_id, $total_price, 'Đặt sân - ' . $court['name'], $user_id);
    redirect('payment-momo.php?booking_id=' . $booking_id);

} else {
    $_SESSION['booking_success'] = 'Đặt sân thành công! Vui lòng thanh toán khi đến sân.';
    redirect('booking-history.php');
}
