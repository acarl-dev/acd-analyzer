"use client";

import { useEffect, useState } from "react";
import { getCrawlRunIssues } from "@/api/crawl";
import type { IssueItem, IssueGroupItem } from "@/types/crawl";
import { getSeverityBadgeClassName, getSeverityLabel } from "../crawlResultUi";

interface IssuesTabProps {
  crawlRunId: number;
}

export function IssuesTab({ crawlRunId }: IssuesTabProps) {
  const [issues, setIssues] = useState<IssueItem[]>([]);
  const [groupedIssues, setGroupedIssues] = useState<IssueGroupItem[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [severityFilter, setSeverityFilter] = useState<string>("");
  const [selectedCode, setSelectedCode] = useState<string>("");

  useEffect(() => {
    let isMounted = true;

    async function loadIssues() {
      setIsLoading(true);
      setError(null);

      try {
        const filters = severityFilter ? { severity: severityFilter } : undefined;
        const response = await getCrawlRunIssues(crawlRunId, filters);

        if (isMounted) {
          setIssues(response.data);
          setGroupedIssues(response.groupedByCode);
        }
      } catch (err) {
        if (isMounted) {
          setError(
            err instanceof Error ? err.message : "Issues konnten nicht geladen werden"
          );
        }
      } finally {
        if (isMounted) {
          setIsLoading(false);
        }
      }
    }

    void loadIssues();

    return () => {
      isMounted = false;
    };
  }, [crawlRunId, severityFilter]);

  if (isLoading) {
    return (
      <div className="rounded-lg border border-slate-800 bg-slate-900/60 p-6">
        <p className="text-sm text-slate-400">Issues werden geladen …</p>
      </div>
    );
  }

  if (error) {
    return (
      <div className="rounded-lg border border-red-900 bg-red-950/60 p-6">
        <p className="text-sm text-red-200">{error}</p>
      </div>
    );
  }

  const displayedIssues = selectedCode
    ? issues.filter((issue) => issue.code === selectedCode)
    : issues;

  return (
    <div className="space-y-4">
      {/* Filters */}
      <div className="flex flex-wrap gap-2">
        <button
          type="button"
          onClick={() => setSeverityFilter("")}
          className={
            severityFilter === ""
              ? "rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-950"
              : "rounded-full border border-slate-700 px-3 py-1 text-xs font-medium text-slate-300 transition hover:border-slate-500 hover:text-slate-100"
          }
        >
          Alle
        </button>
        <button
          type="button"
          onClick={() => setSeverityFilter("error")}
          className={
            severityFilter === "error"
              ? "rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-950"
              : "rounded-full border border-slate-700 px-3 py-1 text-xs font-medium text-slate-300 transition hover:border-slate-500 hover:text-slate-100"
          }
        >
          Fehler
        </button>
        <button
          type="button"
          onClick={() => setSeverityFilter("warning")}
          className={
            severityFilter === "warning"
              ? "rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-950"
              : "rounded-full border border-slate-700 px-3 py-1 text-xs font-medium text-slate-300 transition hover:border-slate-500 hover:text-slate-100"
          }
        >
          Warnungen
        </button>
        <button
          type="button"
          onClick={() => setSeverityFilter("info")}
          className={
            severityFilter === "info"
              ? "rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-950"
              : "rounded-full border border-slate-700 px-3 py-1 text-xs font-medium text-slate-300 transition hover:border-slate-500 hover:text-slate-100"
          }
        >
          Hinweise
        </button>
      </div>

      {/* Grouped Issues Summary */}
      <div className="rounded-lg border border-slate-800 bg-slate-900/60 p-4">
        <h3 className="mb-3 text-sm font-medium text-slate-300">
          Issues nach Code gruppiert
        </h3>
        <div className="space-y-2">
          {groupedIssues.map((group) => (
            <button
              key={group.code}
              type="button"
              onClick={() => setSelectedCode(selectedCode === group.code ? "" : group.code)}
              className={
                selectedCode === group.code
                  ? "w-full rounded-lg border border-slate-600 bg-slate-800 p-3 text-left transition"
                  : "w-full rounded-lg border border-slate-800 bg-slate-900/40 p-3 text-left transition hover:border-slate-700 hover:bg-slate-800/60"
              }
            >
              <div className="flex items-center justify-between gap-3">
                <div className="flex items-center gap-2">
                  <span
                    className={`rounded-full border px-2 py-0.5 text-xs font-medium ${getSeverityBadgeClassName(
                      group.severity
                    )}`}
                  >
                    {getSeverityLabel(group.severity)}
                  </span>
                  <span className="text-sm font-medium text-slate-100">
                    {group.message}
                  </span>
                </div>
                <span className="text-sm font-semibold text-slate-300">
                  {group.count}×
                </span>
              </div>
              <p className="mt-1 text-xs text-slate-500">{group.code}</p>
            </button>
          ))}
        </div>
      </div>

      {/* Issue List */}
      {selectedCode && (
        <div className="space-y-2">
          <div className="flex items-center justify-between">
            <h3 className="text-sm font-medium text-slate-300">
              Betroffene Seiten ({displayedIssues.length})
            </h3>
            <button
              type="button"
              onClick={() => setSelectedCode("")}
              className="text-xs text-slate-400 hover:text-slate-300"
            >
              Filter aufheben
            </button>
          </div>
          {displayedIssues.map((issue) => (
            <div
              key={issue.id}
              className="rounded-lg border border-slate-800 bg-slate-900/40 p-3"
            >
              <div className="flex items-start justify-between gap-3">
                <span
                  className={`rounded-full border px-2 py-0.5 text-xs font-medium ${getSeverityBadgeClassName(
                    issue.severity
                  )}`}
                >
                  {getSeverityLabel(issue.severity)}
                </span>
                {issue.pageUrl && (
                  <a
                    href={issue.pageUrl}
                    target="_blank"
                    rel="noreferrer"
                    className="break-all text-xs text-sky-300 underline decoration-slate-600 underline-offset-2 hover:text-sky-200 hover:decoration-sky-400"
                  >
                    {issue.pageUrl}
                  </a>
                )}
              </div>
              <p className="mt-2 text-sm text-slate-100">{issue.message}</p>
              <p className="mt-1 text-xs text-slate-500">{issue.code}</p>
            </div>
          ))}
        </div>
      )}

      {!selectedCode && displayedIssues.length === 0 && (
        <div className="rounded-lg border border-slate-800 bg-slate-900/60 p-6 text-center">
          <p className="text-sm text-slate-400">
            Keine Issues für diesen Filter gefunden.
          </p>
        </div>
      )}
    </div>
  );
}
