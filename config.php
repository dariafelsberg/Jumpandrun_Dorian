<?php
// config.php – Datenbankverbindung (SQLite) + Session

// Sessions im eigenen, garantiert beschreibbaren Ordner speichern
// (verhindert Session-Verlust auf Shared-Hosting mit restriktivem System-Sessionpfad)
$sessionDir = __DIR__ . '/data/sessions';
if (!is_dir($sessionDir)) {
    @mkdir($sessionDir, 0775, true);
}
if (is_dir($sessionDir) && is_writable($sessionDir)) {
    session_save_path($sessionDir);
}
session_start();

$dbFile = __DIR__ . '/data/game.sqlite';
$dbExists = file_exists($dbFile);

try {
    $pdo = new PDO('sqlite:' . $dbFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec('PRAGMA foreign_keys = ON');

    // Prüfen, ob eine "users"-Tabelle mit dem ALTEN Schema existiert
    // (aus einer früheren Version mit username/password_hash statt vorname/nachname).
    // Falls ja: alte Tabellen entfernen, damit sie unten sauber neu angelegt werden.
    $existingCols = [];
    $tableCheck = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='users'")->fetch();
    if ($tableCheck) {
        $existingCols = array_column($pdo->query("PRAGMA table_info(users)")->fetchAll(PDO::FETCH_ASSOC), 'name');
    }
    if ($tableCheck && !in_array('vorname', $existingCols, true)) {
        $pdo->exec('DROP TABLE IF EXISTS scores');
        $pdo->exec('DROP TABLE IF EXISTS users');
    }

    // Tabellen anlegen, falls sie noch nicht existieren
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        email TEXT NOT NULL UNIQUE,
        vorname TEXT NOT NULL,
        nachname TEXT NOT NULL,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS scores (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        score INTEGER NOT NULL,
        wave INTEGER NOT NULL,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");
} catch (PDOException $e) {
    http_response_code(500);
    die('Datenbankfehler: ' . htmlspecialchars($e->getMessage()));
}

// Hilfsfunktion: ist der Nutzer eingeloggt?
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

// Hilfsfunktion: zwingt Login, sonst Redirect
function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: signin.php');
        exit;
    }
}