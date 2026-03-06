# Gyro-PHP: Vision & Modernisierungsstrategie

> Stand: 2026-03-06 | One Tech Group GmbH

---

## 1. Ehrliche Bestandsaufnahme

### Was Gyro heute ist

Gyro-PHP ist ein **seit 2004 gewachsenes Framework**, das in der One Tech Group GmbH produktiv läuft. Nach 13 Modernisierungsphasen ist es PHP 8.x-kompatibel, hat bcrypt, Prepared Statements, ein CLI-Tool, eine Auto-REST-API, ein Admin-Interface, Middleware, einen DI-Container und 386 Tests.

### Wo Gyro 2026 steht — im Vergleich zum Markt

| Aspekt | Gyro-PHP | Moderner Standard (2025/26) | Gap |
|--------|----------|----------------------------|-----|
| **Namespaces** | Keine (264 globale Klassen) | PSR-4 seit 2013 Pflicht | KRITISCH |
| **Autoloading** | Eigene `Load`-Klasse | Composer PSR-4 | KRITISCH |
| **HTTP-Abstraktion** | Eigene Klassen | PSR-7 / PSR-17 | HOCH |
| **Middleware** | Eigenes Interface | PSR-15 Standard | MITTEL |
| **DI-Container** | Eigene Klasse | PSR-11 Standard | MITTEL |
| **Logger** | PSR-3 *kompatibel* | PSR-3 Interface | NIEDRIG |
| **PHP 8.1+ Features** | Nicht genutzt | Enums, Readonly, Match = Standard | HOCH |
| **Typ-System** | Teilweise (5 Interfaces) | Durchgängig typisiert | HOCH |
| **Testabdeckung** | ~65% (386 Tests) | Gut, aber kein Pest | NIEDRIG |
| **Dokumentation** | CLAUDE.md intern gut | Fehlende öffentliche Docs | HOCH |

### Die unbequeme Wahrheit

**Gyro wird nie mit Laravel konkurrieren** — und muss es auch nicht. Laravel hat 64% Marktanteil, Tausende Contributors, ein Milliarden-Dollar-Ökosystem. Diesen Kampf zu führen ist sinnlos.

Aber Gyro hat **reale Probleme**, die euer Business betreffen:

1. **Niemand neues kann damit arbeiten** — Kein Namespace, kein PSR-4, eigene Konventionen. Jeder neue Entwickler muss alles von Grund auf lernen.
2. **Kein Ökosystem-Anschluss** — Keine Composer-Packages nutzbar ohne Adapter. Kein Monolog, kein Guzzle, kein Doctrine, kein Symfony-Component.
3. **Wissens-Silo** — Das Framework lebt im Kopf weniger Leute. Wenn die gehen, geht das Wissen mit.
4. **Technische Schulden bremsen Feature-Entwicklung** — Jede neue Funktion kämpft gegen 20 Jahre alte Architektur.

---

## 2. Die zentrale strategische Frage

> **Wollen wir Gyro zu einem öffentlich attraktiven Framework machen — oder zu einem gut wartbaren internen Tool?**

### Option A: "Das nächste Slim" — Öffentliches Nischen-Framework

- Voll PSR-kompatibel (PSR-4, PSR-7, PSR-11, PSR-15)
- Eigene Identität: Convention-over-Configuration + Auto-Discovery (Gyros Stärke)
- Open-Source mit Docs, Tutorials, Community
- **Aufwand:** 12-18 Monate Vollzeit, Breaking Changes, parallele Codebasis
- **Risiko:** Hoch. Der Framework-Markt ist gesättigt. Ohne Alleinstellungsmerkmal und Marketing wird es nicht wahrgenommen.

### Option B: "Das moderne Werkzeug" — Internes Framework mit Ökosystem-Anschluss (EMPFOHLEN)

- PSR-4 Namespaces einführen (schrittweise, mit Backward-Compat-Layer)
- Composer-Autoloading als primäres System
- PSR-11/PSR-15 Interfaces für Container und Middleware (Interoperabilität)
- Moderne PHP-Features wo sie Produktivität steigern
- **Aufwand:** 6-9 Monate in Phasen, keine Parallelcodebasis
- **Risiko:** Gering. Jeder Schritt liefert sofort Wert.

### Option C: "Der sanfte Ausstieg" — Schrittweise Migration auf Laravel/Symfony

- Neue Features in Laravel/Symfony bauen
- Gyro als "Legacy-Kern" weiterlaufen lassen
- Über 2-3 Jahre Stück für Stück ablösen
- **Aufwand:** Langfristig am teuersten (zwei Systeme parallel)
- **Risiko:** Mittel. Migrations-Projekte scheitern oft an Halbherzigkeit.

---

## 3. Empfehlung: Option B — Das moderne Werkzeug

### Warum?

1. **Gyro hat echte Stärken**, die es wert sind, erhalten zu werden:
   - Auto-Discovery von Models, Controllern, Modulen
   - Command-Pattern mit Undo-Funktionalität (einzigartig!)
   - Behaviour-Layer für transaktionssichere Operationen
   - Funktionierendes Modulsystem mit 57+ Contributions
   - Auto-REST-API und Auto-Admin aus Model-Schema
   - **20 Jahre Battle-Testing in Produktion**

2. **Die Migration lässt sich inkrementell machen** — kein Big Bang nötig

3. **Jeder Schritt hat sofortigen Business-Wert** — neue Entwickler können schneller starten, externe Packages werden nutzbar, Code wird wartbarer

---

## 4. Roadmap: 6 Phasen in 6-9 Monaten

### Phase 15: Namespace Foundation (4-6 Wochen)

**Ziel:** Composer PSR-4 Autoloading + Namespaces im Core, ohne bestehenden Code zu brechen.

**Strategie: Dual-Loading**

```php
// Neue Dateien in src/ mit Namespaces:
namespace Gyro\Core;

class Config { /* ... */ }

// Backward-Compat in gyro/core/:
// config.cls.php bleibt und wird zum Alias:
class_alias(\Gyro\Core\Config::class, 'Config');
```

**Konkrete Schritte:**
1. `src/` Verzeichnis mit PSR-4 Struktur anlegen
2. `composer.json` um `autoload.psr-4` erweitern: `"Gyro\\": "src/"`
3. Die **10 meistgenutzten Klassen** zuerst migrieren:
   - `Config`, `DB`, `Logger`, `Container`, `Env`
   - `GyroString`, `Arr`, `Cast`, `Common`, `Url`
4. `class_alias()` Brücke für jeden migrierten Klasse
5. `Load`-Klasse um Composer-Fallback erweitern: Wenn Klasse nicht per `Load::` gefunden → Composer-Autoloader fragen

**Ergebnis:** Neue Klassen können Namespaces nutzen. Alter Code läuft unverändert weiter.

**Messbar:** `composer dump-autoload` funktioniert, PHPUnit-Tests grün, `use Gyro\Core\Config;` möglich.

---

### Phase 16: PSR-Interface-Adoption (3-4 Wochen)

**Ziel:** Standard-Interfaces für Container, Middleware und Logger — Interoperabilität mit dem PHP-Ökosystem.

**Konkrete Schritte:**
1. `composer require psr/container psr/http-server-middleware psr/log psr/simple-cache`
2. `Container` implementiert `Psr\Container\ContainerInterface` (PSR-11)
3. `IMiddleware` erweitert `Psr\Http\Server\MiddlewareInterface` (PSR-15) — oder Adapter
4. `Logger` implementiert `Psr\Log\LoggerInterface` (PSR-3)
5. `ICachePersister` → PSR-16 `SimpleCacheInterface` Adapter

**Ergebnis:** Externe Packages (Monolog, PHP-DI, Guzzle Middleware) können eingebunden werden.

**Messbar:** `Container::instance()` besteht PSR-11 Compliance-Test.

---

### Phase 17: Moderne PHP-Features (4-6 Wochen)

**Ziel:** PHP 8.1+ als Minimum. Enums, Readonly, Match, Constructor Promotion wo sie Klarheit schaffen.

**Priorität nach Business-Wert:**

| Feature | Wo einsetzen | Warum |
|---------|-------------|-------|
| **Enums** | DBField-Typen, Log-Levels, HTTP-Methods, Route-Types | Eliminiert Magic Strings, IDE-Support, Typsicherheit |
| **Readonly Properties** | DTOs, Config-Werte, Route-Definitionen | Verhindert versehentliche Mutation |
| **Constructor Promotion** | Alle Klassen mit simplen Konstruktoren | 50% weniger Boilerplate |
| **Match** | Switch-Statements in Convertern, Feldern, Routing | Sicherer (exhaustive), kompakter |
| **Named Arguments** | Komplexe Konstruktoren (DBField, Query-Builder) | Lesbarkeit bei vielen Parametern |

**Konkretes Beispiel — vorher:**
```php
class DBFieldEnum {
    const POLICY_NONE = 0;
    const POLICY_REQUIRED = 1;
    // ...
    private $allowed_values;
    private $default;

    public function __construct($name, $default, $allowed_values, $policy = 0) {
        $this->allowed_values = $allowed_values;
        $this->default = $default;
        // ...
    }
}
```

**Nachher:**
```php
enum FieldPolicy: int {
    case None = 0;
    case Required = 1;
}

class DBFieldEnum {
    public function __construct(
        private readonly string $name,
        private readonly string $default,
        private readonly array $allowed_values,
        private readonly FieldPolicy $policy = FieldPolicy::None,
    ) {}
}
```

**Ergebnis:** Weniger Boilerplate, bessere IDE-Unterstützung, weniger Bugs durch Typsicherheit.

**Messbar:** PHP 8.1 Minimum in `composer.json`, PHPStan Level 4+ ohne neue Fehler.

---

### Phase 18: HTTP-Abstraktion & Testbarkeit (4-6 Wochen)

**Ziel:** Request/Response-Objekte statt globaler Superglobals. Testbare Controller.

**Strategie: PSR-7 inspiriert, nicht dogmatisch**

```php
namespace Gyro\Http;

// Einfache, eigene Request-Klasse (PSR-7-ähnlich aber pragmatisch)
class Request {
    public static function fromGlobals(): self { /* ... */ }
    public function getMethod(): string { /* ... */ }
    public function getPath(): string { /* ... */ }
    public function getQuery(string $key, mixed $default = null): mixed { /* ... */ }
    public function getBody(): array { /* ... */ }
    public function getHeader(string $name): ?string { /* ... */ }
}

class Response {
    public function __construct(
        private int $status = 200,
        private array $headers = [],
        private string $body = '',
    ) {}

    public static function json(mixed $data, int $status = 200): self { /* ... */ }
    public static function html(string $content, int $status = 200): self { /* ... */ }
    public static function redirect(string $url, int $status = 302): self { /* ... */ }
}
```

**Warum nicht voll PSR-7?** PSR-7 ist immutable und stream-basiert — komplex und für Gyros Anwendungsfall Overkill. Eine einfache, testbare Abstraktion bringt 80% des Wertes mit 20% des Aufwands.

**Ergebnis:** Controller sind unit-testbar, keine `$_GET/$_POST/$_SERVER` Abhängigkeiten mehr.

---

### Phase 19: Developer Experience (3-4 Wochen)

**Ziel:** Gyro fühlt sich modern an — für neue und bestehende Entwickler.

**Konkrete Maßnahmen:**

1. **`bin/gyro make:controller`** — Code-Generierung für Controller, Models, Commands
2. **`bin/gyro serve`** — Built-in PHP Development Server (`php -S`)
3. **`bin/gyro test`** — Wrapper für PHPUnit mit sinnvollen Defaults
4. **`bin/gyro routes`** — Alle registrierten Routen anzeigen
5. **Hot-Reload** für Templates in Development-Modus
6. **Bessere Fehlermeldungen** — Stack-Traces mit Code-Context (wie Ignition/Whoops)

**Ergebnis:** Ein `bin/gyro make:controller UserController` erzeugt eine funktionierende Klasse mit korrekten Namespaces und Route-Registration.

---

### Phase 20: Dokumentation & Onboarding (2-3 Wochen)

**Ziel:** Ein neuer Entwickler kann in 30 Minuten eine funktionierende Gyro-App aufsetzen.

1. **Getting Started Guide** (README.md Rewrite)
2. **Architecture Decision Records (ADRs)** — Warum Gyro so ist wie es ist
3. **API-Dokumentation** generiert aus PHPDoc (phpDocumentor oder Doctum)
4. **Beispiel-App** (`examples/blog/`) — komplette CRUD-Anwendung mit REST-API
5. **Migration Guide** — Von Gyro-Legacy auf Gyro-Modern

---

## 5. Was Gyro NICHT werden sollte

- **Kein Laravel-Klon** — Gyros Convention-over-Configuration und Auto-Discovery ist die eigene Identität
- **Kein PSR-Purist** — Standards adoptieren wo sie Wert bringen, nicht als Selbstzweck
- **Kein Framework für Jedermann** — Gyro ist das Werkzeug der One Tech Group. Wenn andere es nutzen: toll. Aber das ist nicht das Ziel.
- **Kein Rewrite von Null** — Der bestehende Code funktioniert. Stück für Stück modernisieren.

---

## 6. Erfolgsmetriken

| Metrik | Heute | Ziel (nach 9 Monaten) |
|--------|-------|----------------------|
| Onboarding-Zeit neuer Entwickler | Wochen | < 1 Tag für erste Route |
| Externe Composer-Packages nutzbar | 0 | Beliebig viele |
| Klassen mit Namespaces | 0 | Core komplett (~50 Klassen) |
| PHP Minimum-Version | 8.0 | 8.1 (dann 8.2) |
| PHPStan Level | 3 | 5+ |
| Testabdeckung | ~65% | >80% |
| Dokumentierte öffentliche APIs | ~45% | >90% |
| `bin/gyro` Commands | 4 | 10+ |

---

## 7. Priorisierung — Was bringt am meisten?

```
              IMPACT
               ▲
        HOCH   │  Phase 15          Phase 17
               │  (Namespaces)      (PHP 8.1+)
               │
               │  Phase 16          Phase 18
               │  (PSR-Interfaces)  (HTTP-Abstraktion)
               │
       MITTEL  │  Phase 20          Phase 19
               │  (Docs)            (DX / CLI)
               │
               └──────────────────────────────────► AUFWAND
                   GERING    MITTEL    HOCH
```

**Reihenfolge nach ROI:**
1. **Phase 15 (Namespaces)** — Größter Impact, enablet alles andere
2. **Phase 16 (PSR-Interfaces)** — Ökosystem-Anschluss, geringer Aufwand
3. **Phase 17 (PHP 8.1+)** — Sofortige DX-Verbesserung
4. **Phase 20 (Docs)** — Onboarding-Killer, günstiger Aufwand
5. **Phase 18 (HTTP)** — Wichtig für Testbarkeit, aber größerer Umbau
6. **Phase 19 (DX/CLI)** — Nice-to-have, poliert das Gesamtbild

---

## 8. Risiken und Mitigations

| Risiko | Wahrscheinlichkeit | Mitigation |
|--------|--------------------|------------|
| Namespace-Migration bricht bestehende Apps | MITTEL | `class_alias()` Brücke, Dual-Loading |
| Zu viel auf einmal, nichts wird fertig | HOCH | Strikte Phasen, jede Phase ist standalone wertvoll |
| PHP 8.1 Minimum schließt alte Server aus | NIEDRIG | PHP 8.0 ist EOL seit Nov 2023, 8.1 EOL Ende 2025 |
| Team hat keine Kapazität | HOCH | Pro Phase nur 3-6 Wochen, parallelisierbar |
| Externe Packages bringen neue Bugs | NIEDRIG | Nur etablierte PSR-Packages, gute Tests |

---

## 9. Quick Wins — Sofort umsetzbar (< 1 Woche)

Diese Maßnahmen erfordern keine Architekturänderungen und liefern sofort Wert:

1. **PHP 8.1 als Minimum** in `composer.json` → ermöglicht Enums und Readonly
2. **`bin/gyro serve`** — 10 Zeilen Code für `php -S localhost:8000 -t public/`
3. **`bin/gyro routes`** — Alle registrierten Routen als Tabelle ausgeben
4. **README.md Rewrite** — Von "Framework seit 2004" zu "Getting Started in 5 Minuten"
5. **`composer require psr/log`** — Logger direkt PSR-3 Interface implementieren lassen
6. **Constructor Promotion** in den 10 neuesten Klassen (Container, Middleware, CLI)

---

## Fazit

Gyro-PHP ist kein hoffnungsloser Fall — es ist ein **funktionierendes Produktionssystem** mit einzigartigen Stärken (Auto-Discovery, Command-Pattern, Battle-Tested seit 20 Jahren). Die Modernisierung muss nicht alles auf einmal ändern.

**Der kritische Pfad ist klar:** Namespaces + Composer-Autoloading → PSR-Interfaces → Moderne PHP-Features. Jeder Schritt macht Gyro für neue Entwickler zugänglicher und für bestehende produktiver.

Die Frage ist nicht "Sollen wir modernisieren?" — sondern **"Wie schnell können wir Phase 15 starten?"**

---

## 10. Claude-Prompts für jede Phase

> Copy-Paste-fertige Prompts für Claude Code. Jeder Prompt ist so geschrieben, dass Claude die Phase eigenständig umsetzen kann. Vor jeder Phase sollten alle Tests grün sein (`vendor/bin/phpunit`).

---

### Prompt: Phase 15 — Namespace Foundation

```
## Aufgabe: Phase 15 — Namespace Foundation für Gyro-PHP

Führe PSR-4 Namespaces im Gyro-PHP Framework ein, ohne bestehenden Code zu brechen.

### Strategie: Dual-Loading mit class_alias()

1. **Verzeichnis `src/` anlegen** mit folgender Struktur:
   ```
   src/
     Core/
       Config.php
       DB.php
       Common.php
     Lib/
       Components/
         Logger.php
         Container.php
       Helpers/
         GyroString.php
         Arr.php
         Cast.php
         Env.php
         Url.php
   ```

2. **`composer.json` erweitern:**
   - `"autoload": { "psr-4": { "Gyro\\": "src/" } }` hinzufügen
   - PHP Minimum auf `">=8.1"` setzen
   - `composer dump-autoload` ausführen

3. **Die 10 meistgenutzten Klassen migrieren** (in dieser Reihenfolge):
   - `Env` → `Gyro\Lib\Helpers\Env`
   - `Logger` → `Gyro\Lib\Components\Logger`
   - `Container` → `Gyro\Lib\Components\Container`
   - `GyroString` → `Gyro\Lib\Helpers\GyroString`
   - `Arr` → `Gyro\Lib\Helpers\Arr`
   - `Cast` → `Gyro\Lib\Helpers\Cast`
   - `Url` → `Gyro\Lib\Helpers\Url`
   - `Config` → `Gyro\Core\Config`
   - `Common` → `Gyro\Core\Common`
   - `DB` → `Gyro\Core\DB`

4. **Für jede migrierte Klasse:**
   - Kopiere die Klasse nach `src/` mit `namespace Gyro\...;`
   - Füge am Ende der Original-Datei (z.B. `gyro/core/lib/helpers/env.cls.php`) hinzu:
     ```php
     // Backward compatibility alias
     class_alias(\Gyro\Lib\Helpers\Env::class, 'Env');
     ```
   - Die Original-Datei soll NUR noch den `class_alias()` Aufruf und ggf. ein `require_once` enthalten
   - Stelle sicher, dass die neue Datei alle Abhängigkeiten via `use` importiert

5. **`Load`-Klasse erweitern:**
   - In `gyro/core/load.cls.php`: Wenn eine Klasse nicht via `Load::` gefunden wird,
     an Composers `spl_autoload` Mechanismus delegieren
   - Composer Autoloader in `start.php` registrieren: `require_once __DIR__ . '/../../vendor/autoload.php';`

6. **Tests anpassen:**
   - `tests/bootstrap.php`: Composer Autoloader einbinden
   - Alle bestehenden Tests müssen weiterhin grün sein (globale Klassennamen via class_alias)
   - Neue Tests für die Namespace-Klassen schreiben (mindestens: `use Gyro\Core\Config;` funktioniert)

### Regeln:
- KEIN bestehender Code darf brechen — `class_alias()` stellt sicher, dass `Config` und `\Gyro\Core\Config` beide funktionieren
- Jede Klasse einzeln migrieren und nach jeder Migration Tests laufen lassen
- PHPStan muss weiterhin 0 neue Fehler haben
- CLAUDE.md, CHANGELOG.md, UPGRADING.md aktualisieren
```

---

### Prompt: Phase 16 — PSR-Interface-Adoption

```
## Aufgabe: Phase 16 — PSR-Interface-Adoption für Gyro-PHP

Implementiere Standard-PSR-Interfaces für Container, Middleware, Logger und Cache.

### Voraussetzung:
- Phase 15 (Namespaces) muss abgeschlossen sein
- `src/` Verzeichnis mit PSR-4 Autoloading existiert

### Schritte:

1. **Composer-Packages installieren:**
   ```bash
   composer require psr/container psr/log psr/http-server-middleware psr/http-message psr/simple-cache
   ```

2. **Container → PSR-11:**
   - `Gyro\Lib\Components\Container` soll `Psr\Container\ContainerInterface` implementieren
   - `get($id)` muss `Psr\Container\NotFoundExceptionInterface` werfen wenn nicht gefunden
   - `has($id)` muss `bool` zurückgeben
   - Bestehende Methoden (`singleton()`, `factory()`, `bind()`) bleiben erhalten
   - Tests: PSR-11 Compliance prüfen (get/has/NotFound)

3. **Logger → PSR-3:**
   - `Gyro\Lib\Components\Logger` soll `Psr\Log\LoggerInterface` implementieren
   - Die bestehenden Methoden (`emergency()`, `alert()`, `critical()`, `error()`, `warning()`, `notice()`, `info()`, `debug()`) haben bereits die richtigen Signaturen — prüfe ob sie PSR-3 konform sind
   - `log($level, $message, array $context = [])` muss `Psr\Log\LogLevel` Konstanten akzeptieren
   - Statische Methoden als Facade beibehalten, intern auf Instanz delegieren
   - Tests: Alle 8 Log-Levels, Context-Interpolation, Exception-Handling

4. **Middleware → PSR-15 Adapter:**
   - NICHT das bestehende `IMiddleware` Interface ändern (zu viele Abhängigkeiten)
   - Stattdessen einen **Adapter** erstellen: `Gyro\Core\Middleware\Psr15Adapter`
   - Der Adapter wrapped ein `Psr\Http\Server\MiddlewareInterface` in ein `IMiddleware`
   - Und umgekehrt: `Gyro\Core\Middleware\GyroPsr15Bridge` wrapped ein `IMiddleware` als PSR-15
   - So können externe PSR-15 Middleware-Packages in Gyro genutzt werden
   - Tests: Adapter in beide Richtungen testen

5. **Cache → PSR-16 Adapter:**
   - `Gyro\Lib\Cache\Psr16Adapter` der `Psr\SimpleCache\CacheInterface` implementiert
   - Intern nutzt er `ICachePersister` von Gyro
   - Methoden: `get()`, `set()`, `delete()`, `clear()`, `has()`, `getMultiple()`, `setMultiple()`, `deleteMultiple()`
   - Tests: Grundlegende PSR-16 Operations

### Regeln:
- Bestehende Interfaces und Klassen NICHT brechen
- Adapter-Pattern nutzen wo nötig (Brücke zwischen Gyro und PSR)
- Alle 386+ bestehenden Tests müssen grün bleiben
- Neue Tests für jede PSR-Integration (mindestens 20 neue Tests)
- CLAUDE.md, CHANGELOG.md, UPGRADING.md aktualisieren
```

---

### Prompt: Phase 17 — Moderne PHP 8.1+ Features

```
## Aufgabe: Phase 17 — PHP 8.1+ Features in Gyro-PHP einführen

Modernisiere den Gyro-PHP Code mit PHP 8.1+ Sprachfeatures wo sie Klarheit und Sicherheit bringen.

### Voraussetzungen:
- PHP Minimum ist 8.1 (in composer.json aus Phase 15 bereits gesetzt)
- Namespaces sind eingeführt (Phase 15)

### 1. Enums einführen (Priorität HOCH)

Identifiziere alle Stellen im Code, die Konstanten-Gruppen für diskrete Werte nutzen,
und konvertiere sie zu PHP 8.1 Backed Enums:

**Pflicht-Kandidaten:**
- **Log-Levels** in `Logger` → `enum LogLevel: string { case Emergency = 'emergency'; ... }`
- **DBField-Policies** (NONE, REQUIRED, etc.) → `enum FieldPolicy: int { ... }`
- **HTTP-Methods** (GET, POST, PUT, DELETE) → `enum HttpMethod: string { ... }`
- **DBField-Typen** (INT, TEXT, BOOL, ENUM, etc.) → `enum FieldType: string { ... }`
- **Route-Types** (EXACT, PARAMETERIZED, etc.) → `enum RouteType: string { ... }`
- **Hash-Algorithmus-Typen** → `enum HashType: string { ... }`
- **Cache-Strategien** → `enum CacheStrategy: string { ... }`

**Für jeden Enum:**
- Erstelle die Enum-Klasse in `src/` mit korrektem Namespace
- Ersetze Konstanten-Referenzen schrittweise (alte Konstanten als deprecated beibehalten)
- Tests anpassen/erweitern

### 2. Readonly Properties (Priorität HOCH)

Konvertiere Properties die nach dem Konstruktor nie geändert werden:
- `Route`-Klassen: `$path`, `$controller`, `$action`
- `DBField`-Klassen: `$name`, `$type`, `$default`
- `MigrationFinding`: `$file`, `$line`, `$severity`, `$message`
- `Container`-Bindings: nach Registration nicht änderbar
- CLI-Command Metadaten: `$name`, `$description`

### 3. Constructor Promotion (Priorität MITTEL)

Vereinfache alle Konstruktoren, die nur Properties zuweisen:
```php
// Vorher:
class ExactMatchRoute {
    private string $path;
    private string $controller;
    public function __construct(string $path, string $controller) {
        $this->path = $path;
        $this->controller = $controller;
    }
}

// Nachher:
class ExactMatchRoute {
    public function __construct(
        private readonly string $path,
        private readonly string $controller,
    ) {}
}
```

Kandidaten finden mit: `grep -r "function __construct" gyro/ src/ --include="*.php" -l`

### 4. Match Expressions (Priorität MITTEL)

Ersetze `switch`-Statements durch `match` wo möglich:
- Typ-Mappings in `JsonResponse` (DBField-Type → JSON-Type)
- `OpenApiGenerator` Field-Type zu OpenAPI-Type Mapping
- `AdminHtml` Field-Type zu Input-Type Mapping
- `detect_param_types()` im MySQL-Driver

### 5. Named Arguments (Priorität NIEDRIG)

Nutze Named Arguments bei komplexen Konstruktor-Aufrufen, besonders:
- `DBField*` Instanziierungen mit vielen optionalen Parametern
- `Route`-Definitionen
- `Logger`-Konfiguration

### Regeln:
- PHP Minimum 8.1 in `composer.json` sicherstellen
- Alte Konstanten als `@deprecated` markieren, NICHT sofort löschen
- Jeden Enum einzeln einführen, nach jedem Tests laufen lassen
- PHPStan Level ggf. auf 4 erhöhen wenn Enums Typ-Sicherheit verbessern
- Mindestens 30 neue Tests für Enum-Verhalten
- CLAUDE.md, CHANGELOG.md, UPGRADING.md aktualisieren
```

---

### Prompt: Phase 18 — HTTP-Abstraktion & Testbarkeit

```
## Aufgabe: Phase 18 — HTTP Request/Response Abstraktion für Gyro-PHP

Erstelle testbare HTTP-Abstraktionen, die globale Superglobals kapseln.

### Voraussetzungen:
- Namespaces (Phase 15) und PSR-Interfaces (Phase 16) sind abgeschlossen
- Enums für HTTP-Methods existieren (Phase 17)

### 1. Request-Klasse erstellen

**Datei:** `src/Http/Request.php`

```php
namespace Gyro\Http;

class Request {
    // Factory aus Superglobals
    public static function fromGlobals(): self;

    // Getter
    public function getMethod(): HttpMethod;
    public function getPath(): string;
    public function getUri(): string;
    public function getQuery(string $key, mixed $default = null): mixed;
    public function getAllQuery(): array;
    public function getPost(string $key, mixed $default = null): mixed;
    public function getAllPost(): array;
    public function getBody(): string;       // Raw body
    public function getJsonBody(): array;    // Parsed JSON
    public function getHeader(string $name): ?string;
    public function getAllHeaders(): array;
    public function getCookie(string $name): ?string;
    public function getServer(string $key): ?string;
    public function isHttps(): bool;
    public function isAjax(): bool;
    public function getClientIp(): string;

    // Für Tests: manuell konstruierbar
    public static function create(
        HttpMethod $method,
        string $path,
        array $query = [],
        array $post = [],
        array $headers = [],
        string $body = '',
    ): self;
}
```

### 2. Response-Klasse erstellen

**Datei:** `src/Http/Response.php`

```php
namespace Gyro\Http;

class Response {
    public function __construct(
        private int $status = 200,
        private string $body = '',
        private array $headers = [],
    ) {}

    // Factory Methods
    public static function html(string $content, int $status = 200): self;
    public static function json(mixed $data, int $status = 200): self;
    public static function redirect(string $url, int $status = 302): self;
    public static function notFound(string $message = 'Not Found'): self;
    public static function error(string $message = 'Internal Server Error', int $status = 500): self;

    // Header Management
    public function withHeader(string $name, string $value): self;
    public function withCookie(string $name, string $value, array $options = []): self;

    // Senden
    public function send(): void;  // http_response_code + headers + echo body

    // Getter für Tests
    public function getStatus(): int;
    public function getBody(): string;
    public function getHeader(string $name): ?string;
    public function getHeaders(): array;
}
```

### 3. Integration in bestehenden Code

- **`RequestInfo`** Klasse (`gyro/core/controller/base/requestinfo.cls.php`):
  Intern auf `Request` umstellen, aber `RequestInfo` als Facade beibehalten
- **`RestApiController`**: `Request`-Objekt statt `$_GET/$_POST/$_SERVER`
- **`AdminController`**: `Request`-Objekt nutzen
- **`pageviewbase.cls.php`**: `Response`-Objekt für Header-Management
- **Controller-Basis:** `ControllerBase::handle(Request $request): Response`

### 4. Tests

Mindestens 40 neue Tests:
- `RequestTest`: fromGlobals, create, alle Getter, Edge Cases (leerer Body, fehlende Header)
- `ResponseTest`: Factories, Header-Management, send() Output (output buffering)
- `IntegrationTest`: Request → Controller → Response Roundtrip mit Mock-Daten
- Bestehende Controller-Tests auf Request/Response umstellen

### Regeln:
- Response ist NICHT immutable (Pragmatismus über Purismus)
- `Request::fromGlobals()` ist der einzige Ort der `$_GET/$_POST/$_SERVER` liest
- Kein PSR-7 implementieren — eigene schlanke Abstraktion
- Alle bestehenden Tests müssen grün bleiben
- CLAUDE.md, CHANGELOG.md, UPGRADING.md aktualisieren
```

---

### Prompt: Phase 19 — Developer Experience

```
## Aufgabe: Phase 19 — Developer Experience für Gyro-PHP verbessern

Erweitere das CLI-Tool `bin/gyro` um produktivitätssteigernde Commands.

### Voraussetzungen:
- CLI-Framework (Phase 8) ist vorhanden
- Namespaces (Phase 15) sind eingeführt
- HTTP-Abstraktion (Phase 18) existiert

### 1. `bin/gyro serve` — Development Server

**Datei:** `gyro/core/cli/commands/serve.cmd.php`

- Startet `php -S localhost:8000 -t public/` (oder konfigurierbarer Port)
- Optionen: `--port=8080`, `--host=0.0.0.0`
- Zeigt URL in der Konsole an mit farbiger Ausgabe
- Fängt Ctrl+C sauber ab

### 2. `bin/gyro routes` — Route-Übersicht

**Datei:** `gyro/core/cli/commands/routes.cmd.php`

- Listet alle registrierten Routen als ASCII-Tabelle (nutze CLITable aus Phase 8)
- Spalten: Method | Path | Controller | Action | Middleware
- Optionen: `--filter=api` (nur Routen die "api" enthalten), `--method=GET`
- Nutzt das Routing-System um alle Routen zu sammeln

### 3. `bin/gyro make:controller` — Code-Generierung

**Datei:** `gyro/core/cli/commands/makecontroller.cmd.php`

- `bin/gyro make:controller UserController`
- Generiert Controller-Datei mit korrektem Namespace, `use` Statements, Route-Registration
- Template:
  ```php
  namespace Gyro\App\Controller;

  use Gyro\Http\Request;
  use Gyro\Http\Response;

  class UserController {
      public function index(Request $request): Response { ... }
      public function show(Request $request, string $id): Response { ... }
  }
  ```
- Optionen: `--crud` (generiert index/show/create/store/edit/update/delete), `--api` (nur JSON Responses)

### 4. `bin/gyro make:model` — Model-Generierung

**Datei:** `gyro/core/cli/commands/makemodel.cmd.php`

- `bin/gyro make:model Product --fields="name:text,price:float,active:bool"`
- Generiert DAO-Klasse mit DBField-Definitionen
- Optionen: `--timestamps` (created_at/updated_at), `--soft-delete`

### 5. `bin/gyro make:command` — Command-Generierung

**Datei:** `gyro/core/cli/commands/makecommand.cmd.php`

- `bin/gyro make:command cache:clear`
- Generiert CLICommand-Subklasse mit Name, Description, execute()

### 6. `bin/gyro test` — Test-Wrapper

**Datei:** `gyro/core/cli/commands/test.cmd.php`

- `bin/gyro test` → `vendor/bin/phpunit`
- `bin/gyro test --filter=UserTest` → Weiterleitung der Argumente
- `bin/gyro test --coverage` → `vendor/bin/phpunit --coverage-text`
- Farbige Zusammenfassung am Ende

### 7. Bessere Fehlerseiten

**Datei:** `src/Http/ErrorHandler.php`

- Im Development-Modus: HTML-Fehlerseite mit:
  - Exception Message + Klasse
  - Stack-Trace mit Code-Context (5 Zeilen vor/nach der fehlerhaften Zeile)
  - Request-Informationen (Method, URL, Headers)
  - Eingebettetes CSS (kein CDN)
- Im Production-Modus: Generische Fehlerseite ohne Details
- Erkennung via `Env::get('APP_ENV', 'production')` oder `APP_DEBUG` Konstante

### Tests:
- `ServeCommandTest`: Prüfe dass der richtige PHP-Befehl zusammengebaut wird
- `RoutesCommandTest`: Mock-Routen registrieren, Output prüfen
- `MakeControllerTest`: Generierte Datei hat korrekten Inhalt
- `MakeModelTest`: Generierte DAO hat korrekte Felder
- `ErrorHandlerTest`: Dev vs Prod Mode, Exception-Rendering

### Regeln:
- Alle `make:*` Commands schreiben in `contributions/` oder einen konfigurierbaren App-Pfad
- Code-Templates als Strings in den Command-Klassen (kein Template-Engine nötig)
- `bin/gyro help` soll alle neuen Commands auflisten
- CLAUDE.md, CHANGELOG.md, UPGRADING.md aktualisieren
```

---

### Prompt: Phase 20 — Dokumentation & Onboarding

```
## Aufgabe: Phase 20 — Dokumentation & Onboarding für Gyro-PHP

Erstelle eine vollständige Dokumentation, die neuen Entwicklern den Einstieg in < 30 Minuten ermöglicht.

### Voraussetzungen:
- Alle vorherigen Phasen (15-19) abgeschlossen
- Framework hat Namespaces, PSR-Interfaces, moderne PHP-Features, CLI-Tools

### 1. README.md komplett neu schreiben

Struktur:
```markdown
# Gyro-PHP

> Convention-over-Configuration PHP Framework mit Auto-Discovery

## Quick Start (5 Minuten)
- composer create-project / git clone
- bin/gyro serve
- Erste Route erstellen

## Features
- Auto-REST-API aus Model-Schema
- Auto-Admin Interface (Django-Style)
- CLI-Tool mit Code-Generierung
- Middleware + DI-Container (PSR-11/PSR-15)
- 386+ Tests, PHPStan Level 3+

## Requirements
- PHP 8.1+
- MySQL/MariaDB
- Composer

## Installation
## Configuration (.env)
## Directory Structure
## Links to detailed docs
```

### 2. `docs/` Verzeichnis mit Detail-Dokumentation

Erstelle folgende Markdown-Dateien:

- **`docs/getting-started.md`** — Installation, Konfiguration, erste App
- **`docs/routing.md`** — Routen definieren, Parameter, Middleware pro Route
- **`docs/controllers.md`** — Request/Response, Controller erstellen, Dependency Injection
- **`docs/models.md`** — DAO-Klassen, DBFields, Queries, Relations
- **`docs/rest-api.md`** — Auto-REST-API aktivieren, Endpoints, Filtering, OpenAPI
- **`docs/admin.md`** — Auto-Admin aktivieren, Customization
- **`docs/cli.md`** — Alle bin/gyro Commands, eigene Commands erstellen
- **`docs/middleware.md`** — Middleware erstellen, globale vs route-level
- **`docs/container.md`** — DI-Container, Services registrieren/auflösen
- **`docs/testing.md`** — Tests schreiben, Mock-DB, Test-Bootstrap
- **`docs/configuration.md`** — .env Variablen, Config-Klasse, Konstanten
- **`docs/security.md`** — bcrypt, CSRF, Prepared Statements, Security Headers
- **`docs/migration-from-legacy.md`** — Upgrade-Pfad von altem Gyro

### 3. Beispiel-App: `examples/blog/`

Eine komplette Mini-Blog-Anwendung:

```
examples/blog/
  public/index.php          # Entry Point
  .env                      # Beispiel-Konfiguration
  app/
    controllers/
      PostController.php    # CRUD für Blog-Posts
      ApiController.php     # JSON API
    models/
      posts.model.php       # DAO mit title, content, author, created_at
  README.md                 # Anleitung zum Starten
```

Features der Beispiel-App:
- Model mit 4-5 Feldern
- Controller mit CRUD Views
- REST-API Endpoint
- Admin-Interface aktiviert
- Middleware-Beispiel (Logging, Auth-Check)
- `.env` Konfiguration

### 4. Architecture Decision Records (ADRs)

**Verzeichnis:** `docs/adr/`

Erstelle ADRs für die wichtigsten Architektur-Entscheidungen:
- `001-no-namespaces-then-dual-loading.md` — Warum erst keine Namespaces, dann Dual-Loading
- `002-own-autoloading-vs-psr4.md` — Load-Klasse vs Composer
- `003-auto-discovery-pattern.md` — Warum Convention-over-Configuration
- `004-command-pattern-with-undo.md` — Einzigartiges Feature, Motivation
- `005-custom-interfaces-with-psr-adapters.md` — Pragmatismus über Purismus

Format pro ADR:
```markdown
# ADR-001: [Titel]
**Status:** Accepted | Superseded | Deprecated
**Datum:** YYYY-MM-DD
**Kontext:** Was war das Problem?
**Entscheidung:** Was wurde entschieden?
**Konsequenzen:** Was folgt daraus? (positiv + negativ)
```

### 5. PHPDoc-Vervollständigung

- Alle `public` und `protected` Methoden in `src/` müssen PHPDoc haben
- Mindestens: `@param`, `@return`, einzeiliger Summary
- `@throws` wo Exceptions geworfen werden
- `@deprecated` für alte Methoden mit Verweis auf Ersatz
- Ziel: >90% PHPDoc-Coverage in `src/`

### Regeln:
- Dokumentation ist auf DEUTSCH (Framework wird intern genutzt)
- Code-Beispiele müssen funktionieren (keine Pseudo-Code-Snippets)
- Jede Docs-Datei hat ein Inhaltsverzeichnis am Anfang
- README.md soll unter 200 Zeilen bleiben (Details in docs/)
- CLAUDE.md, CHANGELOG.md aktualisieren
```

---

### Prompt: Quick Wins — Sofort umsetzbar

```
## Aufgabe: Quick Wins für Gyro-PHP (< 1 Woche Aufwand)

Setze die folgenden 6 Quick Wins um, die keine Architekturänderungen erfordern
und sofort Wert liefern. Jeder Quick Win ist unabhängig.

### 1. PHP 8.1 als Minimum

- `composer.json`: `"php": ">=8.1"` setzen
- PHPUnit/PHPStan Configs prüfen
- `composer validate` ausführen

### 2. `bin/gyro serve`

- Neuer CLI-Command in `gyro/core/cli/commands/serve.cmd.php`
- Startet `php -S localhost:8000` mit konfigurierbarem Port
- Registration im CLIKernel
- 1 Test der den Command-Output prüft

### 3. `bin/gyro routes`

- Neuer CLI-Command in `gyro/core/cli/commands/routes.cmd.php`
- Sammelt alle registrierten Routen und zeigt sie als CLITable
- Spalten: Method | Path | Controller
- Registration im CLIKernel
- 1 Test mit Mock-Routen

### 4. `composer require psr/log` + Logger PSR-3

- `composer require psr/log`
- `Logger`-Klasse: `implements \Psr\Log\LoggerInterface`
- Prüfe dass alle 8 Methoden-Signaturen kompatibel sind
- `log()` Methode muss `Psr\Log\LogLevel` Konstanten akzeptieren
- 3 Tests für PSR-3 Compliance

### 5. Constructor Promotion in neuesten Klassen

Konvertiere Constructor Promotion in diesen 6 Klassen (alle aus Phase 8-13):
- `Container` (`gyro/core/lib/components/container.cls.php`)
- `MiddlewareStack` (`gyro/core/controller/base/middleware/middlewarestack.cls.php`)
- `MiddlewareRenderDecorator` (`gyro/core/controller/base/middleware/middlewarerenderdecorator.cls.php`)
- `CLIKernel` (`gyro/core/cli/clikernel.cls.php`)
- `CLITable` (`gyro/core/cli/helpers/clitable.cls.php`)
- `MigrationFinding` (wenn aus Phase 14 vorhanden)

### 6. `README.md` Rewrite

Ersetze die bestehende README (falls vorhanden) mit einem modernen Format:
- Projekttitel + einzeiliger Pitch
- Quick Start (3 Schritte)
- Feature-Liste (Bullet Points)
- Requirements
- Link zu CHANGELOG.md und UPGRADING.md
- Maximal 80 Zeilen

### Regeln:
- Jeder Quick Win einzeln committen
- Alle bestehenden Tests müssen nach jedem Schritt grün bleiben
- CLAUDE.md, CHANGELOG.md aktualisieren
```
