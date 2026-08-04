# Galaxy Runner – mit Registrierung & Highscore

## Struktur

```
Galaxy_runner_DFu/
├── index.php           # Spiel (nur für eingeloggte Nutzer, ein Versuch pro Konto)
├── signin.php          # Registrierung + Login in einem: Vorname/Nachname/Email,
│                        # Email muss eindeutig sein → legt bei Erstbesuch automatisch ein Konto an
├── logout.php          # Logout
├── highscore.php        # Highscore-Seite (bester Score pro Nutzer, aus DB)
├── view-db.php          # DB-Viewer: listet alle Nutzer + deren besten Score
├── export.php           # Erzeugt ein PDF der Datenbank (eigener Mini-PDF-Generator, ohne Composer/TCPDF)
├── reset.php             # Setzt die Datenbank zurück (löscht alle Nutzer & Scores), schlüsselgeschützt
├── config.php            # DB-Verbindung (SQLite) + Session-Setup + Tabellen-Erstellung
├── style.css              # Gesamtes CSS (Spiel + Auth + Highscore + DB-Viewer)
├── game.js                # Gesamte Spiellogik (JS)
├── api/
│   └── save_score.php     # Nimmt Score/Wave per fetch() entgegen und speichert sie (ein Versuch pro Nutzer)
└── data/
    ├── game.sqlite         # SQLite-Datenbank (wird beim ersten Aufruf automatisch erstellt)
    ├── sessions/            # Eigener Session-Speicherort (falls System-Pfad nicht beschreibbar ist)
    └── .htaccess             # blockiert direkten Zugriff auf den gesamten data/-Ordner
```

## Datenbank

SQLite, Datei liegt unter `data/game.sqlite`. Wird beim ersten Aufruf
automatisch mit folgenden Tabellen angelegt (`config.php`):

- **users**: `id, email (UNIQUE), vorname, nachname, created_at`
- **scores**: `id, user_id, score, wave, created_at`

Die Email-Adresse ist per `UNIQUE`-Constraint in der Datenbank UND per
Prüfung in `signin.php` geschützt – jede Email kann also nur einmal ein
Konto anlegen. `config.php` erkennt zudem ein altes Schema (aus einer
früheren Version mit `username`/`password_hash`) und legt die Tabellen
bei Bedarf automatisch neu an.

## Installation auf deinem Server (z.B. sbw.media)

1. Gesamten Ordner `Galaxy_runner_DFu/` per FTP/SFTP hochladen.
2. Sicherstellen, dass der `data/`-Ordner für den Webserver **beschreibbar**
   ist (chmod 755 oder 775, je nach Hosting).
3. PHP ≥ 7.4 mit aktivierter `pdo_sqlite`-Extension wird benötigt (bei den
   meisten Shared-Hosting-Paketen standardmässig aktiv).
4. Seite aufrufen → `signin.php` → Vorname, Nachname, Email eingeben
   (legt automatisch ein Konto an und loggt ein) → spielen unter
   `index.php` → Ergebnis erscheint in `highscore.php`.

## Ablauf

- Nicht eingeloggte Nutzer werden von `index.php` und `highscore.php`
  automatisch auf `signin.php` umgeleitet.
- Jeder Nutzer darf nur **einen Versuch** spielen – `index.php` prüft das
  vor dem Start, `api/save_score.php` blockt einen zweiten Speicherversuch
  serverseitig zusätzlich ab (403).
- Nach Game Over sendet `game.js` das Ergebnis (Score + Wave) per `fetch()`
  an `api/save_score.php`, welches es (an die Session gebunden) in die DB
  schreibt.
- Die Highscore-Seite zeigt pro Spieler den jeweils besten Score.

## Admin-Funktionen

- **`view-db.php`** – zeigt alle registrierten Nutzer mit bestem Score.
  Von dort aus verlinkt: Datenbank als PDF herunterladen (`export.php`)
  und Datenbank zurücksetzen (`reset.php`).
- **`reset.php`** – löscht alle Nutzer & Scores unwiderruflich. Geschützt
  durch einen Key in der URL (`reset.php?key=...`).
  ⚠️ Der Reset-Key steht aktuell fest im Code (`RESET_KEY` in
  `reset.php`) und ist auch in `view-db.php` fest verlinkt – vor dem
  produktiven Einsatz unbedingt ändern.
- **`export.php`** – erzeugt ein mehrseitiges A4-PDF der Datenbank ganz
  ohne externe Abhängigkeiten (eigener minimaler PDF-Writer inkl.
  Windows-1252-Konvertierung für Umlaute).

## Nächste Schritte (optional)

- Reset-Key aus dem Code lösen (z.B. Umgebungsvariable) statt hartkodiert
  in `reset.php` und `view-db.php`
- Zugriffsschutz auch für `view-db.php` und `export.php` (aktuell ohne
  Prüfung öffentlich erreichbar, sofern die URL bekannt ist)
- Pagination für die Highscore-Liste bei vielen Spielern