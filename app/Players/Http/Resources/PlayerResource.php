<?php

namespace App\Players\Http\Resources;

use App\Core\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin User */
class PlayerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->id,
            'slug'               => $this->slug,
            'firstname'          => $this->firstname,
            'lastname'           => $this->lastname,
            'full_name'          => $this->full_name,
            'email'              => $this->email,
            'phone'              => $this->phone,
            'sex'                => $this->sex,
            'sex_value'          => $this->when($this->sex, fn() => $this->getSexName()),
            'birthdate'          => $this->birthdate?->toISOString(),
            'description'        => $this->description,
            'equipment'          => $this->equipment ?? [],
            'tournament_picture' => $this->tournament_picture ?? null,
            'avatar' => $this->getPicture($this->picture),
            // Location
            'home_city'          => $this->whenLoaded('homeCity', fn() => [
                'id'      => $this->homeCity->id,
                'name'    => $this->homeCity->name,
                'country' => $this->homeCity->country ? [
                    'id'   => $this->homeCity->country->id,
                    'name' => $this->homeCity->country->name,
                ] : null,
            ]),
            'home_club'          => $this->whenLoaded('homeClub', fn() => [
                'id'   => $this->homeClub->id,
                'name' => $this->homeClub->name,
            ]),

            // Minimal stats
            'stats'              => [
                'tournaments_count'        => $this->tournaments_count ?? 0,
                'tournaments_won'          => $this->tournaments_won ?? 0,
                'league_matches_count'     => $this->league_matches_count ?? 0,
                'league_matches_won'       => $this->league_matches_won ?? 0,
                'official_rating_points'   => $this->official_rating_points ?? null,
                'official_rating_position' => $this->official_rating_position ?? null,
            ],
        ];
    }
}
