import React, { useState, useEffect } from "react";
import type { DetectedTechnology, TechnologyCategory, TechnologyConfidence } from "@/types/crawl";

interface TechnologiesTabProps {
  crawlRunId: number;
}

const categoryLabels: Record<TechnologyCategory, string> = {
  cms: "CMS",
  website_builder: "Website Builder",
  ecommerce: "E-Commerce",
  frontend: "Frontend Framework",
  js_library: "JavaScript Library",
  css_ui: "CSS/UI Framework",
  analytics: "Analytics",
  consent: "Consent Management",
  marketing: "Marketing",
  video_maps: "Video & Maps",
  captcha: "CAPTCHA",
  infrastructure: "Infrastructure",
  fonts: "Fonts",
};

const confidenceColors: Record<TechnologyConfidence, string> = {
  high: "bg-green-100 text-green-800",
  medium: "bg-yellow-100 text-yellow-800",
  low: "bg-gray-100 text-gray-800",
};

const confidenceLabels: Record<TechnologyConfidence, string> = {
  high: "High",
  medium: "Medium",
  low: "Low",
};

export default function TechnologiesTab({ crawlRunId }: TechnologiesTabProps) {
  const [technologies, setTechnologies] = useState<DetectedTechnology[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  
  const [selectedCategory, setSelectedCategory] = useState<string>("all");
  const [selectedConfidence, setSelectedConfidence] = useState<string>("all");
  const [expandedIds, setExpandedIds] = useState<Set<number>>(new Set());

  useEffect(() => {
    fetchTechnologies();
  }, [crawlRunId]);

  const fetchTechnologies = async () => {
    try {
      setLoading(true);
      setError(null);
      const response = await fetch(
        `http://localhost:8000/api/crawl-runs/${crawlRunId}/technologies`
      );
      if (!response.ok) {
        throw new Error("Failed to load technologies");
      }
      const data = await response.json();
      setTechnologies(data.data || []);
    } catch (err) {
      setError(err instanceof Error ? err.message : "Unknown error");
    } finally {
      setLoading(false);
    }
  };

  const toggleExpanded = (id: number) => {
    const newExpanded = new Set(expandedIds);
    if (newExpanded.has(id)) {
      newExpanded.delete(id);
    } else {
      newExpanded.add(id);
    }
    setExpandedIds(newExpanded);
  };

  const filteredTechnologies = technologies.filter((tech) => {
    if (selectedCategory !== "all" && tech.category !== selectedCategory) {
      return false;
    }
    if (selectedConfidence !== "all" && tech.confidence !== selectedConfidence) {
      return false;
    }
    return true;
  });

  const groupedTechnologies = filteredTechnologies.reduce((acc, tech) => {
    if (!acc[tech.category]) {
      acc[tech.category] = [];
    }
    acc[tech.category].push(tech);
    return acc;
  }, {} as Record<TechnologyCategory, DetectedTechnology[]>);

  const uniqueCategories = Array.from(
    new Set(technologies.map((t) => t.category))
  ).sort();

  if (loading) {
    return (
      <div className="p-6 text-center text-gray-500">
        Loading technologies...
      </div>
    );
  }

  if (error) {
    return (
      <div className="p-6 bg-red-50 border border-red-200 rounded text-red-700">
        Error: {error}
      </div>
    );
  }

  if (technologies.length === 0) {
    return (
      <div className="p-6 text-center text-gray-500">
        No technologies detected.
      </div>
    );
  }

  return (
    <div className="space-y-4">
      {/* Summary */}
      <div className="bg-blue-50 border border-blue-200 rounded-lg p-4">
        <div className="flex items-center justify-between">
          <div>
            <p className="text-sm font-medium text-blue-900">
              Technologies Detected
            </p>
            <p className="text-2xl font-bold text-blue-600">
              {technologies.length}
            </p>
          </div>
          <div className="text-right">
            <p className="text-sm text-blue-700">
              {uniqueCategories.length} categories
            </p>
          </div>
        </div>
      </div>

      {/* Filters */}
      <div className="bg-white border border-gray-200 rounded-lg p-4">
        <div className="flex gap-4 flex-wrap">
          <div className="flex-1 min-w-[200px]">
            <label className="block text-sm font-medium text-gray-700 mb-1">
              Category
            </label>
            <select
              value={selectedCategory}
              onChange={(e) => setSelectedCategory(e.target.value)}
              className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
              <option value="all">All Categories</option>
              {uniqueCategories.map((cat) => (
                <option key={cat} value={cat}>
                  {categoryLabels[cat as TechnologyCategory]}
                </option>
              ))}
            </select>
          </div>

          <div className="flex-1 min-w-[200px]">
            <label className="block text-sm font-medium text-gray-700 mb-1">
              Confidence
            </label>
            <select
              value={selectedConfidence}
              onChange={(e) => setSelectedConfidence(e.target.value)}
              className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
              <option value="all">All Confidence Levels</option>
              <option value="high">High</option>
              <option value="medium">Medium</option>
              <option value="low">Low</option>
            </select>
          </div>
        </div>

        {filteredTechnologies.length !== technologies.length && (
          <p className="mt-3 text-sm text-gray-600">
            Showing {filteredTechnologies.length} of {technologies.length}{" "}
            technologies
          </p>
        )}
      </div>

      {/* Grouped Technologies */}
      <div className="space-y-6">
        {Object.entries(groupedTechnologies)
          .sort(([a], [b]) => a.localeCompare(b))
          .map(([category, techs]) => (
            <div key={category} className="bg-white border border-gray-200 rounded-lg overflow-hidden">
              {/* Category Header */}
              <div className="bg-gray-50 px-4 py-3 border-b border-gray-200">
                <h3 className="text-lg font-semibold text-gray-900">
                  {categoryLabels[category as TechnologyCategory]}
                  <span className="ml-2 text-sm font-normal text-gray-500">
                    ({techs.length})
                  </span>
                </h3>
              </div>

              {/* Technologies List */}
              <div className="divide-y divide-gray-200">
                {techs.map((tech) => {
                  const isExpanded = expandedIds.has(tech.id);
                  return (
                    <div key={tech.id} className="p-4">
                      <div className="flex items-start justify-between">
                        <div className="flex-1">
                          <div className="flex items-center gap-3 mb-2">
                            <h4 className="text-base font-semibold text-gray-900">
                              {tech.name}
                            </h4>
                            {tech.version && (
                              <span className="px-2 py-0.5 bg-gray-100 text-gray-700 text-xs rounded">
                                v{tech.version}
                              </span>
                            )}
                            <span
                              className={`px-2 py-0.5 text-xs font-medium rounded ${
                                confidenceColors[tech.confidence]
                              }`}
                            >
                              {confidenceLabels[tech.confidence]}
                            </span>
                          </div>

                          <div className="flex items-center gap-4 text-sm text-gray-600">
                            <span>
                              Detected on {tech.detectedOnPages}{" "}
                              {tech.detectedOnPages === 1 ? "page" : "pages"}
                            </span>
                            <span>•</span>
                            <span>
                              {tech.evidence.length}{" "}
                              {tech.evidence.length === 1 ? "signal" : "signals"}
                            </span>
                          </div>

                          {/* Evidence Details (Expanded) */}
                          {isExpanded && tech.evidence.length > 0 && (
                            <div className="mt-4 p-3 bg-gray-50 rounded border border-gray-200">
                              <p className="text-sm font-medium text-gray-700 mb-2">
                                Detection Evidence:
                              </p>
                              <ul className="space-y-2">
                                {tech.evidence.map((ev, idx) => (
                                  <li key={idx} className="text-sm">
                                    <span className="inline-block px-2 py-0.5 bg-blue-600 text-white text-xs font-medium rounded mr-2">
                                      {ev.source}
                                    </span>
                                    <code className="text-xs text-gray-900 bg-white px-2 py-1 rounded border border-gray-300 font-mono">
                                      {ev.value}
                                    </code>
                                    {ev.context && (
                                      <p className="ml-14 mt-1 text-xs text-gray-600">
                                        {ev.context}
                                      </p>
                                    )}
                                  </li>
                                ))}
                              </ul>
                            </div>
                          )}
                        </div>

                        {/* Expand/Collapse Button */}
                        {tech.evidence.length > 0 && (
                          <button
                            onClick={() => toggleExpanded(tech.id)}
                            className="ml-4 px-3 py-1 text-sm text-blue-600 hover:text-blue-700 hover:bg-blue-50 rounded transition-colors"
                          >
                            {isExpanded ? "Hide Details" : "Show Details"}
                          </button>
                        )}
                      </div>
                    </div>
                  );
                })}
              </div>
            </div>
          ))}
      </div>
    </div>
  );
}
