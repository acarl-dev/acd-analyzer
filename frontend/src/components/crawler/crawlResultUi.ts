export function getSeverityLabel(severity: "info" | "warning" | "error") {
  if (severity === "error") {
    return "Fehler";
  }

  if (severity === "warning") {
    return "Warnung";
  }

  return "Hinweis";
}

export function getSeverityBadgeClassName(severity: "info" | "warning" | "error") {
  if (severity === "error") {
    return "border-red-800 bg-red-950/70 text-red-200";
  }

  if (severity === "warning") {
    return "border-amber-800 bg-amber-950/70 text-amber-200";
  }

  return "border-sky-800 bg-sky-950/70 text-sky-200";
}

export function getIssueClassName(severity: "info" | "warning" | "error") {
  if (severity === "error") {
    return "rounded-lg border border-red-900/50 bg-red-950/25 px-3 py-2";
  }

  if (severity === "warning") {
    return "rounded-lg border border-amber-900/50 bg-amber-950/25 px-3 py-2";
  }

  return "rounded-lg border border-sky-900/50 bg-sky-950/25 px-3 py-2";
}

export function formatBytes(bytes: number | null) {
  if (bytes === null) {
    return "n/a";
  }

  if (bytes < 1024) {
    return `${bytes} B`;
  }

  return `${(bytes / 1024).toFixed(1)} KB`;
}

export function getTechnologyTypeLabel(type: string): string {
  if (type === "cms") {
    return "CMS";
  }

  if (type === "frontend") {
    return "Frontend";
  }

  if (type === "rendering") {
    return "Rendering";
  }

  return "Sonstige";
}

export function getTechnologyGroupOrder(type: string): number {
  if (type === "cms") {
    return 1;
  }

  if (type === "frontend") {
    return 2;
  }

  if (type === "rendering") {
    return 3;
  }

  return 99;
}