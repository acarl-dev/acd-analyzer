<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Image extends Model
{
    protected $fillable = [
        'page_id',
        'src',
        'alt',
        'width',
        'height',
    ];

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }
}