<?php
// Disable error display to avoid breaking JSON response
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once __DIR__ . '/../../config/database.php';

// Ensure header is set before any output
header('Content-Type: application/json; charset=utf-8');

// Session has been initialized in database.php, just need to destroy
if (session_status() === PHP_SESSION_ACTIVE) {
    $_SESSION = array();
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}

echo json_encode(['success' => true, 'message' => 'Logout successful'], JSON_UNESCAPED_UNICODE);
exit();
?>

