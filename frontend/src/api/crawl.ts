import { apiFetch } from "./client";
import type {
  CrawlResponse,
  CrawlResultsResponse,
  CrawlRunListResponse,
  CrawlRunOverview,
  PageListItem,
  PageDetail,
  LinkItem,
  RedirectItem,
  RobotsTxtData,
  SitemapItem,
  SitemapUrl,
  CanonicalItem,
  IssueItem,
  IssueGroupItem,
} from "@/types/crawl";

export async function startCrawl(
  url: string,
  maxPages: number,
  maxDepth: number,
): Promise<CrawlResponse> {
  return apiFetch<CrawlResponse>("/crawl", {
    method: "POST",
    body: JSON.stringify({
      url,
      maxPages,
      maxDepth,
    }),
  });
}

export async function listCrawlRuns(): Promise<CrawlRunListResponse> {
  return apiFetch<CrawlRunListResponse>("/crawl-runs");
}

export async function getCrawlResults(
  crawlRunId: number,
): Promise<CrawlResultsResponse> {
  return apiFetch<CrawlResultsResponse>(`/crawl-runs/${crawlRunId}/results`);
}

// New M1.5.1 API functions

export async function getCrawlRunOverview(crawlRunId: number): Promise<{ data: CrawlRunOverview }> {
  return apiFetch<{ data: CrawlRunOverview }>(`/crawl-runs/${crawlRunId}/overview`);
}

export async function getCrawlRunPages(crawlRunId: number): Promise<{ data: PageListItem[] }> {
  return apiFetch<{ data: PageListItem[] }>(`/crawl-runs/${crawlRunId}/pages`);
}

export async function getCrawlRunPage(crawlRunId: number, pageId: number): Promise<{ data: PageDetail }> {
  return apiFetch<{ data: PageDetail }>(`/crawl-runs/${crawlRunId}/pages/${pageId}`);
}

export async function getCrawlRunLinks(crawlRunId: number): Promise<{ summary: { total: number; internal: number; external: number }; data: LinkItem[] }> {
  return apiFetch<{ summary: { total: number; internal: number; external: number }; data: LinkItem[] }>(`/crawl-runs/${crawlRunId}/links`);
}

export async function getCrawlRunRedirects(crawlRunId: number): Promise<{ summary: { total: number; maxHops: number }; data: RedirectItem[] }> {
  return apiFetch<{ summary: { total: number; maxHops: number }; data: RedirectItem[] }>(`/crawl-runs/${crawlRunId}/redirects`);
}

export async function getCrawlRunRobotsTxt(crawlRunId: number): Promise<{ data: RobotsTxtData | null }> {
  return apiFetch<{ data: RobotsTxtData | null }>(`/crawl-runs/${crawlRunId}/robots-txt`);
}

export async function getCrawlRunSitemaps(crawlRunId: number): Promise<{ summary: { total: number; totalUrls: number }; data: SitemapItem[] }> {
  return apiFetch<{ summary: { total: number; totalUrls: number }; data: SitemapItem[] }>(`/crawl-runs/${crawlRunId}/sitemaps`);
}

export async function getCrawlRunSitemap(
  crawlRunId: number, 
  sitemapId: number,
  page: number = 1,
  perPage: number = 50
): Promise<{ 
  sitemap: { id: number; url: string; type: string; statusCode: number }; 
  pagination: { currentPage: number; perPage: number; total: number; lastPage: number }; 
  data: SitemapUrl[] 
}> {
  return apiFetch<{ 
    sitemap: { id: number; url: string; type: string; statusCode: number }; 
    pagination: { currentPage: number; perPage: number; total: number; lastPage: number }; 
    data: SitemapUrl[] 
  }>(`/crawl-runs/${crawlRunId}/sitemaps/${sitemapId}?page=${page}&perPage=${perPage}`);
}

export async function getCrawlRunCanonicals(crawlRunId: number): Promise<{ 
  summary: { 
    total: number; 
    self: number; 
    otherUrl: number; 
    missing: number; 
    invalid: number; 
    multiple: number; 
    empty: number 
  }; 
  data: CanonicalItem[] 
}> {
  return apiFetch<{ 
    summary: { 
      total: number; 
      self: number; 
      otherUrl: number; 
      missing: number; 
      invalid: number; 
      multiple: number; 
      empty: number 
    }; 
    data: CanonicalItem[] 
  }>(`/crawl-runs/${crawlRunId}/canonicals`);
}

export async function getCrawlRunIssues(
  crawlRunId: number,
  filters?: { severity?: string; code?: string; pageId?: number }
): Promise<{ 
  summary: { total: number; errors: number; warnings: number; infos: number; uniqueCodes: number }; 
  groupedByCode: IssueGroupItem[];
  data: IssueItem[] 
}> {
  const params = new URLSearchParams();
  if (filters?.severity) params.set('severity', filters.severity);
  if (filters?.code) params.set('code', filters.code);
  if (filters?.pageId) params.set('pageId', filters.pageId.toString());

  const queryString = params.toString();
  const url = queryString 
    ? `/crawl-runs/${crawlRunId}/issues?${queryString}`
    : `/crawl-runs/${crawlRunId}/issues`;

  return apiFetch<{ 
    summary: { total: number; errors: number; warnings: number; infos: number; uniqueCodes: number }; 
    groupedByCode: IssueGroupItem[];
    data: IssueItem[] 
  }>(url);
}
