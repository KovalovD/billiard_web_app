<?php

namespace App\User\Http\Requests;

use App\Core\Http\Requests\BaseFormRequest;

class UpdateEquipmentRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'equipment'               => ['nullable', 'array'],
            'equipment.*.type'        => ['required_with:equipment.*', 'string', 'in:cue,case,chalk,glove,other'],
            'equipment.*.brand'       => ['required_with:equipment.*', 'string', 'max:100'],
            'equipment.*.model'       => ['nullable', 'string', 'max:100'],
            'equipment.*.description' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'equipment.*.type.required_with'  => 'Equipment type is required.',
            'equipment.*.type.in'             => 'Equipment type must be one of: cue, case, chalk, glove, other.',
            'equipment.*.brand.required_with' => 'Equipment brand is required.',
            'equipment.*.brand.max'           => 'Equipment brand must not exceed 100 characters.',
            'equipment.*.model.max'           => 'Equipment model must not exceed 100 characters.',
            'equipment.*.description.max'     => 'Equipment description must not exceed 500 characters.',
        ];
    }
}
