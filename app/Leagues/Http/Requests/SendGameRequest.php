<?php

namespace App\Leagues\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendGameRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'stream_url' => ['nullable', 'url'],
            'details'    => ['nullable'],
            'club_id'    => ['nullable', 'exists:clubs,id'],
        ];
    }
}
