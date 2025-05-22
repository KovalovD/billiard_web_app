<?php

namespace App\OfficialRatings\Http\Requests;

use App\Core\Http\Requests\BaseFormRequest;

class CreateOfficialRatingRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'name'               => ['required', 'string', 'max:255'],
            'description'        => ['nullable', 'string'],
            'game_id'            => ['required', 'integer', 'exists:games,id'],
            'initial_rating'     => ['integer', 'min:0'],
            'calculation_method' => ['string', 'in:tournament_points,elo,custom'],
            'rating_rules'       => ['nullable', 'array'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'initial_rating'     => $this->initial_rating ?? 1000,
            'calculation_method' => $this->calculation_method ?? 'tournament_points',
        ]);
    }
}
