<?php

namespace App\Core\Models;

use Database\Factories\CityFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class City extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'name',
        'country_id',
    ];

    protected $with = ['country'];
    protected $withCount = ['clubs'];

    public static function newFactory(): CityFactory|Factory
    {
        return CityFactory::new();
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function clubs(): HasMany
    {
        return $this->hasMany(Club::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'home_city_id');
    }
}
