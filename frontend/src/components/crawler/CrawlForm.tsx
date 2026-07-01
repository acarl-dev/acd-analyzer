"use client";

import { FormEvent, useState } from "react";
import { useCrawler } from "@/hooks/useCrawler";
import type { CrawlRun } from "@/types/crawl";

interface CrawlFormProps {
  onCrawlCreated: (crawlRun: CrawlRun) => void;
}

export function CrawlForm({ onCrawlCreated }: CrawlFormProps) {
  const [url, setUrl] = useState("");
  const [maxPages, setMaxPages] = useState(10);
  const [maxDepth, setMaxDepth] = useState(1);
  const { error, isLoading, crawl } = useCrawler();

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();

    const response = await crawl({
      url,
      maxPages,
      maxDepth,
    });

    if (response) {
      onCrawlCreated(response.data);
      setUrl("");
      setMaxPages(10);
      setMaxDepth(1);
    }
  }

  return (
    <section className="rounded-2xl border border-slate-800 bg-slate-900 p-6 shadow-xl">
      <h2 className="mb-4 text-xl font-semibold">Website crawlen</h2>

      <form onSubmit={handleSubmit} className="flex flex-col gap-4">
        <div className="flex flex-col gap-4 sm:flex-row">
          <input
            type="url"
            value={url}
            onChange={(event) => setUrl(event.target.value)}
            placeholder="https://example.com"
            className="min-w-0 flex-1 rounded-xl border border-slate-700 bg-slate-950 px-4 py-3 text-slate-100 outline-none ring-0 placeholder:text-slate-500 focus:border-slate-500"
            required
          />

          <button
            type="submit"
            disabled={isLoading}
            className="rounded-xl bg-slate-100 px-5 py-3 font-semibold text-slate-950 transition hover:bg-white disabled:cursor-not-allowed disabled:opacity-60"
          >
            {isLoading ? "Crawl läuft..." : "Crawl starten"}
          </button>
        </div>

        <div className="grid gap-4 sm:grid-cols-2">
          <label className="block">
            <span className="text-sm font-medium text-slate-300">
              Maximale Seiten
            </span>
            <input
              type="number"
              min={1}
              max={25}
              value={maxPages}
              onChange={(event) =>
                setMaxPages(Math.min(25, Math.max(1, Number(event.target.value))))
              }
              className="mt-1 w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-3 text-slate-100 outline-none ring-0 focus:border-slate-500"
            />
          </label>
          <label className="block">
            <span className="text-sm font-medium text-slate-300">
              Maximale Tiefe
            </span>
            <input
              type="number"
              min={0}
              max={2}
              value={maxDepth}
              onChange={(event) =>
                setMaxDepth(Math.min(2, Math.max(0, Number(event.target.value))))
              }
              className="mt-1 w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-3 text-slate-100 outline-none ring-0 focus:border-slate-500"
            />
          </label>
        </div>

        <p className="text-xs text-slate-500">
          Der Crawl ist aktuell auf interne Links derselben Domain begrenzt.
          Höhere Werte können länger dauern.
        </p>
      </form>

      {error && (
        <div className="mt-6 rounded-xl border border-red-900 bg-red-950/60 p-4 text-red-200">
          {error}
        </div>
      )}
    </section>
  );
}