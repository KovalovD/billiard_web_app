<?php

namespace App\OfficialTournaments\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddParticipantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id'         => 'required_without:team_id|exists:users,id',
            'team_id'         => 'required_without:user_id|exists:official_teams,id',
            'seed'            => 'nullable|integer|min:1',
            'rating_snapshot' => 'nullable|integer|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.required_without' => 'Either user or team must be specified',
            'team_id.required_without' => 'Either user or team must be specified',
        ];
    }
}
