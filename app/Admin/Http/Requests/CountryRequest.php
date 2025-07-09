<?php

namespace App\Admin\Http\Requests;

use App\Core\Http\Requests\BaseFormRequest;

class CountryRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'name'      => ['required', 'string', 'max:255'],
            'flag_path' => ['nullable', 'string', 'max:255'],
        ];
    }
}
