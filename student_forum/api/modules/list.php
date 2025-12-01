<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
header('Content-Type: application/json; charset=utf-8');

// No login required to view module list (public access)
// But if you want only logged-in users to view, uncomment the lines below:
// if (!isLoggedIn()) {
//     http_response_code(401);
//     echo json_encode(['success' => false, 'message' => 'Please login'], JSON_UNESCAPED_UNICODE);
//     exit();
// }

try {
    $pdo = getDBConnection();
    
    // Check and create modules table if it doesn't exist
    try {
        $checkTable = $pdo->query("SHOW TABLES LIKE 'modules'");
        if ($checkTable->rowCount() == 0) {
            $createTableSQL = "
                CREATE TABLE IF NOT EXISTS modules (
                    module_id INT AUTO_INCREMENT PRIMARY KEY,
                    module_name VARCHAR(100) NOT NULL UNIQUE,
                    module_code VARCHAR(50),
                    description TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_module_name (module_name)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ";
            $pdo->exec($createTableSQL);
        }
    } catch (PDOException $e) {
        // Ignore error if table already exists
    }

    // Get list of modules
    $query = "SELECT 
        module_id,
        module_name,
        module_code,
        description
        FROM modules
        ORDER BY module_name ASC";

    $stmt = $pdo->query($query);
    $modules = [];

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $modules[] = [
            'module_id' => (int)$row['module_id'],
            'module_name' => $row['module_name'],
            'module_code' => $row['module_code'],
            'description' => $row['description']
        ];
    }

    echo json_encode([
        'success' => true,
        'modules' => $modules
    ], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    http_response_code(500);
    error_log('List modules error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while fetching the module list'], JSON_UNESCAPED_UNICODE);
}
?>


