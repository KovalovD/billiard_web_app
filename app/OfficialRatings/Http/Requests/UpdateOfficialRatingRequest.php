<?php

namespace App\OfficialRatings\Http\Requests;

use App\Core\Http\Requests\BaseFormRequest;

class UpdateOfficialRatingRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'name'               => ['sometimes', 'string', 'max:255'],
            'description'        => ['nullable', 'string'],
            'game_id'            => ['sometimes', 'integer', 'exists:games,id'],
            'is_active'          => ['boolean'],
            'initial_rating'     => ['integer', 'min:0'],
            'calculation_method' => ['string', 'in:tournament_points,elo,custom'],
            'rating_rules'       => ['nullable', 'array'],
        ];
    }
}
