<?php
require_once __DIR__ . '/../db.php';

function getCourts($filters = []) {
    global $mysqli;

    // Tạo bảng booking_reviews nếu chưa có
    $mysqli->query("CREATE TABLE IF NOT EXISTS booking_reviews (
        id         INT AUTO_INCREMENT PRIMARY KEY,
        booking_id INT DEFAULT NULL,
        user_id    INT NOT NULL,
        court_id   INT NOT NULL,
        rating     TINYINT(1) NOT NULL,
        review_text TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_court (court_id),
        INDEX idx_user  (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $sql = 'SELECT c.*,
                COALESCE(ROUND(AVG(r.rating), 1), 0) AS avg_rating,
                COUNT(r.id) AS review_count
            FROM courts c
            LEFT JOIN booking_reviews r ON r.court_id = c.id
            WHERE c.status = 1';
    $params = [];
    $types = '';

    if (!empty($filters['q'])) {
        $sql .= ' AND (c.name LIKE ? OR c.description LIKE ? OR c.location LIKE ?)';
        $qVal = '%' . $filters['q'] . '%';
        $params[] = $qVal;
        $params[] = $qVal;
        $params[] = $qVal;
        $types .= 'sss';
    }
    if (!empty($filters['location'])) {
        $sql .= ' AND c.location LIKE ?';
        $params[] = '%' . $filters['location'] . '%';
        $types .= 's';
    }
    if (!empty($filters['min_price']) && is_numeric($filters['min_price'])) {
        $sql .= ' AND c.price_per_hour >= ?';
        $params[] = (int)$filters['min_price'];
        $types .= 'i';
    }
    if (!empty($filters['max_price']) && is_numeric($filters['max_price'])) {
        $sql .= ' AND c.price_per_hour <= ?';
        $params[] = (int)$filters['max_price'];
        $types .= 'i';
    }
    if (empty($filters['min_price']) && empty($filters['max_price']) && !empty($filters['price'])) {
        if ($filters['price'] === 'low') {
            $sql .= ' AND c.price_per_hour <= 100000';
        } elseif ($filters['price'] === 'mid') {
            $sql .= ' AND c.price_per_hour BETWEEN 100001 AND 150000';
        } elseif ($filters['price'] === 'high') {
            $sql .= ' AND c.price_per_hour > 150000';
        }
    }

    // Lọc theo danh mục (DB-level)
    if (!empty($filters['category'])) {
        $sql .= ' AND c.category LIKE ?';
        $params[] = '%' . $filters['category'] . '%';
        $types .= 's';
    }

    // Lọc theo đánh giá (dùng HAVING vì avg_rating là aggregate)
    $havingClauses = [];
    if (!empty($filters['rating'])) {
        $ratingVal = (float)$filters['rating'];
        $havingClauses[] = 'avg_rating >= ' . $ratingVal;
    }

    // Lọc theo danh mục nổi bật
    $category = $filters['category'] ?? '';
    $sort = $filters['sort'] ?? '';

    $sql .= ' GROUP BY c.id';

    if (!empty($havingClauses)) {
        $sql .= ' HAVING ' . implode(' AND ', $havingClauses);
    }

    // Xác định ORDER BY dựa trên sort
    if ($sort === 'price_asc') {
        $sql .= ' ORDER BY c.price_per_hour ASC';
    } elseif ($sort === 'price_desc') {
        $sql .= ' ORDER BY c.price_per_hour DESC';
    } elseif ($sort === 'newest') {
        $sql .= ' ORDER BY c.created_at DESC';
    } elseif ($sort === 'rating') {
        $sql .= ' ORDER BY avg_rating DESC';
    } else {
        $sql .= ' ORDER BY c.created_at DESC';
    }

    $stmt = $mysqli->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $courts = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $courts;
}

function getCourtById($id) {
    global $mysqli;
    $stmt = $mysqli->prepare(
        'SELECT c.*,
                COALESCE(ROUND(AVG(r.rating), 1), 0) AS avg_rating,
                COUNT(r.id) AS review_count
         FROM courts c
         LEFT JOIN booking_reviews r ON r.court_id = c.id
         WHERE c.id = ? AND c.status = 1
         GROUP BY c.id'
    );
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $court = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $court;
}
function getCourtAvailability($court_id, $date) {
    global $mysqli;
    $sql = 'SELECT start_time, end_time FROM bookings WHERE court_id = ? AND booking_date = ? AND status != "cancelled"';
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('is', $court_id, $date);
    $stmt->execute();
    $result = $stmt->get_result();
    $slots = [];
    while ($row = $result->fetch_assoc()) {
        $slots[] = $row;
    }
    $stmt->close();
    return $slots;
}

function isSlotAvailable($court_id, $date, $start, $end) {
    global $mysqli;
    $sql = 'SELECT COUNT(*) AS count FROM bookings WHERE court_id = ? AND booking_date = ? AND status != "cancelled" AND NOT (end_time <= ? OR start_time >= ?)';
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('isss', $court_id, $date, $start, $end);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    return $row['count'] == 0;
}

function getUserBookings($user_id) {
    global $mysqli;

    // Đảm bảo cột discount_amount và promo_applied tồn tại
    $mysqli->query("ALTER TABLE bookings ADD COLUMN IF NOT EXISTS discount_amount INT NOT NULL DEFAULT 0");
    $mysqli->query("ALTER TABLE bookings ADD COLUMN IF NOT EXISTS promo_applied VARCHAR(150) DEFAULT NULL");
    // courts.phone
    $chkP = $mysqli->query("SHOW COLUMNS FROM courts LIKE 'phone'");
    if ($chkP && $chkP->num_rows === 0) {
        $mysqli->query("ALTER TABLE courts ADD COLUMN phone VARCHAR(20) DEFAULT NULL");
    }

    $stmt = $mysqli->prepare(
        'SELECT b.*,
                c.name AS court_name,
                c.location,
                COALESCE(c.phone, \'0968073500\') AS court_phone,
                COALESCE(b.discount_amount, 0)   AS discount_amount,
                COALESCE(b.promo_applied, \'\')   AS promo_applied
         FROM bookings b
         JOIN courts c ON b.court_id = c.id
         WHERE b.user_id = ?
         ORDER BY b.booking_date DESC, b.start_time DESC'
    );
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $bookings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $bookings;
}

function getLocations() {
    global $mysqli;
    $result = $mysqli->query('SELECT DISTINCT location FROM courts WHERE status = 1 AND location != "" ORDER BY location ASC');
    $locations = [];
    while ($row = $result->fetch_assoc()) {
        $locations[] = $row['location'];
    }
    return $locations;
}

function getBookingCounts() {
    global $mysqli;
    $result = $mysqli->query('SELECT status, COUNT(*) AS total FROM bookings GROUP BY status');
    $counts = [];
    while ($row = $result->fetch_assoc()) {
        $counts[$row['status']] = $row['total'];
    }
    return $counts;
}

function getRevenueByMonth() {
    global $mysqli;
    $result = $mysqli->query('SELECT DATE_FORMAT(created_at, "%Y-%m") AS month, SUM(total_price) AS revenue FROM bookings WHERE status = "confirmed" GROUP BY month ORDER BY month ASC');
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    return $data;
}

function getAllUsers() {
    global $mysqli;
    $result = $mysqli->query('SELECT id, name, email, role, status, created_at FROM users ORDER BY created_at DESC');
    return $result->fetch_all(MYSQLI_ASSOC);
}

function getAllBookings() {
    global $mysqli;
    $result = $mysqli->query('SELECT b.*, u.name AS user_name, u.email AS user_email, c.name AS court_name FROM bookings b JOIN users u ON b.user_id = u.id JOIN courts c ON b.court_id = c.id ORDER BY b.booking_date DESC, b.start_time DESC');
    return $result->fetch_all(MYSQLI_ASSOC);
}

function getAllCourts() {
    global $mysqli;
    $result = $mysqli->query('SELECT * FROM courts ORDER BY id DESC');
    return $result->fetch_all(MYSQLI_ASSOC);
}

function getCourtCount() {
    global $mysqli;
    $result = $mysqli->query('SELECT COUNT(*) AS total FROM courts WHERE status = 1');
    $row = $result->fetch_assoc();
    return $row['total'] ?? 0;
}

function getRecentBookings($limit = 5) {
    global $mysqli;
    $stmt = $mysqli->prepare('SELECT b.id, b.booking_date, b.start_time, b.end_time, b.total_price, b.status, u.name AS user_name, c.name AS court_name FROM bookings b JOIN users u ON b.user_id = u.id JOIN courts c ON b.court_id = c.id ORDER BY b.created_at DESC LIMIT ?');
    $stmt->bind_param('i', $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    $bookings = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $bookings;
}

function getBookingById($booking_id) {
    global $mysqli;
    $stmt = $mysqli->prepare('
        SELECT b.*, u.email as user_email, u.name as user_name, c.name as court_name
        FROM bookings b
        JOIN users u ON b.user_id = u.id
        JOIN courts c ON b.court_id = c.id
        WHERE b.id = ?
    ');
    $stmt->bind_param('i', $booking_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $booking = $result->fetch_assoc();
    $stmt->close();
    return $booking;
}

// Thêm chức năng admin đơn giản
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function requireAdmin() {
    if (!isAdmin()) {
        header('Location: ../login.php');
        exit;
    }
}

function blockAdminFromPublic() {
    if (isAdmin()) {
        header('Location: admin-redirect.php');
        exit;
    }
    // Coach cũng chỉ vào được trang coach
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'coach') {
        $allowedPaths = ['coach/', 'login.php', 'logout.php'];
        $currentUri = $_SERVER['REQUEST_URI'];
        $isAllowed = false;
        foreach ($allowedPaths as $p) {
            if (strpos($currentUri, $p) !== false) {
                $isAllowed = true;
                break;
            }
        }
        if (!$isAllowed) {
            header('Location: coach/dashboard.php');
            exit;
        }
    }
}

function redirect($url) {
    header('Location: ' . $url);
    exit;
}

function escape($value) {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

// Shop functions
function getShopCategories() {
    global $mysqli;
    $result = $mysqli->query('SELECT * FROM product_categories WHERE status = 1 ORDER BY sort_order, name');
    return $result->fetch_all(MYSQLI_ASSOC);
}

function getShopProducts($category_id = null, $limit = null) {
    global $mysqli;
    $sql = 'SELECT p.*, c.name as category_name FROM products p LEFT JOIN product_categories c ON p.category_id = c.id WHERE p.status = 1';
    
    if ($category_id) {
        $sql .= ' AND p.category_id = ' . intval($category_id);
    }
    
    $sql .= ' ORDER BY p.featured DESC, p.created_at DESC';
    
    if ($limit) {
        $sql .= ' LIMIT ' . intval($limit);
    }
    
    $result = $mysqli->query($sql);
    return $result->fetch_all(MYSQLI_ASSOC);
}

function getProductById($id) {
    global $mysqli;
    $stmt = $mysqli->prepare('SELECT p.*, c.name as category_name FROM products p LEFT JOIN product_categories c ON p.category_id = c.id WHERE p.id = ? AND p.status = 1');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();
    $stmt->close();
    return $product;
}

function getProductVariants($product_id) {
    global $mysqli;
    $stmt = $mysqli->prepare('SELECT * FROM product_variants WHERE product_id = ? AND status = 1 ORDER BY type, value');
    $stmt->bind_param('i', $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $variants = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $variants;
}

function getShopStats() {
    global $mysqli;
    $stats = [];
    
    $stats['total_products'] = $mysqli->query('SELECT COUNT(*) as count FROM products WHERE status = 1')->fetch_assoc()['count'];
    $stats['total_categories'] = $mysqli->query('SELECT COUNT(*) as count FROM product_categories WHERE status = 1')->fetch_assoc()['count'];
    $stats['low_stock'] = $mysqli->query('SELECT COUNT(*) as count FROM products WHERE stock_quantity <= 5 AND status = 1')->fetch_assoc()['count'];
    $stats['total_orders'] = $mysqli->query('SELECT COUNT(*) as count FROM orders')->fetch_assoc()['count'];
    
    return $stats;
}

/**
 * Render 5 ngôi sao từ giá trị rating (0–5, hỗ trợ nửa sao)
 */
function renderStars($rating, $max = 5) {
    $html  = '';
    $full  = floor($rating);
    $half  = ($rating - $full) >= 0.3 ? 1 : 0;
    $empty = $max - $full - $half;
    for ($i = 0; $i < $full;  $i++) $html .= '<i class="fas fa-star star-filled"></i>';
    if ($half)                        $html .= '<i class="fas fa-star-half-alt star-half"></i>';
    for ($i = 0; $i < $empty; $i++) $html .= '<i class="far fa-star star-empty"></i>';
    return $html;
}

/**
 * Lấy ưu đãi đang active NGAY BÂY theo giờ hiện tại + ngày hiện tại
 * Trả về array ['pct', 'title', 'from', 'to', 'text', 'time_start', 'time_end'] hoặc null
 */
function getActivePromo() {
    global $mysqli;
    static $cached = false;
    if ($cached !== false) return $cached;

    try {
        $nowTime   = date('H:i:s');
        $nowDate   = date('Y-m-d');
        $dayOfWeek = (int)date('N'); // 1=T2, 7=CN
        $isWeekend = in_array($dayOfWeek, [6, 7]);

        // Đảm bảo cột apply_date tồn tại
        $chk = $mysqli->query("SHOW COLUMNS FROM promotions LIKE 'apply_date'");
        $hasDate = ($chk && $chk->num_rows > 0);

        $sql = "SELECT * FROM promotions WHERE status=1 AND discount_pct > 0 ORDER BY discount_pct DESC LIMIT 10";
        $res = $mysqli->query($sql);
        if (!$res) { $cached = null; return null; }

        while ($pr = $res->fetch_assoc()) {
            $ok = true;

            // Kiểm tra ngày cụ thể (apply_date)
            if ($hasDate && !empty($pr['apply_date'])) {
                if ($pr['apply_date'] !== $nowDate) { continue; } // không phải hôm nay → bỏ qua
            }

            // Kiểm tra khung giờ
            if ($pr['time_start'] && $pr['time_end']) {
                $ts = substr($pr['time_start'], 0, 5);
                $te = substr($pr['time_end'],   0, 5);
                if (!($nowTime >= $ts . ':00' && $nowTime < $te . ':00')) $ok = false;
            }

            // Điều kiện cuối tuần
            if ($pr['apply_weekend'] && !$isWeekend) $ok = false;

            if ($ok) {
                $cached = [
                    'pct'        => (int)$pr['discount_pct'],
                    'title'      => $pr['title'],
                    'from'       => $pr['color_from'],
                    'to'         => $pr['color_to'],
                    'text'       => $pr['text_color'],
                    'time_start' => $pr['time_start'] ? substr($pr['time_start'], 0, 5) : null,
                    'time_end'   => $pr['time_end']   ? substr($pr['time_end'],   0, 5) : null,
                    'apply_date' => $pr['apply_date'] ?? null,
                ];
                return $cached;
            }
        }
    } catch (Exception $e) {}

    $cached = null;
    return null;
}

/**
 * Tính giá sân sau khi áp ưu đãi hiện tại
 * Trả về ['original'=>int, 'final'=>int, 'pct'=>int, 'has_promo'=>bool]
 */
function calcCourtPrice($price_per_hour) {
    $promo = getActivePromo();
    if (!$promo || $promo['pct'] <= 0) {
        return ['original' => $price_per_hour, 'final' => $price_per_hour, 'pct' => 0, 'has_promo' => false];
    }
    $discounted = (int)round($price_per_hour * (100 - $promo['pct']) / 100);
    return [
        'original'  => $price_per_hour,
        'final'     => $discounted,
        'pct'       => $promo['pct'],
        'has_promo' => true,
        'promo'     => $promo,
    ];
}

// ============================================================
// MEMBERSHIP FUNCTIONS
// ============================================================

/**
 * Lấy gói hội viên đang active của user (còn hạn + đã thanh toán hoặc tiền mặt)
 */
function getActiveMembership($user_id) {
    global $mysqli;

    // Auto-expire: cập nhật các gói đã hết hạn
    $mysqli->query("UPDATE memberships SET status='expired' WHERE status='active' AND end_date < CURDATE()");

    $stmt = $mysqli->prepare(
        'SELECT * FROM memberships
         WHERE user_id = ? AND status = "active"
         ORDER BY end_date DESC LIMIT 1'
    );
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $m = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $m;
}

/**
 * Số vé còn lại của membership
 */
function getMembershipTicketsRemaining($membership) {
    if (!$membership) return 0;
    return max(0, (int)($membership['free_tickets'] ?? 0) - (int)($membership['tickets_used'] ?? 0));
}

/**
 * Kiểm tra user có quyền hội viên hợp lệ không
 * Trả về membership array hoặc null
 */
function checkMemberBenefit($user_id) {
    if (!$user_id) return null;
    return getActiveMembership($user_id);
}

/**
 * Dùng 1 vé hội viên cho booking
 * Trả về true nếu thành công
 */
function useMemberTicket($membership_id, $user_id, $booking_id = null, $note = '') {
    global $mysqli;

    // Kiểm tra còn vé không
    $stmt = $mysqli->prepare(
        'SELECT id, free_tickets, tickets_used FROM memberships
         WHERE id = ? AND user_id = ? AND status = "active" AND end_date >= CURDATE()'
    );
    $stmt->bind_param('ii', $membership_id, $user_id);
    $stmt->execute();
    $m = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$m) return false;
    if ($m['tickets_used'] ?? 0 >= $m['free_tickets'] ?? 0) return false;

    // Trừ vé
    $upd = $mysqli->prepare('UPDATE memberships SET tickets_used = tickets_used + 1 WHERE id = ?');
    $upd->bind_param('i', $membership_id);
    $upd->execute();
    $upd->close();

    // Ghi log
    $log = $mysqli->prepare(
        'INSERT INTO membership_ticket_logs (membership_id, user_id, booking_id, action, tickets_delta, note)
         VALUES (?, ?, ?, "use", -1, ?)'
    );
    $log->bind_param('iiis', $membership_id, $user_id, $booking_id, $note);
    $log->execute();
    $log->close();

    return true;
}

/**
 * Hoàn vé (khi huỷ booking)
 */
function refundMemberTicket($membership_id, $user_id, $booking_id = null) {
    global $mysqli;

    $upd = $mysqli->prepare(
        'UPDATE memberships SET tickets_used = GREATEST(0, tickets_used - 1) WHERE id = ? AND user_id = ?'
    );
    $upd->bind_param('ii', $membership_id, $user_id);
    $upd->execute();
    $upd->close();

    $log = $mysqli->prepare(
        'INSERT INTO membership_ticket_logs (membership_id, user_id, booking_id, action, tickets_delta, note)
         VALUES (?, ?, ?, "refund", 1, "Hoàn vé do huỷ booking")'
    );
    $log->bind_param('iii', $membership_id, $user_id, $booking_id);
    $log->execute();
    $log->close();

    return true;
}

/**
 * Giá hội viên cố định = 80,000đ/giờ
 */
function getMemberPrice() {
    return 80000;
}

/**
 * Tính giá khi booking có dùng vé hội viên
 * $hours: số giờ đặt
 * Trả về ['price' => int, 'discount' => int, 'used_ticket' => bool]
 */
function calcMemberBookingPrice($court_price_per_hour, $hours, $membership) {
    if (!$membership) {
        return ['price' => $court_price_per_hour * $hours, 'discount' => 0, 'used_ticket' => false];
    }

    $remaining = getMembershipTicketsRemaining($membership);
    $is_valid   = ($membership['status'] === 'active') && (strtotime($membership['end_date']) >= time());

    if (!$is_valid || $remaining <= 0) {
        return ['price' => $court_price_per_hour * $hours, 'discount' => 0, 'used_ticket' => false];
    }

    $member_price = getMemberPrice() * $hours;
    $normal_price = $court_price_per_hour * $hours;
    $discount     = max(0, $normal_price - $member_price);

    return [
        'price'       => $member_price,
        'discount'    => $discount,
        'used_ticket' => true,
        'membership'  => $membership,
    ];
}

/**
 * Lấy lịch sử membership của user
 */
function getUserMemberships($user_id) {
    global $mysqli;
    $mysqli->query("UPDATE memberships SET status='expired' WHERE status='active' AND end_date < CURDATE() AND user_id = $user_id");

    $stmt = $mysqli->prepare(
        'SELECT * FROM memberships WHERE user_id = ? ORDER BY created_at DESC'
    );
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $list = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $list;
}

/**
 * Lấy lịch sử dùng vé
 */
function getMembershipTicketLogs($membership_id, $limit = 20) {
    global $mysqli;
    $stmt = $mysqli->prepare(
        'SELECT l.*, b.booking_date, b.start_time, b.end_time,
                c.name AS court_name
         FROM membership_ticket_logs l
         LEFT JOIN bookings b ON b.id = l.booking_id
         LEFT JOIN courts c ON c.id = b.court_id
         WHERE l.membership_id = ?
         ORDER BY l.created_at DESC LIMIT ?'
    );
    $stmt->bind_param('ii', $membership_id, $limit);
    $stmt->execute();
    $logs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $logs;
}

/**
 * Tạo bookings định kỳ từ gói hội viên có time_range + court_id
 *
 * @param int    $membership_id
 * @param int    $user_id
 * @param int    $court_id
 * @param string $time_range   VD: "14H-17H" hoặc "14H–17H"
 * @param string $start_date   "2026-07-26"
 * @param string $end_date     "2026-10-26"
 * @param string $payment_method
 * @return array ['created'=>int, 'skipped'=>int]
 */
function createMembershipBookings($membership_id, $user_id, $court_id, $time_range, $start_date, $end_date, $payment_method = 'cash') {
    global $mysqli;

    if (!$court_id || !$time_range || !$start_date || !$end_date) return ['created'=>0,'skipped'=>0];

    // Parse time_range: "14H-17H", "14H–17H", "20H–22H" (hỗ trợ mọi dấu phân cách)
    if (preg_match('/(\d{1,2})\s*[Hh]\s*[^\d]\s*(\d{1,2})\s*[Hh]/u', $time_range, $m)) {
        $start_hour = (int)$m[1];
        $end_hour   = (int)$m[2];
    } elseif (preg_match('/(\d{1,2}):(\d{2})\s*[\-–—]\s*(\d{1,2}):(\d{2})/u', $time_range, $m)) {
        $start_hour = (int)$m[1];
        $end_hour   = (int)$m[3];
    } else {
        return ['created'=>0,'skipped'=>0,'error'=>'Cannot parse time_range: '.$time_range];
    }

    if ($start_hour >= $end_hour || $start_hour < 0 || $end_hour > 24) {
        return ['created'=>0,'skipped'=>0,'error'=>'Invalid time range'];
    }

    // Đảm bảo các cột cần thiết tồn tại
    $chkBT = $mysqli->query("SHOW COLUMNS FROM bookings LIKE 'booking_type'");
    if ($chkBT && $chkBT->num_rows === 0) {
        $mysqli->query("ALTER TABLE bookings ADD COLUMN booking_type VARCHAR(20) DEFAULT 'single'");
    }
    $chkMID = $mysqli->query("SHOW COLUMNS FROM bookings LIKE 'membership_id'");
    if ($chkMID && $chkMID->num_rows === 0) {
        $mysqli->query("ALTER TABLE bookings ADD COLUMN membership_id INT DEFAULT NULL");
    }
    $chkDa = $mysqli->query("SHOW COLUMNS FROM bookings LIKE 'discount_amount'");
    if ($chkDa && $chkDa->num_rows === 0) {
        $mysqli->query("ALTER TABLE bookings ADD COLUMN discount_amount INT NOT NULL DEFAULT 0");
        $mysqli->query("ALTER TABLE bookings ADD COLUMN promo_applied VARCHAR(150) DEFAULT NULL");
    }

    $payment_status = ($payment_method === 'cash') ? 'unpaid' : 'pending';
    $created = 0;
    $skipped = 0;

    $dtCurrent = new DateTime($start_date);
    $dtEnd     = new DateTime($end_date);

    $start_time_str = sprintf('%02d:00:00', $start_hour);
    $end_time_str   = sprintf('%02d:00:00', $end_hour);

    // Giá mỗi buổi = giá hội viên × số giờ
    $hours = $end_hour - $start_hour;
    $price_per_session = getMemberPrice() * $hours;

    $stmt = $mysqli->prepare(
        'INSERT INTO bookings
         (user_id, court_id, booking_date, start_time, end_time, total_price,
          payment_method, payment_status, status, notes, booking_type, membership_id, discount_amount, promo_applied)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, "confirmed", ?, "membership", ?, 0, "")'
    );

    while ($dtCurrent <= $dtEnd) {
        $dateStr = $dtCurrent->format('Y-m-d');

        if (isSlotAvailable($court_id, $dateStr, $start_time_str, $end_time_str)) {
            $notes = 'Gói hội viên #' . $membership_id;
            // i  i          s        s                s              i                    s               s               s      i
            // uid court_id  date     start_time        end_time      price                pay_method      pay_status      notes  membership_id
            $stmt->bind_param(
                'iisssisssi',
                $user_id, $court_id, $dateStr,
                $start_time_str, $end_time_str,
                $price_per_session,
                $payment_method, $payment_status,
                $notes, $membership_id
            );
            if ($stmt->execute()) {
                $created++;
            }
        } else {
            $skipped++;
        }

        $dtCurrent->modify('+1 day');
    }

    $stmt->close();
    return ['created' => $created, 'skipped' => $skipped];
}
