"use client";

import { useEffect, useState } from "react";
import { listCrawlRuns } from "@/api/crawl";
import type { CrawlRunListItem } from "@/types/crawl";
import { getHealthScoreLabel } from "@/lib/healthScore";

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

function getHealthScoreClassName(score: number): string {
  if (score >= 80) {
    return "border-emerald-900/60 bg-emerald-950/40 text-emerald-200";
  }

  if (score >= 60) {
    return "border-amber-900/60 bg-amber-950/40 text-amber-200";
  }

  if (score >= 40) {
    return "border-orange-900/60 bg-orange-950/40 text-orange-200";
  }

  return "border-red-900/60 bg-red-950/40 text-red-200";
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
                <div
                  key={crawlRun.id}
                  className={
                    isSelected
                      ? "space-y-3 bg-slate-800/80 p-4"
                      : "space-y-3 bg-slate-950/60 p-4 transition hover:bg-slate-800/50"
                  }
                >
                  <div className="flex items-start justify-between gap-3">
                    <div className="min-w-0">
                      {crawlRun.siteUrl ? (
                        <a
                          href={crawlRun.siteUrl}
                          target="_blank"
                          rel="noreferrer"
                          className="block truncate text-sm font-semibold text-slate-100 underline decoration-slate-600 underline-offset-4 transition hover:text-sky-200 hover:decoration-sky-400"
                          title={crawlRun.siteUrl}
                        >
                          {crawlRun.siteUrl}
                        </a>
                      ) : (
                        <p className="truncate text-sm font-semibold text-slate-100">
                          Unbekannte Website
                        </p>
                      )}

                      <p className="mt-1 text-xs text-slate-500">
                        Crawl ID {crawlRun.id} · Website ID {crawlRun.websiteId} ·{" "}
                        {formatDate(crawlRun.createdAt)}
                      </p>
                    </div>

                    <div
                      className={`shrink-0 rounded-xl border px-3 py-2 text-right ${getHealthScoreClassName(
                        crawlRun.healthScore,
                      )}`}
                    >
                      <p className="text-lg font-semibold leading-none">
                        {crawlRun.healthScore}
                      </p>
                      <p className="mt-1 text-[11px] font-medium leading-none">
                        {getHealthScoreLabel(crawlRun.healthScore)}
                      </p>
                    </div>
                  </div>

                  <div className="grid gap-2 text-xs sm:grid-cols-2">
                    <div>
                      <p className="text-slate-500">Status</p>
                      <p className={`mt-1 font-medium ${getStatusClassName(crawlRun.status)}`}>
                        {crawlRun.status}
                      </p>
                    </div>

                    <div>
                      <p className="text-slate-500">Umfang</p>
                      <p className="mt-1 font-medium text-slate-200">
                        {crawlRun.pagesCrawled} Seite{crawlRun.pagesCrawled === 1 ? "" : "n"}
                      </p>
                    </div>
                  </div>

                  <div className="rounded-lg border border-slate-700/70 bg-slate-950/70 p-3 text-xs">
                    <div className="flex items-center justify-between gap-3">
                      <p className="font-medium text-slate-300">
                        {crawlRun.issueSummary.total} Probleme
                      </p>

                      {crawlRun.issueSummary.total === 0 && (
                        <span className="rounded-full bg-emerald-950/40 px-2 py-0.5 text-emerald-200">
                          Keine Probleme
                        </span>
                      )}
                    </div>

                    {crawlRun.issueSummary.total > 0 && (
                      <p className="mt-2 text-slate-500">
                        Fehler: {crawlRun.issueSummary.errors} · Warnungen:{" "}
                        {crawlRun.issueSummary.warnings} · Hinweise: {crawlRun.issueSummary.infos}
                      </p>
                    )}
                  </div>

                  <button
                    type="button"
                    onClick={() => onSelect(crawlRun)}
                    className={
                      isSelected
                        ? "w-full rounded-lg bg-slate-100 px-3 py-2 text-xs font-semibold text-slate-950 transition hover:bg-white"
                        : "w-full rounded-lg border border-slate-700 px-3 py-2 text-xs font-medium text-slate-200 transition hover:border-slate-500 hover:text-white"
                    }
                  >
                    {isSelected ? "Ausblenden" : "Analyse anzeigen"}
                  </button>
                </div>
              );
            })}
          </div>
        </div>
      )}
    </section>
  );
}