<?php
require_once __DIR__.'/config.php';
switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        if (!isset($id)) {
            $stmt = $pdo->query('SELECT id, code, name, capacity, num_rows AS `rows`, cols FROM rooms ORDER BY id');
            echo json_encode(['success'=>true,'rooms'=>$stmt->fetchAll()]);
        } else {
            $stmt = $pdo->prepare('SELECT id, code, name, capacity, num_rows AS `rows`, cols FROM rooms WHERE id=?');
            $stmt->execute([$id]);
            $row = $stmt->fetch();
            if ($row) echo json_encode(['success'=>true,'room'=>$row]);
            else { http_response_code(404); echo json_encode(['success'=>false,'error'=>'Not found']); }
        }
        break;
    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        $stmt = $pdo->prepare('INSERT INTO rooms(code, name, capacity, num_rows, cols) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$data['code'],$data['name'],$data['capacity'],$data['rows'] ?? null,$data['cols'] ?? null]);
        echo json_encode(['success'=>true,'id'=>$pdo->lastInsertId()]);
        break;
    case 'PUT':
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$id) { http_response_code(400); echo json_encode(['success'=>false,'error'=>'No ID']); break; }
        $stmt = $pdo->prepare('UPDATE rooms SET code=?, name=?, capacity=?, num_rows=?, cols=? WHERE id=?');
        $stmt->execute([$data['code'],$data['name'],$data['capacity'],$data['rows'] ?? null,$data['cols'] ?? null,$id]);
        echo json_encode(['success'=>true]);
        break;
    case 'DELETE':
        if (!$id) { http_response_code(400); echo json_encode(['success'=>false,'error'=>'No ID']); break; }
        $stmt = $pdo->prepare('DELETE FROM rooms WHERE id=?');
        $stmt->execute([$id]);
        echo json_encode(['success'=>true]);
        break;
    default:
        http_response_code(405); echo json_encode(['success'=>false,'error'=>'Method not allowed']);
}
