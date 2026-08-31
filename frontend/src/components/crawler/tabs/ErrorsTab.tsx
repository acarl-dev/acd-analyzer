import React, { useState, useEffect } from 'react';
import { CrawlErrorItem, ErrorSeverity, ErrorSource } from '@/types/crawl';

interface ErrorsTabProps {
  crawlRunId: number;
}

const ErrorsTab: React.FC<ErrorsTabProps> = ({ crawlRunId }) => {
  const [errors, setErrors] = useState<CrawlErrorItem[]>([]);
  const [loading, setLoading] = useState(true);
  const [currentPage, setCurrentPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);
  const [totalErrors, setTotalErrors] = useState(0);
  
  // Filters
  const [severityFilter, setSeverityFilter] = useState<ErrorSeverity | ''>('');
  const [sourceFilter, setSourceFilter] = useState<ErrorSource | ''>('');
  const [codeFilter, setCodeFilter] = useState('');

  useEffect(() => {
    fetchErrors();
  }, [crawlRunId, currentPage, severityFilter, sourceFilter, codeFilter]);

  const fetchErrors = async () => {
    setLoading(true);
    try {
      const params = new URLSearchParams({
        page: currentPage.toString(),
        per_page: '50',
      });

      if (severityFilter) params.append('severity', severityFilter);
      if (sourceFilter) params.append('source', sourceFilter);
      if (codeFilter) params.append('code', codeFilter);

      const response = await fetch(
        `http://localhost:8000/api/crawl-runs/${crawlRunId}/errors?${params}`
      );
      const data = await response.json();
      setErrors(data.data);
      setTotalPages(data.pagination.lastPage);
      setTotalErrors(data.pagination.total);
    } catch (error) {
      console.error('Failed to fetch errors:', error);
    } finally {
      setLoading(false);
    }
  };

  const getSeverityColor = (severity: ErrorSeverity): string => {
    switch (severity) {
      case 'critical':
        return 'text-red-700 bg-red-50 border-red-200';
      case 'high':
        return 'text-orange-700 bg-orange-50 border-orange-200';
      case 'medium':
        return 'text-yellow-700 bg-yellow-50 border-yellow-200';
      case 'low':
        return 'text-blue-700 bg-blue-50 border-blue-200';
      default:
        return 'text-gray-700 bg-gray-50 border-gray-200';
    }
  };

  const getSourceBadgeColor = (source: ErrorSource): string => {
    switch (source) {
      case 'http':
        return 'bg-purple-100 text-purple-800';
      case 'renderer':
        return 'bg-violet-100 text-violet-800';
      case 'crawler':
        return 'bg-blue-100 text-blue-800';
      case 'robots':
        return 'bg-green-100 text-green-800';
      case 'sitemap':
        return 'bg-teal-100 text-teal-800';
      case 'parser':
        return 'bg-orange-100 text-orange-800';
      default:
        return 'bg-gray-100 text-gray-800';
    }
  };

  const formatErrorCode = (code: string): string => {
    return code
      .replace(/_/g, ' ')
      .replace(/\b\w/g, (l) => l.toUpperCase());
  };

  if (loading && errors.length === 0) {
    return (
      <div className="flex justify-center items-center h-64">
        <div className="text-gray-500">Lade Fehler...</div>
      </div>
    );
  }

  return (
    <div className="space-y-4">
      {/* Summary */}
      <div className="bg-white shadow rounded-lg p-6">
        <h3 className="text-lg font-medium text-gray-900 mb-4">
          Crawl-Fehler
        </h3>
        <p className="text-sm text-gray-600">
          {totalErrors} {totalErrors === 1 ? 'Fehler' : 'Fehler'} erfasst
        </p>
      </div>

      {/* Filters */}
      <div className="bg-white shadow rounded-lg p-6">
        <h4 className="text-sm font-medium text-gray-900 mb-4">Filter</h4>
        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              Severity
            </label>
            <select
              value={severityFilter}
              onChange={(e) => {
                setSeverityFilter(e.target.value as ErrorSeverity | '');
                setCurrentPage(1);
              }}
              className="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
            >
              <option value="">Alle</option>
              <option value="critical">Critical</option>
              <option value="high">High</option>
              <option value="medium">Medium</option>
              <option value="low">Low</option>
            </select>
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              Source
            </label>
            <select
              value={sourceFilter}
              onChange={(e) => {
                setSourceFilter(e.target.value as ErrorSource | '');
                setCurrentPage(1);
              }}
              className="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
            >
              <option value="">Alle</option>
              <option value="http">HTTP</option>
              <option value="renderer">Renderer</option>
              <option value="crawler">Crawler</option>
              <option value="robots">Robots.txt</option>
              <option value="sitemap">Sitemap</option>
              <option value="parser">Parser</option>
            </select>
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              Error Code
            </label>
            <input
              type="text"
              value={codeFilter}
              onChange={(e) => {
                setCodeFilter(e.target.value);
                setCurrentPage(1);
              }}
              placeholder="z.B. http_4xx"
              className="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
            />
          </div>
        </div>
      </div>

      {/* Errors Table */}
      {errors.length === 0 ? (
        <div className="bg-white shadow rounded-lg p-12 text-center">
          <p className="text-gray-500">Keine Fehler gefunden</p>
        </div>
      ) : (
        <div className="bg-white shadow rounded-lg overflow-hidden">
          <table className="min-w-full divide-y divide-gray-200">
            <thead className="bg-gray-50">
              <tr>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Severity
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Source
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Code
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  URL
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Message
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Zeit
                </th>
              </tr>
            </thead>
            <tbody className="bg-white divide-y divide-gray-200">
              {errors.map((error) => (
                <tr key={error.id} className="hover:bg-gray-50">
                  <td className="px-6 py-4 whitespace-nowrap">
                    <span
                      className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border ${getSeverityColor(
                        error.severity
                      )}`}
                    >
                      {error.severity.toUpperCase()}
                    </span>
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap">
                    <span
                      className={`inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-medium ${getSourceBadgeColor(
                        error.source
                      )}`}
                    >
                      {error.source}
                    </span>
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-mono">
                    {formatErrorCode(error.code)}
                  </td>
                  <td className="px-6 py-4 text-sm text-gray-900 max-w-md truncate">
                    <a
                      href={error.url}
                      target="_blank"
                      rel="noopener noreferrer"
                      className="text-blue-600 hover:text-blue-800 hover:underline"
                      title={error.url}
                    >
                      {error.url}
                    </a>
                  </td>
                  <td className="px-6 py-4 text-sm text-gray-500 max-w-lg">
                    <div className="line-clamp-2" title={error.message}>
                      {error.message}
                    </div>
                    {error.context && Object.keys(error.context).length > 0 && (
                      <details className="mt-1">
                        <summary className="text-xs text-gray-400 cursor-pointer hover:text-gray-600">
                          Context
                        </summary>
                        <pre className="text-xs bg-gray-50 p-2 rounded mt-1 overflow-x-auto">
                          {JSON.stringify(error.context, null, 2)}
                        </pre>
                      </details>
                    )}
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {error.occurredAt
                      ? new Date(error.occurredAt).toLocaleString('de-DE')
                      : 'N/A'}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>

          {/* Pagination */}
          {totalPages > 1 && (
            <div className="bg-gray-50 px-6 py-4 flex items-center justify-between border-t border-gray-200">
              <div className="text-sm text-gray-700">
                Seite {currentPage} von {totalPages}
              </div>
              <div className="flex space-x-2">
                <button
                  onClick={() => setCurrentPage((p) => Math.max(1, p - 1))}
                  disabled={currentPage === 1}
                  className="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  Zurück
                </button>
                <button
                  onClick={() =>
                    setCurrentPage((p) => Math.min(totalPages, p + 1))
                  }
                  disabled={currentPage === totalPages}
                  className="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  Weiter
                </button>
              </div>
            </div>
          )}
        </div>
      )}
    </div>
  );
};

export default ErrorsTab;
