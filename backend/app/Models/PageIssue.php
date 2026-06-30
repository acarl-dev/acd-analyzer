<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PageIssue extends Model
{
    protected $fillable = [
        'crawl_run_id',
        'page_id',
        'crawl_error_id',
        'url',
        'code',
        'severity',
        'message',
        'context',
        'analyzer_version',
    ];

    protected $casts = [
        'context' => 'array',
    ];

    public function crawlRun(): BelongsTo
    {
        return $this->belongsTo(CrawlRun::class);
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    public function crawlError(): BelongsTo
    {
        return $this->belongsTo(CrawlError::class);
    }
}