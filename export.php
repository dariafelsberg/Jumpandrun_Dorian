<?php
require_once __DIR__ . '/config.php';

// Sicherstellen, dass alle offenen Schreibvorgänge abgeschlossen sind
$pdo = null;

if (!file_exists($dbFile)) {
    http_response_code(404);
    die('Datenbankdatei nicht gefunden.');
}

$filename = 'game_' . date('Y-m-d_His') . '.sqlite';

header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . filesize($dbFile));
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

readfile($dbFile);
exit;