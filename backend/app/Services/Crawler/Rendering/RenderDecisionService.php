<?php

namespace App\Services\Crawler\Rendering;

use App\Services\Crawler\DTO\DownloadedPage;

class RenderDecisionService
{
    /**
     * Decide whether a page should be rendered based on HTTP content quality
     */
    public function shouldRender(DownloadedPage $page): bool
    {
        // Don't render if HTTP fetch failed
        if ($page->statusCode === null || $page->statusCode >= 400) {
            return false;
        }

        // Don't render if HTML is completely empty
        if (empty($page->html)) {
            return false;
        }

        $html = $page->html;
        $lowerHtml = strtolower($html);

        // Check for empty or minimal body content
        if ($this->hasEmptyBody($html, $lowerHtml)) {
            return true;
        }

        // Check for typical app-root patterns with insufficient content
        if ($this->hasAppRootWithInsufficientContent($html, $lowerHtml)) {
            return true;
        }

        // Check for JS-heavy indicators with minimal analyzable content
        if ($this->isJsHeavyWithMinimalContent($html, $lowerHtml)) {
            return true;
        }

        return false;
    }

    /**
     * Get the reason why rendering was triggered
     */
    public function getReason(DownloadedPage $page): ?string
    {
        if (!$this->shouldRender($page)) {
            return null;
        }

        $html = $page->html;
        $lowerHtml = strtolower($html);

        if ($this->hasEmptyBody($html, $lowerHtml)) {
            return 'empty_content';
        }

        if ($this->hasAppRootWithInsufficientContent($html, $lowerHtml)) {
            return 'insufficient_html';
        }

        if ($this->isJsHeavyWithMinimalContent($html, $lowerHtml)) {
            return 'js_heavy';
        }

        return 'insufficient_html';
    }

    private function hasEmptyBody(string $html, string $lowerHtml): bool
    {
        // Extract body content
        preg_match('/<body[^>]*>(.*?)<\/body>/is', $html, $matches);
        $bodyContent = $matches[1] ?? '';

        // Strip scripts and styles
        $bodyContent = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $bodyContent);
        $bodyContent = preg_replace('/<style\b[^>]*>.*?<\/style>/is', '', $bodyContent);

        // Get visible text
        $visibleText = trim(strip_tags($bodyContent ?? ''));
        $visibleTextLength = mb_strlen(preg_replace('/\s+/', ' ', $visibleText) ?? '');

        // Consider body empty if very little visible text
        return $visibleTextLength < 50;
    }

    private function hasAppRootWithInsufficientContent(string $html, string $lowerHtml): bool
    {
        // Check for typical SPA root elements
        $hasAppRoot = str_contains($lowerHtml, 'id="root"')
            || str_contains($lowerHtml, "id='root'")
            || str_contains($lowerHtml, 'id="app"')
            || str_contains($lowerHtml, "id='app'")
            || str_contains($lowerHtml, 'id="__next"')
            || str_contains($lowerHtml, "id='__next'");

        if (!$hasAppRoot) {
            return false;
        }

        // Check if there's very little analyzable content
        $linkCount = substr_count($lowerHtml, '<a ');
        $headingCount = substr_count($lowerHtml, '<h1')
            + substr_count($lowerHtml, '<h2')
            + substr_count($lowerHtml, '<h3');

        $textContent = trim(strip_tags($html));
        $visibleTextLength = mb_strlen(preg_replace('/\s+/', ' ', $textContent) ?? '');

        // If app root exists but very little analyzable content, should render
        return $linkCount < 5 && $headingCount < 2 && $visibleTextLength < 500;
    }

    private function isJsHeavyWithMinimalContent(string $html, string $lowerHtml): bool
    {
        $scriptCount = substr_count($lowerHtml, '<script');
        $linkCount = substr_count($lowerHtml, '<a ');
        $headingCount = substr_count($lowerHtml, '<h1')
            + substr_count($lowerHtml, '<h2');

        $textContent = trim(strip_tags($html));
        $visibleTextLength = mb_strlen(preg_replace('/\s+/', ' ', $textContent) ?? '');

        // Many scripts but very few links and headings suggests JS-rendered content
        if ($scriptCount >= 3 && $linkCount < 5 && $headingCount < 2 && $visibleTextLength < 800) {
            return true;
        }

        // Check for loading hints that suggest JS is required
        $hasLoadingHint = str_contains($lowerHtml, 'please enable javascript')
            || str_contains($lowerHtml, 'noscript')
            || preg_match('/javascript\s+(is\s+)?required/i', $html);

        return $scriptCount >= 2 && $hasLoadingHint && $linkCount < 10;
    }
}
