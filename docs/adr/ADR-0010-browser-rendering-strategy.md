# ADR-0009: Browser Rendering Strategy

## Status

Accepted

## Context

ACD Analyzer currently crawls pages through regular HTTP requests and analyzes the initial HTML response.

This works for classic server-rendered websites, but it is insufficient for modern JavaScript-heavy websites, single-page applications, and app-shell based pages where relevant content, links, headings, and images are only available after JavaScript execution.

The project already detects likely JavaScript-heavy pages heuristically as a rendering technology signal. However, detection alone does not make those pages analyzable.

## Decision

Browser-based rendering will be introduced as a separate internal renderer service.

Laravel remains the main application and orchestration layer. It is responsible for crawl orchestration, persistence, analysis, scoring, and API responses.

The renderer service is responsible for loading a single URL in a real browser through Playwright/Chromium, executing JavaScript, and returning the rendered HTML DOM to Laravel.

The renderer service is implemented as a separate Node.js service and runs in Docker Compose as `renderer`.

Laravel communicates with the renderer through an internal HTTP API:

```text
POST http://renderer:3001/render
´´´

The initial response contract is:
{
  "url": "https://example.com",
  "status": 200,
  "html": "<!DOCTYPE html>..."
}

Playwright and the Docker image version must be pinned together because Playwright browser binaries are coupled to the installed Playwright package version.

Consequences

This keeps browser rendering isolated from the Laravel application and avoids installing browser dependencies into the PHP container.

It gives the analyzer a future path to process rendered DOM using the existing parser and analysis pipeline.

The initial implementation is intentionally limited to a single-URL proof of concept. It does not yet replace the existing HTTP crawler, does not perform multi-page browser crawling, and does not automatically re-analyze JavaScript-heavy pages.

Future work can add:

a Laravel-side rendered page downloader or rendering strategy selector
automatic use of rendering for JS-heavy pages
timeout and error taxonomy
browser reuse or worker pooling
rate limiting and queue integration
optional screenshots or network diagnostics
persistence of rendering metadata
Validation

The proof of concept validates that:

the renderer service runs as a separate Docker Compose service
Laravel can reach the renderer through Docker networking
Playwright/Chromium can render a URL
JavaScript execution changes the DOM before HTML is returned
Laravel can receive rendered HTML through BrowserRendererClient
BrowserRendererClient is covered by unit tests using Http::fake()