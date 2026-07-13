<?php
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Nicht eingeloggt.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$score = isset($data['score']) ? (int)$data['score'] : null;
$wave  = isset($data['wave'])  ? (int)$data['wave']  : null;

if ($score === null || $wave === null || $score < 0 || $wave < 1) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Ungültige Daten.']);
    exit;
}

try {
    $stmt = $pdo->prepare('INSERT INTO scores (user_id, score, wave) VALUES (?, ?, ?)');
    $stmt->execute([$_SESSION['user_id'], $score, $wave]);
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Datenbankfehler.']);
}
