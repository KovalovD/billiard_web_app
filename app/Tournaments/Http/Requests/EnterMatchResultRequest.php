<?php

namespace App\Tournaments\Http\Requests;

use App\Core\Http\Requests\BaseFormRequest;

class EnterMatchResultRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'participant_1_score'          => ['required', 'integer', 'min:0'],
            'participant_2_score'          => ['required', 'integer', 'min:0'],
            'scores'                       => ['nullable', 'array'],
            'scores.*'                     => ['array'],
            'scores.*.game_number'         => ['required_with:scores.*', 'integer', 'min:1'],
            'scores.*.participant_1_score' => ['required_with:scores.*', 'integer', 'min:0'],
            'scores.*.participant_2_score' => ['required_with:scores.*', 'integer', 'min:0'],
            'notes'                        => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'participant_1_score.required'               => 'Participant 1 score is required.',
            'participant_2_score.required'               => 'Participant 2 score is required.',
            'participant_1_score.min'                    => 'Score cannot be negative.',
            'participant_2_score.min'                    => 'Score cannot be negative.',
            'scores.*.game_number.required_with'         => 'Game number is required for detailed scores.',
            'scores.*.participant_1_score.required_with' => 'Game score is required.',
            'scores.*.participant_2_score.required_with' => 'Game score is required.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $participant1Score = $this->input('participant_1_score');
            $participant2Score = $this->input('participant_2_score');

            // Check for ties (if not allowed)
            if ($participant1Score === $participant2Score) {
                $validator->errors()->add('participant_2_score', 'Match cannot end in a tie.');
            }

            // Validate detailed scores match the final score
            $scores = $this->input('scores', []);
            if (!empty($scores)) {
                $participant1Games = 0;
                $participant2Games = 0;

                foreach ($scores as $score) {
                    if (isset($score['participant_1_score']) && isset($score['participant_2_score'])) {
                        if ($score['participant_1_score'] > $score['participant_2_score']) {
                            $participant1Games++;
                        } elseif ($score['participant_2_score'] > $score['participant_1_score']) {
                            $participant2Games++;
                        }
                    }
                }

                if ($participant1Games !== $participant1Score || $participant2Games !== $participant2Score) {
                    $validator->errors()->add('scores', 'Detailed scores do not match final score.');
                }
            }
        });
    }
}
