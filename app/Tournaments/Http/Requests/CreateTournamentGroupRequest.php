<?php

namespace App\Tournaments\Http\Requests;

use App\Core\Http\Requests\BaseFormRequest;

class CreateTournamentGroupRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'groups'                    => ['required', 'array', 'min:1'],
            'groups.*.name'             => ['required', 'string', 'max:50'],
            'groups.*.display_name'     => ['nullable', 'string', 'max:100'],
            'groups.*.group_number'     => ['required', 'integer', 'min:1'],
            'groups.*.max_participants' => ['required', 'integer', 'min:2', 'max:16'],
            'groups.*.advance_count'    => ['required', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'groups.required'                    => 'At least one group is required.',
            'groups.*.name.required'             => 'Group name is required.',
            'groups.*.group_number.required'     => 'Group number is required.',
            'groups.*.max_participants.required' => 'Maximum participants is required.',
            'groups.*.max_participants.min'      => 'Group must have at least 2 participants.',
            'groups.*.advance_count.required'    => 'Advance count is required.',
            'groups.*.advance_count.min'         => 'At least 1 participant must advance from each group.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $groups = $this->input('groups', []);
            $groupNumbers = array_column($groups, 'group_number');

            // Check for duplicate group numbers
            if (count($groupNumbers) !== count(array_unique($groupNumbers))) {
                $validator->errors()->add('groups', 'Group numbers must be unique.');
            }

            // Check advance count doesn't exceed max participants
            foreach ($groups as $index => $group) {
                if (isset($group['advance_count']) && isset($group['max_participants'])) {
                    if ($group['advance_count'] > $group['max_participants']) {
                        $validator->errors()->add(
                            "groups.{$index}.advance_count",
                            'Advance count cannot exceed maximum participants.',
                        );
                    }
                }
            }
        });
    }
}
