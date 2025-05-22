<?php

namespace App\Core\Http\Resources;

use App\Core\Models\Game;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Game */
class GameResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'name'           => $this->name,
            'type'           => $this->type,
            'rules'          => $this->rules,
            'is_multiplayer' => $this->is_multiplayer,
        ];
    }
}
