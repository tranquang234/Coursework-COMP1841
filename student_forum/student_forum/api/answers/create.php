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
$question_id = isset($data['question_id']) ? (int)$data['question_id'] : 0;
$content = trim($data['content'] ?? '');
$user_id = $_SESSION['user_id'];

if ($question_id <= 0 || empty($content)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Please fill in all required information']);
    exit();
}

try {
    $pdo = getDBConnection();

    // Check if question exists
    $stmt = $pdo->prepare("SELECT question_id FROM questions WHERE question_id = ?");
    $stmt->execute([$question_id]);
    
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Question not found']);
        exit();
    }

    // Create answer
    $stmt = $pdo->prepare("INSERT INTO answers (question_id, user_id, content) VALUES (?, ?, ?)");
    $stmt->execute([$question_id, $user_id, $content]);
    
    $answer_id = $pdo->lastInsertId();
    echo json_encode([
        'success' => true,
        'message' => 'Answer submitted successfully',
        'answer_id' => $answer_id
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An error occurred while submitting the answer']);
}
?>

