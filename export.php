<?php
require_once __DIR__ . '/config.php';

/**
 * Minimaler PDF-Generator ohne externe Abhängigkeiten (kein Composer/TCPDF nötig).
 * Erzeugt ein mehrseitiges A4-PDF mit einer einfachen Tabelle.
 */

function pdf_escape_text(string $s): string {
    // In Windows-1252 (WinAnsiEncoding) konvertieren, damit Umlaute korrekt dargestellt werden
    if (function_exists('iconv')) {
        $converted = @iconv('UTF-8', 'Windows-1252//TRANSLIT', $s);
        $s = $converted !== false ? $converted : $s;
    } elseif (function_exists('mb_convert_encoding')) {
        $s = mb_convert_encoding($s, 'Windows-1252', 'UTF-8');
    }
    $s = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $s);
    return $s;
}

function pdf_strlen(string $s): int {
    return function_exists('mb_strlen') ? mb_strlen($s, 'UTF-8') : strlen($s);
}

function pdf_substr(string $s, int $start, int $length): string {
    return function_exists('mb_substr') ? mb_substr($s, $start, $length, 'UTF-8') : substr($s, $start, $length);
}

function pdf_truncate(string $s, int $maxChars): string {
    if (pdf_strlen($s) > $maxChars) {
        return pdf_substr($s, 0, $maxChars - 1) . '...';
    }
    return $s;
}

function build_table_pdf(string $title, array $columns, array $rows): string {
    $pageWidth   = 595.28; // A4 in pt
    $pageHeight  = 841.89;
    $marginX     = 40;
    $marginTop   = 56;
    $marginBottom = 40;
    $rowHeight   = 16;
    $titleFontSize = 16;
    $tableFontSize = 9;

    $usableWidth = $pageWidth - 2 * $marginX;
    $totalWidth = array_sum(array_column($columns, 'width'));
    if ($totalWidth <= 0) $totalWidth = 1;
    $scale = $usableWidth / $totalWidth;
    foreach ($columns as &$c) { $c['width'] = $c['width'] * $scale; }
    unset($c);

    $pages = [];
    $pageIndex = 0;
    $y = 0;

    $startPage = function () use (&$pages, &$pageIndex, $pageHeight, $marginTop, $marginX, $columns, $title, $titleFontSize, $tableFontSize) {
        $pageIndex++;
        $lines = [];
        $y = $pageHeight - $marginTop;

        $lines[] = sprintf("BT /F1 %d Tf %.2f %.2f Td (%s) Tj ET", $titleFontSize, $marginX, $y, pdf_escape_text($title));
        $y -= 24;

        $lines[] = "BT /F1 " . $tableFontSize . " Tf";
        $x = $marginX;
        foreach ($columns as $c) {
            $lines[] = sprintf("1 0 0 1 %.2f %.2f Tm (%s) Tj", $x, $y, pdf_escape_text($c['label']));
            $x += $c['width'];
        }
        $lines[] = "ET";
        $y -= 5;

        $tableWidth = array_sum(array_column($columns, 'width'));
        $lines[] = sprintf("0.6 w %.2f %.2f m %.2f %.2f l S", $marginX, $y, $marginX + $tableWidth, $y);
        $y -= 14;

        $pages[$pageIndex] = ['lines' => $lines, 'y' => $y];
    };

    $startPage();

    foreach ($rows as $row) {
        if ($pages[$pageIndex]['y'] < $marginBottom + $rowHeight) {
            $startPage();
        }
        $y = $pages[$pageIndex]['y'];
        $x = $marginX;
        $pages[$pageIndex]['lines'][] = "BT /F1 " . $tableFontSize . " Tf";
        foreach ($columns as $i => $c) {
            $val = (string)($row[$i] ?? '');
            $maxChars = max(3, (int)($c['width'] / ($tableFontSize * 0.52)));
            $val = pdf_truncate($val, $maxChars);
            $pages[$pageIndex]['lines'][] = sprintf("1 0 0 1 %.2f %.2f Tm (%s) Tj", $x, $y, pdf_escape_text($val));
            $x += $c['width'];
        }
        $pages[$pageIndex]['lines'][] = "ET";
        $pages[$pageIndex]['y'] -= $rowHeight;
    }

    // ---- PDF-Objekte zusammenbauen ----
    $objects = [];
    $numPages = count($pages);

    $fontObjNum = 3;
    $pagesObjNum = 2;
    $firstFreePageObj = 4; // Seiten- und Content-Objekte ab hier, je 2 pro Seite

    $kids = [];
    $pageObjNums = [];
    for ($i = 1; $i <= $numPages; $i++) {
        $pageObjNums[$i] = $firstFreePageObj + ($i - 1) * 2;
        $kids[] = $pageObjNums[$i] . ' 0 R';
    }

    $objects[1] = "<< /Type /Catalog /Pages {$pagesObjNum} 0 R >>";
    $objects[$pagesObjNum] = "<< /Type /Pages /Kids [" . implode(' ', $kids) . "] /Count {$numPages} >>";
    $objects[$fontObjNum] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>";

    foreach ($pages as $i => $page) {
        $pageObjNum = $pageObjNums[$i];
        $contentObjNum = $pageObjNum + 1;
        $stream = implode("\n", $page['lines']);
        $streamLen = strlen($stream);

        $objects[$pageObjNum] = "<< /Type /Page /Parent {$pagesObjNum} 0 R /MediaBox [0 0 {$pageWidth} {$pageHeight}] "
            . "/Resources << /Font << /F1 {$fontObjNum} 0 R >> >> /Contents {$contentObjNum} 0 R >>";
        $objects[$contentObjNum] = "<< /Length {$streamLen} >>\nstream\n{$stream}\nendstream";
    }

    ksort($objects);

    $pdf = "%PDF-1.4\n";
    $offsets = [];
    foreach ($objects as $num => $body) {
        $offsets[$num] = strlen($pdf);
        $pdf .= "{$num} 0 obj\n{$body}\nendobj\n";
    }

    $xrefStart = strlen($pdf);
    $maxObjNum = max(array_keys($objects));
    $pdf .= "xref\n0 " . ($maxObjNum + 1) . "\n";
    $pdf .= "0000000000 65535 f \n";
    for ($n = 1; $n <= $maxObjNum; $n++) {
        if (isset($offsets[$n])) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$n]);
        } else {
            $pdf .= "0000000000 00000 f \n";
        }
    }

    $pdf .= "trailer\n<< /Size " . ($maxObjNum + 1) . " /Root 1 0 R >>\n";
    $pdf .= "startxref\n{$xrefStart}\n%%EOF";

    return $pdf;
}

// ---- Daten aus der Datenbank laden ----
$users = $pdo->query('
    SELECT u.id, u.email, u.vorname, u.nachname, u.created_at,
           MAX(s.score) AS best_score
    FROM users u
    LEFT JOIN scores s ON s.user_id = u.id
    GROUP BY u.id
    ORDER BY (best_score IS NULL) ASC, best_score DESC
')->fetchAll(PDO::FETCH_ASSOC);

$columns = [
    ['label' => '#',            'width' => 24],
    ['label' => 'ID',           'width' => 30],
    ['label' => 'Email',        'width' => 150],
    ['label' => 'Vorname',      'width' => 80],
    ['label' => 'Nachname',     'width' => 80],
    ['label' => 'Bester Score', 'width' => 65],
    ['label' => 'Erstellt am',  'width' => 90],
];

$rows = [];
foreach ($users as $i => $u) {
    $rows[] = [
        $i + 1,
        (int)$u['id'],
        $u['email'],
        $u['vorname'],
        $u['nachname'],
        $u['best_score'] !== null ? (int)$u['best_score'] : '–',
        $u['created_at'],
    ];
}

$title = 'Galaxy Runner – Datenbank-Export (' . date('d.m.Y H:i') . ')';
$pdfContent = build_table_pdf($title, $columns, $rows);

$filename = 'galaxy-runner-db_' . date('Y-m-d_His') . '.pdf';

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . strlen($pdfContent));
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

echo $pdfContent;
exit;