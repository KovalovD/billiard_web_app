<?php

namespace App\Tournaments\Http\Requests;

use App\Core\Http\Requests\BaseFormRequest;

class RescheduleMatchRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'scheduled_at' => ['required', 'date', 'after:now'],
            'table_number' => ['nullable', 'integer', 'min:1', 'max:100'],
            'club_id'      => ['nullable', 'integer', 'exists:clubs,id'],
            'notes'        => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'scheduled_at.required' => 'Scheduled time is required.',
            'scheduled_at.after'    => 'Match cannot be scheduled in the past.',
            'table_number.min'      => 'Table number must be at least 1.',
            'table_number.max'      => 'Table number cannot exceed 100.',
            'club_id.exists'        => 'Selected club does not exist.',
        ];
    }
}
