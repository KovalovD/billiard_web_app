<?php

namespace App\Admin\Http\Requests;

use App\Core\Http\Requests\BaseFormRequest;

class ClubTableRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'name'       => ['required', 'string', 'max:255'],
            'stream_url' => ['nullable', 'url', 'max:255'],
            'is_active'  => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
