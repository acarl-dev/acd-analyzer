# ADR-0012: Redirect Handling (M1.2)

## Status

Accepted

## Context

The ACD Analyzer previously followed HTTP redirects transparently through Laravel's HTTP client, but did not capture redirect information. This meant:

- No distinction between requested URL and final URL
- No visibility into redirect chains
- No ability to detect redirect loops
- No data to identify redirect-related issues (chains, incorrect internal links pointing to redirects, etc.)

For professional website analysis, redirect behavior is crucial:
- Long redirect chains hurt performance
- Internal links should point directly to final URLs
- HTTP → HTTPS redirects are expected and normal
- Redirect loops indicate configuration problems
- Redirect-to-404 scenarios should be detected

## Decision

### Redirect Tracking in PageDownloader

`PageDownloader` now tracks redirect behavior by:
- Disabling Laravel's automatic redirect following
- Manually following redirects up to a configurable limit (default: 10)
- Recording each redirect hop with status code and location
- Resolving relative Location headers using `UrlNormalizer`
- Preserving explicit schemes in Location headers (no forced scheme conversion)
- Stopping at external redirects or non-3xx responses

### Data Model Changes

New migration `2026_08_31_120000_add_redirect_tracking_to_pages_table.php` adds:
- `requested_url`: Original URL requested by crawler
- `final_url`: Final URL after following redirects
- `redirect_count`: Number of redirect hops (0 = no redirect)
- `redirect_chain`: JSON array of redirect hops

The existing `url` column continues to represent the canonical page URL (= `final_url`).

### DownloadedPage DTO

Updated from single `url` property to:
- `requestedUrl`: What was requested
- `finalUrl`: What was ultimately fetched
- `statusCode`: Final response code
- `html`: Final response body
- `responseTimeMs`: Total time including redirects
- `redirectCount`: Number of hops
- `redirectChain`: Array of redirect hop details

Each hop contains:
```php
[
    'from_url' => 'http://example.com',
    'status_code' => 301,
    'location' => 'https://example.com',
    'to_url' => 'https://example.com',
]
```

### URL Normalization for Redirects

Critical distinction:
- **Crawl links**: Internal links match base URL scheme (M1.1 behavior)
- **Redirect Location headers**: Preserve explicit scheme as-is

This prevents redirect loops where:
1. Server redirects `http://example.com` → `https://example.com`
2. Crawler must respect the `https://` in the Location header
3. Not convert it back to `http://` based on the original request

### Queue Deduplication

Enhanced from M1.1:
- `visited` tracks final URLs to prevent re-crawling redirect targets
- `queued` tracks requested URLs to prevent duplicate queue entries
- When following a link that redirects to an already-visited page, skip it

Example:
- Page A links to `/old-page`
- Page A also links to `/contact`
- `/old-page` redirects to `/contact`
- Result: Only `/old-page` is crawled (which fetches `/contact`), direct `/contact` link is skipped

### Renderer Integration

`PageContentFetcher` now:
- Returns `DownloadedPage` with redirect info from `PageDownloader`
- Renderer path uses requested URL as both `requestedUrl` and `finalUrl` (no redirect tracking in renderer yet)

### Test Coverage

Comprehensive unit and feature tests:
- No redirects (requested = final)
- Single redirect (301, 302, 307, 308)
- Multiple redirect hops
- Relative Location headers
- External redirects (not followed)
- Max redirect limit
- Invalid/missing Location headers
- Queue deduplication with redirects

## Consequences

### Benefits

- Full visibility into redirect behavior
- Foundation for redirect-related issue detection in later milestones
- No performance impact (same number of requests, just tracked differently)
- Raw redirect data preserved for analysis
- Crawler correctly handles redirect-based deduplication

### No Automatic Issue Detection Yet

M1.2 focuses on **collecting facts**, not interpreting them:
- A redirect chain is captured, but not flagged as problematic yet
- HTTP → HTTPS redirects are tracked, but not evaluated
- Internal links to redirect URLs are persisted, but not reported as issues

These interpretations will be added in later milestones when `WebsiteIssueAnalyzer` is introduced.

### Known Limitations

- Renderer does not yet track redirects (would require Playwright network monitoring)
- Only HTTP redirects are tracked (not JS-based or meta refresh redirects)
- Redirect-based deduplication uses `final_url`, which may miss some edge cases

### Next Steps

M1.3+ will build on this foundation:
- M1.3: robots.txt support
- M1.4: sitemap.xml support
- M1.5: Canonical extraction and validation
- Later: `WebsiteIssueAnalyzer` to detect redirect-related issues
  - Long redirect chains
  - Redirect loops
  - Internal links pointing to redirects
  - Redirect to 404
  - Mixed HTTP/HTTPS redirect patterns
