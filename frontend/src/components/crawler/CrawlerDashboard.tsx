"use client";

import { useState } from "react";
import { CrawlForm } from "@/components/crawler/CrawlForm";
import { CrawlResult } from "@/components/crawler/CrawlResult";
import { CrawlRunList } from "@/components/crawler/CrawlRunList";
import type { CrawlRun, CrawlRunListItem } from "@/types/crawl";

function mapListItemToCrawlRun(crawlRun: CrawlRunListItem): CrawlRun {
  return {
    id: crawlRun.id,
    website_id: crawlRun.websiteId,
    status: crawlRun.status,
    pages_crawled: crawlRun.pagesCrawled,
    error_message: null,
    started_at: crawlRun.startedAt,
    finished_at: crawlRun.finishedAt,
    created_at: crawlRun.createdAt,
    updated_at: crawlRun.createdAt,
  };
}

export function CrawlerDashboard() {
  const [selectedCrawlRun, setSelectedCrawlRun] = useState<CrawlRun | null>(null);
  const [refreshKey, setRefreshKey] = useState(0);

  function handleCrawlCreated(crawlRun: CrawlRun) {
    setSelectedCrawlRun(crawlRun);
    setRefreshKey((current) => current + 1);
  }

  function handleSelectCrawlRun(crawlRun: CrawlRunListItem) {
    setSelectedCrawlRun((current) => {
        if (current?.id === crawlRun.id) {
        return null;
        }

        return mapListItemToCrawlRun(crawlRun);
    });
  }

  return (
    <div className="space-y-6">
      <CrawlForm onCrawlCreated={handleCrawlCreated} />

      <div className="grid gap-6 xl:grid-cols-[420px_1fr] xl:items-start">
        <CrawlRunList
          onSelect={handleSelectCrawlRun}
          selectedCrawlRunId={selectedCrawlRun?.id ?? null}
          refreshKey={refreshKey}
        />

        <section className="min-w-0">
          {selectedCrawlRun ? (
            <CrawlResult crawlRun={selectedCrawlRun} />
          ) : (
            <div className="rounded-2xl border border-slate-800 bg-slate-900 p-6 shadow-xl">
              <h2 className="text-xl font-semibold">Analyseergebnis</h2>
              <p className="mt-2 text-sm text-slate-400">
                Starte einen neuen Crawl oder wähle links einen gespeicherten Crawl aus.
              </p>
            </div>
          )}
        </section>
      </div>
    </div>
  );
}