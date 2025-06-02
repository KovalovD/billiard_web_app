<?php

namespace App\Tournaments\Http\Requests;

use App\Core\Http\Requests\BaseFormRequest;

class UpdateTournamentRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'name'               => ['sometimes', 'string', 'max:255'],
            'regulation'         => ['nullable', 'string'],
            'details'            => ['nullable', 'string'],
            'status'             => ['sometimes', 'string', 'in:upcoming,active,completed,cancelled'],
            'game_id'            => ['sometimes', 'integer', 'exists:games,id'],
            'city_id'            => ['nullable', 'integer', 'exists:cities,id'],
            'club_id'            => ['nullable', 'integer', 'exists:clubs,id'],
            'start_date'         => ['sometimes', 'date'],
            'end_date'           => ['sometimes', 'date', 'after_or_equal:start_date'],
            'max_participants'   => ['nullable', 'integer', 'min:2'],
            'entry_fee'          => ['numeric', 'min:0'],
            'prize_pool'         => ['numeric', 'min:0'],
            'prize_distribution' => ['nullable', 'array'],
            'organizer'          => ['nullable', 'string', 'max:255'],
            'format'             => ['nullable', 'string', 'max:255'],
            'official_rating_id' => ['nullable', 'integer', 'exists:official_ratings,id'],
            'rating_coefficient' => ['nullable', 'numeric', 'min:0.1', 'max:5.0'],
        ];
    }

    public function messages(): array
    {
        return [
            'end_date.after_or_equal'   => 'End date must be after or equal to start date.',
            'official_rating_id.exists' => 'The selected official rating does not exist.',
            'rating_coefficient.min'    => 'Rating coefficient must be at least 0.1.',
            'rating_coefficient.max'    => 'Rating coefficient cannot exceed 5.0.',
        ];
    }
}
