<?php

namespace App\Matches\Http\Requests;

use App\Core\Http\Requests\BaseFormRequest;

class FinishGameRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'positions'             => ['required', 'array', 'min:1'],
            'positions.*.player_id' => ['required', 'integer', 'exists:multiplayer_game_players,id'],
            'positions.*.position'  => ['required', 'integer', 'min:1'],
        ];
    }
}
