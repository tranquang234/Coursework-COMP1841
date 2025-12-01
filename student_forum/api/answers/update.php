<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
header('Content-Type: application/json');

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$answer_id = isset($data['answer_id']) ? (int)$data['answer_id'] : 0;
$content = trim($data['content'] ?? '');
$user_id = $_SESSION['user_id'];

if ($answer_id <= 0 || empty($content)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit();
}

try {
    $pdo = getDBConnection();

    // Check ownership or admin permission
    $stmt = $pdo->prepare("SELECT user_id FROM answers WHERE answer_id = ?");
    $stmt->execute([$answer_id]);
    $answer = $stmt->fetch();

    if (!$answer) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Answer not found']);
        exit();
    }

    if ($answer['user_id'] != $user_id && !isAdmin()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'You do not have permission to edit this answer']);
        exit();
    }

    // Update answer
    $stmt = $pdo->prepare("UPDATE answers SET content = ? WHERE answer_id = ?");
    $stmt->execute([$content, $answer_id]);

    echo json_encode(['success' => true, 'message' => 'Answer updated successfully']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An error occurred while updating']);
}
?>


