<?php
/**
 * MoMo Payment Callback Handler
 * Receives payment response from MoMo and updates booking status
 */

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/email.php';

$booking_id = intval($_GET['booking_id'] ?? 0);
$status = strtolower($_GET['status'] ?? 'failed');

if (!$booking_id) {
    die('Invalid booking ID');
}

// Get booking info
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

if (!$booking) {
    die('Booking not found');
}

if ($status === 'success') {
    // Payment successful
    $update_stmt = $mysqli->prepare('UPDATE bookings SET payment_status = ?, status = ? WHERE id = ?');
    $payment_status = 'paid';
    $booking_status = 'confirmed';
    $update_stmt->bind_param('ssi', $payment_status, $booking_status, $booking_id);
    $update_stmt->execute();
    $update_stmt->close();
    
    // Send payment success email
    EmailNotification::sendPaymentSuccess(
        $booking['user_email'],
        $booking['user_name'],
        $booking_id,
        $booking['total_price']
    );
    
    $_SESSION['payment_success'] = 'Thanh toán MoMo thành công! Sân của bạn đã được xác nhận.';
} else {
    // Payment failed
    $update_stmt = $mysqli->prepare('UPDATE bookings SET payment_status = ? WHERE id = ?');
    $payment_status = 'failed';
    $update_stmt->bind_param('si', $payment_status, $booking_id);
    $update_stmt->execute();
    $update_stmt->close();
    
    $_SESSION['payment_error'] = 'Thanh toán MoMo không thành công. Vui lòng thử lại.';
}

redirect('booking-history.php');
?>
