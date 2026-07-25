<?php
/**
 * API: Xác nhận thanh toán thủ công
 * Dùng khi webhook SePay không reach được (localhost / chưa có ngrok)
 * 
 * POST { type: 'booking'|'membership'|'order', id: int|string }
 * 
 * Chỉ cho phép khi payment_status = 'pending' (đã tạo đơn, chưa xác nhận)
 * Không cho phép confirm đơn đã cancelled/paid
 */
ob_start();
ini_set('display_errors', 0);
header('Content-Type: application/json');
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/functions.php';

function respond($ok, $msg, $code = 200) {
    ob_end_clean();
    http_response_code($code);
    echo json_encode(['success' => $ok, 'message' => $msg]);
    exit;
}

if (!isLoggedIn()) respond(false, 'Unauthorized', 401);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') respond(false, 'Method not allowed', 405);

$body = json_decode(file_get_contents('php://input'), true);
$type = $body['type'] ?? $_POST['type'] ?? '';
$id   = $body['id']   ?? $_POST['id']   ?? 0;
$uid  = (int)$_SESSION['user_id'];

if (!$type || !$id) respond(false, 'Missing type or id', 400);

switch ($type) {
    case 'booking':
        $bid = (int)$id;
        $st  = $mysqli->prepare('SELECT id, payment_status, user_id FROM bookings WHERE id=? LIMIT 1');
        $st->bind_param('i', $bid); $st->execute();
        $row = $st->get_result()->fetch_assoc(); $st->close();

        if (!$row) respond(false, 'Booking not found', 404);
        // Chỉ owner hoặc admin mới confirm được
        if ($row['user_id'] != $uid && !isAdmin()) respond(false, 'Forbidden', 403);
        if ($row['payment_status'] === 'paid') respond(true, 'Already paid');
        if (!in_array($row['payment_status'], ['pending', 'unpaid'])) respond(false, 'Cannot confirm this booking', 400);

        $up = $mysqli->prepare("UPDATE bookings SET payment_status='paid', payment_method='bank_transfer' WHERE id=?");
        $up->bind_param('i', $bid); $up->execute(); $up->close();

        // Gửi notification
        try {
            require_once __DIR__ . '/../includes/notification-system.php';
            $ns = new NotificationSystem();
            $ns->notifyBookingConfirmed($bid);
        } catch (Exception $e) {}

        respond(true, 'Booking confirmed');

    case 'membership':
        $mid = (int)$id;
        $st  = $mysqli->prepare('SELECT id, payment_status, user_id FROM memberships WHERE id=? LIMIT 1');
        $st->bind_param('i', $mid); $st->execute();
        $row = $st->get_result()->fetch_assoc(); $st->close();

        if (!$row) respond(false, 'Membership not found', 404);
        if ($row['user_id'] != $uid && !isAdmin()) respond(false, 'Forbidden', 403);
        if ($row['payment_status'] === 'paid') respond(true, 'Already paid');
        if (!in_array($row['payment_status'], ['pending', 'pending_payment'])) respond(false, 'Cannot confirm', 400);

        $up = $mysqli->prepare("UPDATE memberships SET payment_status='paid', status='active', start_date=CURDATE(), end_date=DATE_ADD(CURDATE(), INTERVAL months MONTH) WHERE id=?");
        $up->bind_param('i', $mid); $up->execute(); $up->close();

        // Notification
        try {
            $mf = $mysqli->prepare('SELECT user_id, member_code, plan_name, plan_detail, end_date FROM memberships WHERE id=?');
            $mf->bind_param('i', $mid); $mf->execute();
            $md = $mf->get_result()->fetch_assoc(); $mf->close();
            if ($md) {
                require_once __DIR__ . '/../includes/notification-system.php';
                $ns = new NotificationSystem();
                $ns->notifyMembershipActivated($md['user_id'], $md['member_code'], $md['plan_name'].': '.$md['plan_detail'], $md['end_date']);
            }
        } catch (Exception $e) {}

        respond(true, 'Membership activated');

    case 'order':
        $oid = (int)$id;
        $st  = $mysqli->prepare('SELECT id, payment_status, user_id FROM orders WHERE id=? LIMIT 1');
        $st->bind_param('i', $oid); $st->execute();
        $row = $st->get_result()->fetch_assoc(); $st->close();

        if (!$row) respond(false, 'Order not found', 404);
        if ($row['user_id'] != $uid && !isAdmin()) respond(false, 'Forbidden', 403);
        if ($row['payment_status'] === 'paid') respond(true, 'Already paid');

        $up = $mysqli->prepare("UPDATE orders SET payment_status='paid', status='confirmed' WHERE id=?");
        $up->bind_param('i', $oid); $up->execute(); $up->close();

        respond(true, 'Order confirmed');

    default:
        respond(false, 'Unknown type', 400);
}
