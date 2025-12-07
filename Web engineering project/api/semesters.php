<?php
require_once __DIR__.'/config.php';
switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        if (!isset($id)) {
            $stmt = $pdo->query('SELECT * FROM semesters ORDER BY id');
            echo json_encode(['success'=>true,'semesters'=>$stmt->fetchAll()]);
        } else {
            $stmt = $pdo->prepare('SELECT * FROM semesters WHERE id=?');
            $stmt->execute([$id]);
            $row = $stmt->fetch();
            if ($row) echo json_encode(['success'=>true,'semester'=>$row]);
            else { http_response_code(404); echo json_encode(['success'=>false,'error'=>'Not found']); }
        }
        break;
    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        $stmt = $pdo->prepare('INSERT INTO semesters(title, code, department_id, exam_date) VALUES (?, ?, ?, ?)');
        $stmt->execute([$data['title'],$data['code'],$data['department_id'],$data['exam_date']]);
        echo json_encode(['success'=>true,'id'=>$pdo->lastInsertId()]);
        break;
    case 'PUT':
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$id) { http_response_code(400); echo json_encode(['success'=>false,'error'=>'No ID']); break; }
        $stmt = $pdo->prepare('UPDATE semesters SET title=?, code=?, department_id=?, exam_date=? WHERE id=?');
        $stmt->execute([$data['title'],$data['code'],$data['department_id'],$data['exam_date'],$id]);
        echo json_encode(['success'=>true]);
        break;
    case 'DELETE':
        if (!$id) { http_response_code(400); echo json_encode(['success'=>false,'error'=>'No ID']); break; }
        $stmt = $pdo->prepare('DELETE FROM semesters WHERE id=?');
        $stmt->execute([$id]);
        echo json_encode(['success'=>true]);
        break;
    default:
        http_response_code(405); echo json_encode(['success'=>false,'error'=>'Method not allowed']);
}
