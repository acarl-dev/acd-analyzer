"use client";

import { useState } from "react";
import type { CrawlRun } from "@/types/crawl";
import { OverviewTab } from "./tabs/OverviewTab";
import { PagesTab } from "./tabs/PagesTab";
import { IssuesTab } from "./tabs/IssuesTab";
import { LinksTab } from "./tabs/LinksTab";
import { RedirectsTab } from "./tabs/RedirectsTab";
import { RobotsTxtTab } from "./tabs/RobotsTxtTab";
import { SitemapsTab } from "./tabs/SitemapsTab";
import { CanonicalsTab } from "./tabs/CanonicalsTab";
import ErrorsTab from "./tabs/ErrorsTab";
import { CrawlTechnologySummary } from "./CrawlTechnologySummary";
import { getCrawlResults } from "@/api/crawl";
import { useEffect } from "react";
import type { DetectedTechnology } from "@/types/crawl";

interface CrawlResultTabsProps {
  crawlRun: CrawlRun;
}

type Tab =
  | "overview"
  | "pages"
  | "issues"
  | "errors"
  | "links"
  | "redirects"
  | "robots-txt"
  | "sitemaps"
  | "canonicals"
  | "technologies";

export function CrawlResultTabs({ crawlRun }: CrawlResultTabsProps) {
  const [activeTab, setActiveTab] = useState<Tab>("overview");
  const [technologies, setTechnologies] = useState<DetectedTechnology[]>([]);
  const [loadingTech, setLoadingTech] = useState(false);

  useEffect(() => {
    // Load technologies when the technologies tab is selected
    if (activeTab === "technologies" && technologies.length === 0) {
      setLoadingTech(true);
      getCrawlResults(crawlRun.id)
        .then((results) => {
          setTechnologies(results.technologies);
        })
        .catch(() => {
          // Handle error silently
        })
        .finally(() => {
          setLoadingTech(false);
        });
    }
  }, [activeTab, crawlRun.id, technologies.length]);

  const tabs: Array<{ id: Tab; label: string }> = [
    { id: "overview", label: "Overview" },
    { id: "pages", label: "Pages" },
    { id: "issues", label: "Issues" },
    { id: "errors", label: "Errors" },
    { id: "links", label: "Links" },
    { id: "redirects", label: "Redirects" },
    { id: "robots-txt", label: "robots.txt" },
    { id: "sitemaps", label: "Sitemaps" },
    { id: "canonicals", label: "Canonicals" },
    { id: "technologies", label: "Technologies" },
  ];

  return (
    <div className="rounded-2xl border border-slate-800 bg-slate-900 p-6 shadow-xl">
      <div className="mb-4">
        <h2 className="text-xl font-semibold">Analyseergebnis</h2>
        <p className="mt-1 text-sm text-slate-400">
          Crawl ID {crawlRun.id} · {crawlRun.pages_crawled} Seiten
        </p>
      </div>

      {/* Tab Navigation */}
      <div className="mb-6 flex flex-wrap gap-2 border-b border-slate-800 pb-4">
        {tabs.map((tab) => (
          <button
            key={tab.id}
            type="button"
            onClick={() => setActiveTab(tab.id)}
            className={
              activeTab === tab.id
                ? "rounded-lg bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-950 transition"
                : "rounded-lg border border-slate-700 px-4 py-2 text-sm font-medium text-slate-300 transition hover:border-slate-500 hover:text-slate-100"
            }
          >
            {tab.label}
          </button>
        ))}
      </div>

      {/* Tab Content */}
      <div>
        {activeTab === "overview" && <OverviewTab crawlRunId={crawlRun.id} />}
        {activeTab === "pages" && <PagesTab crawlRunId={crawlRun.id} />}
        {activeTab === "errors" && <ErrorsTab crawlRunId={crawlRun.id} />}
        {activeTab === "issues" && <IssuesTab crawlRunId={crawlRun.id} />}
        {activeTab === "links" && <LinksTab crawlRunId={crawlRun.id} />}
        {activeTab === "redirects" && <RedirectsTab crawlRunId={crawlRun.id} />}
        {activeTab === "robots-txt" && <RobotsTxtTab crawlRunId={crawlRun.id} />}
        {activeTab === "sitemaps" && <SitemapsTab crawlRunId={crawlRun.id} />}
        {activeTab === "canonicals" && <CanonicalsTab crawlRunId={crawlRun.id} />}
        {activeTab === "technologies" && (
          <div>
            {loadingTech ? (
              <div className="rounded-lg border border-slate-800 bg-slate-900/60 p-6">
                <p className="text-sm text-slate-400">Technologien werden geladen …</p>
              </div>
            ) : (
              <CrawlTechnologySummary technologies={technologies} />
            )}
          </div>
        )}
      </div>
    </div>
  );
}
