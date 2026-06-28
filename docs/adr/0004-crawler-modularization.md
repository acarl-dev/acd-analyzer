# ADR-0004: CrawlerService wird in Downloader, Parser und Persister aufgeteilt

## Status

Akzeptiert

## Kontext

Der erste funktionierende CrawlerService konnte eine URL laden, HTML parsen und Ergebnisse direkt speichern. Dadurch lagen jedoch mehrere Verantwortlichkeiten in einer Klasse: HTTP-Download, DOM-Parsing, Datenextraktion und Persistierung.

Für Version 0.2 und später werden weitere Analysefunktionen erwartet, z. B. SEO-Regeln, Bildprüfungen, interne/externe Links, JavaScript-Erkennung, Lighthouse, axe-core und Business-Analysen.

## Entscheidung

Der CrawlerService wird modularisiert.

Die Verantwortlichkeiten werden aufgeteilt in:

- `CrawlerService`: orchestriert den Ablauf
- `PageDownloader`: lädt eine Webseite herunter
- `HtmlParser`: extrahiert strukturierte Daten aus HTML
- `CrawlResultPersister`: speichert strukturierte Ergebnisse in der Datenbank
- DTOs wie `DownloadedPage` und `ParsedPage`: transportieren Daten zwischen den Komponenten

## Gründe

- jede Klasse hat eine klare Verantwortung
- CrawlerService bleibt klein und lesbar
- Parser können später erweitert oder ersetzt werden
- Playwright kann später den Downloader ergänzen oder ersetzen
- Persistierung kann später erweitert werden, ohne Parser-Logik zu verändern
- bessere Testbarkeit der einzelnen Komponenten

## Alternativen

- alles im CrawlerService belassen
- sofort Repositories und Interfaces einführen
- schon jetzt Extractor-Klassen für jedes HTML-Element bauen

## Warum verworfen?

Ein großer CrawlerService würde schnell unwartbar werden. Repositories und Interfaces wären für v0.1 noch Overengineering. Einzelne Extractor-Klassen sind sinnvoll, aber erst ab v0.2 nötig, wenn die Analyse-Regeln wachsen.

## Konsequenzen

- etwas mehr Dateien
- klarere Architektur
- bessere Erweiterbarkeit
- CrawlerService liest sich wie ein Ablaufdiagramm