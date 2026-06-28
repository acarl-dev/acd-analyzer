# ADR-0002: Laravel Backend und Next.js Frontend getrennt entwickeln

## Status

Akzeptiert

## Kontext

ACD Analyzer besteht aus einer internen Analyse-Engine und einem internen Dashboard. Das Backend übernimmt Crawling, Speicherung, Analyse und API. Das Frontend visualisiert Daten und löst Aktionen aus.

## Entscheidung

Backend und Frontend werden getrennt entwickelt:

- Laravel für Backend, API, Crawler-Logik und Datenzugriff
- Next.js für das interne Dashboard

## Gründe

- klare Trennung zwischen Geschäftslogik und Darstellung
- Laravel passt gut zu Datenbank, Jobs, Services und API
- Next.js passt gut zu einem modernen Dashboard
- beide Teile können später unabhängig wachsen
- spätere Kundenprodukte können dieselbe API nutzen

## Alternativen

- alles in Laravel mit Blade
- Fullstack nur mit Next.js
- Monolith ohne klares Frontend/Backend

## Warum verworfen?

Blade wäre für den Start einfacher, aber für ein langfristiges Dashboard weniger flexibel. Ein reines Next.js-Backend würde Crawling, Jobs und Datenbanklogik unnötig erschweren. Ein ungegliederter Monolith würde spätere Erweiterungen schwieriger machen.

## Konsequenzen

- etwas mehr Setup-Aufwand
- API muss sauber gestaltet werden
- klare Verantwortlichkeiten zwischen Backend und Frontend