<?php
require_once __DIR__ . '/config.php';
requireLogin();

// Bester Score pro Nutzer, absteigend sortiert (Score UND Welle stammen aus demselben Run)
$stmt = $pdo->query("
    SELECT u.vorname, u.nachname, s.score AS best_score, s.wave AS best_wave
    FROM scores s
    JOIN users u ON u.id = s.user_id
    WHERE s.id IN (
        SELECT s2.id FROM scores s2
        WHERE s2.user_id = s.user_id
        ORDER BY s2.score DESC, s2.id ASC
        LIMIT 1
    )
    ORDER BY best_score DESC
    LIMIT 20
");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<title>Highscore – Galaxy Runner</title>
<link rel="stylesheet" href="style.css">
</head>
<body>

<div id="user-header">
    Eingeloggt als <strong><?= htmlspecialchars($_SESSION['username']) ?></strong>
</div>

<div id="highscore-card">
    <h1>🏆 Highscore</h1>

    <?php if (empty($rows)): ?>
        <p style="text-align:center;color:#C9E28F;">Noch keine Einträge vorhanden. Spiel eine Runde!</p>
    <?php else: ?>
        <div class="table-scroll">
        <table class="scores">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Spieler</th>
                    <th>Score</th>
                    <th>Welle</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $i => $row): ?>
                    <tr class="<?= ($row['vorname'] . ' ' . $row['nachname']) === $_SESSION['username'] ? 'me' : '' ?>">
                        <td class="rank"><?= $i + 1 ?></td>
                        <td><?= htmlspecialchars($row['vorname'] . ' ' . $row['nachname']) ?></td>
                        <td><?= (int)$row['best_score'] ?></td>
                        <td><?= (int)$row['best_wave'] ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    <?php endif; ?>

    <div style="text-align:center;">
        <a class="btn" href="logout.php">Zurück zum Spiel</a>
    </div>
</div>

</body>
</html>