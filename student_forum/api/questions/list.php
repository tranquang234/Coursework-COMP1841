<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
header('Content-Type: application/json');

try {
    $pdo = getDBConnection();
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    $offset = ($page - 1) * $limit;

    // Get user_id if logged in
    $current_user_id = isLoggedIn() ? $_SESSION['user_id'] : null;

    // Get total number of questions
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM questions");
    $total = $stmt->fetch()['total'];
    $total_pages = ceil($total / $limit);

    // Get the list of questions
    if ($current_user_id) {
        // If logged in, check if the user has liked it yet
        $query = "SELECT q.*, u.username, u.full_name, m.module_name,
                  (SELECT COUNT(*) FROM answers WHERE question_id = q.question_id) as answer_count,
                  (SELECT COUNT(*) FROM likes WHERE question_id = q.question_id) as like_count,
                  (SELECT COUNT(*) FROM likes WHERE question_id = q.question_id AND user_id = ?) as is_liked
                  FROM questions q
                  LEFT JOIN users u ON q.user_id = u.user_id
                  LEFT JOIN modules m ON q.module_id = m.module_id
                  ORDER BY q.created_at DESC
                  LIMIT ? OFFSET ?";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([$current_user_id, $limit, $offset]);
    } else {
        // If not logged in, do not check like
        $query = "SELECT q.*, u.username, u.full_name, m.module_name,
                  (SELECT COUNT(*) FROM answers WHERE question_id = q.question_id) as answer_count,
                  (SELECT COUNT(*) FROM likes WHERE question_id = q.question_id) as like_count,
                  0 as is_liked
                  FROM questions q
                  LEFT JOIN users u ON q.user_id = u.user_id
                  LEFT JOIN modules m ON q.module_id = m.module_id
                  ORDER BY q.created_at DESC
                  LIMIT ? OFFSET ?";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([$limit, $offset]);
    }

    $questions = [];
    while ($row = $stmt->fetch()) {
        // Transfer is_liked to boolean
        $row['is_liked'] = (int)$row['is_liked'] > 0;
        $questions[] = $row;
    }

    echo json_encode([
        'success' => true,
        'questions' => $questions,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $total_pages,
            'total' => $total,
            'limit' => $limit
        ]
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An error occurred while retrieving the question list.']);
}
?>

