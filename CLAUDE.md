# Gyro-PHP Framework – Projektanalyse & Memory

> Letzte Aktualisierung: 2026-03-05

## Projektübersicht

- **Framework:** Gyro-PHP, eigenes PHP-Webframework (seit 2004, PHP 4 → PHP 5 Rewrite 2005)
- **Aktueller Stand:** Läuft auf PHP 8.x mit Safeguards, Code-Stil ist PHP 5.x Ära
- **Kein Composer**, kein PSR-4, kein Namespace-System
- **Test-Framework:** SimpleTest 1.1.0 (abandoned seit 2012)
- **Kein modernes Dependency Management**

## Verzeichnisstruktur

```
gyro/                          # Framework-Core
  core/
    config.cls.php             # Zentrale Config (281 Zeilen, 100+ Konstanten)
    start.php                  # Bootstrap/Entry Point
    controller/base/           # Basis-Controller & Routing
    model/base/                # DB-Abstraktionsschicht
    model/drivers/mysql/       # MySQL-Driver (nur mysqli_real_escape_string)
    lib/components/            # Core-Komponenten (Logger, HTTP, etc.)
    lib/helpers/               # Hilfsklassen (String, Array, Cast, etc.)
    lib/interfaces/            # Interface-Definitionen
    view/base/                 # View-Layer
  modules/                     # Framework-Module
    simpletest/                # Test-Framework + Tests
    cache.*/                   # Cache-Backends (memcache, xcache, acpu, file, mysql)
    mime/, json/, mail/, etc.  # Diverse Module
contributions/                 # Erweiterungen/Plugins (60+ Module)
  usermanagement/              # User-Verwaltung (bcrypt Default seit Phase 1)
  lib.geocalc/                 # Geo-Berechnungen
  scheduler/, gsitemap/, etc.  # Diverse Beiträge
```

## Statistiken

| Metrik | Wert |
|--------|------|
| Core-Klassen | 239 (.cls.php, .model.php, .facade.php) |
| Test-Dateien | 57 (50 Core + 7 Contributions) |
| Testabdeckung | ~20% (selektiv, nicht umfassend) |
| PHPDoc-Abdeckung | ~15-20% |
| TODO/FIXME/HACK | 14 Marker |
| Contributions | 60+ Module |

## Sicherheitsprobleme

### ✅ GEFIXT: Passwort-Hashing
- Default von MD5/PHPass auf **bcrypt** umgestellt (`password_hash(PASSWORD_BCRYPT, cost 12)`)
- Neuer Hash-Algorithmus: `contributions/usermanagement/behaviour/commands/users/hashes/bcryp.hash.php`
- Timing-safe Vergleiche in MD5/SHA1 Klassen (`hash_equals()`)
- Auto-Upgrade: Alte Hashes werden beim nächsten Login automatisch migriert

### ✅ GEFIXT: HTTP Security Headers
- X-Content-Type-Options, X-Frame-Options, Referrer-Policy, Permissions-Policy
- Gesetzt in `pageviewbase.cls.php` mit `override=false`

### OFFEN: Keine Prepared Statements (KRITISCH)
- **Datei:** `gyro/core/model/drivers/mysql/dbdriver.mysql.php`
- Nutzt nur `mysqli_real_escape_string()` – keine parametrisierten Queries
- **Fix:** Migration auf PDO mit Prepared Statements

### ✅ GEFIXT: Session-Konfiguration
- `httponly`, `secure` (bei HTTPS), `samesite=Lax` auf Session-Cookies konfiguriert

## ✅ PHP 8.x Kompatibilität (GEFIXT)

- `common.cls.php`: `preprocess_input()` → No-op (Magic Quotes seit PHP 7.4 weg)
- `start.php`: `E_ALL | E_STRICT` → `E_ALL`, PHP 5.3 Compat-Check entfernt
- `cast.cls.php`: `isset($value->__toString)` → `method_exists($value, '__toString')`
- `mb_*` Funktionen: NULL-Parameter teilweise gefixt (bereits vor Phase 1)

## Architektur-Schwächen

### Kein Typ-System
- 0/239 Klassen haben Typ-Deklarationen (Parameter, Return, Properties)
- Kein Einsatz von PHP 7.4+ Typed Properties, Union Types, Enums etc.
- Beispiel: `public $component; public $version;` – keine `@var`/Typ-Annotations

### Kein Namespace-System
- Alle Klassen im globalen Namespace
- Namenskonventionen statt Namespaces: `DAO*`, `*Controller`, `*Facade`
- Eigenes Autoloading statt PSR-4

### Logger minimal (27 Zeilen)
- **Datei:** `gyro/core/lib/components/logger.cls.php`
- CSV-only, dateibasiert, kein PSR-3, keine Levels/Context
- Silent Failure bei Schreibfehlern (`@fopen`, `@fputcsv`, `@fclose`)

### Konfigurations-Schwächen
- Hardcoded Timeouts: `$timeout_sec = 30` (HTTP), `$max_age = 600` (Cache)
- Magic Numbers: Port 443 für HTTPS, ASCII-Codes `10`/`13`, Email-Limit `64`
- String-basierte Konstanten-Lookup (flexibel aber nicht typsicher)

## Veraltete/Tote Module

### Definitiv veraltet
- `cache.xcache` – XCache seit PHP 7 tot
- `cache.acpu` – APC deprecated, prüfen ob APCu gemeint
- SimpleTest 1.1.0 – abandoned seit 2012

### Potenziell ungenutzt
- `gyro/modules/phpinfo/` – Debug-Utility (51 Zeilen)
- `gyro/modules/doxygen.php` – Nur Doku-Definition (6 Zeilen)
- Mehrere CSS-Präprozessor-Module (`css.sass`, `css.yaml`, `css.postcss`)
- `javascript.cleditor`, `javascript.wymeditor` – abandoned JS-Editoren

## Modernisierungsplan (Phasen)

### Phase 1: Sicherheit & Lauffähigkeit (KRITISCH) ✅ ERLEDIGT
- [x] PHP 8.x Fatal Errors fixen (`get_magic_quotes_gpc`, `E_STRICT`, `isset(__toString)`)
- [x] Passwort-Hashing: MD5 → `password_hash()` mit bcrypt (neuer `bcryp` Hash-Algorithmus)
- [x] HTTP Security Headers einführen (X-Content-Type-Options, X-Frame-Options, Referrer-Policy, Permissions-Policy)
- [x] Timing-safe Vergleiche in MD5/SHA1 Hash-Klassen (`hash_equals()`)

#### Phase 1 Details
- `common.cls.php`: `preprocess_input()` → No-op, `transcribe()` entfernt (Magic Quotes seit PHP 7.4 weg)
- `start.php`: `E_ALL | E_STRICT` → `E_ALL`, `defined('E_DEPRECATED')` Check entfernt (PHP 5.3 Compat)
- `cast.cls.php`: `isset($value->__toString)` → `method_exists($value, '__toString')`
- Neuer Hash-Algorithmus: `contributions/usermanagement/behaviour/commands/users/hashes/bcryp.hash.php`
- Default Hash-Type: `'pas3p'` → `'bcryp'` in `start.inc.php` und `users.model.php`
- Auto-Upgrade: Bestehender Login-Code migriert alte Hashes automatisch beim nächsten Login
- Security Headers in `pageviewbase.cls.php` mit `override=false` (Apps können überschreiben)

### Phase 2: Infrastruktur ✅ ERLEDIGT
- [x] `composer.json` erstellen mit PHPUnit 10.5 als Dev-Dependency
- [x] PHPUnit Setup: `phpunit.xml.dist`, `tests/bootstrap.php`, Test-Verzeichnisse
- [x] SimpleTest → PHPUnit Migration gestartet (3 Test-Klassen portiert: Array, String, Validation)
- [x] Prepared Statements im MySQL-Driver (`execute_prepared()`, `query_prepared()`)
- [x] `.gitignore` um `/vendor/` erweitert

#### Phase 2 Details
- `composer.json`: PHPUnit 10.5, PHP >=8.0
- `tests/bootstrap.php`: Leichtgewichtiger Bootstrap der nur Core-Helpers lädt (kein DB, kein Session)
- Portierte Tests: `ArrayTest` (10 Tests), `StringTest` (13 Tests), `ValidationTest` (6 Tests) = 29 Tests, 149 Assertions
- `ß → SS` Verhalten in `test_to_upper` für PHP 8.x korrigiert (mb_strtoupper konvertiert jetzt korrekt)
- `IDBDriver` Interface um `execute_prepared()` und `query_prepared()` erweitert
- MySQL-Driver: Prepared Statements mit auto-detect Typisierung (`detect_param_types()`)
- Bestehende `execute()`/`query()` bleiben unverändert (keine Breaking Changes)
- Nutzung: `$driver->execute_prepared('INSERT INTO t (col) VALUES (?)', ['value'])`

### Phase 3: Sicherheit (Vertiefung) ✅ ERLEDIGT
- [x] Session-Security: `secure` Flag bei HTTPS, PHP < 7.3 Branch entfernt, `httponly=true` hardcoded
- [x] CSRF-Token-System: Bereits robust (random_bytes, Session-gebunden, DB-gestützt, Einmal-Tokens)
- [x] CSRF: `==` → `===` in FormHandler::validate() für strikten Vergleich
- [x] Input-Validation: Core sauber (PageData/TracedArray), nur 3rd-Party hat rohe $_REQUEST Zugriffe

#### Phase 3 Details
- `session.cls.php`: `ini_set('session.cookie_secure', 1)` bei HTTPS automatisch gesetzt
- `session.cls.php`: PHP < 7.3 `setcookie()` Branch entfernt (braucht PHP >= 8.0)
- `formhandler.cls.php`: Strikter Vergleich `===` statt `==` bei Token-Validation
- CSRF-Tokens: `Common::create_token()` nutzt `random_bytes(20)` → kryptographisch sicher
- Input-Zugriff: Kein direkter `$_POST/$_GET` im Core (nur `$_GET['cookietest']` in Session)
- 3rd-Party `$_REQUEST` Zugriffe in csstidy/wymeditor → nicht im Scope

### Phase 4: Modernisierung
- [ ] Type Declarations schrittweise einführen (Start: Interfaces)
- [ ] Namespaces einführen (PSR-4)
- [ ] Structured Logging (PSR-3 / Monolog)

### Phase 5: Qualität & Cleanup
- [ ] Veraltete Module entfernen (xcache, acpu, abandoned JS-Libs)
- [ ] PHPDoc für alle public APIs
- [ ] Testabdeckung auf >50% bringen

## Scorecard

| Aspekt | Bewertung | Notizen |
|--------|-----------|---------|
| Testabdeckung | 3/10 | ~20%, selektiv; PHPUnit-Migration gestartet (29 Tests portiert) |
| Test-Framework | 4/10 | PHPUnit 10.5 eingerichtet, SimpleTest bleibt parallel |
| Dokumentation | 4/10 | PHPDoc sparse |
| Dead Code | 8/10 | Minimal, sauber |
| Konfiguration | 6/10 | Zentralisiert aber Magic Numbers |
| Error Logging | 3/10 | CSV-only, minimal |
| Moderne PHP-Features | 2/10 | Keine Nutzung |
| Sicherheit | 7/10 | ✅ bcrypt, ✅ Headers, ✅ Prepared Stmt, ✅ Session, ✅ CSRF |

## Wichtige Dateien für schnellen Einstieg

| Zweck | Pfad |
|-------|------|
| Bootstrap | `gyro/core/start.php` |
| Config | `gyro/core/config.cls.php` |
| DB-Driver | `gyro/core/model/drivers/mysql/dbdriver.mysql.php` |
| Logger | `gyro/core/lib/components/logger.cls.php` |
| User-Model | `contributions/usermanagement/model/classes/users.model.php` |
| String-Helpers | `gyro/core/lib/helpers/string.cls.php` |
| Tests | `gyro/modules/simpletest/simpletests/` |
| Routing | `gyro/core/controller/base/routes/` |

## Git-Branch

- Entwicklung auf: `claude/analyze-repository-7ADOV`
