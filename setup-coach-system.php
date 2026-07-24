<?php
require_once 'db.php';

// Bảng coaches - thông tin HLV
$mysqli->query("CREATE TABLE IF NOT EXISTS coaches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    specialty VARCHAR(200),
    experience INT DEFAULT 0,
    certification VARCHAR(100),
    max_students INT DEFAULT 5,
    current_students INT DEFAULT 0,
    schedule_days VARCHAR(50) DEFAULT 'Mon,Wed,Fri',
    status TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");
echo "coaches: " . ($mysqli->error ?: 'OK') . "\n";

// Bảng coach_schedules - lịch tập cụ thể
$mysqli->query("CREATE TABLE IF NOT EXISTS coach_schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    coach_id INT NOT NULL,
    student_code VARCHAR(20) NOT NULL,
    student_name VARCHAR(100) NOT NULL,
    day_of_week ENUM('Mon','Tue','Wed','Thu','Fri','Sat','Sun') NOT NULL,
    time_slot VARCHAR(20) NOT NULL,
    course VARCHAR(50) NOT NULL,
    start_date DATE NOT NULL,
    status ENUM('active','completed','cancelled') DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");
echo "coach_schedules: " . ($mysqli->error ?: 'OK') . "\n";

// Thêm HLV mẫu
$coaches = [
    ['HLV Nguyễn Văn A', 'Kỹ thuật cơ bản', 15, 'BWF Level 2', 5, 'Mon,Wed,Fri'],
    ['HLV Trần Thị B',   'Chiến thuật thi đấu', 12, 'BWF Level 3', 4, 'Tue,Thu,Sat'],
    ['HLV Lê Văn C',     'Thể lực chuyên môn', 10, 'Fitness Level 3', 4, 'Mon,Wed,Sat'],
];

foreach ($coaches as $c) {
    $check = $mysqli->query("SELECT id FROM coaches WHERE name = '{$c[0]}'");
    if ($check->num_rows === 0) {
        $stmt = $mysqli->prepare("INSERT INTO coaches (name, specialty, experience, certification, max_students, schedule_days) VALUES (?,?,?,?,?,?)");
        $stmt->bind_param('ssisss', $c[0], $c[1], $c[2], $c[3], $c[4], $c[5]);
        $stmt->execute();
        echo "Added coach: {$c[0]}\n";
    } else {
        echo "Coach exists: {$c[0]}\n";
    }
}

$mysqli->close();
echo "\nDone!";
?>