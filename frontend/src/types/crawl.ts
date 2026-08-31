export interface CrawlRun {
  id: number;
  website_id: number;
  status: string;
  pages_crawled: number;
  error_message: string | null;
  started_at: string | null;
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
  depth: number;
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

export interface DetectedTechnology {
  id: number;
  type: string;
  name: string;
  confidence: number;
  evidence: string;
  pageId: number | null;
}

export interface CrawlSummary {
  totalPages: number;
  successfulPages: number;
  failedPages: number;
  pagesWithIssues: number;
  totalIssues: number;
  errors: number;
  warnings: number;
  infos: number;
}

export interface CrawlResultsResponse {
  crawlRunId: number;
  websiteId: number;
  siteUrl: string;
  healthScore: number;
  summary: CrawlSummary;
  technologies: DetectedTechnology[];
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
  healthScore: number;
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

// New M1.5.1 types

export interface CrawlRunOverview {
  crawlRunId: number;
  websiteId: number;
  siteUrl: string;
  status: string;
  startedAt: string | null;
  finishedAt: string | null;
  healthScore: number;
  metrics: {
    crawledPages: number;
    httpPages: number;
    renderedPages: number;
    issues: number;
    errorIssues: number;
    warningIssues: number;
    infoIssues: number;
    internalLinks: number;
    externalLinks: number;
    redirectedPages: number;
    canonicalIssues: number;
    robotsTxtExists: boolean;
    sitemaps: number;
    sitemapUrls: number;
    technologies: number;
    crawlErrors: number;
  };
}

export interface PageListItem {
  id: number;
  url: string;
  statusCode: number;
  title: string | null;
  h1: string | null;
  canonicalHref: string | null;
  canonicalUrl: string | null;
  redirectCount: number;
  depth: number;
  issueCount: number;  fetchMethod: string;
  rendererReason: string | null;}

export interface PageDetail {
  id: number;
  url: string;
  requestedUrl: string | null;
  finalUrl: string | null;
  statusCode: number;
  depth: number;
  
  // SEO
  title: string | null;
  titleLength: number | null;
  metaDescription: string | null;
  metaDescriptionLength: number | null;
  h1: string | null;
  h1Count: number;
  
  // Canonical
  canonicalHref: string | null;
  canonicalUrl: string | null;
  canonicalCount: number;
  
  // Redirects
  redirectCount: number;
  redirectChain: any[] | null;
  
  // Headings
  headings: Array<{ level: number; text: string }>;
  
  // Links
  internalLinksCount: number;
  externalLinksCount: number;
  
  // Images
  imageCount: number;
  imagesWithoutAlt: number;
  images: Array<{ src: string; alt: string | null }>;
  
  // Technical
  htmlSizeBytes: number | null;
  responseTimeMs: number | null;
  
  // Issues
  issues: PageAnalysisIssue[];
  
  createdAt: string | null;
}

export interface LinkItem {
  id: number;
  href: string;
  normalizedUrl: string;
  text: string | null;
  isInternal: boolean;
  statusCode: number | null;
  sourcePageUrl: string;
  sourcePageId: number;
}

export interface RedirectItem {
  id: number;
  requestedUrl: string;
  finalUrl: string;
  url: string;
  redirectCount: number;
  redirectChain: any[];
  statusCode: number;
}

export interface RobotsTxtData {
  url: string;
  statusCode: number;
  exists: boolean;
  content: string | null;
  sitemaps: string[];
  rules: any[];
  fetchedAt: string;
}

export interface SitemapItem {
  id: number;
  url: string;
  type: string;
  statusCode: number;
  exists: boolean;
  urlCount: number;
  parentSitemapId: number | null;
  childSitemapCount: number;
  fetchedAt: string;
  error: string | null;
  children?: SitemapItem[];
}

export interface SitemapUrl {
  id: number;
  url: string;
  normalizedUrl: string;
  lastmod: string | null;
  changefreq: string | null;
  priority: number | null;
}

export interface CanonicalItem {
  id: number;
  url: string;
  canonicalHref: string | null;
  canonicalUrl: string | null;
  canonicalCount: number;
  status: 'self' | 'other_url' | 'missing' | 'invalid' | 'multiple' | 'empty';
}

export interface IssueItem {
  id: number;
  code: string;
  severity: IssueSeverity;
  message: string;
  pageId: number | null;
  pageUrl: string | null;
  crawlErrorId: number | null;
}

export interface IssueGroupItem {
  code: string;
  severity: IssueSeverity;
  message: string;
  count: number;
}
