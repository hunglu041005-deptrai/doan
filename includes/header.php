<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/functions.php';
$isAdminPage = $isAdminPage ?? false;
$isCoachPage = $isCoachPage ?? false;

// Chặn admin truy cập trang web thường
if (!$isAdminPage && isAdmin()) {
    $current_path = $_SERVER['REQUEST_URI'];
    if (strpos($current_path, '/admin/') === false) {
        header('Location: admin/dashboard.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Nền tảng đặt sân cầu lông trực tuyến hàng đầu Hà Nội. Đặt sân nhanh, giá rẻ, thanh toán linh hoạt.">
    <title><?php echo $isAdminPage ? 'Admin - Hưng Dũng Booking' : 'Hưng Dũng Booking - Đặt Sân Cầu Lông'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <?php if (isLoggedIn()): ?>
        <script src="assets/js/notifications.js" defer></script>
    <?php endif; ?>
</head>
<body>
<nav class="navbar navbar-expand-lg <?php echo $isAdminPage ? 'navbar-dark bg-dark' : 'navbar-light navbar-custom'; ?> shadow-sm sticky-top">
    <div class="container-lg">
        <a class="navbar-brand fw-bold d-flex align-items-center" href="<?php echo $isAdminPage ? 'dashboard.php' : '../index.php'; ?>">
            <i class="fas fa-badminton me-2" style="font-size: 1.5rem; color: #ff6b35;"></i>
            <span>Hưng Dũng<span style="color: #ff6b35;">Booking</span></span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-label="Toggle menu">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <?php if ($isAdminPage): ?>
                    <li class="nav-item"><a class="nav-link" href="dashboard.php"><i class="fas fa-chart-line me-1"></i> Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="courts.php"><i class="fas fa-badminton me-1"></i> Quản lý sân</a></li>
                    <li class="nav-item"><a class="nav-link" href="bookings.php"><i class="fas fa-calendar-check me-1"></i> Đơn đặt</a></li>
                    <li class="nav-item"><a class="nav-link" href="shop.php"><i class="fas fa-shopping-cart me-1"></i> Shop</a></li>
                    <li class="nav-item"><a class="nav-link" href="users.php"><i class="fas fa-users me-1"></i> Người dùng</a></li>
                    <li class="nav-item"><a class="nav-link" href="promotions.php"><i class="fas fa-gift me-1"></i> Ưu đãi</a></li>
                    <li class="nav-item"><a class="nav-link" href="coaches.php"><i class="fas fa-chalkboard-teacher me-1"></i> HLV</a></li>
                    <li class="nav-item"><a class="nav-link" href="support.php"><i class="fas fa-headset me-1"></i> Hỗ trợ</a></li>
                    <li class="nav-item"><a class="nav-link" href="payment-transactions.php"><i class="fas fa-exchange-alt me-1"></i> Giao dịch</a></li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link active" href="index.php">Trang chủ</a></li>
                    <li class="nav-item"><a class="nav-link" href="featured.php">Nổi bật</a></li>
                    <li class="nav-item"><a class="nav-link" href="discover.php">Khám phá</a></li>
                    <li class="nav-item"><a class="nav-link" href="map.php">Bản đồ</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?php echo isLoggedIn() ? 'profile.php' : 'login.php'; ?>">Tài khoản</a></li>
                <?php endif; ?>
            </ul>
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-center">
                <?php if (isLoggedIn()): ?>
                    <?php if (!$isAdminPage): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-user-circle me-1"></i> <?php echo escape($_SESSION['name']); ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                <li><a class="dropdown-item" href="profile.php"><i class="fas fa-id-card me-2"></i> Hồ sơ</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="booking-history.php"><i class="fas fa-list me-2"></i> Lịch sử đặt sân</a></li>
                                <li><a class="dropdown-item" href="order-history.php"><i class="fas fa-shopping-bag me-2"></i> Đơn hàng của tôi</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <?php if (isAdmin()): ?>
                                    <li><a class="dropdown-item" href="admin/dashboard.php"><i class="fas fa-cog me-2"></i> Admin Panel</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                <?php endif; ?>
                                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'coach'): ?>
                                    <li><a class="dropdown-item" href="coach/dashboard.php"><i class="fas fa-chalkboard-teacher me-2 text-primary"></i> HLV Dashboard</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                <?php endif; ?>
                                <li><a class="dropdown-item text-danger" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> Đăng xuất</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <?php
                            $logoutPath = $isCoachPage ? '../logout.php' : 'logout.php';
                            ?>
                            <a class="nav-link text-danger" href="<?php echo $logoutPath; ?>">
                                <i class="fas fa-sign-out-alt me-1"></i> Đăng xuất
                            </a>
                        </li>
                    <?php endif; ?>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="login.php"><i class="fas fa-sign-in-alt me-1"></i> Đăng nhập</a></li>
                    <li class="nav-item"><a class="nav-link ms-2" href="register.php"><button class="btn btn-sm btn-outline-light">Đăng ký</button></a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
<main class="min-vh-100">
