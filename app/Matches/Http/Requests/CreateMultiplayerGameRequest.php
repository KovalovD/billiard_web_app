<?php

namespace App\Matches\Http\Requests;

use App\Core\Http\Requests\BaseFormRequest;

class CreateMultiplayerGameRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'name'                     => ['required', 'string', 'max:255'],
            'official_rating_id'       => ['nullable', 'integer', 'exists:official_ratings,id'],
            'max_players'              => ['nullable', 'integer', 'min:2', 'max:24'],
            'registration_ends_at'     => ['nullable', 'date', 'after:now'],
            'allow_player_targeting'   => ['nullable', 'boolean'],
            'entrance_fee'             => ['nullable', 'integer', 'min:0'],
            'first_place_percent'      => ['nullable', 'integer', 'min:0', 'max:100'],
            'second_place_percent'     => ['nullable', 'integer', 'min:0', 'max:100'],
            'grand_final_percent'      => ['nullable', 'integer', 'min:0', 'max:100'],
            'penalty_fee'              => ['nullable', 'integer', 'min:0'],
            'allow_rebuy'              => ['nullable', 'boolean'],
            'rebuy_rounds'             => ['nullable', 'required_if:allow_rebuy,true', 'integer', 'min:1', 'max:20'],
            'lives_per_new_player'     => ['nullable', 'integer', 'min:0', 'max:10'],
            'enable_penalties'         => ['nullable', 'boolean'],
            'penalty_rounds_threshold' => [
                'nullable', 'required_if:enable_penalties,true', 'integer', 'min:1', 'max:10',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'official_rating_id.exists'            => 'The selected official rating does not exist.',
            'first_place_percent.max'              => 'The first place percentage cannot exceed 100%.',
            'second_place_percent.max'             => 'The second place percentage cannot exceed 100%.',
            'grand_final_percent.max'              => 'The grand final percentage cannot exceed 100%.',
            'rebuy_rounds.required_if'             => 'Number of rebuy rounds is required when rebuy is enabled.',
            'penalty_rounds_threshold.required_if' => 'Penalty rounds threshold is required when penalties are enabled.',
        ];
    }

    protected function prepareForValidation(): void
    {
        // Ensure percentages add up to 100%
        if (
            $this->has('first_place_percent') &&
            $this->has('second_place_percent') &&
            $this->has('grand_final_percent')
        ) {
            $total = $this->first_place_percent + $this->second_place_percent + $this->grand_final_percent;

            if ($total !== 100) {
                $this->merge([
                    'first_place_percent'  => 60,
                    'second_place_percent' => 20,
                    'grand_final_percent'  => 20,
                ]);
            }
        }
    }
}
