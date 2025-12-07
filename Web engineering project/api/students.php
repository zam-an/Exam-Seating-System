<?php
require_once __DIR__.'/config.php';
switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        if (!isset($id)) {
            $stmt = $pdo->query('SELECT * FROM students ORDER BY id');
            echo json_encode(['success'=>true,'students'=>$stmt->fetchAll()]);
        } else {
            $stmt = $pdo->prepare('SELECT * FROM students WHERE id=?');
            $stmt->execute([$id]);
            $row = $stmt->fetch();
            if ($row) echo json_encode(['success'=>true,'student'=>$row]);
            else { http_response_code(404); echo json_encode(['success'=>false,'error'=>'Not found']); }
        }
        break;
    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        $stmt = $pdo->prepare('INSERT INTO students(roll_no, full_name, seat_pref, semester_id) VALUES (?, ?, ?, ?)');
        $stmt->execute([$data['roll_no'],$data['full_name'],$data['seat_pref'] ?? null,$data['semester_id'] ?? null]);
        echo json_encode(['success'=>true,'id'=>$pdo->lastInsertId()]);
        break;
    case 'PUT':
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$id) { http_response_code(400); echo json_encode(['success'=>false,'error'=>'No ID']); break; }
        $stmt = $pdo->prepare('UPDATE students SET roll_no=?, full_name=?, seat_pref=?, semester_id=? WHERE id=?');
        $stmt->execute([$data['roll_no'],$data['full_name'],$data['seat_pref'] ?? null,$data['semester_id'] ?? null,$id]);
        echo json_encode(['success'=>true]);
        break;
    case 'DELETE':
        if (!$id) { http_response_code(400); echo json_encode(['success'=>false,'error'=>'No ID']); break; }
        $stmt = $pdo->prepare('DELETE FROM students WHERE id=?');
        $stmt->execute([$id]);
        echo json_encode(['success'=>true]);
        break;
    default:
        http_response_code(405); echo json_encode(['success'=>false,'error'=>'Method not allowed']);
}
