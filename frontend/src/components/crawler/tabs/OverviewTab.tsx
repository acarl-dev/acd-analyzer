"use client";

import { useEffect, useState } from "react";
import { getCrawlRunOverview } from "@/api/crawl";
import type { CrawlRunOverview } from "@/types/crawl";
import { getHealthScoreLabel } from "@/lib/healthScore";

interface OverviewTabProps {
  crawlRunId: number;
}

export function OverviewTab({ crawlRunId }: OverviewTabProps) {
  const [overview, setOverview] = useState<CrawlRunOverview | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    let isMounted = true;

    async function loadOverview() {
      setIsLoading(true);
      setError(null);

      try {
        const response = await getCrawlRunOverview(crawlRunId);

        if (isMounted) {
          setOverview(response.data);
        }
      } catch (err) {
        if (isMounted) {
          setError(
            err instanceof Error ? err.message : "Übersicht konnte nicht geladen werden"
          );
        }
      } finally {
        if (isMounted) {
          setIsLoading(false);
        }
      }
    }

    void loadOverview();

    return () => {
      isMounted = false;
    };
  }, [crawlRunId]);

  if (isLoading) {
    return (
      <div className="rounded-lg border border-slate-800 bg-slate-900/60 p-6">
        <p className="text-sm text-slate-400">Übersicht wird geladen …</p>
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

  if (!overview) {
    return null;
  }

  const metrics = overview.metrics;

  return (
    <div className="space-y-6">
      {/* Health Score */}
      <div className="rounded-lg border border-slate-800 bg-slate-900/60 p-6">
        <p className="text-xs font-medium uppercase tracking-wide text-slate-500">
          Health Score
        </p>
        <p className="mt-2 text-4xl font-semibold text-slate-100">
          {overview.healthScore}/100
        </p>
        <p className="mt-1 text-sm text-slate-400">
          {getHealthScoreLabel(overview.healthScore)}
        </p>
      </div>

      {/* Key Metrics Grid */}
      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <div className="rounded-lg border border-slate-800 bg-slate-900/60 p-4">
          <p className="text-xs font-medium uppercase tracking-wide text-slate-500">
            Seiten gecrawlt
          </p>
          <p className="mt-2 text-3xl font-semibold text-slate-100">
            {metrics.crawledPages}
          </p>
        </div>

        <div className="rounded-lg border border-slate-800 bg-slate-900/60 p-4">
          <p className="text-xs font-medium uppercase tracking-wide text-slate-500">
            Probleme
          </p>
          <p className="mt-2 text-3xl font-semibold text-slate-100">
            {metrics.issues}
          </p>
          <p className="mt-1 text-xs text-slate-500">
            Fehler: {metrics.errorIssues} · Warnungen: {metrics.warningIssues} · Hinweise: {metrics.infoIssues}
          </p>
        </div>

        <div className="rounded-lg border border-slate-800 bg-slate-900/60 p-4">
          <p className="text-xs font-medium uppercase tracking-wide text-slate-500">
            Interne Links
          </p>
          <p className="mt-2 text-3xl font-semibold text-slate-100">
            {metrics.internalLinks}
          </p>
        </div>

        <div className="rounded-lg border border-slate-800 bg-slate-900/60 p-4">
          <p className="text-xs font-medium uppercase tracking-wide text-slate-500">
            Externe Links
          </p>
          <p className="mt-2 text-3xl font-semibold text-slate-100">
            {metrics.externalLinks}
          </p>
        </div>

        <div className="rounded-lg border border-slate-800 bg-slate-900/60 p-4">
          <p className="text-xs font-medium uppercase tracking-wide text-slate-500">
            Seiten mit Redirects
          </p>
          <p className="mt-2 text-3xl font-semibold text-slate-100">
            {metrics.redirectedPages}
          </p>
        </div>

        <div className="rounded-lg border border-slate-800 bg-slate-900/60 p-4">
          <p className="text-xs font-medium uppercase tracking-wide text-slate-500">
            Canonical-Probleme
          </p>
          <p className="mt-2 text-3xl font-semibold text-slate-100">
            {metrics.canonicalIssues}
          </p>
        </div>

        <div className="rounded-lg border border-slate-800 bg-slate-900/60 p-4">
          <p className="text-xs font-medium uppercase tracking-wide text-slate-500">
            robots.txt
          </p>
          <p className="mt-2 text-3xl font-semibold text-slate-100">
            {metrics.robotsTxtExists ? "✓" : "✗"}
          </p>
          <p className="mt-1 text-xs text-slate-500">
            {metrics.robotsTxtExists ? "Vorhanden" : "Nicht gefunden"}
          </p>
        </div>

        <div className="rounded-lg border border-slate-800 bg-slate-900/60 p-4">
          <p className="text-xs font-medium uppercase tracking-wide text-slate-500">
            Sitemaps
          </p>
          <p className="mt-2 text-3xl font-semibold text-slate-100">
            {metrics.sitemaps}
          </p>
          <p className="mt-1 text-xs text-slate-500">
            {metrics.sitemapUrls} URLs
          </p>
        </div>

        <div className="rounded-lg border border-slate-800 bg-slate-900/60 p-4">
          <p className="text-xs font-medium uppercase tracking-wide text-slate-500">
            Technologien
          </p>
          <p className="mt-2 text-3xl font-semibold text-slate-100">
            {metrics.technologies}
          </p>
        </div>

        <div className="rounded-lg border border-slate-800 bg-slate-900/60 p-4">
          <p className="text-xs font-medium uppercase tracking-wide text-slate-500">
            Crawl-Fehler
          </p>
          <p className="mt-2 text-3xl font-semibold text-red-300">
            {metrics.crawlErrors}
          </p>
        </div>
      </div>
    </div>
  );
}
