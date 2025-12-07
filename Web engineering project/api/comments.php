<?php
require_once __DIR__.'/config.php';

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        if (!isset($id)) {
            $stmt = $pdo->query('SELECT c.id, c.content, c.created_at, u.username, p.id as post_id FROM comments c JOIN users u ON c.user_id=u.id JOIN posts p ON c.post_id=p.id ORDER BY c.created_at DESC');
            echo json_encode(['success'=>true,'comments'=>$stmt->fetchAll()]);
        } else {
            $stmt = $pdo->prepare('SELECT c.id, c.content, c.created_at, u.username, p.id as post_id FROM comments c JOIN users u ON c.user_id=u.id JOIN posts p ON c.post_id=p.id WHERE c.id=?');
            $stmt->execute([$id]);
            $comment = $stmt->fetch();
            if ($comment) {
                echo json_encode(['success'=>true,'comment'=>$comment]);
            } else {
                http_response_code(404);
                echo json_encode(['success'=>false,'error'=>'Not found']);
            }
        }
        break;
    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        $stmt = $pdo->prepare('INSERT INTO comments(post_id, user_id, content) VALUES (?, ?, ?)');
        $stmt->execute([$data['post_id'],$data['user_id'],$data['content']]);
        echo json_encode(['success'=>true,'id'=>$pdo->lastInsertId()]);
        break;
    case 'PUT':
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$id) { http_response_code(400); echo json_encode(['success'=>false,'error'=>'No ID']); break; }
        $stmt = $pdo->prepare('UPDATE comments SET content=? WHERE id=?');
        $stmt->execute([$data['content'],$id]);
        echo json_encode(['success'=>true]);
        break;
    case 'DELETE':
        if (!$id) { http_response_code(400); echo json_encode(['success'=>false,'error'=>'No ID']); break; }
        $stmt = $pdo->prepare('DELETE FROM comments WHERE id=?');
        $stmt->execute([$id]);
        echo json_encode(['success'=>true]);
        break;
    default:
        http_response_code(405);
        echo json_encode(['success'=>false,'error'=>'Method not allowed']);
}
