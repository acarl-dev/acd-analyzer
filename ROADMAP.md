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
* Parser
* DTOs
* Persister
* REST API

---

## Sprint 3 (laufend)

* Form Requests
* API Resources
* Dashboard-Anbindung
* erstes funktionierendes Frontend
