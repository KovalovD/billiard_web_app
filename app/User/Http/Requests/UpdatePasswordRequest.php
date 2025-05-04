<?php

namespace App\User\Http\Requests;

use App\Core\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rules\Password;

class UpdatePasswordRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'current_password' => ['required', 'current_password'],
            'password'         => ['required', Password::defaults(), 'confirmed'],
        ];
    }
}
