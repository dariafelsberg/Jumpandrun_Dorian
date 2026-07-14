<?php
require_once __DIR__ . '/config.php';
requireLogin();

// Prüfen, ob der Nutzer bereits einen Versuch gespielt hat
$stmt = $pdo->prepare('SELECT COUNT(*) FROM scores WHERE user_id = ?');
$stmt->execute([$_SESSION['user_id']]);
$alreadyPlayed = (int)$stmt->fetchColumn() > 0;
?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<title>Galaxy Runner</title>
<link rel="stylesheet" href="style.css">
</head>
<body>

<div id="topbar">
    <div>Eingeloggt als <strong><?= htmlspecialchars($_SESSION['username']) ?></strong></div>
    <div class="links">
        <a href="highscore.php">Highscore</a>
        <a href="logout.php">Logout</a>
    </div>
</div>

<div id="game-container">
    <?php if ($alreadyPlayed): ?>
        <div id="center-overlay" style="display:flex;">
            <h1>Galaxy Runner</h1>
            <p>Du hast deinen Versuch bereits gespielt. Jeder Account darf nur einmal antreten.</p>
            <a class="btn" href="highscore.php">Highscore ansehen</a>
        </div>
    <?php else: ?>
        <canvas id="game"></canvas>

        <div id="ui">
            <div class="panel">
                HP: <span id="hp">100</span>
                · Score: <span id="score">0</span>
                · Welle: <span id="wave">1</span>
            </div>
            <div class="panel">
                Laser: <span id="ammo">∞</span>
            </div>
        </div>

        <div id="center-overlay">
            <h1>Galaxy Runner</h1>
            <p>Steuere dein Schiff mit WASD, ziele mit der Maus und schieße mit Linksklick.</p>
            <p style="font-size:12px;color:#9fd8ff;">Achtung: Du hast nur einen Versuch!</p>
            <button class="btn" id="start-btn">Start</button>
        </div>
    <?php endif; ?>
</div>

<?php if (!$alreadyPlayed): ?>
<script src="game.js"></script>
<?php endif; ?>
</body>
</html>