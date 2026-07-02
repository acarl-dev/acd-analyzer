import type { CrawlResultsResponse } from "@/types/crawl";
import { getHealthScoreLabel } from "@/lib/healthScore";

type CrawlResultOverviewProps = {
  results: CrawlResultsResponse;
};

export function CrawlResultOverview({ results }: CrawlResultOverviewProps) {
  return (
    <div className="grid gap-4 lg:grid-cols-2">
      <div className="rounded-lg border border-slate-800 bg-slate-900/60 p-3">
        <p className="text-xs font-medium uppercase tracking-wide text-slate-500">
          Health Score
        </p>
        <p className="mt-2 text-3xl font-semibold text-slate-100">
          {results.healthScore}/100
        </p>
        <p className="mt-1 text-sm text-slate-400">
          {getHealthScoreLabel(results.healthScore)}
        </p>
      </div>

      <div className="rounded-lg border border-slate-800 bg-slate-900/60 p-3">
        <p className="mb-3 text-xs font-medium uppercase tracking-wide text-slate-500">
          Crawl-Zusammenfassung
        </p>

        <dl className="grid gap-3 text-sm sm:grid-cols-2">
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
      </div>
    </div>
  );
}