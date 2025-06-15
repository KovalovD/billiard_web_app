<?php

namespace App\OfficialTournaments\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTournamentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'       => 'sometimes|string|max:255',
            'discipline' => 'sometimes|string|in:8-ball,9-ball,10-ball,snooker,straight-pool',
            'start_at'   => 'sometimes|date',
            'end_at'     => 'sometimes|date|after:start_at',
            'city_id'    => 'sometimes|nullable|exists:cities,id',
            'club_id'    => 'sometimes|nullable|exists:clubs,id',
            'entry_fee'  => 'sometimes|numeric|min:0',
            'prize_pool' => 'sometimes|numeric|min:0',
            'format'     => 'sometimes|array',
        ];
    }
}
