<?php

namespace App\Tournaments\Http\Requests;

use App\Core\Http\Requests\BaseFormRequest;

class AssignPlayersRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'assignment_method'       => ['sometimes', 'string', 'in:auto,manual'],
            'assignments'             => ['required_if:assignment_method,manual', 'array'],
            'assignments.*.player_id' => ['required', 'integer', 'exists:tournament_players,id'],
            'assignments.*.group_id'  => ['required', 'integer', 'exists:tournament_groups,id'],
            'assignments.*.seed'      => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'assignment_method.in'           => 'Assignment method must be either auto or manual.',
            'assignments.required_if'        => 'Assignments are required when using manual assignment method.',
            'assignments.*.player_id.exists' => 'Selected player does not exist.',
            'assignments.*.group_id.exists'  => 'Selected group does not exist.',
        ];
    }
}
