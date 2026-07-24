<?php
session_start();
require_once __DIR__ . '/social-login.php';

try {
    if (!isset($_GET['code']) || !isset($_GET['state'])) {
        throw new Exception('Missing required parameters');
    }
    
    $socialLogin = new SocialLogin();
    $user = $socialLogin->handleFacebookCallback($_GET['code'], $_GET['state']);
    
    if ($user) {
        $_SESSION['social_login_success'] = 'Đăng nhập Facebook thành công!';
        header('Location: ../index.php');
    } else {
        $_SESSION['social_login_error'] = 'Không thể đăng nhập bằng Facebook. Vui lòng thử lại.';
        header('Location: ../login.php');
    }
} catch (Exception $e) {
    $_SESSION['social_login_error'] = 'Lỗi: ' . $e->getMessage();
    header('Location: ../login.php');
}
exit;
?>