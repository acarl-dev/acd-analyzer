<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use App\Services\Renderer\RenderedPageParser;

#[Signature('renderer:parse {url : The URL to render and parse}')]
#[Description('Render a URL through the browser renderer and parse the rendered HTML')]
class RenderAndParseUrlCommand extends Command
{
    public function handle(RenderedPageParser $renderedPageParser): int
    {
        $url = (string) $this->argument('url');

        $this->info("Rendering and parsing: {$url}");

        $parsedPage = $renderedPageParser->renderAndParse($url);

        if ($parsedPage === null) {
            $this->error('Rendering failed or returned no HTML.');

            return self::FAILURE;
        }

        $this->info('Rendered HTML parsed successfully.');
        $this->line('Title: '.($parsedPage->title ?: '[none]'));
        $this->line('Status: '.$parsedPage->statusCode);
        $this->line('Response time: '.$parsedPage->responseTimeMs.' ms');
        $this->line('Headings: '.count($parsedPage->headings));
        $this->line('Links: '.count($parsedPage->links));
        $this->line('Images: '.count($parsedPage->images));

        if (count($parsedPage->headings) > 0) {
            $this->line('Heading texts:');

            foreach (array_slice($parsedPage->headings, 0, 10) as $heading) {
                $level = $heading['level'] ?? '?';
                $text = $heading['text'] ?? '';

                $this->line("- H{$level}: {$text}");
            }
        }

        return self::SUCCESS;
    }
}
