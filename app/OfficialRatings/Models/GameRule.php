<?php

namespace App\OfficialRatings\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameRule extends Model
{
    protected $fillable = [
        'official_rating_id',
        'rules',
    ];

    public function officialRating(): BelongsTo
    {
        return $this->belongsTo(OfficialRating::class);
    }
} 