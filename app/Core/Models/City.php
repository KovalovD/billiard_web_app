<?php

namespace App\Core\Models;

use Database\Factories\CityFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class City extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'name',
        'country_id',
    ];

    protected $with = ['country'];

    public static function newFactory(): CityFactory|Factory
    {
        return CityFactory::new();
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }
}
