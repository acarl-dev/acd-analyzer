<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CrawlError extends Model
{
    protected $fillable = [
        'crawl_run_id',
        'url',
        'message',
        'depth',
    ];

    public function crawlRun(): BelongsTo
    {
        return $this->belongsTo(CrawlRun::class);
    }

    public function issues(): HasMany
    {
        return $this->hasMany(PageIssue::class);
    }
}