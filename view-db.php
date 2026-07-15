<?php
require_once __DIR__ . '/config.php';

$key = $_GET['key'] ?? '';

$users  = $pdo->query('
    SELECT u.id, u.email, u.vorname, u.nachname, u.created_at,
           MAX(s.score) AS best_score
    FROM users u
    LEFT JOIN scores s ON s.user_id = u.id
    GROUP BY u.id
    ORDER BY (best_score IS NULL) ASC, best_score DESC
')->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<title>DB Viewer – Galaxy Runner</title>
<link rel="stylesheet" href="style.css">
<style>
    body { padding: 30px; }
    .db-wrap { max-width: 900px; margin: 0 auto; }
    .db-card {
        background: radial-gradient(circle at center, #0a0f2b, #03040a);
        border-radius: 10px;
        box-shadow: 0 0 20px rgba(0,0,0,0.6);
        padding: 20px 24px;
        margin-bottom: 24px;
        overflow-x: auto;
    }
    .db-card h2 { margin-top: 0; }
    table.db { width: 100%; border-collapse: collapse; font-size: 13px; }
    table.db th, table.db td { padding: 6px 10px; border-bottom: 1px solid rgba(255,255,255,0.1); text-align: left; white-space: nowrap; }
    table.db th { color: #00eaff; }
    table.db th.col-rank, table.db td.col-rank { background: rgba(255,255,255,0.06); }
    .toolbar { text-align: right; margin-bottom: 12px; }
</style>
</head>
<body>

<div class="db-wrap">
    <div class="toolbar">
        <a class="btn" href="export.php">Datenbank als PDF herunterladen</a>
        <a class="btn" style="background:linear-gradient(135deg,#ff5050,#b00020);" href="reset.php?key=galaxy-admin-2026">Zurücksetzen</a>
    </div>

    <div class="db-card">
        <h2>Users (<?= count($users) ?>)</h2>
        <table class="db">
            <thead>
                <tr><th class="col-rank">#</th><th>ID</th><th>Email</th><th>Vorname</th><th>Nachname</th><th>Bester Score</th><th>Erstellt am</th></tr>
            </thead>
            <tbody>
                <?php foreach ($users as $i => $u): ?>
                <tr>
                    <td class="col-rank"><?= $i + 1 ?></td>
                    <td><?= (int)$u['id'] ?></td>
                    <td><?= htmlspecialchars($u['email']) ?></td>
                    <td><?= htmlspecialchars($u['vorname']) ?></td>
                    <td><?= htmlspecialchars($u['nachname']) ?></td>
                    <td><?= $u['best_score'] !== null ? (int)$u['best_score'] : '–' ?></td>
                    <td><?= htmlspecialchars($u['created_at']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

</div>

</body>
</html>