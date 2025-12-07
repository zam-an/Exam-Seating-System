<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__.'/config.php';

$method = $_SERVER['REQUEST_METHOD'];
$uri = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
$segments = explode('/', $uri);

// expects .../api/{resource}/{id?}
if (($apiIdx = array_search('api', $segments)) !== false) {
    $segments = array_slice($segments, $apiIdx+1);
}

$resource = $segments[0] ?? '';
// Allow calling endpoints with or without .php extension (e.g. test_connection or test_connection.php)
$resource = preg_replace('/\.php$/', '', $resource);
$id = $segments[1] ?? null;

switch ($resource) {
    case 'test_connection':
        require __DIR__.'/test_connection.php';
        break;
    case 'departments':
        require __DIR__.'/departments.php';
        break;
    case 'semesters':
        require __DIR__.'/semesters.php';
        break;
    case 'students':
        require __DIR__.'/students.php';
        break;
    case 'rooms':
        require __DIR__.'/rooms.php';
        break;
    case 'plans':
        require __DIR__.'/plans.php';
        break;
    case 'seatings':
        require __DIR__.'/seatings.php';
        break;
    case 'users':
        require __DIR__.'/users.php';
        break;
    case 'login':
        require __DIR__.'/login.php';
        break;
    default:
        echo json_encode(['success' => false, 'error' => 'Unknown endpoint.']);
}
