export interface CrawlRun {
  id: number;
  website_id: number;
  status: string;
  pages_crawled: number;
  error_message: string | null;
  started_at: string;
  finished_at: string | null;
}

export interface CrawlResponse {
  data: CrawlRun;
}

export type IssueSeverity = "info" | "warning" | "error";

export interface PageAnalysisIssue {
  code: string;
  severity: IssueSeverity;
  message: string;
}

export interface CrawlResultsSummary {
  totalPages: number;
  successfulPages: number;
  failedPages: number;
  pagesWithIssues: number;
  totalIssues: number;
  errors: number;
  warnings: number;
  infos: number;
}

export interface PageAnalysisResult {
  id: number | null;
  url: string;
  httpStatus: number | null;
  crawledAt: string | null;

  title: string | null;
  titleLength: number | null;

  metaDescription: string | null;
  metaDescriptionLength: number | null;

  h1: string | null;
  h1Count: number;

  imageCount: number;
  imagesWithoutAlt: number;

  internalLinksCount: number;
  externalLinksCount: number;

  htmlSizeBytes: number | null;

  hasCrawlError: boolean;
  crawlError: string | null;

  issues: PageAnalysisIssue[];
}

export interface CrawlResultsResponse {
  crawlRunId: number;
  websiteId: number;
  siteUrl: string | null;
  summary: CrawlResultsSummary;
  pages: PageAnalysisResult[];
}

export interface CrawlRunListItem {
  id: number;
  websiteId: number;
  siteUrl: string | null;
  status: string;
  pagesCrawled: number;
  startedAt: string | null;
  finishedAt: string | null;
  createdAt: string | null;
  issueSummary: {
    total: number;
    errors: number;
    warnings: number;
    infos: number;
  };
}

export interface CrawlRunListResponse {
  data: CrawlRunListItem[];
}