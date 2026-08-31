<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Page extends Model
{
    protected $fillable = [
        'website_id',
        'crawl_run_id',
        'url',
        'requested_url',
        'final_url',
        'status_code',
        'redirect_count',
        'redirect_chain',
        'title',
        'meta_description',
        'html',
        'response_time_ms',
        'depth',
    ];

    protected $casts = [
        'redirect_chain' => 'array',
    ];

    public function issues(): HasMany
    {
        return $this->hasMany(\App\Models\PageIssue::class);
    }
    
    public function website(): BelongsTo
    {
        return $this->belongsTo(Website::class);
    }

    public function crawlRun(): BelongsTo
    {
        return $this->belongsTo(CrawlRun::class);
    }

    public function links(): HasMany
    {
        return $this->hasMany(Link::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(Image::class);
    }

    public function headings(): HasMany
    {
        return $this->hasMany(Heading::class);
    }

    public function detectedTechnologies(): HasMany
    {
        return $this->hasMany(DetectedTechnology::class);
    }
}