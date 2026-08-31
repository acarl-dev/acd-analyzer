<?php

namespace Tests\Unit\Services\Crawler\RobotsTxt;

use App\Services\Crawler\RobotsTxt\RobotsTxtParser;
use Tests\TestCase;

class RobotsTxtParserTest extends TestCase
{
    private RobotsTxtParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new RobotsTxtParser();
    }

    public function test_parses_empty_content(): void
    {
        $result = $this->parser->parse('');

        $this->assertSame([], $result['rules']);
        $this->assertSame([], $result['sitemaps']);
    }

    public function test_parses_single_user_agent_with_disallow(): void
    {
        $content = <<<TXT
User-agent: *
Disallow: /admin
TXT;

        $result = $this->parser->parse($content);

        $this->assertCount(1, $result['rules']);
        $this->assertSame('*', $result['rules'][0]['user_agent']);
        $this->assertSame([], $result['rules'][0]['allow']);
        $this->assertSame(['/admin'], $result['rules'][0]['disallow']);
    }

    public function test_parses_allow_and_disallow(): void
    {
        $content = <<<TXT
User-agent: *
Disallow: /admin
Allow: /admin/public
TXT;

        $result = $this->parser->parse($content);

        $this->assertCount(1, $result['rules']);
        $this->assertSame(['/admin/public'], $result['rules'][0]['allow']);
        $this->assertSame(['/admin'], $result['rules'][0]['disallow']);
    }

    public function test_parses_multiple_user_agents(): void
    {
        $content = <<<TXT
User-agent: Googlebot
Disallow: /private

User-agent: *
Disallow: /admin
TXT;

        $result = $this->parser->parse($content);

        $this->assertCount(2, $result['rules']);
        $this->assertSame('Googlebot', $result['rules'][0]['user_agent']);
        $this->assertSame(['/private'], $result['rules'][0]['disallow']);
        $this->assertSame('*', $result['rules'][1]['user_agent']);
        $this->assertSame(['/admin'], $result['rules'][1]['disallow']);
    }

    public function test_parses_sitemap_directive(): void
    {
        $content = <<<TXT
User-agent: *
Disallow:

Sitemap: https://example.com/sitemap.xml
TXT;

        $result = $this->parser->parse($content);

        $this->assertSame(['https://example.com/sitemap.xml'], $result['sitemaps']);
    }

    public function test_parses_multiple_sitemaps(): void
    {
        $content = <<<TXT
Sitemap: https://example.com/sitemap.xml
Sitemap: https://example.com/sitemap-images.xml
TXT;

        $result = $this->parser->parse($content);

        $this->assertCount(2, $result['sitemaps']);
        $this->assertContains('https://example.com/sitemap.xml', $result['sitemaps']);
        $this->assertContains('https://example.com/sitemap-images.xml', $result['sitemaps']);
    }

    public function test_ignores_comments(): void
    {
        $content = <<<TXT
# This is a comment
User-agent: *
# Another comment
Disallow: /admin
TXT;

        $result = $this->parser->parse($content);

        $this->assertCount(1, $result['rules']);
        $this->assertSame(['/admin'], $result['rules'][0]['disallow']);
    }

    public function test_ignores_empty_lines(): void
    {
        $content = <<<TXT
User-agent: *

Disallow: /admin

Disallow: /private
TXT;

        $result = $this->parser->parse($content);

        $this->assertCount(1, $result['rules']);
        $this->assertSame(['/admin', '/private'], $result['rules'][0]['disallow']);
    }

    public function test_handles_crlf_line_endings(): void
    {
        $content = "User-agent: *\r\nDisallow: /admin\r\n";

        $result = $this->parser->parse($content);

        $this->assertCount(1, $result['rules']);
        $this->assertSame(['/admin'], $result['rules'][0]['disallow']);
    }

    public function test_handles_cr_line_endings(): void
    {
        $content = "User-agent: *\rDisallow: /admin\r";

        $result = $this->parser->parse($content);

        $this->assertCount(1, $result['rules']);
        $this->assertSame(['/admin'], $result['rules'][0]['disallow']);
    }

    public function test_case_insensitive_directives(): void
    {
        $content = <<<TXT
USER-AGENT: *
DISALLOW: /admin
ALLOW: /public
SITEMAP: https://example.com/sitemap.xml
TXT;

        $result = $this->parser->parse($content);

        $this->assertCount(1, $result['rules']);
        $this->assertSame(['/admin'], $result['rules'][0]['disallow']);
        $this->assertSame(['/public'], $result['rules'][0]['allow']);
        $this->assertSame(['https://example.com/sitemap.xml'], $result['sitemaps']);
    }

    public function test_trims_whitespace(): void
    {
        $content = <<<TXT
  User-agent:   *  
  Disallow:   /admin  
  Sitemap:   https://example.com/sitemap.xml  
TXT;

        $result = $this->parser->parse($content);

        $this->assertSame('*', $result['rules'][0]['user_agent']);
        $this->assertSame(['/admin'], $result['rules'][0]['disallow']);
        $this->assertSame(['https://example.com/sitemap.xml'], $result['sitemaps']);
    }

    public function test_skips_directives_without_user_agent(): void
    {
        $content = <<<TXT
Disallow: /admin
User-agent: *
Disallow: /private
TXT;

        $result = $this->parser->parse($content);

        // First Disallow should be ignored (no user-agent yet)
        $this->assertCount(1, $result['rules']);
        $this->assertSame(['/private'], $result['rules'][0]['disallow']);
    }

    public function test_skips_empty_disallow_values(): void
    {
        $content = <<<TXT
User-agent: *
Disallow:
Disallow: /admin
TXT;

        $result = $this->parser->parse($content);

        // Empty Disallow should be ignored
        $this->assertSame(['/admin'], $result['rules'][0]['disallow']);
    }

    public function test_multiple_directives_per_user_agent(): void
    {
        $content = <<<TXT
User-agent: *
Disallow: /admin
Disallow: /private
Disallow: /secret
Allow: /public
Allow: /open
TXT;

        $result = $this->parser->parse($content);

        $this->assertCount(1, $result['rules']);
        $this->assertSame(['/admin', '/private', '/secret'], $result['rules'][0]['disallow']);
        $this->assertSame(['/public', '/open'], $result['rules'][0]['allow']);
    }

    public function test_handles_malformed_lines(): void
    {
        $content = <<<TXT
User-agent: *
This is invalid
Disallow: /admin
NoColonHere
Disallow: /private
TXT;

        $result = $this->parser->parse($content);

        // Should skip malformed lines
        $this->assertCount(1, $result['rules']);
        $this->assertSame(['/admin', '/private'], $result['rules'][0]['disallow']);
    }

    public function test_deduplicates_sitemaps(): void
    {
        $content = <<<TXT
Sitemap: https://example.com/sitemap.xml
Sitemap: https://example.com/sitemap.xml
Sitemap: https://example.com/sitemap2.xml
TXT;

        $result = $this->parser->parse($content);

        $this->assertCount(2, $result['sitemaps']);
    }

    public function test_real_world_example(): void
    {
        $content = <<<TXT
# Example robots.txt
User-agent: Googlebot
Disallow: /nogooglebot/
Allow: /nogooglebot/allowed/

User-agent: *
Allow: /
Disallow: /search
Disallow: /admin

# Sitemaps
Sitemap: https://www.example.com/sitemap.xml
Sitemap: https://www.example.com/sitemap-news.xml
TXT;

        $result = $this->parser->parse($content);

        $this->assertCount(2, $result['rules']);
        
        // Googlebot rules
        $this->assertSame('Googlebot', $result['rules'][0]['user_agent']);
        $this->assertSame(['/nogooglebot/allowed/'], $result['rules'][0]['allow']);
        $this->assertSame(['/nogooglebot/'], $result['rules'][0]['disallow']);
        
        // Wildcard rules
        $this->assertSame('*', $result['rules'][1]['user_agent']);
        $this->assertSame(['/'], $result['rules'][1]['allow']);
        $this->assertSame(['/search', '/admin'], $result['rules'][1]['disallow']);
        
        // Sitemaps
        $this->assertCount(2, $result['sitemaps']);
        $this->assertContains('https://www.example.com/sitemap.xml', $result['sitemaps']);
        $this->assertContains('https://www.example.com/sitemap-news.xml', $result['sitemaps']);
    }
}
