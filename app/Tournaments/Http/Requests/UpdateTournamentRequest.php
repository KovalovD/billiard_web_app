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
        ];
    }

    public function messages(): array
    {
        return [
            'end_date.after_or_equal' => 'End date must be after or equal to start date.',
        ];
    }
}
