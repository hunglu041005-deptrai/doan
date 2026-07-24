<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/functions.php';

// Auto-create bảng booking_reviews nếu chưa có
$mysqli->query("CREATE TABLE IF NOT EXISTS booking_reviews (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT DEFAULT NULL,
    user_id    INT NOT NULL,
    court_id   INT NOT NULL,
    rating     TINYINT(1) NOT NULL CHECK (rating >= 1 AND rating <= 5),
    review_text TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_court  (court_id),
    INDEX idx_user   (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$action = $_GET['action'] ?? ($_POST['action'] ?? '');

// ── GET: lấy rating + reviews của 1 sân ──
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $court_id = intval($_GET['court_id'] ?? 0);

    if ($action === 'summary') {
        // Tổng hợp rating cho 1 sân
        $stmt = $mysqli->prepare(
            'SELECT COALESCE(ROUND(AVG(rating),1),0) AS avg_rating,
                    COUNT(*) AS total,
                    SUM(rating=5) AS s5,
                    SUM(rating=4) AS s4,
                    SUM(rating=3) AS s3,
                    SUM(rating=2) AS s2,
                    SUM(rating=1) AS s1
             FROM booking_reviews WHERE court_id = ?'
        );
        $stmt->bind_param('i', $court_id);
        $stmt->execute();
        $data = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        echo json_encode(['success' => true, 'data' => $data]);
        exit;
    }

    if ($action === 'list') {
        // Danh sách reviews của 1 sân (10 gần nhất)
        $stmt = $mysqli->prepare(
            'SELECT r.id, r.rating, r.review_text, r.created_at,
                    u.name AS user_name
             FROM booking_reviews r
             JOIN users u ON u.id = r.user_id
             WHERE r.court_id = ?
             ORDER BY r.created_at DESC LIMIT 10'
        );
        $stmt->bind_param('i', $court_id);
        $stmt->execute();
        $reviews = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        echo json_encode(['success' => true, 'reviews' => $reviews]);
        exit;
    }

    // all courts avg
    $result = $mysqli->query(
        'SELECT court_id,
                ROUND(AVG(rating),1) AS avg_rating,
                COUNT(*) AS review_count
         FROM booking_reviews
         GROUP BY court_id'
    );
    $ratings = [];
    while ($row = $result->fetch_assoc()) {
        $ratings[$row['court_id']] = [
            'avg_rating'   => (float)$row['avg_rating'],
            'review_count' => (int)$row['review_count'],
        ];
    }
    echo json_encode(['success' => true, 'ratings' => $ratings]);
    exit;
}

// ── POST: submit đánh giá ──
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Bạn cần đăng nhập để đánh giá.']);
    exit;
}

$court_id   = intval($_POST['court_id']    ?? 0);
$rating     = intval($_POST['rating']      ?? 0);
$review_text = trim($_POST['review_text']  ?? '');
$user_id    = (int)$_SESSION['user_id'];

if (!$court_id || $rating < 1 || $rating > 5) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Dữ liệu không hợp lệ.']);
    exit;
}

// Kiểm tra user đã từng đặt sân này chưa (optional — cho phép ai cũng đánh giá)
// Kiểm tra đã đánh giá chưa (1 user = 1 đánh giá / sân)
$chk = $mysqli->prepare('SELECT id FROM booking_reviews WHERE user_id = ? AND court_id = ?');
$chk->bind_param('ii', $user_id, $court_id);
$chk->execute();
$chk->store_result();
$already = $chk->num_rows > 0;
$existing_id = null;
if ($already) {
    $chk->bind_result($existing_id);
    $chk->fetch();
}
$chk->close();

if ($already) {
    // Cập nhật đánh giá cũ
    $upd = $mysqli->prepare('UPDATE booking_reviews SET rating = ?, review_text = ? WHERE id = ?');
    $upd->bind_param('isi', $rating, $review_text, $existing_id);
    $upd->execute();
    $upd->close();
} else {
    // Thêm mới
    $ins = $mysqli->prepare(
        'INSERT INTO booking_reviews (user_id, court_id, rating, review_text) VALUES (?, ?, ?, ?)'
    );
    $ins->bind_param('iiis', $user_id, $court_id, $rating, $review_text);
    $ins->execute();
    $ins->close();
}

// Lấy lại avg mới
$avg_stmt = $mysqli->prepare(
    'SELECT ROUND(AVG(rating),1) AS avg, COUNT(*) AS cnt FROM booking_reviews WHERE court_id = ?'
);
$avg_stmt->bind_param('i', $court_id);
$avg_stmt->execute();
$avg_data = $avg_stmt->get_result()->fetch_assoc();
$avg_stmt->close();

echo json_encode([
    'success'      => true,
    'updated'      => $already,
    'avg_rating'   => (float)$avg_data['avg'],
    'review_count' => (int)$avg_data['cnt'],
]);
