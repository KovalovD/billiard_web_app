<?php

namespace App\Tournaments\Http\Requests;

use App\Core\Http\Requests\BaseFormRequest;

class UpdateMatchRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'scheduled_at' => ['sometimes', 'date'],
            'table_number' => ['nullable', 'integer', 'min:1', 'max:100'],
            'club_id'      => ['nullable', 'integer', 'exists:clubs,id'],
            'referee'      => ['nullable', 'string', 'max:100'],
            'notes'        => ['nullable', 'string', 'max:1000'],
            'match_data'   => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'scheduled_at.date' => 'Scheduled time must be a valid date.',
            'table_number.min'  => 'Table number must be at least 1.',
            'table_number.max'  => 'Table number cannot exceed 100.',
            'club_id.exists'    => 'Selected club does not exist.',
            'referee.max'       => 'Referee name cannot exceed 100 characters.',
        ];
    }
}
