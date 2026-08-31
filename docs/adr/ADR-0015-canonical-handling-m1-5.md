# ADR-0015: Canonical Handling (M1.5)

**Status:** Accepted  
**Date:** 2026-08-31  
**Milestone:** M1.5 – Canonical Handling

## Context

Canonical Links (`<link rel="canonical">`) sind ein wichtiges SEO-Element zur Vermeidung von Duplicate Content-Problemen. Sie signalisieren Suchmaschinen, welche Version einer Seite die bevorzugte (kanonische) Version ist.

Vor M1.5 wurden Canonical-Links bereits extrahiert und in der Datenbank gespeichert (`canonical_href`, `canonical_url`, `canonical_count`), aber es gab keine umfassende Analyse und Issue-Erkennung.

M1.5 vervollständigt das Canonical-Handling durch:

1. **Umfassende Issue-Erkennung** für verschiedene Canonical-Probleme
2. **Erweiterte Validierung** von Canonical-URLs
3. **Dokumentation** aller Canonical-Issues im issue-codes.md

## Decision

### 1. Canonical Issue Codes

Wir haben folgende Issue-Codes eingeführt:

| Code                       | Severity  | Bedeutung                                                          |
| -------------------------- | --------- | ------------------------------------------------------------------ |
| `missing_canonical`        | `info`    | Seite hat keinen Canonical-Link (bereits vorhanden)                |
| `multiple_canonicals`      | `warning` | Mehrere Canonical-Links gefunden (nur der erste wird verwendet)    |
| `invalid_canonical`        | `error`   | Canonical-Link enthält ungültige/nicht auflösbare URL              |
| `empty_canonical`          | `warning` | Canonical-Link hat leeres href-Attribut                            |
| `canonical_to_other_url`   | `info`    | Canonical zeigt auf andere URL (nicht self-referencing)            |

### 2. Erkennung im PageIssueAnalyzer

Die Canonical-Analyse erfolgt in `analyzeTechnicalSeo()` des `PageIssueAnalyzer` basierend auf den persistierten Feldern:

- `canonical_count` - Anzahl der gefundenen Canonical-Links
- `canonical_href` - Raw href-Attribut des ersten gültigen Canonical
- `canonical_url` - Aufgelöste, normalisierte URL des Canonical
- `final_url` - Finale URL der Page nach Redirects
- `url` - Ursprüngliche URL der Page

**Logik:**

```php
// Fehlende Canonicals
if ($canonicalCount === 0) → missing_canonical

// Mehrere Canonicals
if ($canonicalCount > 1) → multiple_canonicals

// Ungültige URL (href vorhanden, aber nicht auflösbar)
if ($canonicalHref !== null && $canonicalHref !== '' && $canonicalUrl === null)
    → invalid_canonical

// Leeres href
if ($canonicalHref === '' && $canonicalCount > 0) → empty_canonical

// Canonical auf andere URL
if ($canonicalUrl !== null && $finalUrl !== null && $canonicalUrl !== $finalUrl)
    → canonical_to_other_url
```

### 3. CrawlAnalysisService Integration

Der `CrawlAnalysisService` wurde erweitert, um die Canonical-Felder an den `PageIssueAnalyzer` zu übergeben:

```php
'canonical_count' => $page->canonical_count,
'canonical_href' => $page->canonical_href,
'canonical_url' => $page->canonical_url,
'final_url' => $page->final_url,
'url' => $page->url,
```

### 4. Severity-Begründung

- **`missing_canonical`** → `info`: Fehlende Canonicals sind nicht automatisch ein Fehler, aber ein nützlicher Hinweis
- **`multiple_canonicals`** → `warning`: Kann Suchmaschinen verwirren, aber nicht kritisch
- **`invalid_canonical`** → `error`: Technisch defekt, muss korrigiert werden
- **`empty_canonical`** → `warning`: Nutzlos, sollte entfernt oder korrigiert werden
- **`canonical_to_other_url`** → `info`: Nicht automatisch ein Problem (z.B. bei bewussten Duplikaten), aber wichtig für manuelle Überprüfung

## Consequences

### Positive

✅ **Vollständige Canonical-Erkennung**: Alle relevanten Canonical-Probleme werden automatisch erkannt  
✅ **Differenzierte Severities**: Nicht alles ist ein Fehler - `info` für Hinweise, `error` für echte Probleme  
✅ **Self-referencing Canonicals**: Werden korrekt erkannt und erzeugen keine false-positive Issues  
✅ **Cross-URL Canonicals**: Werden als Info markiert für manuelle Überprüfung  
✅ **Testabdeckung**: 5 Unit-Tests + 5 Feature-Tests für alle Canonical-Szenarien  
✅ **Dokumentation**: Alle Issue-Codes sind in `docs/analyzer/issue-codes.md` dokumentiert

### Trade-offs

⚠️ **Self-referencing Best Practice**: Wir erkennen `missing_canonical` als Info, aber empfehlen nicht aktiv self-referencing Canonicals. Dies könnte später als Best Practice hinzugefügt werden.

⚠️ **Canonical-Chains**: Wir erkennen noch keine Canonical-Chains (A→B→C). Dies bleibt für spätere Milestones reserviert.

⚠️ **HTTP-Header Canonicals**: Wir unterstützen noch keine HTTP-Header Canonicals (`Link: <url>; rel="canonical"`). Dies ist selten und kann später ergänzt werden.

⚠️ **Cross-Domain Canonicals**: Werden nicht gesondert markiert, nur als `canonical_to_other_url`. Eine spätere Verfeinerung könnte externe Canonicals separat behandeln.

### Maintenance

- Neue Canonical-Issues können einfach durch weitere Bedingungen in `analyzeTechnicalSeo()` hinzugefügt werden
- Die Severity-Werte können bei Bedarf angepasst werden, wenn echte Crawl-Daten andere Prioritäten zeigen
- Die Dokumentation in `issue-codes.md` sollte bei Änderungen aktualisiert werden

## Testing

### Unit Tests (PageIssueAnalyzerTest)

- ✅ `test_it_detects_multiple_canonicals`
- ✅ `test_it_detects_invalid_canonical`
- ✅ `test_it_detects_empty_canonical`
- ✅ `test_it_detects_canonical_to_other_url`
- ✅ `test_it_does_not_detect_canonical_to_other_url_when_self_referencing`

### Feature Tests (CanonicalIssuesTest)

- ✅ `test_it_detects_multiple_canonicals_in_crawl`
- ✅ `test_it_detects_invalid_canonical_in_crawl`
- ✅ `test_it_detects_empty_canonical_in_crawl`
- ✅ `test_it_detects_canonical_to_other_url_in_crawl`
- ✅ `test_it_does_not_detect_canonical_to_other_url_when_self_referencing`

### Existing Tests

- ✅ `CanonicalExtractorTest` (18 Tests) - Extraction-Logik
- ✅ `CanonicalPersistenceTest` (8 Tests) - Persistierung
- ✅ `HtmlParserCanonicalIntegrationTest` (1 Test) - Integration

**Gesamt: 226 Tests, alle bestanden ✅**

## Related

- **M1.1** – URL & Link Consistency (ADR-0011)
- **M1.2** – Redirect Handling (ADR-0012)
- **Migration:** `2026_08_31_083601_add_canonical_fields_to_pages_table.php`

## Future Enhancements

Mögliche spätere Erweiterungen:

1. **Canonical-Chain Detection**: A→B→C erkennen
2. **HTTP-Header Canonicals**: `Link: <url>; rel="canonical"` unterstützen
3. **Cross-Domain Warning**: Externe Canonicals gesondert markieren
4. **Canonical Target Validation**: Prüfen, ob Ziel-URL existiert und erreichbar ist
5. **Self-referencing Recommendation**: Best Practice für self-referencing Canonicals einbauen
6. **Website-Level Analysis**: Canonical-Konflikte über mehrere Seiten hinweg erkennen (M2)
