<?php
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../includes/auth.php';
header('Content-Type: application/json');

// Only admin can access
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$module_id = isset($data['module_id']) ? (int)$data['module_id'] : 0;

if ($module_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid module ID']);
    exit();
}

try {
    $pdo = getDBConnection();

    // Check if module exists
    $stmt = $pdo->prepare("SELECT module_id FROM modules WHERE module_id = ?");
    $stmt->execute([$module_id]);
    $module = $stmt->fetch();

    if (!$module) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Module not found']);
        exit();
    }

    // Delete module
    $stmt = $pdo->prepare("DELETE FROM modules WHERE module_id = ?");
    $stmt->execute([$module_id]);

    echo json_encode([
        'success' => true,
        'message' => 'Module deleted successfully'
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An error occurred while deleting the module']);
}
?>
