<?php

namespace App\Matches\Http\Requests;

use App\Core\Http\Requests\BaseFormRequest;

class CreateMultiplayerGameRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'name'                 => ['required', 'string', 'max:255'],
            'max_players'          => ['nullable', 'integer', 'min:2', 'max:24'],
            'registration_ends_at' => ['nullable', 'date', 'after:now'],
        ];
    }
}
