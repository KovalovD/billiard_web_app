<?php

namespace App\OfficialTournaments\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApplySeedingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'method'                 => 'required|in:manual,random,rating,previous',
            'avoid_same_club'        => 'sometimes|boolean',
            'group_count'            => 'sometimes|integer|min:1|max:16',
            'seeds'                  => 'required_if:method,manual|array',
            'seeds.*'                => 'integer|min:1',
            'previous_tournament_id' => 'required_if:method,previous|exists:official_tournaments,id',
        ];
    }
}
