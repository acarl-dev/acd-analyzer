export interface DashboardTopIssue {
  code: string;
  severity: "error" | "warning" | "info";
  message: string;
  count: number;
}

export interface DashboardSummary {
  totalWebsites: number;
  totalCrawlRuns: number;
  websitesWithIssues: number;
  totalIssues: number;
  issuesBySeverity: {
    errors: number;
    warnings: number;
    infos: number;
  };
  topIssues: DashboardTopIssue[];
  topTechnologies: DashboardTopTechnology[];
}

export interface DashboardTopTechnology {
  type: string;
  name: string;
  confidence: number;
  count: number;
}