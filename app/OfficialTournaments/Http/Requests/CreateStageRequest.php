<?php

namespace App\OfficialTournaments\Http\Requests;

use App\OfficialTournaments\Models\OfficialStage;
use Illuminate\Foundation\Http\FormRequest;

class CreateStageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $types = implode(',', [
            OfficialStage::TYPE_SINGLE_ELIM,
            OfficialStage::TYPE_DOUBLE_ELIM,
            OfficialStage::TYPE_SWISS,
            OfficialStage::TYPE_GROUP,
            OfficialStage::TYPE_ROUND_ROBIN,
            OfficialStage::TYPE_CUSTOM,
        ]);

        return [
            'type'                       => "required|string|in:{$types}",
            'settings'                   => 'nullable|array',
            'settings.best_of'           => 'nullable|integer|min:1|max:11',
            'settings.third_place_match' => 'nullable|boolean',
            'settings.groups_count'      => 'nullable|integer|min:1|max:16',
            'settings.players_per_group' => 'nullable|integer|min:3|max:16',
            'settings.advance_per_group' => 'nullable|integer|min:1|max:8',
            'settings.race_to'           => 'nullable|integer|min:1|max:25',
        ];
    }
}
