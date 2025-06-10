<?php

namespace App\Tournaments\Http\Requests;

use App\Core\Http\Requests\BaseFormRequest;

class CreateTournamentTeamRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'name'         => ['required', 'string', 'max:100'],
            'short_name'   => ['nullable', 'string', 'max:20'],
            'seed'         => ['nullable', 'integer', 'min:1'],
            'group_id'     => ['nullable', 'integer', 'exists:tournament_groups,id'],
            'player_ids'   => ['required', 'array', 'min:2'],
            'player_ids.*' => ['integer', 'exists:users,id'],
            'roster_data'  => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'       => 'Team name is required.',
            'name.max'            => 'Team name cannot exceed 100 characters.',
            'short_name.max'      => 'Short name cannot exceed 20 characters.',
            'player_ids.required' => 'At least 2 players are required for a team.',
            'player_ids.min'      => 'A team must have at least 2 players.',
            'player_ids.*.exists' => 'Selected player does not exist.',
            'group_id.exists'     => 'Selected group does not exist.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $playerIds = $this->input('player_ids', []);

            // Check for duplicate player IDs
            if (count($playerIds) !== count(array_unique($playerIds))) {
                $validator->errors()->add('player_ids', 'Duplicate players are not allowed.');
            }

            // Check team size limits based on tournament
            $tournament = $this->route('tournament');
            if ($tournament && $tournament->team_size) {
                if (count($playerIds) > $tournament->team_size) {
                    $validator->errors()->add(
                        'player_ids',
                        "Team cannot have more than {$tournament->team_size} players.",
                    );
                }
            }
        });
    }
}
