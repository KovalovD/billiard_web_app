<?php

namespace App\Admin\Http\Resources;

use App\Core\Models\Country;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Country */
class CountryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'name'         => $this->name,
            'flag_path'    => $this->flag_path,
            'cities_count' => $this->whenCounted('cities'),
        ];
    }
}
