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
