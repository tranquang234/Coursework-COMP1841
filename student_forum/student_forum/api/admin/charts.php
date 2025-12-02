<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
header('Content-Type: application/json');

// Only admin can access
requireAdmin();

try {
    $pdo = getDBConnection();
    $data = [];

    // Statistics of users by role
    $stmt = $pdo->query("SELECT role, COUNT(*) as count FROM users GROUP BY role");
    $users_by_role = [];
    while ($row = $stmt->fetch()) {
        $users_by_role[$row['role']] = (int)$row['count'];
    }
    $data['users_by_role'] = $users_by_role;

    // Statistics of user registrations by month (last 7 months)
    $stmt = $pdo->prepare("SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as month,
        COUNT(*) as count
        FROM users
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 MONTH)
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY month ASC");
    $stmt->execute();
    $users_by_month = [];
    while ($row = $stmt->fetch()) {
        $users_by_month[] = [
            'month' => $row['month'],
            'count' => (int)$row['count']
        ];
    }
    $data['users_by_month'] = $users_by_month;

    // Statistics of questions by month (last 7 months)
    $stmt = $pdo->prepare("SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as month,
        COUNT(*) as count
        FROM questions
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 MONTH)
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY month ASC");
    $stmt->execute();
    $questions_by_month = [];
    while ($row = $stmt->fetch()) {
        $questions_by_month[] = [
            'month' => $row['month'],
            'count' => (int)$row['count']
        ];
    }
    $data['questions_by_month'] = $questions_by_month;

    // Statistics of questions with most answers (top 5)
    $stmt = $pdo->prepare("SELECT 
        q.question_id,
        q.title,
        COUNT(a.answer_id) as answer_count
        FROM questions q
        LEFT JOIN answers a ON q.question_id = a.question_id
        GROUP BY q.question_id, q.title
        ORDER BY answer_count DESC
        LIMIT 5");
    $stmt->execute();
    $top_questions = [];
    while ($row = $stmt->fetch()) {
        $top_questions[] = [
            'question_id' => (int)$row['question_id'],
            'title' => $row['title'],
            'answer_count' => (int)$row['answer_count']
        ];
    }
    $data['top_questions'] = $top_questions;

    // Statistics of most active users (top 5 - by number of questions + answers)
    $stmt = $pdo->prepare("SELECT 
        u.user_id,
        u.username,
        u.full_name,
        (SELECT COUNT(*) FROM questions WHERE user_id = u.user_id) as questions_count,
        (SELECT COUNT(*) FROM answers WHERE user_id = u.user_id) as answers_count
        FROM users u
        ORDER BY (questions_count + answers_count) DESC
        LIMIT 5");
    $stmt->execute();
    $top_users = [];
    while ($row = $stmt->fetch()) {
        $top_users[] = [
            'user_id' => (int)$row['user_id'],
            'username' => $row['username'],
            'full_name' => $row['full_name'],
            'questions_count' => (int)$row['questions_count'],
            'answers_count' => (int)$row['answers_count'],
            'total' => (int)$row['questions_count'] + (int)$row['answers_count']
        ];
    }
    $data['top_users'] = $top_users;

    echo json_encode([
        'success' => true,
        'data' => $data
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An error occurred while fetching chart data']);
}
?>

