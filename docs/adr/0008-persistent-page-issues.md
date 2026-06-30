# ADR-0008: Persistent Page Issues

## Status

Accepted

## Context

The analyzer is the core domain component of ACD Analyzer.

Until now, page issues are calculated dynamically when crawl results are requested through the dashboard. This was acceptable for the first vertical slice, but it has important drawbacks:

1. Old crawl results may change when analyzer rules or thresholds change.
2. Dashboard-wide summaries require repeated analysis of stored crawl data.
3. Issue trends across websites and crawl runs are harder to query.
4. Reports cannot rely on a stable historical analysis result.
5. The analyzer result is not treated as a first-class domain concept.

A crawl result should represent a historical finding. If a page was analyzed with a certain rule set, that analysis result should remain available even if future analyzer rules change.

## Decision

Analyzer results will be persisted as page issues.

A new `page_issues` table will store normalized issues produced by analyzer services.

The table will store:

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

A dedicated analysis service will be responsible for running analyzers for a crawl run and storing the resulting issues.

The crawler remains responsible for fetching pages and storing raw crawl data.

## Target Architecture

```txt
CrawlerService
→ crawls websites
→ stores crawl runs, pages, headings, images, links and crawl errors

PageIssueAnalyzer
→ analyzes normalized page data
→ returns normalized issues

CrawlAnalysisService
→ loads stored crawl data
→ runs analyzers
→ persists page issues

CrawlResultsService
→ loads stored crawl data and stored issues
→ builds dashboard response

DashboardSummaryService
→ reads stored issues
→ builds global dashboard overview