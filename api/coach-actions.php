<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/functions.php';

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Chưa đăng nhập.']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// ── Duyệt / Từ chối học viên ──
if ($action === 'approve_student' || $action === 'reject_student') {
    if ($_SESSION['role'] !== 'coach' && !isAdmin()) {
        echo json_encode(['success' => false, 'error' => 'Không có quyền.']); exit;
    }

    $reg_id = intval($_POST['reg_id'] ?? 0);
    if (!$reg_id) { echo json_encode(['success' => false, 'error' => 'ID không hợp lệ.']); exit; }

    // Lấy coach_id của người đang đăng nhập
    $my_coach_id = null;
    if ($_SESSION['role'] === 'coach') {
        $s = $mysqli->prepare('SELECT id FROM coaches WHERE user_id = ? LIMIT 1');
        $s->bind_param('i', $_SESSION['user_id']);
        $s->execute();
        $row = $s->get_result()->fetch_assoc();
        $s->close();
        $my_coach_id = $row['id'] ?? null;
    }

    // Kiểm tra reg thuộc coach này không
    $check = $mysqli->prepare('SELECT id, coach_id, student_name FROM training_registrations WHERE id = ?');
    $check->bind_param('i', $reg_id);
    $check->execute();
    $reg = $check->get_result()->fetch_assoc();
    $check->close();

    if (!$reg) { echo json_encode(['success' => false, 'error' => 'Không tìm thấy đăng ký.']); exit; }
    if ($my_coach_id && $reg['coach_id'] != $my_coach_id) {
        echo json_encode(['success' => false, 'error' => 'Bạn không có quyền với học viên này.']); exit;
    }

    $new_status = ($action === 'approve_student') ? 'active' : 'cancelled';
    $upd = $mysqli->prepare('UPDATE training_registrations SET status = ? WHERE id = ?');
    $upd->bind_param('si', $new_status, $reg_id);
    $upd->execute();
    $upd->close();

    $label = $new_status === 'active' ? 'Đã duyệt' : 'Đã từ chối';
    echo json_encode(['success' => true, 'message' => "$label học viên {$reg['student_name']}.", 'new_status' => $new_status]);
    exit;
}

// ── Cập nhật thông tin cá nhân HLV ──
if ($action === 'update_profile') {
    if ($_SESSION['role'] !== 'coach') {
        echo json_encode(['success' => false, 'error' => 'Không có quyền.']); exit;
    }

    $name       = trim($_POST['name']        ?? '');
    $specialty  = trim($_POST['specialty']   ?? '');
    $exp        = intval($_POST['experience_years']      ?? 0);
    $max        = intval($_POST['max_students_per_week'] ?? 3);
    $phone      = trim($_POST['phone']       ?? '');
    $bio        = trim($_POST['bio']         ?? '');

    if (!$name) { echo json_encode(['success' => false, 'error' => 'Vui lòng nhập tên.']); exit; }

    $s = $mysqli->prepare('UPDATE coaches SET name=?, specialty=?, experience_years=?, max_students_per_week=?, phone=?, bio=? WHERE user_id=?');
    $s->bind_param('ssiissi', $name, $specialty, $exp, $max, $phone, $bio, $_SESSION['user_id']);
    $s->execute();
    $s->close();

    // Cập nhật session name
    $_SESSION['name'] = $name;

    echo json_encode(['success' => true, 'message' => 'Cập nhật hồ sơ thành công.']);
    exit;
}

// ── Gửi yêu cầu hỗ trợ đến admin ──
if ($action === 'send_support') {
    if ($_SESSION['role'] !== 'coach') {
        echo json_encode(['success' => false, 'error' => 'Không có quyền.']); exit;
    }

    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (!$subject || !$message) {
        echo json_encode(['success' => false, 'error' => 'Vui lòng điền đầy đủ tiêu đề và nội dung.']); exit;
    }

    // Tạo bảng support_tickets nếu chưa có
    $mysqli->query("CREATE TABLE IF NOT EXISTS support_tickets (
        id         INT AUTO_INCREMENT PRIMARY KEY,
        user_id    INT NOT NULL,
        role       VARCHAR(20) DEFAULT 'coach',
        subject    VARCHAR(200) NOT NULL,
        message    TEXT NOT NULL,
        status     VARCHAR(20) DEFAULT 'open',
        admin_reply TEXT DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_user (user_id),
        INDEX idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $ins = $mysqli->prepare('INSERT INTO support_tickets (user_id, role, subject, message) VALUES (?,?,?,?)');
    $role = 'coach';
    $ins->bind_param('isss', $_SESSION['user_id'], $role, $subject, $message);
    $ins->execute();
    $ins->close();

    echo json_encode(['success' => true, 'message' => 'Yêu cầu đã được gửi đến quản trị viên. Chúng tôi sẽ phản hồi sớm nhất có thể.']);
    exit;
}

// ── Lấy lịch sử tickets ──
if ($action === 'get_tickets' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($_SESSION['role'] !== 'coach') {
        echo json_encode(['success' => false, 'tickets' => []]); exit;
    }

    // Tạo bảng nếu chưa có
    $mysqli->query("CREATE TABLE IF NOT EXISTS support_tickets (
        id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NOT NULL, role VARCHAR(20) DEFAULT 'coach',
        subject VARCHAR(200) NOT NULL, message TEXT NOT NULL, status VARCHAR(20) DEFAULT 'open',
        admin_reply TEXT DEFAULT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $stmt = $mysqli->prepare('SELECT id, subject, status, admin_reply, created_at FROM support_tickets WHERE user_id = ? ORDER BY created_at DESC LIMIT 10');
    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    $tickets = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    echo json_encode(['success' => true, 'tickets' => $tickets]);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Action không hợp lệ.']);
