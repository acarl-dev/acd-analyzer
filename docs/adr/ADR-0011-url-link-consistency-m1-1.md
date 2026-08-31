# ADR-0011: URL and Link Consistency (M1.1)

## Status

Accepted

## Context

The ACD Analyzer previously had two separate link classification mechanisms:

1. `UrlNormalizer` normalized URLs for the crawl queue
2. `HtmlParser` implemented its own simplified `is_internal` logic

This duplication created inconsistency risks, particularly when comparing URLs for sitemap validation, canonical checks, or broken link detection. Additionally, the URL normalization was not RFC-compliant and didn't handle edge cases like `../`, `./`, protocol-relative URLs, custom ports, or case normalization correctly.

## Decision

### Single Source of Truth

`UrlNormalizer` is now the central authority for all URL operations:
- RFC 3986-compliant relative URL resolution
- Lowercase host normalization
- Fragment removal (always)
- Query parameter preservation (always)
- Protocol-relative URL handling
- Custom port handling (default ports removed)
- www/non-www treated as equivalent for internal classification

### Link Persistence

Links are now stored with:
- `href`: raw extracted value from HTML
- `normalized_url`: absolute normalized URL (nullable if invalid)
- `is_internal`: classification based on normalized URL vs start URL
- `text`: link text

The `HtmlParser` only extracts raw `href` and `text`. All normalization and classification happens centrally in `CrawlResultPersister` using `UrlNormalizer`.

### Queue Deduplication

The crawler now tracks both `visited` and `queued` URLs to prevent:
- Crawling the same URL twice
- Adding the same URL to the queue multiple times

### Test Coverage

All M1.1 requirements are covered by unit and feature tests:
- `/contact` (root-relative)
- `../contact` (parent directory)
- `./contact` (current directory)
- `?foo=bar` (query preservation)
- `#section` (fragment handling)
- `//example.de/contact` (protocol-relative)
- HTTP ↔ HTTPS (scheme handling)
- www ↔ non-www (internal classification)
- external domains
- custom ports
- trailing slashes
- fragments + query combined

## Consequences

### Benefits

- Single, RFC-compliant URL normalization implementation
- Consistent link classification across the application
- Foundation for sitemap/canonical/broken-link analysis in M1.2+
- Queue deduplication prevents redundant crawling
- Raw `href` preserved for debugging/display

### Migration

A new migration adds `normalized_url` to the `links` table with an index for future lookup operations.

### Next Steps

M1.2 will build on this foundation to add:
- Redirect tracking (original URL → final URL)
- Redirect chain detection
- 3xx status code analysis
- robots.txt support
- sitemap.xml support
- canonical extraction and validation
