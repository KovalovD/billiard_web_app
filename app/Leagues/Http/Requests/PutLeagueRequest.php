<?php

namespace App\Leagues\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PutLeagueRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name'                           => ['required', 'string', 'max:255'],
            'game_id'                        => ['nullable', 'exists:games,id'],
            'picture'                        => ['nullable', 'url', 'max:255'],
            'details'                        => ['nullable', 'string'],
            'has_rating'                     => ['boolean'],
            'started_at'                     => ['nullable', 'date'],
            'finished_at'                    => ['nullable', 'date', 'after_or_equal:started_at'],
            'start_rating'                   => ['required', 'integer', 'min:0'],
            'rating_change_for_winners_rule' => ['nullable', 'string'],
            'rating_change_for_losers_rule'  => ['nullable', 'string'],
            'max_players'                    => ['required', 'integer', 'min:0'],
            'max_score'                      => ['required', 'integer', 'min:0'],
            'invite_days_expire'             => ['required', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'finished_at.after_or_equal'  => 'End date must be after or equal to start date.',
            'max_players.required'        => 'Maximum players field is required.',
            'max_score.required'          => 'Maximum score field is required.',
            'invite_days_expire.required' => 'Invite expiration days field is required.',
        ];
    }

    protected function prepareForValidation(): void
    {
        // Convert max_players=0 to null to represent unlimited
        if (isset($this->max_players) && $this->max_players == 0) {
            $this->merge(['max_players' => 0]);
        }
    }
}
