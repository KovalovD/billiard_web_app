<?php

namespace App\Leagues\Http\Resources;

use App\Core\Models\Club;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Club */
class ClubResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'   => $this->id,
            'name' => $this->name,

            'city'    => $this->city?->name,
            'country' => $this->city?->country->name,
        ];
    }
}
