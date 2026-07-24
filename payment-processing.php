<?php
/**
 * Payment Processing Handler - For re-payment from booking history
 * Handles payment initiation for existing bookings that have unpaid/failed status
 */

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/payment.php';
require_once __DIR__ . '/includes/email.php';

// Kiểm tra đăng nhập
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$booking_id = intval($_GET['booking_id'] ?? 0);
$payment_method = strtolower($_GET['method'] ?? 'vnpay');

if (!$booking_id) {
    redirect('booking-history.php');
}

// Get booking info
$stmt = $mysqli->prepare('
    SELECT b.*, c.name as court_name, c.price_per_hour
    FROM bookings b
    JOIN courts c ON b.court_id = c.id
    WHERE b.id = ? AND b.user_id = ?
');
$stmt->bind_param('ii', $booking_id, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$booking = $result->fetch_assoc();
$stmt->close();

if (!$booking) {
    $_SESSION['booking_error'] = 'Đơn đặt không tìm thấy.';
    redirect('booking-history.php');
}

// Only allow payment if not already paid
if (strtolower($booking['payment_status']) === 'paid') {
    $_SESSION['booking_error'] = 'Đơn đặt này đã thanh toán rồi.';
    redirect('booking-history.php');
}

// Process payment based on method
if ($payment_method === 'vnpay') {
    $vnpay_url = PaymentGateway::generateVNPayLink(
        $booking_id,
        $booking['total_price'],
        'Thanh toán lại - Đặt sân cầu lông ' . $booking['court_name'],
        $_SESSION['user_id']
    );
    redirect($vnpay_url);
    
} elseif ($payment_method === 'momo') {
    $momo_data = PaymentGateway::generateMoMoLink(
        $booking_id,
        $booking['total_price'],
        'Thanh toán lại - Đặt sân cầu lông ' . $booking['court_name'],
        $_SESSION['user_id']
    );
    
    $_SESSION['momo_data'] = $momo_data;
    redirect('payment-momo.php?booking_id=' . $booking_id);
} else {
    $_SESSION['payment_error'] = 'Phương thức thanh toán không hợp lệ.';
    redirect('booking-history.php');
}
?>
