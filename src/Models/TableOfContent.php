<?php

namespace PrashantMalla\AutoToc\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class TableOfContent extends Model
{
    protected $fillable = ['content'];

    protected $casts = [
        'content' => 'array',
    ];

    /**
     * Get the parent tocable model.
     */
    public function tocable(): MorphTo
    {
        return $this->morphTo();
    }
}
