<?php

namespace App\Core\Models;

use Database\Factories\CountryFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Country extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $withCount = ['cities'];

    protected $fillable = [
        'name',
        'flag_path',
    ];

    public static function newFactory(): CountryFactory|Factory
    {
        return CountryFactory::new();
    }

    public function cities(): HasMany
    {
        return $this->hasMany(City::class);
    }
}
