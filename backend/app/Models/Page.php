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
        'status_code',
        'title',
        'meta_description',
        'html',
        'response_time_ms',
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
}