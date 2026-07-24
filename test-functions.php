<?php
require_once __DIR__ . '/includes/functions.php';

echo "<!DOCTYPE html>";
echo "<html><head><title>Test Functions</title>";
echo "<meta charset='UTF-8'>";
echo "<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css' rel='stylesheet'>";
echo "</head><body class='bg-light'>";

echo "<div class='container mt-5'>";
echo "<div class='card shadow mx-auto' style='max-width: 600px;'>";
echo "<div class='card-header bg-success text-white'>";
echo "<h3 class='mb-0'>✅ Test Functions</h3>";
echo "</div>";
echo "<div class='card-body'>";

echo "<h5>Kiểm tra các function:</h5>";

// Test functions
echo "<div class='alert alert-info'>";
echo "<strong>isLoggedIn():</strong> " . (isLoggedIn() ? 'true' : 'false') . "<br>";
echo "<strong>isAdmin():</strong> " . (isAdmin() ? 'true' : 'false') . "<br>";
echo "<strong>Session user_id:</strong> " . ($_SESSION['user_id'] ?? 'not set') . "<br>";
echo "<strong>Session role:</strong> " . ($_SESSION['role'] ?? 'not set') . "<br>";
echo "<strong>Session name:</strong> " . ($_SESSION['name'] ?? 'not set') . "<br>";
echo "</div>";

// Test database connection
echo "<h5>Kiểm tra database:</h5>";
try {
    $result = $mysqli->query("SELECT COUNT(*) as count FROM users");
    if ($result) {
        $count = $result->fetch_assoc()['count'];
        echo "<div class='alert alert-success'>✅ Database OK - Có $count users</div>";
    } else {
        echo "<div class='alert alert-danger'>❌ Database query failed</div>";
    }
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>❌ Database error: " . $e->getMessage() . "</div>";
}

// Test other functions
echo "<h5>Kiểm tra các function khác:</h5>";
echo "<div class='alert alert-info'>";
echo "<strong>function_exists('isLoggedIn'):</strong> " . (function_exists('isLoggedIn') ? 'true' : 'false') . "<br>";
echo "<strong>function_exists('isAdmin'):</strong> " . (function_exists('isAdmin') ? 'true' : 'false') . "<br>";
echo "<strong>function_exists('requireAdmin'):</strong> " . (function_exists('requireAdmin') ? 'true' : 'false') . "<br>";
echo "<strong>function_exists('blockAdminFromPublic'):</strong> " . (function_exists('blockAdminFromPublic') ? 'true' : 'false') . "<br>";
echo "</div>";

echo "<div class='mt-3'>";
echo "<a href='index.php' class='btn btn-primary me-2'>← Trang chủ</a>";
echo "<a href='login.php' class='btn btn-warning me-2'>🔑 Đăng nhập</a>";
echo "<a href='booking-history.php' class='btn btn-info'>📋 Test Booking History</a>";
echo "</div>";

echo "</div>";
echo "</div>";
echo "</div>";

echo "</body></html>";
?>