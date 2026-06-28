# ADR-0007: Page Issue Analyzer

## Status

Accepted

## Context

Sprint 4.2 introduced backend-owned analysis results through `CrawlResultsService`.

That service was responsible for two different concerns:

1. mapping stored crawl data into the dashboard response
2. deciding which issues a page has

For Sprint 4.3 the analyzer rules need to grow. Keeping all rules inside `CrawlResultsService` would make the service harder to read, harder to test and harder to extend.

## Decision

Page-level issue detection is moved into a dedicated analyzer class:

```txt
App\Services\Analyzer\PageIssueAnalyzer
```

`CrawlResultsService` remains responsible for loading crawl data, mapping page results and building the summary.

`PageIssueAnalyzer` is responsible for deciding which normalized issues belong to a page.

The existing issue format remains stable:

```txt
code
severity
message
```

## Consequences

### Positive

* Analyzer rules can grow without bloating `CrawlResultsService`.
* Page issue detection can be unit-tested directly.
* The API contract remains stable for the frontend.
* Future analyzer classes can follow the same pattern.
* Later filters, detail views and reports can rely on normalized issue codes.

### Negative

* There is one additional service class.
* Thresholds such as title length or HTML size are now explicit and may need refinement with real crawl data.
* Severity semantics must stay consistent as more rules are added.

## Current Implementation

`PageIssueAnalyzer` currently detects:

* missing title
* title too short
* title too long
* missing meta description
* meta description too short
* meta description too long
* missing H1
* multiple H1 headings
* images without alt attribute
* high ratio of images without alt attribute
* few internal links
* large HTML size

`CrawlResultsService` injects `PageIssueAnalyzer` and delegates page issue detection to it.

## Follow-up Work

* Refine thresholds based on real crawl results.
* Add severity documentation for all issue codes.
* Prepare frontend filters for error, warning and info issues.
* Consider typed issue DTOs or value objects when the issue model grows.
