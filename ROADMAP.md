# ACD Analyzer – Roadmap

## Ziel des Projekts

Der **ACD Analyzer** ist zunächst ein internes Werkzeug von **Alan Carl Digital (ACD)** zur effizienten und reproduzierbaren Analyse von Unternehmenswebsites.

Der Analyzer ist aktuell **kein SaaS-Produkt** und keine öffentlich zugängliche Analyseplattform.

Sein primäres Ziel ist:

> Möglichst viele objektiv prüfbare Bestandteile einer professionellen Website-Analyse automatisiert zu erfassen, aufzubereiten und zu bewerten, sodass die manuelle Analysezeit deutlich reduziert wird.

Der Analyzer unterstützt insbesondere das ACD-Angebot **„Website-Analyse“**.

Der Mensch bleibt dabei ein wesentlicher Bestandteil der Analyse.

Der Analyzer sammelt Fakten, erkennt technische Auffälligkeiten und bereitet Findings vor.

ACD bewertet anschließend insbesondere:

* geschäftliche Relevanz
* Nutzerführung
* Qualität des Anfrageprozesses
* Priorität von Problemen
* Verbesserungspotenziale
* Aufwand und Nutzen möglicher Maßnahmen

Ziel ist nicht, möglichst viele technische Meldungen zu erzeugen.

Ziel ist eine verständliche Antwort auf:

> **Was sollte an dieser Website verbessert werden, warum ist es relevant und was sollte zuerst passieren?**

---

# Geschäftsmodell

Der Analyzer unterstützt den ACD-Kundenprozess:

```text
Analysieren
    ↓
Verbesserungspotenziale erkennen
    ↓
Entwickeln / Optimieren
    ↓
Betreuen & Monitoring
    ↓
später: Digitalisieren & Automatisieren
```

Eine Website-Analyse kann dadurch gleichzeitig ein eigenständiges Produkt und der Einstieg in eine längerfristige Kundenbeziehung sein.

---

# Ziel für Version 1.0

Version 1.0 ist erreicht, wenn mit dem Analyzer eine reale Unternehmenswebsite zuverlässig untersucht und daraus eine professionelle ACD Website-Analyse erstellt werden kann.

Der komplette Prozess soll langfristig ungefähr folgendes Zeitbudget ermöglichen:

| Tätigkeit                       |                Ziel |
| ------------------------------- | ------------------: |
| Website anlegen & Crawl starten |             ~10 min |
| automatische Ergebnisse prüfen  |             ~30 min |
| technische Stichprobe           |             ~20 min |
| UX & mobile Darstellung         |             ~30 min |
| Anfrageprozess                  |             ~30 min |
| Findings priorisieren           |             ~20 min |
| Bericht kontrollieren           |             ~20 min |
| Ergebnisgespräch                |             ~45 min |
| Administration                  |             ~15 min |
| **Gesamt**                      | **ca. 3–4 Stunden** |

Der Analyzer soll insbesondere die zeitaufwendige Datensammlung und wiederkehrende technische Bewertung automatisieren.

---

# Entwicklungsprinzipien

## 1. Business Value vor Featureumfang

Vor Version 1.0 wird ein Feature nur umgesetzt, wenn es mindestens eines dieser Ziele erfüllt:

1. Es spart bei einer realen Website-Analyse messbar Zeit.
2. Es verbessert die Qualität oder Zuverlässigkeit der Analyse.
3. Es erzeugt Informationen, die für Kunden relevant sind.
4. Es hilft dabei, konkrete Folgeaufträge zu erkennen.
5. Es wird für den professionellen Analysebericht benötigt.

Features außerhalb dieser Kriterien werden zurückgestellt.

---

## 2. Analyzer statt SaaS

Vor Version 1.0 werden insbesondere nicht priorisiert:

* öffentliche Benutzerregistrierung
* Kundenaccounts
* Bezahlsystem
* öffentliches SaaS
* Mandantenfähigkeit
* öffentliche API als Produkt
* Branchenbenchmarking
* Wettbewerbsdatenbanken
* komplexe KI-Funktionen
* automatische KI-Berichte

Diese Funktionen können später neu bewertet werden.

---

## 3. Automatisieren, was objektiv messbar ist

Der Analyzer soll objektiv prüfbare Informationen möglichst automatisch erfassen.

Beispiele:

* Statuscodes
* Meta-Daten
* Links
* Sicherheitsheader
* externe Dienste
* Performance-Metriken
* Accessibility-Verstöße
* Formulareigenschaften

Subjektive oder geschäftliche Bewertungen bleiben zunächst bewusst beim Menschen.

Beispiele:

* Ist die Startseite verständlich?
* Ist die Nutzerführung sinnvoll?
* Erzeugt die Website Vertrauen?
* Ist das Anfrageformular für diesen Betrieb angemessen?
* Welche Maßnahme besitzt den größten geschäftlichen Nutzen?

---

# Milestone 0 – Bestehende Grundlage

Bereits vorhandene Funktionen bilden die Basis der weiteren Entwicklung.

Dazu gehören insbesondere:

* Laravel Backend
* Next.js Frontend
* PostgreSQL
* Docker
* Playwright Renderer
* Website-Modell
* CrawlRun
* Page
* Link
* Image
* Heading
* CrawlError
* PageIssue
* DetectedTechnology
* Website-Crawling
* Crawl-Tiefe und Seitenlimits
* HTML Parsing
* Page Issue Analysis
* Technology Detection
* Health Score
* Ergebnisaggregation
* Crawl-Historie
* Ergebnisansicht
* Dashboard-Grundlage

Diese Architektur wird weiterentwickelt und nicht grundsätzlich ersetzt.

---

# Milestone 1 – Crawl Reliability

## Ziel

Bevor weitere Analysefunktionen entstehen, muss sichergestellt werden, dass die Datengrundlage zuverlässig ist.

## Status: In Progress (4/7 Sub-Milestones abgeschlossen)

## Aufgaben

### M1.1 – URL & Link Consistency ✅ ABGESCHLOSSEN

* [x] URL-Normalisierung überprüfen (RFC 3986-konform)
* [x] relative URLs zuverlässig auflösen (../contact, ./about, etc.)
* [x] Fragment-URLs korrekt behandeln (entfernt bei Normalisierung)
* [x] Query-Parameter sinnvoll behandeln (beibehalten)
* [x] HTTP/HTTPS-Varianten erkennen (interne Links übernehmen Schema der Base-URL)
* [x] www/non-www berücksichtigen (normalisiert für Vergleiche)
* [x] externe URLs eindeutig von internen URLs unterscheiden (zentrale `isInternal()` Logik)
* [x] `normalized_url` zu links Tabelle hinzugefügt
* [x] Queue-Deduplizierung mit visited + queued Sets
* [x] 30 Unit Tests + 7 Feature Tests
* [x] ADR-0011 dokumentiert

**Migration:** `2026_08_31_100000_add_normalized_url_to_links_table.php`

---

### M1.2 – Redirect Handling ✅ ABGESCHLOSSEN

* [x] Redirect-Ziele korrekt behandeln
* [x] Redirect-Ketten erkennen und tracken
* [x] 3xx zuverlässig behandeln (301, 302, 307, 308)
* [x] Redirect-Count speichern
* [x] Redirect-Chain mit Status Codes speichern
* [x] Relative Location Headers auflösen
* [x] Max Redirects (10) gegen Loops
* [x] `requested_url` und `final_url` in pages Tabelle
* [x] Queue-Deduplizierung berücksichtigt `final_url`
* [x] 11 Unit Tests + 5 Feature Tests
* [x] ADR-0012 dokumentiert

**Migration:** `2026_08_31_110000_add_redirect_tracking_to_pages_table.php`

---

### M1.3 – robots.txt Support ✅ ABGESCHLOSSEN

* [x] robots.txt abrufen
* [x] Existenz erfassen (200/404/5xx unterscheidbar)
* [x] grundlegende Direktiven auswerten
* [x] Sitemap-Verweise erkennen
* [x] User-agent Regeln parsen
* [x] Disallow-Pfade speichern
* [x] Allow-Pfade speichern
* [x] Kommentare und Leerzeilen behandeln
* [x] CRLF/CR/LF Line Endings unterstützen
* [x] Case-insensitive Direktiven
* [x] Timeout-Behandlung
* [x] URL-Normalisierung
* [x] 18 Unit Tests für Parser + 11 Unit Tests für Fetcher + 8 Feature Tests
* [x] ADR-0013 dokumentiert

**Migration:** `2026_08_31_081300_create_robots_txt_table.php`

---

### M1.4 – sitemap.xml Support ✅ ABGESCHLOSSEN

* [x] Sitemap erkennen (robots.txt + /sitemap.xml fallback)
* [x] Sitemap laden und parsen
* [x] URLs extrahieren mit lastmod, changefreq, priority
* [x] Original- und normalisierte URLs speichern
* [x] Nested Sitemaps mit Limits unterstützen (MAX_SITEMAPS=50, MAX_DEPTH=3, MAX_URLS=10000)
* [x] Sitemap Indizes rekursiv folgen
* [x] XML-Namespaces behandeln
* [x] Sitemaps ohne Namespaces unterstützen
* [x] 404/5xx/Timeout/ungültiges XML unterscheidbar speichern
* [x] Zwei-Tabellen-Architektur (sitemaps + sitemap_urls)
* [x] parent_sitemap_id für verschachtelte Sitemaps
* [x] Redirects korrekt behandeln
* [x] 17 Unit Tests für Parser + 14 Unit Tests für Fetcher + 10 Feature Tests
* [x] ADR-0014 dokumentiert

**Migrations:** 
- `2026_08_31_081715_create_sitemaps_table.php`
- `2026_08_31_081716_create_sitemap_urls_table.php`

**Hinweis:** Sitemap-URLs sind jetzt erfasst, werden aber noch nicht automatisch zum Crawl-Queue hinzugefügt. Gap-Analyse (Vergleich Sitemap ↔ Crawl) folgt in späteren Milestones.

---

### M1.5 – Canonical Handling

* [ ] Canonical extrahieren (link rel="canonical")
* [ ] fehlende Canonicals erkennen
* [ ] Canonical auf andere URL erkennen
* [ ] Canonical auf fehlerhafte URL erkennen
* [ ] widersprüchliche Canonicals erkennen
* [ ] Self-referencing Canonicals validieren

---

### M1.6 – Error Handling & Logging

* [ ] 4xx erfassen und kategorisieren
* [ ] 5xx erfassen und kategorisieren
* [ ] Timeouts erfassen
* [ ] Crawl-Abbrüche sauber protokollieren
* [ ] Connection-Fehler behandeln
* [ ] DNS-Fehler behandeln

---

### M1.7 – Renderer Integration

* [ ] Renderer-Zuverlässigkeit prüfen
* [ ] JS-lastige Websites erkennen
* [ ] Renderer-Fallback definieren
* [ ] Render-Timeouts behandeln
* [ ] Renderer-Fehler protokollieren
* [ ] Redirect-Tracking im Renderer (Playwright Network Monitoring)

## Definition of Done

Eine typische Unternehmenswebsite kann reproduzierbar gecrawlt werden und die gespeicherten Seiten entsprechen weitgehend der tatsächlich erreichbaren Website.

---

# Milestone 2 – Website-Level Analysis

## Ziel

Zusätzlich zu Problemen einzelner Seiten müssen Probleme erkannt werden, die erst im Vergleich mehrerer Seiten sichtbar werden.

## Architektur

Ein eigener Analyzer wird eingeführt:

```text
WebsiteIssueAnalyzer
```

bzw. eine vergleichbare klar getrennte Website-Level-Analyse.

## Aufgaben

* [ ] Duplicate Titles erkennen
* [ ] Duplicate Meta Descriptions erkennen
* [ ] Duplicate H1 erkennen
* [ ] fehlende Titles websiteweit aggregieren
* [ ] fehlende Descriptions aggregieren
* [ ] fehlende H1 aggregieren
* [ ] Canonical-Konflikte erkennen
* [ ] Broken Internal Links aggregieren
* [ ] Seiten ohne interne eingehende Links erkennen
* [ ] ungewöhnlich tiefe Seiten erkennen
* [ ] Sitemap/Crawl-Abweichungen erkennen
* [ ] HTTP/HTTPS-Inkonsistenzen erkennen
* [ ] Host-Inkonsistenzen erkennen

## Definition of Done

Der Analyzer kann nicht nur sagen:

> „Auf Seite X fehlt ein Title.“

sondern beispielsweise:

> „Auf 14 von 32 untersuchten Seiten fehlen individuelle Meta Descriptions.“

---

# Milestone 3 – Security Analysis

## Ziel

Grundlegende technische Sicherheitsmerkmale einer Website automatisiert prüfen.

Der Analyzer führt ausdrücklich **keinen Penetrationstest** und keine vollständige Sicherheitsprüfung durch.

## Aufgaben

### Transport

* [ ] HTTPS prüfen
* [ ] HTTP → HTTPS Redirect prüfen
* [ ] Mixed Content erkennen

### Security Headers

* [ ] Strict-Transport-Security
* [ ] Content-Security-Policy
* [ ] X-Content-Type-Options
* [ ] Referrer-Policy
* [ ] Permissions-Policy
* [ ] X-Frame-Options

### Weitere Indikatoren

* [ ] unsichere Form Actions erkennen
* [ ] HTTP-Ressourcen erkennen
* [ ] offensichtliche Versionsinformationen erfassen, soweit sinnvoll
* [ ] externe Scriptquellen erfassen

## Definition of Done

Der Analyzer kann grundlegende technische Sicherheitsmerkmale nachvollziehbar darstellen und Auffälligkeiten als Findings ausgeben.

---

# Milestone 4 – Privacy & External Services

## Ziel

Technisch erkennbare externe Dienste und datenschutzrelevante Integrationen identifizieren.

Die Analyse stellt **keine Rechtsberatung und keine rechtliche DSGVO-Prüfung** dar.

## Phase 1 – Detection

Erkennung unter anderem von:

* [ ] Google Analytics
* [ ] Google Tag Manager
* [ ] Google Fonts
* [ ] Google Maps
* [ ] YouTube
* [ ] Vimeo
* [ ] Meta Pixel
* [ ] reCAPTCHA
* [ ] Hotjar
* [ ] Matomo
* [ ] HubSpot
* [ ] weiteren relevanten externen Diensten

### Consent Management

Erkennung unter anderem von:

* [ ] Cookiebot
* [ ] Usercentrics
* [ ] Borlabs Cookie
* [ ] Complianz
* [ ] consentmanager
* [ ] weiteren CMPs

### Externe Ressourcen

* [ ] externe Scripts erfassen
* [ ] externe Stylesheets erfassen
* [ ] externe Fonts erfassen
* [ ] externe Frames erfassen
* [ ] externe Requests soweit sinnvoll kategorisieren

## Phase 2 – Consent Behaviour

Erst nach stabiler Phase 1:

* [ ] Browser-Network-Requests erfassen
* [ ] Requests vor Consent erfassen
* [ ] Requests nach Consent vergleichbar machen
* [ ] Tracker vor Einwilligung erkennen
* [ ] auffälliges Consent-Verhalten dokumentieren

## Definition of Done

ACD erhält schnell einen Überblick darüber, welche externen Dienste eine Website technisch einbindet und welche Punkte manuell näher geprüft werden sollten.

---

# Milestone 5 – Accessibility

## Ziel

Automatisierte Barrierefreiheitsprüfung als Bestandteil der Website-Analyse.

## Technologie

Bevorzugt:

```text
Playwright + axe-core
```

## Aufgaben

* [ ] axe-core integrieren
* [ ] Scan über Renderer ausführen
* [ ] Violations erfassen
* [ ] Impact speichern
* [ ] betroffene Elemente speichern
* [ ] Rule-ID speichern
* [ ] Beschreibung speichern
* [ ] Ergebnisse in Analyzer-Findings überführen

Zusätzliche eigene Prüfungen nur dort, wo sie einen klaren Mehrwert bieten.

## Manuelle Prüfung

Automatisierte Ergebnisse ersetzen keine vollständige Accessibility-Prüfung.

ACD ergänzt insbesondere Stichproben zu:

* Tastaturbedienung
* Navigation
* Verständlichkeit
* Fokusverhalten
* visueller Nutzbarkeit

## Definition of Done

Automatisch erkennbare Accessibility-Probleme werden reproduzierbar erfasst und für die manuelle Bewertung aufbereitet.

---

# Milestone 6 – Screenshots & Visual Inspection

## Ziel

Die manuelle visuelle Prüfung soll möglichst schnell durchgeführt werden können.

## Aufgaben

### Desktop

* [ ] Desktop Screenshot erzeugen
* [ ] sinnvolle Standardauflösung definieren
* [ ] Full-Page Screenshot unterstützen

### Mobile

* [ ] Smartphone Viewport definieren
* [ ] Mobile Screenshot erzeugen
* [ ] Full-Page Screenshot unterstützen

### Speicherung

* [ ] Screenshots CrawlRun/Page zuordnen
* [ ] Screenshots im Dashboard anzeigen
* [ ] Fehler beim Screenshot erfassen

## Später optional

* Screenshot-Vergleich zwischen Crawls
* visuelle Regression
* automatische Layout-Anomalien

Diese Funktionen sind nicht Teil von Version 1.0.

## Definition of Done

ACD kann die wichtigsten Seiten einer Website direkt aus dem Analyzer in Desktop- und Mobilansicht überprüfen.

---

# Milestone 7 – Form & Inquiry Analysis

## Ziel

Formulare und Anfragewege technisch erfassen und für die manuelle geschäftliche Bewertung vorbereiten.

Dies ist ein strategisch wichtiger Bereich für ACD.

## Datenextraktion

Für jedes Formular soweit möglich erfassen:

* [ ] Seite
* [ ] action
* [ ] method
* [ ] Anzahl Felder
* [ ] Input Types
* [ ] Textareas
* [ ] Selects
* [ ] Checkboxen
* [ ] Radio Buttons
* [ ] File Upload
* [ ] required
* [ ] Labels
* [ ] autocomplete
* [ ] Submit Element
* [ ] Datenschutzhinweis/Checkbox soweit technisch erkennbar

## Automatische Findings

Mögliche Regeln:

* [ ] Formular ohne Labels
* [ ] Formular ohne Submit
* [ ] unsichere Form Action
* [ ] ungeeigneter Input Type für E-Mail
* [ ] ungeeigneter Input Type für Telefonnummer
* [ ] fehlendes Autocomplete
* [ ] sehr hohe Anzahl Pflichtfelder markieren
* [ ] File Upload erkennen
* [ ] externe Formularanbieter erkennen

## Wichtig

Der Analyzer soll nicht automatisch entscheiden:

> „Dieses Formular hat zu viele Felder.“

Stattdessen:

> „Dieses Formular besitzt 14 Felder, davon 11 Pflichtfelder.“

Die geschäftliche Bewertung erfolgt durch ACD.

## Definition of Done

ACD erhält ohne manuelles Durchsuchen der Website einen strukturierten Überblick über vorhandene Formulare und deren technische Eigenschaften.

---

# Milestone 8 – Performance Analysis

## Ziel

Relevante Performance-Daten automatisch erfassen, ohne bestehende etablierte Messverfahren selbst nachzubauen.

## Aufgaben

* [ ] Lighthouse integrieren oder vergleichbare etablierte Messung verwenden
* [ ] Performance Score erfassen
* [ ] Largest Contentful Paint
* [ ] Cumulative Layout Shift
* [ ] First Contentful Paint
* [ ] Total Blocking Time bzw. geeignete Lab-Metriken
* [ ] relevante Performance Audits erfassen
* [ ] Desktop/Mobile sinnvoll unterscheiden
* [ ] Ergebnisse normalisieren

## Wichtig

Nicht alle Lighthouse-Meldungen werden ungefiltert an Kunden weitergegeben.

ACD priorisiert die geschäftlich und technisch relevanten Ergebnisse.

## Definition of Done

Performance-Probleme können schnell erkannt, verglichen und verständlich in Findings überführt werden.

---

# Milestone 9 – Unified Finding Model

## Ziel

Technische Issues werden zu verständlichen und bearbeitbaren Analyse-Findings.

Das Finding wird zum zentralen Element des Analyseprozesses.

## Zielstruktur

Ein Finding sollte konzeptionell mindestens enthalten:

```text
code
category
severity
title
description
evidence
recommendation
business_impact
effort
source
page
```

Nicht jedes Feld muss zwingend automatisch gesetzt werden.

## Kategorien

Mindestens:

* Technik
* SEO
* Performance
* Sicherheit
* Datenschutz-Technik
* Barrierefreiheit
* Mobile
* Nutzerführung
* Anfrageprozess
* Best Practices

## Priorität

Beispielsweise:

```text
high
medium
low
```

## Aufwand

Beispielsweise:

```text
small
medium
large
```

## Source

Beispielsweise:

```text
crawler
page_analyzer
website_analyzer
security
privacy
axe
lighthouse
form_analyzer
manual
```

## Evidence

Findings sollen soweit möglich nachvollziehbare Belege enthalten.

Beispiele:

* betroffene URL
* Element
* Header
* gemessener Wert
* Anzahl betroffener Seiten
* externe Domain

## Definition of Done

Ein Finding beantwortet nicht nur:

> „Was ist technisch passiert?“

sondern kann als Grundlage für eine konkrete Kundenempfehlung verwendet werden.

---

# Milestone 10 – Manual Analysis Workflow

## Ziel

Automatische und manuelle Findings werden in einem gemeinsamen Analyseprozess zusammengeführt.

## Aufgaben

* [ ] Findings im Dashboard anzeigen
* [ ] Finding aktiv/inaktiv setzen
* [ ] Finding bearbeiten
* [ ] Priorität ändern
* [ ] Aufwand setzen
* [ ] Empfehlung bearbeiten
* [ ] Business Impact ergänzen
* [ ] manuelles Finding erstellen
* [ ] Finding löschen/aus Bericht ausschließen
* [ ] Notizen ergänzen

## Manuelle Kategorien

Besonders relevant:

### Nutzerführung

* Verständlichkeit Startseite
* Navigation
* Informationsarchitektur
* Vertrauen
* Calls-to-Action
* mobile Nutzung

### Anfrageprozess

* Kontaktmöglichkeiten
* Formularqualität
* Informationsbedarf
* Hürden
* Bestätigung
* nächster Schritt
* Eignung für den jeweiligen Betrieb

## Definition of Done

ACD kann eine vollständige Website-Analyse innerhalb des Analyzer-Dashboards vorbereiten, ohne Ergebnisse parallel in externen Notizen sammeln zu müssen.

---

# Milestone 11 – Priorisierung & Recommendations

## Ziel

Aus technischen Befunden wird ein verständlicher Maßnahmenplan.

## Aufgaben

* [ ] Findings nach Priorität sortieren
* [ ] Findings nach Kategorie filtern
* [ ] Findings nach Aufwand filtern
* [ ] wichtigste Maßnahmen markieren
* [ ] Top-Maßnahmen definieren
* [ ] Sofortmaßnahmen definieren
* [ ] mittelfristige Maßnahmen definieren
* [ ] optionale Weiterentwicklung definieren

## Darstellung

Beispiel:

```text
Hohe Priorität
Kleiner Aufwand

Meta Descriptions für wichtige Leistungsseiten ergänzen.

Betroffen:
7 Seiten

Warum:
...

Empfehlung:
...
```

## Definition of Done

Die Analyse liefert eine nachvollziehbare Reihenfolge konkreter Maßnahmen und nicht lediglich eine Sammlung technischer Fehler.

---

# Milestone 12 – Report Generator

## Ziel

Aus den freigegebenen Findings wird automatisch ein professioneller Analysebericht vorbereitet.

## Berichtstruktur

### 1. Deckblatt

* Unternehmen
* Website
* Datum
* ACD Website-Analyse

### 2. Executive Summary

Kurze Gesamtbewertung.

### 3. Wichtigste Maßnahmen

Beispielsweise:

```text
Die 5 wichtigsten Maßnahmen
```

### 4. Kategorien

* Technik
* SEO
* Performance
* Sicherheit
* Datenschutz-Technik
* Barrierefreiheit
* Nutzerführung
* Anfrageprozess

### 5. Maßnahmenplan

Unterteilung beispielsweise in:

* Sofortmaßnahmen
* mittelfristige Verbesserungen
* optionale Weiterentwicklung

### 6. Methodik & Grenzen

Insbesondere Hinweise:

* keine Rechtsberatung
* kein Penetrationstest
* automatisierte Accessibility-Tests sind nicht vollständig
* Performance-Messungen sind Momentaufnahmen
* Analyse basiert auf dem untersuchten Crawl-Zeitpunkt

### 7. Nächste Schritte

Sachlicher Hinweis darauf, dass ACD auf Wunsch bei der Umsetzung der Maßnahmen unterstützen kann.

## Definition of Done

Der Bericht kann weitgehend aus den vorhandenen Analyzer-Daten erzeugt werden und benötigt nur noch eine abschließende menschliche Kontrolle.

---

# Milestone 13 – PDF Export

## Ziel

Der fertige Bericht kann professionell an Kunden ausgeliefert werden.

## Aufgaben

* [ ] PDF-Layout definieren
* [ ] ACD Branding integrieren
* [ ] Inhaltsverzeichnis falls sinnvoll
* [ ] Seitenumbrüche kontrollieren
* [ ] Findings sauber darstellen
* [ ] Screenshots optional einbinden
* [ ] Datum und CrawlRun dokumentieren
* [ ] PDF erzeugen
* [ ] PDF archivieren

## Definition of Done

Eine vollständige ACD Website-Analyse kann als professioneller PDF-Bericht an einen zahlenden Kunden ausgeliefert werden.

---

# Milestone 14 – Real World Validation

## Ziel

Nicht weiterentwickeln, sondern testen.

Nach Erreichen der vorherigen Milestones wird eine reale Unternehmenswebsite analysiert.

## Ablauf

Timer starten.

```text
Website anlegen
↓
Crawl
↓
automatische Analyse
↓
Ergebnisse prüfen
↓
manuelle Analyse
↓
Findings priorisieren
↓
Bericht erstellen
↓
Bericht kontrollieren
```

Dokumentieren:

* [ ] Gesamtdauer
* [ ] Crawl-Dauer
* [ ] manuelle Analysezeit
* [ ] Zeit für Bericht
* [ ] fehlende Informationen
* [ ] falsche Positive
* [ ] falsche Negative
* [ ] unnötige Findings
* [ ] wiederkehrende manuelle Tätigkeiten
* [ ] Stellen mit Medienbruch
* [ ] technische Fehler

## Entscheidungsregel

Neue Features werden anschließend anhand real beobachteter Probleme priorisiert.

Beispiel:

> Wenn bei drei Analysen dieselbe Information jeweils 15 Minuten manuell gesucht werden muss, ist dies ein starker Kandidat für Automatisierung.

## Definition of Done

Mindestens eine vollständige Analyse wurde mit einer realen, fremden Unternehmenswebsite durchgeführt und der Workflow anhand der tatsächlichen Erfahrung verbessert.

---

# Version 1.0 – Sellable Analyzer

Version 1.0 ist erreicht, wenn:

* [ ] reale Unternehmenswebsites zuverlässig gecrawlt werden
* [ ] technische Website-Probleme erkannt werden
* [ ] Website-Level-Probleme erkannt werden
* [ ] grundlegende Security-Prüfungen vorhanden sind
* [ ] externe Dienste und Datenschutzindikatoren erkannt werden
* [ ] Accessibility automatisiert geprüft wird
* [ ] Desktop-/Mobile-Prüfung unterstützt wird
* [ ] Formulare strukturiert analysiert werden
* [ ] Performance-Daten vorhanden sind
* [ ] automatische Findings erzeugt werden
* [ ] manuelle Findings ergänzt werden können
* [ ] Findings priorisiert und bearbeitet werden können
* [ ] Empfehlungen hinterlegt werden können
* [ ] ein Maßnahmenplan erstellt werden kann
* [ ] ein professioneller Bericht erzeugt werden kann
* [ ] PDF-Export funktioniert
* [ ] der vollständige Workflow an realen Websites getestet wurde

Dann gilt:

> **Der Analyzer ist ausreichend fertig, um die ACD Website-Analyse professionell zu verkaufen.**

Nicht erforderlich für Version 1.0:

> perfekte Software.

---

# Nach Version 1.0

Die weitere Entwicklung richtet sich nach realer Kundennachfrage.

---

## Version 1.x – Workflow Optimization

Mögliche Weiterentwicklungen:

* Analysezeit weiter reduzieren
* bessere False-Positive-Erkennung
* wiederkehrende Empfehlungen
* Finding Templates
* Vergleich mehrerer Crawls
* automatische Rechecks
* bessere Screenshots
* bessere Report-Erstellung
* Maßnahmenstatus
* Projektübergabe aus Analyse

Priorisierung ausschließlich nach tatsächlichem Nutzen.

---

# Phase 2 – Betreuung & Monitoring

Wenn ACD erste Betreuungskunden besitzt, kann der Analyzer zur Monitoring-Plattform weiterentwickelt werden.

Mögliche Funktionen:

* geplante Crawls
* regelmäßige technische Checks
* Uptime Monitoring
* SSL Monitoring
* Domain Monitoring
* Performance-Verlauf
* neue Fehler seit letztem Crawl
* behobene Fehler
* neue externe Dienste
* Änderungen an Technologien
* Accessibility Regression
* regelmäßiger Kundenbericht

Ziel:

> Wiederkehrende Betreuung effizienter machen und den Wert der Betreuung sichtbar machen.

---

# Phase 3 – Digitalization & Request Intelligence

Wenn reale Kundenanforderungen dies bestätigen, kann der Bereich Anfrageprozesse deutlich ausgebaut werden.

Mögliche Funktionen:

* detaillierte Formanalyse
* Anfragewege erkennen
* Funnel-Schritte modellieren
* CRM-Integrationen erkennen
* Medienbrüche dokumentieren
* manuelle Prozesse erfassen
* Optimierungspotenziale bewerten
* Request-Flow-Dokumentation
* Request Engine

Dieser Bereich wird erst priorisiert, wenn reale Kundenprojekte die Nachfrage bestätigen.

---

# Phase 4 – Productization

Erst nach erfolgreichem Einsatz im ACD-Kundengeschäft wird geprüft, ob Teile des Analyzers als eigenständiges Produkt sinnvoll sind.

Mögliche Optionen:

* Kundenportal
* Self-Service Website Check
* Agenturtool
* Monitoring SaaS
* API
* White Label
* Benchmarking
* Branchenvergleich
* automatisierte Reports
* KI-Unterstützung

Keine dieser Optionen ist aktuell Teil des Kernprojekts.

---

# KI

KI wird nicht integriert, nur weil sie technisch möglich ist.

Eine Integration erfolgt nur, wenn ein konkreter Nutzen nachgewiesen werden kann.

Sinnvolle zukünftige Einsatzmöglichkeiten könnten sein:

* Zusammenfassung technischer Findings
* Formulierung verständlicher Erklärungen
* Clustering ähnlicher Probleme
* Unterstützung bei Empfehlungen
* Zusammenfassung großer Websites
* Erkennung ungewöhnlicher Muster
* Unterstützung bei manueller UX-Analyse

Entscheidungen über Priorität, geschäftliche Relevanz und Kundenempfehlungen bleiben nachvollziehbar und kontrollierbar.

---

# Langfristige Vision

Der ACD Analyzer kann langfristig die technische Grundlage für mehrere Bereiche von Alan Carl Digital bilden:

```text
Website Analyse
       ↓
Optimierung / Entwicklung
       ↓
Betreuung
       ↓
Monitoring
       ↓
Prozessanalyse
       ↓
Digitalisierung
```

Die langfristige Vision darf jedoch die aktuelle Priorität nicht verdrängen.

---

# Aktuelle Priorität

```text
1. Crawl Reliability
2. Website-Level Analysis
3. Security Analysis
4. Privacy & External Services
5. Accessibility
6. Screenshots
7. Forms & Inquiry Analysis
8. Performance
9. Unified Finding Model
10. Manual Analysis Workflow
11. Priorisierung & Recommendations
12. Report Generator
13. PDF Export
14. Real World Validation
```

Danach:

```text
STOP FEATURE DEVELOPMENT
↓
erste reale Analysen durchführen
↓
Zeit messen
↓
Probleme beobachten
↓
gezielt verbessern
```

---

# Leitfrage für jede neue Funktion

Vor jeder Implementierung:

> **Hilft diese Funktion dabei, eine ACD Website-Analyse schneller, zuverlässiger oder wertvoller für einen zahlenden Kunden zu machen?**

Wenn die Antwort **nein** lautet:

> **Backlog.**

Wenn die Antwort **ja** lautet:

> Nutzen gegen Implementierungsaufwand bewerten und entsprechend priorisieren.

---

**Aktuelles Hauptziel:**

> **Nicht den perfekten Analyzer bauen. Den Analyzer bauen, mit dem ACD zuverlässig Geld verdienen kann.**
