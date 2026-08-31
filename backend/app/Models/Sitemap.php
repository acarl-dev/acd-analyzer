<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sitemap extends Model
{
    use HasFactory;

    protected $fillable = [
        'website_id',
        'crawl_run_id',
        'url',
        'status_code',
        'type',
        'exists',
        'content_type',
        'error',
        'parent_sitemap_id',
        'fetched_at',
    ];

    protected $casts = [
        'exists' => 'boolean',
        'fetched_at' => 'datetime',
    ];

    public function website(): BelongsTo
    {
        return $this->belongsTo(Website::class);
    }

    public function crawlRun(): BelongsTo
    {
        return $this->belongsTo(CrawlRun::class);
    }

    public function parentSitemap(): BelongsTo
    {
        return $this->belongsTo(Sitemap::class, 'parent_sitemap_id');
    }

    public function childSitemaps(): HasMany
    {
        return $this->hasMany(Sitemap::class, 'parent_sitemap_id');
    }

    public function urls(): HasMany
    {
        return $this->hasMany(SitemapUrl::class);
    }
}
