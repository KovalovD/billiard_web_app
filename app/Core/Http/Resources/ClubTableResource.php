<?php
// app/Core/Http/Resources/ClubTableResource.php

namespace App\Core\Http\Resources;

use App\Admin\Http\Resources\ClubResource;
use App\Core\Models\ClubTable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ClubTable */
class ClubTableResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'club_id'    => $this->club_id,
            'name'       => $this->name,
            'stream_url' => $this->stream_url,
            'is_active'  => $this->is_active,
            'sort_order' => $this->sort_order,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // Relations
            'club'       => new ClubResource($this->whenLoaded('club')),
        ];
    }
}
