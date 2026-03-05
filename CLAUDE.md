# Gyro-PHP Framework – Projektanalyse & Memory

> Letzte Aktualisierung: 2026-03-05

## Projektübersicht

- **Framework:** Gyro-PHP, eigenes PHP-Webframework (seit 2004, PHP 4 → PHP 5 Rewrite 2005)
- **Aktueller Stand:** Läuft auf PHP 8.x mit Safeguards, Code-Stil ist PHP 5.x Ära
- **Composer** mit classmap-Autoload (Phase 6), kein PSR-4, kein Namespace-System
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
| Test-Dateien | 57 SimpleTest + 8 PHPUnit (66 Tests, 350 Assertions) |
| Testabdeckung | ~25% (PHPUnit-Migration fortgeschritten) |
| PHPDoc-Abdeckung | ~15-20% |
| TODO/FIXME/HACK | 14 Marker |
| Contributions | 57+ Module (3 tote entfernt in Phase 5) |

## Sicherheitsprobleme

### ✅ GEFIXT: Passwort-Hashing
- Default von MD5/PHPass auf **bcrypt** umgestellt (`password_hash(PASSWORD_BCRYPT, cost 12)`)
- Neuer Hash-Algorithmus: `contributions/usermanagement/behaviour/commands/users/hashes/bcryp.hash.php`
- Timing-safe Vergleiche in MD5/SHA1 Klassen (`hash_equals()`)
- Auto-Upgrade: Alte Hashes werden beim nächsten Login automatisch migriert

### ✅ GEFIXT: HTTP Security Headers
- X-Content-Type-Options, X-Frame-Options, Referrer-Policy, Permissions-Policy
- Gesetzt in `pageviewbase.cls.php` mit `override=false`

### ✅ GEFIXT: Prepared Statements
- **Driver:** `execute_prepared()` und `query_prepared()` in `dbdriver.mysql.php` (Phase 2)
- **DB-Klasse:** `DB::execute_prepared()` und `DB::query_prepared()` Wrapper (Phase 6)
- Legacy `execute()`/`query()` nutzen weiterhin `mysqli_real_escape_string()` (Rückwärtskompatibilität)
- **Nächster Schritt:** Schrittweise Migration bestehender Queries auf Prepared Statements

### ✅ GEFIXT: Session-Konfiguration
- `httponly`, `secure` (bei HTTPS), `samesite=Lax` auf Session-Cookies konfiguriert

## ✅ PHP 8.x Kompatibilität (GEFIXT)

- `common.cls.php`: `preprocess_input()` → No-op (Magic Quotes seit PHP 7.4 weg)
- `start.php`: `E_ALL | E_STRICT` → `E_ALL`, PHP 5.3 Compat-Check entfernt
- `cast.cls.php`: `isset($value->__toString)` → `method_exists($value, '__toString')`
- `mb_*` Funktionen: NULL-Parameter teilweise gefixt (bereits vor Phase 1)

## Architektur-Schwächen

### Typ-System (Phase 4 + Phase 6)
- Interfaces mit Type Declarations versehen (Phase 4)
- Typed Properties in Interface-Implementierungen (Phase 6)
- Kein Einsatz von Enums, Attributes, Match, Readonly etc.

### Kein Namespace-System
- Alle Klassen im globalen Namespace
- Namenskonventionen statt Namespaces: `DAO*`, `*Controller`, `*Facade`
- Eigenes Autoloading statt PSR-4

### ✅ Logger modernisiert (Phase 4)
- **Datei:** `gyro/core/lib/components/logger.cls.php`
- PSR-3 kompatible Log-Levels (emergency → debug)
- Context-Interpolation (`{placeholder}` Syntax)
- JSON-Ausgabe für strukturierte Logs, CSV für Legacy `log()`
- Exception-Support mit Stack-Traces
- Konfigurierbares Minimum-Level via `Logger::set_min_level()`

### Konfigurations-Schwächen
- Hardcoded Timeouts: `$timeout_sec = 30` (HTTP), `$max_age = 600` (Cache)
- Magic Numbers: Port 443 für HTTPS, ASCII-Codes `10`/`13`, Email-Limit `64`
- String-basierte Konstanten-Lookup (flexibel aber nicht typsicher)

## Veraltete/Tote Module

### ✅ Entfernt in Phase 5
- `cache.xcache` – XCache seit PHP 7 tot (8 Dateien)
- `javascript.cleditor` – CLEditor abandoned (~36 Dateien)
- `javascript.wymeditor` – WYMeditor abandoned (~79 Dateien)

### Noch vorhanden, prüfen
- `cache.acpu` – APCu noch aktiv, nur entfernen wenn Server kein APCu nutzt
- SimpleTest 1.1.0 – abandoned seit 2012, PHPUnit parallel eingerichtet
- Mehrere CSS-Präprozessor-Module (`css.sass`, `css.yaml`, `css.postcss`)

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

### Phase 4: Modernisierung ✅ ERLEDIGT
- [x] Type Declarations in Interfaces + Implementierungen eingeführt
- [ ] Namespaces einführen (PSR-4) – **zurückgestellt** (zu großer Breaking Change)
- [x] Structured Logging (PSR-3 kompatibel)

#### Phase 4 Details: Type Declarations
- **IDBResultSet** + 3 Implementierungen (DBResultSet, DBResultSetMysql, DBResultSetSphinx)
- **ISessionHandler** + 4 Implementierungen (DBSession, ACPuSession, MemcacheSession, XCacheSession)
- **IHashAlgorithm** + 6 Implementierungen (bcryp, bcrypt, md5, sha1, pas2p, pas3p)
- **IConverter** + 12 Implementierungen (callback, chain, html, mimeheader, none, json, htmltidy, punycode, htmlpurifier, textplaceholders, unidecode, twitter)
- **ICachePersister** + 5 Implementierungen (CacheDBImpl, CacheFileImpl, CacheXCacheImpl, CacheACPuImpl, CacheMemcacheImpl)
- Union Types: `array|false`, `string|false`, `int|false`, `ICacheItem|false`, `mixed`
- **IDBDriver** zurückgestellt (Sphinx-Driver hat fehlende Methoden)

#### Phase 4 Details: Structured Logging
- `Logger` erweitert um PSR-3 kompatible Methoden: `Logger::error()`, `Logger::info()`, etc.
- Context-Interpolation: `Logger::error('User {user} failed login', ['user' => $name])`
- JSON-Output pro Level-Datei (z.B. `error-2026-03-05.log`)
- Exception-Support: `Logger::error('Fehler', ['exception' => $ex])` → inkl. Trace
- Konfigurierbar: `Logger::set_min_level(Logger::WARNING)` filtert Debug/Info/Notice
- Legacy `Logger::log()` bleibt voll rückwärtskompatibel (CSV-Format)

### Phase 5: Qualität & Cleanup
- [ ] Veraltete Module entfernen (xcache, acpu, abandoned JS-Libs)
- [ ] PHPDoc für alle public APIs
- [ ] Testabdeckung auf >50% bringen

### Phase 6: Modernisierung II ✅ ERLEDIGT
- [x] Typed Properties in allen Interface-Implementierungen (12 Klassen, 16 Properties)
- [x] `DB::execute_prepared()` und `DB::query_prepared()` statische Wrapper
- [x] Composer classmap Autoload (`gyro/core/`, `contributions/`)
- [x] PHPStan Level 1 eingerichtet (`phpstan.neon.dist`)

#### Phase 6 Details: Typed Properties
- **DBResultSet**: `?PDOStatement $pdo_statement`
- **DBResultSetMysql**: `?mysqli_result $result_set`, `?Status $status`
- **DBResultSetSphinx**: `?array $result`, `Status $status`
- **DBResultSetCountSphinx**: `bool $done`
- **CacheDBImpl**: `mixed $cache_item`
- **CacheFileImpl**: `string $cache_dir`, `string $ext`, `string $divider`
- **FileCacheItem**: `array $item_data`
- **ACPuCacheItem**: `array $item_data`
- **MemcacheCacheItem**: `array $item_data`
- **ConverterChain**: `array $converters`, `array $params`
- **ConverterHtmlTidy**: `array $predefined_params`
- **ConverterUnidecode**: `static array $groups`

#### Phase 6 Details: Composer & PHPStan
- `composer.json`: `autoload.classmap` für `gyro/core/` und `contributions/` (3rd-Party ausgeschlossen)
- `phpstan.neon.dist`: Level 1, analysiert Core + Contributions, excludiert 3rd-Party/Tests
- PHPStan als `require-dev` Dependency hinzugefügt

## Scorecard

| Aspekt | Bewertung | Notizen |
|--------|-----------|---------|
| Testabdeckung | 3/10 | ~20%, selektiv; PHPUnit-Migration gestartet (29 Tests portiert) |
| Test-Framework | 4/10 | PHPUnit 10.5 eingerichtet, SimpleTest bleibt parallel |
| Dokumentation | 4/10 | PHPDoc sparse |
| Dead Code | 8/10 | Minimal, sauber |
| Konfiguration | 6/10 | Zentralisiert aber Magic Numbers |
| Error Logging | 7/10 | ✅ PSR-3 Levels, JSON-Output, Context, Exception-Support |
| Moderne PHP-Features | 5/10 | ✅ Type Declarations, ✅ Typed Properties, ✅ Union Types |
| Sicherheit | 7/10 | ✅ bcrypt, ✅ Headers, ✅ Prepared Stmt, ✅ Session, ✅ CSRF |
| Statische Analyse | 3/10 | PHPStan Level 1 eingerichtet, noch nicht durchgelaufen |

## Moderne PHP-Features Analyse

### Bestandsaufnahme (Stand 2026-03-05)

| Feature | Vorhanden? | Details |
|---------|-----------|---------|
| Namespaces | NEIN | 0 Deklarationen im Framework (nur 3rd-Party FPDI nutzt sie) |
| Typed Properties | TEILWEISE | ✅ In 12 Interface-Implementierungen (Phase 6), Rest noch untypisiert |
| Enums | NEIN | Kein PHP 8.1+ `enum` |
| Named Arguments | NEIN | Nicht genutzt |
| Match Expressions | NEIN | Nur in 3rd-Party (SimpleTest, Sphinx) |
| Readonly Properties | NEIN | Nicht genutzt |
| Fibers/Async | NEIN | Nicht genutzt |
| Attributes | NEIN | Kein PHP 8.0+ `#[...]` |
| PSR-Interfaces | MINIMAL | Eigene Event-Interfaces (IEventSink/IEventSource), kein PSR-7/11/14/15/17/18 |
| Composer Autoload | TEILWEISE | ✅ classmap für `gyro/core/` + `contributions/` (Phase 6), eigene `Load`-Klasse bleibt parallel |
| Environment Vars (.env) | MINIMAL | Nur `getenv()` für Temp-Verzeichnis (TMP/TEMP/TMPDIR); kein dotenv |
| Return Type Declarations | TEILWEISE | In 5 Core-Interfaces (Phase 4) |
| Union Types | TEILWEISE | `string\|false`, `array\|false`, `int\|false`, `ICacheItem\|false`, `mixed` |

### Interfaces mit Type Declarations (Phase 4)

| Interface | Datei | Implementierungen |
|-----------|-------|-------------------|
| IDBResultSet | `gyro/core/lib/interfaces/idbresultset.cls.php` | DBResultSet, DBResultSetMysql, DBResultSetSphinx |
| ISessionHandler | `gyro/core/lib/interfaces/isessionhandler.cls.php` | DBSession, ACPuSession, MemcacheSession, XCacheSession |
| ICachePersister | `gyro/core/lib/interfaces/icachepersister.cls.php` | CacheDBImpl, CacheFileImpl, CacheXCacheImpl, CacheACPuImpl, CacheMemcacheImpl |
| IConverter | `gyro/core/lib/interfaces/iconverter.cls.php` | 12+ Implementierungen (callback, chain, html, json, punycode, etc.) |
| IHashAlgorithm | `contributions/usermanagement/lib/interfaces/ihash.cls.php` | bcryp, bcrypt, md5, sha1, pas2p, pas3p |

### Autoloading

- **Eigene Klasse:** `gyro/core/load.cls.php` (`Load::add_module_base_dir()`)
- Kein PSR-4, kein Composer-Autoload
- Modul-Discovery über Framework-eigenes System

### Fazit

Framework ist **selektiv modernisiert**: Return Types + Union Types in Core-Interfaces, aber keinerlei Nutzung von Namespaces, Typed Properties, Enums, Attributes, Match, Readonly oder anderen PHP 8.x Features. Code-Stil bleibt PHP 5.x Ära mit PHP 8.x Kompatibilität.

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
