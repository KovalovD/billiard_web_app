<?php

namespace App\User\Http\Requests;

use App\Core\Http\Requests\BaseFormRequest;
use App\Core\Models\User;
use App\Core\Rules\PhoneNumber;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends BaseFormRequest
{
    public function rules(): array
    {
        $userId = $this->user() ? $this->user()->id : null;

        return [
            'firstname'    => ['required', 'string', 'max:255'],
            'lastname'     => ['required', 'string', 'max:255'],
            'email'        => [
                'required',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($userId),
            ],
            'phone'        => [
                'required',
                'string',
                'max:15',
                Rule::unique(User::class)->ignore($userId),
                new PhoneNumber(),
            ],
            'home_city_id' => ['nullable', 'exists:cities,id'],
            'home_club_id' => ['nullable', 'exists:clubs,id'],
        ];
    }
}
