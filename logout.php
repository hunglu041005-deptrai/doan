<?php
session_start();

// Xóa tất cả session variables
$_SESSION = [];

// Xóa session cookie nếu có
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}

// Hủy session
session_destroy();

// Redirect về trang login với thông báo
header('Location: login.php?message=logout_success');
exit;
?>
