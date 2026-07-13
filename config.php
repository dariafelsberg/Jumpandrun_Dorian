<?php
// config.php – Datenbankverbindung (SQLite) + Session
session_start();

$dbFile = __DIR__ . '/data/game.sqlite';
$dbExists = file_exists($dbFile);

try {
    $pdo = new PDO('sqlite:' . $dbFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec('PRAGMA foreign_keys = ON');

    // Tabellen anlegen, falls sie noch nicht existieren
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        email TEXT NOT NULL UNIQUE,
        username TEXT NOT NULL,
        password_hash TEXT NOT NULL,
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
        header('Location: login.php');
        exit;
    }
}
