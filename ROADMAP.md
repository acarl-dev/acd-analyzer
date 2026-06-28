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

### Backend

* Neuer Endpoint `GET /api/crawl-runs/{crawlRun}/results`
* `CrawlResultsController` eingeführt
* `CrawlResultsService` als zentrale Mapping- und Analyse-Schicht eingeführt
* CrawlRun-, Website-, Page-, Heading-, Image- und Link-Daten werden zu einer strukturierten Ergebnis-Response aufbereitet
* Summary-Werte werden berechnet:

  * Seiten gesamt
  * erfolgreiche Seiten
  * fehlgeschlagene Seiten
  * Seiten mit Issues
  * Errors
  * Warnings
  * Infos
* Erste Issue-Regeln umgesetzt:

  * fehlender Title
  * fehlende Meta Description
  * fehlende H1
  * mehrere H1
  * Bilder ohne alt-Attribut

### Frontend

* TypeScript-Typen für Analyseergebnisse ergänzt
* API-Funktion `getCrawlResults` eingeführt
* Analyseergebnisse werden nach einem Crawl geladen
* Dashboard zeigt eine erste Summary mit:

  * Seiten gesamt
  * Seiten mit Issues
  * Errors
  * Warnings
* Dashboard zeigt pro Seite:

  * URL
  * HTTP-Status
  * Title
  * H1
  * Meta Description
  * Issues

### Architektur

* Analyseergebnisse werden backendseitig berechnet und normalisiert
* Das Frontend analysiert kein rohes HTML
* Controller bleibt schlank und delegiert Analyse-/Mapping-Logik an einen Service
* Frontend nutzt typisierte API-Responses

### Ergebnis

Nach einem Crawl zeigt das Dashboard erstmals echte Analyseergebnisse aus gespeicherten Crawl-Daten an. Damit steht ein vollständiger vertikaler Schnitt von Crawler über Datenbank und Backend-API bis zur Dashboard-Anzeige.

---

### Offen / nächste Schritte

* Analyse-Regeln weiter ausbauen
* Crawl-Fehler in die Ergebnisanzeige integrieren
* Ergebnisliste optisch und funktional verbessern
* Filterung nach Errors/Warnings ergänzen
* Detailansicht pro Seite vorbereiten
* Designsystem weiter vereinheitlichen
* Architekturentscheidung zu backendseitiger Analyse in einem ADR dokumentieren
