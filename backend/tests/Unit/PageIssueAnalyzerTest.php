<?php

namespace Tests\Unit;

use App\Services\Analyzer\PageIssueAnalyzer;
use PHPUnit\Framework\TestCase;

class PageIssueAnalyzerTest extends TestCase
{
    public function test_it_detects_missing_basic_page_elements(): void
    {
        $analyzer = new PageIssueAnalyzer();

        $issues = $analyzer->analyze([
            'title' => null,
            'meta_description' => null,
            'headings' => [],
            'images' => [],
            'links' => [],
            'html_size_bytes' => 0,
        ]);

        $codes = array_column($issues, 'code');

        $this->assertContains('missing_title', $codes);
        $this->assertContains('missing_meta_description', $codes);
        $this->assertContains('missing_h1', $codes);
    }

    public function test_it_detects_length_link_image_and_size_issues(): void
    {
        $analyzer = new PageIssueAnalyzer();

        $issues = $analyzer->analyze([
            'title' => 'Hi',
            'meta_description' => 'Too short',
            'headings' => [
                ['level' => 1, 'text' => 'Main heading'],
                ['level' => 1, 'text' => 'Second main heading'],
            ],
            'images' => [
                ['alt' => null],
                ['alt' => ''],
                ['alt' => 'Useful image description'],
            ],
            'links' => [
                ['type' => 'external'],
            ],
            'html_size_bytes' => 600000,
        ]);

        $codes = array_column($issues, 'code');

        $this->assertContains('title_too_short', $codes);
        $this->assertContains('meta_description_too_short', $codes);
        $this->assertContains('multiple_h1', $codes);
        $this->assertContains('images_without_alt', $codes);
        $this->assertContains('high_missing_alt_ratio', $codes);
        $this->assertContains('few_internal_links', $codes);
        $this->assertContains('large_html_size', $codes);
    }

    public function test_it_returns_no_issues_for_a_basic_valid_page(): void
    {
        $analyzer = new PageIssueAnalyzer();

        $issues = $analyzer->analyze([
            'title' => 'A useful page title for the website',
            'meta_description' => 'This is a useful meta description that explains the page content clearly.',
            'headings' => [
                ['level' => 1, 'text' => 'Main heading'],
                ['level' => 2, 'text' => 'Sub heading'],
            ],
            'images' => [
                ['alt' => 'Useful image description'],
            ],
            'links' => [
                [
                    'type' => 'internal',
                    'href' => 'https://example.com',
                    'text' => 'Startseite',
                ],
                [
                    'type' => 'internal',
                    'href' => 'https://example.com/about',
                    'text' => 'Über uns',
                ],
            ],
            'html_size_bytes' => 120000,
        ]);

        $this->assertSame([], $issues);
    }

    private function findIssueByCode(array $issues, string $code): ?array
    {
        foreach ($issues as $issue) {
            if (($issue['code'] ?? null) === $code) {
                return $issue;
            }
        }

        return null;
    }

    public function test_it_assigns_expected_severities_to_detected_issues(): void
    {
        $analyzer = new PageIssueAnalyzer();

        $issues = $analyzer->analyze([
            'title' => null,
            'meta_description' => null,
            'headings' => [],
            'images' => [
                ['alt' => null],
                ['alt' => ''],
            ],
            'links' => [],
            'html_size_bytes' => 600000,
        ]);

        $this->assertSame(
            'error',
            $this->findIssueByCode($issues, 'missing_title')['severity'] ?? null
        );

        $this->assertSame(
            'warning',
            $this->findIssueByCode($issues, 'missing_meta_description')['severity'] ?? null
        );

        $this->assertSame(
            'error',
            $this->findIssueByCode($issues, 'missing_h1')['severity'] ?? null
        );

        $this->assertSame(
            'warning',
            $this->findIssueByCode($issues, 'images_without_alt')['severity'] ?? null
        );

        $this->assertSame(
            'warning',
            $this->findIssueByCode($issues, 'high_missing_alt_ratio')['severity'] ?? null
        );

        $this->assertSame(
            'warning',
            $this->findIssueByCode($issues, 'few_internal_links')['severity'] ?? null
        );

        $this->assertSame(
            'info',
            $this->findIssueByCode($issues, 'large_html_size')['severity'] ?? null
        );
    }

    public function test_it_detects_missing_technical_seo_basics(): void
    {
        $issues = (new PageIssueAnalyzer())->analyze([
            'title' => 'Eine ausreichend lange Beispielseite',
            'meta_description' => 'Diese Meta Description ist lang genug, um keinen bestehenden Fehler auszulösen.',
            'headings' => [
                ['level' => 1, 'text' => 'Hauptüberschrift'],
            ],
            'images' => [],
            'links' => [
                ['type' => 'internal'],
                ['type' => 'internal'],
            ],
            'html' => '<html><head></head><body><h1>Hauptüberschrift</h1></body></html>',
            'html_size_bytes' => 1000,
        ]);

        $codes = array_column($issues, 'code');

        $this->assertContains('missing_html_lang', $codes);
        $this->assertContains('missing_viewport_meta', $codes);
        $this->assertContains('missing_canonical', $codes);
        $this->assertNotContains('robots_noindex', $codes);
    }

    public function test_it_does_not_report_technical_seo_basics_when_present(): void
    {
        $issues = (new PageIssueAnalyzer())->analyze([
            'title' => 'Eine ausreichend lange Beispielseite',
            'meta_description' => 'Diese Meta Description ist lang genug, um keinen bestehenden Fehler auszulösen.',
            'headings' => [
                ['level' => 1, 'text' => 'Hauptüberschrift'],
            ],
            'images' => [],
            'links' => [
                ['type' => 'internal'],
                ['type' => 'internal'],
            ],
            'html' => '<html lang="de"><head><meta name="viewport" content="width=device-width, initial-scale=1"><link rel="canonical" href="https://example.com"></head><body><h1>Hauptüberschrift</h1></body></html>',
            'html_size_bytes' => 1000,
        ]);

        $codes = array_column($issues, 'code');

        $this->assertNotContains('missing_html_lang', $codes);
        $this->assertNotContains('missing_viewport_meta', $codes);
        $this->assertNotContains('missing_canonical', $codes);
        $this->assertNotContains('robots_noindex', $codes);
    }

    public function test_it_detects_robots_noindex(): void
    {
        $issues = (new PageIssueAnalyzer())->analyze([
            'title' => 'Eine ausreichend lange Beispielseite',
            'meta_description' => 'Diese Meta Description ist lang genug, um keinen bestehenden Fehler auszulösen.',
            'headings' => [
                ['level' => 1, 'text' => 'Hauptüberschrift'],
            ],
            'images' => [],
            'links' => [
                ['type' => 'internal'],
                ['type' => 'internal'],
            ],
            'html' => '<html lang="de"><head><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex,follow"><link rel="canonical" href="https://example.com"></head><body><h1>Hauptüberschrift</h1></body></html>',
            'html_size_bytes' => 1000,
        ]);

        $robotsNoindexIssue = collect($issues)->firstWhere('code', 'robots_noindex');

        $this->assertNotNull($robotsNoindexIssue);
        $this->assertSame('error', $robotsNoindexIssue['severity']);
    }

    public function test_it_detects_empty_link_href(): void
    {
        $issues = (new PageIssueAnalyzer())->analyze([
            'title' => 'Eine ausreichend lange Beispielseite',
            'meta_description' => 'Diese Meta Description ist lang genug, um keinen bestehenden Fehler auszulösen.',
            'headings' => [
                ['level' => 1, 'text' => 'Hauptüberschrift'],
            ],
            'images' => [],
            'links' => [
                ['type' => 'internal', 'href' => 'https://example.com'],
                ['type' => 'internal', 'href' => 'https://example.com/about'],
                ['type' => 'external', 'href' => ''],
            ],
            'html' => '<html lang="de"><head><meta name="viewport" content="width=device-width, initial-scale=1"><link rel="canonical" href="https://example.com"></head><body><h1>Hauptüberschrift</h1></body></html>',
            'html_size_bytes' => 1000,
        ]);

        $emptyLinkIssue = collect($issues)->firstWhere('code', 'empty_link_href');

        $this->assertNotNull($emptyLinkIssue);
        $this->assertSame('warning', $emptyLinkIssue['severity']);
    }

    public function test_it_detects_empty_link_text(): void
    {
        $issues = (new PageIssueAnalyzer())->analyze([
            'title' => 'Eine ausreichend lange Beispielseite',
            'meta_description' => 'Diese Meta Description ist lang genug, um keinen bestehenden Fehler auszulösen.',
            'headings' => [
                ['level' => 1, 'text' => 'Hauptüberschrift'],
            ],
            'images' => [],
            'links' => [
                ['type' => 'internal', 'href' => 'https://example.com', 'text' => 'Startseite'],
                ['type' => 'internal', 'href' => 'https://example.com/about', 'text' => 'Über uns'],
                ['type' => 'external', 'href' => 'https://external-example.com', 'text' => ''],
            ],
            'html' => '<html lang="de"><head><meta name="viewport" content="width=device-width, initial-scale=1"><link rel="canonical" href="https://example.com"></head><body><h1>Hauptüberschrift</h1></body></html>',
            'html_size_bytes' => 1000,
        ]);

        $emptyLinkTextIssue = collect($issues)->firstWhere('code', 'empty_link_text');

        $this->assertNotNull($emptyLinkTextIssue);
        $this->assertSame('warning', $emptyLinkTextIssue['severity']);
        $this->assertSame(
            '1 Link(s) haben keinen sichtbaren Linktext.',
            $emptyLinkTextIssue['message']
        );
    }

    public function test_it_detects_insecure_external_links(): void
    {
        $issues = (new PageIssueAnalyzer())->analyze([
            'title' => 'Eine ausreichend lange Beispielseite',
            'meta_description' => 'Diese Meta Description ist lang genug, um keinen bestehenden Fehler auszulösen.',
            'headings' => [
                ['level' => 1, 'text' => 'Hauptüberschrift'],
            ],
            'images' => [],
            'links' => [
                ['type' => 'internal', 'href' => 'https://example.com'],
                ['type' => 'internal', 'href' => 'https://example.com/about'],
                ['type' => 'external', 'href' => 'http://external-example.com'],
            ],
            'html' => '<html lang="de"><head><meta name="viewport" content="width=device-width, initial-scale=1"><link rel="canonical" href="https://example.com"></head><body><h1>Hauptüberschrift</h1></body></html>',
            'html_size_bytes' => 1000,
        ]);

        $insecureLinksIssue = collect($issues)->firstWhere('code', 'insecure_external_links');

        $this->assertNotNull($insecureLinksIssue);
        $this->assertSame('warning', $insecureLinksIssue['severity']);
    }

    public function test_it_detects_many_external_links(): void
    {
        $externalLinks = [];

        for ($i = 1; $i <= 21; $i++) {
            $externalLinks[] = [
                'type' => 'external',
                'href' => "https://external-example-{$i}.com",
            ];
        }

        $issues = (new PageIssueAnalyzer())->analyze([
            'title' => 'Eine ausreichend lange Beispielseite',
            'meta_description' => 'Diese Meta Description ist lang genug, um keinen bestehenden Fehler auszulösen.',
            'headings' => [
                ['level' => 1, 'text' => 'Hauptüberschrift'],
            ],
            'images' => [],
            'links' => [
                ['type' => 'internal', 'href' => 'https://example.com'],
                ['type' => 'internal', 'href' => 'https://example.com/about'],
                ...$externalLinks,
            ],
            'html' => '<html lang="de"><head><meta name="viewport" content="width=device-width, initial-scale=1"><link rel="canonical" href="https://example.com"></head><body><h1>Hauptüberschrift</h1></body></html>',
            'html_size_bytes' => 1000,
        ]);

        $manyExternalLinksIssue = collect($issues)->firstWhere('code', 'many_external_links');

        $this->assertNotNull($manyExternalLinksIssue);
        $this->assertSame('warning', $manyExternalLinksIssue['severity']);
    }
    
    public function test_it_detects_missing_h2_structure(): void
    {
        $issues = (new PageIssueAnalyzer())->analyze([
            'title' => 'Eine ausreichend lange Beispielseite',
            'meta_description' => 'Diese Meta Description ist lang genug, um keinen bestehenden Fehler auszulösen.',
            'headings' => [
                ['level' => 1, 'text' => 'Hauptüberschrift'],
            ],
            'images' => [],
            'links' => [
                ['type' => 'internal', 'href' => 'https://example.com'],
                ['type' => 'internal', 'href' => 'https://example.com/about'],
            ],
            'html' => '<html lang="de"><head><meta name="viewport" content="width=device-width, initial-scale=1"><link rel="canonical" href="https://example.com"></head><body><h1>Hauptüberschrift</h1></body></html>',
            'html_size_bytes' => 1000,
        ]);

        $missingH2Issue = collect($issues)->firstWhere('code', 'missing_h2_structure');

        $this->assertNotNull($missingH2Issue);
        $this->assertSame('warning', $missingH2Issue['severity']);
    }

    public function test_it_detects_duplicate_heading_text(): void
    {
        $issues = (new PageIssueAnalyzer())->analyze([
            'title' => 'Eine ausreichend lange Beispielseite',
            'meta_description' => 'Diese Meta Description ist lang genug, um keinen bestehenden Fehler auszulösen.',
            'headings' => [
                ['level' => 1, 'text' => 'Hauptüberschrift'],
                ['level' => 2, 'text' => 'Leistungen'],
                ['level' => 3, 'text' => 'Leistungen'],
            ],
            'images' => [],
            'links' => [
                ['type' => 'internal', 'href' => 'https://example.com'],
                ['type' => 'internal', 'href' => 'https://example.com/about'],
            ],
            'html' => '<html lang="de"><head><meta name="viewport" content="width=device-width, initial-scale=1"><link rel="canonical" href="https://example.com"></head><body><h1>Hauptüberschrift</h1><h2>Leistungen</h2><h3>Leistungen</h3></body></html>',
            'html_size_bytes' => 1000,
        ]);

        $duplicateHeadingIssue = collect($issues)->firstWhere('code', 'duplicate_heading_text');

        $this->assertNotNull($duplicateHeadingIssue);
        $this->assertSame('info', $duplicateHeadingIssue['severity']);
    }

    public function test_it_detects_very_low_text_content(): void
    {
        $issues = (new PageIssueAnalyzer())->analyze([
            'title' => 'Eine ausreichend lange Beispielseite',
            'meta_description' => 'Diese Meta Description ist lang genug, um keinen bestehenden Fehler auszulösen.',
            'headings' => [
                ['level' => 1, 'text' => 'Hauptüberschrift'],
                ['level' => 2, 'text' => 'Abschnitt'],
            ],
            'images' => [],
            'links' => [
                ['type' => 'internal', 'href' => 'https://example.com'],
                ['type' => 'internal', 'href' => 'https://example.com/about'],
            ],
            'html' => '<html lang="de"><head><meta name="viewport" content="width=device-width, initial-scale=1"><link rel="canonical" href="https://example.com"><style>.hidden { display: none; }</style><script>console.log("not visible content");</script></head><body><h1>Hauptüberschrift</h1><h2>Abschnitt</h2><p>Sehr kurzer Inhalt.</p></body></html>',
            'html_size_bytes' => 1000,
        ]);

        $veryLowTextContentIssue = collect($issues)->firstWhere('code', 'very_low_text_content');

        $this->assertNotNull($veryLowTextContentIssue);
        $this->assertSame('warning', $veryLowTextContentIssue['severity']);
    }

    public function test_it_does_not_report_very_low_text_content_when_page_has_enough_text(): void
    {
        $content = str_repeat(
            'Dies ist ein sinnvoller sichtbarer Beispieltext mit mehreren Wörtern für die Inhaltsanalyse. ',
            12
        );

        $issues = (new PageIssueAnalyzer())->analyze([
            'title' => 'Eine ausreichend lange Beispielseite',
            'meta_description' => 'Diese Meta Description ist lang genug, um keinen bestehenden Fehler auszulösen.',
            'headings' => [
                ['level' => 1, 'text' => 'Hauptüberschrift'],
                ['level' => 2, 'text' => 'Abschnitt'],
            ],
            'images' => [],
            'links' => [
                ['type' => 'internal', 'href' => 'https://example.com'],
                ['type' => 'internal', 'href' => 'https://example.com/about'],
            ],
            'html' => '<html lang="de"><head><meta name="viewport" content="width=device-width, initial-scale=1"><link rel="canonical" href="https://example.com"></head><body><h1>Hauptüberschrift</h1><h2>Abschnitt</h2><p>' . $content . '</p></body></html>',
            'html_size_bytes' => 1000,
        ]);

        $codes = array_column($issues, 'code');

        $this->assertNotContains('very_low_text_content', $codes);
    }

    public function test_it_detects_slow_response_time(): void
    {
        $issues = (new PageIssueAnalyzer())->analyze([
            'title' => 'A useful page title',
            'meta_description' => 'This is a useful meta description for the page.',
            'headings' => [
                ['level' => 1, 'text' => 'Main heading'],
                ['level' => 2, 'text' => 'Section heading'],
            ],
            'images' => [],
            'links' => [
                ['type' => 'internal', 'href' => '/about'],
                ['type' => 'internal', 'href' => '/contact'],
            ],
            'html' => str_repeat('word ', 120),
            'html_size_bytes' => 1000,
            'response_time_ms' => 2500,
        ]);

        $this->assertContains('slow_response_time', array_column($issues, 'code'));
    }

    public function test_it_does_not_detect_slow_response_time_for_fast_pages(): void
    {
        $issues = (new PageIssueAnalyzer())->analyze([
            'title' => 'A useful page title',
            'meta_description' => 'This is a useful meta description for the page.',
            'headings' => [
                ['level' => 1, 'text' => 'Main heading'],
                ['level' => 2, 'text' => 'Section heading'],
            ],
            'images' => [],
            'links' => [
                ['type' => 'internal', 'href' => '/about'],
                ['type' => 'internal', 'href' => '/contact'],
            ],
            'html' => str_repeat('word ', 120),
            'html_size_bytes' => 1000,
            'response_time_ms' => 500,
        ]);

        $this->assertNotContains('slow_response_time', array_column($issues, 'code'));
    }

    public function test_it_detects_http_error_status(): void
    {
        $issues = (new PageIssueAnalyzer())->analyze([
            'title' => 'A useful page title',
            'meta_description' => 'This is a useful meta description for the page.',
            'headings' => [
                ['level' => 1, 'text' => 'Main heading'],
                ['level' => 2, 'text' => 'Section heading'],
            ],
            'images' => [],
            'links' => [
                ['type' => 'internal', 'href' => '/about'],
                ['type' => 'internal', 'href' => '/contact'],
            ],
            'html' => str_repeat('word ', 120),
            'html_size_bytes' => 1000,
            'status_code' => 404,
        ]);

        $issue = collect($issues)->firstWhere('code', 'http_error_status');

        $this->assertNotNull($issue);
        $this->assertSame('error', $issue['severity']);
    }

    public function test_it_does_not_detect_http_error_status_for_successful_status(): void
    {
        $issues = (new PageIssueAnalyzer())->analyze([
            'title' => 'A useful page title',
            'meta_description' => 'This is a useful meta description for the page.',
            'headings' => [
                ['level' => 1, 'text' => 'Main heading'],
                ['level' => 2, 'text' => 'Section heading'],
            ],
            'images' => [],
            'links' => [
                ['type' => 'internal', 'href' => '/about'],
                ['type' => 'internal', 'href' => '/contact'],
            ],
            'html' => str_repeat('word ', 120),
            'html_size_bytes' => 1000,
            'status_code' => 200,
        ]);

        $issue = collect($issues)->firstWhere('code', 'http_error_status');

        $this->assertNull($issue);
    }
}