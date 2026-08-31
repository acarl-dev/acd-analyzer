"use client";

import { useEffect, useState } from "react";
import { getCrawlRunRobotsTxt } from "@/api/crawl";
import type { RobotsTxtData } from "@/types/crawl";

interface RobotsTxtTabProps {
  crawlRunId: number;
}

export function RobotsTxtTab({ crawlRunId }: RobotsTxtTabProps) {
  const [robotsTxt, setRobotsTxt] = useState<RobotsTxtData | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [showRawContent, setShowRawContent] = useState(false);

  useEffect(() => {
    let isMounted = true;

    async function loadRobotsTxt() {
      setIsLoading(true);
      setError(null);

      try {
        const response = await getCrawlRunRobotsTxt(crawlRunId);

        if (isMounted) {
          setRobotsTxt(response.data);
        }
      } catch (err) {
        if (isMounted) {
          setError(
            err instanceof Error
              ? err.message
              : "robots.txt konnte nicht geladen werden"
          );
        }
      } finally {
        if (isMounted) {
          setIsLoading(false);
        }
      }
    }

    void loadRobotsTxt();

    return () => {
      isMounted = false;
    };
  }, [crawlRunId]);

  if (isLoading) {
    return (
      <div className="rounded-lg border border-slate-800 bg-slate-900/60 p-6">
        <p className="text-sm text-slate-400">robots.txt wird geladen …</p>
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

  if (!robotsTxt) {
    return (
      <div className="rounded-lg border border-amber-900 bg-amber-950/40 p-6">
        <p className="text-sm text-amber-200">
          Keine robots.txt-Daten für diesen Crawl vorhanden.
        </p>
      </div>
    );
  }

  return (
    <div className="space-y-4">
      {/* Status */}
      <div className="rounded-lg border border-slate-800 bg-slate-900/60 p-4">
        <h3 className="mb-3 text-sm font-medium text-slate-300">Status</h3>
        <dl className="grid gap-3 text-sm sm:grid-cols-2">
          <div>
            <dt className="text-slate-400">URL</dt>
            <dd className="mt-1 break-all font-medium text-slate-100">
              {robotsTxt.url}
            </dd>
          </div>
          <div>
            <dt className="text-slate-400">Status Code</dt>
            <dd className="mt-1 font-medium text-slate-100">
              {robotsTxt.statusCode}
            </dd>
          </div>
          <div>
            <dt className="text-slate-400">Existiert</dt>
            <dd className="mt-1 font-medium">
              {robotsTxt.exists ? (
                <span className="text-emerald-300">✓ Ja</span>
              ) : (
                <span className="text-red-300">✗ Nein</span>
              )}
            </dd>
          </div>
        </dl>
      </div>

      {robotsTxt.exists && (
        <>
          {/* Sitemaps */}
          <div className="rounded-lg border border-slate-800 bg-slate-900/60 p-4">
            <h3 className="mb-3 text-sm font-medium text-slate-300">
              Sitemaps ({robotsTxt.sitemaps.length})
            </h3>
            {robotsTxt.sitemaps.length > 0 ? (
              <ul className="space-y-2">
                {robotsTxt.sitemaps.map((sitemap, index) => (
                  <li key={index}>
                    <a
                      href={sitemap}
                      target="_blank"
                      rel="noreferrer"
                      className="break-all text-sm text-sky-300 underline decoration-slate-600 underline-offset-2 hover:text-sky-200 hover:decoration-sky-400"
                    >
                      {sitemap}
                    </a>
                  </li>
                ))}
              </ul>
            ) : (
              <p className="text-sm text-slate-500">
                Keine Sitemaps in robots.txt definiert.
              </p>
            )}
          </div>

          {/* Rules */}
          <div className="rounded-lg border border-slate-800 bg-slate-900/60 p-4">
            <h3 className="mb-3 text-sm font-medium text-slate-300">
              Rules ({robotsTxt.rules.length})
            </h3>
            {robotsTxt.rules.length > 0 ? (
              <div className="space-y-3">
                {robotsTxt.rules.map((rule: any, index: number) => (
                  <div
                    key={index}
                    className="rounded-lg border border-slate-800 bg-slate-950/50 p-3"
                  >
                    <p className="text-xs font-medium text-slate-400">
                      User-agent: <span className="text-slate-200">{rule.user_agent}</span>
                    </p>
                    {rule.disallow && rule.disallow.length > 0 && (
                      <div className="mt-2">
                        <p className="text-xs text-slate-500">Disallow:</p>
                        <ul className="mt-1 space-y-0.5">
                          {rule.disallow.map((path: string, i: number) => (
                            <li key={i} className="text-xs text-slate-300">
                              • {path}
                            </li>
                          ))}
                        </ul>
                      </div>
                    )}
                    {rule.allow && rule.allow.length > 0 && (
                      <div className="mt-2">
                        <p className="text-xs text-slate-500">Allow:</p>
                        <ul className="mt-1 space-y-0.5">
                          {rule.allow.map((path: string, i: number) => (
                            <li key={i} className="text-xs text-slate-300">
                              • {path}
                            </li>
                          ))}
                        </ul>
                      </div>
                    )}
                  </div>
                ))}
              </div>
            ) : (
              <p className="text-sm text-slate-500">Keine Regeln definiert.</p>
            )}
          </div>

          {/* Raw Content */}
          {robotsTxt.content && (
            <div className="rounded-lg border border-slate-800 bg-slate-900/60 p-4">
              <button
                type="button"
                onClick={() => setShowRawContent(!showRawContent)}
                className="mb-3 text-sm font-medium text-slate-300 hover:text-slate-100"
              >
                {showRawContent ? "▼" : "▶"} Raw Content
              </button>
              {showRawContent && (
                <pre className="overflow-x-auto rounded-lg bg-slate-950/80 p-3 text-xs text-slate-300">
                  {robotsTxt.content}
                </pre>
              )}
            </div>
          )}
        </>
      )}
    </div>
  );
}
