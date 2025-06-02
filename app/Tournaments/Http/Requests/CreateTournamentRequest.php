<?php

namespace App\Tournaments\Http\Requests;

use App\Core\Http\Requests\BaseFormRequest;

class CreateTournamentRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'name'                 => ['required', 'string', 'max:255'],
            'regulation'           => ['nullable', 'string'],
            'details'              => ['nullable', 'string'],
            'game_id'              => ['required', 'integer', 'exists:games,id'],
            'city_id'              => ['nullable', 'integer', 'exists:cities,id'],
            'club_id'              => ['nullable', 'integer', 'exists:clubs,id'],
            'start_date'           => ['required', 'date', 'after_or_equal:today'],
            'end_date'             => ['required', 'date', 'after_or_equal:start_date'],
            'application_deadline' => ['nullable', 'date', 'before_or_equal:start_date', 'after_or_equal:today'],
            'max_participants'     => ['nullable', 'integer', 'min:2'],
            'entry_fee'            => ['numeric', 'min:0'],
            'prize_pool'           => ['numeric', 'min:0'],
            'prize_distribution'   => ['nullable', 'array'],
            'organizer'            => ['nullable', 'string', 'max:255'],
            'format'               => ['nullable', 'string', 'max:255'],
            'official_rating_id' => ['nullable', 'integer', 'exists:official_ratings,id'],
            'rating_coefficient' => ['nullable', 'numeric', 'min:0.1', 'max:5.0'],
        ];
    }

    public function messages(): array
    {
        return [
            'end_date.after_or_equal'   => 'End date must be after or equal to start date.',
            'start_date.after_or_equal' => 'Start date must be today or in the future.',
            'official_rating_id.exists' => 'The selected official rating does not exist.',
            'rating_coefficient.min'    => 'Rating coefficient must be at least 0.1.',
            'rating_coefficient.max'    => 'Rating coefficient cannot exceed 5.0.',
        ];
    }
}
