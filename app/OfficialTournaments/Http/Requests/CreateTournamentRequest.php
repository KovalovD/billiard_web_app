<?php

namespace App\OfficialTournaments\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateTournamentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Handled by controller
    }

    public function rules(): array
    {
        return [
            'name'                   => 'required|string|max:255',
            'discipline'             => 'required|string|in:8-ball,9-ball,10-ball,snooker,straight-pool',
            'start_at'               => 'required|date|after:today',
            'end_at'                 => 'required|date|after:start_at',
            'city_id'                => 'nullable|exists:cities,id',
            'club_id'                => 'nullable|exists:clubs,id',
            'entry_fee'              => 'nullable|numeric|min:0',
            'prize_pool'             => 'nullable|numeric|min:0',
            'format'                 => 'nullable|array',
            'format.race_to'         => 'nullable|integer|min:1|max:25',
            'format.alternate_break' => 'nullable|boolean',
            'format.ball_in_hand'    => 'nullable|boolean',
        ];
    }
}
