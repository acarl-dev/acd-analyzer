# ADR-0005: Docker-Container verwenden die UID/GID des Host-Benutzers

## Status

Akzeptiert

## Kontext

Während der Entwicklung von ACD Analyzer wurden Dateien über Laravel Artisan innerhalb des Docker-Containers erstellt.

Da der Container standardmäßig als `root` lief, gehörten neu erzeugte Dateien anschließend dem Benutzer `root`.

Dadurch traten regelmäßig Probleme auf:

* Dateien konnten in VS Code nicht gespeichert werden.
* Änderungen erforderten `sudo chown`.
* Git arbeitete mit Dateien unterschiedlicher Besitzer.
* Der Entwicklungsablauf wurde unnötig unterbrochen.

## Entscheidung

Die Docker-Container werden mit der UID und GID des Host-Benutzers gestartet.

Hierzu werden die Umgebungsvariablen

* `UID`
* `GID`

über eine `.env`-Datei an Docker Compose übergeben.

Im `docker-compose.yml` wird für die relevanten Services gesetzt:

```yaml
user: "${UID}:${GID}"
```

## Gründe

* Dateien gehören nach ihrer Erstellung automatisch dem lokalen Entwickler.
* Keine manuellen Berechtigungsänderungen mehr.
* VS Code kann Dateien jederzeit bearbeiten.
* Git arbeitet konsistent.
* Entwicklungsumgebung verhält sich vorhersehbar.

## Alternativen

### Container dauerhaft als root betreiben

Einfach einzurichten, erzeugt jedoch dauerhaft Berechtigungsprobleme.

### Nach jeder Änderung `sudo chown` ausführen

Funktioniert kurzfristig, ist jedoch fehleranfällig und unterbricht den Entwicklungsfluss.

### Eigene Benutzer im Docker-Image anlegen

Technisch möglich, für Version 0.1 jedoch unnötig komplex.

## Warum verworfen?

Die Alternativen lösen entweder das eigentliche Problem nicht oder erhöhen die Komplexität ohne nennenswerten Mehrwert.

Die Verwendung der Host-UID/GID ist eine einfache und bewährte Lösung.

## Konsequenzen

### Vorteile

* Keine Berechtigungsprobleme mehr
* Sauberer Git-Workflow
* Bessere Zusammenarbeit zwischen Docker und Host-System
* Weniger Wartungsaufwand

### Nachteile

* Zusätzliche `.env`-Variablen für Docker Compose
* UID und GID müssen einmalig auf dem Entwicklungssystem bekannt sein

## Langfristige Auswirkungen

Diese Entscheidung bildet die Grundlage für eine stabile lokale Entwicklungsumgebung.

Alle zukünftigen Services (Laravel, Next.js, Worker, Playwright usw.) sollen ebenfalls mit der Host-UID/GID betrieben werden, sofern keine technischen Gründe dagegen sprechen.
