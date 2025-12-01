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
$question_id = isset($data['question_id']) ? (int)$data['question_id'] : null;
$answer_id = isset($data['answer_id']) ? (int)$data['answer_id'] : null;
$user_id = $_SESSION['user_id'];

if (($question_id === null && $answer_id === null) || ($question_id !== null && $answer_id !== null)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Must provide question_id or answer_id']);
    exit();
}

try {
    $pdo = getDBConnection();

    // Check if already liked
    if ($question_id !== null) {
        $stmt = $pdo->prepare("SELECT like_id FROM likes WHERE user_id = ? AND question_id = ? AND answer_id IS NULL");
        $stmt->execute([$user_id, $question_id]);
    } else {
        $stmt = $pdo->prepare("SELECT like_id FROM likes WHERE user_id = ? AND answer_id = ? AND question_id IS NULL");
        $stmt->execute([$user_id, $answer_id]);
    }
    $like = $stmt->fetch();

    if ($like) {
        // Already liked, unlike it
        $delete_stmt = $pdo->prepare("DELETE FROM likes WHERE like_id = ?");
        $delete_stmt->execute([$like['like_id']]);
        $action = 'unliked';
    } else {
        // Not liked yet, add like
        $insert_stmt = $pdo->prepare("INSERT INTO likes (user_id, question_id, answer_id) VALUES (?, ?, ?)");
        $insert_stmt->execute([$user_id, $question_id, $answer_id]);
        $action = 'liked';
    }

    // Count likes
    if ($question_id !== null) {
        $count_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM likes WHERE question_id = ?");
        $count_stmt->execute([$question_id]);
    } else {
        $count_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM likes WHERE answer_id = ?");
        $count_stmt->execute([$answer_id]);
    }
    $count = $count_stmt->fetch()['count'];

    echo json_encode([
        'success' => true,
        'action' => $action,
        'like_count' => (int)$count
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An error occurred']);
}
?>

