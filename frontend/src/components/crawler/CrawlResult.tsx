"use client";

import { useEffect, useState } from "react";
import { getCrawlResults } from "@/api/crawl";
import type { CrawlResultsResponse, CrawlRun } from "@/types/crawl";

type SeverityFilter = "all" | "error" | "warning" | "info";

interface CrawlResultProps {
  crawlRun: CrawlRun;
}

function getIssueClassName(severity: "info" | "warning" | "error") {
  if (severity === "error") {
    return "rounded border border-red-900/60 bg-red-950/40 px-2 py-1 text-red-100";
  }

  if (severity === "warning") {
    return "rounded border border-amber-900/60 bg-amber-950/40 px-2 py-1 text-amber-100";
  }

  return "rounded border border-sky-900/60 bg-sky-950/40 px-2 py-1 text-sky-100";
}

function formatBytes(bytes: number | null) {
  if (bytes === null) {
    return "n/a";
  }

  if (bytes < 1024) {
    return `${bytes} B`;
  }

  return `${(bytes / 1024).toFixed(1)} KB`;
}

export function CrawlResult({ crawlRun }: CrawlResultProps) {
  const [results, setResults] = useState<CrawlResultsResponse | null>(null);
  const [isLoadingResults, setIsLoadingResults] = useState(false);
  const [resultsError, setResultsError] = useState<string | null>(null);
  const [severityFilter, setSeverityFilter] = useState<SeverityFilter>("all");

  const filteredPages =
    results?.pages.filter((page) => {
      if (severityFilter === "all") {
        return true;
      }

      return page.issues.some((issue) => issue.severity === severityFilter);
    }) ?? [];

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
            <dl className="grid gap-3 text-sm sm:grid-cols-4">
              <div>
                <dt className="text-slate-400">Seiten gesamt</dt>
                <dd className="font-medium">{results.summary.totalPages}</dd>
              </div>

              <div>
                <dt className="text-slate-400">Fehlgeschlagen</dt>
                <dd className="font-medium text-red-300">
                  {results.summary.failedPages}
                </dd>
              </div>

              <div>
                <dt className="text-slate-400">Seiten mit Problemen</dt>
                <dd className="font-medium">
                  {results.summary.pagesWithIssues}
                </dd>
              </div>

              <div>
                <dt className="text-slate-400">Probleme gesamt</dt>
                <dd className="font-medium">
                  {results.summary.totalIssues}
                </dd>
                <dd className="mt-1 text-xs leading-relaxed text-slate-500">
                  Fehler: {results.summary.errors} · Warnungen:{" "}
                  {results.summary.warnings} · Hinweise: {results.summary.infos}
                </dd>
              </div>
            </dl>

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

              {filteredPages.map((page) => (
                <div
                  key={page.id ?? page.url}
                  className="rounded-xl border border-slate-800 bg-slate-900/70 p-4"
                >
                  <div className="flex flex-col gap-2 border-b border-slate-800 pb-4 sm:flex-row sm:items-start sm:justify-between">
                    <div className="min-w-0">
                      <p className="break-all text-sm font-semibold text-slate-100">
                        {page.url}
                      </p>

                      <p className="mt-1 text-xs text-slate-500">
                        {page.hasCrawlError
                          ? "Dieser Crawl konnte für die Seite nicht abgeschlossen werden."
                          : "Erkannte Seitendaten und Analysehinweise."}
                      </p>
                    </div>

                    <span
                      className={
                        page.hasCrawlError
                          ? "inline-flex w-fit rounded-full border border-red-900/60 bg-red-950/40 px-2.5 py-1 text-xs font-medium text-red-200"
                          : "inline-flex w-fit rounded-full border border-emerald-900/60 bg-emerald-950/40 px-2.5 py-1 text-xs font-medium text-emerald-200"
                      }
                    >
                      {page.hasCrawlError ? "Crawl fehlgeschlagen" : `HTTP ${page.httpStatus ?? "n/a"}`}
                    </span>
                  </div>

                  <div className="mt-4 grid gap-3 lg:grid-cols-3">
                    <div className="rounded-lg border border-slate-800 bg-slate-950/50 p-3">
                      <p className="text-xs font-medium uppercase tracking-wide text-slate-500">
                        Title
                      </p>
                      <p className="mt-2 text-sm font-medium leading-relaxed text-slate-100">
                        {page.title ?? "Fehlt"}
                      </p>
                      <p className="mt-2 text-xs text-slate-500">
                        Länge: {page.titleLength ?? 0} Zeichen
                      </p>
                    </div>

                    <div className="rounded-lg border border-slate-800 bg-slate-950/50 p-3">
                      <p className="text-xs font-medium uppercase tracking-wide text-slate-500">
                        H1
                      </p>
                      <p className="mt-2 text-sm font-medium leading-relaxed text-slate-100">
                        {page.h1 ?? "Fehlt"}
                      </p>
                      <p className="mt-2 text-xs text-slate-500">
                        Anzahl: {page.h1Count}
                      </p>
                    </div>

                    <div className="rounded-lg border border-slate-800 bg-slate-950/50 p-3">
                      <p className="text-xs font-medium uppercase tracking-wide text-slate-500">
                        Meta Description
                      </p>
                      <p className="mt-2 text-sm font-medium leading-relaxed text-slate-100">
                        {page.metaDescription ?? "Fehlt"}
                      </p>
                      <p className="mt-2 text-xs text-slate-500">
                        Länge: {page.metaDescriptionLength ?? 0} Zeichen
                      </p>
                    </div>
                  </div>

                  {!page.hasCrawlError && (
                    <div className="mt-4 grid gap-2 sm:grid-cols-2 lg:grid-cols-5">
                      <div className="rounded-lg bg-slate-950/50 p-3">
                        <p className="text-xs text-slate-500">Bilder</p>
                        <p className="mt-1 text-lg font-semibold text-slate-100">
                          {page.imageCount}
                        </p>
                      </div>

                      <div className="rounded-lg bg-slate-950/50 p-3">
                        <p className="text-xs text-slate-500">Ohne alt</p>
                        <p
                          className={
                            page.imagesWithoutAlt > 0
                              ? "mt-1 text-lg font-semibold text-amber-200"
                              : "mt-1 text-lg font-semibold text-slate-100"
                          }
                        >
                          {page.imagesWithoutAlt}
                        </p>
                      </div>

                      <div className="rounded-lg bg-slate-950/50 p-3">
                        <p className="text-xs text-slate-500">Interne Links</p>
                        <p className="mt-1 text-lg font-semibold text-slate-100">
                          {page.internalLinksCount}
                        </p>
                      </div>

                      <div className="rounded-lg bg-slate-950/50 p-3">
                        <p className="text-xs text-slate-500">Externe Links</p>
                        <p className="mt-1 text-lg font-semibold text-slate-100">
                          {page.externalLinksCount}
                        </p>
                      </div>

                      <div className="rounded-lg bg-slate-950/50 p-3">
                        <p className="text-xs text-slate-500">HTML-Größe</p>
                        <p className="mt-1 text-lg font-semibold text-slate-100">
                          {formatBytes(page.htmlSizeBytes)}
                        </p>
                      </div>
                    </div>
                  )}

                  {(() => {
                    const visibleIssues =
                      severityFilter === "all"
                        ? page.issues
                        : page.issues.filter((issue) => issue.severity === severityFilter);

                    if (visibleIssues.length > 0) {
                      return (
                        <div className="mt-4 rounded-lg border border-slate-800 bg-slate-950/40 p-3">
                          <div className="mb-2 flex items-center justify-between gap-3">
                            <p className="text-xs font-medium uppercase tracking-wide text-slate-500">
                              Gefundene Hinweise
                            </p>

                            <span className="text-xs text-slate-500">
                              {visibleIssues.length} angezeigt
                            </span>
                          </div>

                          <ul className="space-y-2 text-xs">
                            {visibleIssues.map((issue) => (
                              <li
                                key={`${page.id ?? page.url}-${issue.code}`}
                                className={getIssueClassName(issue.severity)}
                              >
                                <span className="font-medium">
                                  {issue.message}
                                </span>
                              </li>
                            ))}
                          </ul>
                        </div>
                      );
                    }

                    if (page.issues.length > 0) {
                      return (
                        <p className="mt-4 rounded-lg border border-slate-800 bg-slate-950/40 p-3 text-xs text-slate-400">
                          Für diesen Filter gibt es auf dieser Seite keine passenden Issues.
                        </p>
                      );
                    }

                    return (
                      <p className="mt-4 rounded-lg border border-emerald-900/50 bg-emerald-950/30 p-3 text-xs text-emerald-300">
                        Keine Probleme erkannt.
                      </p>
                    );
                  })()}
                </div>
              ))}
            </div>
          </div>
        )}
      </div>
    </div>
  );
}