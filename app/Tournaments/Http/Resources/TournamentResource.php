<?php
// app/Tournaments/Http/Resources/TournamentResource.php

namespace App\Tournaments\Http\Resources;

use App\Leagues\Http\Resources\ClubResource;
use App\Tournaments\Models\Tournament;
use App\User\Http\Resources\CityResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Tournament */
class TournamentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                        => $this->id,
            'slug'                      => $this->slug,
            'name'                      => $this->name,
            'game_id'                   => $this->game_id,
            'regulation'                => $this->regulation,
            'details'                   => $this->details,
            'status'                    => $this->status->value,
            'stage'                     => $this->stage,
            'status_display'            => $this->status->displayValue(),
            'stage_display'             => $this->stage->displayValue(),
            'max_participants'          => $this->max_participants,
            'entry_fee'                 => $this->entry_fee,
            'prize_pool'                => $this->prize_pool,
            'prize_distribution'        => $this->prize_distribution,
            'place_prizes'              => $this->place_prizes,
            'place_bonuses'             => $this->place_bonuses,
            'place_rating_points'       => $this->place_rating_points,
            'seeding_completed'         => $this->seeding_completed,
            'brackets_generated'        => $this->brackets_generated,
            'organizer'                 => $this->organizer,
            'format'                    => $this->format,
            'tournament_type'           => $this->tournament_type,
            'tournament_type_display'   => $this->tournament_type?->displayValue(),
            'olympic_phase_size'        => $this->olympic_phase_size,
            'olympic_has_third_place'   => $this->olympic_has_third_place,
            'group_size_min'            => $this->group_size_min,
            'group_size_max'            => $this->group_size_max,
            'is_registration_open'      => $this->isRegistrationOpen(),
            'playoff_players_per_group' => $this->playoff_players_per_group,
            'races_to'                  => $this->races_to,
            'round_races_to'            => $this->round_races_to,
            'has_third_place_match'     => $this->has_third_place_match,
            'seeding_method'            => $this->seeding_method,
            'seeding_method_display'    => $this->seeding_method->displayName(),
            'players_count'             => $this->players_count,
            'confirmed_players_count'   => $this->confirmed_players_count,
            'pending_applications_count' => $this->pending_applications_count,
            'requires_application'      => $this->requires_application,
            'auto_approve_applications' => $this->auto_approve_applications,
            'is_old'                    => $this->is_old,
            'created_at'                => $this->created_at,
            'updated_at'                => $this->updated_at,
            'start_date'                => $this->start_date?->format('Y-m-d H:i:s'),
            'end_date'                  => $this->end_date?->format('Y-m-d H:i:s'),
            'application_deadline'      => $this->application_deadline?->format('Y-m-d H:i:s'),
            'seeding_completed_at'      => $this->seeding_completed_at?->format('Y-m-d H:i:s'),
            'groups_completed_at'       => $this->groups_completed_at?->format('Y-m-d H:i:s'),

            // Relations
            'game'                      => $this->whenLoaded('game', function () {
                return [
                    'id' => $this->game->id,
                    'name' => $this->game->name,
                    'type' => $this->game->type,
                ];
            }),
            'city'                      => $this->whenLoaded('city', fn() => new CityResource($this->city)),
            'club'                      => $this->whenLoaded('club', fn() => new ClubResource($this->club)),
            'players'                   => TournamentPlayerResource::collection($this->whenLoaded('players')),
            'matches'                   => TournamentMatchResource::collection($this->whenLoaded('matches')),
            'groups'                    => TournamentGroupResource::collection($this->whenLoaded('groups')),
            'brackets'                  => TournamentBracketResource::collection($this->whenLoaded('brackets')),
            'table_widgets'             => TournamentTableWidgetResource::collection($this->whenLoaded('tableWidgets')),
            'official_ratings'          => $this->whenLoaded('officialRatings', function () {
                return $this->officialRatings->map(function ($rating) {
                    return [
                        'id'          => $rating->id,
                        'name'        => $rating->name,
                        'rating_coefficient' => $rating->pivot->rating_coefficient,
                        'is_counting' => $rating->pivot->is_counting,
                    ];
                });
            }),
        ];
    }
}
