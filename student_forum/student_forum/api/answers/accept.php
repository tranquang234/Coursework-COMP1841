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
$user_id = $_SESSION['user_id'];

if ($answer_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid answer ID']);
    exit();
}

try {
    $pdo = getDBConnection();

    // Get answer and question information
    $stmt = $pdo->prepare("SELECT a.answer_id, a.question_id, q.user_id as question_owner 
                            FROM answers a
                            JOIN questions q ON a.question_id = q.question_id
                            WHERE a.answer_id = ?");
    $stmt->execute([$answer_id]);
    $data_row = $stmt->fetch();

    if (!$data_row) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Answer not found']);
        exit();
    }

    // Only the question owner can accept the answer
    if ($data_row['question_owner'] != $user_id && !isAdmin()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'You do not have permission to accept this answer']);
        exit();
    }

    // Start transaction to ensure data integrity
    $pdo->beginTransaction();

    // Unaccept all other answers for this question
    $question_id = $data_row['question_id'];
    $stmt = $pdo->prepare("UPDATE answers SET is_accepted = FALSE WHERE question_id = ?");
    $stmt->execute([$question_id]);

    // Accept this answer
    $stmt = $pdo->prepare("UPDATE answers SET is_accepted = TRUE WHERE answer_id = ?");
    $stmt->execute([$answer_id]);

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Answer accepted successfully']);
} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An error occurred']);
}
?>

