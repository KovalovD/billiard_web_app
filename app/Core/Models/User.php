<?php

namespace App\Core\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Leagues\Models\Rating;
use database\factories\UserFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'firstname',
        'lastname',
        'email',
        'password',
        'phone',
        'is_admin',
        'home_city_id',
        'home_club_id',
        'email_verified_at',
        'is_active',
        'is_changed_once',
        'sex',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];
    protected $with = ['homeCity', 'homeClub'];

    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }

    public function activeRatings(): HasMany
    {
        return $this->hasMany(Rating::class)->where('is_active', true);
    }

    public function homeCity(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function homeClub(): BelongsTo
    {
        return $this->belongsTo(Club::class);
    }

    public function getSexName(): string
    {
        return match ($this->sex) {
            'M' => 'Male',
            'F' => 'Female',
            'N' => 'Non-binary',
            default => 'Unknown',
        };
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'is_admin'          => 'boolean',
            'is_active'         => 'boolean',
            'is_changed_once'   => 'boolean',
        ];
    }

    protected function fullName(): Attribute
    {
        return Attribute::make(
            get: fn($value) => $this->lastname.' '.$this->firstname,
        );
    }
}
