<?php
header('Content-Type: application/json');
error_reporting(0);
require_once __DIR__ . '/../db.php';

$code = strtoupper(trim($_GET['code'] ?? ''));

if (!$code) {
    echo json_encode(['success' => false, 'error' => 'Mã không hợp lệ']);
    exit;
}

$stmt = $mysqli->prepare("
    SELECT tr.*, c.name as coach_name
    FROM training_registrations tr
    LEFT JOIN coaches c ON tr.coach_id = c.id
    WHERE tr.student_code = ?
    LIMIT 1
");
$stmt->bind_param('s', $code);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$student) {
    echo json_encode(['success' => false, 'error' => 'Không tìm thấy học viên']);
    exit;
}

// Log attendance check-in (create table if needed)
$mysqli->query("CREATE TABLE IF NOT EXISTS attendance_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_code VARCHAR(30) NOT NULL,
    coach_id INT DEFAULT NULL,
    scanned_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_code (student_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Insert check-in record
$logStmt = $mysqli->prepare('INSERT INTO attendance_logs (student_code, coach_id) VALUES (?,?)');
$coachIdForLog = $student['coach_id'] ?? null;
$logStmt->bind_param('si', $code, $coachIdForLog);
$logStmt->execute();
$logStmt->close();

$course_labels = [
    'beginner'     => 'Cơ bản (3 tháng)',
    'intermediate' => 'Trung cấp (4 tháng)',
    'advanced'     => 'Nâng cao (5 tháng)',
];

echo json_encode([
    'success' => true,
    'student' => [
        'student_code'  => $student['student_code'],
        'student_name'  => $student['student_name'],
        'phone'         => $student['phone'],
        'course'        => $student['course'],
        'course_label'  => $course_labels[$student['course']] ?? $student['course'],
        'coach_name'    => $student['coach_name'] ?? 'Chưa phân công',
        'schedule_days' => $student['schedule_days'],
        'schedule_time' => $student['schedule_time'],
        'week_start'    => $student['week_start'],
        'status'        => $student['status'],
    ]
]);
