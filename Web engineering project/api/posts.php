<?php
require_once __DIR__.'/config.php';

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        if (!isset($id)) {
            $stmt = $pdo->query('SELECT p.id, p.title, p.content, p.created_at, u.username FROM posts p JOIN users u ON p.user_id=u.id ORDER BY p.created_at DESC');
            echo json_encode(['success'=>true,'posts'=>$stmt->fetchAll()]);
        } else {
            $stmt = $pdo->prepare('SELECT p.id, p.title, p.content, p.created_at, u.username FROM posts p JOIN users u ON p.user_id=u.id WHERE p.id=?');
            $stmt->execute([$id]);
            $post = $stmt->fetch();
            if ($post) {
                echo json_encode(['success'=>true,'post'=>$post]);
            } else {
                http_response_code(404);
                echo json_encode(['success'=>false,'error'=>'Not found']);
            }
        }
        break;
    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        $stmt = $pdo->prepare('INSERT INTO posts(user_id, title, content) VALUES (?, ?, ?)');
        $stmt->execute([$data['user_id'],$data['title'],$data['content']]);
        echo json_encode(['success'=>true,'id'=>$pdo->lastInsertId()]);
        break;
    case 'PUT':
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$id) { http_response_code(400); echo json_encode(['success'=>false,'error'=>'No ID']); break; }
        $stmt = $pdo->prepare('UPDATE posts SET title=?, content=? WHERE id=?');
        $stmt->execute([$data['title'],$data['content'],$id]);
        echo json_encode(['success'=>true]);
        break;
    case 'DELETE':
        if (!$id) { http_response_code(400); echo json_encode(['success'=>false,'error'=>'No ID']); break; }
        $stmt = $pdo->prepare('DELETE FROM posts WHERE id=?');
        $stmt->execute([$id]);
        echo json_encode(['success'=>true]);
        break;
    default:
        http_response_code(405);
        echo json_encode(['success'=>false,'error'=>'Method not allowed']);
}
