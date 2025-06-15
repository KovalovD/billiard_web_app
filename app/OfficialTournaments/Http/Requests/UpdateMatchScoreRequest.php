<?php

namespace App\OfficialTournaments\Http\Requests;

use App\OfficialTournaments\Models\OfficialMatch;
use Illuminate\Foundation\Http\FormRequest;

class UpdateMatchScoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $statuses = implode(',', [
            OfficialMatch::STATUS_ONGOING,
            OfficialMatch::STATUS_FINISHED,
        ]);

        return [
            'status'           => "required|in:{$statuses}",
            'sets'             => 'required|array|min:1',
            'sets.*.winner_id' => 'required|exists:official_participants,id',
            'sets.*.score1'    => 'required|integer|min:0',
            'sets.*.score2'    => 'required|integer|min:0',
        ];
    }
}
