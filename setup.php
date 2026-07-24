<?php
$host = 'localhost';
$user = 'root';
$pass = '';
$db = 'badminton_booking';

$mysqli = new mysqli($host, $user, $pass);
if ($mysqli->connect_error) {
    die('Database connection failed: ' . $mysqli->connect_error);
}

$mysqli->set_charset('utf8mb4');
$mysqli->query("CREATE DATABASE IF NOT EXISTS `$db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$mysqli->select_db($db);

$queries = [
    "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(120) NOT NULL,
        email VARCHAR(150) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        phone VARCHAR(30),
        role ENUM('user','admin') NOT NULL DEFAULT 'user',
        status TINYINT NOT NULL DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "CREATE TABLE IF NOT EXISTS courts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(150) NOT NULL,
        location VARCHAR(150) NOT NULL,
        price_per_hour INT NOT NULL,
        cover_image VARCHAR(255) DEFAULT '',
        description TEXT,
        status TINYINT NOT NULL DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "CREATE TABLE IF NOT EXISTS court_images (
        id INT AUTO_INCREMENT PRIMARY KEY,
        court_id INT NOT NULL,
        image_path VARCHAR(255) NOT NULL,
        sort_order INT NOT NULL DEFAULT 0,
        FOREIGN KEY (court_id) REFERENCES courts(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "CREATE TABLE IF NOT EXISTS bookings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        court_id INT NOT NULL,
        booking_date DATE NOT NULL,
        start_time TIME NOT NULL,
        end_time TIME NOT NULL,
        total_price INT NOT NULL,
        payment_method ENUM('cash','momo','vnpay') NOT NULL,
        payment_status ENUM('pending','paid') NOT NULL DEFAULT 'pending',
        status ENUM('pending','confirmed','cancelled') NOT NULL DEFAULT 'pending',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (court_id) REFERENCES courts(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
];

foreach ($queries as $sql) {
    if (!$mysqli->query($sql)) {
        die('Table creation failed: ' . $mysqli->error);
    }
}

$adminEmail = 'admin@badminton.local';
$adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
$result = $mysqli->query("SELECT id FROM users WHERE email = '$adminEmail'");
if (!$result->num_rows) {
    $mysqli->query("INSERT INTO users (name, email, password, role) VALUES ('Admin Badminton', '$adminEmail', '$adminPassword', 'admin')");
}

$courts = [
    [
        'name' => 'Sân Cầu Lông Bách Khoa',
        'location' => 'Nguyễn Du',
        'price' => 120000,
        'image' => 'https://images.unsplash.com/photo-1517649763962-0c623066013b?auto=format&fit=crop&w=900&q=80',
        'description' => 'Sân cầu lông tiêu chuẩn 2 người, mặt sân trải thảm chuyên dụng.'
    ],
    [
        'name' => 'Sân Cầu Lông Phạm Ngọc Thạch',
        'location' => 'Trung Hòa',
        'price' => 90000,
        'image' => 'https://images.unsplash.com/photo-1521412644187-c49fa049e84d?auto=format&fit=crop&w=900&q=80',
        'description' => 'Sân rộng, mái che, phù hợp đội nhóm và gia đình.'
    ],
    [
        'name' => 'Sân Cầu Lông Cầu Giấy',
        'location' => 'Dịch Vọng',
        'price' => 100000,
        'image' => 'https://images.unsplash.com/photo-1521412644187-c49fa049e84d?auto=format&fit=crop&w=900&q=81',
        'description' => 'Sân mới, hệ thống chiếu sáng ban đêm và phòng thay đồ.'
    ],
    [
        'name' => 'Sân Cầu Lông Hoàn Kiếm',
        'location' => 'Hàng Bông',
        'price' => 130000,
        'image' => 'https://images.unsplash.com/photo-1521412644187-c49fa049e84d?auto=format&fit=crop&w=900&q=82',
        'description' => 'Sân trung tâm, dễ đi lại, phù hợp khách thuê ngắn hạn.'
    ],
    [
        'name' => 'Sân Cầu Lông Tây Hồ',
        'location' => 'Nhật Tân',
        'price' => 140000,
        'image' => 'https://images.unsplash.com/photo-1521412644187-c49fa049e84d?auto=format&fit=crop&w=900&q=83',
        'description' => 'Sân cao cấp với hệ thống chiếu sáng và phục vụ tốt.'
    ],
    [
        'name' => 'Sân Cầu Lông Thanh Xuân',
        'location' => 'Văn Quán',
        'price' => 110000,
        'image' => 'https://images.unsplash.com/photo-1568085047845-1848d7a99114?auto=format&fit=crop&w=900&q=80',
        'description' => 'Sân sạch, gần trung tâm thương mại, phù hợp đội nhóm.'
    ],
    [
        'name' => 'Sân Cầu Lông Long Biên',
        'location' => 'Bồ Đề',
        'price' => 115000,
        'image' => 'https://images.unsplash.com/photo-1568085047845-1848d7a99114?auto=format&fit=crop&w=900&q=81',
        'description' => 'Sân mới, không gian thoáng và dễ di chuyển qua cầu.'
    ],
    [
        'name' => 'Sân Cầu Lông Ba Đình',
        'location' => 'Trúc Bạch',
        'price' => 125000,
        'image' => 'https://images.unsplash.com/photo-1568085047845-1848d7a99114?auto=format&fit=crop&w=900&q=82',
        'description' => 'Sân chất lượng, nằm trong khu vực văn phòng và gần hồ Tây.'
    ],
];

foreach ($courts as $court) {
    $stmt = $mysqli->prepare('SELECT id FROM courts WHERE name = ?');
    $stmt->bind_param('s', $court['name']);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows === 0) {
        $insert = $mysqli->prepare('INSERT INTO courts (name, location, price_per_hour, cover_image, description) VALUES (?, ?, ?, ?, ?)');
        $insert->bind_param('sisss', $court['name'], $court['location'], $court['price'], $court['image'], $court['description']);
        $insert->execute();
        $insert->close();
    }
    $stmt->close();
}

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Badminton Booking</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="card shadow-sm">
        <div class="card-body">
            <h1 class="h3 mb-3">Thiết lập hệ thống đặt sân cầu lông</h1>
            <p class="mb-2">Database <strong><?php echo $db; ?></strong> đã được tạo và bảng đã được thiết lập.</p>
            <p class="mb-2">Tài khoản admin:</p>
            <ul>
                <li>Email: <strong><?php echo $adminEmail; ?></strong></li>
                <li>Mật khẩu: <strong>admin123</strong></li>
            </ul>
            <p class="mb-0">Mở <a href="index.php">index.php</a> để kiểm tra trang chủ.</p>
        </div>
    </div>
</div>
</body>
</html>
