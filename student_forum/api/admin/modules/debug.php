<?php
// Debug file to check 500 errors
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json; charset=utf-8');

$debug_info = [
    'timestamp' => date('Y-m-d H:i:s'),
    'steps' => []
];

try {
    // Step 1: Check session
    $debug_info['steps']['1_session'] = 'Checking session...';
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $debug_info['steps']['1_session'] = 'Session started. Session ID: ' . session_id();
    
    // Step 2: Include database
    $debug_info['steps']['2_database'] = 'Including database.php...';
    require_once __DIR__ . '/../../config/database.php';
    $debug_info['steps']['2_database'] = 'Database.php included successfully';
    
    // Step 3: Include auth
    $debug_info['steps']['3_auth'] = 'Including auth.php...';
    require_once __DIR__ . '/../../includes/auth.php';
    $debug_info['steps']['3_auth'] = 'Auth.php included successfully';
    
    // Step 4: Check login
    $debug_info['steps']['4_login'] = 'Checking login...';
    $isLoggedIn = isLoggedIn();
    $debug_info['steps']['4_login'] = 'isLoggedIn: ' . ($isLoggedIn ? 'true' : 'false');
    $debug_info['session_user_id'] = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'not set';
    
    // Step 5: Get current user
    if ($isLoggedIn) {
        $debug_info['steps']['5_get_user'] = 'Getting current user...';
        $user = getCurrentUser();
        $debug_info['steps']['5_get_user'] = $user ? 'User found' : 'User not found';
        $debug_info['user'] = $user;
        
        // Step 6: Check admin
        if ($user) {
            $debug_info['steps']['6_admin'] = 'Checking admin...';
            $isAdmin = isAdmin();
            $debug_info['steps']['6_admin'] = 'isAdmin: ' . ($isAdmin ? 'true' : 'false');
            $debug_info['user_role'] = $user['role'] ?? 'not set';
        }
    }
    
    // Step 7: Test database connection
    $debug_info['steps']['7_db_conn'] = 'Testing database connection...';
    $pdo = getDBConnection();
    $debug_info['steps']['7_db_conn'] = 'Database connection successful';
    
    // Step 8: Check modules table
    $debug_info['steps']['8_table'] = 'Checking modules table...';
    $checkTable = $pdo->query("SHOW TABLES LIKE 'modules'");
    $tableExists = $checkTable->rowCount() > 0;
    $debug_info['steps']['8_table'] = 'Modules table exists: ' . ($tableExists ? 'yes' : 'no');
    
    if ($tableExists) {
        // Step 9: Try query
        $debug_info['steps']['9_query'] = 'Trying to query modules...';
        $stmt = $pdo->query("SELECT module_id, module_name, module_code, description, created_at, updated_at FROM modules ORDER BY module_name ASC LIMIT 5");
        $modules = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $debug_info['steps']['9_query'] = 'Query successful';
        $debug_info['modules_count'] = count($modules);
        $debug_info['modules_sample'] = $modules;
    }
    
    $debug_info['success'] = true;
    
} catch (Exception $e) {
    $debug_info['success'] = false;
    $debug_info['error'] = [
        'message' => $e->getMessage(),
        'code' => $e->getCode(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ];
}

echo json_encode($debug_info, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>

