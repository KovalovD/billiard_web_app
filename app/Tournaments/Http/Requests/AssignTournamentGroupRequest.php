<?php
// app/Tournaments/Http/Requests/AssignTournamentGroupRequest.php

namespace App\Tournaments\Http\Requests;

use App\Core\Http\Requests\BaseFormRequest;

class AssignTournamentGroupRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'group_code' => ['required', 'string', 'max:10'],
        ];
    }
}
