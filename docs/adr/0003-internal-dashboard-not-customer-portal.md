# ADR-0003: Dashboard v0.1 ist ein internes Command Center

## Status

Akzeptiert

## Kontext

ACD Analyzer soll zunächst Daten sammeln, Crawls sichtbar machen und die Entwicklung der Analyse-Engine unterstützen. Ein Kundenportal würde zusätzliche Anforderungen wie Login, Rechte, Zahlungen, PDF-Reports und UI-Polish erzeugen.

## Entscheidung

Das Dashboard in Version 0.1 ist ausschließlich intern.

Es dient als Command Center für:

- Starten von Crawls
- Anzeigen analysierter Websites
- Debugging des Crawlers
- Prüfen gespeicherter Daten
- Erkennen von Fehlern
- spätere Visualisierung von Analyseergebnissen

## Gründe

- schnellerer Fokus auf die Engine
- weniger Komplexität in v0.1
- keine unnötigen Kundenfunktionen zu früh
- besseres Entwicklungs- und Debugging-Werkzeug
- unterstützt datenbasierte Produktentwicklung

## Alternativen

- direkt ein Kundenportal bauen
- gar kein Dashboard bauen und nur API/Tinker nutzen

## Warum verworfen?

Ein Kundenportal wäre zu früh und würde den Fokus verschieben. Nur API/Tinker wäre technisch möglich, aber für die Weiterentwicklung unübersichtlich und wenig hilfreich bei Datenvisualisierung.

## Konsequenzen

- Dashboard muss nicht perfekt aussehen
- keine Benutzerverwaltung in v0.1
- keine Bezahlung, PDF-Reports oder Kundenrollen
- Fokus liegt auf Sichtbarkeit und Kontrolle der Engine