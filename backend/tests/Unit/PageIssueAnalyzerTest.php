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
                ['type' => 'internal'],
                ['type' => 'internal'],
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
}