<?php

namespace App\Admin\Http\Requests;

use App\Core\Http\Requests\BaseFormRequest;
use App\Core\Rules\PhoneNumber;
use Illuminate\Validation\Rule;

class AdminPlayerRequest extends BaseFormRequest
{
    public function rules(): array
    {
        $playerId = $this->route('player')?->id;

        return [
            'firstname'          => ['required', 'string', 'max:255'],
            'lastname'           => ['required', 'string', 'max:255'],
            'email'              => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique('users')->ignore($playerId),
            ],
            'phone'              => ['required', 'string', 'max:15', new PhoneNumber()],
            'password'           => ['nullable', 'string', 'min:8'],
            'sex'                => ['nullable', 'in:M,F,N'],
            'birthdate'          => ['nullable', 'date', 'before:today', 'after:1900-01-01'],
            'home_city_id'       => ['nullable', 'exists:cities,id'],
            'home_club_id'       => ['nullable', 'exists:clubs,id'],
            'description'        => ['nullable', 'string', 'max:1000'],
            'is_active'          => ['boolean'],
            'is_admin'           => ['boolean'],
            'email_verified'     => ['boolean'],
            'picture'            => ['nullable', 'image', 'max:5120'], // 5MB
            'tournament_picture' => ['nullable', 'image', 'max:5120'], // 5MB
        ];
    }
}
