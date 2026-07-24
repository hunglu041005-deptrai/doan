<?php
header('Content-Type: application/json; charset=utf-8');
error_reporting(0);
ini_set('display_errors', 0);

require_once __DIR__ . '/../includes/notification-system.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$ns     = new NotificationSystem();
$userId = (int) $_SESSION['user_id'];
$action = $_GET['action'] ?? 'list';

switch ($action) {

    case 'list':
        $limit  = min(intval($_GET['limit']  ?? 20), 50);
        $offset = intval($_GET['offset'] ?? 0);
        echo json_encode([
            'success'       => true,
            'notifications' => $ns->getUserNotifications($userId, $limit, $offset),
            'unread_count'  => $ns->getUnreadCount($userId),
        ]);
        break;

    case 'unread_count':
        echo json_encode(['success' => true, 'count' => $ns->getUnreadCount($userId)]);
        break;

    case 'mark_read':
        $id = intval($_POST['notification_id'] ?? 0);
        echo json_encode(['success' => $id ? $ns->markAsRead($id, $userId) : false]);
        break;

    case 'mark_all_read':
        echo json_encode(['success' => $ns->markAllAsRead($userId)]);
        break;

    case 'realtime':
        $last = $_GET['last_timestamp'] ?? null;
        echo json_encode([
            'success'       => true,
            'notifications' => $ns->getRealTimeNotifications($userId, $last),
            'timestamp'     => date('Y-m-d H:i:s'),
        ]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
