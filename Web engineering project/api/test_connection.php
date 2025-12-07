<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/config.php';

try {
    // If we reached here, $pdo is already created in config.php
    $stmt = $pdo->query('SELECT 1');
    $stmt->fetch();

    echo json_encode([
        'success' => true,
        'message' => 'Database connection OK'
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}


