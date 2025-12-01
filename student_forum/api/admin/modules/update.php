<?php
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../includes/auth.php';
header('Content-Type: application/json');

// Only admin can access
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$module_id = isset($data['module_id']) ? (int)$data['module_id'] : 0;
$module_name = trim($data['module_name'] ?? '');
$module_code = trim($data['module_code'] ?? '');
$description = trim($data['description'] ?? '');

if ($module_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid module ID']);
    exit();
}

if (empty($module_name)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Module name cannot be empty']);
    exit();
}

try {
    $pdo = getDBConnection();

    // Check if module exists
    $stmt = $pdo->prepare("SELECT module_id FROM modules WHERE module_id = ?");
    $stmt->execute([$module_id]);
    
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Module not found']);
        exit();
    }

    // Check if module name already exists in another module
    $stmt = $pdo->prepare("SELECT module_id FROM modules WHERE module_name = ? AND module_id != ?");
    $stmt->execute([$module_name, $module_id]);
    
    if ($stmt->fetch()) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Module name already exists']);
        exit();
    }

    // Update module
    $stmt = $pdo->prepare("UPDATE modules SET module_name = ?, module_code = ?, description = ? WHERE module_id = ?");
    $stmt->execute([$module_name, $module_code ?: null, $description ?: null, $module_id]);

    echo json_encode([
        'success' => true,
        'message' => 'Module updated successfully'
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    
    // Check for duplicate error
    if ($e->getCode() == 23000 || strpos($e->getMessage(), 'Duplicate') !== false) {
        echo json_encode(['success' => false, 'message' => 'Module name already exists']);
    } else {
        echo json_encode(['success' => false, 'message' => 'An error occurred while updating the module']);
    }
}
?>
