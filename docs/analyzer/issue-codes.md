# Analyzer Issue Codes

Diese Datei dokumentiert die aktuell bekannten Issue-Codes des ACD Analyzers.

Issue-Codes sind bewusst stabil und technisch lesbar gehalten. Sie bilden die Grundlage für:

* Dashboard-Anzeige
* Filter nach Schweregrad
* spätere Score-Berechnung
* Reports
* verständliche Handlungsempfehlungen
* Tests der Analyzer-Regeln

## Severity-Modell

ACD verwendet aktuell drei Schweregrade:

| Severity  | Bedeutung                                                         |
| --------- | ----------------------------------------------------------------- |
| `error`   | Klares Basisproblem, das technisch oder inhaltlich stark auffällt |
| `warning` | Wahrscheinliches Optimierungspotenzial oder Qualitätsproblem      |
| `info`    | Hinweis oder Kontext, der für spätere Bewertung nützlich ist      |

Die Severity ist keine endgültige SEO-Wahrheit, sondern eine erste interne Priorisierung.

---

## Übersicht

| Code                         |  Severity | Bereich          |
| ---------------------------- | --------: | ---------------- |
| `missing_title`              |   `error` | Title            |
| `title_too_short`            | `warning` | Title            |
| `title_too_long`             | `warning` | Title            |
| `missing_meta_description`   | `warning` | Meta Description |
| `meta_description_too_short` | `warning` | Meta Description |
| `meta_description_too_long`  | `warning` | Meta Description |
| `missing_h1`                 |   `error` | Headings         |
| `multiple_h1`                | `warning` | Headings         |
| `missing_h2_structure`       | `warning` | Headings         |
| `duplicate_heading_text`     |    `info` | Headings         |
| `images_without_alt`         | `warning` | Images           |
| `high_missing_alt_ratio`     | `warning` | Images           |
| `few_internal_links`         | `warning` | Links            |
| `empty_link_href`            | `warning` | Links            |
| `insecure_external_links`    | `warning` | Links            |
| `many_external_links`        | `warning` | Links            |
| `missing_html_lang`          | `warning` | Technical SEO    |
| `missing_viewport_meta`      | `warning` | Technical SEO    |
| `robots_noindex`             |   `error` | Technical SEO    |
| `missing_canonical`          |    `info` | Technical SEO    |
| `very_low_text_content`      | `warning` | Content          |
| `large_html_size`            |    `info` | HTML             |
| `crawl_error`                |   `error` | Crawl            |

---

## Title

### `missing_title`

**Severity:** `error`

**Bedeutung:**
Die Seite hat keinen HTML-Title.

**Warum relevant:**
Der Title ist eines der wichtigsten Basiselemente einer HTML-Seite. Er wird im Browser-Tab angezeigt, kann in Suchergebnissen erscheinen und hilft dabei, den Inhalt einer Seite einzuordnen.

**Aktuelle Regel:**
Der Title fehlt oder ist leer.

**Mögliche spätere Verbesserung:**

* Prüfung auf doppelte Titles innerhalb eines Crawl-Laufs
* Prüfung auf generische Titles wie „Home“ oder „Untitled“
* kundenverständlichere Handlungsempfehlung ergänzen

---

### `title_too_short`

**Severity:** `warning`

**Bedeutung:**
Der Title ist auffällig kurz.

**Warum relevant:**
Ein sehr kurzer Title beschreibt den Seiteninhalt oft nicht ausreichend.

**Aktuelle Regel:**
Der Title ist kürzer als der aktuelle Mindestwert im `PageIssueAnalyzer`.

**Aktueller Schwellwert:**
`MIN_TITLE_LENGTH = 10`

**Mögliche spätere Verbesserung:**

* Schwellwert mit echten Crawl-Daten prüfen
* Seitentyp berücksichtigen
* sehr kurze Markennamen gesondert behandeln

---

### `title_too_long`

**Severity:** `warning`

**Bedeutung:**
Der Title ist auffällig lang.

**Warum relevant:**
Ein sehr langer Title kann unübersichtlich sein und in Suchergebnissen gekürzt werden.

**Aktuelle Regel:**
Der Title ist länger als der aktuelle Maximalwert im `PageIssueAnalyzer`.

**Aktueller Schwellwert:**
`MAX_TITLE_LENGTH = 60`

**Mögliche spätere Verbesserung:**

* Schwellwert anhand echter Beispiele prüfen
* Pixelbreite statt Zeichenlänge berücksichtigen
* Duplikate und überladene Keyword-Listen erkennen

---

## Meta Description

### `missing_meta_description`

**Severity:** `warning`

**Bedeutung:**
Die Seite hat keine Meta Description.

**Warum relevant:**
Die Meta Description kann als Zusammenfassung einer Seite dienen und in Suchergebnissen sichtbar werden. Sie ist kein reines technisches Muss, aber ein wichtiges Qualitäts- und Kommunikationssignal.

**Aktuelle Regel:**
Die Meta Description fehlt oder ist leer.

**Mögliche spätere Verbesserung:**

* doppelte Meta Descriptions erkennen
* generische Meta Descriptions erkennen
* Empfehlungen je Seitentyp ergänzen

---

### `meta_description_too_short`

**Severity:** `warning`

**Bedeutung:**
Die Meta Description ist auffällig kurz.

**Warum relevant:**
Eine sehr kurze Beschreibung erklärt den Seiteninhalt oft nicht ausreichend.

**Aktuelle Regel:**
Die Meta Description ist kürzer als der aktuelle Mindestwert im `PageIssueAnalyzer`.

**Aktueller Schwellwert:**
`MIN_META_DESCRIPTION_LENGTH = 50`

**Mögliche spätere Verbesserung:**

* Schwellwert mit echten Crawl-Daten prüfen
* sehr kurze Kontakt- oder Impressumsseiten gesondert bewerten
* später zwischen technischer Warnung und redaktioneller Empfehlung unterscheiden

---

### `meta_description_too_long`

**Severity:** `warning`

**Bedeutung:**
Die Meta Description ist auffällig lang.

**Warum relevant:**
Sehr lange Beschreibungen können unübersichtlich sein und in Suchergebnissen gekürzt werden.

**Aktuelle Regel:**
Die Meta Description ist länger als der aktuelle Maximalwert im `PageIssueAnalyzer`.

**Aktueller Schwellwert:**
`MAX_META_DESCRIPTION_LENGTH = 160`

**Mögliche spätere Verbesserung:**

* Pixelbreite statt Zeichenlänge berücksichtigen
* Snippet-Preview im Frontend vorbereiten
* überladene Keyword-Listen erkennen

---

## Headings

### `missing_h1`

**Severity:** `error`

**Bedeutung:**
Die Seite hat keine H1-Überschrift.

**Warum relevant:**
Die H1 ist ein zentrales Strukturelement einer Seite. Sie hilft Nutzern, Suchmaschinen und assistiven Technologien, das Hauptthema der Seite zu erkennen.

**Aktuelle Regel:**
Es wurde keine Überschrift mit Level `1` gefunden.

**Mögliche spätere Verbesserung:**

* prüfen, ob die H1 leer oder versteckt ist
* H1 und Title miteinander vergleichen
* semantische Struktur der Überschriften bewerten

---

### `multiple_h1`

**Severity:** `warning`

**Bedeutung:**
Die Seite enthält mehrere H1-Überschriften.

**Warum relevant:**
Mehrere H1-Überschriften sind nicht automatisch falsch, können aber auf eine unklare Seitenstruktur hinweisen.

**Aktuelle Regel:**
Es wurden mehr als eine H1-Überschrift gefunden.

**Mögliche spätere Verbesserung:**

* moderne HTML5-Sektionslogik berücksichtigen
* sichtbare und unsichtbare Überschriften unterscheiden
* Kontext der H1-Elemente analysieren

---

## Images

### `images_without_alt`

**Severity:** `warning`

**Bedeutung:**
Mindestens ein Bild hat kein alt-Attribut oder einen leeren alt-Text.

**Warum relevant:**
Alt-Texte sind wichtig für Barrierefreiheit, Screenreader und die inhaltliche Einordnung von Bildern.

**Aktuelle Regel:**
Mindestens ein Bild hat keinen oder einen leeren alt-Wert.

**Mögliche spätere Verbesserung:**

* dekorative Bilder gesondert behandeln
* fehlende alt-Attribute und leere alt-Attribute unterscheiden
* Bildkontext aus umliegendem Text analysieren

---

### `high_missing_alt_ratio`

**Severity:** `warning`

**Bedeutung:**
Ein hoher Anteil der Bilder hat kein alt-Attribut oder einen leeren alt-Text.

**Warum relevant:**
Ein einzelnes fehlendes alt-Attribut kann ein kleiner Fehler sein. Wenn viele Bilder betroffen sind, deutet das eher auf ein strukturelles Qualitätsproblem hin.

**Aktuelle Regel:**
Mindestens 50 Prozent der Bilder haben keinen oder einen leeren alt-Wert.

**Aktueller Schwellwert:**
`missingAltCount / imageCount >= 0.5`

**Mögliche spätere Verbesserung:**

* Mindestanzahl an Bildern berücksichtigen
* dekorative Bilder ausnehmen
* Severity abhängig vom Verhältnis und der Gesamtanzahl berechnen

---

## Links

### `few_internal_links`

**Severity:** `warning`

**Bedeutung:**
Die Seite hat sehr wenige interne Links.

**Warum relevant:**
Interne Links helfen Nutzern und Crawlern dabei, weitere relevante Inhalte zu finden. Sehr wenige interne Links können auf eine schwache interne Vernetzung hinweisen.

**Aktuelle Regel:**
Die Seite hat weniger interne Links als der aktuelle Mindestwert im `PageIssueAnalyzer`.

**Aktueller Schwellwert:**
`MIN_INTERNAL_LINKS = 2`

**Mögliche spätere Verbesserung:**

* Seitentyp berücksichtigen
* Navigation, Footer und Content-Links getrennt bewerten
* interne Linkziele und Ankertexte analysieren
* Severity später abhängig vom Seitentyp bewerten

---

## HTML

### `large_html_size`

**Severity:** `info`

**Bedeutung:**
Die gespeicherte HTML-Datei ist ungewöhnlich groß.

**Warum relevant:**
Sehr große HTML-Dokumente können auf aufgeblähtes Markup, viele Inline-Daten oder ineffiziente Seitengenerierung hinweisen. Das ist nicht automatisch ein Fehler, aber ein nützlicher technischer Hinweis.

**Aktuelle Regel:**
Die gespeicherte HTML-Größe überschreitet den aktuellen Schwellwert im `PageIssueAnalyzer`.

**Aktueller Schwellwert:**
`LARGE_HTML_SIZE_BYTES = 500000`

**Mögliche spätere Verbesserung:**

* HTML-Größe in Kilobyte im Issue ausgeben
* Verhältnis von sichtbarem Text zu HTML-Größe prüfen
* JavaScript-heavy Seiten gesondert erkennen
* später Performance-Checks ergänzen

---

## Crawl

### `crawl_error`

**Severity:** `error`

**Bedeutung:**
Die Seite konnte nicht erfolgreich gecrawlt werden.

**Warum relevant:**
Wenn eine Seite nicht gecrawlt werden kann, können keine verlässlichen Analyseergebnisse erzeugt werden.

**Aktuelle Regel:**
Ein Crawl-Fehler wurde gespeichert und als Ergebnis in die Analyseansicht gemappt.

**Mögliche spätere Verbesserung:**

* HTTP-Fehler und technische Fetch-Fehler getrennt ausweisen
* Redirect-Probleme gesondert behandeln
* Timeout, DNS, SSL und Robots-Regeln differenzieren
* Retry-Strategie vorbereiten
* 
## Crawl Health Score

Der Crawl Health Score ist eine erste, vereinfachte Bewertung eines Crawl-Laufs auf einer Skala von `0` bis `100`.

Der Score wird nicht als globale Summe aller Issues berechnet. Stattdessen wird zuerst für jede gecrawlte Seite beziehungsweise jeden Crawl-Fehler ein eigener Page Score berechnet. Der Crawl Health Score ist anschließend der gerundete Durchschnitt dieser Page Scores.

Jede Page startet mit `100` Punkten. Issues reduzieren den Page Score abhängig von ihrer Severity:

| Severity  | Abzug |
| --------- | ----: |
| `error`   |  `30` |
| `warning` |  `10` |
| `info`    |   `2` |

Ein Page Score kann nicht unter `0` fallen.

Crawl-Fehler werden wie eigene fehlerhafte Page Results behandelt und fließen dadurch ebenfalls in den Durchschnitt ein.

Beispiel:

```text
Page 1:
1 warning, 1 info
100 - 10 - 2 = 88

Crawl Error:
1 error
100 - 30 = 70

Crawl Health Score:
(88 + 70) / 2 = 79
