# Gyro-PHP Framework – Projektanalyse & Memory

> Letzte Aktualisierung: 2026-03-06 (Phase 16 abgeschlossen)

## Projektübersicht

- **Framework:** Gyro-PHP, eigenes PHP-Webframework (seit 2004, PHP 4 → PHP 5 Rewrite 2005)
- **Aktueller Stand:** Läuft auf PHP 8.x mit Safeguards, Code-Stil ist PHP 5.x Ära
- **Composer** für Dev-Dependencies (PHPUnit, PHPStan) + PSR-4 Autoload (`Gyro\` → `src/`)
- **Namespaces:** 10 Core-Klassen via `class_alias()` Dual-Loading (Phase 15)
- **Test-Framework:** PHPUnit 10.5 (primär, 470 Tests) + SimpleTest 1.1.0 (Legacy, abandoned)
- **CLI-Tool:** `bin/gyro` (Phase 8) — model:list, model:show, db:sync
- **Middleware:** MiddlewareStack + IMiddleware Interface (Phase 13)
- **DI-Container:** Container-Klasse mit Singleton/Factory/Bind (Phase 13)
- **Statische Analyse:** PHPStan Level 3, 0 Fehler (Baseline leer)
- **Environment:** `.env` Support (Phase 7), rückwärtskompatibel mit `APP_*` Konstanten

## Verzeichnisstruktur

```
bin/                             # CLI-Werkzeuge
  gyro                           # CLI Entry Point (Phase 8)
src/                           # PSR-4 Namespace-Stubs (Phase 15)
  Core/                        # Config, Common, DB
  Lib/Components/              # Logger, Container
  Lib/Helpers/                 # Env, GyroString, Arr, Cast, Url
gyro/                          # Framework-Core
  core/
    config.cls.php             # Zentrale Config (281 Zeilen, 100+ Konstanten)
    start.php                  # Bootstrap/Entry Point
    cli/                       # CLI-Kernel, Commands, Helpers (Phase 8)
    controller/base/           # Basis-Controller & Routing
    model/base/                # DB-Abstraktionsschicht
    model/drivers/mysql/       # MySQL-Driver (nur mysqli_real_escape_string)
    lib/components/            # Core-Komponenten (Logger, HTTP, etc.)
    lib/helpers/               # Hilfsklassen (String, Array, Cast, etc.)
    lib/interfaces/            # Interface-Definitionen
    view/base/                 # View-Layer
  modules/                     # Framework-Module
    api/                       # Auto-REST-API (Phase 9)
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
| PHPUnit-Tests | 470 Tests, 1468 Assertions (77 Test-Dateien) |
| PSR-4 Namespace-Aliase | 10 Klassen in `src/` (Phase 15) |
| REST-API-Modul | 3 Dateien (Controller, Helper, Start) |
| SimpleTest (Legacy) | 57 Dateien (größtenteils nach PHPUnit portiert) |
| Testabdeckung | ~50%+ (Phase 7: massive Erweiterung) |
| PHPDoc-Abdeckung | ~25-30% |
| TODO/FIXME/HACK | 14 Marker |
| Contributions | 57+ Module (3 tote entfernt in Phase 5) |
| PHPStan | Level 3, 0 Fehler (Baseline leer, von ursprünglich 1262) |

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

### ✅ Environment-Konfiguration (Phase 7)
- **Datei:** `gyro/core/lib/helpers/env.cls.php`
- `.env` Datei-Loader mit automatischer `APP_*` Konstanten-Definition
- Rückwärtskompatibel: Ohne `.env` funktioniert alles wie bisher
- Integration in `start.php`: Lädt `.env` vor `constants.inc.php`
- `.env.example` mit allen verfügbaren Konfigurationsvariablen
- Type-Casting: `true`/`false` → bool, Zahlen → int/float
- Keine externe Dependency (kein vlucas/phpdotenv nötig)

### Konfigurations-Schwächen (teilweise behoben)
- ✅ `.env` Support für Environment-abhängige Konfiguration (Phase 7)
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
- [x] Testabdeckung auf >50% bringen ✅ (Phase 7)

### Phase 6: Modernisierung II ✅ ERLEDIGT
- [x] Typed Properties in allen Interface-Implementierungen (12 Klassen, 16 Properties)
- [x] `DB::execute_prepared()` und `DB::query_prepared()` statische Wrapper
- [x] Composer classmap Autoload → **entfernt** (Phase 7: Konflikt mit `Load::directories()` und `include_once` Pfad-Auflösung)
- [x] PHPStan Level 1 eingerichtet → **Level 2 mit Baseline** (Phase 7)

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
- `composer.json`: classmap entfernt (Phase 7 — Pfadkonflikte mit `Load::directories()`)
- `phpstan.neon.dist`: Level 2 mit Baseline (Phase 7), analysiert Core + Contributions
- PHPStan als `require-dev` Dependency hinzugefügt

### Phase 7: Testabdeckung & Infrastruktur ✅ ERLEDIGT
- [x] SimpleTest → PHPUnit Migration: 43 von 45 Tests portiert (2 brauchen echte DB)
- [x] Neue Tests für alle DB-Feldtypen (Bool, Enum, Float, Serialized, Set)
- [x] Neue Tests für Converter (Callback, Chain, None, Html, HtmlEx, MimeHeader)
- [x] Neue Tests für Query Builder (Select, Count, Delete, Insert, Update, Joined, Secondary)
- [x] Neue Tests für Where/Filter/Sort (DBWhere, DBWhereGroup, DBFilter, DBFilterColumn, DBSortColumn, DBCondition)
- [x] Neue Tests für Routing (ExactMatchRoute, ParameterizedRoute, RouteBase)
- [x] Neue Tests für Helpers (Cast, Timer, HtmlString, PathStack, Header, RuntimeCache, Locale)
- [x] Neue Tests für Model (DAO, DataObject, DBExpression, DBNull, DBFieldRelation, DBJoinCondition)
- [x] Neue Tests für weitere Klassen (TracedArray, RequestInfo, GyroCookieConfig, Referer, WidgetInput)
- [x] `.env` Environment-Konfiguration (eigener Loader, keine externe Dependency)
- [x] PHPStan Level 1 → Level 2 mit Baseline (1262 bekannte Fehler)
- [x] Composer classmap entfernt (Pfadkonflikt mit `include_once`)
- [x] `ConverterHtmlEx` PHP 8.x Type-Kompatibilität gefixt
- [x] EnvTest (11 Tests)
- **Ergebnis:** 254 Tests, 985 Assertions (alle grün)

#### Phase 7 Details: .env Support
- **Datei:** `gyro/core/lib/helpers/env.cls.php` (Env-Klasse)
- **Integration:** `start.php` lädt `.env` vor `constants.inc.php`
- **Mechanismus:** `.env` Werte werden als `APP_*` Konstanten definiert (wenn nicht bereits definiert)
- Bestehende `set_value_from_constant()` / `set_feature_from_constant()` Aufrufe greifen automatisch
- `.env.example` dokumentiert alle verfügbaren `APP_*` Variablen
- `.env` in `.gitignore` aufgenommen
- Nutzung: `Env::get('DB_HOST', 'localhost')` oder über `APP_DB_HOST` Konstante

#### Phase 7 Details: PHPStan Level 2
- `phpstan.neon.dist`: Level 2, Baseline (`phpstan-baseline.neon`) mit 1262 bekannten Fehlern
- Neue Fehler werden sofort gemeldet, bestehende sind getracked
- 10 Contribution-Dateien excludiert (fehlende externe Klassen/Interfaces)
- Sphinx-Driver: `execute_prepared()`/`query_prepared()` fehlen weiterhin (bekannt)

#### Phase 7 Details: Testinfrastruktur
- `tests/bootstrap.php`: Lädt kompletten Framework-Core für Tests
  - Model-Subdirectories (`fields/`, `queries/`, `sqlbuilder/`, `constraints/`)
  - Controller/Routing, Behaviour, View/Widgets
  - Converter-Klassen (`lib/helpers/converters/`)
  - Mock-DB-Driver via Reflection als Default-Connection registriert
- `phpunit.xml.dist`: Core + Contributions Test-Suites
- Mock-Klassen: `DBDriverMySqlMock` (kein DB-Connect), `MockIDBTable` (SQL-Generation testen)

#### Phase 7 Details: Bekannte Test-Limitierungen
- 2 SimpleTest-Dateien nicht portierbar ohne echte DB (Cache, UpdateCommand)
- ~~3 PHP 8.4 Deprecation Warnings~~ → alle gefixt (dynamische Properties deklariert)
- Mock-Driver nutzt `GyroString::escape()` (HTML-Entities) statt `mysqli_real_escape_string`

### Phase 8: CLI-Tool ✅ ERLEDIGT
- [x] CLI Entry Point (`bin/gyro`) mit Bootstrap ohne HTTP-Kontext
- [x] CLI-Kernel mit Command-Routing, Argument-Parsing, farbiger Ausgabe
- [x] `model:list` — Alle DAO-Modelle auflisten (mit Model-Discovery)
- [x] `model:show <table>` — Detailliertes Schema, CREATE TABLE SQL
- [x] `db:sync` — Schema-Diff mit ALTER TABLE Generation (Dry-Run + Execute)
- [x] CLITable ASCII-Tabellenrenderer
- [x] 33 neue Tests (CLITable, CLIKernel, ModelShowCommand)
- **Ergebnis:** 287 Tests, 1066 Assertions (alle grün)

#### Phase 8 Details: CLI-Architektur
- **Entry Point:** `bin/gyro` (executable PHP-Script)
- **Bootstrap:** `gyro/core/cli/bootstrap.cli.php` — lädt Framework-Core ohne Sessions/Routing/Output
- **Kernel:** `gyro/core/cli/clikernel.cls.php` — registriert Commands, parsed Args, delegiert
- **Commands:** `gyro/core/cli/commands/` — je ein Kommando pro Datei
- **Erweiterbar:** Eigene Commands durch Ableitung von `CLICommand`

#### Phase 8 Details: Model-Discovery
- Scannt `GYRO_CORE_DIR/model/classes/` und alle geladenen Module-Verzeichnisse
- Instanziiert DAOs und liest Schema via `get_table_fields()`, `get_table_keys()`, `get_table_relations()`
- Fallback: Wenn Klassennamen-Ableitung nicht passt, erkennt neue `DAO*` Klassen via `get_declared_classes()`
- Generiert CREATE TABLE SQL aus DBField-Introspection

#### Phase 8 Details: db:sync
- Vergleicht Model-Schema mit INFORMATION_SCHEMA (SHOW COLUMNS)
- Erkennt: fehlende Tabellen (CREATE), fehlende Spalten (ADD COLUMN), geänderte Typen (MODIFY COLUMN)
- Warnt bei DB-Spalten, die nicht im Model existieren (kein Auto-DROP — zu gefährlich)
- `--dry-run` (Default) zeigt SQL, `--execute` führt aus

### Phase 9: Auto-REST-API ✅ ERLEDIGT
- [x] REST-API-Modul (`gyro/modules/api/`) mit Auto-Discovery aller DAO-Modelle
- [x] CRUD-Endpoints: GET (list/show), POST (create), PUT (update), DELETE
- [x] Schema-Endpoint: GET /api/{table}/schema (Felder, Typen, Relations als JSON)
- [x] API-Index: GET /api (alle verfügbaren Endpoints auflisten)
- [x] Paging, Filtering, Sorting über Query-Parameter
- [x] JsonResponse Helper mit Typ-gerechter Serialisierung
- [x] INTERNAL-Felder automatisch ausgeblendet
- [x] X-HTTP-Method-Override Support
- [x] Composite Primary Key Support (Pipe-separiert)
- [x] 20 neue Tests (JsonResponse + RestApiController)
- [x] phpunit.xml.dist Fix (fehlende contributions-Verzeichnis)
- **Ergebnis:** 307 Tests, 1138 Assertions (alle grün)

#### Phase 9 Details: Architektur
- **Modul:** `gyro/modules/api/` — aktivierbar via `Load::enable_module('api')`
- **Controller:** `RestApiController` extends `ControllerBase` — registriert `/api/` Routes
- **JSON Helper:** `JsonResponse` — Typ-Mapping (INT→integer, BOOL→boolean, FLOAT→number)
- **Auto-Discovery:** Nutzt `ModelListCommand::discover_models()` aus Phase 8
- **Konfiguration:** `RestApiController::register_model()` / `::exclude_table()`

#### Phase 9 Details: Endpoints
| Methode | URL | Beschreibung |
|---------|-----|--------------|
| `GET` | `/api` | Alle Endpoints auflisten |
| `GET` | `/api/{table}` | Records auflisten (Paging: `?page=2&per_page=10`) |
| `GET` | `/api/{table}/{id}` | Einzelnen Record abrufen |
| `POST` | `/api/{table}` | Record erstellen (JSON Body) |
| `PUT` | `/api/{table}/{id}` | Record aktualisieren (JSON Body) |
| `DELETE` | `/api/{table}/{id}` | Record löschen |
| `GET` | `/api/{table}/schema` | Schema als JSON |

#### Phase 9 Details: Features
- **Filtering:** `?filter[field]=value` — nur auf nicht-INTERNAL Feldern
- **Sorting:** `?sort=field&order=asc|desc`
- **Paging:** `?page=1&per_page=25` (max 200 pro Seite)
- **Validierung:** Nutzt `DataObjectBase::validate()` mit Feld-Level Validation
- **Error Responses:** Einheitliches JSON-Format mit HTTP Status Codes (400, 404, 405, 422, 500)
- **Composite Keys:** `/api/table/key1|key2` für Multi-Column Primary Keys

### Phase 11: Auto-Admin ✅ ERLEDIGT
- [x] `AdminController` (`gyro/modules/admin/controller/admin.controller.php`)
- [x] `AdminHtml` Helper (`gyro/modules/admin/lib/helpers/adminhtml.cls.php`)
- [x] Dashboard: `/admin/` mit Modell-Übersicht und Statistiken
- [x] List View: Paging, Sorting, bis zu 6 Spalten (INTERNAL/Blob ausgeblendet)
- [x] Detail View: Alle nicht-INTERNAL Felder als Key-Value
- [x] Create/Edit Forms: Auto-generated aus DBField-Schema
- [x] Delete: Bestätigungs-Dialog, POST-basiert
- [x] Form-Mapping: DBFieldInt→number, DBFieldBool→checkbox, DBFieldEnum→select, DBFieldBlob→textarea
- [x] Self-Contained HTML+CSS (kein CDN, kein Template-System nötig)
- [x] Flash-Messages für CRUD-Operationen
- [x] 34 neue Tests (AdminHtml + AdminController)
- **Ergebnis:** 361 Tests, 1290 Assertions (alle grün)

#### Phase 11 Details: Auto-Admin Architektur
- **Module:** `gyro/modules/admin/` — aktivierbar via `Load::enable_module('admin')`
- **Controller:** `AdminController` mit `get_routes()` → ExactMatchRoute + RouteBase
- **HTML-Rendering:** `AdminHtml` als statische Helper-Klasse (kein Template-Engine nötig)
- **Auto-Discovery:** Nutzt `ModelListCommand::discover_models()` (gleich wie REST-API)
- **Konfiguration:** `AdminController::register_model()` / `::exclude_table()`
- **Features:**
  - Responsive Design mit eingebettetem CSS
  - Breadcrumb-Navigation
  - Flash-Messages (created/updated/deleted)
  - INTERNAL-Felder nie exponiert (Formulare + Detail-Ansicht)
  - Composite Primary Key Support (Pipe-separiert)
  - Validierung über `DataObjectBase::validate()` mit Fehler-Anzeige
  - AUTOINCREMENT PKs in Create-Formularen ausgeblendet
  - PKs in Edit-Formularen nicht editierbar

### Phase 10: OpenAPI/Swagger ✅ ERLEDIGT
- [x] `OpenApiGenerator` Klasse (`gyro/modules/api/lib/helpers/openapigenerator.cls.php`)
- [x] `GET /api/openapi.json` Endpoint im RestApiController
- [x] Vollständige OpenAPI 3.0.3 Spezifikation aus DAO-Modellen
- [x] Schema-Generation mit Typ-Mapping, Enum-Werte, maxLength, nullable, required
- [x] Input-Schemas ohne AUTOINCREMENT Primary Keys
- [x] 20 neue Tests für OpenApiGenerator
- **Ergebnis:** 327 Tests, 1199 Assertions (alle grün)

#### Phase 10 Details: OpenAPI Generator
- **Datei:** `gyro/modules/api/lib/helpers/openapigenerator.cls.php`
- **Endpoint:** `GET /api/openapi.json` — liefert vollständige OpenAPI 3.0.3 Spezifikation
- **Features:**
  - Auto-Discovery: Liest alle registrierten Models aus RestApiController
  - Typ-Mapping: DBField → OpenAPI-Typen (integer, number, boolean, string mit Formaten)
  - Enum-Werte: DBFieldEnum-Werte werden als `enum` im Schema ausgegeben
  - Text-Längen: `maxLength` aus DBFieldText
  - Nullable: Felder ohne NOT_NULL bekommen `nullable: true`
  - Required: NOT_NULL Felder ohne Default werden als `required` markiert
  - Input-Schemas: Separate Schemas ohne AUTOINCREMENT PKs für POST/PUT
  - Vollständige Pfade: GET list, GET show, POST create, PUT update, DELETE, Schema
  - Query-Parameter: page, per_page, sort, order für List-Endpoints
  - Error-Responses: 400, 404, 405, 422 mit einheitlichem Schema

### Phase 13: Middleware, DI-Container, PHPStan Baseline Abbau ✅ ERLEDIGT
- [x] PHPStan Baseline: 539 → 13 Fehler (98% Reduktion, 526 Fehler behoben)
- [x] PHPDoc-Korrekturen in 120+ Dateien (fehlende `$variable`-Namen, Typ-Fixes)
- [x] Middleware-Pattern: `IMiddleware` Interface + `MiddlewareStack` + `MiddlewareRenderDecorator`
- [x] DI-Container: `Container`-Klasse mit Singleton/Factory/Bind
- [x] 25 neue Tests (MiddlewareTest + ContainerTest)
- **Ergebnis:** 386 Tests, 1333 Assertions (alle grün)

#### Phase 13 Details: Middleware-Architektur
- **Interface:** `gyro/core/lib/interfaces/imiddleware.cls.php` (`handle()` + `process_response()`)
- **Basisklasse:** `gyro/core/controller/base/middleware/middlewarebase.cls.php`
- **Stack:** `gyro/core/controller/base/middleware/middlewarestack.cls.php` (globale Registrierung mit Prioritäten)
- **Bridge:** `gyro/core/controller/base/middleware/middlewarerenderdecorator.cls.php` (Adapter zum RenderDecorator-System)
- **Integration:** `RouteBase::get_renderer()` injiziert globale + route-level Middleware in Decorator-Chain
- **Per-Route:** `RouteBase::add_middleware($mw)` für route-spezifische Middleware
- **Globale:** `MiddlewareStack::add($mw, $priority)` für systemweite Middleware

#### Phase 13 Details: DI-Container
- **Datei:** `gyro/core/lib/components/container.cls.php`
- **Singleton-Pattern:** `Container::instance()` gibt globale Instanz zurück
- **Registrierung:** `singleton()` (lazy, einmal), `factory()` (jedes Mal neu), `bind()` (direktes Objekt)
- **Auflösung:** `$container->get('service')` oder `Container::get_service('name')`
- **Container-Injection:** Factory-Closures erhalten den Container als Parameter
- **Testbar:** `Container::reset_instance()` für saubere Tests

#### Phase 13 Details: PHPStan Baseline Abbau
- 262 PHPDoc `@param` fehlende `$variable`-Namen korrigiert
- 72 Default-Value-Typ-Mismatches behoben (PHPDoc-Typen um `|false` erweitert)
- 28 Parameter-Typ-Inkompatibilitäten gefixt (`timestamp` → `int`, etc.)
- 32 SystemUpdateInstaller-Referenzen verbleiben in Baseline (Runtime-Klasse)
- Verbleibende 69 Fehler: externe Klassen, Runtime-Abhängigkeiten, Legacy-Defaults

## Scorecard

| Aspekt | Bewertung | Notizen |
|--------|-----------|---------|
| Testabdeckung | 8/10 | ~65%+, 470 Tests / 1468 Assertions (PHPUnit 10.5) |
| Test-Framework | 7/10 | PHPUnit 10.5 primär, Mock-Infrastruktur, SimpleTest Legacy |
| Dokumentation | 6/10 | PHPDoc ~45-50%, Core-APIs dokumentiert (Phase 12+13) |
| Dead Code | 8/10 | Minimal, sauber |
| Konfiguration | 7/10 | ✅ `.env` Support, zentralisiert, noch Magic Numbers |
| Error Logging | 7/10 | ✅ PSR-3 Levels, JSON-Output, Context, Exception-Support |
| Moderne PHP-Features | 7/10 | ✅ Type Declarations, ✅ Typed Properties, ✅ Union Types, ✅ Middleware, ✅ DI-Container, ✅ PSR-4 Namespaces |
| Sicherheit | 7/10 | ✅ bcrypt, ✅ Headers, ✅ Prepared Stmt, ✅ Session, ✅ CSRF |
| CLI-Tooling | 6/10 | ✅ `bin/gyro` mit model:list, model:show, db:sync |
| Auto-Admin | 7/10 | ✅ Django-Style CRUD UI aus Model-Schema |
| REST-API | 8/10 | ✅ Auto-REST-API + OpenAPI/Swagger Dokumentation |
| Statische Analyse | 10/10 | ✅ PHPStan Level 3, 0 Fehler (Baseline leer, von 1262) |

## Moderne PHP-Features Analyse

### Bestandsaufnahme (Stand 2026-03-06)

| Feature | Vorhanden? | Details |
|---------|-----------|---------|
| Namespaces | ✅ TEILWEISE | 10 Core-Klassen via `class_alias()` Dual-Loading (Phase 15), Rest noch global |
| Typed Properties | TEILWEISE | ✅ In 12 Interface-Implementierungen (Phase 6), Rest noch untypisiert |
| Enums | NEIN | Kein PHP 8.1+ `enum` |
| Named Arguments | NEIN | Nicht genutzt |
| Match Expressions | NEIN | Nur in 3rd-Party (SimpleTest, Sphinx) |
| Readonly Properties | NEIN | Nicht genutzt |
| Fibers/Async | NEIN | Nicht genutzt |
| Attributes | NEIN | Kein PHP 8.0+ `#[...]` |
| PSR-Interfaces | ✅ TEILWEISE | PSR-11 (Container), PSR-3 (Logger), PSR-15 (Middleware-Adapter), PSR-16 (Cache-Adapter) |
| Composer Autoload | ✅ TEILWEISE | PSR-4 für `Gyro\` Namespace (Phase 15), eigene `Load`-Klasse als primär |
| Environment Vars (.env) | ✅ JA | Eigener `.env` Loader (`Env`-Klasse), `APP_*` auto-define (Phase 7) |
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

- **Primär:** `gyro/core/load.cls.php` (`Load::add_module_base_dir()`)
- **Sekundär:** Composer PSR-4 Autoload für `Gyro\` Namespace (`src/`)
- Composer-Autoloader wird als Fallback in `Load`-Klasse registriert
- Modul-Discovery über Framework-eigenes System

### Fazit

Framework ist **umfassend modernisiert**: Return Types + Union Types in Core-Interfaces, Typed Properties in Implementierungen, `.env` Support, PHPStan Level 3, Middleware-Pattern, DI-Container, **PSR-4 Namespaces für 10 Core-Klassen**. Keine Nutzung von Enums, Attributes, Match, Readonly. Code-Stil bleibt PHP 5.x Ära mit PHP 8.1+ Kompatibilität und moderner Tooling-Infrastruktur.

### Nächste Schritte (Empfehlung)
- ~~PHPStan Baseline schrittweise abbauen~~ ✅ Phase 13: 1262 → 13 Fehler (99% Reduktion)
- ~~PHPDoc für public APIs ergänzen~~ ✅ Phase 12+13: ~45-50% Coverage
- ~~Middleware-Pattern einführen~~ ✅ Phase 13: `IMiddleware` + `MiddlewareStack`
- ~~Einfacher DI-Container für bessere Testbarkeit~~ ✅ Phase 13: `Container`-Klasse
- ~~CLI-Tool für Code-Generierung (ähnlich Artisan)~~ ✅ Phase 8: `bin/gyro`
- ~~Auto-REST-API aus DAO-Modellen generieren~~ ✅ Phase 9: `gyro/modules/api/`
- ~~Auto-Admin-Interface aus ISelfDescribing + IActionSource~~ ✅ Phase 11: `gyro/modules/admin/`
- ~~PHPStan Baseline verbleibende 13 Fehler~~ ✅ Alle behoben (Baseline leer)
- ~~Namespaces/PSR-4 einführen~~ ✅ Phase 15: Dual-Loading mit `class_alias()`
- **Migrations-Assistent** (Phase 14) — siehe Plan unten

### Phase 15: Namespace Foundation ✅ ERLEDIGT
- [x] `src/` Verzeichnis mit PSR-4 Struktur (`Core/`, `Lib/Components/`, `Lib/Helpers/`)
- [x] Composer PSR-4 Autoloading: `"Gyro\\": "src/"`
- [x] PHP Minimum auf 8.1 angehoben
- [x] 10 Core-Klassen mit `class_alias()` Dual-Loading
- [x] Composer-Autoloader als Fallback in `Load`-Klasse registriert
- [x] 29 neue Tests (`NamespaceAliasTest`)
- **Ergebnis:** 415 Tests, 1384 Assertions (alle grün), PHPStan 0 neue Fehler

#### Phase 15 Details: Namespace-Strategie
- **Ansatz:** Dual-Loading — Original-Klassen bleiben in `gyro/`, `class_alias()` macht Namespaces verfügbar
- **Kein Breaking Change** — bestehender Code nutzt weiterhin globale Klassennamen
- **Neuer Code** kann `use Gyro\Core\Config;` etc. verwenden
- **`src/` Stubs:** Enthalten `require_once` + verlassen sich auf das `class_alias()` im Original
- **Composer-Fallback:** `Load`-Klasse registriert Composers Autoloader als Fallback

#### Phase 15 Details: Migrierte Klassen
| Global | Namespace | Original-Datei |
|--------|-----------|---------------|
| `Config` | `Gyro\Core\Config` | `gyro/core/config.cls.php` |
| `Common` | `Gyro\Core\Common` | `gyro/core/lib/helpers/common.cls.php` |
| `DB` | `Gyro\Core\DB` | `gyro/core/model/base/db.cls.php` |
| `Logger` | `Gyro\Lib\Components\Logger` | `gyro/core/lib/components/logger.cls.php` |
| `Container` | `Gyro\Lib\Components\Container` | `gyro/core/lib/components/container.cls.php` |
| `Env` | `Gyro\Lib\Helpers\Env` | `gyro/core/lib/helpers/env.cls.php` |
| `GyroString` | `Gyro\Lib\Helpers\GyroString` | `gyro/core/lib/helpers/string.cls.php` |
| `Arr` | `Gyro\Lib\Helpers\Arr` | `gyro/core/lib/helpers/array.cls.php` |
| `Cast` | `Gyro\Lib\Helpers\Cast` | `gyro/core/lib/helpers/cast.cls.php` |
| `Url` | `Gyro\Lib\Helpers\Url` | `gyro/core/lib/helpers/url.cls.php` |

### Phase 16: PSR-Interface-Adoption ✅ ERLEDIGT
- [x] PSR-11: `Container` implementiert `Psr\Container\ContainerInterface`
- [x] PSR-3: Neue `LoggerInstance` Klasse implementiert `Psr\Log\LoggerInterface`
- [x] PSR-15: `Psr15Adapter` und `GyroPsr15Bridge` Middleware-Adapter
- [x] PSR-16: `Psr16Adapter` implementiert `Psr\SimpleCache\CacheInterface`
- [x] Composer-Dependencies: `psr/container`, `psr/log`, `psr/http-server-middleware`, `psr/http-message`, `psr/simple-cache`
- [x] 55 neue Tests (PSR-11: 12, PSR-3: 14, PSR-15: 11, PSR-16: 18)
- **Ergebnis:** 470 Tests, 1468 Assertions (alle grün), PHPStan 0 neue Fehler

#### Phase 16 Details: PSR-11 Container
- **Datei:** `gyro/core/lib/components/container.cls.php`
- `Container` implementiert `Psr\Container\ContainerInterface` direkt
- `get(string $id): mixed` mit typisierter Signatur
- `has(string $id): bool` mit typisierter Signatur
- `ServiceNotFoundException` (`src/Lib/Components/Exception/`) implementiert `NotFoundExceptionInterface`
- Bestehende Methoden (`singleton()`, `factory()`, `bind()`) bleiben unverändert

#### Phase 16 Details: PSR-3 Logger
- **Datei:** `src/Lib/Components/LoggerInstance.php`
- Wrapper-Klasse die `Psr\Log\LoggerInterface` implementiert
- Delegiert alle Aufrufe an die statische `Logger`-Klasse
- `log($level, ...)` akzeptiert `Psr\Log\LogLevel` Konstanten
- Statische Logger-Facade bleibt unverändert für bestehenden Code

#### Phase 16 Details: PSR-15 Middleware-Adapter
- **Dateien:** `src/Core/Middleware/Psr15Adapter.php`, `GyroPsr15Bridge.php`
- `Psr15Adapter`: Wrapped `Psr\Http\Server\MiddlewareInterface` als `IMiddleware`
- `GyroPsr15Bridge`: Wrapped `IMiddleware` als `Psr\Http\Server\MiddlewareInterface`
- `Psr15PageData`: Minimaler PageData-Wrapper für PSR-7 Request Transport
- Bestehende `IMiddleware` und `MiddlewareStack` bleiben unverändert

#### Phase 16 Details: PSR-16 Cache-Adapter
- **Datei:** `src/Lib/Cache/Psr16Adapter.php`
- Implementiert `Psr\SimpleCache\CacheInterface`
- Wrapped `ICachePersister` mit Serialisierung (serialize/unserialize)
- `DateInterval` TTL-Support, Key-Validation nach PSR-16 Spec
- `InvalidCacheKeyException` implementiert `Psr\SimpleCache\InvalidArgumentException`

### Phase 14: Migrations-Assistent (GEPLANT)

Ziel: CLI-Commands `migrate:check` und `migrate:apply`, die bestehende Gyro-PHP-Installationen analysieren und beim Upgrade auf die aktuelle Version unterstützen.

#### Phase 14a: `migrate:check` — Diagnose-Command
- [x] Neuer CLI-Command `bin/gyro migrate:check`
- [ ] Scanner-Architektur: `IMigrationCheck` Interface mit `scan()` → Array von Findings
- [ ] Findings mit Severity-Levels: CRITICAL, WARNING, INFO
- [ ] Konsolenausgabe: Farbiger Report gruppiert nach Severity
- [ ] Checks implementieren:
  - **CRITICAL: PHP 8.x Kompatibilität**
    - `get_magic_quotes_gpc()` Aufrufe erkennen
    - `E_STRICT` / `defined('E_DEPRECATED')` Patterns
    - `isset($value->__toString)` statt `method_exists()`
  - **CRITICAL: Sicherheit**
    - Veraltete Passwort-Hashes (MD5/SHA1 Default statt bcrypt)
    - `execute()` mit String-Interpolation statt `execute_prepared()`
    - Fehlende Security-Headers
    - `==` statt `===` in sicherheitskritischen Vergleichen
  - **WARNING: Veraltete Module**
    - `cache.xcache` (tot seit PHP 7)
    - `javascript.cleditor`, `javascript.wymeditor` (abandoned)
  - **WARNING: Konfiguration**
    - Fehlende `.env`-Datei (APP_* Konstanten direkt in Code)
    - Hardcoded Credentials in Config-Dateien
  - **INFO: Modernisierung**
    - Fehlende Type Declarations in Interfaces
    - Fehlende PHPDoc `$variable`-Namen
    - Middleware/DI-Container nicht genutzt
- [ ] Exit-Code: 0 = alles OK, 1 = Warnings, 2 = Critical Findings

#### Phase 14b: `migrate:apply` — Automatische Fixes
- [ ] `bin/gyro migrate:apply [--phase=N] [--dry-run] [--execute]`
- [ ] Dry-Run als Default (wie `db:sync`)
- [ ] Automatisch fixbare Checks:
  - `get_magic_quotes_gpc()` → No-op ersetzen
  - `E_ALL | E_STRICT` → `E_ALL`
  - `isset($value->__toString)` → `method_exists($value, '__toString')`
  - `.env.example` generieren aus gefundenen `APP_*` Konstanten
  - Tote Module-Verzeichnisse auflisten (manuelles Löschen empfehlen)
- [ ] Nicht-automatisch (nur Hinweis):
  - Passwort-Hash-Migration (braucht DB-Zugriff + User-Login)
  - `execute()` → `execute_prepared()` (Query-Kontext nötig)
  - Middleware/DI-Container Integration (architektonisch)
- [ ] Backup-Hinweis vor Ausführung
- [ ] Zusammenfassung: X Dateien geändert, Y Stellen gefixt

#### Phase 14c: Tests
- [ ] MigrationCheck-Interface Tests
- [ ] Jeder Scanner einzeln getestet mit Fixture-Dateien
- [ ] `migrate:check` Integration-Test (Command-Output prüfen)
- [ ] `migrate:apply --dry-run` Test (keine Dateien verändert)

#### Phase 14 Architektur
```
gyro/core/cli/commands/
  migratecheck.cmd.php          # migrate:check Command
  migrateapply.cmd.php          # migrate:apply Command
gyro/core/cli/migration/
  imigrationcheck.cls.php       # Interface für Scanner
  migrationfinding.cls.php      # Finding-Datenklasse (file, line, severity, message, fix)
  migrationrunner.cls.php       # Orchestriert alle Checks
  checks/
    php8compat.check.php        # PHP 8.x Kompatibilitäts-Checks
    security.check.php          # Sicherheits-Checks
    deadmodules.check.php       # Tote Module erkennen
    config.check.php            # Konfigurations-Checks
    modernization.check.php     # Modernisierungs-Empfehlungen
tests/core/
  MigrationCheckTest.php        # Scanner-Tests
  MigrateCommandTest.php        # Command-Tests
```

#### Phase 14 Abhängigkeiten
- Nutzt bestehendes CLI-Framework (Phase 8): `CLICommand`, `CLIKernel`, `CLIOutput`
- Nutzt `Glob`/Datei-Scanning aus `Load`-Klasse
- Kein neues Composer-Package nötig

## Wichtige Dateien für schnellen Einstieg

| Zweck | Pfad |
|-------|------|
| Bootstrap | `gyro/core/start.php` |
| Config | `gyro/core/config.cls.php` |
| Env-Loader | `gyro/core/lib/helpers/env.cls.php` |
| .env Beispiel | `.env.example` |
| CLI Entry Point | `bin/gyro` |
| CLI Kernel | `gyro/core/cli/clikernel.cls.php` |
| CLI Bootstrap | `gyro/core/cli/bootstrap.cli.php` |
| CLI Commands | `gyro/core/cli/commands/` |
| REST-API Controller | `gyro/modules/api/controller/restapi.controller.php` |
| JSON Response Helper | `gyro/modules/api/lib/helpers/jsonresponse.cls.php` |
| OpenAPI Generator | `gyro/modules/api/lib/helpers/openapigenerator.cls.php` |
| Admin Controller | `gyro/modules/admin/controller/admin.controller.php` |
| Admin HTML Helper | `gyro/modules/admin/lib/helpers/adminhtml.cls.php` |
| API Module Init | `gyro/modules/api/start.inc.php` |
| DB-Driver | `gyro/core/model/drivers/mysql/dbdriver.mysql.php` |
| Logger | `gyro/core/lib/components/logger.cls.php` |
| User-Model | `contributions/usermanagement/model/classes/users.model.php` |
| String-Helpers | `gyro/core/lib/helpers/string.cls.php` |
| PSR-4 Namespace-Stubs | `src/` (10 Dateien) |
| PSR-3 Logger Instance | `src/Lib/Components/LoggerInstance.php` |
| PSR-11 Exception | `src/Lib/Components/Exception/ServiceNotFoundException.php` |
| PSR-15 Middleware-Adapter | `src/Core/Middleware/Psr15Adapter.php`, `GyroPsr15Bridge.php` |
| PSR-16 Cache-Adapter | `src/Lib/Cache/Psr16Adapter.php` |
| Namespace-Tests | `tests/core/NamespaceAliasTest.php` |
| PHPUnit-Tests | `tests/core/` (61 Dateien) |
| Test-Bootstrap | `tests/bootstrap.php` |
| SimpleTest (Legacy) | `gyro/modules/simpletest/simpletests/` |
| Routing | `gyro/core/controller/base/routes/` |
| Middleware | `gyro/core/controller/base/middleware/` (4 Dateien) |
| DI-Container | `gyro/core/lib/components/container.cls.php` |
| IMiddleware Interface | `gyro/core/lib/interfaces/imiddleware.cls.php` |
| PHPStan Config | `phpstan.neon.dist` + `phpstan-baseline.neon` |
| Changelog | `CHANGELOG.md` |
| Upgrade-Leitfaden | `UPGRADING.md` |

## Pflichtregeln für Änderungen

Bei **jeder Code-Änderung** müssen folgende Dateien mit-aktualisiert werden:

1. **`CHANGELOG.md`** — Neue Einträge oben einfügen (gleiche Phase oder neue Phase)
2. **`UPGRADING.md`** — Wenn die Änderung bestehende Nutzer betrifft (Breaking Changes, neue Features, neue Konfiguration)
3. **`CLAUDE.md`** — Statistiken, Scorecard, Phase-Details und Feature-Tabelle aktuell halten

**Reihenfolge:** Zuerst Code ändern → Tests grün → Dokumentation updaten → Committen

## Git-Branch

- Entwicklung auf: `claude/analyze-repository-7ADOV`
