import {
  getSeverityBadgeClassName,
  getSeverityLabel,
} from "./crawlResultUi";

type IssueSummaryItem = {
  key: string;
  message: string;
  severity: "info" | "warning" | "error";
  count: number;
};

type CrawlIssueSummaryProps = {
  items: IssueSummaryItem[];
};

export function CrawlIssueSummary({ items }: CrawlIssueSummaryProps) {
  return (
    <div className="rounded-lg border border-slate-800 bg-slate-900/60 p-3">
      <div className="mb-3">
        <p className="text-xs font-medium uppercase tracking-wide text-slate-500">
          Häufigste Probleme
        </p>
        <p className="mt-1 text-xs text-slate-500">
          Gruppiert nach Problemtext über alle analysierten Seiten dieses Crawls.
        </p>
      </div>

      {items.length === 0 ? (
        <p className="text-sm text-emerald-300">
          Für diesen Crawl wurden keine Probleme erkannt.
        </p>
      ) : (
        <ul className="space-y-2 text-xs">
          {items.map((item) => (
            <li
              key={item.key}
              className="flex flex-col gap-2 rounded-lg border border-slate-800 bg-slate-950/50 px-3 py-2 sm:flex-row sm:items-start sm:justify-between"
            >
              <div className="flex min-w-0 flex-col gap-2 sm:flex-row sm:items-start">
                <span
                  className={`w-fit rounded-full border px-2 py-0.5 font-medium ${getSeverityBadgeClassName(
                    item.severity,
                  )}`}
                >
                  {getSeverityLabel(item.severity)}
                </span>

                <span className="leading-relaxed text-slate-100">
                  {item.message}
                </span>
              </div>

              <span className="w-fit rounded-full border border-slate-700 bg-slate-900 px-2 py-0.5 font-semibold text-slate-200">
                {item.count}×
              </span>
            </li>
          ))}
        </ul>
      )}
    </div>
  );
}