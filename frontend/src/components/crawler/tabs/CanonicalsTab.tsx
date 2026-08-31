"use client";

import { useEffect, useState } from "react";
import { getCrawlRunCanonicals } from "@/api/crawl";
import type { CanonicalItem } from "@/types/crawl";

interface CanonicalsTabProps {
  crawlRunId: number;
}

export function CanonicalsTab({ crawlRunId }: CanonicalsTabProps) {
  const [canonicals, setCanonicals] = useState<CanonicalItem[]>([]);
  const [summary, setSummary] = useState<any>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [statusFilter, setStatusFilter] = useState<string>("");

  useEffect(() => {
    let isMounted = true;

    async function loadCanonicals() {
      setIsLoading(true);
      setError(null);

      try {
        const response = await getCrawlRunCanonicals(crawlRunId);

        if (isMounted) {
          setCanonicals(response.data);
          setSummary(response.summary);
        }
      } catch (err) {
        if (isMounted) {
          setError(
            err instanceof Error
              ? err.message
              : "Canonicals konnten nicht geladen werden"
          );
        }
      } finally {
        if (isMounted) {
          setIsLoading(false);
        }
      }
    }

    void loadCanonicals();

    return () => {
      isMounted = false;
    };
  }, [crawlRunId]);

  if (isLoading) {
    return (
      <div className="rounded-lg border border-slate-800 bg-slate-900/60 p-6">
        <p className="text-sm text-slate-400">Canonicals werden geladen …</p>
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

  const getStatusLabel = (status: string) => {
    const labels: Record<string, string> = {
      self: "Self",
      other_url: "Andere URL",
      missing: "Fehlt",
      invalid: "Ungültig",
      multiple: "Mehrfach",
      empty: "Leer",
    };
    return labels[status] || status;
  };

  const getStatusColor = (status: string) => {
    switch (status) {
      case "self":
        return "text-emerald-300";
      case "other_url":
        return "text-sky-300";
      case "missing":
      case "invalid":
      case "multiple":
      case "empty":
        return "text-amber-300";
      default:
        return "text-slate-300";
    }
  };

  const filteredCanonicals = statusFilter
    ? canonicals.filter((c) => c.status === statusFilter)
    : canonicals;

  return (
    <div className="space-y-4">
      {/* Summary */}
      {summary && (
        <div className="grid gap-4 sm:grid-cols-3 lg:grid-cols-4">
          <div className="rounded-lg border border-slate-800 bg-slate-900/60 p-4">
            <p className="text-xs font-medium uppercase tracking-wide text-slate-500">
              Gesamt
            </p>
            <p className="mt-2 text-3xl font-semibold text-slate-100">
              {summary.total}
            </p>
          </div>
          <div className="rounded-lg border border-emerald-900/60 bg-emerald-950/40 p-4">
            <p className="text-xs font-medium uppercase tracking-wide text-emerald-400">
              Self-Canonical
            </p>
            <p className="mt-2 text-3xl font-semibold text-emerald-200">
              {summary.self}
            </p>
          </div>
          <div className="rounded-lg border border-sky-900/60 bg-sky-950/40 p-4">
            <p className="text-xs font-medium uppercase tracking-wide text-sky-400">
              Andere URL
            </p>
            <p className="mt-2 text-3xl font-semibold text-sky-200">
              {summary.otherUrl}
            </p>
          </div>
          <div className="rounded-lg border border-amber-900/60 bg-amber-950/40 p-4">
            <p className="text-xs font-medium uppercase tracking-wide text-amber-400">
              Fehlend
            </p>
            <p className="mt-2 text-3xl font-semibold text-amber-200">
              {summary.missing}
            </p>
          </div>
          <div className="rounded-lg border border-red-900/60 bg-red-950/40 p-4">
            <p className="text-xs font-medium uppercase tracking-wide text-red-400">
              Ungültig
            </p>
            <p className="mt-2 text-3xl font-semibold text-red-200">
              {summary.invalid}
            </p>
          </div>
          <div className="rounded-lg border border-amber-900/60 bg-amber-950/40 p-4">
            <p className="text-xs font-medium uppercase tracking-wide text-amber-400">
              Mehrfach
            </p>
            <p className="mt-2 text-3xl font-semibold text-amber-200">
              {summary.multiple}
            </p>
          </div>
          <div className="rounded-lg border border-amber-900/60 bg-amber-950/40 p-4">
            <p className="text-xs font-medium uppercase tracking-wide text-amber-400">
              Leer
            </p>
            <p className="mt-2 text-3xl font-semibold text-amber-200">
              {summary.empty}
            </p>
          </div>
        </div>
      )}

      {/* Filters */}
      <div className="flex flex-wrap gap-2">
        <button
          type="button"
          onClick={() => setStatusFilter("")}
          className={
            statusFilter === ""
              ? "rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-950"
              : "rounded-full border border-slate-700 px-3 py-1 text-xs font-medium text-slate-300 transition hover:border-slate-500 hover:text-slate-100"
          }
        >
          Alle
        </button>
        {["self", "other_url", "missing", "invalid", "multiple", "empty"].map((status) => (
          <button
            key={status}
            type="button"
            onClick={() => setStatusFilter(status)}
            className={
              statusFilter === status
                ? "rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-950"
                : "rounded-full border border-slate-700 px-3 py-1 text-xs font-medium text-slate-300 transition hover:border-slate-500 hover:text-slate-100"
            }
          >
            {getStatusLabel(status)}
          </button>
        ))}
      </div>

      <p className="text-sm text-slate-400">
        {filteredCanonicals.length} von {canonicals.length} Seiten
      </p>

      {/* Canonical List */}
      <div className="overflow-x-auto">
        <table className="w-full text-left text-sm">
          <thead className="border-b border-slate-800 bg-slate-900/60 text-xs uppercase tracking-wide text-slate-400">
            <tr>
              <th className="px-4 py-3">Seite</th>
              <th className="px-4 py-3">Raw href</th>
              <th className="px-4 py-3">Canonical URL</th>
              <th className="px-4 py-3">Count</th>
              <th className="px-4 py-3">Status</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-slate-800">
            {filteredCanonicals.map((canonical) => (
              <tr
                key={canonical.id}
                className="bg-slate-900/40 transition hover:bg-slate-800/60"
              >
                <td className="px-4 py-3">
                  <a
                    href={canonical.url}
                    target="_blank"
                    rel="noreferrer"
                    className="break-all text-sky-300 underline decoration-slate-600 underline-offset-2 transition hover:text-sky-200 hover:decoration-sky-400"
                  >
                    {canonical.url}
                  </a>
                </td>
                <td className="px-4 py-3">
                  <code className="break-all text-xs text-slate-300">
                    {canonical.canonicalHref ?? "—"}
                  </code>
                </td>
                <td className="px-4 py-3 text-slate-300">
                  {canonical.canonicalUrl ? (
                    <span className="break-all">{canonical.canonicalUrl}</span>
                  ) : (
                    <span className="text-slate-500">—</span>
                  )}
                </td>
                <td className="px-4 py-3 text-slate-300">
                  {canonical.canonicalCount}
                </td>
                <td className="px-4 py-3">
                  <span className={getStatusColor(canonical.status)}>
                    {getStatusLabel(canonical.status)}
                  </span>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      {filteredCanonicals.length === 0 && (
        <div className="rounded-lg border border-slate-800 bg-slate-900/60 p-6 text-center">
          <p className="text-sm text-slate-400">
            Keine Canonicals für diesen Filter gefunden.
          </p>
        </div>
      )}
    </div>
  );
}
