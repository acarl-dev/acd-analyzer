<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Heading extends Model
{
    protected $fillable = [
        'page_id',
        'level',
        'text',
    ];

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }
}