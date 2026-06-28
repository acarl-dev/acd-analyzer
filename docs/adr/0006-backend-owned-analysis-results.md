# ADR-0006: Backend-owned Analysis Results

## Status

Accepted

## Context

ACD Analyzer crawlt Websites, speichert HTML und extrahierte Metadaten im Backend. Im Dashboard sollen diese Crawl-Daten als verständliche Analyseergebnisse angezeigt werden.

Es gibt grundsätzlich zwei Möglichkeiten:

1. Das Frontend lädt rohe Daten wie HTML, Links, Headings und Images und berechnet daraus Analyseergebnisse selbst.
2. Das Backend berechnet Analyseergebnisse und liefert dem Frontend eine normalisierte, strukturierte API-Response.

Für Sprint 4.2 wurde erstmals ein vollständiger vertikaler Schnitt umgesetzt:

Crawler → Datenbank → Backend-Service → API → Frontend-Dashboard

Dabei wurde der Endpoint `GET /api/crawl-runs/{crawlRun}/results` eingeführt. Die Analyse-/Mapping-Logik liegt im `CrawlResultsService`.

## Decision

Analyseergebnisse werden backendseitig berechnet und normalisiert.

Das Frontend erhält keine rohen HTML-Daten zur eigenen Analyse, sondern eine strukturierte Ergebnis-Response mit Summary, Page Results und Issues.

Der Controller bleibt schlank und delegiert die Ergebnisaufbereitung an einen Service.

## Consequences

### Positive

- Analyse-Regeln bleiben zentral im Backend.
- Das Frontend bleibt auf Darstellung und Bedienung fokussiert.
- Analyse-Logik ist besser testbar.
- Die API kann später auch von anderen Clients genutzt werden.
- Das Datenmodell bleibt vom UI-Aufbau entkoppelt.
- Neue Analyzer-Regeln können backendseitig ergänzt werden, ohne die UI-Grundstruktur neu zu entwerfen.

### Negative

- Das Backend trägt mehr fachliche Verantwortung.
- Die API-Response muss stabil gehalten und bei Änderungen bewusst versioniert oder migriert werden.
- Für sehr interaktive Analysen könnten später zusätzliche API-Endpunkte oder Filterparameter nötig werden.

## Alternatives Considered

### Frontend-owned analysis

Das Frontend könnte rohe HTML-Daten laden und dort prüfen, ob Title, Meta Description, H1 oder alt-Attribute fehlen.

Diese Option wurde verworfen, weil sie Analyse-Logik in die UI verschieben würde. Dadurch wären Regeln schlechter testbar, schwerer wiederverwendbar und stärker an React/Next.js gekoppelt.

### Mixed analysis

Ein Teil der Analyse könnte im Backend und ein Teil im Frontend stattfinden.

Diese Option wurde für die aktuelle Architektur ebenfalls verworfen, weil dadurch unklar wäre, welche Schicht fachlich verantwortlich ist. Für Sprint 4.2 ist eine klare Trennung wichtiger.

## Current Implementation

- `GET /api/crawl-runs/{crawlRun}/results`
- `CrawlResultsController`
- `CrawlResultsService`
- Frontend API-Funktion `getCrawlResults`
- TypeScript-Typen für `CrawlResultsResponse`, `PageAnalysisResult` und `PageAnalysisIssue`

## Follow-up Work

- Crawl-Fehler in die Ergebnisstruktur integrieren
- Analyzer-Regeln erweitern
- Ergebnis-Response bei wachsendem Umfang ggf. in Resources oder DTOs überführen
- Tests für `CrawlResultsService` ergänzen