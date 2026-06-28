"use client";

import { useEffect, useState } from "react";
import { getCrawlResults } from "@/api/crawl";
import type { CrawlResultsResponse, CrawlRun } from "@/types/crawl";

interface CrawlResultProps {
  crawlRun: CrawlRun;
}

export function CrawlResult({ crawlRun }: CrawlResultProps) {
  const [results, setResults] = useState<CrawlResultsResponse | null>(null);
  const [isLoadingResults, setIsLoadingResults] = useState(false);
  const [resultsError, setResultsError] = useState<string | null>(null);

  useEffect(() => {
    let isMounted = true;

    async function loadResults() {
      setIsLoadingResults(true);
      setResultsError(null);

      try {
        const crawlResults = await getCrawlResults(crawlRun.id);

        if (isMounted) {
          setResults(crawlResults);
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
    <div className="mt-6 space-y-4">
      <div className="rounded-xl border border-emerald-900 bg-emerald-950/50 p-4">
        <h3 className="mb-3 font-semibold text-emerald-200">
          Crawl erfolgreich gestartet
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
            <dl className="grid gap-3 text-sm sm:grid-cols-4">
              <div>
                <dt className="text-slate-400">Seiten gesamt</dt>
                <dd className="font-medium">{results.summary.totalPages}</dd>
              </div>

              <div>
                <dt className="text-slate-400">Seiten mit Issues</dt>
                <dd className="font-medium">
                  {results.summary.pagesWithIssues}
                </dd>
              </div>

              <div>
                <dt className="text-slate-400">Errors</dt>
                <dd className="font-medium">{results.summary.errors}</dd>
              </div>

              <div>
                <dt className="text-slate-400">Warnings</dt>
                <dd className="font-medium">{results.summary.warnings}</dd>
              </div>
            </dl>

            <div className="space-y-3">
              {results.pages.map((page) => (
                <div
                  key={page.id ?? page.url}
                  className="rounded-lg border border-slate-800 bg-slate-900/60 p-3"
                >
                  <div className="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                    <p className="break-all text-sm font-medium text-slate-100">
                      {page.url}
                    </p>
                    <span className="text-xs text-slate-400">
                      HTTP {page.httpStatus ?? "n/a"}
                    </span>
                  </div>

                  <dl className="mt-3 grid gap-2 text-xs sm:grid-cols-3">
                    <div>
                      <dt className="text-slate-500">Title</dt>
                      <dd className="text-slate-200">
                        {page.title ?? "Fehlt"}
                      </dd>
                    </div>

                    <div>
                      <dt className="text-slate-500">H1</dt>
                      <dd className="text-slate-200">
                        {page.h1 ?? "Fehlt"}
                      </dd>
                    </div>

                    <div>
                      <dt className="text-slate-500">Meta Description</dt>
                      <dd className="text-slate-200">
                        {page.metaDescription ?? "Fehlt"}
                      </dd>
                    </div>
                  </dl>

                  {page.issues.length > 0 && (
                    <ul className="mt-3 space-y-1 text-xs">
                      {page.issues.map((issue) => (
                        <li
                          key={`${page.id ?? page.url}-${issue.code}`}
                          className="rounded border border-amber-900/60 bg-amber-950/40 px-2 py-1 text-amber-100"
                        >
                          {issue.message}
                        </li>
                      ))}
                    </ul>
                  )}
                </div>
              ))}
            </div>
          </div>
        )}
      </div>
    </div>
  );
}