<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../includes/functions.php';

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

try {
    $court_id = $_GET['court_id'] ?? null;
    $date = $_GET['date'] ?? date('Y-m-d');
    
    if (!$court_id) {
        throw new Exception('Court ID is required');
    }
    
    // Validate date
    $dateObj = DateTime::createFromFormat('Y-m-d', $date);
    if (!$dateObj || $dateObj->format('Y-m-d') !== $date) {
        throw new Exception('Invalid date format');
    }
    
    // Check if date is not in the past
    $today = new DateTime();
    $today->setTime(0, 0, 0);
    if ($dateObj < $today) {
        throw new Exception('Cannot book for past dates');
    }
    
    // Get court information
    $court = getCourtById($court_id);
    if (!$court) {
        throw new Exception('Court not found');
    }
    
    // Get existing bookings for this court and date
    $bookedSlots = getCourtAvailability($court_id, $date);
    
    // Generate time slots from 6:00 to 22:00
    $slots = [];
    $currentHour = date('H');
    $isToday = $date === date('Y-m-d');
    
    for ($hour = 6; $hour <= 21; $hour++) {
        $timeStr = sprintf('%02d:00', $hour);
        $endTimeStr = sprintf('%02d:00', $hour + 1);
        
        // Check if slot is booked
        $isBooked = false;
        foreach ($bookedSlots as $booking) {
            $bookingStart = strtotime($booking['start_time']);
            $bookingEnd = strtotime($booking['end_time']);
            $slotStart = strtotime($timeStr);
            $slotEnd = strtotime($endTimeStr);
            
            // Check for overlap
            if ($slotStart < $bookingEnd && $slotEnd > $bookingStart) {
                $isBooked = true;
                break;
            }
        }
        
        // Check if time has passed (for today only)
        $isPassed = $isToday && $hour <= $currentHour;
        
        // Determine availability
        $status = 'available';
        $statusText = 'Trống';
        $statusClass = 'success';
        
        if ($isPassed) {
            $status = 'passed';
            $statusText = 'Đã qua';
            $statusClass = 'secondary';
        } elseif ($isBooked) {
            $status = 'booked';
            $statusText = 'Đã đặt';
            $statusClass = 'danger';
        }
        
        // Calculate price (peak hours, morning discount)
        $basePrice = $court['price_per_hour'];
        $price = $basePrice;
        
        if ($hour >= 18 && $hour <= 21) {
            // Peak hours (6 PM - 9 PM) - 20% increase
            $price = round($basePrice * 1.2);
        } elseif ($hour >= 6 && $hour <= 9) {
            // Morning hours (6 AM - 9 AM) - 10% discount
            $price = round($basePrice * 0.9);
        }
        
        $slots[] = [
            'time' => $timeStr,
            'endTime' => $endTimeStr,
            'hour' => $hour,
            'price' => $price,
            'basePrice' => $basePrice,
            'status' => $status,
            'statusText' => $statusText,
            'statusClass' => $statusClass,
            'available' => $status === 'available',
            'isPeakHour' => $hour >= 18 && $hour <= 21,
            'isDiscountHour' => $hour >= 6 && $hour <= 9,
            'priceMultiplier' => round($price / $basePrice, 2)
        ];
    }
    
    // Calculate statistics
    $totalSlots = count($slots);
    $availableSlots = array_filter($slots, function($slot) {
        return $slot['available'];
    });
    $availableCount = count($availableSlots);
    $bookedCount = count(array_filter($slots, function($slot) {
        return $slot['status'] === 'booked';
    }));
    $passedCount = count(array_filter($slots, function($slot) {
        return $slot['status'] === 'passed';
    }));
    
    // Response
    $response = [
        'success' => true,
        'data' => [
            'court' => [
                'id' => $court['id'],
                'name' => $court['name'],
                'location' => $court['location'],
                'basePrice' => $court['price_per_hour']
            ],
            'date' => $date,
            'dateFormatted' => $dateObj->format('d/m/Y'),
            'isToday' => $isToday,
            'slots' => $slots,
            'statistics' => [
                'total' => $totalSlots,
                'available' => $availableCount,
                'booked' => $bookedCount,
                'passed' => $passedCount,
                'availabilityRate' => $totalSlots > 0 ? round(($availableCount / $totalSlots) * 100, 1) : 0
            ],
            'pricing' => [
                'basePrice' => $court['price_per_hour'],
                'peakMultiplier' => 1.2,
                'discountMultiplier' => 0.9,
                'peakHours' => '18:00 - 21:00',
                'discountHours' => '06:00 - 09:00'
            ],
            'lastUpdated' => date('Y-m-d H:i:s')
        ]
    ];
    
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
?>