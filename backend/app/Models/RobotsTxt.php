<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RobotsTxt extends Model
{
    use HasFactory;

    protected $table = 'robots_txt';

    protected $fillable = [
        'website_id',
        'crawl_run_id',
        'url',
        'status_code',
        'exists',
        'content',
        'sitemaps',
        'rules',
        'fetched_at',
    ];

    protected $casts = [
        'exists' => 'boolean',
        'sitemaps' => 'array',
        'rules' => 'array',
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
}
