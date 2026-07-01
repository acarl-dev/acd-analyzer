# ADR-0009: Synchronous Limited Multi-Page Crawling

## Status

Accepted

## Context

Until Sprint 4.3, the ACD Analyzer crawled and analyzed one submitted URL per CrawlRun. The crawler already persisted pages, headings, images, links, crawl errors and analysis issues. Sprint 4.3 also introduced persistent issue storage through `page_issues`, so result endpoints no longer perform live analysis.

For Sprint 4.4, the next product step is to move from single-page analysis toward a small website crawl. The analyzer should follow internal links from the submitted start URL, persist multiple pages in the same CrawlRun and analyze the complete persisted CrawlRun afterward.

A full crawler architecture with asynchronous workers, scheduling, parallel crawling, sitemap processing, robots.txt handling or JavaScript rendering would add significant complexity. The project currently focuses on a clear portfolio-ready vertical slice and a maintainable product foundation.

## Decision

The ACD Analyzer will implement multi-page crawling synchronously inside `CrawlerService` for now.

The crawler uses explicit limits:

- `MAX_PAGES = 10`
- `MAX_DEPTH = 1`

The crawl starts with the normalized start URL at depth `0`. Internal links found on crawled pages are normalized, filtered and added to an in-memory queue when the current depth is below the configured maximum depth.

The crawler only follows links that belong to the same host as the submitted start URL. External links are stored as page links but are not crawled.

A new `UrlNormalizer` service owns URL normalization and link filtering concerns such as:

- adding a default scheme to submitted start URLs
- converting relative links into absolute URLs
- removing fragments
- ignoring non-crawlable links such as `mailto:`, `tel:`, `javascript:` and anchor-only links
- comparing hosts for internal/external classification

The `pages` table now stores a `depth` value. The start page is stored with depth `0`; directly linked internal pages are stored with depth `1`.

`CrawlAnalysisService` is executed only after the crawl loop has finished and all crawl data has been persisted.

## Consequences

This keeps the implementation simple and easy to reason about while enabling the product to analyze more than a single URL.

The synchronous approach is acceptable because the crawl is intentionally limited by page count and depth. It avoids introducing workers, queues and scheduling before they are necessary.

The current implementation is not designed for large websites, deep crawls or long-running crawl jobs. Those use cases should be handled in a later architecture step, likely with queued jobs or workers.

The crawler now distinguishes between fatal and non-fatal crawl failures. If the start URL fails, the CrawlRun fails. If a linked internal subpage fails, the error is persisted as a crawl error and the CrawlRun may still complete.

Future improvements may include:

- configurable crawl limits
- queued/asynchronous crawl jobs
- sitemap support
- robots.txt handling
- per-host crawl politeness and delays
- JavaScript rendering for JS-heavy pages
- better URL canonicalization
- duplicate prevention before URLs enter the queue