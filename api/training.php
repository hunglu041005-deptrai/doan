<?php
header('Content-Type: application/json');
error_reporting(0);
ini_set('display_errors', 0);
require_once __DIR__ . '/../db.php';

// Auto-migration: đảm bảo bảng + cột tồn tại
$mysqli->query("CREATE TABLE IF NOT EXISTS training_registrations (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    student_code     VARCHAR(20)  UNIQUE NOT NULL,
    student_name     VARCHAR(100) NOT NULL,
    phone            VARCHAR(20)  NOT NULL,
    email            VARCHAR(100) DEFAULT NULL,
    course           VARCHAR(20)  NOT NULL,
    age_group        VARCHAR(30)  DEFAULT NULL,
    preferred_time   VARCHAR(60)  DEFAULT NULL,
    preferred_coach  VARCHAR(100) DEFAULT NULL,
    current_level    VARCHAR(50)  DEFAULT NULL,
    learning_goals   TEXT         DEFAULT NULL,
    coach_id         INT          DEFAULT NULL,
    schedule_days    VARCHAR(100) DEFAULT NULL,
    schedule_time    VARCHAR(60)  DEFAULT NULL,
    week_start       DATE         DEFAULT NULL,
    qr_code          VARCHAR(50)  DEFAULT NULL,
    status           VARCHAR(30)  NOT NULL DEFAULT 'pending_payment',
    payment_method   VARCHAR(30)  DEFAULT NULL,
    payment_at       DATETIME     DEFAULT NULL,
    created_at       DATETIME     DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
$mysqli->query("ALTER TABLE training_registrations ADD COLUMN IF NOT EXISTS payment_method VARCHAR(30) DEFAULT NULL");
$mysqli->query("ALTER TABLE training_registrations ADD COLUMN IF NOT EXISTS payment_at DATETIME DEFAULT NULL");
$mysqli->query("ALTER TABLE training_registrations ADD COLUMN IF NOT EXISTS status VARCHAR(30) NOT NULL DEFAULT 'pending_payment'");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed.']);
    exit;
}

$student_name    = trim($_POST['student_name']   ?? '');
$phone           = trim($_POST['phone']           ?? '');
$email           = trim($_POST['email']           ?? '');
$course          = $_POST['course']               ?? '';
$age_group       = $_POST['age_group']            ?? '';
$preferred_time  = $_POST['preferred_time']       ?? '';
$preferred_coach = trim($_POST['preferred_coach'] ?? '');
$preferred_coach_id = intval($_POST['preferred_coach_id'] ?? 0);
$current_level   = $_POST['current_level']        ?? '';
$learning_goals  = trim($_POST['learning_goals']  ?? '');

if (!$student_name || !$phone || !$course) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Vui lòng điền đủ thông tin bắt buộc.']);
    exit;
}

$valid_courses = ['beginner', 'intermediate', 'advanced'];
if (!in_array($course, $valid_courses)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Khóa học không hợp lệ.']);
    exit;
}

$course_labels = [
    'beginner'     => 'Cơ bản (3 tháng)',
    'intermediate' => 'Trung cấp (4 tháng)',
    'advanced'     => 'Nâng cao (5 tháng)',
];

// ===== TÌM HLV PHÙ HỢP =====
$coach_id   = null;
$coach_name = $preferred_coach ?: 'Chưa chọn';
$coach_data = null;

// Ngày đầu tuần hiện tại (Monday)
$week_start = date('Y-m-d', strtotime('monday this week'));

// Nếu user chọn HLV cụ thể (ưu tiên theo ID, fallback theo tên)
if ($preferred_coach_id > 0) {
    $stmt = $mysqli->prepare('SELECT id, name, max_students_per_week FROM coaches WHERE id = ? AND status = 1 LIMIT 1');
    $stmt->bind_param('i', $preferred_coach_id);
    $stmt->execute();
    $coach_data = $stmt->get_result()->fetch_assoc();
    $stmt->close();
} elseif ($preferred_coach && $preferred_coach !== 'Chưa chọn') {
    $stmt = $mysqli->prepare('SELECT id, name, max_students_per_week FROM coaches WHERE name = ? AND status = 1 LIMIT 1');
    $stmt->bind_param('s', $preferred_coach);
    $stmt->execute();
    $coach_data = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// Nếu không tìm được hoặc không chọn → tự động gán HLV còn chỗ
if (!$coach_data) {
    $coaches = $mysqli->query('SELECT id, name, max_students_per_week FROM coaches WHERE status = 1 ORDER BY id');
    while ($c = $coaches->fetch_assoc()) {
        // Đếm học viên tuần này
        $cnt_stmt = $mysqli->prepare(
            'SELECT COUNT(*) as cnt FROM training_registrations 
             WHERE coach_id = ? AND week_start = ? AND status = "active"'
        );
        $cnt_stmt->bind_param('is', $c['id'], $week_start);
        $cnt_stmt->execute();
        $cnt = $cnt_stmt->get_result()->fetch_assoc()['cnt'];
        $cnt_stmt->close();

        if ($cnt < $c['max_students_per_week']) {
            $coach_data = $c;
            $coach_data['current_students'] = $cnt;
            break;
        }
    }
}

if (!$coach_data) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Tất cả HLV đã full lịch tuần này. Vui lòng thử lại tuần sau.']);
    exit;
}

$coach_id   = $coach_data['id'];
$coach_name = $coach_data['name'];

// ===== KIỂM TRA GIỚI HẠN HLV TUẦN NÀY =====
$cnt_stmt = $mysqli->prepare(
    'SELECT COUNT(*) as cnt FROM training_registrations 
     WHERE coach_id = ? AND week_start = ? AND status = "active"'
);
$cnt_stmt->bind_param('is', $coach_id, $week_start);
$cnt_stmt->execute();
$currentCount = $cnt_stmt->get_result()->fetch_assoc()['cnt'];
$cnt_stmt->close();

if ($currentCount >= $coach_data['max_students_per_week']) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error'   => "HLV {$coach_name} đã full lịch tuần này ({$coach_data['max_students_per_week']}/{$coach_data['max_students_per_week']} học viên). Vui lòng chọn HLV khác hoặc đăng ký tuần sau."
    ]);
    exit;
}

// ===== LỀN LỊCH 3 NGÀY/TUẦN =====
// Chọn 3 ngày trong tuần dựa vào thời gian học
$daySchedules = [
    'Sáng (6:00–9:00)'   => ['Mon', 'Wed', 'Fri'],
    'Chiều (14:00–17:00)' => ['Tue', 'Thu', 'Sat'],
    'Tối (18:00–21:00)'   => ['Mon', 'Wed', 'Fri'],
];
$scheduleDays = $daySchedules[$preferred_time] ?? ['Mon', 'Wed', 'Fri'];
$scheduleStr  = implode(', ', $scheduleDays);

// Tên ngày tiếng Việt
$dayNames = ['Mon'=>'Thứ 2','Tue'=>'Thứ 3','Wed'=>'Thứ 4','Thu'=>'Thứ 5','Fri'=>'Thứ 6','Sat'=>'Thứ 7','Sun'=>'Chủ nhật'];
$scheduleVN = implode(' · ', array_map(fn($d) => $dayNames[$d] ?? $d, $scheduleDays));

// ===== SINH MÃ HỌC VIÊN =====
do {
    $student_code = 'HV' . date('Y') . strtoupper(substr(md5(uniqid(rand(), true)), 0, 6));
    $check = $mysqli->prepare('SELECT id FROM training_registrations WHERE student_code = ?');
    $check->bind_param('s', $student_code);
    $check->execute();
    $check->store_result();
    $exists = $check->num_rows > 0;
    $check->close();
} while ($exists);

// ===== LƯU VÀO DATABASE (trạng thái pending_payment) =====
$stmt = $mysqli->prepare(
    'INSERT INTO training_registrations
     (student_code, student_name, phone, email, course, age_group, preferred_time, 
      preferred_coach, current_level, learning_goals, coach_id, schedule_days, 
      schedule_time, week_start, qr_code, status)
     VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
);
$qr_code = $student_code;
$reg_status = 'pending_payment';
$stmt->bind_param(
    'ssssssssssisssss',
    $student_code, $student_name, $phone, $email,
    $course, $age_group, $preferred_time, $preferred_coach,
    $current_level, $learning_goals, $coach_id,
    $scheduleStr, $preferred_time, $week_start, $qr_code, $reg_status
);

if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Lỗi lưu dữ liệu: ' . $stmt->error]);
    exit;
}
$registration_id = $mysqli->insert_id;
$stmt->close();

// Gửi thông báo nếu đã đăng nhập — CHỈ gửi sau khi thanh toán thành công
// (chuyển sang training-checkout.php xử lý)

// Giá khóa học
$course_prices = [
    'beginner'     => 1800000,
    'intermediate' => 2800000,
    'advanced'     => 4500000,
];
$course_price = $course_prices[$course] ?? 0;

// Slot còn lại
$remaining = $coach_data['max_students_per_week'] - $currentCount - 1;

echo json_encode([
    'success'         => true,
    'registration_id' => $registration_id,
    'student_code'    => $student_code,
    'student_name'    => $student_name,
    'phone'           => $phone,
    'course'          => $course,
    'course_label'    => $course_labels[$course],
    'course_price'    => $course_price,
    'coach'           => $coach_name,
    'coach_id'        => $coach_id,
    'schedule_days'   => $scheduleVN,
    'schedule_time'   => $preferred_time,
    'week_start'      => $week_start,
    'remaining_slots' => $remaining,
    'is_full'         => $remaining <= 0,
    'registered_at'   => date('d/m/Y'),
    'redirect'        => 'training-checkout.php?reg=' . $registration_id . '&code=' . urlencode($student_code),
    'qr_data'         => "BADMINTONPRO-HV|{$student_code}|{$student_name}|{$course_labels[$course]}|{$coach_name}|{$scheduleVN}|{$phone}",
]);
