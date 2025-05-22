<?php
// app/Tournaments/Http/Requests/CreateTournamentRequest.php

namespace App\Tournaments\Http\Requests;

use App\Core\Http\Requests\BaseFormRequest;

class CreateTournamentRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'name'               => ['required', 'string', 'max:255'],
            'regulation'         => ['nullable', 'string'],
            'details'            => ['nullable', 'string'],
            'game_id'            => ['required', 'integer', 'exists:games,id'],
            'city_id'            => ['nullable', 'integer', 'exists:cities,id'],
            'club_id'            => ['nullable', 'integer', 'exists:clubs,id'],
            'start_date'         => ['required', 'date', 'after_or_equal:today'],
            'end_date'           => ['required', 'date', 'after_or_equal:start_date'],
            'max_participants'   => ['nullable', 'integer', 'min:2'],
            'entry_fee'          => ['numeric', 'min:0'],
            'prize_pool'         => ['numeric', 'min:0'],
            'prize_distribution' => ['nullable', 'array'],
            'organizer'          => ['nullable', 'string', 'max:255'],
            'format'             => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'end_date.after_or_equal'   => 'End date must be after or equal to start date.',
            'start_date.after_or_equal' => 'Start date must be today or in the future.',
        ];
    }
}
