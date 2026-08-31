# ADR-0012: Redirect Tracking

**Status**: Accepted  
**Date**: 2026-08-31  
**Context**: M1.2 – Redirect Handling  

## Context and Problem Statement

When crawling websites, HTTP redirects (301, 302, 307, 308) are common. We need to:
- Distinguish between the requested URL and the final URL after redirects
- Track the complete redirect chain for analysis
- Count redirect hops
- Prevent crawling the same final URL multiple times (via redirect AND direct link)
- Handle relative Location headers correctly
- Preserve URL normalization as single source of truth

Without redirect tracking, we lose important information about site structure and can't detect issues like redirect loops or unnecessary redirect chains.

## Decision Drivers

- **Fact Collection over Evaluation**: M1.2 focuses on collecting redirect data, not evaluating it
- **Schema Preservation**: Redirect Location headers specify explicit schemes that must be respected
- **Deduplication**: A URL reached via redirect should not be crawled again if linked directly
- **RFC Compliance**: Location header resolution must follow RFC 3986 for relative URLs
- **Single Source of Truth**: `UrlNormalizer` remains authoritative for all URL operations

## Considered Options

### Option 1: Store only final URL
- ❌ Loses information about redirect chains
- ❌ Can't analyze redirect patterns

### Option 2: Let Laravel Http follow redirects automatically
- ❌ Loses control over redirect chain tracking
- ❌ Can't track individual hops

### Option 3: Manual redirect following with tracking (CHOSEN)
- ✅ Full control over redirect chain
- ✅ Can track each hop's status code and location
- ✅ Enables redirect loop detection
- ✅ Allows deduplication of final URLs

## Decision

Implement manual redirect following in `PageDownloader` with:

1. **Database Schema** (`pages` table):
   - `requested_url` TEXT nullable - the URL originally requested
   - `final_url` TEXT nullable - the URL after all redirects
   - `redirect_count` INTEGER default 0 - number of redirect hops
   - `redirect_chain` JSON nullable - full chain with status codes and locations

2. **New DTO Properties** (`DownloadedPage`):
   - Changed from single `url` to `requestedUrl` and `finalUrl`
   - Added `redirectCount` and `redirectChain`

3. **Redirect Resolution** (`UrlNormalizer::normalizeRedirectLocation()`):
   - New method separate from `normalizeLink()`
   - Resolves relative Location headers using RFC 3986
   - **Preserves explicit schemes** (unlike `normalizeLink()` which converts internal links to base URL scheme)
   - Still normalizes host, removes fragments, removes trailing slashes

4. **Manual Redirect Following** (`PageDownloader`):
   - Uses `Http::withoutRedirecting()` to prevent automatic redirects
   - Loops up to 10 hops, tracking each:
     - `from_url`, `status_code`, `location`, `to_url`
   - Handles redirect status codes: 301, 302, 303, 307, 308
   - Handles edge cases: missing Location header, invalid Location, redirect loops

5. **Queue Deduplication** (`CrawlerService`):
   - Marks both `requestedUrl` AND `finalUrl` as visited
   - Prevents crawling final URL twice if it's also linked directly

6. **Data Flow**:
   ```
   PageDownloader (tracks redirects)
     → DownloadedPage (requestedUrl, finalUrl, chain)
     → HtmlParser (passes through redirect info)
     → ParsedPage (includes redirect data)
     → CrawlResultPersister (saves to DB)
   ```

## Consequences

### Positive

- ✅ Complete redirect information available for analysis
- ✅ Can detect redirect chains, loops, and unnecessary hops
- ✅ Efficient crawling: final URLs not crawled twice
- ✅ Respects explicit schemes in redirect targets (HTTP→HTTPS upgrades preserved)
- ✅ Handles relative Location headers correctly
- ✅ Maintains URL normalization as single source of truth
- ✅ All 114 tests passing

### Negative

- Manual redirect following adds complexity to `PageDownloader`
- Separate `normalizeRedirectLocation()` method creates slight duplication
- Migration required for existing crawl data

### Neutral

- Redirect chain stored as JSON (PostgreSQL handles this efficiently)
- Max 10 redirects prevents infinite loops but could theoretically cut off legitimate chains

## Validation

**Test Coverage**:
- Unit tests for `PageDownloader`: 11 tests covering 301/302/307/308, multiple hops, relative locations, external domains, loops, max redirects
- Feature tests for `RedirectHandlingTest`: 5 tests covering persistence, deduplication, external redirects
- All 114 tests passing

**Schema Changes**:
- Migration `2026_08_31_110000_add_redirect_tracking_to_pages_table.php` applied
- Backfills existing pages with `requested_url = url`, `final_url = url`

**Known Limitations**:
- Meta refresh redirects not tracked (only HTTP redirects)
- JavaScript redirects not tracked
- These can be addressed in M1.6 (Renderer Integration)

## Links

- Milestone: M1.2 – Redirect Handling
- Related: ADR-0011 (URL Normalization)
- Next: M1.3 – robots.txt Handling
- Migration: `backend/database/migrations/2026_08_31_110000_add_redirect_tracking_to_pages_table.php`
- Implementation: `backend/app/Services/Crawler/Download/PageDownloader.php`, `backend/app/Services/Crawler/Url/UrlNormalizer.php`
