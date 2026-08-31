# ADR-0014: Sitemap Support (M1.4)

## Status

Accepted

## Context

The ACD Analyzer needs to discover and parse XML sitemaps to understand which URLs a website considers important. This data is essential for:

- Identifying URLs declared by the website
- Comparing declared URLs with actually crawled URLs (gap analysis)
- Detecting missing sitemaps
- Understanding lastmod, changefreq, priority metadata
- Preparing data for later issue detection (e.g., broken sitemap links, outdated lastmod)

For V1, the analyzer focuses on **collecting and storing sitemap data**, not interpreting it. Issue detection (e.g., "sitemap contains 404 pages") belongs in later milestones with `WebsiteIssueAnalyzer`.

## Decision

### Data Model: Two-Table Architecture

Created two related tables:

#### `sitemaps` Table
Stores sitemap **documents** (XML files):
- `website_id` and `crawl_run_id` for association
- `url`: Full URL to sitemap (e.g., https://example.com/sitemap.xml)
- `status_code`: HTTP status (200, 404, 500, null for connection errors)
- `type`: Sitemap type (`urlset`, `index`, `unknown`)
- `exists`: Boolean indicating successful fetch (true only for 200)
- `content_type`: MIME type from HTTP response
- `error`: Error message (invalid XML, unknown format, HTTP error)
- `parent_sitemap_id`: Self-referencing FK for nested sitemap indexes
- `fetched_at`: Timestamp

#### `sitemap_urls` Table
Stores individual **URL entries** from sitemaps:
- `sitemap_id`: FK to sitemaps table
- `url`: Original URL from `<loc>`
- `normalized_url`: Normalized URL via `UrlNormalizer`
- `lastmod`: Last modification timestamp
- `changefreq`: Change frequency hint
- `priority`: Priority value (0.0-1.0)

This separation enables:
- Storing recursive sitemap indexes
- Tracking which sitemap a URL came from
- Detecting duplicate URLs across sitemaps
- Cascade deletion when a crawl run is deleted

### Sitemap Discovery Strategy

Discovery follows this order:
1. **Primary**: Use sitemap URLs from `RobotsTxt->sitemaps` (M1.3)
2. **Fallback**: If no robots.txt or no sitemaps listed, try:
   - HEAD request to `/sitemap.xml`
   - HEAD request to `/sitemap_index.xml`

### SitemapParser

XML parser handling:
- `<urlset>` (regular sitemap)
- `<sitemapindex>` (sitemap index)
- XML namespaces (`xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"`)
- Sitemaps **without** namespaces
- Optional fields (`lastmod`, `changefreq`, `priority`)
- Invalid XML
- Unknown root elements

Returns:
```php
[
    'type' => 'urlset|index|unknown',
    'urls' => [['loc' => '...', 'lastmod' => '...', 'changefreq' => '...', 'priority' => 0.8], ...],
    'sitemaps' => [['loc' => '...', 'lastmod' => '...'], ...],
    'error' => null|string,
]
```

Skips entries without `<loc>` element.

### SitemapFetcher

Recursive sitemap fetching with **strict limits**:
- `MAX_SITEMAPS = 50`: Max sitemap documents to fetch
- `MAX_SITEMAP_DEPTH = 3`: Max recursion depth for nested indexes
- `MAX_SITEMAP_URLS = 10000`: Max total URL entries

Features:
- Normalizes sitemap URLs via `UrlNormalizer`
- Reuses `PageDownloader` for HTTP requests (consistent redirect handling)
- Recursively follows sitemap indexes
- Tracks counters: `getFetchedSitemapCount()`, `getTotalUrlCount()`
- Normalizes URL entries via `UrlNormalizer`
- Stores **both** original URL and normalized URL

Returns recursive tree structure:
```php
[
    'url' => 'https://example.com/sitemap_index.xml',
    'status_code' => 200,
    'type' => 'index',
    'exists' => true,
    'content_type' => 'application/xml',
    'error' => null,
    'urls' => [],
    'child_sitemaps' => [
        [
            'url' => 'https://example.com/sitemap1.xml',
            'type' => 'urlset',
            'urls' => [['url' => '...', 'normalized_url' => '...', ...], ...],
            'child_sitemaps' => [],
        ],
    ],
]
```

### SitemapService

Orchestration service:
- Discovers sitemap URLs (robots.txt first, then fallback)
- Fetches sitemaps via `SitemapFetcher`
- Recursively stores sitemap documents and URL entries
- Sets `parent_sitemap_id` for nested indexes
- Parses `lastmod` timestamps
- Returns array of `Sitemap` models

### Integration Point

For V1, `SitemapService` is **not yet integrated** into the main `CrawlerService` workflow. Integration will happen when:
- Sitemap URLs should be added to the crawl queue
- Or when a dedicated crawler orchestration command is created

This keeps M1.4 isolated and testable.

### URL Normalization

Both sitemap URLs and sitemap URL entries are normalized:
- Sitemap URLs: `UrlNormalizer->normalizeStartUrl()` (same as robots.txt)
- Sitemap URL entries: `UrlNormalizer->normalizeInternalLink()` (same as crawled links)

**Both original and normalized URLs are stored** to enable later comparison:
- "Does this sitemap contain URLs with fragments?"
- "Does this sitemap URL match the crawled URL?"

### Test Coverage

Comprehensive tests:
- **17 unit tests** for `SitemapParser`:
  - Empty content
  - Simple urlset with/without namespace
  - Sitemap index with/without namespace
  - Invalid XML
  - Unknown root element
  - Missing `<loc>` elements
  - Missing optional fields
  - Priority as float
  - Real-world WordPress sitemap
  - Real-world sitemap index with multiple children

- **14 unit tests** for `SitemapFetcher`:
  - Simple urlset
  - 404/500/timeout handling
  - URL normalization (sitemap + entries)
  - Recursive sitemap index
  - MAX_DEPTH limit
  - Sitemap counter
  - URL counter
  - Invalid XML
  - HTML instead of XML
  - Counter reset

- **10 feature tests** for `SitemapPersistenceTest`:
  - Store sitemap from robots.txt
  - Store sitemap URLs with all fields
  - Store nested sitemap index
  - Store 404 sitemap
  - Fallback when no robots.txt
  - URL normalization
  - Cascade deletion with crawl run
  - Multiple sitemaps from robots.txt

Total: **41 new tests**, all passing. Full test suite: **190 tests, 556 assertions**.

## Consequences

### Benefits

- Sitemap data is now captured for every crawl run
- Both regular sitemaps and sitemap indexes are supported
- Nested sitemap indexes are handled recursively
- Limits prevent runaway fetching
- URL normalization enables accurate comparison with crawled URLs
- `parent_sitemap_id` tracks sitemap hierarchy
- `lastmod`, `changefreq`, `priority` are preserved (not evaluated yet)
- XML namespace handling works with real-world sitemaps

### No Automatic Crawling Yet

M1.4 **does not**:
- Add sitemap URLs to the crawl queue
- Compare sitemap URLs with crawled URLs
- Validate sitemap XML schema
- Flag broken links in sitemaps
- Interpret `lastmod`, `changefreq`, `priority`
- Follow sitemap directives

These are future enhancements for later milestones.

### Handoff to Later Milestones

The stored sitemap data enables:
- **Gap analysis**: Compare `sitemap_urls.normalized_url` with `pages.normalized_url`
- **Issue detection**: 404 pages in sitemap, missing sitemap, outdated lastmod
- **Crawl prioritization**: Use `priority` and `changefreq` to guide crawl order

Example query for gap analysis:
```php
$missingInSitemap = Page::whereNotIn('normalized_url', 
    SitemapUrl::select('normalized_url')
)->get();
```

### Next Steps

- **M1.5**: Extract and validate canonical URLs
- **M1.6**: Renderer integration and error handling
- **M1.7**: Real-world crawl test (all M1 features together)
- **Later**: Add sitemap URLs to crawl queue
- **Later**: Issue detection via `WebsiteIssueAnalyzer`
  - No sitemap found
  - Sitemap contains 404 pages
  - Sitemap returns 500
  - Important pages not in sitemap
  - Sitemap lastmod older than actual page
  - Sitemap exceeds 50MB or 50,000 URLs
  - Invalid XML namespace
