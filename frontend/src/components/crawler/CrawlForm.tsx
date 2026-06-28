"use client";

import { FormEvent, useState } from "react";
import { useCrawler } from "@/hooks/useCrawler";
import { CrawlResult } from "@/components/crawler/CrawlResult";

export function CrawlForm() {
  const [url, setUrl] = useState("");
  const { result, error, isLoading, crawl } = useCrawler();

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();

    const response = await crawl(url);

    if (response) {
      setUrl("");
    }
  }

  return (
    <section className="rounded-2xl border border-slate-800 bg-slate-900 p-6 shadow-xl">
      <h2 className="mb-4 text-xl font-semibold">Website crawlen</h2>

      <form onSubmit={handleSubmit} className="flex flex-col gap-4 sm:flex-row">
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
      </form>

      {error && (
        <div className="mt-6 rounded-xl border border-red-900 bg-red-950/60 p-4 text-red-200">
          {error}
        </div>
      )}

      {result && <CrawlResult crawlRun={result.data} />}
    </section>
  );
}