"use client";

import { useEffect, useState } from "react";
import { getCrawlRunPages } from "@/api/crawl";
import type { PageListItem } from "@/types/crawl";

interface PagesTabProps {
  crawlRunId: number;
}

export function PagesTab({ crawlRunId }: PagesTabProps) {
  const [pages, setPages] = useState<PageListItem[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [searchTerm, setSearchTerm] = useState("");

  useEffect(() => {
    let isMounted = true;

    async function loadPages() {
      setIsLoading(true);
      setError(null);

      try {
        const response = await getCrawlRunPages(crawlRunId);

        if (isMounted) {
          setPages(response.data);
        }
      } catch (err) {
        if (isMounted) {
          setError(
            err instanceof Error ? err.message : "Seiten konnten nicht geladen werden"
          );
        }
      } finally {
        if (isMounted) {
          setIsLoading(false);
        }
      }
    }

    void loadPages();

    return () => {
      isMounted = false;
    };
  }, [crawlRunId]);

  if (isLoading) {
    return (
      <div className="rounded-lg border border-slate-800 bg-slate-900/60 p-6">
        <p className="text-sm text-slate-400">Seiten werden geladen …</p>
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

  const filteredPages = searchTerm
    ? pages.filter((page) =>
        page.url.toLowerCase().includes(searchTerm.toLowerCase())
      )
    : pages;

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between gap-4">
        <p className="text-sm text-slate-400">
          {filteredPages.length} von {pages.length} Seiten
        </p>
        <input
          type="text"
          placeholder="URL durchsuchen..."
          value={searchTerm}
          onChange={(e) => setSearchTerm(e.target.value)}
          className="rounded-lg border border-slate-700 bg-slate-900 px-3 py-2 text-sm text-slate-100 placeholder-slate-500 focus:border-slate-500 focus:outline-none"
        />
      </div>

      <div className="overflow-x-auto">
        <table className="w-full text-left text-sm">
          <thead className="border-b border-slate-800 bg-slate-900/60 text-xs uppercase tracking-wide text-slate-400">
            <tr>
              <th className="px-4 py-3">URL</th>
              <th className="px-4 py-3">Status</th>
              <th className="px-4 py-3">Title</th>
              <th className="px-4 py-3">H1</th>
              <th className="px-4 py-3">Canonical</th>
              <th className="px-4 py-3">Redirects</th>
              <th className="px-4 py-3">Issues</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-slate-800">
            {filteredPages.map((page) => (
              <tr
                key={page.id}
                className="bg-slate-900/40 transition hover:bg-slate-800/60"
              >
                <td className="px-4 py-3">
                  <a
                    href={page.url}
                    target="_blank"
                    rel="noreferrer"
                    className="break-all text-sky-300 underline decoration-slate-600 underline-offset-2 transition hover:text-sky-200 hover:decoration-sky-400"
                  >
                    {page.url}
                  </a>
                </td>
                <td className="px-4 py-3">
                  <span
                    className={
                      page.statusCode >= 200 && page.statusCode < 300
                        ? "text-emerald-300"
                        : page.statusCode >= 300 && page.statusCode < 400
                          ? "text-amber-300"
                          : "text-red-300"
                    }
                  >
                    {page.statusCode}
                  </span>
                </td>
                <td className="px-4 py-3 text-slate-300">
                  {page.title ? (
                    <span className="line-clamp-1">{page.title}</span>
                  ) : (
                    <span className="text-slate-500">—</span>
                  )}
                </td>
                <td className="px-4 py-3 text-slate-300">
                  {page.h1 ? (
                    <span className="line-clamp-1">{page.h1}</span>
                  ) : (
                    <span className="text-slate-500">—</span>
                  )}
                </td>
                <td className="px-4 py-3">
                  {page.canonicalUrl ? (
                    page.canonicalUrl === page.url ? (
                      <span className="text-emerald-300">Self</span>
                    ) : (
                      <span className="text-amber-300">Other</span>
                    )
                  ) : (
                    <span className="text-slate-500">—</span>
                  )}
                </td>
                <td className="px-4 py-3 text-slate-300">
                  {page.redirectCount > 0 ? (
                    <span className="text-amber-300">{page.redirectCount}</span>
                  ) : (
                    <span className="text-slate-500">0</span>
                  )}
                </td>
                <td className="px-4 py-3">
                  {page.issueCount > 0 ? (
                    <span className="text-red-300">{page.issueCount}</span>
                  ) : (
                    <span className="text-emerald-300">0</span>
                  )}
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      {filteredPages.length === 0 && (
        <div className="rounded-lg border border-slate-800 bg-slate-900/60 p-6 text-center">
          <p className="text-sm text-slate-400">
            Keine Seiten gefunden, die deinen Suchkriterien entsprechen.
          </p>
        </div>
      )}
    </div>
  );
}
