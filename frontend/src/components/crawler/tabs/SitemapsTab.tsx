"use client";

import { useEffect, useState } from "react";
import { getCrawlRunSitemaps, getCrawlRunSitemap } from "@/api/crawl";
import type { SitemapItem, SitemapUrl } from "@/types/crawl";

interface SitemapsTabProps {
  crawlRunId: number;
}

export function SitemapsTab({ crawlRunId }: SitemapsTabProps) {
  const [sitemaps, setSitemaps] = useState<SitemapItem[]>([]);
  const [summary, setSummary] = useState<{ total: number; totalUrls: number } | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [selectedSitemap, setSelectedSitemap] = useState<number | null>(null);
  const [sitemapUrls, setSitemapUrls] = useState<SitemapUrl[]>([]);
  const [loadingUrls, setLoadingUrls] = useState(false);
  const [pagination, setPagination] = useState<any>(null);
  const [currentPage, setCurrentPage] = useState(1);

  useEffect(() => {
    let isMounted = true;

    async function loadSitemaps() {
      setIsLoading(true);
      setError(null);

      try {
        const response = await getCrawlRunSitemaps(crawlRunId);

        if (isMounted) {
          setSitemaps(response.data);
          setSummary(response.summary);
        }
      } catch (err) {
        if (isMounted) {
          setError(
            err instanceof Error
              ? err.message
              : "Sitemaps konnten nicht geladen werden"
          );
        }
      } finally {
        if (isMounted) {
          setIsLoading(false);
        }
      }
    }

    void loadSitemaps();

    return () => {
      isMounted = false;
    };
  }, [crawlRunId]);

  useEffect(() => {
    if (selectedSitemap === null) return;

    let isMounted = true;

    async function loadSitemapUrls() {
      setLoadingUrls(true);

      try {
        const response = await getCrawlRunSitemap(crawlRunId, selectedSitemap, currentPage);

        if (isMounted) {
          setSitemapUrls(response.data);
          setPagination(response.pagination);
        }
      } catch (err) {
        // Handle error silently
      } finally {
        if (isMounted) {
          setLoadingUrls(false);
        }
      }
    }

    void loadSitemapUrls();

    return () => {
      isMounted = false;
    };
  }, [crawlRunId, selectedSitemap, currentPage]);

  function handleSelectSitemap(sitemapId: number) {
    setSelectedSitemap(sitemapId === selectedSitemap ? null : sitemapId);
    setCurrentPage(1);
    setSitemapUrls([]);
  }

  function handlePageChange(page: number) {
    setCurrentPage(page);
  }

  if (isLoading) {
    return (
      <div className="rounded-lg border border-slate-800 bg-slate-900/60 p-6">
        <p className="text-sm text-slate-400">Sitemaps werden geladen …</p>
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

  const renderSitemapTree = (items: SitemapItem[], level: number = 0) => {
    return items.map((sitemap) => (
      <div key={sitemap.id} style={{ marginLeft: `${level * 1.5}rem` }}>
        <button
          type="button"
          onClick={() => handleSelectSitemap(sitemap.id)}
          className={
            selectedSitemap === sitemap.id
              ? "w-full rounded-lg border border-slate-600 bg-slate-800 p-3 text-left transition"
              : "w-full rounded-lg border border-slate-800 bg-slate-900/40 p-3 text-left transition hover:border-slate-700 hover:bg-slate-800/60"
          }
        >
          <div className="flex items-center justify-between gap-3">
            <div className="min-w-0">
              <p className="break-all text-sm font-medium text-slate-100">
                {sitemap.url}
              </p>
              <p className="mt-1 text-xs text-slate-500">
                Type: {sitemap.type} · URLs: {sitemap.urlCount} · Status: {sitemap.statusCode}
              </p>
            </div>
            {sitemap.urlCount > 0 && (
              <span className="shrink-0 rounded-full border border-slate-700 bg-slate-900 px-2 py-0.5 text-xs font-medium text-slate-300">
                {sitemap.urlCount} URLs
              </span>
            )}
          </div>
        </button>
        {sitemap.children && sitemap.children.length > 0 && (
          <div className="mt-2">
            {renderSitemapTree(sitemap.children, level + 1)}
          </div>
        )}
      </div>
    ));
  };

  return (
    <div className="space-y-4">
      {/* Summary */}
      {summary && (
        <div className="grid gap-4 sm:grid-cols-2">
          <div className="rounded-lg border border-slate-800 bg-slate-900/60 p-4">
            <p className="text-xs font-medium uppercase tracking-wide text-slate-500">
              Sitemaps gefunden
            </p>
            <p className="mt-2 text-3xl font-semibold text-slate-100">
              {summary.total}
            </p>
          </div>
          <div className="rounded-lg border border-slate-800 bg-slate-900/60 p-4">
            <p className="text-xs font-medium uppercase tracking-wide text-slate-500">
              URLs gesamt
            </p>
            <p className="mt-2 text-3xl font-semibold text-slate-100">
              {summary.totalUrls}
            </p>
          </div>
        </div>
      )}

      {/* Sitemap Tree */}
      <div className="space-y-2">
        {sitemaps.length > 0 ? (
          renderSitemapTree(sitemaps)
        ) : (
          <div className="rounded-lg border border-slate-800 bg-slate-900/60 p-6 text-center">
            <p className="text-sm text-slate-400">Keine Sitemaps gefunden.</p>
          </div>
        )}
      </div>

      {/* Sitemap URLs */}
      {selectedSitemap && (
        <div className="rounded-lg border border-slate-800 bg-slate-900/60 p-4">
          <h3 className="mb-3 text-sm font-medium text-slate-300">
            Sitemap URLs
            {pagination && ` (Seite ${pagination.currentPage} von ${pagination.lastPage})`}
          </h3>

          {loadingUrls ? (
            <p className="text-sm text-slate-400">URLs werden geladen …</p>
          ) : (
            <>
              <div className="overflow-x-auto">
                <table className="w-full text-left text-sm">
                  <thead className="border-b border-slate-800 text-xs uppercase tracking-wide text-slate-400">
                    <tr>
                      <th className="px-4 py-3">URL</th>
                      <th className="px-4 py-3">Normalized URL</th>
                      <th className="px-4 py-3">Lastmod</th>
                      <th className="px-4 py-3">Priority</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-slate-800">
                    {sitemapUrls.map((url) => (
                      <tr
                        key={url.id}
                        className="bg-slate-900/40 transition hover:bg-slate-800/60"
                      >
                        <td className="px-4 py-3">
                          <code className="break-all text-xs text-slate-300">
                            {url.url}
                          </code>
                        </td>
                        <td className="px-4 py-3">
                          <a
                            href={url.normalizedUrl}
                            target="_blank"
                            rel="noreferrer"
                            className="break-all text-xs text-sky-300 underline decoration-slate-600 underline-offset-2 hover:text-sky-200 hover:decoration-sky-400"
                          >
                            {url.normalizedUrl}
                          </a>
                        </td>
                        <td className="px-4 py-3 text-xs text-slate-300">
                          {url.lastmod
                            ? new Date(url.lastmod).toLocaleDateString()
                            : "—"}
                        </td>
                        <td className="px-4 py-3 text-xs text-slate-300">
                          {url.priority ?? "—"}
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>

              {/* Pagination */}
              {pagination && pagination.lastPage > 1 && (
                <div className="mt-4 flex items-center justify-center gap-2">
                  <button
                    type="button"
                    onClick={() => handlePageChange(currentPage - 1)}
                    disabled={currentPage === 1}
                    className="rounded-lg border border-slate-700 px-3 py-1 text-xs font-medium text-slate-300 transition hover:border-slate-500 hover:text-slate-100 disabled:opacity-50"
                  >
                    ← Zurück
                  </button>
                  <span className="text-xs text-slate-400">
                    Seite {currentPage} von {pagination.lastPage}
                  </span>
                  <button
                    type="button"
                    onClick={() => handlePageChange(currentPage + 1)}
                    disabled={currentPage === pagination.lastPage}
                    className="rounded-lg border border-slate-700 px-3 py-1 text-xs font-medium text-slate-300 transition hover:border-slate-500 hover:text-slate-100 disabled:opacity-50"
                  >
                    Weiter →
                  </button>
                </div>
              )}
            </>
          )}
        </div>
      )}
    </div>
  );
}
