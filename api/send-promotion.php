<?php
header('Content-Type: application/json');
error_reporting(0);
ini_set('display_errors', 0);

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/notification-system.php';

// Chỉ admin mới gửi được khuyến mãi
if (!isAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Không có quyền truy cập.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed.']);
    exit;
}

$title   = trim($_POST['title']   ?? '');
$message = trim($_POST['message'] ?? '');
$link    = trim($_POST['link']    ?? '');
$target  = $_POST['target'] ?? 'all'; // 'all' | user_id cụ thể

if (!$title || !$message) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Tiêu đề và nội dung không được trống.']);
    exit;
}

$ns = new NotificationSystem();

if ($target === 'all') {
    $ns->broadcastPromotion($title, $message, $link ?: null);
    echo json_encode(['success' => true, 'message' => 'Đã gửi khuyến mãi tới tất cả người dùng.']);
} else {
    $userId = intval($target);
    if ($userId > 0) {
        $ns->notifyPromotion($userId, $title, $message, $link ?: null);
        echo json_encode(['success' => true, 'message' => "Đã gửi thông báo tới user #{$userId}."]);
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Target không hợp lệ.']);
    }
}
