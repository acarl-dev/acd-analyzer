# ACD Engineering Handbook

## 1. Vision

Alan Carl Digital entwickelt keine klassische Agentursoftware.

ACD entwickelt eine Intelligence Engine, die Unternehmenswebsites analysiert, wiederkehrende Probleme erkennt und daraus langfristig digitale Produkte entstehen lässt.

---

## 2. Architekturprinzipien

### So einfach wie möglich.

### So modular wie nötig.

### Erst funktionierend.

### Danach sauber.

### Refactoring ist Teil der Entwicklung.

---

## 3. Grundprinzipien

* Daten sind wertvoller als Features.
* Eine Klasse hat genau eine Verantwortung.
* Jede Entscheidung muss Version 3.0 einfacher machen.
* Erst verstehen, dann abstrahieren.
* Keine Architektur für Probleme bauen, die noch nicht existieren.
* Wiederverwendbare Komponenten bevorzugen.

---

## 4. Projektstruktur

### Engine

* Downloader
* Parser
* DTOs
* Persister
* Analyzer
* Page-level issue detection belongs in analyzer classes.
* Result mapping belongs in result services.


### Dashboard

Internes Command Center.

Nicht für Kunden.

### Produkte

Spätere SaaS-Produkte und Werkzeuge.

---

## 5. Technologiestack

Backend

* Laravel

Frontend

* Next.js

Datenbank

* PostgreSQL

Container

* Docker Compose

Webserver

* Nginx

Später

* Redis
* Queue Worker
* Playwright
* Lighthouse
* axe-core

---

## 6. Architekturregeln

Businesslogik gehört ins Backend.

Frontend visualisiert Daten.

DTOs transportieren Daten.

Services orchestrieren.

Parser extrahieren Informationen.

Persister speichern Ergebnisse.

Der CrawlerService orchestriert den Crawl-Ablauf.

Seit Sprint 4.4 unterstützt der CrawlerService synchrones, limitiertes Multi-Page-Crawling.

Die Start-URL wird mit Tiefe 0 gecrawlt.

Direkt gefundene interne Links können mit Tiefe 1 gecrawlt werden.

Die aktuellen Crawl-Limits sind 10 Seiten und Tiefe 1.

Externe Links werden gespeichert, aber nicht weiter gecrawlt.

URL-Normalisierung gehört in den UrlNormalizer, nicht in Parser, Persister oder Controller.

Der CrawlResultPersister speichert einzelne Pages und ihre zugehörigen Daten, berechnet aber nicht die Gesamtzahl gecrawlter Seiten.

pages_crawled wird nach Abschluss des Crawl-Loops im CrawlerService berechnet.

CrawlAnalysisService läuft erst nach Abschluss des vollständigen Crawl-Loops.

Controller enthalten keine Geschäftslogik.

Form Requests werden für Validierung verwendet.

Analyse-Logik gehört backendseitig in Services, nicht ins Frontend.

Analyzer-Regeln gehören in Analyzer-Klassen, nicht direkt in Mapping- oder Controller-Klassen.

Analyseergebnisse werden persistiert und nicht beim Abruf der Results live berechnet.

CrawlResultsService liest gespeicherte Crawl-Daten und gespeicherte Issues.

CrawlAnalysisService orchestriert die Analyse eines CrawlRuns und speichert Issues.

Ein CrawlRun gilt erst als completed, wenn Crawl-Daten persistiert und die Analyse erfolgreich abgeschlossen wurden.

Controller starten Anwendungsfälle, führen aber keine Analyse- oder Mappinglogik selbst aus.

---

## 7. Coding Standards

* PSR-12
* TypeScript Strict Mode
* SOLID
* Kleine Klassen
* Kleine Methoden
* Aussagekräftige Namen

---

## 8. Definition of Done

Eine Aufgabe ist erst abgeschlossen wenn

* Docker läuft
* Tests erfolgreich sind
* Architektur sauber bleibt
* ADR bei Bedarf erstellt wurde
* Handbook aktualisiert wurde
* Roadmap geprüft wurde

---

## 9. Sprint Workflow

Jeder Sprint besitzt

* Ziel
* Aufgaben
* Definition of Done
* Architekturentscheidungen
* Abschluss

---

## 10. Dokumentation

ROADMAP beschreibt den Entwicklungsplan.

Engineering Handbook beschreibt die Arbeitsweise.

ADR beschreibt Architekturentscheidungen.

README erklärt das Projekt.

AGENTS.md enthält Regeln für KI-Assistenten.

---

## 11. Philosophie

Wir entwickeln keine Software, die heute funktioniert.

Wir entwickeln Software, die in fünf Jahren noch erweitert werden kann.
