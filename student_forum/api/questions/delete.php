<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
header('Content-Type: application/json');

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

$question_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($question_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid question ID']);
    exit();
}

try {
    $pdo = getDBConnection();

    // Check ownership or admin rights
    $stmt = $pdo->prepare("SELECT user_id FROM questions WHERE question_id = ?");
    $stmt->execute([$question_id]);
    $question = $stmt->fetch();

    if (!$question) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'No question found.']);
        exit();
    }

    $user_id = $_SESSION['user_id'];

    if ($question['user_id'] != $user_id && !isAdmin()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'You do not have permission to delete this question.']);
        exit();
    }

    // Delete question (cascade will delete related answers and comments)
    $stmt = $pdo->prepare("DELETE FROM questions WHERE question_id = ?");
    $stmt->execute([$question_id]);

    echo json_encode(['success' => true, 'message' => 'Question deleted successfully']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An error occurred while deleting.']);
}
?>

