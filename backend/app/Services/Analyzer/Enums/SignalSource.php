<?php

namespace App\Services\Analyzer\Enums;

enum SignalSource: string
{
    case META = 'meta';
    case HTML = 'html';
    case SCRIPT = 'script';
    case STYLESHEET = 'stylesheet';
    case HEADER = 'header';
    case COOKIE = 'cookie';
    case DOM_ATTRIBUTE = 'dom_attribute';
    case RESOURCE_URL = 'resource_url';

    public function label(): string
    {
        return match ($this) {
            self::META => 'Meta Tag',
            self::HTML => 'HTML Content',
            self::SCRIPT => 'Script',
            self::STYLESHEET => 'Stylesheet',
            self::HEADER => 'HTTP Header',
            self::COOKIE => 'Cookie',
            self::DOM_ATTRIBUTE => 'DOM Attribute',
            self::RESOURCE_URL => 'Resource URL',
        };
    }
}
