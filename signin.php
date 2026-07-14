<?php
require_once __DIR__ . '/config.php';

if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vorname  = trim($_POST['vorname'] ?? '');
    $nachname = trim($_POST['nachname'] ?? '');
    $email    = trim($_POST['email'] ?? '');

    if ($vorname === '' || $nachname === '' || $email === '') {
        $error = 'Bitte Vorname, Nachname und Email eingeben.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Bitte eine gültige Email-Adresse eingeben.';
    } else {
        // Prüfen, ob diese Email bereits ein Konto hat
        $stmt = $pdo->prepare('SELECT id, vorname, nachname FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // Bekannte Email -> direkt einloggen
            $_SESSION['user_id']  = $user['id'];
            $_SESSION['username'] = $user['vorname'] . ' ' . $user['nachname'];
        } else {
            // Neue Email -> Konto automatisch anlegen und einloggen
            try {
                $stmt = $pdo->prepare('INSERT INTO users (email, vorname, nachname) VALUES (?, ?, ?)');
                $stmt->execute([$email, $vorname, $nachname]);
                $_SESSION['user_id']  = (int)$pdo->lastInsertId();
                $_SESSION['username'] = $vorname . ' ' . $nachname;
            } catch (PDOException $e) {
                // Falls die UNIQUE-Regel auf DB-Ebene greift (Race Condition)
                $error = 'Diese Email wird bereits verwendet. Bitte erneut versuchen.';
            }
        }

        if ($error === '') {
            header('Location: index.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<title>Anmelden – Galaxy Runner</title>
<link rel="stylesheet" href="style.css">
</head>
<body>

<div class="auth-card">
    <h1>Anmelden</h1>
    <p style="text-align:center;color:#9fd8ff;font-size:13px;margin-top:-8px;">
        Neu hier? Einfach ausfüllen – dein Konto wird automatisch erstellt.
    </p>

    <?php if ($error): ?>
        <div class="error-box"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post" action="signin.php">
        <div class="field">
            <label for="vorname">Vorname</label>
            <input type="text" id="vorname" name="vorname" value="<?= htmlspecialchars($_POST['vorname'] ?? '') ?>" required>
        </div>
        <div class="field">
            <label for="nachname">Nachname</label>
            <input type="text" id="nachname" name="nachname" value="<?= htmlspecialchars($_POST['nachname'] ?? '') ?>" required>
        </div>
        <div class="field">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
        </div>
        <button type="submit" class="btn">Los geht's</button>
    </form>
</div>

</body>
</html>