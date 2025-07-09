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
            'firstname'          => ['required', 'string', 'max:255'],
            'lastname'           => ['required', 'string', 'max:255'],
            'email'              => [
                'required',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($userId),
            ],
            'phone'              => [
                'required',
                'string',
                'max:15',
                Rule::unique(User::class)->ignore($userId),
                new PhoneNumber(),
            ],
            'sex'                => ['nullable', 'in:M,F,N'],
            'birthdate'          => ['nullable', 'date', 'before:today', 'after:1900-01-01'],
            'home_city_id'       => ['nullable', 'exists:cities,id'],
            'home_club_id'       => ['nullable', 'exists:clubs,id'],
            'description'        => ['nullable', 'string', 'max:1000'],
            'picture'            => ['nullable', 'image', 'max:5120'], // 5MB max
            'tournament_picture' => ['nullable', 'image', 'max:5120'], // 5MB max
        ];
    }

    public function messages(): array
    {
        return [
            'birthdate.before'       => 'Birthdate must be in the past.',
            'birthdate.after'        => 'Please enter a valid birthdate.',
            'picture.max'            => 'Profile picture must not exceed 5MB.',
            'tournament_picture.max' => 'Tournament picture must not exceed 5MB.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Ensure multipart form data is properly parsed
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            // For PUT/PATCH requests with multipart/form-data,
            // Laravel might not parse the data correctly
            if ($this->header('Content-Type') && str_contains($this->header('Content-Type'), 'multipart/form-data')) {
                // The data should already be available via $this->all()
                // This is just to ensure it's properly loaded
                $this->merge($this->all());
            }
        }
    }
}
