<?php
/**
 * API: Kiểm tra trạng thái thanh toán - dùng cho tất cả loại đơn
 * 
 * GET ?ref=DATSAN00027          → check theo nội dung CK
 * GET ?booking_id=123           → check booking
 * GET ?order_id=123             → check shop order
 * GET ?order_number=ORD25xxxxx  → check shop order theo số đơn
 * GET ?training_code=HV2025XXXX → check khóa học
 * GET ?membership_id=123        → check hội viên
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$uid = (int)$_SESSION['user_id'];

// ── Check booking ──────────────────────────────────────────────────────────
if (!empty($_GET['booking_id'])) {
    $bid = (int)$_GET['booking_id'];
    $st  = $mysqli->prepare('SELECT id, payment_status FROM bookings WHERE id=? AND user_id=? LIMIT 1');
    $st->bind_param('ii', $bid, $uid);
    $st->execute();
    $row = $st->get_result()->fetch_assoc();
    $st->close();
    echo json_encode([
        'success' => true,
        'paid'    => ($row && $row['payment_status'] === 'paid'),
        'status'  => $row['payment_status'] ?? 'unknown',
    ]);
    exit;
}

// ── Check shop order ───────────────────────────────────────────────────────
if (!empty($_GET['order_id'])) {
    $oid = (int)$_GET['order_id'];
    $st  = $mysqli->prepare('SELECT id, payment_status FROM orders WHERE id=? AND user_id=? LIMIT 1');
    $st->bind_param('ii', $oid, $uid);
    $st->execute();
    $row = $st->get_result()->fetch_assoc();
    $st->close();
    echo json_encode([
        'success' => true,
        'paid'    => ($row && $row['payment_status'] === 'paid'),
        'status'  => $row['payment_status'] ?? 'unknown',
    ]);
    exit;
}

if (!empty($_GET['order_number'])) {
    $onum = $_GET['order_number'];
    $st   = $mysqli->prepare('SELECT id, payment_status FROM orders WHERE order_number=? AND user_id=? LIMIT 1');
    $st->bind_param('si', $onum, $uid);
    $st->execute();
    $row = $st->get_result()->fetch_assoc();
    $st->close();
    echo json_encode([
        'success' => true,
        'paid'    => ($row && $row['payment_status'] === 'paid'),
        'status'  => $row['payment_status'] ?? 'unknown',
    ]);
    exit;
}

// ── Check khóa học ─────────────────────────────────────────────────────────
if (!empty($_GET['training_code'])) {
    $code = $_GET['training_code'];
    $st   = $mysqli->prepare('SELECT id, status FROM training_registrations WHERE student_code=? LIMIT 1');
    $st->bind_param('s', $code);
    $st->execute();
    $row = $st->get_result()->fetch_assoc();
    $st->close();
    echo json_encode([
        'success' => true,
        'paid'    => ($row && $row['status'] === 'active'),
        'status'  => $row['status'] ?? 'unknown',
    ]);
    exit;
}

// ── Check hội viên ──────────────────────────────────────────────────────────
if (!empty($_GET['membership_id'])) {
    $mid = (int)$_GET['membership_id'];
    $st  = $mysqli->prepare('SELECT id, payment_status FROM memberships WHERE id=? AND user_id=? LIMIT 1');
    $st->bind_param('ii', $mid, $uid);
    $st->execute();
    $row = $st->get_result()->fetch_assoc();
    $st->close();
    echo json_encode([
        'success' => true,
        'paid'    => ($row && $row['payment_status'] === 'paid'),
        'status'  => $row['payment_status'] ?? 'unknown',
    ]);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Missing parameter']);
