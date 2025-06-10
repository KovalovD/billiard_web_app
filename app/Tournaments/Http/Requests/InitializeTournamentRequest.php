<?php

namespace App\Tournaments\Http\Requests;

use App\Core\Http\Requests\BaseFormRequest;

class InitializeTournamentRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'force_initialize' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'force_initialize.boolean' => 'Force initialize must be a boolean value.',
        ];
    }
}
