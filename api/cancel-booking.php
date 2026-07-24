<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/functions.php';

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập.']);
    exit;
}

$input     = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$booking_id = intval($input['booking_id'] ?? 0);
$user_id    = (int)$_SESSION['user_id'];

if (!$booking_id) {
    echo json_encode(['success' => false, 'message' => 'Mã booking không hợp lệ.']);
    exit;
}

$stmt = $mysqli->prepare(
    'SELECT id, status, booking_date, start_time FROM bookings WHERE id = ? AND user_id = ?'
);
$stmt->bind_param('ii', $booking_id, $user_id);
$stmt->execute();
$b = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$b) {
    echo json_encode(['success' => false, 'message' => 'Không tìm thấy booking.']);
    exit;
}

if ($b['status'] === 'cancelled') {
    echo json_encode(['success' => false, 'message' => 'Booking đã được huỷ trước đó.']);
    exit;
}

// Chỉ được huỷ nếu booking_date còn trong tương lai
$bookingDT = strtotime($b['booking_date'] . ' ' . $b['start_time']);
if ($bookingDT <= time()) {
    echo json_encode(['success' => false, 'message' => 'Không thể huỷ booking đã qua hoặc đang diễn ra.']);
    exit;
}

$upd = $mysqli->prepare(
    "UPDATE bookings SET status='cancelled', cancelled_at=NOW() WHERE id=? AND user_id=?"
);
// Thêm cột nếu chưa có
$chk = $mysqli->query("SHOW COLUMNS FROM bookings LIKE 'cancelled_at'");
if ($chk && $chk->num_rows === 0) {
    $mysqli->query("ALTER TABLE bookings ADD COLUMN cancelled_at DATETIME DEFAULT NULL");
}

$upd = $mysqli->prepare("UPDATE bookings SET status='cancelled', cancelled_at=NOW() WHERE id=? AND user_id=?");
$upd->bind_param('ii', $booking_id, $user_id);
$upd->execute();
$upd->close();

echo json_encode(['success' => true, 'message' => 'Huỷ booking thành công.']);
