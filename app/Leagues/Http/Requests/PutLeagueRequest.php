<?php

namespace App\Leagues\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PutLeagueRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name'                           => ['required'],
            'game_id'                        => ['nullable', 'exists:games,id'],
            'picture'                        => ['nullable'],
            'details'                        => ['nullable'],
            'has_rating'                     => ['boolean'],
            'started_at'                     => ['nullable', 'date'],
            'finished_at'                    => ['nullable', 'date'],
            'start_rating'                   => ['required', 'integer'],
            'rating_change_for_winners_rule' => ['nullable', 'string'],
            'rating_change_for_losers_rule'  => ['nullable', 'string'],
        ];
    }
}
