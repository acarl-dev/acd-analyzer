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

function getSeverityBadgeClassName(severity: "error" | "warning" | "info") {
  if (severity === "error") {
    return "border-red-800 bg-red-950/70 text-red-200";
  }

  if (severity === "warning") {
    return "border-amber-800 bg-amber-950/70 text-amber-200";
  }

  return "border-sky-800 bg-sky-950/70 text-sky-200";
}

function getTechnologyTypeLabel(type: string) {
  if (type === "cms") {
    return "CMS";
  }

  if (type === "frontend") {
    return "Frontend";
  }

  if (type === "rendering") {
    return "Rendering";
  }

  return type;
}

export function DashboardSummary({ refreshKey = 0 }: DashboardSummaryProps) {
  const [summary, setSummary] = useState<DashboardSummaryType | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [showAllTopIssues, setShowAllTopIssues] = useState(false);
  const [showAllTopTechnologies, setShowAllTopTechnologies] = useState(false);

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

  const visibleTopIssues = showAllTopIssues
    ? summary?.topIssues ?? []
    : summary?.topIssues.slice(0, 3) ?? [];

  const visibleTopTechnologies = showAllTopTechnologies
    ? summary?.topTechnologies ?? []
    : summary?.topTechnologies.slice(0, 3) ?? [];

  return (
    <section className="rounded-2xl border border-slate-800 bg-slate-900 p-6 shadow-xl">
      <div className="mb-5">
        <h2 className="text-xl font-semibold">Gesamtübersicht</h2>
        <p className="mt-1 text-sm text-slate-400">
          Überblick über gespeicherte Websites, Crawls, erkannte Probleme und
          Technologien.
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

                    <div className="grid gap-3 lg:grid-cols-2">
            <div className="rounded-xl border border-slate-800 bg-slate-950/60 p-4">
              <div className="mb-3 flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                <div>
                  <h3 className="font-semibold text-slate-100">
                    Häufigste Probleme
                  </h3>
                  <p className="text-sm text-slate-500">
                    Top-Issue-Codes über alle gespeicherten Analysen.
                  </p>
                </div>

                {summary.topIssues.length > 3 && (
                  <button
                    type="button"
                    onClick={() => setShowAllTopIssues((current) => !current)}
                    className="w-fit rounded-full border border-slate-700 px-3 py-1 text-xs font-medium text-slate-300 transition hover:border-slate-500 hover:text-slate-100"
                  >
                    {showAllTopIssues ? "Weniger anzeigen" : "Mehr anzeigen"}
                  </button>
                )}
              </div>

              {summary.topIssues.length === 0 ? (
                <p className="text-sm text-slate-400">
                  Es wurden noch keine Probleme gespeichert.
                </p>
              ) : (
                <ul className="space-y-2">
                  {visibleTopIssues.map((issue) => (
                    <li
                      key={`${issue.code}-${issue.severity}`}
                      className="flex flex-col gap-2 rounded-lg border border-slate-800 bg-slate-900/70 px-3 py-2 sm:flex-row sm:items-center sm:justify-between"
                    >
                      <div className="min-w-0">
                        <p className="text-sm font-medium text-slate-100">
                          {issue.message}
                        </p>
                        <div className="mt-2 flex flex-wrap items-center gap-2 text-xs">
                          <span
                            className={`rounded-full border px-2 py-0.5 font-medium ${getSeverityBadgeClassName(
                              issue.severity,
                            )}`}
                          >
                            {getSeverityLabel(issue.severity)}
                          </span>

                          <span className="font-mono text-slate-500">
                            {issue.code}
                          </span>
                        </div>
                      </div>

                      <span className="w-fit rounded-full border border-slate-700 bg-slate-800 px-3 py-1 text-sm font-semibold text-slate-100">
                        {issue.count}×
                      </span>
                    </li>
                  ))}
                </ul>
              )}
            </div>

            <div className="rounded-xl border border-slate-800 bg-slate-950/60 p-4">
              <div className="mb-3 flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                <div>
                  <h3 className="font-semibold text-slate-100">
                    Häufigste Technologien
                  </h3>
                  <p className="text-sm text-slate-500">
                    Top-Technologien über alle gespeicherten Crawls.
                  </p>
                </div>

                {summary.topTechnologies.length > 3 && (
                  <button
                    type="button"
                    onClick={() =>
                      setShowAllTopTechnologies((current) => !current)
                    }
                    className="w-fit rounded-full border border-slate-700 px-3 py-1 text-xs font-medium text-slate-300 transition hover:border-slate-500 hover:text-slate-100"
                  >
                    {showAllTopTechnologies
                      ? "Weniger anzeigen"
                      : "Mehr anzeigen"}
                  </button>
                )}
              </div>

              {summary.topTechnologies.length === 0 ? (
                <p className="text-sm text-slate-400">
                  Es wurden noch keine Technologien erkannt.
                </p>
              ) : (
                <ul className="space-y-2">
                  {visibleTopTechnologies.map((technology) => (
                    <li
                      key={`${technology.type}-${technology.name}`}
                      className="flex flex-col gap-2 rounded-lg border border-slate-800 bg-slate-900/70 px-3 py-2 sm:flex-row sm:items-center sm:justify-between"
                    >
                      <div className="min-w-0">
                        <p className="text-sm font-medium text-slate-100">
                          {technology.name}
                        </p>
                        <div className="mt-2 flex flex-wrap items-center gap-2 text-xs">
                          <span className="rounded-full border border-slate-700 bg-slate-800 px-2 py-0.5 font-medium text-slate-300">
                            {getTechnologyTypeLabel(technology.type)}
                          </span>

                          <span className="text-slate-500">
                            Sicherheit:{" "}
                            {Math.round(technology.confidence * 100)}%
                          </span>
                        </div>
                      </div>

                      <span className="w-fit rounded-full border border-slate-700 bg-slate-800 px-3 py-1 text-sm font-semibold text-slate-100">
                        {technology.count}{" "}
                        {technology.count === 1 ? "Crawl" : "Crawls"}
                      </span>
                    </li>
                  ))}
                </ul>
              )}
            </div>
          </div>
        </div>
      )}
    </section>
  );
}