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

$answer_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($answer_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid answer ID']);
    exit();
}

try {
    $pdo = getDBConnection();

    // Check ownership or admin permission
    $stmt = $pdo->prepare("SELECT user_id FROM answers WHERE answer_id = ?");
    $stmt->execute([$answer_id]);
    $answer = $stmt->fetch();

    if (!$answer) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Answer not found']);
        exit();
    }

    $user_id = $_SESSION['user_id'];

    if ($answer['user_id'] != $user_id && !isAdmin()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'You do not have permission to delete this answer']);
        exit();
    }

    // Delete answer (cascade will delete related comments)
    $stmt = $pdo->prepare("DELETE FROM answers WHERE answer_id = ?");
    $stmt->execute([$answer_id]);

    echo json_encode(['success' => true, 'message' => 'Answer deleted successfully']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An error occurred while deleting']);
}
?>


