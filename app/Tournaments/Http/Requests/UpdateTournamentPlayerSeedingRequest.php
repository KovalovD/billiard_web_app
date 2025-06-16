<?php
// app/Tournaments/Http/Requests/UpdateTournamentPlayerSeedingRequest.php

namespace App\Tournaments\Http\Requests;

use App\Core\Http\Requests\BaseFormRequest;

class UpdateTournamentPlayerSeedingRequest extends BaseFormRequest
{
	public function rules(): array
	{
		return [
			'seed_number' => ['required', 'integer', 'min:1'],
		];
	}
}
