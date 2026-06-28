# ACD Engineering Handbook

## 1. Ziel des Projekts

ACD Analyzer ist die interne Analyseplattform von Alan Carl Digital.
Sie sammelt Website-Daten, analysiert wiederkehrende Probleme und dient als Grundlage für spätere digitale Produkte.

## 2. Grundprinzipien

- So einfach wie möglich, so modular wie nötig.
- Keine Technologie zweimal entwickeln.
- Interne Engine vor Kundenprodukt.
- Datenqualität vor Funktionsumfang.
- Jede Klasse hat genau eine klare Verantwortung.
- Erst funktionierend, dann sauber erweitern.

## 3. Architektur

- Laravel Backend
- Next.js internes Dashboard
- PostgreSQL
- Docker Compose
- Nginx
- Später: Redis, Queue Worker, Playwright, Lighthouse, axe-core

## 4. Module

### Engine

Crawler, Downloader, Parser, Persister, Analyzer.

### Dashboard

Internes Command Center zur Visualisierung, Kontrolle und Fehleranalyse.

### Produkte

Spätere Kundenprodukte auf Basis wiederkehrender Erkenntnisse.

## 5. Coding-Regeln

- Businesslogik ins Backend.
- Frontend zeigt Daten und löst Aktionen aus.
- Keine Fat Controller.
- Keine God Services.
- DTOs für strukturierte Übergaben.
- Services nach Verantwortung trennen.

## 6. Definition of Done

Eine Funktion gilt erst als fertig, wenn:

- sie lokal in Docker läuft,
- sie nachvollziehbar getestet wurde,
- Fehlerfälle berücksichtigt sind,
- sie keine unnötige Komplexität einführt,
- sie zur Roadmap passt.

## 7. Aktueller Sprint

Sprint 2: Crawler modularisieren.

Ziel:
CrawlerService orchestriert nur noch Downloader, Parser und Persister.