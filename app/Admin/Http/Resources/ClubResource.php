<?php

namespace App\Admin\Http\Resources;

use App\Core\Http\Resources\ClubTableResource;
use App\Core\Models\Club;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Club */
class ClubResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'name'              => $this->name,
            'city_id'           => $this->city_id,
            'city'              => new CityResource($this->whenLoaded('city')),
            'tables'            => ClubTableResource::collection($this->whenLoaded('tables')),
            'tables_count'      => $this->whenCounted('tables'),
            'leagues_count'     => $this->whenCounted('leagues'),
            'tournaments_count' => $this->whenCounted('tournaments'),
            'users_count'       => $this->whenCounted('users'),
        ];
    }
}
