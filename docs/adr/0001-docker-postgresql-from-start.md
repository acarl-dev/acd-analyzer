# ADR-0001: Docker und PostgreSQL von Anfang an

## Status

Akzeptiert

## Kontext

ACD Analyzer soll langfristig als interne Analyseplattform wachsen. Neben Laravel und Next.js werden später weitere Services wie Redis, Worker, Playwright und Lighthouse benötigt.

## Entscheidung

Das Projekt startet direkt mit Docker Compose und PostgreSQL.

## Gründe

- produktionsnähere Umgebung
- saubere Trennung der Services
- spätere Erweiterung einfacher
- keine Migration von SQLite auf PostgreSQL nötig
- reproduzierbares Setup

## Alternativen

- Lokale Installation ohne Docker
- SQLite für den Start

## Warum verworfen?

SQLite und lokales Setup wären kurzfristig einfacher, hätten aber später zusätzliche Migrationsarbeit erzeugt.

## Konsequenzen

- etwas mehr Komplexität am Anfang
- stabilere Basis für Version 0.1 und spätere Module