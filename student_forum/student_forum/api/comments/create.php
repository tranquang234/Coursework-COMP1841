<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
header('Content-Type: application/json');

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
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
    echo json_encode(['success' => false, 'message' => 'Please fill in all required information']);
    exit();
}

try {
    $pdo = getDBConnection();

    // Check if answer exists
    $stmt = $pdo->prepare("SELECT answer_id FROM answers WHERE answer_id = ?");
    $stmt->execute([$answer_id]);
    
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Answer not found']);
        exit();
    }

    // Create comment
    $stmt = $pdo->prepare("INSERT INTO comments (answer_id, user_id, content) VALUES (?, ?, ?)");
    $stmt->execute([$answer_id, $user_id, $content]);
    
    $comment_id = $pdo->lastInsertId();
    echo json_encode([
        'success' => true,
        'message' => 'Comment added successfully',
        'comment_id' => $comment_id
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An error occurred while adding the comment']);
}
?>

