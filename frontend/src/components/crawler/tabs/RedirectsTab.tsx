"use client";

import { useEffect, useState } from "react";
import { getCrawlRunRedirects } from "@/api/crawl";
import type { RedirectItem } from "@/types/crawl";

interface RedirectsTabProps {
  crawlRunId: number;
}

export function RedirectsTab({ crawlRunId }: RedirectsTabProps) {
  const [redirects, setRedirects] = useState<RedirectItem[]>([]);
  const [summary, setSummary] = useState<{ total: number; maxHops: number } | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [expandedIds, setExpandedIds] = useState<Set<number>>(new Set());

  useEffect(() => {
    let isMounted = true;

    async function loadRedirects() {
      setIsLoading(true);
      setError(null);

      try {
        const response = await getCrawlRunRedirects(crawlRunId);

        if (isMounted) {
          setRedirects(response.data);
          setSummary(response.summary);
        }
      } catch (err) {
        if (isMounted) {
          setError(
            err instanceof Error
              ? err.message
              : "Redirects konnten nicht geladen werden"
          );
        }
      } finally {
        if (isMounted) {
          setIsLoading(false);
        }
      }
    }

    void loadRedirects();

    return () => {
      isMounted = false;
    };
  }, [crawlRunId]);

  function toggleExpanded(id: number) {
    setExpandedIds((prev) => {
      const next = new Set(prev);
      if (next.has(id)) {
        next.delete(id);
      } else {
        next.add(id);
      }
      return next;
    });
  }

  if (isLoading) {
    return (
      <div className="rounded-lg border border-slate-800 bg-slate-900/60 p-6">
        <p className="text-sm text-slate-400">Redirects werden geladen …</p>
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

  return (
    <div className="space-y-4">
      {/* Summary */}
      {summary && (
        <div className="grid gap-4 sm:grid-cols-2">
          <div className="rounded-lg border border-slate-800 bg-slate-900/60 p-4">
            <p className="text-xs font-medium uppercase tracking-wide text-slate-500">
              Seiten mit Redirects
            </p>
            <p className="mt-2 text-3xl font-semibold text-slate-100">
              {summary.total}
            </p>
          </div>
          <div className="rounded-lg border border-slate-800 bg-slate-900/60 p-4">
            <p className="text-xs font-medium uppercase tracking-wide text-slate-500">
              Max. Redirect-Hops
            </p>
            <p className="mt-2 text-3xl font-semibold text-slate-100">
              {summary.maxHops}
            </p>
          </div>
        </div>
      )}

      {/* Redirect List */}
      <div className="space-y-3">
        {redirects.map((redirect) => {
          const isExpanded = expandedIds.has(redirect.id);

          return (
            <div
              key={redirect.id}
              className="rounded-lg border border-slate-800 bg-slate-900/40 p-4"
            >
              <div className="flex items-start justify-between gap-3">
                <div className="min-w-0">
                  <a
                    href={redirect.requestedUrl}
                    target="_blank"
                    rel="noreferrer"
                    className="break-all text-sm font-medium text-sky-300 underline decoration-slate-600 underline-offset-2 hover:text-sky-200 hover:decoration-sky-400"
                  >
                    {redirect.requestedUrl}
                  </a>
                  <p className="mt-1 text-xs text-slate-500">
                    → {redirect.finalUrl}
                  </p>
                </div>
                <div className="flex shrink-0 items-center gap-2">
                  <span className="rounded-full border border-amber-900/60 bg-amber-950/40 px-2 py-0.5 text-xs font-medium text-amber-200">
                    {redirect.redirectCount} Hop{redirect.redirectCount !== 1 ? "s" : ""}
                  </span>
                  <button
                    type="button"
                    onClick={() => toggleExpanded(redirect.id)}
                    className="rounded-full border border-slate-700 px-3 py-1 text-xs font-medium text-slate-300 transition hover:border-slate-500 hover:text-slate-100"
                  >
                    {isExpanded ? "Ausblenden" : "Chain zeigen"}
                  </button>
                </div>
              </div>

              {isExpanded && redirect.redirectChain && redirect.redirectChain.length > 0 && (
                <div className="mt-4 rounded-lg border border-slate-800 bg-slate-950/50 p-3">
                  <p className="mb-2 text-xs font-medium uppercase tracking-wide text-slate-500">
                    Redirect-Chain
                  </p>
                  <div className="space-y-2">
                    {redirect.redirectChain.map((hop: any, index: number) => (
                      <div key={index} className="flex items-start gap-2 text-xs">
                        <span className="shrink-0 text-slate-500">{index + 1}.</span>
                        <div className="min-w-0">
                          <p className="break-all text-slate-300">{hop.url}</p>
                          <p className="mt-0.5 text-slate-500">
                            Status: {hop.status_code}
                          </p>
                        </div>
                      </div>
                    ))}
                  </div>
                </div>
              )}
            </div>
          );
        })}
      </div>

      {redirects.length === 0 && (
        <div className="rounded-lg border border-slate-800 bg-slate-900/60 p-6 text-center">
          <p className="text-sm text-slate-400">
            Keine Redirects gefunden.
          </p>
        </div>
      )}
    </div>
  );
}
