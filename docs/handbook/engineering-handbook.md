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
* Technische Metriken müssen im Frontend verständlich und ehrlich benannt werden.

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

Das Dashboard visualisiert gespeicherte Crawl- und Analyseergebnisse.

Es soll technische Daten verständlich machen, ohne Analyse- oder Geschäftslogik ins Frontend zu verschieben.

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

Controller enthalten keine Geschäftslogik.

Form Requests werden für Validierung verwendet.

Analyse-Logik gehört backendseitig in Services, nicht ins Frontend.

Analyzer-Regeln gehören in Analyzer-Klassen, nicht direkt in Mapping- oder Controller-Klassen.

Analyseergebnisse werden persistiert und nicht beim Abruf der Results live berechnet.

Controller starten Anwendungsfälle, führen aber keine Analyse- oder Mappinglogik selbst aus.

---

## 7. Crawler-Architektur

Der CrawlerService orchestriert den Crawl-Ablauf.

Seit Sprint 4.4 unterstützt der CrawlerService synchrones, limitiertes Multi-Page-Crawling.

Die Start-URL wird mit Tiefe 0 gecrawlt.

Direkt gefundene interne Links können mit Tiefe 1 gecrawlt werden.

Externe Links werden gespeichert, aber nicht weiter gecrawlt.

URL-Normalisierung gehört in den UrlNormalizer, nicht in Parser, Persister oder Controller.

Der CrawlResultPersister speichert einzelne Pages und ihre zugehörigen Daten, berechnet aber nicht die Gesamtzahl gecrawlter Seiten.

pages_crawled wird nach Abschluss des Crawl-Loops im CrawlerService berechnet.

CrawlAnalysisService läuft erst nach Abschluss des vollständigen Crawl-Loops.

Ein CrawlRun gilt erst als completed, wenn Crawl-Daten persistiert und die Analyse erfolgreich abgeschlossen wurden.

Ein CrawlRun verwendet `finished_at` als Abschlusszeitpunkt. Der Service darf keine nicht vorhandenen Zeitstempelspalten wie `completed_at` beschreiben.

---

## 8. Crawl-Konfiguration

Seit Sprint 4.5 sind Crawl-Limits konfigurierbar.

Der CrawlerService erhält Crawl-Optionen und verwendet keine fest verdrahteten Seiten- oder Tiefenlimits mehr.

Die gewählten Limits werden auf dem CrawlRun gespeichert, damit ein späteres Analyseergebnis nachvollziehbar bleibt.

Aktuelle Limits:

* `maxPages`: 1 bis 25
* `maxDepth`: 0 bis 2

Standardwerte:

* `maxPages`: 10
* `maxDepth`: 1

Die Backend-Validierung ist die Quelle der Wahrheit.

Das Frontend darf Werte zur besseren Bedienbarkeit clientseitig begrenzen, ersetzt aber niemals die Backend-Validierung.

---

## 9. Analyse-Architektur

CrawlResultsService liest gespeicherte Crawl-Daten und gespeicherte Issues.

CrawlAnalysisService orchestriert die Analyse eines CrawlRuns und speichert Issues.

Page-level issue detection gehört in Analyzer-Klassen.

Result mapping gehört in Result Services.

Crawl-Fehler werden als Teil des Analyseergebnisses behandelt und gemeinsam mit erfolgreich gecrawlten Seiten angezeigt.

Erfolgreiche Seiten und Crawl-Fehler müssen einen stabilen Ergebnisvertrag erfüllen.

Mindestens erforderlich sind:

* URL
* Tiefe
* Crawl-Status
* Issues
* Crawl-Error-Informationen, falls vorhanden

Crawl-Fehler enthalten ebenfalls eine Tiefe, damit Multi-Page-Ergebnisse korrekt sortiert und verständlich angezeigt werden können.

---

## 10. Frontend- und UX-Regeln

Das Frontend visualisiert den Zustand des Systems.

Das Frontend darf Ergebnisse sortieren, filtern und verständlicher darstellen.

Das Frontend berechnet keine fachlichen Analyseergebnisse.

Multi-Page-Ergebnisse sollen so dargestellt werden, dass problematische Seiten schnell sichtbar sind.

Die Ergebnisliste soll bevorzugt sortieren nach:

1. Crawl-Fehler zuerst
2. Schwerere Issues zuerst
3. Mehr Issues zuerst
4. Geringere Crawl-Tiefe zuerst

Technische HTML-Metriken müssen präzise benannt werden.

Wenn der Analyzer `<img>`-Tags zählt, wird im Frontend von `Bild-Elementen` gesprochen, nicht pauschal von sichtbaren Bildern.

Alt-Text-Metriken werden als `Ohne Alt-Text` bezeichnet.

---

## 11. Datenbank- und Entwicklungsumgebung

Bei lokaler Laravel-Ausführung mit `php artisan serve` und PostgreSQL in Docker wird als Datenbankhost verwendet:

```env
DB_HOST=127.0.0.1
````

Wenn Laravel selbst im Docker-Container läuft, wird als Datenbankhost verwendet:

```env
DB_HOST=postgres
```

Nach Änderungen an `.env` oder Datenbankkonfiguration sollte der Laravel-Konfigurationscache geleert werden:

```bash
php artisan config:clear
php artisan cache:clear
php artisan optimize:clear
```

`php artisan migrate:fresh` löscht die aktuell konfigurierte Datenbank.

Für Tests soll möglichst `php artisan test` verwendet werden.

Wenn eine Testdatenbank explizit zurückgesetzt wird, muss sichergestellt sein, dass nicht versehentlich die Entwicklungsdatenbank verwendet wird.

---

## 12. Coding Standards

* PSR-12
* TypeScript Strict Mode
* SOLID
* Kleine Klassen
* Kleine Methoden
* Aussagekräftige Namen
* Nullable Felder werden im Frontend explizit typisiert
* TypeScript-Fehler werden als Hinweis auf fehlerhafte Verträge ernst genommen

---

## 13. Definition of Done

Eine Aufgabe ist erst abgeschlossen wenn

* Docker läuft
* Tests erfolgreich sind
* TypeScript-Build erfolgreich ist
* Architektur sauber bleibt
* API-Verträge stabil sind
* ADR bei Bedarf erstellt oder ergänzt wurde
* Handbook aktualisiert wurde
* Roadmap geprüft wurde

---

## 14. Sprint Workflow

Jeder Sprint besitzt

* Ziel
* Aufgaben
* Definition of Done
* Architekturentscheidungen
* Abschluss

Am Ende eines Sprints wird geprüft:

* Was wurde fachlich erreicht?
* Was wurde technisch verbessert?
* Welche Architekturentscheidungen wurden getroffen?
* Müssen ROADMAP, Handbook, ADRs oder README angepasst werden?
* Ist ein sinnvoller Commit-Zustand erreicht?

---

## 15. Dokumentation

ROADMAP beschreibt den Entwicklungsplan.

Engineering Handbook beschreibt die Arbeitsweise.

ADR beschreibt Architekturentscheidungen.

README erklärt das Projekt.

AGENTS.md enthält Regeln für KI-Assistenten.

Dokumentation wird nicht erst am Projektende gepflegt, sondern sprintweise aktualisiert.

---

## 16. Philosophie

Wir entwickeln keine Software, die heute funktioniert.

Wir entwickeln Software, die in fünf Jahren noch erweitert werden kann.


