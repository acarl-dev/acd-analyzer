<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SitemapUrl extends Model
{
    use HasFactory;

    protected $fillable = [
        'sitemap_id',
        'url',
        'normalized_url',
        'lastmod',
        'changefreq',
        'priority',
    ];

    protected $casts = [
        'lastmod' => 'datetime',
        'priority' => 'decimal:2',
    ];

    public function sitemap(): BelongsTo
    {
        return $this->belongsTo(Sitemap::class);
    }
}
