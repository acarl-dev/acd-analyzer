"use client";

import { useEffect, useState } from "react";
import { getCrawlRunLinks } from "@/api/crawl";
import type { LinkItem } from "@/types/crawl";

interface LinksTabProps {
  crawlRunId: number;
}

export function LinksTab({ crawlRunId }: LinksTabProps) {
  const [links, setLinks] = useState<LinkItem[]>([]);
  const [summary, setSummary] = useState<{ total: number; internal: number; external: number } | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [filter, setFilter] = useState<"all" | "internal" | "external">("all");
  const [searchTerm, setSearchTerm] = useState("");

  useEffect(() => {
    let isMounted = true;

    async function loadLinks() {
      setIsLoading(true);
      setError(null);

      try {
        const response = await getCrawlRunLinks(crawlRunId);

        if (isMounted) {
          setLinks(response.data);
          setSummary(response.summary);
        }
      } catch (err) {
        if (isMounted) {
          setError(
            err instanceof Error ? err.message : "Links konnten nicht geladen werden"
          );
        }
      } finally {
        if (isMounted) {
          setIsLoading(false);
        }
      }
    }

    void loadLinks();

    return () => {
      isMounted = false;
    };
  }, [crawlRunId]);

  if (isLoading) {
    return (
      <div className="rounded-lg border border-slate-800 bg-slate-900/60 p-6">
        <p className="text-sm text-slate-400">Links werden geladen …</p>
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

  const filteredLinks = links
    .filter((link) => {
      if (filter === "internal") return link.isInternal;
      if (filter === "external") return !link.isInternal;
      return true;
    })
    .filter((link) =>
      searchTerm
        ? link.href.toLowerCase().includes(searchTerm.toLowerCase()) ||
          link.normalizedUrl.toLowerCase().includes(searchTerm.toLowerCase())
        : true
    );

  return (
    <div className="space-y-4">
      {/* Summary */}
      {summary && (
        <div className="grid gap-4 sm:grid-cols-3">
          <div className="rounded-lg border border-slate-800 bg-slate-900/60 p-4">
            <p className="text-xs font-medium uppercase tracking-wide text-slate-500">
              Gesamt
            </p>
            <p className="mt-2 text-3xl font-semibold text-slate-100">
              {summary.total}
            </p>
          </div>
          <div className="rounded-lg border border-slate-800 bg-slate-900/60 p-4">
            <p className="text-xs font-medium uppercase tracking-wide text-slate-500">
              Intern
            </p>
            <p className="mt-2 text-3xl font-semibold text-slate-100">
              {summary.internal}
            </p>
          </div>
          <div className="rounded-lg border border-slate-800 bg-slate-900/60 p-4">
            <p className="text-xs font-medium uppercase tracking-wide text-slate-500">
              Extern
            </p>
            <p className="mt-2 text-3xl font-semibold text-slate-100">
              {summary.external}
            </p>
          </div>
        </div>
      )}

      {/* Filters */}
      <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div className="flex flex-wrap gap-2">
          <button
            type="button"
            onClick={() => setFilter("all")}
            className={
              filter === "all"
                ? "rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-950"
                : "rounded-full border border-slate-700 px-3 py-1 text-xs font-medium text-slate-300 transition hover:border-slate-500 hover:text-slate-100"
            }
          >
            Alle
          </button>
          <button
            type="button"
            onClick={() => setFilter("internal")}
            className={
              filter === "internal"
                ? "rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-950"
                : "rounded-full border border-slate-700 px-3 py-1 text-xs font-medium text-slate-300 transition hover:border-slate-500 hover:text-slate-100"
            }
          >
            Intern
          </button>
          <button
            type="button"
            onClick={() => setFilter("external")}
            className={
              filter === "external"
                ? "rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-950"
                : "rounded-full border border-slate-700 px-3 py-1 text-xs font-medium text-slate-300 transition hover:border-slate-500 hover:text-slate-100"
            }
          >
            Extern
          </button>
        </div>
        <input
          type="text"
          placeholder="URL durchsuchen..."
          value={searchTerm}
          onChange={(e) => setSearchTerm(e.target.value)}
          className="rounded-lg border border-slate-700 bg-slate-900 px-3 py-2 text-sm text-slate-100 placeholder-slate-500 focus:border-slate-500 focus:outline-none"
        />
      </div>

      <p className="text-sm text-slate-400">
        {filteredLinks.length} von {links.length} Links
      </p>

      {/* Links Table */}
      <div className="overflow-x-auto">
        <table className="w-full text-left text-sm">
          <thead className="border-b border-slate-800 bg-slate-900/60 text-xs uppercase tracking-wide text-slate-400">
            <tr>
              <th className="px-4 py-3">Raw href</th>
              <th className="px-4 py-3">Normalized URL</th>
              <th className="px-4 py-3">Type</th>
              <th className="px-4 py-3">Quelle</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-slate-800">
            {filteredLinks.map((link) => (
              <tr
                key={link.id}
                className="bg-slate-900/40 transition hover:bg-slate-800/60"
              >
                <td className="px-4 py-3">
                  <code className="break-all text-xs text-slate-300">{link.href}</code>
                </td>
                <td className="px-4 py-3">
                  <a
                    href={link.normalizedUrl}
                    target="_blank"
                    rel="noreferrer"
                    className="break-all text-sky-300 underline decoration-slate-600 underline-offset-2 transition hover:text-sky-200 hover:decoration-sky-400"
                  >
                    {link.normalizedUrl}
                  </a>
                </td>
                <td className="px-4 py-3">
                  <span
                    className={
                      link.isInternal
                        ? "text-emerald-300"
                        : "text-amber-300"
                    }
                  >
                    {link.isInternal ? "Intern" : "Extern"}
                  </span>
                </td>
                <td className="px-4 py-3">
                  <a
                    href={link.sourcePageUrl}
                    target="_blank"
                    rel="noreferrer"
                    className="break-all text-xs text-slate-400 underline decoration-slate-700 underline-offset-2 hover:text-slate-300"
                  >
                    {link.sourcePageUrl}
                  </a>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      {filteredLinks.length === 0 && (
        <div className="rounded-lg border border-slate-800 bg-slate-900/60 p-6 text-center">
          <p className="text-sm text-slate-400">
            Keine Links gefunden, die deinen Suchkriterien entsprechen.
          </p>
        </div>
      )}
    </div>
  );
}
