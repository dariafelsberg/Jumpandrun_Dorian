<?php
require_once __DIR__ . '/config.php';

if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? '';

    if ($username === '' || $email === '' || $password === '') {
        $error = 'Bitte alle Felder ausfüllen.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Bitte eine gültige Email-Adresse eingeben.';
    } elseif (strlen($password) < 6) {
        $error = 'Das Passwort muss mindestens 6 Zeichen lang sein.';
    } elseif ($password !== $password2) {
        $error = 'Die Passwörter stimmen nicht überein.';
    } else {
        // Prüfen, ob die Email bereits verwendet wird
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'Diese Email-Adresse wird bereits verwendet.';
        } else {
            try {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare('INSERT INTO users (email, username, password_hash) VALUES (?, ?, ?)');
                $stmt->execute([$email, $username, $hash]);
                $success = true;
            } catch (PDOException $e) {
                // Falls die UNIQUE-Regel auf DB-Ebene greift (Race Condition)
                $error = 'Diese Email-Adresse wird bereits verwendet.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<title>Registrieren – Galaxy Runner</title>
<link rel="stylesheet" href="style.css">
</head>
<body>

<div class="auth-card">
    <h1>Registrieren</h1>

    <?php if ($error): ?>
        <div class="error-box"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="success-box">Konto erstellt! Du kannst dich jetzt <a href="login.php">einloggen</a>.</div>
    <?php else: ?>
        <form method="post" action="register.php">
            <div class="field">
                <label for="username">Benutzername</label>
                <input type="text" id="username" name="username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
            </div>
            <div class="field">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
            </div>
            <div class="field">
                <label for="password">Passwort</label>
                <input type="password" id="password" name="password" required minlength="6">
            </div>
            <div class="field">
                <label for="password2">Passwort bestätigen</label>
                <input type="password" id="password2" name="password2" required minlength="6">
            </div>
            <button type="submit" class="btn">Konto erstellen</button>
        </form>
    <?php endif; ?>

    <div class="auth-switch">
        Bereits registriert? <a href="login.php">Zum Login</a>
    </div>
</div>

</body>
</html>
