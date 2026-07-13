# Galaxy Runner – mit Login & Highscore

## Struktur

```
galaxy-runner/
├── index.php          # Spiel (nur für eingeloggte Nutzer)
├── login.php          # Login-Seite
├── register.php       # Registrierung (Email muss eindeutig sein)
├── logout.php         # Logout
├── highscore.php       # Highscore-Seite (eigene Seite, aus DB)
├── config.php         # DB-Verbindung + Session + Tabellen-Erstellung
├── style.css           # Gesamtes CSS (Spiel + Auth + Highscore)
├── game.js             # Gesamte Spiellogik (JS)
├── api/
│   └── save_score.php  # Nimmt Score per fetch() entgegen und speichert ihn
└── data/
    ├── game.sqlite      # SQLite-Datenbank (wird automatisch erstellt)
    └── .htaccess         # blockiert direkten Zugriff auf die DB-Datei
```

## Datenbank

SQLite, Datei liegt unter `data/game.sqlite`. Wird beim ersten Aufruf
automatisch mit folgenden Tabellen angelegt (`config.php`):

- **users**: `id, email (UNIQUE), username, password_hash, created_at`
- **scores**: `id, user_id, score, wave, created_at`

Die Email-Adresse ist per `UNIQUE`-Constraint in der Datenbank UND per
Prüfung im `register.php`-Formular geschützt – jede Email kann also nur
einmal registriert werden.

## Installation auf deinem Server (z.B. sbw.media)

1. Gesamten Ordner `galaxy-runner/` per FTP/SFTP hochladen.
2. Sicherstellen, dass der `data/`-Ordner für den Webserver **beschreibbar**
   ist (chmod 755 oder 775, je nach Hosting).
3. PHP ≥ 7.4 mit aktivierter `pdo_sqlite`-Extension wird benötigt (bei den
   meisten Shared-Hosting-Paketen standardmässig aktiv).
4. Seite aufrufen → `register.php` → Konto erstellen → `login.php` →
   spielen unter `index.php` → Ergebnis erscheint in `highscore.php`.

## Ablauf

- Nicht eingeloggte Nutzer werden von `index.php` und `highscore.php`
  automatisch auf `login.php` umgeleitet.
- Nach jedem Game Over sendet `game.js` das Ergebnis per `fetch()` an
  `api/save_score.php`, welches es (an die Session gebunden) in die DB
  schreibt.
- Die Highscore-Seite zeigt pro Spieler den jeweils besten Score.

## Nächste Schritte (optional)

- Passwort-Reset-Funktion
- Rate-Limiting / Lockout gegen Brute-Force beim Login (wie bei deinem
  Spendly-Projekt)
- Pagination für die Highscore-Liste bei vielen Spielern
