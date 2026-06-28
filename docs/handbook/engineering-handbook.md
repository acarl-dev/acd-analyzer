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

Der CrawlerService enthält keine Parserlogik.

Controller enthalten keine Geschäftslogik

Form Requests werden für Validierung verwendet

Analyse-Logik gehört backendseitig in Services, nicht ins Frontend

Analyzer-Regeln gehören in Analyzer-Klassen, nicht direkt in Mapping- oder Controller-Klassen

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
