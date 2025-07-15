<?php

namespace App\Players\Http\Resources;

use App\Core\Models\User;
use App\Players\Services\PlayerService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin User */
class PlayerDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $playerService = app(PlayerService::class);

        return [
            'id'                 => $this->id,
            'slug'               => $this->slug,
            'firstname'          => $this->firstname,
            'lastname'           => $this->lastname,
            'full_name'          => $this->full_name,
            'email'              => $this->email,
            'phone'              => $this->phone,
            'created_at'         => $this->created_at,
            'sex'                => $this->sex,
            'sex_value'          => $this->when($this->sex, fn() => $this->getSexName()),
            'birthdate'          => $this->birthdate?->toISOString(),
            'description'        => $this->description,
            'equipment'          => $this->equipment ?? [],
            'picture'            => $this->getPicture($this->picture),
            'tournament_picture' => $this->getPicture($this->tournament_picture),
            'avatar'             => $this->getPicture($this->picture),
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
                'city' => $this->homeClub->city ? [
                    'id'   => $this->homeClub->city->id,
                    'name' => $this->homeClub->city->name,
                ] : null,
            ]),

            // Detailed statistics
            'tournament_stats'   => $playerService->getTournamentStats($this->resource),
            'league_stats'       => $playerService->getLeagueStats($this->resource),
            'official_ratings'   => $playerService->getOfficialRatings($this->resource),
            'recent_tournaments' => $playerService->getRecentTournaments($this->resource),
            'recent_matches'     => $playerService->getRecentMatches($this->resource),
            'achievements'       => $playerService->getAchievements($this->resource),
        ];
    }
}
