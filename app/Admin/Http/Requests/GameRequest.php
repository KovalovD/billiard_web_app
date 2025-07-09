<?php

namespace App\Admin\Http\Requests;

use App\Core\Http\Requests\BaseFormRequest;
use App\Matches\Enums\GameType;
use Illuminate\Validation\Rule;

class GameRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'name'           => ['required', 'string', 'max:255'],
            'type'           => ['required', Rule::enum(GameType::class)],
            'rules'          => ['nullable', 'string'],
            'is_multiplayer' => ['boolean'],
        ];
    }
}
