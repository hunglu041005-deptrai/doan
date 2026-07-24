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
    $user_id = $_GET['user_id'] ?? $_SESSION['user_id'] ?? null;
    
    if (!$user_id) {
        throw new Exception('User ID is required');
    }
    
    // Get user bookings
    $bookings = getUserBookings($user_id);
    
    // Format bookings for API response
    $formattedBookings = array_map(function($booking) {
        return [
            'id' => $booking['id'],
            'court_id' => $booking['court_id'],
            'court_name' => $booking['court_name'],
            'location' => $booking['location'],
            'booking_date' => $booking['booking_date'],
            'start_time' => substr($booking['start_time'], 0, 5), // Remove seconds
            'end_time' => substr($booking['end_time'], 0, 5),
            'total_price' => $booking['total_price'],
            'payment_method' => $booking['payment_method'],
            'payment_status' => $booking['payment_status'],
            'status' => $booking['status'],
            'notes' => $booking['notes'] ?? '',
            'created_at' => $booking['created_at']
        ];
    }, $bookings);
    
    // Calculate statistics
    $stats = [
        'total' => count($bookings),
        'confirmed' => count(array_filter($bookings, function($b) { return $b['status'] === 'confirmed'; })),
        'pending' => count(array_filter($bookings, function($b) { return $b['status'] === 'pending'; })),
        'cancelled' => count(array_filter($bookings, function($b) { return $b['status'] === 'cancelled'; })),
        'paid' => count(array_filter($bookings, function($b) { return $b['payment_status'] === 'paid'; })),
        'unpaid' => count(array_filter($bookings, function($b) { return $b['payment_status'] === 'unpaid'; })),
    ];
    
    $response = [
        'success' => true,
        'bookings' => $formattedBookings,
        'statistics' => $stats,
        'user_id' => $user_id,
        'timestamp' => date('Y-m-d H:i:s')
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
</content>
</invoke>