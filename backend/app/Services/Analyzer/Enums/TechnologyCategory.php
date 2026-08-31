<?php

namespace App\Services\Analyzer\Enums;

enum TechnologyCategory: string
{
    case CMS = 'cms';
    case WEBSITE_BUILDER = 'website_builder';
    case ECOMMERCE = 'ecommerce';
    case FRONTEND = 'frontend';
    case JS_LIBRARY = 'js_library';
    case CSS_UI = 'css_ui';
    case ANALYTICS = 'analytics';
    case CONSENT = 'consent';
    case MARKETING = 'marketing';
    case VIDEO_MAPS = 'video_maps';
    case CAPTCHA = 'captcha';
    case INFRASTRUCTURE = 'infrastructure';
    case FONTS = 'fonts';

    public function label(): string
    {
        return match ($this) {
            self::CMS => 'CMS',
            self::WEBSITE_BUILDER => 'Website Builder',
            self::ECOMMERCE => 'E-Commerce',
            self::FRONTEND => 'Frontend Framework',
            self::JS_LIBRARY => 'JavaScript Library',
            self::CSS_UI => 'CSS/UI Framework',
            self::ANALYTICS => 'Analytics',
            self::CONSENT => 'Consent Management',
            self::MARKETING => 'Marketing',
            self::VIDEO_MAPS => 'Video/Maps',
            self::CAPTCHA => 'CAPTCHA',
            self::INFRASTRUCTURE => 'Infrastructure',
            self::FONTS => 'Fonts',
        };
    }
}
