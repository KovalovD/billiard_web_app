<?php

namespace App\OfficialTournaments\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ScheduleMatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'table_id'     => 'required|exists:official_pool_tables,id',
            'scheduled_at' => 'required|date|after:now',
        ];
    }
}
