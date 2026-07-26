<?php
/**
 * API: Lấy danh sách gói hội viên
 * GET ?court_id=123  → gói của sân đó + global
 * GET               → tất cả gói global
 */
ob_start();
ini_set('display_errors', 0);
header('Content-Type: application/json');
require_once __DIR__ . '/../db.php';

// Tạo bảng nếu chưa có
$mysqli->query("CREATE TABLE IF NOT EXISTS membership_plans (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    court_id     INT DEFAULT NULL,
    name         VARCHAR(150) NOT NULL,
    detail       VARCHAR(150) NOT NULL,
    price_per    INT NOT NULL DEFAULT 80000,
    total_price  INT NOT NULL DEFAULT 720000,
    months       INT NOT NULL DEFAULT 3,
    free_tickets INT NOT NULL DEFAULT 11,
    time_range   VARCHAR(30) DEFAULT '14H-17H',
    sale_start   DATE DEFAULT NULL,
    sale_end     DATE DEFAULT NULL,
    status       TINYINT DEFAULT 1,
    sort_order   INT DEFAULT 0,
    created_at   DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$court_id = isset($_GET['court_id']) ? (int)$_GET['court_id'] : null;

$today = date('Y-m-d');

if ($court_id) {
    // Lấy gói của sân + gói global
    $stmt = $mysqli->prepare(
        "SELECT mp.*, c.name AS court_name
         FROM membership_plans mp
         LEFT JOIN courts c ON c.id = mp.court_id
         WHERE mp.status = 1
           AND (mp.court_id = ? OR mp.court_id IS NULL)
           AND (mp.sale_start IS NULL OR mp.sale_start <= ?)
           AND (mp.sale_end IS NULL OR mp.sale_end >= ?)
         ORDER BY mp.court_id IS NOT NULL DESC, mp.sort_order, mp.id"
    );
    $stmt->bind_param('iss', $court_id, $today, $today);
} else {
    // Chỉ lấy gói global
    $stmt = $mysqli->prepare(
        "SELECT mp.*, c.name AS court_name
         FROM membership_plans mp
         LEFT JOIN courts c ON c.id = mp.court_id
         WHERE mp.status = 1
           AND mp.court_id IS NULL
           AND (mp.sale_start IS NULL OR mp.sale_start <= ?)
           AND (mp.sale_end IS NULL OR mp.sale_end >= ?)
         ORDER BY mp.sort_order, mp.id"
    );
    $stmt->bind_param('ss', $today, $today);
}

$stmt->execute();
$plans = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Nếu không có trong DB, trả về fallback
if (empty($plans)) {
    $plans = [
        ['id'=>1,'court_id'=>null,'court_name'=>null,'name'=>'COMBO CHIỀU 14H–17H','detail'=>'10 VÉ TẶNG 1 VÉ','price_per'=>80000,'total_price'=>720000,'months'=>3,'free_tickets'=>11,'time_range'=>'14H–17H','sale_start'=>null,'sale_end'=>null],
        ['id'=>2,'court_id'=>null,'court_name'=>null,'name'=>'COMBO CHIỀU 14H–17H','detail'=>'20 VÉ TẶNG 2 VÉ','price_per'=>80000,'total_price'=>1440000,'months'=>6,'free_tickets'=>22,'time_range'=>'14H–17H','sale_start'=>null,'sale_end'=>null],
        ['id'=>3,'court_id'=>null,'court_name'=>null,'name'=>'COMBO TỐI 20H–22H','detail'=>'20 VÉ TẶNG 2 VÉ','price_per'=>80000,'total_price'=>1440000,'months'=>9,'free_tickets'=>22,'time_range'=>'20H–22H','sale_start'=>null,'sale_end'=>null],
        ['id'=>4,'court_id'=>null,'court_name'=>null,'name'=>'COMBO TỐI 20H–22H','detail'=>'30 VÉ TẶNG 3 VÉ','price_per'=>80000,'total_price'=>2160000,'months'=>12,'free_tickets'=>33,'time_range'=>'20H–22H','sale_start'=>null,'sale_end'=>null],
    ];
}

ob_end_clean();
echo json_encode(['success' => true, 'plans' => $plans, 'court_id' => $court_id]);
