# ADR-0016: Renderer Integration (M1.6)

**Status:** Accepted

**Date:** 2026-09-01

---

## Context

The ACD Analyzer currently crawls pages through regular HTTP requests. This works well for traditional server-rendered websites, but is insufficient for modern JavaScript-heavy applications where essential content (links, headings, images) is only available after JavaScript execution.

While the project already includes a renderer service for proof-of-concept use, it was not yet integrated into the main crawling pipeline as a controlled fallback mechanism.

M1.6 integrates the existing Playwright-based renderer service as a **fallback option** when HTTP content is insufficient for meaningful analysis.

---

## Decision

### Pipeline Architecture

The renderer replaces the HTML source only, not the analysis pipeline:

```
HTTP Fetch
   ↓
HTML sufficient?
   ├─ Yes  → continue normally
   └─ No   → Renderer
               ↓
            HTML
               ↓
         existing Parser
```

The normal HTTP-based pipeline remains the primary approach. Rendering is used only when HTTP content is insufficient.

### Data Model Changes

Added two fields to the `pages` table:

- **`fetch_method`**: `'http'` or `'renderer'`
- **`renderer_reason`**: reason for rendering (nullable)

Possible values for `renderer_reason`:

- `'empty_content'` - body is practically empty
- `'insufficient_html'` - very little analyzable content
- `'js_heavy'` - many scripts but little visible content
- `'manual'` - manually triggered (future use)

### Render Decision Logic

Created `RenderDecisionService` to centralize the decision logic. It evaluates HTTP content and decides if rendering is needed based on:

**Criteria for "insufficient HTML":**

- **Empty body:** Very little visible text (< 50 characters)
- **App-root patterns:** SPA markers (`#root`, `#app`, `#__next`) with minimal analyzable content (< 5 links, < 2 headings, < 500 chars)
- **JS-heavy indicators:** Many script tags (≥ 3) with minimal content and few links/headings
- **JavaScript-required messages:** Presence of "please enable JavaScript" or similar messages

**Does NOT automatically render:**

- React/Next.js/Nuxt detection alone is not sufficient
- Many modern frameworks use server-side rendering (SSR) and deliver complete HTML
- Only pages with **both** framework markers **and** insufficient content trigger rendering

### Renderer Fallback Handling

The crawler handles multiple scenarios robustly:

1. **HTTP good → no rendering needed**
2. **HTTP weak → renderer succeeds → use rendered HTML**
3. **HTTP weak → renderer fails → keep HTTP content, log error**
4. **HTTP unusable → renderer fails → CrawlError**

Renderer failures do not break the crawl. HTTP content is retained if it provides any usable data.

### Configuration

Renderer behavior is fully configurable via environment variables:

```env
RENDERER_ENABLED=true
RENDERER_URL=http://renderer:3001
RENDERER_TIMEOUT=20
RENDERER_MAX_PER_CRAWL=10
```

The `max_per_crawl` limit prevents uncontrolled Chromium usage when crawling large sites with many JS-heavy pages.

### Frontend Integration

The `PagesTab` displays:

- **Fetch** column showing `HTTP` or `🌐 Rendered`
- Tooltip showing `renderer_reason` when hovering over rendered pages

The `OverviewTab` shows:

- **HTTP Pages** count
- **Rendered Pages** count

---

## Consequences

### Positive

- **Extends crawlability** to modern JavaScript applications
- **Conservative approach** - only renders when necessary
- **Robust fallback** - renderer failures don't break crawls
- **Configurable limits** - prevents resource exhaustion
- **Observability** - render decisions and reasons are tracked
- **Isolated logic** - `RenderDecisionService` can be tested and refined independently

### Constraints

- Rendering adds latency (typically 2-5 seconds per page)
- Chromium consumes significant memory
- The `max_per_crawl` limit must be respected to avoid resource exhaustion
- Renderer service must be available (Docker Compose service)

### Not Yet Included

M1.6 explicitly does **not** include:

- Screenshots
- axe-core accessibility scanning
- Lighthouse performance audits
- Network request monitoring
- Cookie consent handling
- JavaScript error collection
- DOM comparison (HTTP vs. rendered)
- Customer-facing rendering

These features will use the same renderer service in future milestones.

---

## Validation

### Tests

Created comprehensive test coverage:

**Unit Tests** (`RenderDecisionServiceTest`):

- HTTP fetch failures (4xx, 5xx)
- Empty body content
- Minimal visible text
- App-root patterns with insufficient content
- JS-heavy pages with minimal links/headings
- JavaScript-required messages
- SSR pages with sufficient content (should not render)
- Traditional pages with good content (should not render)

**Feature Tests** (`CrawlerServiceRendererIntegrationTest`):

- HTTP used for pages with sufficient content
- Renderer used for empty body pages
- Renderer used for JS-heavy pages
- Fallback to HTTP when renderer fails
- Renderer disabled via config
- Max rendered pages limit respected
- SSR pages with sufficient content use HTTP

### Migration

- Added migration: `2026_09_01_100000_add_fetch_method_to_pages_table.php`
- Updated `Page` model fillable fields
- Updated `ParsedPage` DTO to include fetch_method and renderer_reason
- Updated `CrawlResultPersister` to persist new fields

---

## Related

- [ADR-0010: Browser Rendering Strategy](ADR-0010-browser-rendering-strategy.md) - Initial renderer service architecture
- [ADR-0004: Crawler Modularization](0004-crawler-modularization.md) - Separation of concerns in crawler pipeline

---

## Future Work

- **M1.7:** Error handling and logging improvements
- **M2.x:** Screenshot capture for visual regression testing
- **M3.x:** Accessibility scanning with axe-core
- **M4.x:** Performance audits with Lighthouse
- **M5.x:** Network monitoring and resource analysis
