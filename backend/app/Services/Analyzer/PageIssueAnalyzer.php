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

    private const LARGE_HTML_SIZE_BYTES = 500000;

    public function analyze(array $page): array
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

            if (! preg_match('/<link\b[^>]*\brel\s*=\s*["\']canonical["\'][^>]*>/i', $html)) {
                $issues[] = $this->issue(
                    'missing_canonical',
                    'info',
                    'Die Seite hat keinen Canonical-Link.'
                );
            }
        }

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
}