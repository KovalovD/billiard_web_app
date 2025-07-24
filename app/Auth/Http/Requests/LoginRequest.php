<?php

namespace App\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'email'      => ['required', 'string', 'email'],
            'password'   => ['required', 'string'],
            'deviceName' => ['sometimes', 'string'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email'      => strtolower($this->email ?? ''),
            'deviceName' => $this->deviceName ?? $this->header('User-Agent'),
        ]);
    }
}
