<?php

namespace App\OfficialTournaments\Http\Resources;

use App\Leagues\Http\Resources\ClubResource;
use App\OfficialTournaments\Models\OfficialTournament;
use App\User\Http\Resources\CityResource;
use Illuminate\Http\Resources\Json\JsonResource;

/**@mixin OfficialTournament */
class TournamentResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                 => $this->id,
            'name'               => $this->name,
            'discipline'         => $this->discipline,
            'start_at'           => $this->start_at?->toIso8601String(),
            'end_at'             => $this->end_at?->toIso8601String(),
            'city'               => new CityResource($this->whenLoaded('city')),
            'club'               => new ClubResource($this->whenLoaded('club')),
            'entry_fee'          => $this->entry_fee,
            'prize_pool'         => $this->prize_pool,
            'format'             => $this->format,
            'status'             => $this->getStatus(),
            'participants_count' => $this->whenCounted('participants'),
            'stages'             => StageResource::collection($this->whenLoaded('stages')),
            'pool_tables'        => PoolTableResource::collection($this->whenLoaded('poolTables')),
            'created_at'         => $this->created_at?->toIso8601String(),
            'updated_at'         => $this->updated_at?->toIso8601String(),
        ];
    }

    protected function getStatus(): string
    {
        if (!$this->start_at || $this->start_at->isFuture()) {
            return 'upcoming';
        }

        if ($this->end_at && $this->end_at->isPast()) {
            return 'completed';
        }

        return 'ongoing';
    }
}
