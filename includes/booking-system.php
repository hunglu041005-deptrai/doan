<?php
require_once __DIR__ . '/functions.php';

class AdvancedBookingSystem {
    private $mysqli;
    
    public function __construct() {
        global $mysqli;
        $this->mysqli = $mysqli;
    }
    
    /**
     * Get available time slots for a court on a specific date
     */
    public function getAvailableSlots($courtId, $date) {
        // Get court operating hours (default 6:00 - 22:00)
        $operatingHours = $this->getCourtOperatingHours($courtId);
        
        // Generate all possible time slots
        $allSlots = $this->generateTimeSlots($operatingHours['start'], $operatingHours['end']);
        
        // Get booked slots
        $bookedSlots = $this->getBookedSlots($courtId, $date);
        
        // Filter available slots
        $availableSlots = array_filter($allSlots, function($slot) use ($bookedSlots) {
            return !in_array($slot['start_time'], $bookedSlots);
        });
        
        return array_values($availableSlots);
    }
    
    /**
     * Create a new booking
     */
    public function createBooking($data) {
        $userId = $data['user_id'];
        $courtId = $data['court_id'];
        $bookingDate = $data['booking_date'];
        $startTime = $data['start_time'];
        $endTime = $data['end_time'];
        $totalPrice = $data['total_price'];
        $paymentMethod = $data['payment_method'] ?? 'cash';
        $notes = $data['notes'] ?? '';
        $bookingType = $data['booking_type'] ?? 'single'; // single, recurring, group
        
        // Validate booking
        if (!$this->validateBooking($courtId, $bookingDate, $startTime, $endTime)) {
            return ['success' => false, 'message' => 'Khung giờ đã được đặt hoặc không hợp lệ'];
        }
        
        // Start transaction
        $this->mysqli->begin_transaction();
        
        try {
            // Create main booking
            $stmt = $this->mysqli->prepare('
                INSERT INTO bookings (user_id, court_id, booking_date, start_time, end_time, 
                                    total_price, payment_method, payment_status, notes, booking_type, 
                                    status, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, "pending", ?, ?, "confirmed", NOW())
            ');
            
            $stmt->bind_param('iisssdsss', $userId, $courtId, $bookingDate, $startTime, 
                            $endTime, $totalPrice, $paymentMethod, $notes, $bookingType);
            
            if (!$stmt->execute()) {
                throw new Exception('Không thể tạo booking');
            }
            
            $bookingId = $this->mysqli->insert_id;
            
            // Handle recurring bookings
            if ($bookingType === 'recurring' && isset($data['recurring_config'])) {
                $this->createRecurringBookings($bookingId, $data['recurring_config']);
            }
            
            // Handle group bookings
            if ($bookingType === 'group' && isset($data['group_members'])) {
                $this->addGroupMembers($bookingId, $data['group_members']);
            }
            
            // Send confirmation email/SMS
            $this->sendBookingConfirmation($bookingId);
            
            $this->mysqli->commit();
            
            return [
                'success' => true, 
                'booking_id' => $bookingId,
                'message' => 'Đặt sân thành công!'
            ];
            
        } catch (Exception $e) {
            $this->mysqli->rollback();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Create recurring bookings
     */
    private function createRecurringBookings($parentBookingId, $config) {
        $frequency = $config['frequency']; // weekly, monthly
        $occurrences = $config['occurrences'];
        $endDate = $config['end_date'] ?? null;
        
        // Get parent booking details
        $stmt = $this->mysqli->prepare('SELECT * FROM bookings WHERE id = ?');
        $stmt->bind_param('i', $parentBookingId);
        $stmt->execute();
        $parentBooking = $stmt->get_result()->fetch_assoc();
        
        $currentDate = new DateTime($parentBooking['booking_date']);
        $createdCount = 0;
        
        for ($i = 1; $i < $occurrences; $i++) {
            if ($frequency === 'weekly') {
                $currentDate->add(new DateInterval('P7D'));
            } elseif ($frequency === 'monthly') {
                $currentDate->add(new DateInterval('P1M'));
            }
            
            if ($endDate && $currentDate > new DateTime($endDate)) {
                break;
            }
            
            $newDate = $currentDate->format('Y-m-d');
            
            // Check if slot is available
            if ($this->validateBooking($parentBooking['court_id'], $newDate, 
                                     $parentBooking['start_time'], $parentBooking['end_time'])) {
                
                $stmt = $this->mysqli->prepare('
                    INSERT INTO bookings (user_id, court_id, booking_date, start_time, end_time, 
                                        total_price, payment_method, payment_status, notes, 
                                        booking_type, parent_booking_id, status, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, "recurring", ?, "confirmed", NOW())
                ');
                
                $stmt->bind_param('iisssdssi', 
                    $parentBooking['user_id'],
                    $parentBooking['court_id'],
                    $newDate,
                    $parentBooking['start_time'],
                    $parentBooking['end_time'],
                    $parentBooking['total_price'],
                    $parentBooking['payment_method'],
                    $parentBooking['payment_status'],
                    $parentBooking['notes'],
                    $parentBookingId
                );
                
                $stmt->execute();
                $createdCount++;
            }
        }
        
        return $createdCount;
    }
    
    /**
     * Add group members to booking
     */
    private function addGroupMembers($bookingId, $members) {
        foreach ($members as $member) {
            $stmt = $this->mysqli->prepare('
                INSERT INTO booking_members (booking_id, name, email, phone, role) 
                VALUES (?, ?, ?, ?, ?)
            ');
            
            $role = $member['role'] ?? 'member';
            $stmt->bind_param('issss', $bookingId, $member['name'], 
                            $member['email'], $member['phone'], $role);
            $stmt->execute();
        }
    }
    
    /**
     * Get booking calendar data
     */
    public function getBookingCalendar($courtId, $month, $year) {
        $startDate = "$year-$month-01";
        $endDate = date('Y-m-t', strtotime($startDate));
        
        $stmt = $this->mysqli->prepare('
            SELECT booking_date, start_time, end_time, status, booking_type
            FROM bookings 
            WHERE court_id = ? AND booking_date BETWEEN ? AND ? AND status != "cancelled"
            ORDER BY booking_date, start_time
        ');
        
        $stmt->bind_param('iss', $courtId, $startDate, $endDate);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $calendar = [];
        while ($row = $result->fetch_assoc()) {
            $date = $row['booking_date'];
            if (!isset($calendar[$date])) {
                $calendar[$date] = [];
            }
            $calendar[$date][] = $row;
        }
        
        return $calendar;
    }
    
    /**
     * Cancel booking with refund policy
     */
    public function cancelBooking($bookingId, $userId, $reason = '') {
        // Get booking details
        $stmt = $this->mysqli->prepare('
            SELECT * FROM bookings 
            WHERE id = ? AND user_id = ? AND status != "cancelled"
        ');
        $stmt->bind_param('ii', $bookingId, $userId);
        $stmt->execute();
        $booking = $stmt->get_result()->fetch_assoc();
        
        if (!$booking) {
            return ['success' => false, 'message' => 'Không tìm thấy booking'];
        }
        
        // Check cancellation policy
        $refundInfo = $this->calculateRefund($booking);
        
        // Update booking status
        $stmt = $this->mysqli->prepare('
            UPDATE bookings 
            SET status = "cancelled", cancellation_reason = ?, 
                refund_amount = ?, cancelled_at = NOW() 
            WHERE id = ?
        ');
        
        $stmt->bind_param('sdi', $reason, $refundInfo['refund_amount'], $bookingId);
        
        if ($stmt->execute()) {
            // Process refund if applicable
            if ($refundInfo['refund_amount'] > 0) {
                $this->processRefund($bookingId, $refundInfo['refund_amount']);
            }
            
            // Send cancellation notification
            $this->sendCancellationNotification($bookingId);
            
            return [
                'success' => true, 
                'message' => 'Hủy booking thành công',
                'refund_info' => $refundInfo
            ];
        }
        
        return ['success' => false, 'message' => 'Không thể hủy booking'];
    }
    
    /**
     * Calculate refund amount based on cancellation policy
     */
    private function calculateRefund($booking) {
        $bookingDateTime = new DateTime($booking['booking_date'] . ' ' . $booking['start_time']);
        $now = new DateTime();
        $hoursUntilBooking = ($bookingDateTime->getTimestamp() - $now->getTimestamp()) / 3600;
        
        $refundPercentage = 0;
        $policy = '';
        
        if ($hoursUntilBooking >= 24) {
            $refundPercentage = 100;
            $policy = 'Hoàn tiền 100% (hủy trước 24h)';
        } elseif ($hoursUntilBooking >= 2) {
            $refundPercentage = 50;
            $policy = 'Hoàn tiền 50% (hủy trước 2h)';
        } else {
            $refundPercentage = 0;
            $policy = 'Không hoàn tiền (hủy trong vòng 2h)';
        }
        
        $refundAmount = ($booking['total_price'] * $refundPercentage) / 100;
        
        return [
            'refund_percentage' => $refundPercentage,
            'refund_amount' => $refundAmount,
            'policy' => $policy
        ];
    }
    
    // Helper methods
    private function getCourtOperatingHours($courtId) {
        // Default operating hours, can be customized per court
        return ['start' => '06:00', 'end' => '22:00'];
    }
    
    private function generateTimeSlots($startTime, $endTime) {
        $slots = [];
        $current = new DateTime($startTime);
        $end = new DateTime($endTime);
        
        while ($current < $end) {
            $slotStart = $current->format('H:i');
            $current->add(new DateInterval('PT1H')); // 1 hour slots
            $slotEnd = $current->format('H:i');
            
            $slots[] = [
                'start_time' => $slotStart,
                'end_time' => $slotEnd,
                'display' => $slotStart . ' - ' . $slotEnd
            ];
        }
        
        return $slots;
    }
    
    private function getBookedSlots($courtId, $date) {
        $stmt = $this->mysqli->prepare('
            SELECT start_time FROM bookings 
            WHERE court_id = ? AND booking_date = ? AND status != "cancelled"
        ');
        $stmt->bind_param('is', $courtId, $date);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $bookedSlots = [];
        while ($row = $result->fetch_assoc()) {
            $bookedSlots[] = $row['start_time'];
        }
        
        return $bookedSlots;
    }
    
    private function validateBooking($courtId, $date, $startTime, $endTime) {
        $stmt = $this->mysqli->prepare('
            SELECT COUNT(*) as count FROM bookings 
            WHERE court_id = ? AND booking_date = ? 
            AND ((start_time <= ? AND end_time > ?) OR (start_time < ? AND end_time >= ?))
            AND status != "cancelled"
        ');
        
        $stmt->bind_param('isssss', $courtId, $date, $startTime, $startTime, $endTime, $endTime);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        return $result['count'] == 0;
    }
    
    private function sendBookingConfirmation($bookingId) {
        // Implementation for sending confirmation email/SMS
        // This would integrate with your email/SMS service
    }
    
    private function sendCancellationNotification($bookingId) {
        // Implementation for sending cancellation notification
    }
    
    private function processRefund($bookingId, $amount) {
        // Implementation for processing refund
        // This would integrate with your payment gateway
    }
}
?>