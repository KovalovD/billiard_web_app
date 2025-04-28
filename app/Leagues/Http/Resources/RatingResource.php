<?php

namespace App\Leagues\Http\Resources;

use App\Leagues\Models\Rating;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Rating */
class RatingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'       => $this->id,
            'player'   => [
                'id'   => $this->user->id,
                'name' => $this->user->full_name,
            ],
            'rating'   => $this->rating,
            'position' => $this->position,
        ];
    }
}
