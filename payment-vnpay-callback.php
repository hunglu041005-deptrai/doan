<?php
/**
 * VNPay Payment Callback Handler
 * Receives payment response from VNPay and updates booking status
 */

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/payment.php';
require_once __DIR__ . '/includes/email.php';

// Get VNPay response parameters
extract($_GET);

// Verify the response (in production, check vnp_SecureHash)
$booking_id = intval($_GET['vnp_OrderInfo'] ?? 0);
$response_code = $_GET['vnp_ResponseCode'] ?? '';
$transaction_no = $_GET['vnp_TransactionNo'] ?? '';
$amount = intval($_GET['vnp_Amount'] ?? 0) / 100; // VNPay sends amount in hundreds of VND

if (!$booking_id) {
    die('Invalid booking ID');
}

// Get booking info
$booking = getBookingById($booking_id);
if (!$booking) {
    die('Booking not found');
}

// Verify payment
$is_valid = PaymentGateway::verifyPayment([
    'booking_id' => $booking_id,
    'order_info' => $_GET['vnp_OrderInfo'] ?? '',
    'response_code' => $response_code,
    'transaction_no' => $transaction_no,
    'amount' => $amount,
    'sign' => $_GET['vnp_SecureHash'] ?? '',
    'all_params' => $_GET
]);

if ($response_code == '00') {
    // Payment successful
    $update_stmt = $mysqli->prepare('UPDATE bookings SET payment_status = ?, payment_transaction_id = ?, status = ? WHERE id = ?');
    $status = 'confirmed';
    $payment_status = 'paid';
    $update_stmt->bind_param('sssi', $payment_status, $transaction_no, $status, $booking_id);
    $update_stmt->execute();
    $update_stmt->close();
    
    // Send payment success email
    EmailNotification::sendPaymentSuccess(
        $booking['user_email'],
        $booking['user_name'],
        $booking_id,
        $amount
    );
    
    // Notify admin
    $admin_subject = "█✓ Thanh toán VNPay thành công | Đơn #{$booking_id}";
    // Could send admin notification here
    
    $_SESSION['payment_success'] = 'Thanh toán thành công! Sân của bạn đã được xác nhận.';
    redirect('booking-history.php');
} else {
    // Payment failed
    $update_stmt = $mysqli->prepare('UPDATE bookings SET payment_status = ? WHERE id = ?');
    $payment_status = 'failed';
    $update_stmt->bind_param('si', $payment_status, $booking_id);
    $update_stmt->execute();
    $update_stmt->close();
    
    $_SESSION['payment_error'] = 'Thanh toán không thành công. Vui lòng thử lại.';
    redirect('booking-history.php');
}

/**
 * Get booking by ID
 */
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
?>
