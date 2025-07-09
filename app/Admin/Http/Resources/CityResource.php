<?php

namespace App\Admin\Http\Resources;

use App\Core\Models\City;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin City */
class CityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'country_id'  => $this->country_id,
            'country'     => new CountryResource($this->whenLoaded('country')),
            'clubs_count' => $this->whenCounted('clubs'),
        ];
    }
}
