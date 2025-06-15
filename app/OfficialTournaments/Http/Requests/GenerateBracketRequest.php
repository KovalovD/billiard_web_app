<?php

namespace App\OfficialTournaments\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GenerateBracketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'include_third_place' => 'sometimes|boolean',
            'group_count'         => 'sometimes|integer|min:1|max:16',
        ];
    }
}
