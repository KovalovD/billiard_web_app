<?php

namespace App\Matches\Http\Requests;

use App\Core\Http\Requests\BaseFormRequest;

class RebuyPlayerRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'user_id'       => ['required', 'integer', 'exists:users,id'],
            'rebuy_fee'     => ['required', 'integer', 'min:0'],
            'is_new_player' => ['required', 'boolean'], // Admin specifies if this is a new player or rebuy
        ];
    }
}
