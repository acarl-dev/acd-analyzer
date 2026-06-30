import { apiFetch } from "./client";
import {
  CrawlResponse,
  CrawlResultsResponse,
  CrawlRunListResponse,
} from "@/types/crawl";

export async function startCrawl(url: string): Promise<CrawlResponse> {
  return apiFetch<CrawlResponse>("/crawl", {
    method: "POST",
    body: JSON.stringify({ url }),
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