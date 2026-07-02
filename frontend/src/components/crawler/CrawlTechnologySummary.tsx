import type { CrawlResultsResponse } from "@/types/crawl";
import {
  getTechnologyGroupOrder,
  getTechnologyTypeLabel,
} from "./crawlResultUi";

type CrawlTechnologySummaryProps = {
  technologies: CrawlResultsResponse["technologies"];
};

export function CrawlTechnologySummary({
  technologies,
}: CrawlTechnologySummaryProps) {
  const technologyGroups = technologies.reduce<Record<string, typeof technologies>>(
    (groups, technology) => {
      const type = technology.type;

      return {
        ...groups,
        [type]: [...(groups[type] ?? []), technology],
      };
    },
    {},
  );

  const sortedTechnologyGroups = Object.entries(technologyGroups).sort(
    ([firstType], [secondType]) =>
      getTechnologyGroupOrder(firstType) - getTechnologyGroupOrder(secondType),
  );

  return (
    <div className="rounded-lg border border-slate-800 bg-slate-900/60 p-3">
      <div className="mb-3">
        <p className="text-xs font-medium uppercase tracking-wide text-slate-500">
          Erkannte Technologien
        </p>
        <p className="mt-1 text-xs text-slate-500">
          Gruppiert nach Technologie-Kategorie mit Erkennungssicherheit und Hinweis
          zur Erkennung.
        </p>
      </div>

      {technologies.length === 0 ? (
        <p className="text-sm text-slate-400">
          Für diesen Crawl wurden keine Technologien erkannt.
        </p>
      ) : (
        <div className="space-y-4">
          {sortedTechnologyGroups.map(([type, groupedTechnologies]) => (
            <div key={type}>
              <p className="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-400">
                {getTechnologyTypeLabel(type)}
              </p>

              <div className="flex flex-wrap gap-2">
                {groupedTechnologies.map((technology) => (
                  <span
                    key={technology.id}
                    title={technology.evidence}
                    className="rounded-full border border-slate-700 bg-slate-950 px-2.5 py-1 text-xs font-medium text-slate-300"
                  >
                    {technology.name} · {Math.round(technology.confidence * 100)}%
                  </span>
                ))}
              </div>
            </div>
          ))}
        </div>
      )}
    </div>
  );
}