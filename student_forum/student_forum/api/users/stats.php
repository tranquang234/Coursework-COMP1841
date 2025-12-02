<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'You need to login to view statistics'
    ]);
    exit();
}

try {
$user_id = $_SESSION['user_id'];
    $pdo = getDBConnection();

    // Count number of questions
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM questions WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $questions_count = (int)$stmt->fetch()['count'];

    // Count number of answers
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM answers WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $answers_count = (int)$stmt->fetch()['count'];

    // Count number of comments
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM comments WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $comments_count = (int)$stmt->fetch()['count'];

    // Count number of likes received (for user's questions and answers)
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM likes l
                            LEFT JOIN questions q ON l.question_id = q.question_id
                            LEFT JOIN answers a ON l.answer_id = a.answer_id
                            WHERE (q.user_id = ? OR a.user_id = ?)");
    $stmt->execute([$user_id, $user_id]);
    $likes_received = (int)$stmt->fetch()['count'];

    // Count number of likes given (user has liked)
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM likes WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $likes_given = (int)$stmt->fetch()['count'];

    echo json_encode([
        'success' => true,
        'stats' => [
            'questions_count' => $questions_count,
            'answers_count' => $answers_count,
            'comments_count' => $comments_count,
            'likes_received' => $likes_received,
            'likes_given' => $likes_given
        ]
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database connection error'
    ]);
}
?>

