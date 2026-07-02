<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetectedTechnology extends Model
{
    protected $fillable = [
        'website_id',
        'crawl_run_id',
        'page_id',
        'type',
        'name',
        'confidence',
        'evidence',
    ];

    protected $casts = [
        'confidence' => 'float',
    ];

    public function website(): BelongsTo
    {
        return $this->belongsTo(Website::class);
    }

    public function crawlRun(): BelongsTo
    {
        return $this->belongsTo(CrawlRun::class);
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }
}