<?php

namespace App\Matches\Http\Requests;

use App\Core\Http\Requests\BaseFormRequest;

class PerformGameActionRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'action'         => ['required', 'string', 'in:increment_lives,decrement_lives,use_card,record_turn'],
            'target_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'acting_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'card_type'      => ['required_if:action,use_card', 'string', 'in:skip_turn,pass_turn,hand_shot'],
        ];
    }
}
