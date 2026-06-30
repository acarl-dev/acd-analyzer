# ACD Analyzer

**ACD Analyzer** ist ein internes Analyse- und Crawling-Dashboard für Alan Carl Digital. Das Projekt dient als technische Grundlage für eine spätere datengetriebene Website-Analyse-Plattform.

Der Analyzer kann aktuell eine Website per HTTP abrufen, zentrale HTML-Daten extrahieren, diese in PostgreSQL speichern und erste technische sowie SEO-nahe Hinweise im Dashboard anzeigen.

> **Projektstatus:** In Entwicklung  
> Das Projekt ist noch kein fertiges Produkt und aktuell als internes Lern-, Architektur- und Portfolio-Projekt angelegt.

---

## Ziel des Projekts

Langfristig soll der ACD Analyzer Websites strukturiert untersuchen, typische technische und inhaltliche Schwachstellen erkennen und daraus verständliche Handlungsempfehlungen ableiten.

Der Fokus liegt nicht auf einer klassischen Webagentur-Lösung, sondern auf einer modularen Analyse-Engine, die später für interne Auswertungen, Reports, Akquise-Unterstützung oder eigene SaaS-Ideen erweitert werden kann.

---

## Aktueller Funktionsumfang

Aktuell umgesetzt:

- Start eines Website-Crawls über ein internes Dashboard
- HTTP-Abruf einer eingegebenen URL
- Normalisierung einfacher URLs
- Speicherung von Website, CrawlRun, Page, Headings, Links, Images und CrawlErrors
- Extraktion von:
  - HTTP-Statuscode
  - HTML
  - Title
  - Meta Description
  - H1-H3-Überschriften
  - Links
  - Bildern und Alt-Texten
- API-Endpunkte zum Starten von Crawls, Abrufen letzter Crawl-Läufe und Anzeigen von Crawl-Ergebnissen
- Eigene Analyzer-Klasse für Page-Level-Issues (`PageIssueAnalyzer`)
- Erste Analyse-Regeln für typische Seitenprobleme
- Zusammenfassung der Ergebnisse im Dashboard
- Filterung der Ergebnisse nach Severity (`error`, `warning`, `info`)
- Backend-Tests für Analyzer-Regeln und Ergebnis-Mapping
- Dokumentation von Architekturentscheidungen über ADRs

Noch nicht umgesetzt bzw. geplant:

- Mehrseitiger Crawl
- Crawl-Limits und Queue-basierte Verarbeitung
- robots.txt- und Sitemap-Unterstützung
- Login / Benutzerverwaltung für das Dashboard
- Report-Export
- Scoring-Modell
- Playwright-Unterstützung für JavaScript-lastige Websites
- produktionsreife Deployment-Konfiguration

---

## Tech Stack

### Backend

- Laravel
- PHP 8.3+
- PostgreSQL
- REST API
- Symfony DomCrawler
- PHPUnit

### Frontend

- Next.js
- React
- TypeScript
- Tailwind CSS

### Infrastruktur

- Docker Compose
- PostgreSQL 16
- Nginx
- Node 22

---

## Architekturüberblick

Das Projekt ist bewusst in Backend, Frontend, Datenbank und Infrastruktur getrennt.

```text
acd-analyzer/
├── backend/              # Laravel API, Crawler, Analyzer, Datenmodell
├── frontend/             # Next.js Dashboard
├── docker/               # Docker-Konfiguration für PHP/Nginx
├── docs/                 # Architektur- und Analyzer-Dokumentation
├── docker-compose.yml    # Lokale Entwicklungsumgebung
└── ROADMAP.md            # Langfristige Produkt- und Architekturplanung
```

### Backend-Struktur

Wichtige Bereiche im Laravel-Backend:

```text
backend/app/
├── Http/
│   ├── Controllers/Api/       # API Controller
│   ├── Requests/              # Validierung eingehender Requests
│   └── Resources/             # API Response Resources
├── Models/                    # Eloquent Models
└── Services/
    ├── Analyzer/              # Analyse-Regeln
    ├── Crawler/               # Download, Parsing, Persistierung
    └── CrawlResultsService.php
```

Der Controller enthält bewusst möglichst wenig Geschäftslogik. Crawling, Parsing, Persistierung und Analyse sind in eigene Services aufgeteilt.

---

## Analyse-Regeln

Die aktuelle Analyse ist bewusst einfach gehalten und dient als erste Grundlage für spätere Erweiterungen.

Aktuell erkannte Issues:

- fehlender Title
- zu kurzer oder zu langer Title
- fehlende Meta Description
- zu kurze oder zu lange Meta Description
- fehlende H1
- mehrere H1-Überschriften
- Bilder ohne Alt-Text
- hoher Anteil fehlender Alt-Texte
- sehr wenige interne Links
- ungewöhnlich große HTML-Größe
- Crawl-Fehler

Die Issue-Codes sind in `docs/analyzer/issue-codes.md` dokumentiert.

---

## API-Endpunkte

### Crawl starten

```http
POST /api/crawl
```

Beispiel-Body:

```json
{
  "url": "https://example.com"
}
```

### Letzte Crawl-Läufe abrufen

```http
GET /api/crawl-runs
```

Dieser Endpunkt liefert die letzten Crawl-Läufe mit Website, Status, Seitenanzahl und Zeitstempeln.

### Crawl-Ergebnisse abrufen

```http
GET /api/crawl-runs/{crawlRun}/results
```

Dieser Endpunkt liefert eine zusammengeführte Ergebnisstruktur mit Summary, Seiteninformationen und Issues.

---

## Lokale Entwicklung

### Voraussetzungen

- Docker und Docker Compose
- Git
- Optional für lokale Entwicklung ohne Container:
  - PHP 8.3+
  - Composer
  - Node.js 22+
  - npm

---

## Setup mit Docker Compose

Repository klonen:

```bash
git clone <repository-url>
cd acd-analyzer
```

Benutzer-ID für Docker setzen:

```bash
export UID=$(id -u)
export GID=$(id -g)
```

Container starten:

```bash
docker compose up -d
```

Backend-Abhängigkeiten installieren:

```bash
docker compose exec app composer install
```

Backend-Environment erstellen:

```bash
cp backend/.env.example backend/.env
```

In `backend/.env` die Datenbankverbindung für Docker anpassen:

```env
DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=acd_analyzer
DB_USERNAME=acd_user
DB_PASSWORD=acd_password
```

Laravel-App-Key generieren:

```bash
docker compose exec app php artisan key:generate
```

Migrationen ausführen:

```bash
docker compose exec app php artisan migrate
```

Das Frontend wird über den `frontend`-Service gestartet. Falls nötig, kann das Frontend auch manuell im Container installiert werden:

```bash
docker compose exec frontend npm install
```

---

## Lokale URLs

Nach dem Start der Container:

- Frontend: `http://localhost:3000`
- Backend API über Nginx: `http://localhost:8000/api`
- PostgreSQL: `localhost:5432`

Das Frontend verwendet standardmäßig:

```env
NEXT_PUBLIC_API_URL=http://localhost:8000/api
```

Falls das Backend lokal über `php artisan serve` läuft, kann stattdessen z. B. gesetzt werden:

```env
NEXT_PUBLIC_API_URL=http://127.0.0.1:8080/api
```

---

## Entwicklung ohne Backend-Container

Während der Entwicklung kann PostgreSQL weiterhin in Docker laufen, während Laravel lokal gestartet wird.

In diesem Fall muss `backend/.env` auf den lokalen Port zeigen:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=acd_analyzer
DB_USERNAME=acd_user
DB_PASSWORD=acd_password
```

Laravel lokal starten:

```bash
cd backend
php artisan serve --host=127.0.0.1 --port=8080
```

Frontend-Environment anpassen:

```env
NEXT_PUBLIC_API_URL=http://127.0.0.1:8080/api
```

Frontend lokal starten:

```bash
cd frontend
npm install
npm run dev
```

---

## Tests

Backend-Tests ausführen:

```bash
cd backend
php artisan test
```

Oder im Docker-Container:

```bash
docker compose exec app php artisan test
```

Aktuell gibt es unter anderem Tests für den `PageIssueAnalyzer` und für das Mapping im `CrawlResultsService`.

---

## Dokumentation

Wichtige Projektdokumente:

- `ROADMAP.md` – langfristige Produkt- und Entwicklungsplanung
- `docs/handbook/engineering-handbook.md` – technische Arbeitsweise und Architekturprinzipien
- `docs/adr/` – Architecture Decision Records
- `docs/adr/0006-page-issue-analyzer.md` – Entscheidung zur Trennung der Analyzer-Regeln vom Ergebnis-Mapping
- `docs/analyzer/issue-codes.md` – Dokumentation der Analyzer-Regeln

Die Dokumentation ist Teil des Projekts und soll bei größeren technischen Entscheidungen mitgepflegt werden.

---

## Roadmap

Kurzfristige nächste Schritte:

- Analyzer-Regeln weiter strukturieren
- Ergebnisdarstellung im Dashboard verbessern
- Mehrseitigen Crawl vorbereiten
- Fehlerfälle sauberer abbilden
- Crawler stärker modularisieren
- Erste Score- oder Priorisierungslogik entwerfen

Langfristige mögliche Erweiterungen:

- Playwright für JavaScript-lastige Websites
- Report-Export
- historische Crawl-Vergleiche
- technische Qualitäts-Scores
- Handlungsempfehlungen für Website-Betreiber
- SaaS- oder internes Akquise-Dashboard

---

## Hinweis

Dieses Projekt befindet sich bewusst in einem frühen Entwicklungsstand. Der Schwerpunkt liegt aktuell auf sauberer Architektur, nachvollziehbaren Entwicklungsschritten und einer erweiterbaren technischen Grundlage.

