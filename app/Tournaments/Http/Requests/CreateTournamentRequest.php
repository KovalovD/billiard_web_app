<?php
// app/Tournaments/Http/Requests/CreateTournamentRequest.php

namespace App\Tournaments\Http\Requests;

use App\Core\Http\Requests\BaseFormRequest;
use App\Tournaments\Enums\SeedingMethod;
use App\Tournaments\Enums\TournamentType;
use Illuminate\Validation\Rules\Enum;

class CreateTournamentRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'name'                      => ['required', 'string', 'max:255'],
            'regulation'                => ['nullable', 'string'],
            'details'                   => ['nullable', 'string'],
            'game_id'                   => ['required', 'integer', 'exists:games,id'],
            'city_id'                   => ['nullable', 'integer', 'exists:cities,id'],
            'club_id'                   => ['nullable', 'integer', 'exists:clubs,id'],
            'start_date'                => ['required', 'date', 'after_or_equal:today'],
            'end_date'                  => ['required', 'date', 'after_or_equal:start_date'],
            'application_deadline' => ['nullable', 'date', 'before_or_equal:start_date', 'after_or_equal:today'],
            'max_participants'          => ['nullable', 'integer', 'min:2'],
            'entry_fee'                 => ['numeric', 'min:0'],
            'prize_pool' => ['nullable'],
            'prize_distribution'        => ['nullable', 'array'],
            'place_prizes'              => ['nullable', 'array'],
            'place_prizes.*'            => ['numeric', 'min:0'],
            'place_bonuses'             => ['nullable', 'array'],
            'place_bonuses.*'           => ['numeric', 'min:0'],
            'place_rating_points'       => ['nullable', 'array'],
            'place_rating_points.*'     => ['integer', 'min:0'],
            'organizer'                 => ['nullable', 'string', 'max:255'],
            'format'                    => ['nullable', 'string', 'max:255'],
            'tournament_type'           => ['required', new Enum(TournamentType::class)],
            'olympic_phase_size'      => ['nullable', 'integer', 'in:2,4,8,16,32,64'],
            'olympic_has_third_place' => ['boolean'],
            'group_size_min'            => ['nullable', 'integer', 'min:3', 'max:5'],
            'group_size_max'            => ['nullable', 'integer', 'min:3', 'max:5', 'gte:group_size_min'],
            'playoff_players_per_group' => ['nullable', 'integer', 'min:1', 'lt:group_size_min'],
            'races_to'                  => ['nullable', 'integer', 'min:1'],
            'round_races_to'          => ['nullable', 'array'],
            'round_races_to.*'        => ['integer', 'min:1'],
            'has_third_place_match'     => ['boolean'],
            'seeding_method'            => ['nullable', new Enum(SeedingMethod::class)],
            'requires_application'      => ['boolean'],
            'auto_approve_applications' => ['boolean'],
            'official_rating_id'   => ['nullable', 'integer', 'exists:official_ratings,id'],
            'rating_coefficient'   => ['nullable', 'numeric', 'min:0.1', 'max:5.0'],
        ];
    }
    public function messages(): array
    {
        return [
            'end_date.after_or_equal'              => 'End date must be after or equal to start date.',
            'start_date.after_or_equal' => 'Start date must be today or in the future.',
            'application_deadline.before_or_equal' => 'Application deadline must be before or equal to start date.',
            'group_size_max.gte'                   => 'Maximum group size must be greater than or equal to minimum group size.',
            'playoff_players_per_group.lt'         => 'Number of players advancing to playoffs must be less than minimum group size.',
            'official_rating_id.exists' => 'The selected official rating does not exist.',
            'rating_coefficient.min'               => 'Rating coefficient must be at least 0.1.',
            'rating_coefficient.max'               => 'Rating coefficient cannot exceed 5.0.',
        ];
    }
}
