<?php
/**
 * CampusEvents API - Cancel Registration
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/auth.php';

if (!isLoggedIn() || !isStudent()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized request.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

$student = getCurrentUser();
$regId = (int)($_POST['registration_id'] ?? 0);
$csrfToken = $_POST['csrf_token'] ?? '';

if (!verify_csrf_token($csrfToken)) {
    echo json_encode(['success' => false, 'message' => 'Security token invalid. Please refresh.']);
    exit;
}

$pdo = getDBConnection();

try {
    $stmt = $pdo->prepare("
        SELECT r.id, r.status, e.title, e.registration_deadline
        FROM event_registrations r
        JOIN events e ON e.id = r.event_id
        WHERE r.id = ? AND r.student_id = ?
        LIMIT 1
    ");
    $stmt->execute([$regId, $student['student_id_pk']]);
    $reg = $stmt->fetch();

    if (!$reg) {
        echo json_encode(['success' => false, 'message' => 'Registration not found.']);
        exit;
    }

    if ($reg['status'] === 'completed') {
        echo json_encode(['success' => false, 'message' => 'Completed events cannot be cancelled.']);
        exit;
    }

    if (strtotime($reg['registration_deadline']) < time()) {
        echo json_encode(['success' => false, 'message' => 'Cancellation deadline has passed.']);
        exit;
    }

    $update = $pdo->prepare("UPDATE event_registrations SET status = 'cancelled' WHERE id = ?");
    $update->execute([$regId]);

    addNotification(
        $student['user_id'], 
        'Registration Cancelled', 
        "Your registration for '{$reg['title']}' has been cancelled.", 
        'info', 
        'student/registrations.php'
    );

    echo json_encode(['success' => true, 'message' => 'Registration successfully cancelled.']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to cancel registration.']);
}
