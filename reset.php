<?php
require_once __DIR__ . '/config.php';

// ⚠️ Bitte diesen Schlüssel ändern, bevor du live gehst!
define('RESET_KEY', 'galaxy-admin-2026');

$key = $_GET['key'] ?? $_POST['key'] ?? '';

if ($key !== RESET_KEY) {
    http_response_code(403);
    die('Kein Zugriff. Aufruf mit: reset.php?key=DEIN_SCHLÜSSEL');
}

$done = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm'])) {
    $pdo->exec('DELETE FROM scores');
    $pdo->exec('DELETE FROM users');
    // Auto-Increment-Zähler zurücksetzen (optional, rein kosmetisch)
    $pdo->exec("DELETE FROM sqlite_sequence WHERE name IN ('users','scores')");
    $done = true;
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<title>Reset – Galaxy Runner</title>
<link rel="stylesheet" href="style.css">
</head>
<body>

<div class="auth-card">
    <h1>⚠️ Datenbank zurücksetzen</h1>

    <?php if ($done): ?>
        <p style="text-align:center;">Alle Konten und Highscores wurden gelöscht.</p>
        <div style="text-align:center;margin-top:16px;">
            <a class="btn" href="signin.php">Zur Anmeldung</a>
        </div>
    <?php else: ?>
        <p style="text-align:center;color:#ff8080;">
            Das löscht ALLE Konten und Highscores unwiderruflich. Bist du sicher?
        </p>
        <form method="post" style="text-align:center;">
            <input type="hidden" name="key" value="<?= htmlspecialchars($key) ?>">
            <button type="submit" name="confirm" value="1" class="btn">Ja, alles löschen</button>
        </form>
    <?php endif; ?>
</div>

</body>
</html>