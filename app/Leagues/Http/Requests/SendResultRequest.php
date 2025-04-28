<?php

namespace App\Leagues\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendResultRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'first_user_score'  => ['required', 'integer'],
            'second_user_score' => ['required', 'integer'],
        ];
    }
}
