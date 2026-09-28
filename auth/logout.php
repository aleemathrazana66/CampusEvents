<?php
/**
 * CampusEvents - Authentication Logout
 */
require_once __DIR__ . '/../includes/auth.php';

$baseUrl = getBaseUrl();

$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();

// Start a fresh session just to set flash message
session_start();
setFlashMessage('info', 'You have been safely logged out. See you next time!');
header("Location: {$baseUrl}auth/login.php");
exit;
