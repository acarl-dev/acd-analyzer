"use client";

import { useEffect, useState } from "react";
import { getCrawlResults } from "@/api/crawl";
import type { CrawlResultsResponse, CrawlRun } from "@/types/crawl";
import { CrawlTechnologySummary } from "./CrawlTechnologySummary";
import { CrawlPageResultCard } from "./CrawlPageResultCard";
import { CrawlResultOverview } from "./CrawlResultOverview";
import { CrawlIssueSummary } from "./CrawlIssueSummary";


type SeverityFilter = "all" | "error" | "warning" | "info";

type IssueSummaryItem = {
  key: string;
  message: string;
  severity: "info" | "warning" | "error";
  count: number;
};

interface CrawlResultProps {
  crawlRun: CrawlRun;
}

export function CrawlResult({ crawlRun }: CrawlResultProps) {
  const [results, setResults] = useState<CrawlResultsResponse | null>(null);
  const [isLoadingResults, setIsLoadingResults] = useState(false);
  const [resultsError, setResultsError] = useState<string | null>(null);
  const [severityFilter, setSeverityFilter] = useState<SeverityFilter>("all");
  const [expandedPageKeys, setExpandedPageKeys] = useState<Set<string>>(new Set());

  function togglePageDetails(pageKey: string) {
    setExpandedPageKeys((currentKeys) => {
      const nextKeys = new Set(currentKeys);

      if (nextKeys.has(pageKey)) {
        nextKeys.delete(pageKey);
      } else {
        nextKeys.add(pageKey);
      }

      return nextKeys;
    });
  }

  const sortedPages =
    results?.pages.toSorted((a, b) => {
      if (a.hasCrawlError !== b.hasCrawlError) {
        return a.hasCrawlError ? -1 : 1;
      }

      const severityRank = {
        error: 3,
        warning: 2,
        info: 1,
      };

      const highestSeverityA = Math.max(
        0,
        ...a.issues.map((issue) => severityRank[issue.severity]),
      );

      const highestSeverityB = Math.max(
        0,
        ...b.issues.map((issue) => severityRank[issue.severity]),
      );

      if (highestSeverityA !== highestSeverityB) {
        return highestSeverityB - highestSeverityA;
      }

      if (a.issues.length !== b.issues.length) {
        return b.issues.length - a.issues.length;
      }

      return a.depth - b.depth;
    }) ?? [];

  const filteredPages = sortedPages.filter((page) => {
    if (severityFilter === "all") {
      return true;
    }

    return page.issues.some((issue) => issue.severity === severityFilter);
  });

  const issueSummaryItems =
    results?.pages
      .flatMap((page) => page.issues)
      .reduce<Record<string, IssueSummaryItem>>((items, issue) => {
        const key = `${issue.severity}:${issue.message}`;
        const existingItem = items[key];

        return {
          ...items,
          [key]: {
            key,
            message: issue.message,
            severity: issue.severity,
            count: (existingItem?.count ?? 0) + 1,
          },
        };
      }, {}) ?? {};

  const topIssueSummaryItems = Object.values(issueSummaryItems)
    .sort((a, b) => b.count - a.count)
    .slice(0, 5);

  const technologies = results?.technologies ?? [];

  useEffect(() => {
    let isMounted = true;

    async function loadResults() {
      setIsLoadingResults(true);
      setResultsError(null);

      try {
        const crawlResults = await getCrawlResults(crawlRun.id);

        if (isMounted) {
          setResults(crawlResults);
          setSeverityFilter("all");
        }
      } catch (error) {
        if (isMounted) {
          setResultsError(
            error instanceof Error
              ? error.message
              : "Die Analyseergebnisse konnten nicht geladen werden.",
          );
        }
      } finally {
        if (isMounted) {
          setIsLoadingResults(false);
        }
      }
    }

    void loadResults();

    return () => {
      isMounted = false;
    };
  }, [crawlRun.id]);

  return (
    <div className="space-y-4">
      <div className="rounded-xl border border-emerald-900 bg-emerald-950/50 p-4">
        <h3 className="mb-3 font-semibold text-emerald-200">
          Crawl-Lauf
        </h3>

        <dl className="grid gap-3 text-sm sm:grid-cols-2">
          <div>
            <dt className="text-slate-400">Crawl ID</dt>
            <dd className="font-medium">{crawlRun.id}</dd>
          </div>

          <div>
            <dt className="text-slate-400">Status</dt>
            <dd className="font-medium">{crawlRun.status}</dd>
          </div>

          <div>
            <dt className="text-slate-400">Website ID</dt>
            <dd className="font-medium">{crawlRun.website_id}</dd>
          </div>

          <div>
            <dt className="text-slate-400">Seiten gecrawlt</dt>
            <dd className="font-medium">{crawlRun.pages_crawled}</dd>
          </div>
        </dl>
      </div>

      <div className="rounded-xl border border-slate-800 bg-slate-950/70 p-4">
        <h3 className="mb-3 font-semibold text-slate-100">
          Analyseergebnisse
        </h3>

        {isLoadingResults && (
          <p className="text-sm text-slate-400">
            Analyseergebnisse werden geladen …
          </p>
        )}

        {resultsError && (
          <p className="text-sm text-red-300">
            {resultsError}
          </p>
        )}

        {results && (
          <div className="space-y-4">
            <CrawlResultOverview results={results} />

            <div className="grid gap-4 lg:grid-cols-2">
              <CrawlTechnologySummary technologies={technologies} />
              <CrawlIssueSummary items={topIssueSummaryItems} />
            </div>
                        <div className="flex flex-wrap gap-2 border-t border-slate-800 pt-4">
              {[
                { value: "all", label: "Alle" },
                { value: "error", label: "Fehler" },
                { value: "warning", label: "Warnungen" },
                { value: "info", label: "Hinweise" },
              ].map((filter) => (
                <button
                  key={filter.value}
                  type="button"
                  onClick={() => setSeverityFilter(filter.value as SeverityFilter)}
                  className={
                    severityFilter === filter.value
                      ? "rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-950"
                      : "rounded-full border border-slate-700 px-3 py-1 text-xs font-medium text-slate-300 transition hover:border-slate-500 hover:text-slate-100"
                  }
                >
                  {filter.label}
                </button>
              ))}
            </div>

            <div className="space-y-3">
              {filteredPages.length === 0 && (
                <p className="rounded-lg border border-slate-800 bg-slate-900/60 p-3 text-sm text-slate-400">
                  Für diesen Filter wurden keine Seiten gefunden.
                </p>
              )}

              {filteredPages.map((page) => {
                const pageKey = String(page.id ?? page.url);

                return (
                  <CrawlPageResultCard
                    key={pageKey}
                    page={page}
                    severityFilter={severityFilter}
                    isExpanded={expandedPageKeys.has(pageKey)}
                    onToggleDetails={() => togglePageDetails(pageKey)}
                  />
                );
              })}
            </div>
          </div>
        )}
      </div>
    </div>
  );
}