"use client";

import { useEffect, useState } from "react";
import { getDashboardSummary } from "@/api/dashboard";
import type { DashboardSummary as DashboardSummaryType } from "@/types/dashboard";

interface DashboardSummaryProps {
  refreshKey?: number;
}

function getSeverityLabel(severity: "error" | "warning" | "info") {
  if (severity === "error") {
    return "Fehler";
  }

  if (severity === "warning") {
    return "Warnung";
  }

  return "Hinweis";
}

function getSeverityClassName(severity: "error" | "warning" | "info") {
  if (severity === "error") {
    return "text-red-300";
  }

  if (severity === "warning") {
    return "text-amber-300";
  }

  return "text-sky-300";
}

export function DashboardSummary({ refreshKey = 0 }: DashboardSummaryProps) {
  const [summary, setSummary] = useState<DashboardSummaryType | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [isTopIssuesOpen, setIsTopIssuesOpen] = useState(false);

  useEffect(() => {
    let isMounted = true;

    async function loadSummary() {
      try {
        const response = await getDashboardSummary();

        if (isMounted) {
          setSummary(response);
          setError(null);
        }
      } catch (error) {
        if (isMounted) {
          setError(
            error instanceof Error
              ? error.message
              : "Die Gesamtübersicht konnte nicht geladen werden.",
          );
        }
      } finally {
        if (isMounted) {
          setIsLoading(false);
        }
      }
    }

    void loadSummary();

    return () => {
      isMounted = false;
    };
  }, [refreshKey]);

  return (
    <section className="rounded-2xl border border-slate-800 bg-slate-900 p-6 shadow-xl">
      <div className="mb-5">
        <h2 className="text-xl font-semibold">Gesamtübersicht</h2>
        <p className="mt-1 text-sm text-slate-400">
          Überblick über gespeicherte Websites, Crawls und erkannte Probleme.
        </p>
      </div>

      {isLoading && (
        <p className="text-sm text-slate-400">
          Gesamtübersicht wird geladen …
        </p>
      )}

      {error && (
        <p className="rounded-xl border border-red-900 bg-red-950/60 p-4 text-sm text-red-200">
          {error}
        </p>
      )}

      {summary && (
        <div className="space-y-5">
          <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <div className="rounded-xl border border-slate-800 bg-slate-950/60 p-4">
              <p className="text-xs font-medium uppercase tracking-wide text-slate-500">
                Websites gesamt
              </p>
              <p className="mt-2 text-3xl font-bold text-slate-100">
                {summary.totalWebsites}
              </p>
            </div>

            <div className="rounded-xl border border-slate-800 bg-slate-950/60 p-4">
              <p className="text-xs font-medium uppercase tracking-wide text-slate-500">
                Crawls gesamt
              </p>
              <p className="mt-2 text-3xl font-bold text-slate-100">
                {summary.totalCrawlRuns}
              </p>
            </div>

            <div className="rounded-xl border border-slate-800 bg-slate-950/60 p-4">
              <p className="text-xs font-medium uppercase tracking-wide text-slate-500">
                Websites mit Problemen
              </p>
              <p className="mt-2 text-3xl font-bold text-amber-200">
                {summary.websitesWithIssues}
              </p>
            </div>

            <div className="rounded-xl border border-slate-800 bg-slate-950/60 p-4">
              <p className="text-xs font-medium uppercase tracking-wide text-slate-500">
                Probleme gesamt
              </p>
              <p className="mt-2 text-3xl font-bold text-slate-100">
                {summary.totalIssues}
              </p>
              <p className="mt-2 text-xs text-slate-500">
                Fehler: {summary.issuesBySeverity.errors} · Warnungen:{" "}
                {summary.issuesBySeverity.warnings} · Hinweise:{" "}
                {summary.issuesBySeverity.infos}
              </p>
            </div>
          </div>

          <div className="rounded-xl border border-slate-800 bg-slate-950/60">
            <button
              type="button"
              onClick={() => setIsTopIssuesOpen((current) => !current)}
              className="flex w-full flex-col gap-2 p-4 text-left sm:flex-row sm:items-center sm:justify-between"
            >
              <div>
                <h3 className="font-semibold text-slate-100">
                  Häufigste Probleme
                </h3>
                <p className="text-sm text-slate-500">
                  Gruppiert nach Issue-Code über alle gespeicherten Analysen.
                </p>
              </div>

              <span className="rounded-full border border-slate-700 px-3 py-1 text-xs font-medium text-slate-300">
                {isTopIssuesOpen ? "Einklappen" : "Ausklappen"}
              </span>
            </button>

            {isTopIssuesOpen && (
              <div className="border-t border-slate-800 p-4 pt-3">
                {summary.topIssues.length === 0 ? (
                  <p className="text-sm text-slate-400">
                    Es wurden noch keine Probleme gespeichert.
                  </p>
                ) : (
                  <ul className="space-y-2">
                    {summary.topIssues.map((issue) => (
                      <li
                        key={`${issue.code}-${issue.severity}`}
                        className="flex flex-col gap-2 rounded-lg border border-slate-800 bg-slate-900/70 p-3 sm:flex-row sm:items-center sm:justify-between"
                      >
                        <div className="min-w-0">
                          <p className="text-sm font-medium text-slate-100">
                            {issue.message}
                          </p>
                          <p className="mt-1 text-xs text-slate-500">
                            <span className={getSeverityClassName(issue.severity)}>
                              {getSeverityLabel(issue.severity)}
                            </span>{" "}
                            · {issue.code}
                          </p>
                        </div>

                        <span className="w-fit rounded-full bg-slate-800 px-3 py-1 text-sm font-semibold text-slate-100">
                          {issue.count}×
                        </span>
                      </li>
                    ))}
                  </ul>
                )}
              </div>
            )}
          </div>
        </div>
      )}
    </section>
  );
}