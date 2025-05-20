<?php

namespace App\Core\Models;

use Database\Factories\ClubFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Club extends Model
{
    use HasFactory;

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

    public static function newFactory(): ClubFactory|Factory
    {
        return ClubFactory::new();
    }
}
