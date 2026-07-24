<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/functions.php';

$date       = $_GET['date']       ?? date('Y-m-d');
$start_time = $_GET['start_time'] ?? '00:00';
$user_id    = isLoggedIn() ? (int)$_SESSION['user_id'] : 0;

// Tạo bảng nếu chưa có
$mysqli->query("CREATE TABLE IF NOT EXISTS promotions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    description VARCHAR(255),
    color_from VARCHAR(20) DEFAULT '#f472b6',
    color_to VARCHAR(20) DEFAULT '#ef4444',
    text_color VARCHAR(20) DEFAULT '#fff',
    discount_pct TINYINT DEFAULT 0,
    time_start TIME DEFAULT NULL,
    time_end TIME DEFAULT NULL,
    apply_weekend TINYINT DEFAULT 0,
    apply_newuser TINYINT DEFAULT 0,
    status TINYINT DEFAULT 1,
    sort_order INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$promos = $mysqli->query(
    'SELECT * FROM promotions WHERE status=1 AND discount_pct > 0 ORDER BY discount_pct DESC'
);

$bookingHour   = (int)date('H', strtotime($start_time));
$bookingMinute = (int)date('i', strtotime($start_time));
$bookingTime   = $bookingHour * 60 + $bookingMinute;
$dayOfWeek     = (int)date('N', strtotime($date)); // 1=T2, 7=CN
$isWeekend     = in_array($dayOfWeek, [6, 7]);

// Kiểm tra thành viên mới
$isNewUser = false;
if ($user_id) {
    $stmt = $mysqli->prepare('SELECT COUNT(*) AS cnt FROM bookings WHERE user_id = ?');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $isNewUser = ($stmt->get_result()->fetch_assoc()['cnt'] ?? 1) === 0;
    $stmt->close();
}

$applicable_promos = [];
if ($promos) {
    while ($promo = $promos->fetch_assoc()) {
        $ok = false;

        if ($promo['time_start'] && $promo['time_end']) {
            $pStart = (int)substr($promo['time_start'],0,2)*60 + (int)substr($promo['time_start'],3,2);
            $pEnd   = (int)substr($promo['time_end'],0,2)*60   + (int)substr($promo['time_end'],3,2);
            if ($bookingTime >= $pStart && $bookingTime < $pEnd) $ok = true;
        } else {
            $ok = true;
        }

        if ($promo['apply_weekend'] && !$isWeekend) $ok = false;
        if ($promo['apply_newuser'] && !$isNewUser) $ok = false;

        if ($ok) {
            $applicable_promos[] = [
                'title'        => $promo['title'],
                'description'  => $promo['description'],
                'discount_pct' => (int)$promo['discount_pct'],
                'color_from'   => $promo['color_from'],
                'color_to'     => $promo['color_to'],
                'text_color'   => $promo['text_color'],
            ];
        }
    }
}

// Trả về ưu đãi tốt nhất (cao nhất)
$best = !empty($applicable_promos) ? $applicable_promos[0] : null;

echo json_encode([
    'success'     => true,
    'has_promo'   => !empty($best),
    'promo'       => $best,
    'all_promos'  => $applicable_promos,
    'is_weekend'  => $isWeekend,
    'is_new_user' => $isNewUser,
]);
