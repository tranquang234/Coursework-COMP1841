<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'You need to login to view your likes given list'
    ]);
    exit();
}

try {
    $user_id = $_SESSION['user_id'];
    $pdo = getDBConnection();
    
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    $offset = ($page - 1) * $limit;

    // Get total number of likes given
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM likes WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $total = (int)$stmt->fetch()['total'];
    $total_pages = ceil($total / $limit);

    // Get list of likes given - separate queries to avoid UNION errors
    $likes = [];
    
    // Get likes for questions
    $query_questions = "SELECT l.like_id, l.user_id, l.created_at,
                        q.question_id, q.title as question_title, q.content as question_content,
                        q.user_id as question_user_id, 
                        u.username as question_username, u.full_name as question_full_name,
                        NULL as answer_id, NULL as answer_content, NULL as answer_user_id,
                        NULL as answer_username, NULL as answer_full_name,
                        'question' as like_type
                        FROM likes l
                        INNER JOIN questions q ON l.question_id = q.question_id
                        LEFT JOIN users u ON q.user_id = u.user_id
                        WHERE l.user_id = ? AND l.answer_id IS NULL
                        ORDER BY l.created_at DESC";
    
    $stmt = $pdo->prepare($query_questions);
    $stmt->execute([$user_id]);
    $question_likes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get likes for answers
    $query_answers = "SELECT l.like_id, l.user_id, l.created_at,
                      NULL as question_id, NULL as question_title, NULL as question_content,
                      NULL as question_user_id, NULL as question_username, NULL as question_full_name,
                      a.answer_id, a.content as answer_content,
                      q.question_id, q.title as question_title,
                      a.user_id as answer_user_id,
                      u.username as answer_username, u.full_name as answer_full_name,
                      'answer' as like_type
                      FROM likes l
                      INNER JOIN answers a ON l.answer_id = a.answer_id
                      INNER JOIN questions q ON a.question_id = q.question_id
                      LEFT JOIN users u ON a.user_id = u.user_id
                      WHERE l.user_id = ? AND l.question_id IS NULL
                      ORDER BY l.created_at DESC";
    
    $stmt = $pdo->prepare($query_answers);
    $stmt->execute([$user_id]);
    $answer_likes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Merge and sort
    $likes = array_merge($question_likes, $answer_likes);
    
    // Sort by creation time (newest first)
    usort($likes, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });
    
    // Pagination
    $likes = array_slice($likes, $offset, $limit);

    echo json_encode([
        'success' => true,
        'likes' => $likes,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $total_pages,
            'total' => $total,
            'limit' => $limit
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

