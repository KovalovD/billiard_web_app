<?php

namespace App\Auth\Http\Requests;

use App\Core\Http\Requests\BaseFormRequest;
use App\Core\Models\User;
use App\Core\Rules\PhoneNumber;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'firstname' => ['required', 'string', 'max:255'],
            'lastname'  => ['required', 'string', 'max:255'],
            'email'     => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'phone' => ['required', 'string', 'max:15', 'unique:'.User::class, new PhoneNumber()],
            'password'  => ['required', 'confirmed', Password::defaults()],
        ];
    }
}
