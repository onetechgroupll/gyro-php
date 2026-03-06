# Changelog

Alle wesentlichen Änderungen am Gyro-PHP Framework, chronologisch nach Phasen geordnet.

## [Phase 13b] – 2026-03-05

### Verbessert
- **PHPStan Baseline:** 32 weitere Baseline-Einträge für 16 Contribution-Dateien entfernt
  - Fixes: PHPDoc `@param` fehlende `$variable`-Namen, Default-Value-Mismatches (`false` -> `0` für `int`), `enum` -> `string` Type-Hints, undefinierte Variablen, fehlende Konstanten-Guards
  - Betroffene Dateien: `tree.widget`, `unidecode.converter`, `htmltext.cls`, `bookmarking.widget`, 3x `templated.*.block`, `isearchindex.cls`, `jcssmanagerviewfactory`, `compress.base.cmd`, `googlechart.widget`, `dbtabledriverswitch`, `memcache.cls`, `tweets.widget`, `fetch.cmd`, `defines.0.3.inc`

## [Phase 13] – 2026-03-05

### Hinzugefügt
- **Middleware-Pattern:** Formalisiertes Middleware-System für den Request/Response-Pipeline
  - `IMiddleware` Interface (`gyro/core/lib/interfaces/imiddleware.cls.php`)
  - `MiddlewareBase` Basisklasse für eigene Middleware
  - `MiddlewareStack` für globale Middleware-Registrierung mit Prioritäten
  - `MiddlewareRenderDecorator` als Brücke zum bestehenden RenderDecorator-System
  - Per-Route Middleware via `RouteBase::add_middleware()`
  - Globale Middleware via `MiddlewareStack::add($mw, $priority)`
- **DI-Container:** Einfacher Dependency-Injection-Container (`gyro/core/lib/components/container.cls.php`)
  - Singleton-Services (lazy, einmal erstellt)
  - Factory-Services (jedes Mal neu erstellt)
  - Instance-Binding (vorgefertigte Objekte)
  - Container-Injection in Factories
  - Statischer Shortcut: `Container::get_service('name')`
  - `Container::reset_instance()` für Testbarkeit

### Verbessert
- **PHPStan Baseline:** 539 → 53 bekannte Fehler (weitere 50 Fehler in 10 Dateien behoben)
  - PHPDoc `@param` fehlende `$variable`-Namen in 120+ Dateien korrigiert
  - `RenderDecoratorBase`: PHPDoc `@param IRenderDecorator` → `@param IRenderDecorator $content_render_decorator`, `@param int` → `@param int|false`, `@return void` → `@return string|void`
  - `Pager`/`PagerDefaultAdapter`: PHPDoc `@param` Variable-Namen hinzugefuegt, `@param $page_data` entfernt
  - `Arr`: PHPDoc `@param Array`/`@param array`/`@param string` Variable-Namen hinzugefuegt
  - `History`: PHPDoc `@param Integer`/`@param Mixed`/`@param Status|String` Variable-Namen hinzugefuegt
  - `Status`: PHPDoc `@param Mixed` → `@param mixed $message`, `@param String` → `@param string $text`, PEAR_Error-Handling ohne Klassenreferenz
  - `Url`: PHPDoc `@param String`/`@param bool`/`@param string` Variable-Namen hinzugefuegt, `@return url` → `@return Url`
  - `IBlock`: PHPDoc `@param string`/`@param integer` Variable-Namen fuer alle Setter hinzugefuegt
  - `DBSortColumn`: PHPDoc `@param` Variable-Namen hinzugefuegt, `@return unknown` → `@return string`, `@return true` → `@return bool`
  - `DBWhereGroup`: Constructor PHPDoc bereinigt (nicht-existente `$column`/`$operator`/`$value` entfernt), `@return string` → `@return string|null` fuer `get_column()`/`get_operator()`
  - `CacheDBImpl`: PHPDoc `@param Mixed` → `@param mixed $cache_keys`, `@param string` → `@param string $content`
  - `IDBWhere` Interface: `get_column()`/`get_operator()` `@return string` → `@return string|null`
  - PHPDoc Typen korrigiert (`timestamp` → `int`, `String` → `string`, etc.)
  - Cache-Header-Manager: einheitliche `@param int` statt `@param timestamp`
  - `Load::classes_in_directory()`: `@return void` → `@return bool`
  - `Session::set_from()`: `isset($url)` → `!empty($url)`
  - `ConverterUnidecode::encode()`: `@param string` → `@param string|false`
  - `ConverterTextPlaceholders::encode()`: `@param array` → `@param array|false`
  - Widget-Klassen: `render()` PHPDoc `@param int|false $policy` hinzugefuegt (Block, Breadcrumb, DebugBlock)
  - `WidgetBlock::retrieve()`: `@return void` → `@return array`
  - `WidgetDebugBlock`: `APP_START_MICROTIME` mit `defined()` Guard
  - `DBSqlBuilderSelect`/`Count`: `@var DBQuerySelect` fuer `$query` (statt `DBQuery`)
  - `DBSqlBuilderWhereGroup`: `$params` Property hinzugefuegt (war unused), PHPDoc `$where` → `$group`
  - `ITimeStamped`/`DataObjectTimestampedCached`: `@return timestamp` → `@return int`
  - `CommandChainAdapter`: PHPDoc `@param ICommand $cmd` Variable hinzugefuegt
  - `CommandChain::$prev`: `@var CommandChain|ICommand|null` (akzeptiert ICommand)
  - `Common::header()`: `@param string $value` → `string|false`, fehlender `$override` Name
  - `IActionSource::get_actions()`: PHPDoc `@param` Variable-Namen hinzugefuegt
  - `IDBConstraint`/`DBConstraint`: PHPDoc `@param` Variable-Namen korrigiert
  - `DBFilter`: PHPDoc `@param` Variable-Namen hinzugefuegt
  - `Filter`: PHPDoc `@param Url` und `@param string` Variable-Namen hinzugefuegt
  - `RouteBase`: PHPDoc `@param` Variable-Namen hinzugefuegt (`$action`, `$decorators`)
- **PHPDoc-Abdeckung:** ~25-30% → ~45-50% durch flaechendeckende Korrekturen
- **25 neue Tests:** MiddlewareTest (10) + ContainerTest (15)

### Ergebnis
- 386 Tests, 1333 Assertions (alle grün)
- PHPStan Level 3: Baseline 510 bekannte Fehler, 0 neue Fehler

## [Phase 12] – 2026-03-05

### Verbessert
- **PHPStan Level 2 → 3:** Strengere statische Analyse
  - Baseline von 1262 → 525 bekannte Fehler (58% Reduktion)
  - Contribution-Module mit fehlenden Dependencies konsequent excludiert
  - Template-Dateien (`.tpl.php`) von Analyse ausgeschlossen
  - `DBField::$policy` Typ-Annotation korrigiert (`bool` → `int`)
  - `ConstantCacheManager`: Alle 13 PHPStan-Fehler behoben — `@var/@param/@return timestamp` → `int`, Konstruktor-Default `false` → `null` mit Nullable-Type
  - `BlockBase`: Alle 11 PHPStan-Fehler behoben — fehlende `$variable`-Namen in `@param` Tags (Konstruktor + Setter), `render()` PHPDoc `int` → `int|false` für `$policy`
  - Event-System: PHPDoc `@param` in `IEventSink`, `IEventSource`, `EventSource` und 10 EventSink-Implementierungen korrigiert (fehlende `$variable`-Namen); `@return status` → `@return Status` in `IEventSource`
  - 3 `check_preconditions.php` Dateien: `@phpstan-ignore class.notFound` für dynamisch geladene `SystemUpdateInstaller`-Klasse
  - `GyroCookie`: 7 PHPDoc `@param` Tags mit fehlenden `$variable`-Namen korrigiert
  - `StringMBString`: 3 Default-Value-Mismatches (`false` → `''` für `string`-typisierte Parameter), 3 PHPDoc `@param` Tags korrigiert
  - `Session`: Fehlende `return`-Statements in `cookies_enabled()`, 5 PHPDoc `@param` Tags korrigiert
  - `Cast`: PHPDoc-Typos (`mixes`, `date`, fehlende `$variable`-Namen), `@return date` → `@return mixed`
  - `FilterText`: `@return unknown_type` → `@return string`, 4 PHPDoc `@param` Tags korrigiert
  - `PageData`: `Pathstack` → `PathStack` (Casing), `strin` → `string`, `unknown_type` → `array`, `empty()` → null-Check, `@param IDispatcher Dispatcher` korrigiert
  - `ImageToolsGD`: `$sie` → `$size` (Typo/Bug-Fix), `get_extension()` `@return int` → `@return string` + Default-Case, `IImageInformation` `@property` für `$handle`/`$type`
  - `JCSSManagerCompressCSSCsstidyCommand`: `strnig` → `string` Typo, `csstidy` 3rd-Party via `scanFiles` eingebunden
  - `GyroString` (`string.cls.php`): Alle 18 PHPStan-Baseline-Fehler behoben:
    - 10 PHPDoc `@param` Tags mit fehlenden `$variable`-Namen korrigiert (`clear_html`, `unescape`, `currency`, `int`, `number`, `to_lower`, `to_upper`)
    - 3 Default-Value-Mismatches: `$encoding`/`$from`/`$to` PHPDoc `string` → `string|false`, `$count` Default `false` → `0`
    - `preg_replace()` `@return integer` → `@return string|null` (korrekter Rückgabetyp)
    - `$decimal_sep` → `$decimal_point` (undefinierte Variable in `currency()`)
    - Inner named function `_append_u_modifier` → anonyme Closure (PHPStan-kompatibel)
- **PHPDoc-Dokumentation** für öffentliche APIs der wichtigsten Framework-Klassen:
  - `GyroString` (`string.cls.php`): `@param`/`@return` Tags für `starts_with`, `ends_with`, `extract_before`, `extract_after`, `explode_terms`, `substr_word`, `substr_sentence`, `singular_plural`, `plain_ascii`, `localize_number`, `delocalize_number`
  - `Logger` (`logger.cls.php`): PHPDoc für alle 8 PSR-3 Level-Methoden (`emergency` bis `debug`)
  - `DBField` (`dbfield.cls.php`): PHPDoc für Konstruktor, `has_default_value`, `format_select`, `read_from_array`, `quote`, `is_null`

### Ergebnis
- 361 Tests, 1290 Assertions (alle grün)
- PHPStan Level 3: 0 neue Fehler, Baseline aktuell 549 bekannte Fehler (505 Einträge)
- PHPStan-Baseline-Fehler in Interface- und Core-Dateien behoben:
  - PHPDoc `@param` fehlende `$variable`-Namen in 10 Interfaces/Klassen
  - PHPDoc `@return $headers` → `@return array` in `IMailMessageBuilder` + `SingleMessageBuilder`
  - Typ-Korrekturen: `PageDate` → `PageData`, `IDBSQLBuilder` → `IDBSqlBuilder`, `@param timestamp` → `@param int`
  - Default-Value-Mismatches: PHPDoc-Typen um `|false` erweitert wo nötig
  - `CacheRenderDecorator`: Property-Typo `$chache_manager` → `$cache_manager`
  - `CatchAllRoute`: Undeclared property `$path_further` deklariert
  - `MailMessage`: `Status::append()` mit 3 Parametern → `tr()` Wrapper
  - `GyroHttpRequestConfig`: `CONVERTER_JSON` Konstante → String-Literal `'json'`
  - `Translator`: Return-Value von `Load::classes_in_directory()` nicht mehr verwendet
  - `UpfrontCache`: `method_exists()` Check vor `is_cached()` Aufruf
  - `CommandBase`: `@param mixed` → `@var mixed` für Property-PHPDoc
  - `CommandsFactory`: `@return ICommand` → `@return ICommand|false`
  - 16 Widget/View-Klassen: `render($policy = self::NONE)` → `render($policy = 0)` (Default `false` inkompatibel mit `int`)
  - `TemplatePathResolver`: Variable `$ret` vor Schleife initialisiert
  - `TemplateEngineCore`: `resolve_quick_tags()` Return-Wert zu `string` gecastet
  - `DBConstraintUnique`, `DBRelation`: PHPDoc `@param array Associative...` → `@param array $arr_fields Associative...`
  - `DBJoinCondition`: PHPDoc `@param IDBTable One table` → `@param IDBTable $table1 One table`
  - `DBExpression`: PHPDoc `@param mixed $value` entfernt (Parameter existiert nicht)
  - `DBSession::gc()`: `return true` → `return 0` (Rückgabetyp `int|false`)
  - `DBFieldInt::do_format_not_null()`: `return Cast::int($value)` → `return (string)Cast::int($value)`
  - `DBFieldSet::do_format_not_null()`: Return-Wert zu `string` gecastet
  - `DBFieldTime::format_date_value()`: Überflüssigen zweiten Parameter von `GyroDate::mysql_time()` entfernt
  - `DBQueryJoined::$relations`: PHPDoc `@var array` → `@var array|false`
  - `DBSqlBuilderWhere`: Ungenutzten `$params`-Parameter als Property gespeichert
  - `DBWhereFulltext`: PHPDoc `@param enum $mode` → `@param string $mode`
- PHPDoc-Abdeckung von ~15-20% auf ~25-30% verbessert

---

## [Phase 11] – 2026-03-05

### Hinzugefügt
- **Auto-Admin Modul** (`gyro/modules/admin/`): Django-Admin-Style CRUD-Interface,
  automatisch generiert aus DAO-Model-Schemas — keine Templates nötig:
  - `GET /admin/` — Dashboard mit Modell-Übersicht
  - `GET /admin/{table}/` — Datensätze auflisten (Paging, Sorting)
  - `GET /admin/{table}/create` — Neuen Datensatz anlegen (Formular)
  - `GET /admin/{table}/{id}/` — Einzelnen Datensatz anzeigen
  - `GET /admin/{table}/{id}/edit` — Datensatz bearbeiten (Formular)
  - `GET /admin/{table}/{id}/delete` — Datensatz löschen (mit Bestätigung)
- **AdminHtml** Helper (`gyro/modules/admin/lib/helpers/adminhtml.cls.php`):
  - Self-Contained HTML mit eingebettetem CSS (kein CDN nötig)
  - Automatisches Form-Mapping: DBField → HTML-Input-Typ
  - Responsive Design, Breadcrumb-Navigation, Flash-Messages
- **AdminController** (`gyro/modules/admin/controller/admin.controller.php`):
  - Auto-Discovery aller DAO-Modelle
  - Validierung über `DataObjectBase::validate()`
  - INTERNAL-Felder automatisch ausgeblendet
- **34 neue Tests** für Auto-Admin (AdminHtml + AdminController)

### Ergebnis
- 361 Tests, 1290 Assertions (alle grün)

---

## [Phase 10] – 2026-03-05

### Hinzugefügt
- **OpenAPI/Swagger Dokumentation** (`gyro/modules/api/lib/helpers/openapigenerator.cls.php`):
  Automatische Generierung einer vollständigen OpenAPI 3.0.3 Spezifikation aus DAO-Modellen:
  - `GET /api/openapi.json` — Vollständige API-Dokumentation im OpenAPI-Format
  - Typ-Mapping: DBField → OpenAPI-Typen (integer, number, boolean, string mit format)
  - Enum-Werte, maxLength, nullable, required automatisch aus Model-Schema
  - Separate Input-Schemas ohne AUTOINCREMENT Primary Keys (für POST/PUT)
  - Alle CRUD-Endpoints mit Query-Parametern (page, per_page, sort, order)
  - Error-Response-Schemas (400, 404, 405, 422)
  - Kompatibel mit Swagger UI, Redoc und anderen OpenAPI-Tools
- **20 neue Tests** für OpenApiGenerator

### Ergebnis
- 327 Tests, 1199 Assertions (alle grün)

---

## [Phase 9] – 2026-03-05

### Hinzugefügt
- **Auto-REST-API Modul** (`gyro/modules/api/`): Automatisch generierte REST-Endpoints
  für alle DAO-Modelle — keine manuelle Konfiguration nötig:
  - `GET /api` — Alle verfügbaren Endpoints auflisten
  - `GET /api/{table}` — Records auflisten (mit Paging, Filtering, Sorting)
  - `GET /api/{table}/{id}` — Einzelnen Record abrufen
  - `POST /api/{table}` — Neuen Record erstellen (JSON Body)
  - `PUT /api/{table}/{id}` — Record aktualisieren (JSON Body)
  - `DELETE /api/{table}/{id}` — Record löschen
  - `GET /api/{table}/schema` — Tabellenschema als JSON (Felder, Typen, Relations)
- **JsonResponse** Helper (`gyro/modules/api/lib/helpers/jsonresponse.cls.php`):
  - Typ-gerechte JSON-Serialisierung (INT→integer, BOOL→boolean, FLOAT→number)
  - Automatisches Ausfiltern von INTERNAL-Feldern
  - Einheitliche Error-Response-Struktur mit HTTP Status Codes
- **RestApiController** (`gyro/modules/api/controller/restapi.controller.php`):
  - Auto-Discovery: Findet alle DAO-Modelle automatisch über `ModelListCommand`
  - Manuelle Konfiguration: `RestApiController::register_model()` / `::exclude_table()`
  - X-HTTP-Method-Override Support (für Clients ohne PUT/DELETE)
  - Composite Primary Key Support (Pipe-separiert: `val1|val2`)
  - Validierung über das bestehende `DataObjectBase::validate()`
  - Access-Control-ready (INTERNAL-Felder werden nie exponiert)
- **20 neue Tests** für REST-API-Komponenten (JsonResponse + RestApiController)

### Geändert
- **`phpunit.xml.dist`:** Contribution-Testsuite auskommentiert (Verzeichnis existierte nicht,
  verursachte Fehler bei `vendor/bin/phpunit` ohne `--testsuite`)

### Ergebnis
- 307 Tests, 1138 Assertions (alle grün)

---

## [Phase 8] – 2026-03-05

### Hinzugefügt
- **CLI-Tool (`bin/gyro`):** Neues Kommandozeilen-Werkzeug für Gyro-PHP:
  - `gyro help` — Verfügbare Kommandos anzeigen
  - `gyro model:list` — Alle DAO-Modelle mit Tabellennamen, Feldern und Primary Keys auflisten
  - `gyro model:show <table>` — Detailliertes Schema eines Modells anzeigen (Felder, Typen, Defaults, Relations, CREATE TABLE SQL)
  - `gyro db:sync` — Model-Schema mit der Datenbank vergleichen und ALTER TABLE SQL generieren
- **CLI-Kernel** (`gyro/core/cli/clikernel.cls.php`): Command-Routing, Argument-Parsing, farbige Ausgabe
- **CLICommand** Basisklasse für eigene Kommandos
- **CLITable** ASCII-Tabellenrenderer für formatierte CLI-Ausgabe
- **CLI-Bootstrap** (`gyro/core/cli/bootstrap.cli.php`): Framework-Initialisierung ohne HTTP-Kontext
- **Model-Discovery:** Automatische Erkennung aller DAO-Klassen in Core, Modules und Contributions
- **Schema-Introspection:** Liest `create_table_object()` und generiert CREATE TABLE / ALTER TABLE SQL
- **33 neue Tests** für CLI-Komponenten (CLITable, CLIKernel, ModelShowCommand)

### Ergebnis
- 287 Tests, 1066 Assertions (alle grün, 0 Deprecations)

---

## [Phase 7] – 2026-03-05

### Hinzugefügt
- **`.env` Konfiguration:** Neuer `Env`-Loader (`gyro/core/lib/helpers/env.cls.php`) ermöglicht
  Environment-Konfiguration über `.env`-Dateien. Alle `APP_*` Variablen aus der `.env`-Datei
  werden automatisch als PHP-Konstanten definiert — vollständig rückwärtskompatibel.
- **`.env.example`:** Referenzdatei mit allen verfügbaren Konfigurationsvariablen.
- **11 neue Tests** für den Env-Loader (`tests/core/EnvTest.php`).
- **PHPStan Baseline** (`phpstan-baseline.neon`): 1262 bekannte Fehler getracked,
  neue Fehler werden sofort gemeldet.

### Geändert
- **PHPStan Level 1 → 2:** Strengere statische Analyse mit Baseline-Strategie.
- **Composer Classmap entfernt:** Die `autoload.classmap` Konfiguration wurde entfernt,
  da sie einen Pfadkonflikt mit dem Framework-eigenen `Load::directories()` verursachte
  (`include_once` erkannte die gleiche Datei unter verschiedenen Pfaden nicht als identisch).
- **`start.php`:** Lädt jetzt `.env` vor `constants.inc.php` (nur wenn `APP_INCLUDE_ABSPATH` definiert ist).
- **`.gitignore`:** `.env` hinzugefügt.

### Behoben
- **PHP 8.4 Deprecation Warnings:** 3 dynamische Properties gefixt:
  - `DAOStudentsTest::$modificationdate` als explizite Property deklariert
  - `Url::$url` als explizite Property deklariert (verwendet in `__sleep`/`__wakeup`)

### Ergebnis
- 254 Tests, 985 Assertions (alle grün, 0 Deprecations)
- PHPStan Level 2: keine neuen Fehler

---

## [Phase 6] – 2026-03-05

### Hinzugefügt
- **Typed Properties** in 12 Interface-Implementierungen (16 Properties total):
  - `DBResultSet`, `DBResultSetMysql`, `DBResultSetSphinx`, `DBResultSetCountSphinx`
  - `CacheDBImpl`, `CacheFileImpl`, `FileCacheItem`, `ACPuCacheItem`, `MemcacheCacheItem`
  - `ConverterChain`, `ConverterHtmlTidy`, `ConverterUnidecode`
- **`DB::execute_prepared()`** und **`DB::query_prepared()`** — statische Wrapper für
  Prepared Statements auf der DB-Klasse. Vereinfacht die Nutzung gegenüber dem direkten
  Driver-Zugriff.
- **PHPStan Level 1** eingerichtet (`phpstan.neon.dist`).

---

## [Phase 5] – 2026-03-05

### Entfernt
- **`cache.xcache`** — XCache ist seit PHP 7.0 nicht mehr verfügbar (8 Dateien).
- **`javascript.cleditor`** — CLEditor ist seit Jahren abandoned (~36 Dateien).
- **`javascript.wymeditor`** — WYMeditor ist seit Jahren abandoned (~79 Dateien).

### Hinzugefügt
- Weitere SimpleTest → PHPUnit Migrationen.
- PHPDoc für ausgewählte public APIs.

---

## [Phase 4] – 2026-03-05

### Hinzugefügt
- **Type Declarations** in 5 Core-Interfaces und allen Implementierungen:
  - `IDBResultSet` (3 Impl.), `ISessionHandler` (4 Impl.), `ICachePersister` (5 Impl.),
    `IConverter` (12+ Impl.), `IHashAlgorithm` (6 Impl.)
  - Union Types: `array|false`, `string|false`, `int|false`, `ICacheItem|false`, `mixed`
- **Structured Logging** (PSR-3 kompatibel) in `Logger`:
  - Neue Methoden: `Logger::emergency()`, `::alert()`, `::critical()`, `::error()`,
    `::warning()`, `::notice()`, `::info()`, `::debug()`
  - Context-Interpolation: `Logger::error('User {user} failed', ['user' => $name])`
  - JSON-Ausgabe pro Level (z.B. `error-2026-03-05.log`)
  - Exception-Support mit automatischem Stack-Trace
  - Konfigurierbares Minimum-Level: `Logger::set_min_level(Logger::WARNING)`

### Nicht geändert
- `IDBDriver` Type Declarations zurückgestellt (Sphinx-Driver hat fehlende Methoden).
- Namespace-Migration (PSR-4) zurückgestellt (zu großer Breaking Change).

---

## [Phase 3] – 2026-03-05

### Verbessert
- **Session-Security:**
  - `session.cookie_secure = 1` wird bei HTTPS automatisch gesetzt
  - `session.cookie_httponly = true` fest konfiguriert
  - `session.cookie_samesite = Lax` konfiguriert
  - Veralteter PHP < 7.3 `setcookie()` Fallback entfernt
- **CSRF-Token Validierung:** Strikter Vergleich `===` statt `==` in
  `FormHandler::validate()`.

### Geprüft (keine Änderung nötig)
- CSRF-Token-System: Bereits robust (random_bytes, Session-gebunden, DB-gestützt, Einmal-Tokens).
- Input-Handling: Core nutzt `PageData`/`TracedArray`, kein direkter `$_POST`/`$_GET` Zugriff.

---

## [Phase 2] – 2026-03-05

### Hinzugefügt
- **Composer** (`composer.json`): PHPUnit 10.5 als Dev-Dependency, PHP >=8.0.
- **PHPUnit Setup:** `phpunit.xml.dist`, `tests/bootstrap.php`, Test-Verzeichnisstruktur.
- **Prepared Statements** im MySQL-Driver:
  - `$driver->execute_prepared('INSERT INTO t (col) VALUES (?)', ['value'])`
  - `$driver->query_prepared('SELECT * FROM t WHERE id = ?', [42])`
  - Automatische Typerkennung der Parameter (`detect_param_types()`)
- **IDBDriver Interface:** Um `execute_prepared()` und `query_prepared()` erweitert.
- **SimpleTest → PHPUnit Migration** gestartet: `ArrayTest`, `StringTest`, `ValidationTest`.
- `.gitignore`: `/vendor/` hinzugefügt.

### Nicht geändert
- Bestehende `execute()`/`query()` Methoden bleiben unverändert (Rückwärtskompatibilität).
  Sie verwenden weiterhin `mysqli_real_escape_string()`.

---

## [Phase 1] – 2026-03-05

### Behoben (PHP 8.x Kompatibilität)
- **`common.cls.php`:** `preprocess_input()` als No-op implementiert, `transcribe()` entfernt
  (Magic Quotes gibt es seit PHP 7.4 nicht mehr).
- **`start.php`:** `E_ALL | E_STRICT` → `E_ALL` (E_STRICT ist seit PHP 8.0 Teil von E_ALL).
  PHP 5.3 Kompatibilitäts-Check (`defined('E_DEPRECATED')`) entfernt.
- **`cast.cls.php`:** `isset($value->__toString)` → `method_exists($value, '__toString')`
  (PHP 8.0 wirft bei `isset()` auf Magic Methods einen Fehler).

### Verbessert (Sicherheit)
- **Passwort-Hashing:** Default von MD5/PHPass auf **bcrypt** umgestellt:
  - `password_hash()` mit `PASSWORD_BCRYPT`, Cost-Factor 12
  - Neuer Hash-Algorithmus `bcryp` in `contributions/usermanagement/`
  - Automatische Migration: Bestehende Hashes werden beim nächsten Login transparent
    auf bcrypt aktualisiert
- **HTTP Security Headers:**
  - `X-Content-Type-Options: nosniff`
  - `X-Frame-Options: SAMEORIGIN`
  - `Referrer-Policy: strict-origin-when-cross-origin`
  - `Permissions-Policy` (restriktiv)
  - Alle mit `override=false` — Applikationen können sie überschreiben
- **Timing-safe Vergleiche:** `hash_equals()` in MD5 und SHA1 Hash-Klassen.
