"use client";

import { useState } from "react";
import { startCrawl } from "@/api/crawl";
import { CrawlResponse } from "@/types/crawl";

export function useCrawler() {
  const [result, setResult] = useState<CrawlResponse | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [isLoading, setIsLoading] = useState(false);

  async function crawl(url: string) {
    setIsLoading(true);
    setError(null);
    setResult(null);

    try {
      const response = await startCrawl(url);
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