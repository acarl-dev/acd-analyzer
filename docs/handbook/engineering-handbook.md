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
* Analyseergebnisse müssen erklärbar und nachvollziehbar bleiben.

---

## 4. Projektstruktur

### Engine

* Downloader
* Parser
* DTOs
* Persister
* Analyzer
* Page-level issue detection belongs in analyzer classes.
* Technology detection belongs in analyzer classes.
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

TechnologyDetectionService läuft nach Persistierung der Crawl-Daten und nach der Page-Issue-Analyse.

Ein CrawlRun gilt erst als completed, wenn Crawl-Daten persistiert, Issues analysiert und Technology Detection erfolgreich abgeschlossen wurden.

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

`maxPages` ist ein Limit, kein Zielwert. Wenn eine Website im initialen HTML keine crawlbaren internen Links enthält, kann ein Crawl trotz höherem `maxPages`-Wert nur aus der Startseite bestehen.

---

## 9. Analyse-Architektur

CrawlResultsService liest gespeicherte Crawl-Daten, gespeicherte Issues und gespeicherte Technology Detections.

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

### Persistenter Analysefluss

Der ACD Analyzer berechnet Analyseergebnisse nicht live beim Abruf der Detailansicht.

Der aktuelle Ablauf ist:

1. `CrawlerService` crawlt eine oder mehrere URLs innerhalb der gesetzten Limits.
2. `CrawlResultPersister` speichert Pages, Headings, Images, Links und Crawl Errors.
3. `CrawlAnalysisService` lädt den vollständigen CrawlRun mit seinen gespeicherten Daten.
4. Analyzer-Klassen wie `PageIssueAnalyzer` erzeugen normalisierte Issues.
5. Die Issues werden in `page_issues` persistiert.
6. `TechnologyDetectionService` erkennt Technologien und speichert sie in `detected_technologies`.
7. `CrawlResultsService` liest nur noch persistierte Daten und mapped sie in eine stabile API-Response.
8. Das Frontend visualisiert die API-Response, berechnet aber keine fachlichen Analyzer-Ergebnisse.

Diese Trennung ist wichtig, damit Analyseergebnisse nachvollziehbar, testbar und reproduzierbar bleiben.

### Page Issues vs. Crawl Errors

Der Analyzer unterscheidet zwischen erfolgreich gespeicherten Pages und Crawl Errors.

**Page Issues** beschreiben fachliche, technische oder strukturelle Auffälligkeiten auf einer gespeicherten Page. Beispiele sind fehlende Titles, fehlende H1-Überschriften, Bilder ohne Alt-Text, technische SEO-Probleme, sehr wenig sichtbarer Text, langsame HTTP-Antwortzeiten oder problematische HTTP-Statuscodes.

Page Issues werden aus gespeicherten Page-Daten erzeugt. Dazu gehören unter anderem:

* Title
* Meta Description
* Headings
* Images
* Links
* HTML
* Response Time
* HTTP Status Code

**Crawl Errors** beschreiben dagegen URLs, die beim Crawling nicht erfolgreich verarbeitet werden konnten. Das kann zum Beispiel passieren, wenn eine URL nicht erreichbar ist, ein Request fehlschlägt oder ein technischer Fehler beim Crawlen auftritt.

Crawl Errors werden ebenfalls in die Analyseergebnisse integriert, damit fehlgeschlagene URLs nicht außerhalb der Bewertung stehen. In der einheitlichen Ergebnisstruktur erscheinen sie neben erfolgreich gecrawlten Pages als eigene Result-Einträge.

Für die Auswertung bedeutet das:

* Eine gespeicherte Page mit HTTP `404` kann ein `http_error_status`-Page-Issue erhalten.
* Eine URL, die gar nicht erfolgreich gecrawlt werden konnte, erscheint als Crawl Error.
* Beide Fälle können den Crawl Health Score beeinflussen.
* Die UI behandelt beide Fälle gemeinsam, zeigt aber an, ob es sich um eine gespeicherte Page oder einen fehlgeschlagenen Crawl handelt.

Diese Unterscheidung ist wichtig, weil nicht jede problematische URL ein Crawl Error ist. Manche URLs werden technisch erfolgreich gespeichert, liefern aber dennoch problematische Seitensignale.

Crawl-Fehler enthalten ebenfalls eine Tiefe, damit Multi-Page-Ergebnisse korrekt sortiert und verständlich angezeigt werden können.

### Crawl Health Score

Der Crawl Health Score ist eine vereinfachte Orientierung zur Gesamtqualität eines CrawlRuns.

Der Score wird nicht global über alle Issues summiert. Stattdessen wird jeder Page Result einzeln bewertet und anschließend über alle Page Results gemittelt.

Der Ablauf ist:

1. Jede gespeicherte Page oder jeder Crawl Error startet mit `100` Punkten.
2. Issues reduzieren den Page Score anhand ihrer Severity.
3. Der Page Score kann nicht unter `0` fallen.
4. Der Crawl Health Score ist der Durchschnitt aller Page Scores.
5. Das Ergebnis wird als Wert von `0` bis `100` ausgegeben.

Aktuelle Penalties:

* `error` → `-30`
* `warning` → `-10`
* `info` → `-2`

Crawl Errors fließen über die einheitliche Page/Error-Struktur ebenfalls in den Score ein. Dadurch werden fehlgeschlagene URLs nicht ignoriert.

Der Health Score ist bewusst kein wissenschaftlich exakter SEO- oder Performance-Score. Er ist eine kompakte Orientierung für die UI, damit Crawls schnell miteinander verglichen und besonders problematische Crawls schneller erkannt werden können.

Die fachliche Score-Berechnung gehört ins Backend, aktuell in `CrawlHealthScoreService`. Das Frontend darf den Score anzeigen, labeln und visuell hervorheben, aber nicht eigenständig fachliche Score-Regeln berechnen.

### Frontend-Auswertung

Das Frontend liest Analyseergebnisse über die API und visualisiert persistierte Backend-Daten.

Die wichtigsten UI-Bereiche sind:

* Dashboard-Gesamtübersicht
* Letzte Crawls
* Crawl Detail
* Crawl Detail Page-Karten
* Issue- und Technologie-Zusammenfassungen

Das Frontend darf aus vorhandenen API-Daten abgeleitete UI-Strukturen berechnen, zum Beispiel:

* Top-Probleme für eine kompakte Übersicht
* Gruppierte Technologien nach Kategorie
* Gefilterte Page-Listen nach Severity
* Ein- und ausgeklappte Page-Karten
* Labels für Health Scores

Das Frontend soll aber keine fachlichen Analyzer-Regeln enthalten. Regeln wie `missing_h1`, `slow_response_time` oder `http_error_status` gehören ins Backend und müssen dort getestet werden.

Diese Trennung hält die Architektur klar:

* Backend: Crawling, Analyse, Persistenz, fachliche Bewertung
* Frontend: Darstellung, Filterung, Gruppierung und Interaktion

### Neue Analyzer-Regeln ergänzen

Neue Analyzer-Regeln sollen klein, testbar und nachvollziehbar ergänzt werden.

Für page-bezogene Regeln gilt aktuell dieser Ablauf:

1. Prüfen, ob die benötigten Rohdaten bereits gespeichert werden.
2. Falls nötig, Crawler oder Persistierung erweitern.
3. Analyzer-Input in `CrawlAnalysisService` ergänzen.
4. Regel im passenden Analyzer ergänzen, aktuell meist `PageIssueAnalyzer`.
5. Unit-Test für die Regel schreiben.
6. Falls die Regel Daten aus der Datenbank benötigt, zusätzlich einen Integrationstest über `CrawlAnalysisService` schreiben.
7. Issue-Code in `docs/analyzer/issue-codes.md` dokumentieren.
8. Backend-Tests ausführen.
9. Frontend-Lint ausführen, falls UI oder TypeScript-Typen betroffen sind.

Eine neue Regel sollte immer einen stabilen Issue-Code, eine Severity, eine verständliche Message und bei Bedarf Context-Daten liefern.

Beispielstruktur eines Issues:

```php
[
    'code' => 'example_issue_code',
    'severity' => 'warning',
    'message' => 'Verständliche Beschreibung des Problems.',
    'context' => [
        'example_value' => 123,
    ],
]
```

Die Severity soll bewusst gewählt werden:

error für klare technische oder fachliche Fehler, die die Nutzbarkeit oder Auswertbarkeit stark beeinträchtigen.
warning für relevante Qualitätsprobleme, die überprüft oder verbessert werden sollten.
info für Hinweise, die interessant sind, aber nicht zwingend ein direktes Problem darstellen.

Wichtig ist, dass Analyzer-Regeln nicht im Frontend implementiert werden. Das Frontend darf Issues sortieren, gruppieren und filtern, aber nicht entscheiden, ob eine Page fachlich problematisch ist.

Wenn eine neue Regel nicht mehr nur eine einzelne Page betrachtet, sondern mehrere Pages eines CrawlRuns vergleichen muss, sollte vorher bewusst entschieden werden, ob ein eigener crawl-weiter Analyzer-Service eingeführt wird. Beispiele dafür wären Duplicate Titles, Duplicate Meta Descriptions oder andere crawl-weite Muster.

### Dokumentationspflege

Bei Änderungen am Analyzer oder an der Auswertungsarchitektur muss geprüft werden, welche Dokumentation angepasst werden sollte.

`docs/analyzer/issue-codes.md` wird aktualisiert, wenn:

* ein neuer Issue-Code eingeführt wird
* eine Severity geändert wird
* eine Issue-Message fachlich anders interpretiert werden muss
* Context-Daten ergänzt oder geändert werden

Das Engineering Handbook wird aktualisiert, wenn:

* sich der Analysefluss ändert
* neue Services eingeführt werden
* Verantwortlichkeiten zwischen Crawler, Analyzer, Result Services und Frontend verschoben werden
* neue Muster für Analyzer-Regeln entstehen
* UI-Auswertungen eine neue fachliche Struktur bekommen

Ein ADR wird erstellt, wenn eine langfristig relevante Architekturentscheidung getroffen wird. Beispiele:

* Einführung eines crawl-weiten Analyzer-Services
* Änderung der Health-Score-Berechnung
* Änderung des Persistenzmodells für Issues
* Trennung oder Zusammenführung größerer Services
* Einführung eines Workers oder Queue-basierten Analyseflusses

Die ROADMAP wird aktualisiert, wenn sich Prioritäten, größere Meilensteine oder der geplante Produktumfang ändern.

Dokumentation ist Teil der Definition of Done, sobald eine Änderung Architektur, Analyzer-Regeln oder Produktverhalten dauerhaft beeinflusst.

---

## 10. Technology Detection

Seit Sprint 4.6 erkennt der Analyzer grundlegende Website-Technologien auf Basis des initial heruntergeladenen HTML.

Technology Detection ist bewusst heuristisch und kein vollständiger Ersatz für Werkzeuge wie Wappalyzer.

Aktuelle Kategorien:

* CMS
* Frontend Framework
* Rendering-Verhalten

Aktuell erkannte Technologien:

* WordPress
* TYPO3
* Wix
* Next.js
* Nuxt
* JS-heavy

Technology Detection Ergebnisse werden in `detected_technologies` gespeichert.

Gespeichert werden:

* Website-Referenz
* CrawlRun-Referenz
* optionale Page-Referenz
* Typ
* Name
* Confidence
* Evidence

Analyzer-Klassen erkennen Technologien.

DTOs transportieren Detection-Ergebnisse.

Services orchestrieren die Analyse eines CrawlRuns.

Result Services mappen gespeicherte Technologien für die API-Ausgabe.

Das Frontend zeigt erkannte Technologien an, führt aber keine eigene Erkennung durch.

Mehrfache Erkennungen derselben Kombination aus `type` und `name` werden in der Ergebnis-Ausgabe dedupliziert.

Beispiel:

* `cms:WordPress`
* `cms:WordPress`
* `cms:Wix`

wird in der Ergebnisansicht zu:

* `WordPress`
* `Wix`

Bei Duplikaten wird bevorzugt die Erkennung mit der höheren Confidence angezeigt.

---

## 11. JS-heavy Websites und Analysegrenzen

JS-heavy bedeutet, dass die Website vermutlich stark von clientseitigem JavaScript abhängt.

Der aktuelle HTTP-Crawler analysiert nur das initiale HTML.

Wenn das initiale HTML keine crawlbaren internen Links enthält, kann der Crawler keine weiteren Unterseiten finden.

Das ist erwartetes Verhalten, bis gerendertes Crawling mit Playwright oder einem ähnlichen Browser-basierten Ansatz eingeführt wird.

JS-heavy-Erkennung ist ein Hinweis auf mögliche Analysegrenzen, kein Fehlerzustand.

Beispiel:

* Eine App-Seite kann `maxPages = 5` erhalten.
* Wenn im initialen HTML keine internen Links vorhanden sind, wird trotzdem nur die Startseite gecrawlt.
* Das Ergebnis ist technisch korrekt, aber möglicherweise nicht vollständig im Sinne der gerenderten Website.

Das Dashboard soll solche Fälle später verständlich erklären.

---

## 12. URL Normalization und interne Links

Interne Link-Erkennung behandelt `www` und non-`www` Hosts als dieselbe Website.

Beispiel:

* `https://example.com`
* `https://www.example.com`

gelten gegenseitig als intern.

Nicht crawlbare Link-Schemata dürfen nicht als interne Crawl-Ziele behandelt werden.

Dazu gehören:

* `mailto:`
* `tel:`
* `javascript:`
* reine Ankerlinks wie `#kontakt`

URL-Normalisierung gehört ausschließlich in den UrlNormalizer.

Crawler, Parser, Persister und Controller dürfen keine eigene Host- oder URL-Sonderlogik duplizieren.

---

## 13. Persistenzregeln

Persister speichern Datenbank-kompatible Werte.

Parser extrahieren Rohinformationen aus HTML.

Wenn extrahierte Werte nicht direkt zur Datenbank passen, normalisiert der Persister diese Werte vor dem Speichern.

Bild-Dimensionen können im HTML Dezimalwerte enthalten.

Beispiel:

* `822.857142857`

wird vor dem Speichern zu:

* `823`

Bild-Dimensionen werden als nullable Integer gespeichert.

Der Crawl darf nicht fehlschlagen, nur weil eine Website Dezimalwerte für Bildbreiten oder Bildhöhen liefert.

Bilder dürfen nicht versehentlich doppelt gespeichert werden.

---

## 14. Frontend- und UX-Regeln

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

Erkannte Technologien werden im Frontend angezeigt, aber nicht im Frontend berechnet.

Technology Badges sollen verständlich machen:

* Name der Technologie
* Kategorie
* Confidence

---

## 15. Datenbank- und Entwicklungsumgebung

ACD wird lokal standardmäßig vollständig über Docker Compose entwickelt.

Die PostgreSQL-Datenbank läuft im Docker-Service `postgres`.

Da Laravel im Docker-Container läuft, verwendet das Backend in der Docker-Entwicklung:

```env
DB_HOST=postgres

Artisan-Befehle werden in der Docker-Entwicklung im App-Container ausgeführt:

docker compose exec app php artisan migrate
docker compose exec app php artisan test
docker compose exec app php artisan tinker

Falls Tinker im Container wegen PsySH-Rechten Probleme macht, kann HOME=/tmp gesetzt werden:

docker compose exec -e HOME=/tmp app php artisan tinker

Frontend-Befehle werden im Frontend-Container ausgeführt:

docker compose exec frontend npm run lint
docker compose exec frontend npm run build

Die API-URL für das Frontend ist eine Browser-URL, keine Docker-interne Service-URL.

Da der Browser außerhalb des Docker-Netzwerks läuft, verwendet das Frontend:

NEXT_PUBLIC_API_URL=http://localhost:8000/api

Merksatz:

Container zu Container: Docker-Service-Namen, z. B. postgres
Browser zu Container: veröffentlichte Ports, z. B. localhost:8000

Lokale Befehle wie php artisan ... oder npm run ... außerhalb der Container werden vermieden, damit Entwicklungsumgebung, Datenbankverbindung und Testverhalten konsistent bleiben.

Wenn .env-Werte geändert werden, wird der Laravel-Cache im Container geleert:

docker compose exec app php artisan optimize:clear

php artisan migrate:fresh löscht die aktuell konfigurierte Datenbank.

Für Tests soll möglichst verwendet werden:

docker compose exec app php artisan test

Wenn eine Testdatenbank explizit zurückgesetzt wird, muss sichergestellt sein, dass nicht versehentlich die Entwicklungsdatenbank verwendet wird.

16. Coding Standards
PSR-12
TypeScript Strict Mode
SOLID
Kleine Klassen
Kleine Methoden
Aussagekräftige Namen
Nullable Felder werden im Frontend explizit typisiert
TypeScript-Fehler werden als Hinweis auf fehlerhafte Verträge ernst genommen
Analyzer-Logik wird getestet
Persistenz-Normalisierung wird getestet
API-Verträge werden durch Feature-Tests abgesichert
17. Definition of Done

Eine Aufgabe ist erst abgeschlossen wenn

Docker läuft
Backend-Tests erfolgreich sind
Frontend-Lint erfolgreich ist
TypeScript-Build erfolgreich ist
Architektur sauber bleibt
API-Verträge stabil sind
ADR bei Bedarf erstellt oder ergänzt wurde
Handbook aktualisiert wurde
Roadmap geprüft wurde
18. Sprint Workflow

Jeder Sprint besitzt

Ziel
Aufgaben
Definition of Done
Architekturentscheidungen
Abschluss

Am Ende eines Sprints wird geprüft:

Was wurde fachlich erreicht?
Was wurde technisch verbessert?
Welche Architekturentscheidungen wurden getroffen?
Müssen ROADMAP, Handbook, ADRs oder README angepasst werden?
Ist ein sinnvoller Commit-Zustand erreicht?
19. Dokumentation

ROADMAP beschreibt den Entwicklungsplan.

Engineering Handbook beschreibt die Arbeitsweise.

ADR beschreibt Architekturentscheidungen.

README erklärt das Projekt.

AGENTS.md enthält Regeln für KI-Assistenten.

Dokumentation wird nicht erst am Projektende gepflegt, sondern sprintweise aktualisiert.

20. Philosophie

Wir entwickeln keine Software, die heute funktioniert.

Wir entwickeln Software, die in fünf Jahren noch erweitert werden kann.