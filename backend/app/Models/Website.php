<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Website extends Model
{
    protected $fillable = [
        'url',
        'host',
    ];

    public function crawlRuns(): HasMany
    {
        return $this->hasMany(CrawlRun::class);
    }

    public function pages(): HasMany
    {
        return $this->hasMany(Page::class);
    }

    public function detectedTechnologies(): HasMany
    {
        return $this->hasMany(DetectedTechnology::class);
    }

    public function robotsTxt(): HasMany
    {
        return $this->hasMany(\App\Models\RobotsTxt::class);
    }

    public function sitemaps(): HasMany
    {
        return $this->hasMany(\App\Models\Sitemap::class);
    }
}