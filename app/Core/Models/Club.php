<?php

namespace App\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Club extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'name',
        'city_id',
    ];

    protected $with = ['city.country'];

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }
}
