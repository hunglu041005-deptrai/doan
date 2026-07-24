<?php
require_once __DIR__ . '/../db.php';

class NotificationSystem {
    private $mysqli;

    public function __construct() {
        global $mysqli;
        $this->mysqli = $mysqli;
    }

    // ===== CORE: Tạo thông báo =====
    public function create(int $userId, string $type, string $title, string $message, ?string $link = null): int|false {
        $stmt = $this->mysqli->prepare(
            'INSERT INTO notifications (user_id, type, title, message, link) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->bind_param('issss', $userId, $type, $title, $message, $link);
        if ($stmt->execute()) {
            return $stmt->insert_id;
        }
        return false;
    }

    // ===== ĐẶT SÂN =====
    public function notifyBookingConfirmed(int $bookingId): void {
        $booking = $this->getBooking($bookingId);
        if (!$booking) return;

        $date = date('d/m/Y', strtotime($booking['booking_date']));
        $time = substr($booking['start_time'], 0, 5) . ' – ' . substr($booking['end_time'], 0, 5);

        $this->create(
            $booking['user_id'],
            'booking',
            '✅ Đặt sân thành công!',
            "Bạn đã đặt {$booking['court_name']} vào ngày {$date}, {$time}. Vui lòng có mặt đúng giờ.",
            'booking-history.php'
        );
    }

    public function notifyBookingReminder(int $bookingId): void {
        $booking = $this->getBooking($bookingId);
        if (!$booking) return;

        $time = substr($booking['start_time'], 0, 5);
        $this->create(
            $booking['user_id'],
            'booking',
            '⏰ Nhắc lịch chơi cầu lông',
            "Bạn có lịch chơi tại {$booking['court_name']} lúc {$time} hôm nay. Chuẩn bị tốt nhé!",
            'booking-history.php'
        );
    }

    public function notifyBookingCancelled(int $bookingId): void {
        $booking = $this->getBooking($bookingId);
        if (!$booking) return;

        $date = date('d/m/Y', strtotime($booking['booking_date']));
        $this->create(
            $booking['user_id'],
            'booking',
            '❌ Booking đã bị hủy',
            "Booking sân {$booking['court_name']} ngày {$date} đã bị hủy.",
            'booking-history.php'
        );
    }

    // ===== GÓI HỘI VIÊN =====
    public function notifyMembershipActivated(int $userId, string $memberCode, string $planName, string $endDate): void {
        $end = date('d/m/Y', strtotime($endDate));
        $this->create(
            $userId,
            'membership',
            '🎉 Thẻ hội viên đã được kích hoạt!',
            "Gói \"{$planName}\" của bạn đã được kích hoạt. Mã thẻ: {$memberCode}. Hạn sử dụng đến {$end}.",
            'membership.php'
        );
    }

    public function notifyMembershipExpiringSoon(int $userId, string $memberCode, int $daysLeft): void {
        $this->create(
            $userId,
            'membership',
            '⚠️ Thẻ hội viên sắp hết hạn',
            "Thẻ {$memberCode} của bạn còn {$daysLeft} ngày nữa sẽ hết hạn. Gia hạn ngay để không gián đoạn.",
            'membership.php'
        );
    }

    // ===== ĐĂNG KÝ KHÓA HỌC =====
    public function notifyTrainingRegistered(int $userId, string $studentCode, string $courseLabel, string $coach): void {
        $this->create(
            $userId,
            'training',
            '📚 Đăng ký khóa học thành công!',
            "Bạn đã đăng ký {$courseLabel}. Mã học viên: {$studentCode}. HLV: {$coach}. Đội ngũ sẽ liên hệ xác nhận lịch học.",
            'training.php'
        );
    }

    public function notifyTrainingScheduleUpdated(int $userId, string $courseLabel, string $newSchedule): void {
        $this->create(
            $userId,
            'coach',
            '📅 Lịch học đã được cập nhật',
            "Lịch học {$courseLabel} của bạn đã thay đổi: {$newSchedule}.",
            'training.php'
        );
    }

    // ===== KHUYẾN MÃI =====
    public function notifyPromotion(int $userId, string $title, string $detail, ?string $link = null): void {
        $this->create($userId, 'promotion', "🎁 {$title}", $detail, $link ?? 'discover.php');
    }

    public function broadcastPromotion(string $title, string $detail, ?string $link = null): void {
        $result = $this->mysqli->query('SELECT id FROM users WHERE status = 1');
        while ($row = $result->fetch_assoc()) {
            $this->notifyPromotion((int)$row['id'], $title, $detail, $link);
        }
    }

    // ===== HLV =====
    public function notifyCoachMessage(int $userId, string $coachName, string $message): void {
        $this->create(
            $userId,
            'coach',
            "💬 Tin nhắn từ HLV {$coachName}",
            $message,
            'training.php'
        );
    }

    // ===== HỆ THỐNG =====
    public function notifySystem(int $userId, string $title, string $message): void {
        $this->create($userId, 'system', $title, $message);
    }

    // ===== QUERY =====
    public function getUserNotifications(int $userId, int $limit = 20, int $offset = 0): array {
        $stmt = $this->mysqli->prepare(
            'SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ? OFFSET ?'
        );
        $stmt->bind_param('iii', $userId, $limit, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $row['sent_at'] = $row['created_at']; // backward compat
            $rows[] = $row;
        }
        return $rows;
    }

    public function getUnreadCount(int $userId): int {
        $stmt = $this->mysqli->prepare(
            'SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0'
        );
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        return (int) $stmt->get_result()->fetch_row()[0];
    }

    public function markAsRead(int $notifId, int $userId): bool {
        $stmt = $this->mysqli->prepare(
            'UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?'
        );
        $stmt->bind_param('ii', $notifId, $userId);
        return $stmt->execute();
    }

    public function markAllAsRead(int $userId): bool {
        $stmt = $this->mysqli->prepare(
            'UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0'
        );
        $stmt->bind_param('i', $userId);
        return $stmt->execute();
    }

    public function getRealTimeNotifications(int $userId, ?string $lastTimestamp = null): array {
        $sql = 'SELECT * FROM notifications WHERE user_id = ?';
        $params = [$userId];
        $types  = 'i';
        if ($lastTimestamp) {
            $sql   .= ' AND created_at > ?';
            $params[] = $lastTimestamp;
            $types  .= 's';
        }
        $sql .= ' ORDER BY created_at DESC LIMIT 5';
        $stmt = $this->mysqli->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $row['sent_at'] = $row['created_at'];
            $rows[] = $row;
        }
        return $rows;
    }

    // ===== PRIVATE HELPERS =====
    private function getBooking(int $bookingId): ?array {
        $stmt = $this->mysqli->prepare(
            'SELECT b.*, c.name as court_name
             FROM bookings b JOIN courts c ON b.court_id = c.id
             WHERE b.id = ?'
        );
        $stmt->bind_param('i', $bookingId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return $row ?: null;
    }
}
