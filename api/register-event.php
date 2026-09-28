<?php
/**
 * CampusEvents API - Free Event Registration Handler
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/auth.php';

if (!isLoggedIn() || !isStudent()) {
    echo json_encode([
        'success' => false, 
        'login_required' => true, 
        'message' => 'Please log in to your student account to register for events.'
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

$student = getCurrentUser();
$eventId = (int)($_POST['event_id'] ?? 0);
$csrfToken = $_POST['csrf_token'] ?? '';

if (!verify_csrf_token($csrfToken)) {
    echo json_encode(['success' => false, 'message' => 'Invalid security token. Please refresh the page.']);
    exit;
}

if ($eventId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid event identifier.']);
    exit;
}

$pdo = getDBConnection();

try {
    // 1. Check Event Existence, published status, and deadline
    $stmtEvent = $pdo->prepare("
        SELECT id, title, max_participants, registration_deadline, is_published,
               (SELECT COUNT(*) FROM event_registrations WHERE event_id = events.id AND status != 'cancelled') as registered_count
        FROM events 
        WHERE id = ?
        LIMIT 1
    ");
    $stmtEvent->execute([$eventId]);
    $event = $stmtEvent->fetch();

    if (!$event || !$event['is_published']) {
        echo json_encode(['success' => false, 'message' => 'This event is currently unavailable.']);
        exit;
    }

    if (strtotime($event['registration_deadline']) < time()) {
        echo json_encode(['success' => false, 'message' => 'Registration deadline has passed for this event.']);
        exit;
    }

    if ($event['registered_count'] >= $event['max_participants']) {
        echo json_encode(['success' => false, 'message' => 'Sorry, this event has reached its maximum participant capacity.']);
        exit;
    }

    // 2. Check duplicate registration
    $stmtCheck = $pdo->prepare("
        SELECT id, status FROM event_registrations 
        WHERE event_id = ? AND student_id = ?
        LIMIT 1
    ");
    $stmtCheck->execute([$eventId, $student['student_id_pk']]);
    $existing = $stmtCheck->fetch();

    if ($existing) {
        if ($existing['status'] === 'cancelled') {
            // Re-activate previously cancelled registration
            $stmtUpdate = $pdo->prepare("
                UPDATE event_registrations 
                SET status = 'registered', registration_date = NOW() 
                WHERE id = ?
            ");
            $stmtUpdate->execute([$existing['id']]);

            addNotification($student['user_id'], 'Registration Re-activated', "Your registration for '{$event['title']}' has been re-activated.", 'success', 'student/registrations.php');

            echo json_encode([
                'success' => true, 
                'message' => 'Registration successful! Your pass has been restored.',
                'registered' => true
            ]);
            exit;
        } else {
            echo json_encode([
                'success' => false, 
                'message' => 'You are already registered for this event.',
                'already_registered' => true
            ]);
            exit;
        }
    }

    // 3. Insert new registration
    $stmtInsert = $pdo->prepare("
        INSERT INTO event_registrations (event_id, student_id, registration_date, status)
        VALUES (?, ?, NOW(), 'registered')
    ");
    $stmtInsert->execute([$eventId, $student['student_id_pk']]);

    // 4. Send Notifications
    addNotification(
        $student['user_id'],
        'Registration Confirmed!',
        "You have successfully registered for '{$event['title']}'. We look forward to seeing you there!",
        'success',
        'student/registrations.php'
    );

    // Notify admins
    $stmtAdmins = $pdo->query("SELECT user_id FROM admins");
    $admins = $stmtAdmins->fetchAll();
    foreach ($admins as $adminUser) {
        addNotification(
            $adminUser['user_id'],
            'New Event Registration',
            "{$student['full_name']} registered for '{$event['title']}'.",
            'info',
            'admin/registrations.php'
        );
    }

    echo json_encode([
        'success' => true,
        'message' => 'Registration successful! Event pass added to your dashboard.',
        'registered' => true
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An error occurred during registration. Please try again.']);
}
