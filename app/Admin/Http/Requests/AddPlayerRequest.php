<?php

namespace App\Admin\Http\Requests;

use App\Core\Http\Requests\BaseFormRequest;
use App\Core\Rules\PhoneNumber;

class AddPlayerRequest extends BaseFormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => strtolower($this->email ?? ''),
        ]);
    }

    public function rules(): array
    {
        return [
            'firstname' => ['required', 'string', 'max:255'],
            'lastname'  => ['required', 'string', 'max:255'],
            'email'     => ['required', 'string', 'lowercase', 'email', 'max:255'],
            'phone'     => ['required', 'string', 'max:15', new PhoneNumber()],
            'password'  => ['required', 'string', 'min:8'],
        ];
    }
}
