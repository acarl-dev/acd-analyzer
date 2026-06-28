<?php

namespace App\Services\Analyzer;

class PageIssueAnalyzer
{
    private const MIN_TITLE_LENGTH = 10;
    private const MAX_TITLE_LENGTH = 60;

    private const MIN_META_DESCRIPTION_LENGTH = 50;
    private const MAX_META_DESCRIPTION_LENGTH = 160;

    private const MIN_INTERNAL_LINKS = 2;

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
                        '%d Bild(er) haben keinen Alt-Text.',
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