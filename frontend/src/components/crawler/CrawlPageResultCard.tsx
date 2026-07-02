import type { CrawlResultsResponse } from "@/types/crawl";
import {
  formatBytes,
  getIssueClassName,
  getSeverityBadgeClassName,
  getSeverityLabel,
} from "./crawlResultUi";

type SeverityFilter = "all" | "error" | "warning" | "info";

type PageResult = CrawlResultsResponse["pages"][number];

type CrawlPageResultCardProps = {
  page: PageResult;
  severityFilter: SeverityFilter;
  isExpanded: boolean;
  onToggleDetails: () => void;
};

export function CrawlPageResultCard({
  page,
  severityFilter,
  isExpanded,
  onToggleDetails,
}: CrawlPageResultCardProps) {
    const errorCount = page.issues.filter(
    (issue) => issue.severity === "error",
  ).length;
  const warningCount = page.issues.filter(
    (issue) => issue.severity === "warning",
  ).length;
  const infoCount = page.issues.filter(
    (issue) => issue.severity === "info",
  ).length;
  const totalIssues = page.issues.length;
  const primaryIssue = page.issues[0] ?? null;

  const visibleIssues =
    severityFilter === "all"
      ? page.issues
      : page.issues.filter((issue) => issue.severity === severityFilter);

  return (
    <div className="rounded-xl border border-slate-800 bg-slate-900/70 p-4">
      <div className="flex flex-col gap-2 border-b border-slate-800 pb-4 sm:flex-row sm:items-start sm:justify-between">
        <div className="min-w-0">
          <div className="flex flex-wrap items-center gap-2">
            <a
              href={page.url}
              target="_blank"
              rel="noreferrer"
              className="break-all text-sm font-semibold text-slate-100 underline decoration-slate-600 underline-offset-4 transition hover:text-sky-200 hover:decoration-sky-400"
            >
              {page.url}
            </a>

            <span className="rounded-full border border-slate-700 bg-slate-900 px-2 py-0.5 text-xs font-medium text-slate-400">
              {page.depth === 0 ? "Startseite" : `Tiefe ${page.depth}`}
            </span>
          </div>

          <p className="mt-1 text-xs text-slate-500">
            {page.hasCrawlError
              ? "Dieser Crawl konnte für die Seite nicht abgeschlossen werden."
              : "Erkannte Seitendaten und Analysehinweise."}
          </p>

          <div className="mt-2 flex flex-wrap gap-2">
            {totalIssues === 0 ? (
              <span className="rounded-full border border-emerald-900/60 bg-emerald-950/30 px-2 py-0.5 text-xs font-medium text-emerald-300">
                Keine Probleme
              </span>
            ) : (
              <>
                {errorCount > 0 && (
                  <span className="rounded-full border border-red-900/60 bg-red-950/40 px-2 py-0.5 text-xs font-medium text-red-200">
                    {errorCount} Fehler
                  </span>
                )}

                {warningCount > 0 && (
                  <span className="rounded-full border border-amber-900/60 bg-amber-950/40 px-2 py-0.5 text-xs font-medium text-amber-200">
                    {warningCount} Warnungen
                  </span>
                )}

                {infoCount > 0 && (
                  <span className="rounded-full border border-sky-900/60 bg-sky-950/40 px-2 py-0.5 text-xs font-medium text-sky-200">
                    {infoCount} Hinweise
                  </span>
                )}
              </>
            )}
          </div>

          {primaryIssue && (
            <p className="mt-2 text-xs leading-relaxed text-slate-400">
              Wichtigstes Problem:{" "}
              <span className="text-slate-200">{primaryIssue.message}</span>
            </p>
          )}
        </div>

        <div className="flex flex-col items-start gap-2 sm:items-end">
          <span
            className={
              page.hasCrawlError
                ? "inline-flex w-fit rounded-full border border-red-900/60 bg-red-950/40 px-2.5 py-1 text-xs font-medium text-red-200"
                : "inline-flex w-fit rounded-full border border-emerald-900/60 bg-emerald-950/40 px-2.5 py-1 text-xs font-medium text-emerald-200"
            }
          >
            {page.hasCrawlError
              ? "Crawl fehlgeschlagen"
              : `HTTP ${page.httpStatus ?? "n/a"}`}
          </span>

          <button
            type="button"
            onClick={onToggleDetails}
            className="rounded-full border border-slate-700 px-3 py-1 text-xs font-medium text-slate-300 transition hover:border-slate-500 hover:text-slate-100"
          >
            {isExpanded ? "Details ausblenden" : "Details anzeigen"}
          </button>
        </div>
      </div>

      {isExpanded && (
        <>
          <div className="mt-4 grid gap-3 lg:grid-cols-3">
            <div className="rounded-lg border border-slate-800 bg-slate-950/50 p-3">
              <p className="text-xs font-medium uppercase tracking-wide text-slate-500">
                Title
              </p>
              <p className="mt-2 text-sm font-medium leading-relaxed text-slate-100">
                {page.title ?? "Fehlt"}
              </p>
              <p className="mt-2 text-xs text-slate-500">
                Länge: {page.titleLength ?? 0} Zeichen
              </p>
            </div>

            <div className="rounded-lg border border-slate-800 bg-slate-950/50 p-3">
              <p className="text-xs font-medium uppercase tracking-wide text-slate-500">
                H1
              </p>
              <p className="mt-2 text-sm font-medium leading-relaxed text-slate-100">
                {page.h1 ?? "Fehlt"}
              </p>
              <p className="mt-2 text-xs text-slate-500">
                Anzahl: {page.h1Count}
              </p>
            </div>

            <div className="rounded-lg border border-slate-800 bg-slate-950/50 p-3">
              <p className="text-xs font-medium uppercase tracking-wide text-slate-500">
                Meta Description
              </p>
              <p className="mt-2 text-sm font-medium leading-relaxed text-slate-100">
                {page.metaDescription ?? "Fehlt"}
              </p>
              <p className="mt-2 text-xs text-slate-500">
                Länge: {page.metaDescriptionLength ?? 0} Zeichen
              </p>
            </div>
          </div>

          {!page.hasCrawlError && (
            <div className="mt-4 grid gap-2 sm:grid-cols-2 lg:grid-cols-5">
              <div className="rounded-lg bg-slate-950/50 p-3">
                <p className="text-xs text-slate-500">Bild-Elemente</p>
                <p className="mt-1 text-lg font-semibold text-slate-100">
                  {page.imageCount}
                </p>
              </div>

              <div className="rounded-lg bg-slate-950/50 p-3">
                <p className="text-xs text-slate-500">Ohne Alt-Text</p>
                <p
                  className={
                    page.imagesWithoutAlt > 0
                      ? "mt-1 text-lg font-semibold text-amber-200"
                      : "mt-1 text-lg font-semibold text-slate-100"
                  }
                >
                  {page.imagesWithoutAlt}
                </p>
              </div>

              <div className="rounded-lg bg-slate-950/50 p-3">
                <p className="text-xs text-slate-500">Interne Links</p>
                <p className="mt-1 text-lg font-semibold text-slate-100">
                  {page.internalLinksCount}
                </p>
              </div>

              <div className="rounded-lg bg-slate-950/50 p-3">
                <p className="text-xs text-slate-500">Externe Links</p>
                <p className="mt-1 text-lg font-semibold text-slate-100">
                  {page.externalLinksCount}
                </p>
              </div>

              <div className="rounded-lg bg-slate-950/50 p-3">
                <p className="text-xs text-slate-500">HTML-Größe</p>
                <p className="mt-1 text-lg font-semibold text-slate-100">
                  {formatBytes(page.htmlSizeBytes)}
                </p>
              </div>
            </div>
          )}

          {visibleIssues.length > 0 ? (
            <div className="mt-4 rounded-lg border border-slate-800 bg-slate-950/40 p-3">
              <div className="mb-2 flex items-center justify-between gap-3">
                <p className="text-xs font-medium uppercase tracking-wide text-slate-500">
                  Gefundene Probleme
                </p>

                <span className="text-xs text-slate-500">
                  {visibleIssues.length} angezeigt
                </span>
              </div>

              <ul className="space-y-2 text-xs">
                {visibleIssues.map((issue) => (
                  <li
                    key={`${page.id ?? page.url}-${issue.code}`}
                    className={getIssueClassName(issue.severity)}
                  >
                    <div className="flex flex-col gap-2 sm:flex-row sm:items-start">
                      <span
                        className={`w-fit rounded-full border px-2 py-0.5 font-medium ${getSeverityBadgeClassName(
                          issue.severity,
                        )}`}
                      >
                        {getSeverityLabel(issue.severity)}
                      </span>

                      <span className="font-medium leading-relaxed text-slate-100">
                        {issue.message}
                      </span>
                    </div>
                  </li>
                ))}
              </ul>
            </div>
          ) : page.issues.length > 0 ? (
            <p className="mt-4 rounded-lg border border-slate-800 bg-slate-950/40 p-3 text-xs text-slate-400">
              Für diesen Filter gibt es auf dieser Seite keine passenden Issues.
            </p>
          ) : (
            <p className="mt-4 rounded-lg border border-emerald-900/50 bg-emerald-950/30 p-3 text-xs text-emerald-300">
              Keine Probleme erkannt.
            </p>
          )}
        </>
      )}
    </div>
  );
}