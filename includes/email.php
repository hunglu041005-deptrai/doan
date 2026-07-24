<?php
/**
 * Email Notification System
 * Send professional emails for booking confirmations, reminders, etc.
 */

class EmailNotification {
    private static $from = "noreply@badmintonpro.vn";
    private static $from_name = "BadmintonPro";
    
    /**
     * Send Booking Confirmation Email
     */
    public static function sendBookingConfirmation($user_email, $user_name, $booking_data) {
        $subject = "✓ Xác nhận đặt sân cầu lông | " . $booking_data['court_name'];
        
        $html = self::renderTemplate('booking_confirmation', [
            'user_name' => $user_name,
            'court_name' => $booking_data['court_name'],
            'location' => $booking_data['location'],
            'booking_date' => date('d/m/Y', strtotime($booking_data['booking_date'])),
            'time' => substr($booking_data['start_time'], 0, 5) . " - " . substr($booking_data['end_time'], 0, 5),
            'price' => number_format($booking_data['total_price']) . " VND",
            'booking_id' => $booking_data['id'],
            'status' => $booking_data['status']
        ]);
        
        return self::send($user_email, $user_name, $subject, $html);
    }
    
    /**
     * Send Booking Confirmation to Admin
     */
    public static function notifyAdminBooking($booking_data) {
        $admin_email = "admin@badmintonpro.vn";
        $subject = "📅 Đơn đặt sân mới | " . $booking_data['court_name'];
        
        $html = self::renderTemplate('admin_booking_notification', [
            'booking_id' => $booking_data['id'],
            'user_name' => $booking_data['user_name'],
            'user_email' => $booking_data['user_email'],
            'court_name' => $booking_data['court_name'],
            'booking_date' => date('d/m/Y', strtotime($booking_data['booking_date'])),
            'time' => substr($booking_data['start_time'], 0, 5) . " - " . substr($booking_data['end_time'], 0, 5),
            'total_price' => number_format($booking_data['total_price']),
            'payment_method' => $booking_data['payment_method']
        ]);
        
        return self::send($admin_email, "Admin", $subject, $html);
    }
    
    /**
     * Send Payment Success Email
     */
    public static function sendPaymentSuccess($user_email, $user_name, $booking_id, $amount) {
        $subject = "✓ Thanh toán thành công | BadmintonPro";
        
        $html = self::renderTemplate('payment_success', [
            'user_name' => $user_name,
            'booking_id' => $booking_id,
            'amount' => number_format($amount),
            'transaction_time' => date('d/m/Y H:i:s')
        ]);
        
        return self::send($user_email, $user_name, $subject, $html);
    }
    
    /**
     * Send Booking Reminder (day before)
     */
    public static function sendBookingReminder($user_email, $user_name, $booking_data) {
        $subject = "🔔 Nhắc nhở: Bạn có lịch đặt sân ngày mai";
        
        $html = self::renderTemplate('booking_reminder', [
            'user_name' => $user_name,
            'court_name' => $booking_data['court_name'],
            'time' => substr($booking_data['start_time'], 0, 5) . " - " . substr($booking_data['end_time'], 0, 5),
            'location' => $booking_data['location']
        ]);
        
        return self::send($user_email, $user_name, $subject, $html);
    }
    
    /**
     * Generic Email Sender - Using PHP mail() or SMTP
     */
    private static function send($to_email, $to_name, $subject, $html_body) {
        // Headers
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8\r\n";
        $headers .= "From: " . self::$from_name . " <" . self::$from . ">\r\n";
        $headers .= "Reply-To: " . self::$from . "\r\n";
        
        // Alternative: Use PHPMailer for better control
        // For now, use PHP native mail()
        // In production, consider using Swift Mailer or PHPMailer
        
        $result = mail($to_email, $subject, $html_body, $headers);
        
        // Log email
        self::logEmail($to_email, $subject, $result);
        
        return $result;
    }
    
    /**
     * Render Email Template
     */
    private static function renderTemplate($template_name, $data) {
        $templates = [
            'booking_confirmation' => self::templateBookingConfirmation($data),
            'admin_booking_notification' => self::templateAdminNotification($data),
            'payment_success' => self::templatePaymentSuccess($data),
            'booking_reminder' => self::templateBookingReminder($data)
        ];
        
        return $templates[$template_name] ?? '';
    }
    
    private static function templateBookingConfirmation($data) {
        return "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; background: #f5f5f5; }
                .container { max-width: 600px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
                .header { background: linear-gradient(135deg, #0d6efd 0%, #6610f2 100%); color: white; padding: 20px; border-radius: 8px 8px 0 0; text-align: center; }
                .content { padding: 20px; }
                .detail { background: #f9f9f9; padding: 15px; border-left: 4px solid #0d6efd; margin: 15px 0; }
                .button { display: inline-block; background: #0d6efd; color: white; padding: 12px 24px; text-decoration: none; border-radius: 4px; margin-top: 15px; }
                .footer { text-align: center; color: #999; font-size: 12px; margin-top: 20px; border-top: 1px solid #eee; padding-top: 15px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>✓ Xác nhận đặt sân cầu lông</h1>
                </div>
                <div class='content'>
                    <p>Xin chào <strong>{$data['user_name']}</strong>,</p>
                    <p>Cảm ơn bạn đã đặt sân cầu lông tại BadmintonPro. Đây là chi tiết đơn đặt của bạn:</p>
                    
                    <div class='detail'>
                        <p><strong>Sân:</strong> {$data['court_name']}</p>
                        <p><strong>Khu vực:</strong> {$data['location']}</p>
                        <p><strong>Ngày:</strong> {$data['booking_date']}</p>
                        <p><strong>Thời gian:</strong> {$data['time']}</p>
                        <p><strong>Giá:</strong> {$data['price']}</p>
                        <p><strong>ID Đơn:</strong> #{$data['booking_id']}</p>
                        <p><strong>Trạng thái:</strong> {$data['status']}</p>
                    </div>
                    
                    <p>Bạn có thể xem chi tiết đơn đặt tại:</p>
                    <a href='http://localhost/badminton_booking/booking-history.php' class='button'>Xem lịch sử đặt sân</a>
                    
                    <p style='margin-top: 20px; color: #666;'>Nếu có bất kỳ câu hỏi nào, vui lòng liên hệ support@badmintonpro.vn</p>
                </div>
                <div class='footer'>
                    <p>&copy; 2026 BadmintonPro. Tất cả các quyền được bảo lưu.</p>
                </div>
            </div>
        </body>
        </html>";
    }
    
    private static function templateAdminNotification($data) {
        return "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; }
                .container { max-width: 600px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
                .detail { background: #f9f9f9; padding: 15px; border-left: 4px solid #ff6b35; margin: 10px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <h2>📅 Đơn đặt sân mới</h2>
                <div class='detail'>
                    <p><strong>ID Đơn:</strong> #{$data['booking_id']}</p>
                    <p><strong>Khách hàng:</strong> {$data['user_name']} ({$data['user_email']})</p>
                    <p><strong>Sân:</strong> {$data['court_name']}</p>
                    <p><strong>Ngày:</strong> {$data['booking_date']}</p>
                    <p><strong>Thời gian:</strong> {$data['time']}</p>
                    <p><strong>Giá:</strong> {$data['total_price']} VND</p>
                    <p><strong>Phương thức thanh toán:</strong> {$data['payment_method']}</p>
                </div>
            </div>
        </body>
        </html>";
    }
    
    private static function templatePaymentSuccess($data) {
        return "
        <html>
        <head>
            <style>
                .container { max-width: 600px; margin: 0 auto; background: white; padding: 20px; }
                .success { color: #198754; font-size: 24px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <h2 class='success'>✓ Thanh toán thành công</h2>
                <p>Xin chào {$data['user_name']},</p>
                <p>Thanh toán của bạn đã được xử lý thành công.</p>
                <p><strong>Số tiền:</strong> {$data['amount']} VND</p>
                <p><strong>Thời gian:</strong> {$data['transaction_time']}</p>
                <p><strong>ID Đơn:</strong> #{$data['booking_id']}</p>
            </div>
        </body>
        </html>";
    }
    
    private static function templateBookingReminder($data) {
        return "
        <html>
        <head>
            <style>
                .container { max-width: 600px; margin: 0 auto; }
            </style>
        </head>
        <body>
            <div class='container'>
                <h2>🔔 Nhắc nhở lịch đặt sân</h2>
                <p>Xin chào {$data['user_name']},</p>
                <p>Bạn có lịch đặt sân ngày mai:</p>
                <p><strong>Sân:</strong> {$data['court_name']}</p>
                <p><strong>Khu vực:</strong> {$data['location']}</p>
                <p><strong>Thời gian:</strong> {$data['time']}</p>
                <p>Vui lòng đến sân đúng giờ. Cảm ơn!</p>
            </div>
        </body>
        </html>";
    }
    
    /**
     * Log email sending for debugging
     */
    private static function logEmail($to, $subject, $result) {
        $log_file = __DIR__ . '/../logs/email.log';
        $timestamp = date('Y-m-d H:i:s');
        $status = $result ? 'SUCCESS' : 'FAILED';
        $message = "[$timestamp] $status | To: $to | Subject: $subject\n";
        
        @error_log($message, 3, $log_file);
    }
}
?>
