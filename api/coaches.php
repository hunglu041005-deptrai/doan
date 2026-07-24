<?php
header('Content-Type: application/json');
error_reporting(0);
ini_set('display_errors', 0);
require_once __DIR__ . '/../db.php';

$week_start = date('Y-m-d', strtotime('monday this week'));

$result = $mysqli->query("
    SELECT c.id, c.name, c.specialty, c.experience_years, c.max_students_per_week,
           c.avatar,
           COALESCE(cnt.total, 0) AS current_students
    FROM coaches c
    LEFT JOIN (
        SELECT coach_id, COUNT(*) AS total
        FROM training_registrations
        WHERE week_start = '$week_start' AND status = 'active'
        GROUP BY coach_id
    ) cnt ON cnt.coach_id = c.id
    WHERE c.status = 1
    ORDER BY c.id
");

$coaches = [];
while ($row = $result->fetch_assoc()) {
    $remaining = $row['max_students_per_week'] - $row['current_students'];
    $coaches[] = [
        'id'              => $row['id'],
        'name'            => $row['name'],
        'specialty'       => $row['specialty'],
        'experience'      => $row['experience_years'] . ' năm kinh nghiệm',
        'experience_years'=> (int)$row['experience_years'],
        'max'             => (int)$row['max_students_per_week'],
        'current'         => (int)$row['current_students'],
        'remaining'       => max(0, $remaining),
        'full'            => $remaining <= 0,
        'avatar'          => $row['avatar'] ? $row['avatar'] : null,
    ];
}

echo json_encode(['success' => true, 'coaches' => $coaches, 'week_start' => $week_start]);
