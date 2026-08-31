# ADR-0013: robots.txt Support (M1.3)

## Status

Accepted

## Context

The ACD Analyzer needs to understand website crawling directives defined in robots.txt files. This is essential for:

- Identifying which URLs are allowed or disallowed for crawling
- Discovering sitemap references
- Understanding crawler permissions per user-agent
- Providing data for later issue detection (e.g., important pages blocked, missing robots.txt)

For V1, the analyzer focuses on **collecting facts**, not interpreting them. Issue detection (e.g., "important page is blocked") belongs in later milestones with `WebsiteIssueAnalyzer`.

## Decision

### Data Model

Created `robots_txt` table with:
- `website_id` and `crawl_run_id` for association
- `url`: Full URL to robots.txt (e.g., https://example.com/robots.txt)
- `status_code`: HTTP status (200, 404, 500, null for connection errors)
- `exists`: Boolean indicating successful fetch (true only for 200)
- `content`: Raw robots.txt content for manual review
- `sitemaps`: JSON array of sitemap URLs found
- `rules`: JSON array of parsed user-agent rules
- `fetched_at`: Timestamp

### Three Distinct States

The implementation distinguishes:
1. **404**: robots.txt does not exist (not an error)
2. **200**: robots.txt exists (may be empty)
3. **5xx/timeout**: Technical error fetching robots.txt

### RobotsTxtParser

Custom parser handling:
- Multiple user-agent blocks
- `Allow` and `Disallow` directives
- `Sitemap` references
- Comments and empty lines
- Case-insensitive directive names
- CRLF, CR, and LF line endings
- Malformed lines (skipped gracefully)

Directives like `Crawl-delay` are captured in raw content but not parsed for V1.

### RobotsTxtFetcher

Fetching logic:
- Normalizes base URL via `UrlNormalizer->normalizeStartUrl()`
- Appends `/robots.txt` to base URL
- 10-second timeout
- Normalizes sitemap URLs via `UrlNormalizer->normalizeStartUrl()`
- Handles connection exceptions gracefully

### RobotsTxtService

Orchestration service:
- Fetches robots.txt for a website and crawl run
- Parses content
- Persists to database
- Returns `RobotsTxt` model

### Integration Point

For V1, `RobotsTxtService` is **not yet integrated** into the main `CrawlerService` workflow. Integration will happen when:
- Robots.txt rules should be respected during crawling
- Or when a dedicated crawler orchestration command is created

This keeps M1.3 isolated and testable.

### Rule Format

Rules are stored as JSON:
```json
[
  {
    "user_agent": "*",
    "allow": ["/assets"],
    "disallow": ["/admin", "/private"]
  },
  {
    "user_agent": "Googlebot",
    "allow": [],
    "disallow": ["/secret"]
  }
]
```

### Sitemap Discovery

Sitemaps found in robots.txt are:
- Extracted by `RobotsTxtParser`
- Normalized by `UrlNormalizer`
- Stored as JSON array

M1.4 will consume these sitemap URLs.

### Test Coverage

Comprehensive tests:
- **18 unit tests** for `RobotsTxtParser`:
  - Empty content
  - Single/multiple user-agents
  - Allow/Disallow directives
  - Sitemap extraction
  - Comments, empty lines, malformed lines
  - CRLF/CR/LF line endings
  - Case-insensitive directives
  - Whitespace trimming
  - Real-world example
  
- **11 unit tests** for `RobotsTxtFetcher`:
  - 200/404/500 status codes
  - URL normalization
  - Sitemap extraction and normalization
  - Connection exceptions
  - Empty robots.txt
  - Raw content preservation

- **8 feature tests** for `RobotsTxtPersistenceTest`:
  - 200/404/500 persistence
  - Multiple sitemaps
  - Multiple user-agent rules
  - Raw content storage
  - Cascade deletion with crawl run
  - Empty robots.txt

Total: **37 new tests**, all passing. Full test suite: **151 tests, 435 assertions**.

## Consequences

### Benefits

- robots.txt data is now captured for every crawl run
- Sitemap references are discoverable (foundation for M1.4)
- Raw content is preserved for manual review
- Three states (missing, present, error) are clearly distinguished
- Parser handles real-world robots.txt complexity

### No Automatic Enforcement Yet

M1.3 **does not**:
- Respect robots.txt during crawling
- Flag blocked important pages
- Validate robots.txt syntax
- Interpret Crawl-delay
- Follow disallow rules

These are future enhancements for later milestones.

### Sitemap Handoff to M1.4

The `sitemaps` array provides a clean handoff:
```php
$robotsTxt = RobotsTxt::where('crawl_run_id', $crawlRun->id)->first();
$sitemaps = $robotsTxt->sitemaps; // Ready for M1.4
```

### Next Steps

- **M1.4**: Load and parse sitemap.xml
- **M1.5**: Extract and validate canonical URLs
- **M1.6**: Renderer integration and error handling
- **Later**: Respect robots.txt rules during crawling
- **Later**: Issue detection via `WebsiteIssueAnalyzer`
  - Important pages blocked by robots.txt
  - No sitemap reference in robots.txt
  - Robots.txt returns 500
  - Malformed robots.txt
