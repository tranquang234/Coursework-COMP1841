<?php
// Disable error display to avoid breaking JSON response
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Start output buffering
if (ob_get_level() > 0) {
    ob_clean();
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

// Set header
header('Content-Type: application/json; charset=utf-8');

requireLogin();

// Accept both POST and PUT methods
$method = $_SERVER['REQUEST_METHOD'];
if ($method !== 'PUT' && $method !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed'], JSON_UNESCAPED_UNICODE);
    exit();
}

// Read data from input
$raw_input = file_get_contents('php://input');
$data = json_decode($raw_input, true);

// If JSON parsing fails, try getting from POST
if ($data === null && $method === 'POST' && !empty($_POST)) {
    $data = $_POST;
}

if (!$data) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid data'], JSON_UNESCAPED_UNICODE);
    exit();
}

$email = trim($data['email'] ?? '');
$full_name = trim($data['full_name'] ?? '');
$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'You need to login'], JSON_UNESCAPED_UNICODE);
    exit();
}

if (empty($email)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Email cannot be empty'], JSON_UNESCAPED_UNICODE);
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid email'], JSON_UNESCAPED_UNICODE);
    exit();
}

// Convert empty string to NULL
$full_name = empty($full_name) ? null : $full_name;

try {
    $pdo = getDBConnection();

    // Check if email is already used by another user
    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
    $stmt->execute([$email, $user_id]);
    if ($stmt->fetch()) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'This email is already used by another account'], JSON_UNESCAPED_UNICODE);
        exit();
    }

    // Check if user exists
    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'User not found'], JSON_UNESCAPED_UNICODE);
        exit();
    }

    // Update information
    $stmt = $pdo->prepare("UPDATE users SET email = ?, full_name = ? WHERE user_id = ?");
    $result = $stmt->execute([$email, $full_name, $user_id]);
    
    if (!$result) {
        throw new Exception('Unable to update information');
    }

    echo json_encode([
        'success' => true,
        'message' => 'Information updated successfully',
        'user' => [
            'email' => $email,
            'full_name' => $full_name
        ]
    ], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    http_response_code(500);
    error_log('Update profile error: ' . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'An error occurred while updating information: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(500);
    error_log('Update profile error: ' . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'An error occurred: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>

