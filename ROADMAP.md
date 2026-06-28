# Alan Carl Digital – Analyzer Roadmap

## Vision

ACD Analyzer ist die interne Intelligence Engine von Alan Carl Digital.

Die Plattform sammelt, strukturiert und analysiert Daten von Unternehmenswebsites und bildet die Grundlage für zukünftige Produkte, SaaS-Lösungen und Dienstleistungen.

Das langfristige Ziel ist keine klassische Webagentur, sondern eine datengetriebene Produktplattform mit einer wiederverwendbaren Analyse-Engine.

---

# Version 0.1 – Foundation

## Ziel

Eine stabile, modulare und erweiterbare technische Basis schaffen.

---

## Architektur

### Backend

* Laravel
* REST API
* Service Layer

### Frontend

* Next.js
* internes Dashboard

### Infrastruktur

* Docker Compose
* PostgreSQL
* Nginx

---

## Engine

### Crawling

* HTTP Downloader
* URL-Normalisierung
* CrawlRun-Verwaltung

### Parsing

* HTML Parser
* DTOs
* Modularer CrawlerService

### Persistierung

* CrawlResultPersister
* PostgreSQL

---

## API

* REST API
* POST `/api/crawl`
* JSON Responses
* Vorbereitung für spätere Dashboard-Kommunikation

---

## Dashboard (intern)

Command Center ausschließlich für die Entwicklung.

### Funktionen

* Crawl starten
* Crawlstatus anzeigen
* Websites anzeigen
* Crawlhistorie anzeigen
* Debugging
* Datenkontrolle

---

## Gespeicherte Daten

### Website

* URL
* Host

### Crawl

* Status
* Startzeit
* Endzeit
* Fehler
* Anzahl Seiten

### Seiten

* HTML
* HTTP Statuscode
* Response Time
* Title
* Meta Description

### Inhalte

* Headings
* Links
* Bilder

---

## Architekturziele

* Kleine Services
* Eine Verantwortung pro Klasse
* DTOs zwischen den Schichten
* Controller enthalten keine Geschäftslogik
* Erweiterbarkeit durch modulare Komponenten

---

# Version 0.2 – Analyzer

## Ziel

Eigene Analyse-Engine entwickeln.

### SEO

* fehlender Title
* fehlende Meta Description
* H1 Analyse
* Überschriftenstruktur
* Bilder ohne Alt
* interne/externe Links
* Statuscode-Auswertung

### Crawling

* Mehrseitiger Crawl
* Crawl-Limits
* robots.txt berücksichtigen
* Sitemap-Unterstützung

### JavaScript

* Erkennung JS-lastiger Websites
* Vorbereitung für Playwright

### Architektur

* Aufteilung des HtmlParsers in einzelne Extractor-Klassen
* Analyzer-Module

---

# Version 0.3 – Dashboard

## Ziel

Das interne Dashboard wird zum Analysezentrum.

### Visualisierung

* Websiteübersicht
* Crawlhistorie
* Statistiken
* Diagramme
* Fehlerübersicht
* Detailansicht einzelner Seiten

### Bedienung

* Projekte
* Filter
* Suche
* Analyse erneut starten

---

# Version 0.4 – Scanner

## Ziel

Integration etablierter Analysewerkzeuge.

### Lighthouse

* Performance
* Best Practices
* SEO

### Accessibility

* axe-core

### Browser Rendering

* Playwright

---

# Version 0.5 – Business Analyzer

## Ziel

Eigene Business-Regeln entwickeln.

### Beispiele

* Conversion
* Vertrauen
* Kontaktmöglichkeiten
* lokale Auffindbarkeit
* UX
* Informationsstruktur
* Contentqualität
* Call-to-Actions
* Terminbuchung
* Branchenregeln

---

# Version 0.6 – KI

## Ziel

KI als Analyse- und Assistenzsystem.

### Funktionen

* Zusammenfassungen
* Priorisierung
* Handlungsempfehlungen
* automatische Berichte
* Erkennung wiederkehrender Muster
* Vergleich ähnlicher Unternehmen

---

# Version 1.0

## Erstes internes Release

### Engine

* vollständig modular
* produktiv nutzbar

### Dashboard

* vollständiges internes Analysezentrum

### Analyse

* reale Unternehmensanalysen
* stabile Crawls
* aussagekräftige Auswertungen

---

# Langfristige Vision

Aus der Intelligence Engine entstehen:

* SaaS-Produkte
* Branchenlösungen
* APIs
* Plugins
* Reports
* Benchmarking
* KI-gestützte Empfehlungen
* automatisierte Audits
* White-Label-Lösungen

---

# Aktueller Entwicklungsstand

## Sprint 1

✅ Docker-Grundlage

✅ PostgreSQL

✅ Laravel

✅ Next.js

✅ Datenmodell

---

## Sprint 2

✅ Modularisierung des Crawlers

* Downloader
* HTML Parser
* DTOs
* CrawlResultPersister
* Modularer CrawlerService
* REST API (Grundlage)

---

## Sprint 3

✅ Erste REST API

### Backend

* API installiert
* `POST /api/crawl`
* CrawlController
* Form Request (`StoreCrawlRequest`)
* API Resource (`CrawlRunResource`)
* JSON Responses
* Fehlerbehandlung über Laravel Validation

### Architektur

* Controller enthalten keine Geschäftslogik
* Validierung über Form Requests
* API-Ausgaben über Resources
* Crawler vollständig über HTTP ansteuerbar

### Ergebnis

Der Crawler kann jetzt über eine REST API gestartet werden und liefert strukturierte JSON-Antworten zurück.

---

## Sprint 4 – Dashboard MVP

Status:
🟡 In Arbeit

Ziel von Sprint 4 ist der Aufbau eines ersten internen Dashboards, mit dem Crawls gestartet und grundlegende Analyseergebnisse sichtbar gemacht werden können.

---

### Sprint 4.1 – Dashboard-Grundstruktur und API-Anbindung

Status:
✅ Abgeschlossen

### Frontend

* Startseite als internes Dashboard aufgebaut
* Crawl-Formular erstellt
* API-Client für Backend-Kommunikation angelegt
* `useCrawler` Hook eingeführt
* Crawl-Ergebnis-Komponente erstellt
* TypeScript-Typen für CrawlRun/API-Responses ergänzt

### Architektur

* API-Zugriffe liegen nicht direkt in UI-Komponenten
* Crawler-Logik im Frontend über Hook gekapselt
* UI-Komponenten für Formular und Ergebnisanzeige getrennt
* Frontend ist auf spätere Dashboard-Erweiterungen vorbereitet

### Ergebnis

Das Dashboard kann einen Crawl über die bestehende REST API starten und den zurückgegebenen CrawlRun anzeigen.

---

### Sprint 4.2 – Erste Analyseergebnisse im Dashboard

Status:
✅ Abgeschlossen

---

### Ziel

Der ACD Analyzer soll nach einem Crawl erstmals echte, nutzbare Analyseergebnisse im internen Dashboard anzeigen.

Der Fokus liegt auf einem vollständigen vertikalen Schnitt:

```txt
Crawler → Datenbank → Laravel Backend/API → typisiertes Frontend → Dashboard-Anzeige
```

---

### Backend

Umgesetzt:

* Neuer Endpoint eingeführt:

```txt
GET /api/crawl-runs/{crawlRun}/results
```

* `CrawlResultsController` eingeführt

* `CrawlResultsService` als zentrale Mapping- und Analyse-Schicht eingeführt

* CrawlRun-, Website-, Page-, Heading-, Image-, Link- und CrawlError-Daten werden backendseitig geladen und zu einer strukturierten Ergebnis-Response aufbereitet

* Crawl-Fehler werden in dieselbe Ergebnisstruktur integriert wie erfolgreich gecrawlte Seiten

* Crawl-Fehler werden als eigene Ergebniszeilen abgebildet mit:

  * `hasCrawlError`
  * `crawlError`
  * `crawl_error` Issue

* Summary-Werte werden backendseitig berechnet:

  * Seiten gesamt
  * erfolgreiche Seiten
  * fehlgeschlagene Seiten
  * Seiten mit Issues
  * Issues gesamt
  * Errors
  * Warnings
  * Infos

* Erste Issue-Regeln umgesetzt:

  * fehlender Title
  * fehlende Meta Description
  * fehlende H1
  * mehrere H1
  * Bilder ohne alt-Attribut
  * Crawl-Fehler

* Issue-Ergebnisse werden normalisiert ausgegeben mit:

  * `code`
  * `severity`
  * `message`

---

### Frontend

Umgesetzt:

* TypeScript-Typen für Crawl- und Analyseergebnisse ergänzt

* API-Funktion `getCrawlResults` eingeführt

* Analyseergebnisse werden nach einem Crawl geladen und im Dashboard angezeigt

* Ergebniszeilen unterstützen sowohl echte Pages als auch Crawl-Fehler-Zeilen

* Dashboard zeigt eine Summary mit:

  * Seiten gesamt
  * fehlgeschlagene Seiten
  * Seiten mit Problemen
  * Probleme gesamt
  * Fehler
  * Warnungen
  * Hinweise

* Dashboard zeigt pro Seite:

  * URL
  * HTTP-Status
  * Title
  * Title-Länge
  * H1
  * H1-Anzahl
  * Meta Description
  * Meta-Description-Länge
  * Bilder gesamt
  * Bilder ohne alt-Attribut
  * interne Links
  * externe Links
  * HTML-Größe
  * Issues

* Issues werden abhängig von ihrer Severity visuell unterschieden:

  * `error`
  * `warning`
  * `info`

* Seiten ohne technische Crawl-Fehler werden als HTTP-Ergebnisse angezeigt

* Crawl-Fehler werden als fehlgeschlagene Ergebniszeilen sichtbar gemacht

---

### Architektur

* Analyseergebnisse werden backendseitig berechnet, normalisiert und als View Model für das Frontend bereitgestellt

* Das Frontend analysiert kein rohes HTML

* Das Frontend rendert die vorbereiteten Ergebnisdaten und dupliziert keine Analyse-Logik

* Der Controller bleibt schlank und delegiert Analyse- und Mapping-Logik an den `CrawlResultsService`

* Die Frontend-Seite arbeitet mit typisierten API-Responses

* Crawl-Fehler und erfolgreich gecrawlte Seiten werden in einem gemeinsamen Ergebnisformat dargestellt

* Architekturentscheidung zu backendseitiger Analyse wurde in `ADR-0006: Backend-owned Analysis Results` dokumentiert

---

### Validierung / Tests

Sprint 4.2 wurde mit mehreren echten Websites getestet.

Bestätigt wurde:

* HTTP-Status wird korrekt angezeigt

* erfolgreiche Seiten werden nicht als fehlgeschlagen gezählt

* Crawl- und Analyseergebnisse werden korrekt im Dashboard dargestellt

* Summary-Werte zählen korrekt:

  * Seiten gesamt
  * fehlgeschlagene Seiten
  * Seiten mit Problemen
  * Probleme gesamt
  * Errors
  * Warnings
  * Infos

* Fehlende H1 wird als Issue erkannt

* Fehlende Meta Description wird als Issue erkannt

* Bilder ohne alt-Attribut werden als Issue erkannt

* Title, H1, Meta Description, Linkzahlen, Bildzahlen und HTML-Größe werden im Dashboard sichtbar

---

### Ergebnis

Nach einem Crawl zeigt das Dashboard erstmals echte Analyseergebnisse aus gespeicherten Crawl-Daten an.

Neben erfolgreich gecrawlten Seiten werden auch Crawl-Fehler in derselben Ergebnisstruktur sichtbar gemacht. Damit steht ein vollständiger vertikaler Schnitt von Crawler über Datenbank und Backend-API bis zur Dashboard-Anzeige.

Sprint 4.2 liefert damit den ersten nutzbaren Analyse-Stand des ACD Analyzers.

---

### Bekannte Grenzen

* Die Analyse-Regeln liegen aktuell noch direkt im `CrawlResultsService`

* Die Severity-Semantik ist noch einfach gehalten

* SEO-/Content-Probleme und technische Crawl-Probleme werden zwar unterschieden, aber noch nicht vollständig fachlich gewichtet

* Es gibt noch keine Filterung nach Errors, Warnings oder Infos

* Es gibt noch keine Detailansicht pro Seite

* Die Ergebnisansicht ist für kleine Crawls nutzbar, aber noch nicht für größere Crawls optimiert

* JavaScript-heavy Websites werden noch nicht gesondert erkannt oder behandelt

* Es gibt noch keine automatisierten Tests für `CrawlResultsService`

---

### Offen / nächste Schritte

* Analyse-Regeln weiter ausbauen

* Analyse-Logik aus `CrawlResultsService` herauslösen

* Eigenen Analyzer-Service oder einzelne Analyzer-Klassen einführen

* Severity-Semantik fachlich schärfen

* Ergebnisliste optisch und funktional verbessern

* Filterung nach Errors, Warnings und Infos ergänzen

* Detailansicht pro Seite vorbereiten

* Tests für `CrawlResultsService` bzw. zukünftige Analyzer ergänzen

* Designsystem weiter vereinheitlichen

---

### Nächster Sprint

### Sprint 4.3 – Analyzer-Regeln strukturieren und erweitern

Status:
🚧 Gestartet

---

### Ziel

Die Analyse-Logik soll aus dem `CrawlResultsService` herausgelöst und in eine besser erweiterbare Analyzer-Struktur überführt werden.

Der Sprint stärkt die Trennung zwischen:

```txt
CrawlResultsService → lädt und mapped Ergebnisdaten
PageIssueAnalyzer   → bewertet Seiten und erzeugt Issues
Frontend            → zeigt normalisierte Issues an
```

---

### Gestartet / umgesetzt

* `PageIssueAnalyzer` eingeführt

* bestehende Issue-Regeln aus `CrawlResultsService` ausgelagert

* `CrawlResultsService` delegiert Page-Issue-Erkennung an `PageIssueAnalyzer`

* API-Response bleibt kompatibel zum Frontend:

  * `code`
  * `severity`
  * `message`

* erste neue Regeln ergänzt:

  * Title zu kurz
  * Title zu lang
  * Meta Description zu kurz
  * Meta Description zu lang
  * auffällig große HTML-Datei
  * sehr wenige interne Links
  * hoher Anteil von Bildern ohne alt-Attribut

* Unit-Test-Grundlage für `PageIssueAnalyzer` ergänzt

* Architekturentscheidung dokumentiert:

  * `ADR-0007: Page Issue Analyzer`

---

### Offene Sprint-4.3-Aufgaben

* Severity-Semantik fachlich schärfen

* Schwellwerte anhand echter Crawl-Ergebnisse prüfen

* Issue-Codes dokumentieren

* Ergebnisliste optisch/funktional für mehr Issues verbessern

* Filterung nach `error`, `warning` und `info` vorbereiten

* Detailansicht pro Seite vorbereiten

* Tests ausbauen, sobald die Analyse-Regeln stabiler sind
