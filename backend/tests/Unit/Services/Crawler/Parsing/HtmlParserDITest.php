<?php

namespace Tests\Unit\Services\Crawler\Parsing;

use App\Services\Crawler\Parsing\HtmlParser;
use App\Services\Crawler\Parsing\CanonicalExtractor;
use Tests\TestCase;

class HtmlParserDITest extends TestCase
{
    public function test_html_parser_can_be_resolved_from_container(): void
    {
        $parser = app(HtmlParser::class);
        
        $this->assertInstanceOf(HtmlParser::class, $parser);
        
        // Use reflection to check if CanonicalExtractor was injected
        $reflection = new \ReflectionClass($parser);
        $property = $reflection->getProperty('canonicalExtractor');
        $property->setAccessible(true);
        $canonicalExtractor = $property->getValue($parser);
        
        $this->assertInstanceOf(CanonicalExtractor::class, $canonicalExtractor);
    }
}
