<?php

namespace App\Services\Analyzer;

class PageIssueAnalyzer
{
    private const MIN_TITLE_LENGTH = 10;
    private const MAX_TITLE_LENGTH = 60;

    private const MIN_META_DESCRIPTION_LENGTH = 50;
    private const MAX_META_DESCRIPTION_LENGTH = 160;

    private const MIN_INTERNAL_LINKS = 2;
    private const MANY_EXTERNAL_LINKS = 20;

    private const MIN_VISIBLE_WORDS = 80;

    private const SLOW_RESPONSE_TIME_MS = 2000;

    private const LARGE_HTML_SIZE_BYTES = 500000;

    public function analyze(array $page): array
    {
        $issues = [
            ...$this->analyzeTitle($page),
        ];

        $issues = [
            ...$issues,
            ...$this->analyzeMetaDescription($page),
        ];

        $issues = [
            ...$issues,
            ...$this->analyzeHeadings($page),
        ];

        $issues = [
            ...$issues,
            ...$this->analyzeImages($page),
        ];

        $issues = [
            ...$issues,
            ...$this->analyzeLinks($page),
        ];

        $issues = [
            ...$issues,
            ...$this->analyzeTechnicalSeo($page),
        ];

        $issues = [
            ...$issues,
            ...$this->analyzeHttpStatus($page),
        ];

        $issues = [
            ...$issues,
            ...$this->analyzeContent($page),
        ];

        $issues = [
            ...$issues,
            ...$this->analyzePerformance($page),
        ];

        $issues = [
            ...$issues,
            ...$this->analyzeHtmlSize($page),
        ];

        return $issues;
    }

    private function analyzeTitle(array $page): array
    {
        $issues = [];

        $title = trim((string) ($page['title'] ?? ''));

        if ($title === '') {
            $issues[] = $this->issue(
                'missing_title',
                'error',
                'Die Seite hat keinen Title.'
            );
        } elseif (mb_strlen($title) < self::MIN_TITLE_LENGTH) {
            $issues[] = $this->issue(
                'title_too_short',
                'warning',
                'Der Title der Seite ist sehr kurz.'
            );
        } elseif (mb_strlen($title) > self::MAX_TITLE_LENGTH) {
            $issues[] = $this->issue(
                'title_too_long',
                'warning',
                'Der Title der Seite ist sehr lang.'
            );
        }

        return $issues;
    }

    private function analyzeMetaDescription(array $page): array
    {
        $issues = [];

        $metaDescription = trim((string) ($page['meta_description'] ?? ''));

        if ($metaDescription === '') {
            $issues[] = $this->issue(
                'missing_meta_description',
                'warning',
                'Die Seite hat keine Meta Description.'
            );
        } elseif (mb_strlen($metaDescription) < self::MIN_META_DESCRIPTION_LENGTH) {
            $issues[] = $this->issue(
                'meta_description_too_short',
                'warning',
                'Die Meta Description der Seite ist sehr kurz.'
            );
        } elseif (mb_strlen($metaDescription) > self::MAX_META_DESCRIPTION_LENGTH) {
            $issues[] = $this->issue(
                'meta_description_too_long',
                'warning',
                'Die Meta Description der Seite ist sehr lang.'
            );
        }

        return $issues;
    }

    private function analyzeHeadings(array $page): array
    {
        $issues = [];

        $headings = $page['headings'] ?? [];
        $h1Headings = array_filter($headings, static function (array $heading): bool {
            return (int) ($heading['level'] ?? 0) === 1;
        });

        $h1Count = count($h1Headings);

        if ($h1Count === 0) {
            $issues[] = $this->issue(
                'missing_h1',
                'error',
                'Die Seite hat keine H1-Überschrift.'
            );
        } elseif ($h1Count > 1) {
            $issues[] = $this->issue(
                'multiple_h1',
                'warning',
                'Die Seite enthält mehrere H1-Überschriften.'
            );
        }

        $h2Headings = array_filter($headings, static function (array $heading): bool {
            return (int) ($heading['level'] ?? 0) === 2;
        });

        if ($h1Count > 0 && count($h2Headings) === 0) {
            $issues[] = $this->issue(
                'missing_h2_structure',
                'warning',
                'Die Seite hat keine H2-Überschriften und wirkt dadurch wenig strukturiert.'
            );
        }

        $headingTexts = array_map(
            static fn (array $heading): string => mb_strtolower(trim((string) ($heading['text'] ?? ''))),
            $headings
        );

        $headingTexts = array_filter($headingTexts, static function (string $text): bool {
            return $text !== '';
        });

        $duplicateHeadingTexts = array_filter(
            array_count_values($headingTexts),
            static fn (int $count): bool => $count > 1
        );

        if (count($duplicateHeadingTexts) > 0) {
            $issues[] = $this->issue(
                'duplicate_heading_text',
                'info',
                'Mehrere Überschriften verwenden denselben Text.'
            );
        }

        return $issues;
    }

    private function analyzeImages(array $page): array
    {
        $issues = [];

        $images = $page['images'] ?? [];
        $imageCount = count($images);

        if ($imageCount > 0) {
            $imagesWithoutAlt = array_filter($images, static function (array $image): bool {
                return trim((string) ($image['alt'] ?? '')) === '';
            });

            $missingAltCount = count($imagesWithoutAlt);

            if ($missingAltCount > 0) {
                $issues[] = $this->issue(
                    'images_without_alt',
                    'warning',
                    sprintf(
                        '%d Bild-Element(e) haben keinen Alt-Text.',
                        $missingAltCount
                    )
                );
            }

            if ($missingAltCount / $imageCount >= 0.5) {
                $issues[] = $this->issue(
                    'high_missing_alt_ratio',
                    'warning',
                    'Ein hoher Anteil der Bilder hat keinen Alt-Text.'
                );
            }
        }

        return $issues;
    }

    private function analyzeLinks(array $page): array
    {
        $issues = [];

        $links = $page['links'] ?? [];
        $internalLinks = array_filter($links, static function (array $link): bool {
            return ($link['type'] ?? null) === 'internal';
        });

        if (count($internalLinks) < self::MIN_INTERNAL_LINKS) {
            $issues[] = $this->issue(
                'few_internal_links',
                'warning',
                'Die Seite hat sehr wenige interne Links.'
            );
        }

        $externalLinks = array_filter($links, static function (array $link): bool {
            return ($link['type'] ?? null) === 'external';
        });

        $emptyHrefLinks = array_filter($links, static function (array $link): bool {
            return trim((string) ($link['href'] ?? '')) === '';
        });

        if (count($emptyHrefLinks) > 0) {
            $issues[] = $this->issue(
                'empty_link_href',
                'warning',
                sprintf(
                    '%d Link(s) haben kein gültiges Ziel.',
                    count($emptyHrefLinks)
                )
            );
        }

        $emptyTextLinks = array_filter($links, static function (array $link): bool {
            return trim((string) ($link['text'] ?? '')) === '';
        });

        if (count($emptyTextLinks) > 0) {
            $issues[] = $this->issue(
                'empty_link_text',
                'warning',
                sprintf(
                    '%d Link(s) haben keinen sichtbaren Linktext.',
                    count($emptyTextLinks)
                )
            );
        }

        if (count($externalLinks) > self::MANY_EXTERNAL_LINKS) {
            $issues[] = $this->issue(
                'many_external_links',
                'warning',
                'Die Seite enthält sehr viele externe Links.'
            );
        }

        $insecureExternalLinks = array_filter($externalLinks, static function (array $link): bool {
            return str_starts_with(trim((string) ($link['href'] ?? '')), 'http://');
        });

        if (count($insecureExternalLinks) > 0) {
            $issues[] = $this->issue(
                'insecure_external_links',
                'warning',
                sprintf(
                    '%d externe Link(s) verwenden kein HTTPS.',
                    count($insecureExternalLinks)
                )
            );
        }

        return $issues;
    }

    private function analyzeTechnicalSeo(array $page): array
    {
        $issues = [];

        $html = (string) ($page['html'] ?? '');

        if ($html !== '') {
            if (! preg_match('/<html\b[^>]*\blang\s*=/i', $html)) {
                $issues[] = $this->issue(
                    'missing_html_lang',
                    'warning',
                    'Das HTML-Dokument hat kein lang-Attribut am html-Element.'
                );
            }

            if (! preg_match('/<meta\b[^>]*\bname\s*=\s*["\']viewport["\'][^>]*>/i', $html)) {
                $issues[] = $this->issue(
                    'missing_viewport_meta',
                    'warning',
                    'Die Seite hat kein Viewport-Meta-Tag.'
                );
            }

            if (preg_match('/<meta\b[^>]*\bname\s*=\s*["\']robots["\'][^>]*\bcontent\s*=\s*["\'][^"\']*\bnoindex\b[^"\']*["\'][^>]*>/i', $html)) {
                $issues[] = $this->issue(
                    'robots_noindex',
                    'error',
                    'Die Seite ist per Robots-Meta-Tag auf noindex gesetzt.'
                );
            }

            // Check canonical using persisted data (not regex)
            $canonicalCount = (int) ($page['canonical_count'] ?? 0);
            $canonicalHref = $page['canonical_href'] ?? null;
            $canonicalUrl = $page['canonical_url'] ?? null;
            $finalUrl = $page['final_url'] ?? $page['url'] ?? null;
            
            if ($canonicalCount === 0) {
                $issues[] = $this->issue(
                    'missing_canonical',
                    'info',
                    'Die Seite hat keinen Canonical-Link.'
                );
            } elseif ($canonicalCount > 1) {
                $issues[] = $this->issue(
                    'multiple_canonicals',
                    'warning',
                    sprintf('Die Seite enthält %d Canonical-Links. Nur der erste wird verwendet.', $canonicalCount)
                );
            }
            
            // Check for invalid canonical (has href but no resolved URL)
            if ($canonicalHref !== null && $canonicalHref !== '' && $canonicalUrl === null) {
                $issues[] = $this->issue(
                    'invalid_canonical',
                    'error',
                    'Der Canonical-Link enthält eine ungültige oder nicht auflösbare URL.'
                );
            }
            
            // Check for empty canonical href
            if ($canonicalHref === '' && $canonicalCount > 0) {
                $issues[] = $this->issue(
                    'empty_canonical',
                    'warning',
                    'Der Canonical-Link hat ein leeres href-Attribut.'
                );
            }
            
            // Check if canonical points to a different URL (not self-referencing)
            if ($canonicalUrl !== null && $finalUrl !== null && $canonicalUrl !== $finalUrl) {
                $issues[] = $this->issue(
                    'canonical_to_other_url',
                    'info',
                    sprintf('Der Canonical-Link zeigt auf eine andere URL: %s', $canonicalUrl)
                );
            }
        }

        return $issues;
    }

    private function analyzeContent(array $page): array
    {
        $issues = [];

        $html = (string) ($page['html'] ?? '');

        if ($html === '') {
            return $issues;
        }

        $visibleText = preg_replace('/<script\b[^>]*>.*?<\/script>/is', ' ', $html);
        $visibleText = preg_replace('/<style\b[^>]*>.*?<\/style>/is', ' ', $visibleText ?? '');
        $visibleText = strip_tags($visibleText ?? '');
        $visibleText = html_entity_decode($visibleText, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $visibleText = trim(preg_replace('/\s+/u', ' ', $visibleText) ?? '');

        if ($visibleText === '') {
            $wordCount = 0;
        } else {
            $wordCount = str_word_count($visibleText);
        }

        if ($wordCount < self::MIN_VISIBLE_WORDS) {
            $issues[] = $this->issue(
                'very_low_text_content',
                'warning',
                'Die Seite enthält sehr wenig sichtbaren Textinhalt.'
            );
        }

        return $issues;
    }

    private function analyzeHtmlSize(array $page): array
    {
        $issues = [];

        $htmlSizeBytes = (int) ($page['html_size_bytes'] ?? 0);

        if ($htmlSizeBytes > self::LARGE_HTML_SIZE_BYTES) {
            $issues[] = $this->issue(
                'large_html_size',
                'info',
                'Die gespeicherte HTML-Größe ist ungewöhnlich groß.'
            );
        }

        return $issues;
    }

    private function issue(string $code, string $severity, string $message): array
    {
        return [
            'code' => $code,
            'severity' => $severity,
            'message' => $message,
        ];
    }

    private function analyzePerformance(array $page): array
    {
        $issues = [];

        $responseTimeMs = $page['response_time_ms'] ?? null;

        if ($responseTimeMs !== null && $responseTimeMs > self::SLOW_RESPONSE_TIME_MS) {
            $issues[] = $this->issue(
                'slow_response_time',
                'warning',
                'Die Seite hat eine langsame Server-Antwortzeit.'
            );
        }

        return $issues;
    }

    private function analyzeHttpStatus(array $page): array
    {
        $statusCode = $page['status_code'] ?? null;

        if (! is_int($statusCode) || $statusCode < 400) {
            return [];
        }

        return [
            [
                'code' => 'http_error_status',
                'severity' => 'error',
                'message' => sprintf('Die Seite liefert einen problematischen HTTP-Statuscode: %d.', $statusCode),
                'context' => [
                    'status_code' => $statusCode,
                ],
            ],
        ];
    }
}