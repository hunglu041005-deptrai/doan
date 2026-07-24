<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../db.php';
$response = ['success' => false, 'data' => null, 'message' => ''];

$action = $_GET['action'] ?? 'list';

if ($action === 'list') {
    $stmt = $mysqli->prepare('SELECT id, name, location, price_per_hour, cover_image, description FROM courts WHERE status = 1 ORDER BY created_at DESC');
    $stmt->execute();
    $result = $stmt->get_result();
    $courts = $result->fetch_all(MYSQLI_ASSOC);
    
    // Add demo coordinates for map display (in real app, these should be stored in database)
    $locationCoords = [
        'Hoàng Mai' => ['lat' => 20.9815, 'lng' => 105.8468],
        'Thanh Xuân' => ['lat' => 20.9955, 'lng' => 105.8195],
        'Cầu Giấy' => ['lat' => 21.0335, 'lng' => 105.7935],
        'Đống Đa' => ['lat' => 21.0167, 'lng' => 105.8270],
        'Ba Đình' => ['lat' => 21.0333, 'lng' => 105.8167],
        'Hai Bà Trưng' => ['lat' => 21.0122, 'lng' => 105.8580],
        'Long Biên' => ['lat' => 21.0364, 'lng' => 105.8938],
        'Tây Hồ' => ['lat' => 21.0583, 'lng' => 105.8200],
        'Hà Đông' => ['lat' => 20.9715, 'lng' => 105.7829],
        'Nam Từ Liêm' => ['lat' => 21.0378, 'lng' => 105.7644]
    ];
    
    // Add coordinates and status to each court
    foreach ($courts as &$court) {
        $location = $court['location'];
        if (isset($locationCoords[$location])) {
            $court['lat'] = $locationCoords[$location]['lat'] + (rand(-50, 50) / 10000); // Add small random offset
            $court['lng'] = $locationCoords[$location]['lng'] + (rand(-50, 50) / 10000);
        } else {
            // Default to Hanoi center with random offset
            $court['lat'] = 21.0285 + (rand(-100, 100) / 10000);
            $court['lng'] = 105.8542 + (rand(-100, 100) / 10000);
        }
        
        // Add random status for demo (in real app, this should be calculated from bookings)
        $statuses = ['available', 'limited', 'full'];
        $court['status'] = $statuses[array_rand($statuses)];
        
        // Add operating hours
        $court['operating_hours'] = '06:00 - 22:00';
        
        // Add facilities info
        $court['facilities'] = ['Có mái che', 'Sân gỗ', 'Đèn LED', 'Điều hòa'];
    }
    
    $response['success'] = true;
    $response['courts'] = $courts; // Change 'data' to 'courts' to match JavaScript
} else {
    $response['message'] = 'Action không hợp lệ.';
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
