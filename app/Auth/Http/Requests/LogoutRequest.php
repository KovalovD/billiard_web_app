<?php

namespace App\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LogoutRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'deviceName' => ['sometimes', 'string'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'deviceName' => $this->header('User-Agent'),
        ]);
    }

}
