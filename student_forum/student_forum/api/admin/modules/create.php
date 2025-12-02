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
$module_name = trim($data['module_name'] ?? '');
$module_code = trim($data['module_code'] ?? '');
$description = trim($data['description'] ?? '');

if (empty($module_name)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Module name cannot be empty']);
    exit();
}

try {
    $pdo = getDBConnection();

    // Check if module name already exists
    $stmt = $pdo->prepare("SELECT module_id FROM modules WHERE module_name = ?");
    $stmt->execute([$module_name]);
    
    if ($stmt->fetch()) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Module name already exists']);
        exit();
    }

    // Add new module
    $stmt = $pdo->prepare("INSERT INTO modules (module_name, module_code, description) VALUES (?, ?, ?)");
    $stmt->execute([$module_name, $module_code ?: null, $description ?: null]);
    
    $module_id = $pdo->lastInsertId();
    
    echo json_encode([
        'success' => true,
        'message' => 'Module added successfully',
        'module_id' => $module_id
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    
    // Check for duplicate error
    if ($e->getCode() == 23000 || strpos($e->getMessage(), 'Duplicate') !== false) {
        echo json_encode(['success' => false, 'message' => 'Module name already exists']);
    } else {
        echo json_encode(['success' => false, 'message' => 'An error occurred while adding the module']);
    }
}
?>
