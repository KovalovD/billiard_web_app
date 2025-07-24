<?php

namespace App\Core\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Core\Traits\HasFiles;
use App\Core\Traits\HasSlug;
use App\Leagues\Models\Rating;
use App\OfficialRatings\Models\OfficialRatingPlayer;
use App\Tournaments\Models\TournamentPlayer;
use database\factories\UserFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasApiTokens, HasSlug, HasFiles;

    protected string $slugSource = 'full_name';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'slug',
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
        'birthdate',
        'picture',
        'tournament_picture',
        'description',
        'equipment',
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

    public function getAge(): ?int
    {
        if (!$this->birthdate) {
            return null;
        }

        return now()->diffInYears($this->birthdate);
    }

    public function tournamentPlayers(): HasMany
    {
        return $this->hasMany(TournamentPlayer::class);
    }

    public function officialRatingPlayers(): HasMany
    {
        return $this->hasMany(OfficialRatingPlayer::class);
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
            'birthdate'         => 'date',
            'equipment'         => 'array',
        ];
    }

    protected function fullName(): Attribute
    {
        return Attribute::make(
            get: fn($value) => $this->lastname.' '.$this->firstname,
        );
    }

    public static function searchUser(string $query, int $limit = 20): Collection
    {
        $query = mb_strtolower($query);

        return self::where(static function ($q) use ($query) {
            $q
                ->whereRaw('LOWER(firstname) LIKE ?', ["%$query%"])
                ->orWhereRaw('LOWER(lastname) LIKE ?', ["%$query%"])
                ->orWhereRaw('LOWER(email) LIKE ?', ["%$query%"])
                ->orWhereRaw("LOWER(CONCAT(firstname, ' ', lastname)) LIKE ?", ["%$query%"])
                ->orWhereRaw("LOWER(CONCAT(lastname, ' ', firstname)) LIKE ?", ["%$query%"])
            ;
        })
            ->where('is_active', true)
            ->orderBy('lastname')
            ->orderBy('firstname')
            ->limit($limit)
            ->get()
        ;
    }
}
