<?php
session_start();
require_once __DIR__ . '/includes/functions.php';

echo "<!DOCTYPE html>";
echo "<html><head><title>Test Session</title>";
echo "<meta charset='UTF-8'>";
echo "<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css' rel='stylesheet'>";
echo "</head><body class='bg-light'>";

echo "<div class='container mt-5'>";
echo "<div class='row justify-content-center'>";
echo "<div class='col-md-8'>";

echo "<div class='card shadow'>";
echo "<div class='card-header bg-info text-white'>";
echo "<h3 class='mb-0'>🔍 Test Session & Logout</h3>";
echo "</div>";
echo "<div class='card-body'>";

echo "<h5>📊 Thông tin Session hiện tại:</h5>";
echo "<div class='table-responsive'>";
echo "<table class='table table-bordered'>";
echo "<tr><th>Key</th><th>Value</th></tr>";

if (empty($_SESSION)) {
    echo "<tr><td colspan='2' class='text-center text-muted'>Không có session nào</td></tr>";
} else {
    foreach ($_SESSION as $key => $value) {
        echo "<tr>";
        echo "<td><strong>" . htmlspecialchars($key) . "</strong></td>";
        echo "<td>" . htmlspecialchars(is_array($value) ? json_encode($value) : $value) . "</td>";
        echo "</tr>";
    }
}
echo "</table>";
echo "</div>";

echo "<hr>";

echo "<h5>🔐 Trạng thái đăng nhập:</h5>";
echo "<ul class='list-group mb-3'>";
echo "<li class='list-group-item d-flex justify-content-between'>";
echo "<span>Đã đăng nhập:</span>";
echo "<span class='badge " . (isLoggedIn() ? 'bg-success' : 'bg-danger') . "'>" . (isLoggedIn() ? 'Có' : 'Không') . "</span>";
echo "</li>";

echo "<li class='list-group-item d-flex justify-content-between'>";
echo "<span>Là Admin:</span>";
echo "<span class='badge " . (isAdmin() ? 'bg-warning' : 'bg-secondary') . "'>" . (isAdmin() ? 'Có' : 'Không') . "</span>";
echo "</li>";

if (isLoggedIn()) {
    echo "<li class='list-group-item d-flex justify-content-between'>";
    echo "<span>User ID:</span>";
    echo "<span class='badge bg-info'>" . ($_SESSION['user_id'] ?? 'N/A') . "</span>";
    echo "</li>";
    
    echo "<li class='list-group-item d-flex justify-content-between'>";
    echo "<span>Tên:</span>";
    echo "<span class='badge bg-info'>" . ($_SESSION['name'] ?? 'N/A') . "</span>";
    echo "</li>";
    
    echo "<li class='list-group-item d-flex justify-content-between'>";
    echo "<span>Role:</span>";
    echo "<span class='badge bg-info'>" . ($_SESSION['role'] ?? 'N/A') . "</span>";
    echo "</li>";
}
echo "</ul>";

echo "<h5>🔧 Test Actions:</h5>";
echo "<div class='d-grid gap-2'>";

if (isLoggedIn()) {
    // Kiểm tra xem đang ở admin hay user
    $current_path = $_SERVER['REQUEST_URI'];
    $logout_url = (strpos($current_path, '/admin/') !== false) ? 'logout.php' : 'logout.php';
    
    echo "<a href='$logout_url' class='btn btn-danger'>";
    echo "<i class='fas fa-sign-out-alt me-2'></i>Test Logout";
    echo "</a>";
    
    if (isAdmin()) {
        echo "<a href='admin/dashboard.php' class='btn btn-warning'>";
        echo "<i class='fas fa-cogs me-2'></i>Vào Admin Dashboard";
        echo "</a>";
    }
} else {
    echo "<a href='login.php' class='btn btn-primary'>";
    echo "<i class='fas fa-sign-in-alt me-2'></i>Đăng nhập";
    echo "</a>";
}

echo "<a href='index.php' class='btn btn-secondary'>";
echo "<i class='fas fa-home me-2'></i>Về trang chủ";
echo "</a>";

echo "<button onclick='location.reload()' class='btn btn-info'>";
echo "<i class='fas fa-refresh me-2'></i>Refresh trang";
echo "</button>";

echo "</div>";

echo "<hr>";

echo "<h5>📋 Hướng dẫn test:</h5>";
echo "<ol class='small'>";
echo "<li>Kiểm tra thông tin session ở trên</li>";
echo "<li>Nếu đã đăng nhập → Click 'Test Logout'</li>";
echo "<li>Sau khi logout → Kiểm tra session đã bị xóa chưa</li>";
echo "<li>Thử đăng nhập lại và kiểm tra session</li>";
echo "</ol>";

echo "<div class='alert alert-info'>";
echo "<strong>💡 Lưu ý:</strong> Nếu logout không hoạt động, có thể do:";
echo "<ul class='mb-0 mt-2'>";
echo "<li>Session không được khởi tạo đúng</li>";
echo "<li>Cookie settings không đúng</li>";
echo "<li>Đường dẫn file logout sai</li>";
echo "<li>Headers đã được gửi trước khi redirect</li>";
echo "</ul>";
echo "</div>";

echo "</div>";
echo "</div>";
echo "</div>";
echo "</div>";
echo "</div>";

echo "<script src='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js'></script>";
echo "</body></html>";
?>