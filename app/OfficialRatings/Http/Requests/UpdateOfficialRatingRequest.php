<?php

namespace App\OfficialRatings\Http\Requests;

use App\Core\Http\Requests\BaseFormRequest;
use App\Matches\Enums\GameType;
use Illuminate\Validation\Rules\Enum;

class UpdateOfficialRatingRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'name'               => ['sometimes', 'string', 'max:255'],
            'description'        => ['nullable', 'string'],
            'game_type' => ['sometimes', new Enum(GameType::class)],
            'is_active'          => ['boolean'],
            'initial_rating'     => ['integer', 'min:0'],
            'calculation_method' => ['string', 'in:tournament_points,elo,custom'],
            'rating_rules'       => ['nullable', 'array'],
        ];
    }
}
