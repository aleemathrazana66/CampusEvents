<?php
/**
 * CampusEvents API - Get Next Upcoming Event
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        SELECT id, title, category, event_date, event_time, venue, banner_image
        FROM events
        WHERE is_published = 1 
          AND CONCAT(event_date, ' ', event_time) > NOW()
        ORDER BY CONCAT(event_date, ' ', event_time) ASC
        LIMIT 1
    ");
    $stmt->execute();
    $event = $stmt->fetch();

    if ($event) {
        $eventDateTime = strtotime($event['event_date'] . ' ' . $event['event_time']);
        $event['formatted_date'] = date('M j, Y', $eventDateTime);
        $event['formatted_time'] = date('g:i A', $eventDateTime);
        echo json_encode(['success' => true, 'event' => $event]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No upcoming events found.']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
