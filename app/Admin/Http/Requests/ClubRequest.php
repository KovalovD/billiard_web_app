<?php

namespace App\Admin\Http\Requests;

use App\Core\Http\Requests\BaseFormRequest;

class ClubRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'name'    => ['required', 'string', 'max:255'],
            'city_id' => ['required', 'exists:cities,id'],
        ];
    }
}
