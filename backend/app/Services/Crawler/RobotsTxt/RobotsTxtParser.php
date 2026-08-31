<?php

namespace App\Services\Crawler\RobotsTxt;

class RobotsTxtParser
{
    /**
     * Parse robots.txt content into structured rules and sitemaps.
     *
     * @param string $content Raw robots.txt content
     * @return array{rules: array, sitemaps: array}
     */
    public function parse(string $content): array
    {
        $lines = $this->normalizeLines($content);
        $rules = [];
        $sitemaps = [];
        $currentUserAgent = null;
        $currentRules = ['allow' => [], 'disallow' => []];

        foreach ($lines as $line) {
            // Skip empty lines and comments
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            // Split on first colon
            $parts = explode(':', $line, 2);
            if (count($parts) !== 2) {
                continue;
            }

            $directive = strtolower(trim($parts[0]));
            $value = trim($parts[1]);

            switch ($directive) {
                case 'user-agent':
                    // Save previous user-agent block if exists
                    if ($currentUserAgent !== null) {
                        $rules[] = [
                            'user_agent' => $currentUserAgent,
                            'allow' => $currentRules['allow'],
                            'disallow' => $currentRules['disallow'],
                        ];
                    }
                    // Start new user-agent block
                    $currentUserAgent = $value;
                    $currentRules = ['allow' => [], 'disallow' => []];
                    break;

                case 'allow':
                    if ($currentUserAgent !== null && $value !== '') {
                        $currentRules['allow'][] = $value;
                    }
                    break;

                case 'disallow':
                    if ($currentUserAgent !== null && $value !== '') {
                        $currentRules['disallow'][] = $value;
                    }
                    break;

                case 'sitemap':
                    if ($value !== '') {
                        $sitemaps[] = $value;
                    }
                    break;

                // Ignore other directives like Crawl-delay for now
            }
        }

        // Save last user-agent block
        if ($currentUserAgent !== null) {
            $rules[] = [
                'user_agent' => $currentUserAgent,
                'allow' => $currentRules['allow'],
                'disallow' => $currentRules['disallow'],
            ];
        }

        return [
            'rules' => $rules,
            'sitemaps' => array_unique($sitemaps),
        ];
    }

    /**
     * Normalize line endings and return array of trimmed lines.
     *
     * @param string $content
     * @return array<string>
     */
    private function normalizeLines(string $content): array
    {
        // Normalize CRLF and CR to LF
        $content = str_replace(["\r\n", "\r"], "\n", $content);
        
        // Split into lines and trim
        $lines = explode("\n", $content);
        
        return array_map('trim', $lines);
    }
}
