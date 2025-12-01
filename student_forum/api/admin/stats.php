<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
header('Content-Type: application/json');

// Only admin can access
requireAdmin();

try {
    $pdo = getDBConnection();

    // Overall statistics
    $stats = [];

    // Total users
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $stats['total_users'] = (int)$stmt->fetch()['count'];

    // Total questions
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM questions");
    $stats['total_questions'] = (int)$stmt->fetch()['count'];

    // Total answers
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM answers");
    $stats['total_answers'] = (int)$stmt->fetch()['count'];

    // Total comments
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM comments");
    $stats['total_comments'] = (int)$stmt->fetch()['count'];

    // Statistics by role
    $stmt = $pdo->query("SELECT role, COUNT(*) as count FROM users GROUP BY role");
    $stats['users_by_role'] = [];
    while ($row = $stmt->fetch()) {
        $stats['users_by_role'][$row['role']] = (int)$row['count'];
    }

    echo json_encode([
        'success' => true,
        'stats' => $stats
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An error occurred while fetching statistics']);
}
?>

