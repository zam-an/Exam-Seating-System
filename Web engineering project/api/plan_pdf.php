<?php
require_once __DIR__.'/config.php';

class SimplePDF
{
    private array $pages = [];
    private string $current = '';
    private float $width = 595.28;   // A4 width in points
    private float $height = 841.89;  // A4 height in points
    private float $margin = 40.0;
    private float $cursorY;
    private array $columnWidths = [100, 120, 260];

    public function __construct()
    {
        $this->cursorY = $this->height - $this->margin;
        $this->addPage();
    }

    private function escape(string $text): string
    {
        $map = ['\\' => '\\\\', '(' => '\\(', ')' => '\\)'];
        return strtr($text, $map);
    }

    private function ensureSpace(float $needed): void
    {
        if ($this->cursorY - $needed < $this->margin) {
            $this->addPage();
        }
    }

    public function addPage(): void
    {
        if ($this->current !== '') {
            $this->pages[] = $this->current;
        }
        $this->current = '';
        $this->cursorY = $this->height - $this->margin;
    }

    public function heading(string $text): void
    {
        $this->ensureSpace(24);
        $this->writeLine($text, 16, true);
        $this->cursorY -= 6;
    }

    public function subHeading(string $text): void
    {
        $this->ensureSpace(18);
        $this->writeLine($text, 13, true);
        $this->cursorY -= 2;
    }

    public function textLine(string $text, int $size = 11): void
    {
        $this->ensureSpace($size + 6);
        $this->writeLine($text, $size, false);
        $this->cursorY -= 4;
    }

    private function writeLine(string $text, int $size, bool $bold): void
    {
        $font = $bold ? '/F2' : '/F1';
        $this->current .= sprintf(
            "BT %s %d Tf %.2f %.2f Td (%s) Tj ET\n",
            $font,
            $size,
            $this->margin,
            $this->cursorY,
            $this->escape($text)
        );
        $this->cursorY -= ($size + 4);
    }

    public function table(array $rows): void
    {
        if (empty($rows)) {
            $this->textLine('No seat assignments for this room.');
            return;
        }
        $this->ensureSpace(20);
        $this->writeRow(['Seat', 'Roll No', 'Student'], true);
        foreach ($rows as $row) {
            $this->writeRow($row, false);
        }
        $this->cursorY -= 6;
    }

    private function writeRow(array $columns, bool $bold): void
    {
        $this->ensureSpace(14);
        $font = $bold ? '/F2' : '/F1';
        $y = $this->cursorY;
        $x = $this->margin;
        foreach ($columns as $index => $value) {
            $width = $this->columnWidths[$index] ?? end($this->columnWidths);
            $this->current .= sprintf(
                "BT %s 10 Tf %.2f %.2f Td (%s) Tj ET\n",
                $font,
                $x,
                $y,
                $this->escape((string)$value)
            );
            $x += $width;
        }
        $this->cursorY -= 14;
    }

    public function output(string $filename): void
    {
        if ($this->current !== '') {
            $this->pages[] = $this->current;
            $this->current = '';
        }
        if (empty($this->pages)) {
            $this->addPage();
        }

        $objects = [];
        // Font objects
        $objects[] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';
        $objects[] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>';

        $contentIds = [];
        foreach ($this->pages as $content) {
            $contentIds[] = count($objects) + 1;
            $objects[] = sprintf("<< /Length %d >>\nstream\n%s\nendstream", strlen($content), $content);
        }

        $numPages = count($this->pages);
        $pageIds = [];
        $pagesTreeId = 2 + (2 * $numPages) + 1;

        foreach ($contentIds as $contentId) {
            $pageIds[] = count($objects) + 1;
            $objects[] = sprintf(
                "<< /Type /Page /Parent %d 0 R /MediaBox [0 0 %.2f %.2f] /Resources << /Font << /F1 1 0 R /F2 2 0 R >> >> /Contents %d 0 R >>",
                $pagesTreeId,
                $this->width,
                $this->height,
                $contentId
            );
        }

        $kids = implode(' ', array_map(fn($id) => $id.' 0 R', $pageIds));
        $objects[] = "<< /Type /Pages /Kids [ $kids ] /Count $numPages >>";
        $catalogId = count($objects) + 1;
        $objects[] = "<< /Type /Catalog /Pages $pagesTreeId 0 R >>";
        $infoId = count($objects) + 1;
        $objects[] = '<< /Producer (Exam Seating System) >>';

        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($objects as $index => $obj) {
            $offsets[$index + 1] = strlen($pdf);
            $pdf .= ($index + 1)." 0 obj\n".$obj."\nendobj\n";
        }

        $xref = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objects) + 1)."\n0000000000 65535 f \n";
        for ($i = 1; $i <= count($objects); $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }
        $pdf .= "trailer\n<< /Size ".(count($objects) + 1)." /Root $catalogId 0 R /Info $infoId 0 R >>\nstartxref\n$xref\n%%EOF";

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="'.$filename.'"');
        header('Content-Length: '.strlen($pdf));
        echo $pdf;
        exit;
    }
}

$planId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($planId <= 0) {
    http_response_code(400);
    echo 'Missing plan id.';
    exit;
}

$planStmt = $pdo->prepare('SELECT * FROM plans WHERE id = ?');
$planStmt->execute([$planId]);
$plan = $planStmt->fetch();
if (!$plan) {
    http_response_code(404);
    echo 'Plan not found.';
    exit;
}

$semStmt = $pdo->prepare("
    SELECT s.title
    FROM plan_semesters ps
    JOIN semesters s ON s.id = ps.semester_id
    WHERE ps.plan_id = ?
    ORDER BY s.title
");
$semStmt->execute([$planId]);
$semesterNames = array_column($semStmt->fetchAll(), 'title');

$seatStmt = $pdo->prepare("
    SELECT seatings.room_id, rooms.name AS room_name, rooms.code AS room_code,
           seatings.seat_row, seatings.seat_col,
           students.roll_no, students.full_name
    FROM seatings
    JOIN rooms ON rooms.id = seatings.room_id
    JOIN students ON students.id = seatings.student_id
    WHERE seatings.plan_id = ?
    ORDER BY rooms.name, seatings.seat_row, seatings.seat_col
");
$seatStmt->execute([$planId]);
$rows = $seatStmt->fetchAll();

if (empty($rows)) {
    http_response_code(404);
    echo 'No seat assignments were generated for this plan.';
    exit;
}

$grouped = [];
foreach ($rows as $row) {
    $roomId = $row['room_id'];
    if (!isset($grouped[$roomId])) {
        $grouped[$roomId] = [
            'name' => $row['room_name'],
            'code' => $row['room_code'],
            'seats' => []
        ];
    }
    $seatLabel = sprintf('Row %d / Col %d', $row['seat_row'], $row['seat_col']);
    $grouped[$roomId]['seats'][] = [$seatLabel, $row['roll_no'], $row['full_name']];
}

$pdf = new SimplePDF();
$pdf->heading('Exam Seating Plan: '.$plan['title']);
$pdf->textLine('Plan Date: '.($plan['plan_date'] ?: 'Not set'));
$pdf->textLine('Strategy: '.ucfirst(str_replace('-', ' ', $plan['strategy'] ?? 'round-robin')));
$pdf->textLine('Semesters: '.(!empty($semesterNames) ? implode(', ', $semesterNames) : 'Not specified'));
$pdf->textLine('Generated: '.($plan['generated_at'] ?? $plan['created_at']));
$pdf->textLine('');

foreach ($grouped as $room) {
    $pdf->subHeading('Room: '.$room['name'].' ('.$room['code'].')');
    $pdf->table($room['seats']);
}

$safeTitle = preg_replace('/[^a-zA-Z0-9_-]+/', '-', strtolower($plan['title']));
$filename = 'seating-plan-'.$planId.'-'.$safeTitle.'.pdf';
$pdf->output($filename);

