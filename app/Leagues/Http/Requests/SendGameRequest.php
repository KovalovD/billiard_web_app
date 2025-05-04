<?php

namespace App\Leagues\Http\Requests;

use App\Core\Http\Requests\BaseFormRequest;

class SendGameRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'stream_url' => ['nullable', 'url', 'max:2048'],
            'details'    => ['nullable', 'string', 'max:1000'],
            'club_id'    => ['nullable', 'integer', 'exists:clubs,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'stream_url.url' => 'The stream URL must be a valid URL.',
            'club_id.exists' => 'The selected club does not exist.',
        ];
    }
}
