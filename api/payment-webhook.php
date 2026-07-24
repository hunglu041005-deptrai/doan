<?php
/**
 * WEBHOOK NHẬN THANH TOÁN TỪ SEPAY
 *
 * Cách cài đặt:
 * 1. Vào SePay → Webhooks → click webhook → copy "API Key"
 * 2. Dán vào SEPAY_API_KEY bên dưới (hoặc để trống '' để bỏ qua xác thực)
 * 3. Đặt Webhook URL trong SePay trỏ đến file này
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../db.php';

// ── CẤU HÌNH ──────────────────────────────────────────────────────────────
// Để trống '' để bỏ qua xác thực; hoặc điền API Key từ SePay dashboard
define('SEPAY_API_KEY', '');
define('LOG_FILE',      __DIR__ . '/../logs/payment_webhook.log');
// ──────────────────────────────────────────────────────────────────────────

if (!is_dir(dirname(LOG_FILE))) {
    mkdir(dirname(LOG_FILE), 0755, true);
}

function wLog($msg) {
    file_put_contents(LOG_FILE, '[' . date('Y-m-d H:i:s') . '] ' . $msg . "\n", FILE_APPEND);
}

function wRespond($ok, $msg, $code = 200) {
    http_response_code($code);
    echo json_encode(['success' => $ok, 'message' => $msg]);
    exit;
}

// Chỉ nhận POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    wRespond(false, 'Method not allowed', 405);
}

$rawBody = file_get_contents('php://input');
$data    = json_decode($rawBody, true);
wLog("INCOMING: " . $rawBody);

if (!$data) {
    wLog("ERROR: Invalid JSON");
    wRespond(false, 'Invalid JSON', 400);
}

// ── Xác thực API Key (chỉ khi đã cấu hình) ───────────────────────────────
if (!empty(SEPAY_API_KEY)) {
    $authHeader = '';
    // Thử nhiều cách lấy header Authorization
    if (function_exists('getallheaders')) {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    }
    if (empty($authHeader) && isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    }
    if (empty($authHeader) && isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
        $authHeader = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
    }

    $expectedBearer = 'Bearer ' . SEPAY_API_KEY;
    if ($authHeader !== $expectedBearer && $authHeader !== SEPAY_API_KEY) {
        wLog("AUTH FAIL. Header: " . $authHeader);
        wRespond(false, 'Unauthorized', 401);
    }
    wLog("AUTH OK");
} else {
    wLog("AUTH SKIPPED (no API key configured)");
}

// ── Parse dữ liệu SePay ───────────────────────────────────────────────────
// SePay gửi JSON format:
// {
//   "id": 12345,
//   "gateway": "MB Bank",
//   "transactionDate": "2024-01-01 10:00:00",
//   "accountNumber": "7369786789",
//   "content": "DATSAN00027 chuyen khoan",
//   "transferType": "in",
//   "transferAmount": 120000,
//   "referenceCode": "FT24001XXXXXX"
// }
$transferType   = strtolower($data['transferType'] ?? 'in');
$transferAmount = (int)($data['transferAmount'] ?? $data['amount'] ?? 0);
$description    = strtoupper(trim($data['content'] ?? $data['description'] ?? ''));
$transactionId  = (string)($data['id'] ?? $data['referenceCode'] ?? uniqid('tx_'));
$gateway        = $data['gateway'] ?? 'MB Bank';

wLog("Type=$transferType | Amount=$transferAmount | Desc=$description | TxID=$transactionId");

// Chỉ xử lý giao dịch tiền VÀO
if ($transferType !== 'in') {
    wLog("SKIP: outgoing transaction");
    wRespond(true, 'Skipped - outgoing');
}

if ($transferAmount <= 0) {
    wLog("SKIP: amount=0");
    wRespond(true, 'Skipped - zero amount');
}

// ── Tạo bảng payment_transactions nếu chưa có ────────────────────────────
$mysqli->query("CREATE TABLE IF NOT EXISTS payment_transactions (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    transaction_id VARCHAR(100) UNIQUE,
    gateway        VARCHAR(50),
    amount         INT,
    description    TEXT,
    order_type     VARCHAR(30),
    order_id       VARCHAR(50),
    status         VARCHAR(20) DEFAULT 'pending',
    raw_data       TEXT,
    created_at     DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_order (order_type, order_id),
    INDEX idx_trans (transaction_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Chống duplicate: kiểm tra transactionId đã xử lý chưa
$dupCheck = $mysqli->prepare("SELECT id FROM payment_transactions WHERE transaction_id=? LIMIT 1");
$dupCheck->bind_param('s', $transactionId);
$dupCheck->execute();
if ($dupCheck->get_result()->num_rows > 0) {
    wLog("SKIP: transaction $transactionId already processed");
    wRespond(true, 'Already processed');
}
$dupCheck->close();

// ── Trích xuất mã đơn từ nội dung chuyển khoản ───────────────────────────
$orderId   = null;
$orderType = null;

if      (preg_match('/DATSAN[_\-]?0*(\d+)/i',        $description, $m)) { $orderId = intval($m[1]);       $orderType = 'booking';    }
elseif  (preg_match('/\bBK(\d+)\b/i',                $description, $m)) { $orderId = intval($m[1]);       $orderType = 'booking';    }
elseif  (preg_match('/\b(ORD[A-Z0-9\-]+)\b/i',       $description, $m)) { $orderId = strtoupper($m[1]);   $orderType = 'order';      } // full order_number e.g. ORD25ABCDE
elseif  (preg_match('/\b(MBR|MEMBER)[_\-]?(\w+)\b/i',$description, $m)) { $orderId = strtoupper($m[2]);   $orderType = 'membership'; }
elseif  (preg_match('/\b(HV\d{4}[A-Z0-9]{4,8})\b/i', $description, $m)) {
    // HV... có thể là training (student_code) hoặc membership (member_code)
    // Ưu tiên check membership trước vì nó cần payment confirmation
    $hvCode = strtoupper($m[1]);
    $chkMem = $mysqli->prepare("SELECT id FROM memberships WHERE member_code=? AND payment_status='pending' LIMIT 1");
    $chkMem->bind_param('s', $hvCode);
    $chkMem->execute();
    if ($chkMem->get_result()->num_rows > 0) {
        $orderId   = $hvCode;
        $orderType = 'membership';
    } else {
        $orderId   = $hvCode;
        $orderType = 'training';
    }
    $chkMem->close();
}

if (!$orderId || !$orderType) {
    wLog("WARN: Cannot parse order from: $description");
    // Lưu lại để admin xem
    $s = $mysqli->prepare("INSERT IGNORE INTO payment_transactions (transaction_id,gateway,amount,description,order_type,order_id,status,raw_data) VALUES (?,?,?,?,'unknown','unknown','unmatched',?)");
    $raw = substr($rawBody, 0, 2000);
    $s->bind_param('ssisl', $transactionId, $gateway, $transferAmount, $description, $raw);
    $s->execute(); $s->close();
    wRespond(true, 'Logged - no order match');
}

wLog("Parsed: type=$orderType id=$orderId amount=$transferAmount");

// ── Cập nhật DB theo loại đơn ─────────────────────────────────────────────
$updated = false;
$detail  = '';

switch ($orderType) {

    case 'booking':
        $st = $mysqli->prepare('SELECT id, total_price, payment_status FROM bookings WHERE id=? LIMIT 1');
        $st->bind_param('i', $orderId);
        $st->execute();
        $bk = $st->get_result()->fetch_assoc();
        $st->close();

        if (!$bk) { wLog("WARN: Booking #$orderId not found"); wRespond(false, 'Booking not found', 404); }
        if ($bk['payment_status'] === 'paid') { wLog("INFO: already paid"); wRespond(true, 'Already paid'); }
        if ($transferAmount < $bk['total_price'] * 0.9) {
            wLog("WARN: Amount mismatch. Expected:{$bk['total_price']} Got:$transferAmount");
        }

        $up = $mysqli->prepare("UPDATE bookings SET payment_status='paid', payment_method='bank_transfer' WHERE id=?");
        $up->bind_param('i', $orderId);
        $updated = $up->execute();
        $up->close();

        // Gửi thông báo khi tiền thật về
        if ($updated) {
            try {
                require_once __DIR__ . '/../includes/notification-system.php';
                $ns = new NotificationSystem();
                $ns->notifyBookingConfirmed($orderId);
            } catch (Exception $e) {}
        }

        $detail = "Booking #$orderId paid {$transferAmount}d";
        break;

    case 'order':
        $st = $mysqli->prepare('SELECT id, payment_status FROM orders WHERE order_number=? LIMIT 1');
        $st->bind_param('s', $orderId);
        $st->execute();
        $ord = $st->get_result()->fetch_assoc();
        $st->close();

        if (!$ord) { wRespond(false, 'Order not found', 404); }
        if ($ord['payment_status'] === 'paid') { wRespond(true, 'Already paid'); }

        $up = $mysqli->prepare("UPDATE orders SET payment_status='paid', status='confirmed' WHERE id=?");
        $up->bind_param('i', $ord['id']);
        $updated = $up->execute();
        $up->close();
        $detail = "Order $orderId paid & confirmed";
        break;

    case 'training':
        $st = $mysqli->prepare('SELECT id, status FROM training_registrations WHERE student_code=? LIMIT 1');
        $st->bind_param('s', $orderId);
        $st->execute();
        $reg = $st->get_result()->fetch_assoc();
        $st->close();

        if (!$reg) { wRespond(false, 'Training not found', 404); }
        if ($reg['status'] === 'active') { wRespond(true, 'Already active'); }

        $up = $mysqli->prepare("UPDATE training_registrations SET status='active', payment_method='bank_transfer', payment_at=NOW() WHERE student_code=?");
        $up->bind_param('s', $orderId);
        $updated = $up->execute();
        $up->close();
        $detail = "Training $orderId activated";
        break;

    case 'membership':
        $like = '%' . $orderId . '%';
        $st = $mysqli->prepare('SELECT id, payment_status FROM memberships WHERE member_code LIKE ? LIMIT 1');
        $st->bind_param('s', $like);
        $st->execute();
        $mem = $st->get_result()->fetch_assoc();
        $st->close();

        if (!$mem) { wRespond(false, 'Membership not found', 404); }
        if ($mem['payment_status'] === 'paid') { wRespond(true, 'Already paid'); }

        $up = $mysqli->prepare("UPDATE memberships SET payment_status='paid', status='active', start_date=CURDATE(), end_date=DATE_ADD(CURDATE(), INTERVAL months MONTH) WHERE id=?");
        $up->bind_param('i', $mem['id']);
        $updated = $up->execute();
        $up->close();

        // Gửi thông báo kích hoạt hội viên
        if ($updated) {
            try {
                $memFull = $mysqli->prepare('SELECT user_id, member_code, plan_name, plan_detail, end_date FROM memberships WHERE id=? LIMIT 1');
                $memFull->bind_param('i', $mem['id']);
                $memFull->execute();
                $mData = $memFull->get_result()->fetch_assoc();
                $memFull->close();
                if ($mData) {
                    require_once __DIR__ . '/../includes/notification-system.php';
                    $ns = new NotificationSystem();
                    $ns->notifyMembershipActivated($mData['user_id'], $mData['member_code'], $mData['plan_name'] . ': ' . $mData['plan_detail'], $mData['end_date']);
                }
            } catch (Exception $e) {}
        }

        $detail = "Membership $orderId paid";
        break;
}

// ── Lưu lịch sử giao dịch ────────────────────────────────────────────────
$status   = $updated ? 'processed' : 'failed';
$rawStore = substr($rawBody, 0, 2000);
$sv = $mysqli->prepare(
    'INSERT IGNORE INTO payment_transactions (transaction_id,gateway,amount,description,order_type,order_id,status,raw_data) VALUES (?,?,?,?,?,?,?,?)'
);
$sv->bind_param('ssisssss', $transactionId, $gateway, $transferAmount, $description,
    $orderType, (string)$orderId, $status, $rawStore);
$sv->execute();
$sv->close();

if ($updated) {
    wLog("SUCCESS: $detail");
    wRespond(true, $detail);
} else {
    wLog("FAIL: DB update failed for $orderType $orderId");
    wRespond(false, 'DB update failed', 500);
}
