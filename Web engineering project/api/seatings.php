<?php
require_once __DIR__.'/config.php';
switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        if (!isset($id)) {
            $stmt = $pdo->query('SELECT * FROM seatings ORDER BY id');
            echo json_encode(['success'=>true,'seatings'=>$stmt->fetchAll()]);
        } else {
            $stmt = $pdo->prepare('SELECT * FROM seatings WHERE id=?');
            $stmt->execute([$id]);
            $row = $stmt->fetch();
            if ($row) echo json_encode(['success'=>true,'seating'=>$row]);
            else { http_response_code(404); echo json_encode(['success'=>false,'error'=>'Not found']); }
        }
        break;
    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        $stmt = $pdo->prepare('INSERT INTO seatings(plan_id, student_id, room_id, seat_row, seat_col) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$data['plan_id'],$data['student_id'],$data['room_id'],$data['seat_row'],$data['seat_col']]);
        echo json_encode(['success'=>true,'id'=>$pdo->lastInsertId()]);
        break;
    case 'PUT':
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$id) { http_response_code(400); echo json_encode(['success'=>false,'error'=>'No ID']); break; }
        $stmt = $pdo->prepare('UPDATE seatings SET plan_id=?, student_id=?, room_id=?, seat_row=?, seat_col=? WHERE id=?');
        $stmt->execute([$data['plan_id'],$data['student_id'],$data['room_id'],$data['seat_row'],$data['seat_col'],$id]);
        echo json_encode(['success'=>true]);
        break;
    case 'DELETE':
        if (!$id) { http_response_code(400); echo json_encode(['success'=>false,'error'=>'No ID']); break; }
        $stmt = $pdo->prepare('DELETE FROM seatings WHERE id=?');
        $stmt->execute([$id]);
        echo json_encode(['success'=>true]);
        break;
    default:
        http_response_code(405); echo json_encode(['success'=>false,'error'=>'Method not allowed']);
}
