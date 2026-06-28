import { CrawlForm } from "@/components/crawler/CrawlForm";

export default function Home() {
  return (
    <main className="min-h-screen bg-slate-950 px-6 py-10 text-slate-100">
      <div className="mx-auto max-w-3xl">
        <section className="mb-10">
          <p className="mb-2 text-sm font-medium uppercase tracking-widest text-slate-400">
            Alan Carl Digital
          </p>

          <h1 className="text-4xl font-bold tracking-tight">
            ACD Analyzer
          </h1>

          <p className="mt-4 max-w-2xl text-slate-300">
            Internes Dashboard zum Starten und Prüfen von Website-Crawls.
          </p>
        </section>

        <CrawlForm />
      </div>
    </main>
  );
}