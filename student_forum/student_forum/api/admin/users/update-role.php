<?php
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../includes/auth.php';
header('Content-Type: application/json');

// Only admin can access
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$user_id = isset($data['user_id']) ? (int)$data['user_id'] : 0;
$new_role = isset($data['role']) ? trim($data['role']) : '';
$current_user_id = $_SESSION['user_id'];

if ($user_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
    exit();
}

// Validate role
$allowed_roles = ['student', 'teacher', 'admin'];
if (!in_array($new_role, $allowed_roles)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid role']);
    exit();
}

// Do not allow changing your own role
if ($user_id == $current_user_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'You cannot change your own role']);
    exit();
}

try {
    $pdo = getDBConnection();

    // Check if user exists
    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit();
    }

    // Update role
    $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE user_id = ?");
    $stmt->execute([$new_role, $user_id]);

    echo json_encode([
        'success' => true,
        'message' => 'Role updated successfully',
        'role' => $new_role
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An error occurred while updating the role']);
}
?>

