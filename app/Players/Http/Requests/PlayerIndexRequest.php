<?php

namespace App\Players\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PlayerIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'country_id' => 'nullable|exists:countries,id',
            'city_id'    => 'nullable|exists:cities,id',
            'club_id'    => 'nullable|exists:clubs,id',
            'name'       => 'nullable|string|min:2|max:50',
            'per_page'   => 'nullable|integer|min:10|max:100',
        ];
    }
}
