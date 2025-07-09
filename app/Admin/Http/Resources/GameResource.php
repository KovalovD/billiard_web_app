<?php

namespace App\Admin\Http\Resources;

use App\Core\Models\Game;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Game */
class GameResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'name'              => $this->name,
            'type'              => $this->type,
            'type_display'      => ucfirst($this->type->name),
            'rules'             => $this->rules,
            'is_multiplayer'    => $this->is_multiplayer,
            'leagues_count'     => $this->whenCounted('leagues'),
            'tournaments_count' => $this->whenCounted('tournaments'),
        ];
    }
}
