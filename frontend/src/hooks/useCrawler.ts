"use client";

import { useState } from "react";
import { startCrawl } from "@/api/crawl";
import { CrawlResponse } from "@/types/crawl";

interface StartCrawlRequest {
  url: string;
  maxPages: number;
  maxDepth: number;
}

export function useCrawler() {
  const [result, setResult] = useState<CrawlResponse | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [isLoading, setIsLoading] = useState(false);

  async function crawl(payload: StartCrawlRequest) {
    setIsLoading(true);
    setError(null);
    setResult(null);

    try {
      const response = await startCrawl(payload.url, payload.maxPages, payload.maxDepth);
      setResult(response);
      return response;
    } catch (exception) {
      const message =
        exception instanceof Error
          ? exception.message
          : "Ein unbekannter Fehler ist aufgetreten.";

      setError(message);
      return null;
    } finally {
      setIsLoading(false);
    }
  }

  function reset() {
    setResult(null);
    setError(null);
    setIsLoading(false);
  }

  return {
    result,
    error,
    isLoading,
    crawl,
    reset,
  };
}