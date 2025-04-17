<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include configuration if not already included
if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../config/config.php';
}

// Log the logout attempt for debugging (in development mode only)
if (defined('MODE') && MODE === 'Development') {
    error_log('Logout attempt from user ID: ' . ($_SESSION['admin_id'] ?? 'unknown'));
}

// Unset all session variables
$_SESSION = array();

// If a session cookie is used, destroy that too
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Start a new session for the message
session_start();

// Set a logout message
$_SESSION['alert_message'] = 'Logout realizado com sucesso!';
$_SESSION['alert_type'] = 'success';

// Redirect to login page
header('Location: ' . BASE_URL . '/admin/Admin_Login.php');
exit;