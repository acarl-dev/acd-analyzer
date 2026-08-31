<?php

namespace App\Services\Crawler\Enums;

enum ErrorSource: string
{
    case CRAWLER = 'crawler';
    case HTTP = 'http';
    case RENDERER = 'renderer';
    case ROBOTS = 'robots';
    case SITEMAP = 'sitemap';
    case PARSER = 'parser';
}
