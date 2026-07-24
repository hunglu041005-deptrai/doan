<?php
header('Content-Type: application/json');
require_once __DIR__ . '/includes/functions.php';

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$order_id = intval($input['order_id'] ?? 0);
$user_id  = (int)$_SESSION['user_id'];

if (!$order_id) {
    echo json_encode(['success' => false, 'message' => 'Mã đơn hàng không hợp lệ.']);
    exit;
}

// Kiểm tra quyền và trạng thái
$stmt = $mysqli->prepare(
    'SELECT id, status, created_at FROM orders WHERE id = ? AND user_id = ?'
);
$stmt->bind_param('ii', $order_id, $user_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) {
    echo json_encode(['success' => false, 'message' => 'Không tìm thấy đơn hàng.']);
    exit;
}

if ($order['status'] !== 'pending') {
    echo json_encode(['success' => false, 'message' => 'Chỉ có thể huỷ đơn hàng đang chờ xử lý.']);
    exit;
}

// Cho phép huỷ trong 30 phút
$minutes = (time() - strtotime($order['created_at'])) / 60;
if ($minutes > 30) {
    echo json_encode(['success' => false, 'message' => 'Đã quá 30 phút, không thể huỷ đơn hàng.']);
    exit;
}

// Huỷ đơn + hoàn stock
$mysqli->begin_transaction();
try {
    $upd = $mysqli->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ?");
    $upd->bind_param('i', $order_id);
    $upd->execute();
    $upd->close();

    // Hoàn lại stock
    $items = $mysqli->prepare('SELECT product_id, quantity FROM order_items WHERE order_id = ?');
    $items->bind_param('i', $order_id);
    $items->execute();
    $rows = $items->get_result()->fetch_all(MYSQLI_ASSOC);
    $items->close();

    foreach ($rows as $row) {
        $rst = $mysqli->prepare('UPDATE products SET stock_quantity = stock_quantity + ? WHERE id = ?');
        $rst->bind_param('ii', $row['quantity'], $row['product_id']);
        $rst->execute();
        $rst->close();
    }

    $mysqli->commit();
    echo json_encode(['success' => true, 'message' => 'Đơn hàng đã được huỷ thành công.']);
} catch (Exception $e) {
    $mysqli->rollback();
    echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
}
