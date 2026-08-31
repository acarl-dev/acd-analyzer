# ADR-0017: Error Handling & Logging (M1.7)

**Status:** Accepted

**Datum:** 2026-09-01

**Kontext:** M1.7 - Error Handling & Logging

---

## Context

Der Crawler muss mit verschiedenen Fehlerszenarien umgehen können:

- HTTP-Fehler (4xx, 5xx)
- Netzwerkfehler (Timeouts, Connection Failures, DNS Failures)
- Renderer-Fehler (Timeouts, Unavailable, Failed)
- Redirect-Probleme (Loops, Limit Exceeded)
- External Resource Errors (robots.txt, sitemap failures)

Vor M1.7 wurden Fehler inkonsistent behandelt:
- Teilweise führten Fehler zum Crawl-Abbruch
- Fehler wurden nicht kategorisiert
- Keine Unterscheidung zwischen fatal und recoverable errors
- Fehlende Sichtbarkeit im Dashboard

**Ziel:** Fehlerzustände sollen einheitlich erfasst, kategorisiert und im Dashboard nachvollziehbar werden.

---

## Decision

### 1. Erweitertes Error Model

Die bestehende `crawl_errors` Tabelle wird erweitert um:

```php
code         // string: error_code (http_4xx, http_5xx, timeout, ...)
severity     // string: low | medium | high | critical
source       // string: crawler | http | renderer | robots | sitemap | parser
context      // json: zusätzliche Fehlerdetails
occurred_at  // timestamp: wann der Fehler auftrat
```

Bestehende Felder bleiben erhalten:
- url
- message
- depth
- timestamps

### 2. Error Codes (Enum)

Definierte Error Codes:

**HTTP Errors:**
- `http_4xx` – Client errors (404, 403, etc.)
- `http_5xx` – Server errors (500, 502, etc.)

**Network Errors:**
- `timeout` – Request timeout
- `connection_failed` – Connection refused, network unreachable
- `dns_failed` – DNS resolution failure

**Redirect Errors:**
- `redirect_loop` – Detected redirect loop
- `redirect_limit_exceeded` – More than 10 redirects

**Renderer Errors:**
- `renderer_timeout` – Renderer request timeout
- `renderer_unavailable` – Renderer service not reachable
- `renderer_failed` – Renderer returned error

**External Resource Errors:**
- `robots_fetch_failed` – robots.txt fetch failed
- `sitemap_fetch_failed` – Sitemap fetch failed
- `sitemap_parse_failed` – Invalid sitemap XML

**Other:**
- `parse_failed` – HTML parsing error
- `unknown` – Unclassified error

### 3. Error Severity (Enum)

Automatische Severity-Bestimmung:

- **CRITICAL:** (Reserved for future use)
- **HIGH:** http_5xx, connection_failed, dns_failed
- **MEDIUM:** http_4xx, timeout, redirect_loop, redirect_limit_exceeded
- **LOW:** renderer errors, robots/sitemap errors, parse_failed

### 4. Error Source (Enum)

Kategorisierung der Fehlerquelle:

- `crawler` – General crawler logic
- `http` – HTTP client errors
- `renderer` – Browser renderer errors
- `robots` – robots.txt related
- `sitemap` – Sitemap related
- `parser` – HTML parsing

### 5. Recoverable vs. Fatal Errors

**Fatal Errors** (stoppen den Crawl):
- Start-URL returns 4xx/5xx
- Start-URL connection failed
- Start-URL DNS failed
- Start-URL timeout

**Recoverable Errors** (werden geloggt, Crawl läuft weiter):
- Subpage HTTP errors
- Renderer failures (mit HTTP-Fallback)
- robots.txt/sitemap failures
- Redirect limit exceeded
- Parser errors

**Regel:** Ein Fehler darf nicht automatisch den ganzen Crawl abbrechen, wenn nur eine einzelne Unterseite betroffen ist.

### 6. ErrorService

Zentraler Service für einheitliche Fehlererfassung:

```php
ErrorService::recordError(
    crawlRun: CrawlRun,
    code: ErrorCode,
    source: ErrorSource,
    url: string,
    message: string,
    context: ?array = null,
    depth: ?int = null,
    severity: ?ErrorSeverity = null  // auto-determined if null
): CrawlError
```

```php
ErrorService::isFatal(
    code: ErrorCode,
    url: string,
    startUrl: string
): bool
```

### 7. Context JSON Field

`context` speichert zusätzliche strukturierte Informationen:

**Beispiele:**
```json
// HTTP Error
{
  "status_code": 404,
  "final_url": "https://example.com/redirected"
}

// Connection Error
{
  "exception": "Illuminate\\Http\\Client\\ConnectionException"
}

// Redirect Limit
{
  "redirect_count": 10,
  "redirect_chain": [...]
}

// Renderer Error
{
  "exception": "GuzzleHttp\\Exception\\ConnectException",
  "timeout": 20
}
```

### 8. Frontend: Errors Tab

Neuer "Errors" Tab im CrawlRun Dashboard:

**Features:**
- Tabelle: Severity | Source | Code | URL | Message | Zeit
- Filter nach Severity, Source, Code
- Pagination
- Context-Details expandierbar
- Farbcodierung nach Severity
- Badge für Source

### 9. Integration in CrawlerService

**HTTP Errors:**
```php
if ($downloadedPage->statusCode >= 400) {
    $errorCode = $statusCode >= 500 ? ErrorCode::HTTP_5XX : ErrorCode::HTTP_4XX;
    
    $this->errorService->recordError(...);
    
    if ($this->errorService->isFatal($errorCode, $currentUrl, $startUrl)) {
        throw new \RuntimeException("Start URL returned {$statusCode}");
    }
    
    continue; // Skip page but continue crawl
}
```

**Network Errors:**
```php
catch (ConnectionException $exception) {
    $errorCode = /* determine DNS or CONNECTION_FAILED */;
    $this->errorService->recordError(...);
    
    if ($this->errorService->isFatal($errorCode, $currentUrl, $startUrl)) {
        throw $exception;
    }
}
```

**Renderer Errors:**
```php
catch (\Throwable $rendererException) {
    $this->errorService->recordError(
        code: ErrorCode::RENDERER_FAILED,
        source: ErrorSource::RENDERER,
        ...
    );
    // Fall back to HTTP content, continue crawl
}
```

---

## Consequences

### Positive

✅ **Einheitliche Fehlerbehandlung**
- Alle Fehler werden konsistent kategorisiert
- Source und Code machen Fehlertyp sofort erkennbar

✅ **Robuster Crawler**
- Subpage-Fehler brechen Crawl nicht ab
- Renderer-Fehler führen zu HTTP-Fallback
- Crawl läuft so weit wie möglich durch

✅ **Bessere Sichtbarkeit**
- Alle Fehler im Dashboard sichtbar
- Filterbare Fehlerübersicht
- Context-Details für Debugging

✅ **Automatische Severity**
- Keine manuelle Severity-Entscheidung nötig
- Konsistente Bewertung

✅ **Erweiterbar**
- Neue Error Codes einfach hinzufügbar
- Context-JSON flexibel für neue Infos
- Error Enum dokumentiert alle möglichen Fehler

### Negative

⚠️ **Migration erforderlich**
- Bestehende crawl_errors Einträge haben keine code/severity/source
- Alte Einträge müssen migriert oder bleiben unvollständig

⚠️ **Komplexere Fehlerlogik**
- Mehr Code im CrawlerService
- try-catch Blöcke differenzierter

⚠️ **Kein automatisches Retry**
- Fehlerhafte Seiten werden nicht automatisch erneut versucht
- Manueller Recrawl nötig

### Trade-offs

**Recoverable vs. Fatal:**
- Bewusst konservativ: Nur Start-URL-Fehler sind fatal
- Pro: Crawl läuft meistens durch
- Con: Manche Crawls mit vielen Fehlern könnten abgebrochen werden sollten

**Automatische Severity:**
- Pro: Konsistent und wartbar
- Con: Manche Fehler könnten kontextabhängig andere Severity haben
- Entscheidung: Start mit automatischer Severity, bei Bedarf override-Möglichkeit

**Context als JSON:**
- Pro: Flexibel für verschiedene Fehlertypen
- Con: Kein Schema, kann inkonsistent werden
- Entscheidung: Dokumentierte Beispiele als Best Practice

---

## Implementation

### Migration
`2026_09_01_120000_enhance_crawl_errors_table.php`

### Backend
- `ErrorCode` enum (13 Codes)
- `ErrorSeverity` enum (4 Levels)
- `ErrorSource` enum (6 Sources)
- `ErrorService` (recordError, isFatal, determineSeverity)
- `CrawlError` model erweitert
- `CrawlerService` erweitert mit Error Handling
- `CrawlRunErrorsController` (API Endpoint mit Filtern)
- Route: `GET /api/crawl-runs/{id}/errors`

### Frontend
- `CrawlErrorItem` TypeScript type
- `ErrorsTab` Komponente
- Integration in `CrawlResultTabs`
- Filter UI (Severity, Source, Code)
- Pagination

### Tests
**Unit Tests (11 Tests):**
- ErrorServiceTest (record, severity, fatal detection, URL normalization)

**Feature Tests (9 Tests):**
- CrawlerErrorHandlingTest (404, 500, start URL fatal, redirect limit, renderer fallback, multiple errors, context, occurred_at)

---

## Validation

### Test Coverage
- ✅ HTTP 4xx/5xx captured and classified
- ✅ Timeouts categorized
- ✅ Connection/DNS errors distinguished
- ✅ Redirect errors classified
- ✅ Renderer errors logged with fallback
- ✅ Recoverable errors don't break crawl
- ✅ Fatal start-URL errors fail crawl properly
- ✅ Errors visible in frontend with filters

### Remaining Work

**Not in M1.7 (Future):**
- Retry-Mechanismus für fehlgeschlagene Seiten
- Batch-Error-Resolution
- Error-Rate-Monitoring
- Error-Alerts/Notifications
- Redirect-Loop-Detection (derzeit nur Limit)
- robots.txt/sitemap error semantic consistency (separate issue)

---

## Related

- M1.1: URL & Link Consistency (ADR-0011)
- M1.2: Redirect Handling (ADR-0012)
- M1.6: Renderer Integration (ADR-0016)
- M1.8: Real-World Crawl Validation (upcoming)

---

**Aktueller Stand:** M1.7 vollständig implementiert, getestet und dokumentiert.
