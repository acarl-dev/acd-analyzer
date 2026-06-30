# ADR-0008: Persistent Page Issues

## Status

Accepted

## Context

The analyzer is the core domain component of ACD Analyzer.

Previously, page issues were calculated dynamically when crawl results were requested through the dashboard. This was acceptable for the first vertical slice, but it had important drawbacks:

1. Old crawl results could change when analyzer rules or thresholds changed.
2. Dashboard-wide summaries required repeated analysis of stored crawl data.
3. Issue trends across websites and crawl runs were harder to query.
4. Reports could not rely on stable historical analysis results.
5. Analyzer results were not treated as first-class domain data.

A crawl result should represent a historical finding. If a page was analyzed with a certain rule set, that analysis result should remain available even if future analyzer rules change.

## Decision

Analyzer results are persisted as page issues.

The `page_issues` table stores normalized issues produced by analyzer services.

The table stores:

- the related crawl run
- the related page, if available
- the related crawl error, if available
- the affected URL
- issue code
- severity
- message
- optional context data
- analyzer version

The analyzer remains responsible for deciding which issues exist.

`CrawlAnalysisService` is responsible for running analyzers for a crawl run and storing the resulting issues.

`CrawlerService` is responsible for fetching pages, storing crawl data and triggering the analysis step after crawl data has been persisted.

`CrawlResultsService` must not run live analysis. It reads stored pages, crawl errors and persisted issues only.

A crawl run is considered `completed` only after crawl data has been persisted and analysis has successfully stored its issues.

## Target Architecture

```txt
CrawlController
→ starts a crawl through CrawlerService

CrawlerService
→ creates CrawlRun
→ downloads page
→ parses page
→ persists crawl data
→ runs CrawlAnalysisService
→ marks CrawlRun as completed

PageIssueAnalyzer
→ analyzes normalized page data
→ returns normalized issues

CrawlAnalysisService
→ loads stored crawl data
→ deletes existing issues for the crawl run
→ runs analyzers
→ persists page issues and crawl error issues

CrawlResultsService
→ loads stored crawl data and stored issues
→ builds crawl result response for the dashboard

DashboardSummaryService
→ reads stored issues
→ builds global dashboard overview
Consequences
Positive
Crawl results become historically stable.
Dashboard summaries can query stored issues directly.
Issue counts across websites and crawl runs become easier to calculate.
Reports can be generated from persisted analyzer results.
Analyzer results become a first-class domain concept.
Future analyzer versions can be tracked.
The results endpoint stays deterministic and does not perform hidden analysis work.
Negative
Additional database table and model are required.
Analyzer execution becomes a separate application step.
Re-running analysis needs a clear strategy to avoid duplicate issues.
For now, a failed analysis marks the crawl run as failed as well.
Later, separate crawl and analysis status fields may be needed.
Implementation Notes

The first implementation persists page-level issues after crawl data has been stored.

Existing page-level analyzer output keeps the current format:

code
severity
message

The persistence layer adds:

crawl_run_id
page_id
crawl_error_id
url
context
analyzer_version

Crawl errors are also mapped into the issue model so that global summaries can count them together with page issues.

To avoid duplicate issues, CrawlAnalysisService deletes existing issues for a crawl run before storing newly generated issues.

Issues returned by CrawlResultsService are sorted by severity:

error
warning
info
Current Implementation

Implemented in Sprint 4.3:

PageIssueAnalyzer
PageIssue model and page_issues table
relationships for CrawlRun, Page and CrawlError
CrawlAnalysisService
automatic analysis execution inside CrawlerService
persisted issue loading in CrawlResultsService
dashboard summary based on stored issues
severity-based issue sorting
tests for analyzer output, persisted result mapping and issue sorting
Follow-up Work
Add explicit analyzer versioning strategy.
Consider separate analysis_status if crawl and analysis failure handling should be separated.
Improve user-facing crawl error messages.
Add re-analysis action for existing crawl runs.
Consider separate analyzer result tables if the issue model grows significantly.