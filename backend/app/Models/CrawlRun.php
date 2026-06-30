<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CrawlRun extends Model
{
    protected $fillable = [
        'website_id',
        'status',
        'started_at',
        'finished_at',
        'pages_crawled',
        'error_message',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function website(): BelongsTo
    {
        return $this->belongsTo(Website::class);
    }

    public function pages(): HasMany
    {
        return $this->hasMany(Page::class);
    }

    public function errors(): HasMany
    {
        return $this->hasMany(CrawlError::class);
    }

    public function issues(): HasMany
    {
        return $this->hasMany(PageIssue::class);
    }
}