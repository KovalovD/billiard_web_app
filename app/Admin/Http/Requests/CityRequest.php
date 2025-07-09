<?php

namespace App\Admin\Http\Requests;

use App\Core\Http\Requests\BaseFormRequest;

class CityRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'name'       => ['required', 'string', 'max:255'],
            'country_id' => ['required', 'exists:countries,id'],
        ];
    }
}
