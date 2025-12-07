<?php
require_once __DIR__.'/config.php';

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        if (!isset($id)) {
            // List all users
            $stmt = $pdo->query('SELECT id, username, email, created_at FROM users');
            echo json_encode(['success'=>true,'users'=>$stmt->fetchAll()]);
        } else {
            // Single user
            $stmt = $pdo->prepare('SELECT id, username, email, created_at FROM users WHERE id=?');
            $stmt->execute([$id]);
            $user = $stmt->fetch();
            if ($user) {
                echo json_encode(['success'=>true,'user'=>$user]);
            } else {
                http_response_code(404);
                echo json_encode(['success'=>false,'error'=>'Not found']);
            }
        }
        break;
    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        $stmt = $pdo->prepare('INSERT INTO users(username,email,password) VALUES (?, ?, ?)');
        $passhash = password_hash($data['password'], PASSWORD_DEFAULT);
        $stmt->execute([$data['username'],$data['email'],$passhash]);
        echo json_encode(['success'=>true,'id'=>$pdo->lastInsertId()]);
        break;
    case 'PUT':
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$id) { http_response_code(400); echo json_encode(['success'=>false,'error'=>'No ID']); break; }
        if (isset($data['password'])) {
            $stmt = $pdo->prepare('UPDATE users SET username=?, email=?, password=? WHERE id=?');
            $passhash = password_hash($data['password'], PASSWORD_DEFAULT);
            $stmt->execute([$data['username'],$data['email'],$passhash,$id]);
        } else {
            $stmt = $pdo->prepare('UPDATE users SET username=?, email=? WHERE id=?');
            $stmt->execute([$data['username'],$data['email'],$id]);
        }
        echo json_encode(['success'=>true]);
        break;
    case 'DELETE':
        if (!$id) { http_response_code(400); echo json_encode(['success'=>false,'error'=>'No ID']); break; }
        $stmt = $pdo->prepare('DELETE FROM users WHERE id=?');
        $stmt->execute([$id]);
        echo json_encode(['success'=>true]);
        break;
    default:
        http_response_code(405);
        echo json_encode(['success'=>false,'error'=>'Method not allowed']);
}
