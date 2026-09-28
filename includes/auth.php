<?php
/**
 * CampusEvents - Authentication & Security Helpers
 */

if (session_status() === PHP_SESSION_NONE) {
    // Secure cookie parameters
    session_start();
}

require_once __DIR__ . '/../config/database.php';

/**
 * Returns dynamic base URL path for assets and page links
 */
function getBaseUrl(): string {
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    if (strpos($scriptName, '/campusevents') !== false) {
        return '/campusevents/';
    }
    return '/';
}

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function isAdmin(): bool {
    return isLoggedIn() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function isStudent(): bool {
    return isLoggedIn() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'student';
}

function requireLogin(string $redirectPath = null): void {
    if (!isLoggedIn()) {
        $base = getBaseUrl();
        $target = $redirectPath ? urlencode($redirectPath) : urlencode($_SERVER['REQUEST_URI'] ?? '');
        header("Location: {$base}auth/login.php?redirect={$target}");
        exit;
    }
}

function requireAdmin(): void {
    requireLogin();
    if (!isAdmin()) {
        $base = getBaseUrl();
        setFlashMessage('error', 'Unauthorized access. Administrative privileges required.');
        header("Location: {$base}student/dashboard.php");
        exit;
    }
}

function requireStudent(): void {
    requireLogin();
    if (!isStudent()) {
        $base = getBaseUrl();
        setFlashMessage('error', 'Access restricted to student accounts.');
        header("Location: {$base}admin/dashboard.php");
        exit;
    }
}

/**
 * Fetch full profile details for logged in user
 */
function getCurrentUser(): ?array {
    if (!isLoggedIn()) {
        return null;
    }

    $pdo = getDBConnection();
    $userId = (int)$_SESSION['user_id'];
    $role = $_SESSION['user_role'] ?? 'student';

    if ($role === 'admin') {
        $stmt = $pdo->prepare("
            SELECT u.id as user_id, u.email, u.role, a.id as admin_id, a.full_name, a.department, a.phone
            FROM users u
            JOIN admins a ON a.user_id = u.id
            WHERE u.id = ?
        ");
    } else {
        $stmt = $pdo->prepare("
            SELECT u.id as user_id, u.email, u.role, s.id as student_id_pk, s.student_id, s.full_name, 
                   s.phone, s.department, s.course, s.semester, s.avatar
            FROM users u
            JOIN students s ON s.user_id = u.id
            WHERE u.id = ?
        ");
    }

    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    return $user ?: null;
}

/**
 * XSS prevention helper
 */
function e(?string $string): string {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * CSRF Protection
 */
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token(?string $token): bool {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Flash Notification messages (toasts/alerts)
 */
function setFlashMessage(string $type, string $message): void {
    if (!isset($_SESSION['flash_messages'])) {
        $_SESSION['flash_messages'] = [];
    }
    $_SESSION['flash_messages'][] = ['type' => $type, 'message' => $message];
}

function getFlashMessages(): array {
    if (isset($_SESSION['flash_messages'])) {
        $messages = $_SESSION['flash_messages'];
        unset($_SESSION['flash_messages']);
        return $messages;
    }
    return [];
}

/**
 * Unread notification count for badge
 */
function getUnreadNotificationCount(int $userId): int {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$userId]);
        return (int)$stmt->fetchColumn();
    } catch (Exception $e) {
        return 0;
    }
}

/**
 * Create a new notification for a specific user
 */
function addNotification(int $userId, string $title, string $message, string $type = 'info', ?string $link = null): bool {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message, type, link) VALUES (?, ?, ?, ?, ?)");
        return $stmt->execute([$userId, $title, $message, $type, $link]);
    } catch (Exception $e) {
        return false;
    }
}
