<?php
require_once __DIR__.'/config.php';

header('Content-Type: application/json');

function respondError(string $message, int $status = 400): void {
    http_response_code($status);
    echo json_encode(['success' => false, 'error' => $message]);
    exit;
}

function sanitizeIds($values): array {
    if (!is_array($values)) return [];
    $ids = array_map('intval', $values);
    $ids = array_filter($ids, fn($id) => $id > 0);
    return array_values(array_unique($ids));
}

function fetchStudentsBySemesters(PDO $pdo, array $semesterIds): array {
    if (empty($semesterIds)) return [];
    $placeholders = implode(',', array_fill(0, count($semesterIds), '?'));
    $stmt = $pdo->prepare("
        SELECT students.id, students.roll_no, students.full_name, students.seat_pref, students.semester_id,
               semesters.title AS semester_title
        FROM students
        LEFT JOIN semesters ON semesters.id = students.semester_id
        WHERE students.semester_id IN ($placeholders)
        ORDER BY students.semester_id, students.id
    ");
    $stmt->execute($semesterIds);
    return $stmt->fetchAll();
}

function fetchRoomsByIds(PDO $pdo, array $roomIds): array {
    if (empty($roomIds)) return [];
    $placeholders = implode(',', array_fill(0, count($roomIds), '?'));
    $stmt = $pdo->prepare("
        SELECT id, code, name, capacity, num_rows, cols
        FROM rooms
        WHERE id IN ($placeholders)
    ");
    $stmt->execute($roomIds);
    $rooms = $stmt->fetchAll();
    usort($rooms, function ($a, $b) use ($roomIds) {
        return array_search($a['id'], $roomIds) <=> array_search($b['id'], $roomIds);
    });
    return $rooms;
}

function buildSeatPool(array $rooms): array {
    $pool = [];
    foreach ($rooms as $room) {
        $capacity = max(0, (int)$room['capacity']);
        if ($capacity === 0) continue;

        $rows = (int)$room['num_rows'];
        $cols = (int)$room['cols'];

        if ($rows <= 0 && $cols <= 0) {
            $rows = max(1, (int)round(sqrt($capacity)));
            $cols = max(1, (int)ceil($capacity / $rows));
        } elseif ($rows <= 0) {
            $rows = max(1, (int)ceil($capacity / $cols));
        } elseif ($cols <= 0) {
            $cols = max(1, (int)ceil($capacity / $rows));
        }

        if ($rows * $cols < $capacity) {
            $rows = (int)ceil($capacity / $cols);
        }

        $count = 0;
        for ($r = 1; $r <= $rows; $r++) {
            for ($c = 1; $c <= $cols; $c++) {
                $pool[] = [
                    'room_id' => $room['id'],
                    'room_name' => $room['name'],
                    'seat_row' => $r,
                    'seat_col' => $c,
                    'total_rows' => $rows,
                    'total_cols' => $cols
                ];
                $count++;
                if ($count >= $capacity) {
                    break 2;
                }
            }
        }
    }
    return $pool;
}

function orderStudents(array $students, string $strategy): array {
    if ($strategy === 'round-robin') {
        $groups = [];
        foreach ($students as $student) {
            $key = $student['semester_id'] ?? 0;
            $groups[$key][] = $student;
        }
        $ordered = [];
        $hasData = true;
        while ($hasData) {
            $hasData = false;
            foreach ($groups as $key => &$group) {
                if (empty($group)) continue;
                $ordered[] = array_shift($group);
                $hasData = true;
            }
        }
        return $ordered;
    }

    if ($strategy === 'random') {
        shuffle($students);
        return $students;
    }

    if ($strategy === 'preference') {
        usort($students, function ($a, $b) {
            $weight = fn($pref) => match (strtolower((string)$pref)) {
                'front' => 0,
                'window' => 1,
                'aisle' => 2,
                'center' => 3,
                'back' => 4,
                default => 5,
            };
            return $weight($a['seat_pref'] ?? null) <=> $weight($b['seat_pref'] ?? null);
        });
        return $students;
    }

    return $students;
}

function pickSeatByPreference(array &$pool, ?string $preference): array {
    if (!$preference) {
        return array_shift($pool);
    }

    $preference = strtolower(trim($preference));

    $matchers = [];
    if ($preference === 'front') {
        $matchers[] = fn($seat) => $seat['seat_row'] <= max(1, (int)ceil($seat['total_rows'] / 3));
    } elseif ($preference === 'back') {
        $matchers[] = fn($seat) => $seat['seat_row'] >= max(1, $seat['total_rows'] - (int)ceil($seat['total_rows'] / 3));
    } elseif ($preference === 'window') {
        $matchers[] = fn($seat) => in_array($seat['seat_col'], [1, $seat['total_cols']], true);
    } elseif ($preference === 'aisle') {
        $matchers[] = function ($seat) {
            if ($seat['total_cols'] <= 2) {
                return in_array($seat['seat_col'], [1, $seat['total_cols']], true);
            }
            return in_array($seat['seat_col'], [2, $seat['total_cols'] - 1], true);
        };
    }

    foreach ($matchers as $matcher) {
        foreach ($pool as $index => $seat) {
            if ($matcher($seat)) {
                array_splice($pool, $index, 1);
                return $seat;
            }
        }
    }

    return array_shift($pool);
}

function generateAssignments(array $students, array $rooms, string $strategy): array {
    $seatPool = buildSeatPool($rooms);
    if (count($seatPool) < count($students)) {
        throw new Exception('Selected rooms do not have enough capacity for all students.');
    }

    $orderedStudents = orderStudents($students, $strategy);
    $assignments = [];

    foreach ($orderedStudents as $student) {
        $seat = $strategy === 'preference'
            ? pickSeatByPreference($seatPool, $student['seat_pref'] ?? null)
            : array_shift($seatPool);

        if (!$seat) {
            throw new Exception('Ran out of seats while generating assignments.');
        }

        $assignments[] = [
            'student_id' => $student['id'],
            'room_id' => $seat['room_id'],
            'seat_row' => $seat['seat_row'],
            'seat_col' => $seat['seat_col']
        ];
    }

    return $assignments;
}

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        if (!isset($id)) {
            $stmt = $pdo->query("
                SELECT p.id, p.title, p.plan_date, p.strategy, p.status, p.created_at, p.generated_at,
                       COALESCE(st.total_students, 0) AS total_students,
                       COALESCE(rr.total_rooms, 0) AS total_rooms
                FROM plans p
                LEFT JOIN (
                    SELECT plan_id, COUNT(*) AS total_students
                    FROM seatings
                    GROUP BY plan_id
                ) st ON st.plan_id = p.id
                LEFT JOIN (
                    SELECT plan_id, COUNT(*) AS total_rooms
                    FROM plan_rooms
                    GROUP BY plan_id
                ) rr ON rr.plan_id = p.id
                ORDER BY p.created_at DESC
            ");
            echo json_encode(['success' => true, 'plans' => $stmt->fetchAll()]);
        } else {
            $planStmt = $pdo->prepare('SELECT * FROM plans WHERE id = ?');
            $planStmt->execute([$id]);
            $plan = $planStmt->fetch();
            if (!$plan) {
                respondError('Plan not found', 404);
            }

            $semStmt = $pdo->prepare("
                SELECT ps.semester_id, s.title, s.code
                FROM plan_semesters ps
                JOIN semesters s ON s.id = ps.semester_id
                WHERE ps.plan_id = ?
            ");
            $semStmt->execute([$id]);
            $semesters = $semStmt->fetchAll();

            $roomStmt = $pdo->prepare("
                SELECT pr.room_id, r.name, r.code, r.capacity
                FROM plan_rooms pr
                JOIN rooms r ON r.id = pr.room_id
                WHERE pr.plan_id = ?
            ");
            $roomStmt->execute([$id]);
            $rooms = $roomStmt->fetchAll();

            $seatStmt = $pdo->prepare("
                SELECT seatings.room_id, rooms.name AS room_name, rooms.code AS room_code,
                       seatings.seat_row, seatings.seat_col,
                       students.full_name, students.roll_no
                FROM seatings
                JOIN students ON students.id = seatings.student_id
                JOIN rooms ON rooms.id = seatings.room_id
                WHERE seatings.plan_id = ?
                ORDER BY rooms.name, seatings.seat_row, seatings.seat_col
            ");
            $seatStmt->execute([$id]);
            $seatings = $seatStmt->fetchAll();

            echo json_encode([
                'success' => true,
                'plan' => $plan,
                'semesters' => $semesters,
                'rooms' => $rooms,
                'seatings' => $seatings
            ]);
        }
        break;

    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $title = trim($data['title'] ?? '');
        $planDate = $data['plan_date'] ?? null;
        $strategy = $data['strategy'] ?? 'round-robin';
        $allowedStrategies = ['round-robin', 'random', 'preference'];
        if (!in_array($strategy, $allowedStrategies, true)) {
            $strategy = 'round-robin';
        }
        $semesterIds = sanitizeIds($data['semesters'] ?? []);
        $roomIds = sanitizeIds($data['rooms'] ?? []);

        if ($title === '') respondError('Plan title is required.');
        if (empty($semesterIds)) respondError('Select at least one semester.');
        if (empty($roomIds)) respondError('Select at least one room.');

        $students = fetchStudentsBySemesters($pdo, $semesterIds);
        if (empty($students)) respondError('No students found for the selected semesters.');

        $rooms = fetchRoomsByIds($pdo, $roomIds);
        if (empty($rooms)) respondError('Selected rooms could not be found.');

        try {
            $assignments = generateAssignments($students, $rooms, $strategy);
        } catch (Exception $e) {
            respondError($e->getMessage());
        }

        $pdo->beginTransaction();
        try {
            $insertPlan = $pdo->prepare('INSERT INTO plans (title, plan_date, strategy, status, generated_at) VALUES (?, ?, ?, ?, NOW())');
            $insertPlan->execute([$title, $planDate ?: null, $strategy, 'generated']);
            $planId = (int)$pdo->lastInsertId();

            $insertSem = $pdo->prepare('INSERT INTO plan_semesters (plan_id, semester_id) VALUES (?, ?)');
            foreach ($semesterIds as $semId) {
                $insertSem->execute([$planId, $semId]);
            }

            $insertRoom = $pdo->prepare('INSERT INTO plan_rooms (plan_id, room_id) VALUES (?, ?)');
            foreach ($roomIds as $roomId) {
                $insertRoom->execute([$planId, $roomId]);
            }

            $insertSeat = $pdo->prepare('INSERT INTO seatings (plan_id, student_id, room_id, seat_row, seat_col) VALUES (?, ?, ?, ?, ?)');
            foreach ($assignments as $assignment) {
                $insertSeat->execute([
                    $planId,
                    $assignment['student_id'],
                    $assignment['room_id'],
                    $assignment['seat_row'],
                    $assignment['seat_col']
                ]);
            }

            $pdo->commit();

            echo json_encode([
                'success' => true,
                'plan' => [
                    'id' => $planId,
                    'title' => $title,
                    'plan_date' => $planDate,
                    'strategy' => $strategy,
                    'status' => 'generated',
                    'total_students' => count($assignments),
                    'total_rooms' => count($roomIds)
                ]
            ]);
        } catch (Exception $e) {
            $pdo->rollBack();
            respondError('Failed to generate plan: '.$e->getMessage(), 500);
        }
        break;

    case 'DELETE':
        if (!$id) respondError('No ID provided.');
        $stmt = $pdo->prepare('DELETE FROM plans WHERE id=?');
        $stmt->execute([$id]);
        echo json_encode(['success' => true]);
        break;

    default:
        respondError('Method not allowed', 405);
}
