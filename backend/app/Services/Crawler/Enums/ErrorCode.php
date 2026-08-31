<?php

namespace App\Services\Crawler\Enums;

enum ErrorCode: string
{
    // HTTP errors
    case HTTP_4XX = 'http_4xx';
    case HTTP_5XX = 'http_5xx';
    
    // Network errors
    case TIMEOUT = 'timeout';
    case CONNECTION_FAILED = 'connection_failed';
    case DNS_FAILED = 'dns_failed';
    
    // Redirect errors
    case REDIRECT_LOOP = 'redirect_loop';
    case REDIRECT_LIMIT_EXCEEDED = 'redirect_limit_exceeded';
    
    // Renderer errors
    case RENDERER_TIMEOUT = 'renderer_timeout';
    case RENDERER_UNAVAILABLE = 'renderer_unavailable';
    case RENDERER_FAILED = 'renderer_failed';
    
    // External resource errors
    case ROBOTS_FETCH_FAILED = 'robots_fetch_failed';
    case SITEMAP_FETCH_FAILED = 'sitemap_fetch_failed';
    case SITEMAP_PARSE_FAILED = 'sitemap_parse_failed';
    
    // Parser errors
    case PARSE_FAILED = 'parse_failed';
    
    // Generic
    case UNKNOWN = 'unknown';
}
