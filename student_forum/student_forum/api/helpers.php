<?php
// Helper functions for API

// Set up JSON response header
function setJsonHeader() {
    header('Content-Type: application/json; charset=utf-8');
}

// Return JSON response successfully
function jsonSuccess($message = '', $data = []) {
    setJsonHeader();
    $response = ['success' => true];
    if ($message) {
        $response['message'] = $message;
    }
    if (!empty($data)) {
        $response = array_merge($response, $data);
    }
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit();
}

// Return JSON response error
function jsonError($message = 'Errors occur', $code = 400) {
    setJsonHeader();
    http_response_code($code);
    echo json_encode([
        'success' => false,
        'message' => $message
    ], JSON_UNESCAPED_UNICODE);
    exit();
}
?>





