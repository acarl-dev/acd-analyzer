"use client";

import { useEffect, useState } from "react";
import { listCrawlRuns } from "@/api/crawl";
import type { CrawlRunListItem } from "@/types/crawl";

interface CrawlRunListProps {
  onSelect: (crawlRun: CrawlRunListItem) => void;
  selectedCrawlRunId?: number | null;
  refreshKey?: number;
}

function formatDate(value: string | null) {
  if (value === null) {
    return "n/a";
  }

  return new Intl.DateTimeFormat("de-DE", {
    dateStyle: "short",
    timeStyle: "short",
  }).format(new Date(value));
}

function getStatusClassName(status: string) {
  if (status === "completed") {
    return "text-emerald-300";
  }

  if (status === "failed") {
    return "text-red-300";
  }

  if (status === "running") {
    return "text-sky-300";
  }

  return "text-slate-300";
}

export function CrawlRunList({
  onSelect,
  selectedCrawlRunId,
  refreshKey = 0,
}: CrawlRunListProps) {
  const [crawlRuns, setCrawlRuns] = useState<CrawlRunListItem[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [isRefreshing, setIsRefreshing] = useState(false);
  const [error, setError] = useState<string | null>(null);

  async function handleRefresh() {
    setIsRefreshing(true);
    setError(null);

    try {
      const response = await listCrawlRuns();

      setCrawlRuns(response.data);
    } catch (error) {
      setError(
        error instanceof Error
          ? error.message
          : "Die Crawl-Übersicht konnte nicht geladen werden.",
      );
    } finally {
      setIsRefreshing(false);
    }
  }

  useEffect(() => {
    let isMounted = true;

    async function loadInitialCrawlRuns() {
      try {
        const response = await listCrawlRuns();

        if (isMounted) {
          setCrawlRuns(response.data);
          setError(null);
        }
      } catch (error) {
        if (isMounted) {
          setError(
            error instanceof Error
              ? error.message
              : "Die Crawl-Übersicht konnte nicht geladen werden.",
          );
        }
      } finally {
        if (isMounted) {
          setIsLoading(false);
        }
      }
    }

    void loadInitialCrawlRuns();

    return () => {
      isMounted = false;
    };
  }, [refreshKey]);

  const isBusy = isLoading || isRefreshing;

  return (
    <section className="rounded-2xl border border-slate-800 bg-slate-900 p-6 shadow-xl">
      <div className="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h2 className="text-xl font-semibold">Letzte Crawls</h2>
          <p className="mt-1 text-sm text-slate-400">
            Gespeicherte Crawl-Läufe aus der Datenbank.
          </p>
        </div>

        <button
          type="button"
          onClick={() => void handleRefresh()}
          disabled={isBusy}
          className="rounded-xl border border-slate-700 px-4 py-2 text-sm font-medium text-slate-200 transition hover:border-slate-500 hover:text-white disabled:cursor-not-allowed disabled:opacity-60"
        >
          {isBusy ? "Lädt..." : "Aktualisieren"}
        </button>
      </div>

      {error && (
        <p className="rounded-xl border border-red-900 bg-red-950/60 p-4 text-sm text-red-200">
          {error}
        </p>
      )}

      {!error && isLoading && crawlRuns.length === 0 && (
        <p className="text-sm text-slate-400">
          Crawl-Übersicht wird geladen …
        </p>
      )}

      {!error && !isLoading && crawlRuns.length === 0 && (
        <p className="rounded-xl border border-slate-800 bg-slate-950/70 p-4 text-sm text-slate-400">
          Es wurden noch keine Crawls gespeichert.
        </p>
      )}

      {crawlRuns.length > 0 && (
        <div className="max-h-[560px] overflow-y-auto rounded-xl border border-slate-800">
          <div className="grid grid-cols-1 divide-y divide-slate-800">
            {crawlRuns.map((crawlRun) => {
              const isSelected = selectedCrawlRunId === crawlRun.id;

              return (
                <button
                  key={crawlRun.id}
                  type="button"
                  onClick={() => onSelect(crawlRun)}
                  className={
                    isSelected
                      ? "grid gap-2 bg-slate-800/80 p-4 text-left transition hover:bg-slate-800 sm:grid-cols-[1fr_auto]"
                      : "grid gap-2 bg-slate-950/60 p-4 text-left transition hover:bg-slate-800/70 sm:grid-cols-[1fr_auto]"
                  }
                >
                  <div>
                    <p className="break-all text-sm font-medium text-slate-100">
                      {crawlRun.siteUrl ?? "Unbekannte Website"}
                    </p>

                    <p className="mt-1 text-xs text-slate-500">
                      Crawl ID {crawlRun.id} · Website ID {crawlRun.websiteId}
                    </p>
                  </div>

                  <div className="flex flex-wrap gap-x-4 gap-y-1 text-xs sm:justify-end">
                    <span className={getStatusClassName(crawlRun.status)}>
                      {crawlRun.status}
                    </span>

                    <span className="text-slate-400">
                      Seiten: {crawlRun.pagesCrawled}
                    </span>

                    <span className="text-slate-400">
                      {formatDate(crawlRun.createdAt)}
                    </span>
                  </div>
                </button>
              );
            })}
          </div>
        </div>
      )}
    </section>
  );
}