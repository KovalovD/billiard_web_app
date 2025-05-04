<?php

namespace App\User\Http\Resources;

use App\Core\Models\City;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin City */
class CityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'      => $this->id,
            'name'    => $this->name,
            'country' => [
                'id'   => $this->country->id,
                'name' => $this->country->name,
            ],
        ];
    }
}
