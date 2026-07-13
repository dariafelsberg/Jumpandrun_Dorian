<?php
require_once __DIR__ . '/config.php';
requireLogin();
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
        <button class="btn" id="start-btn">Start</button>
    </div>
</div>

<script src="game.js"></script>
</body>
</html>
