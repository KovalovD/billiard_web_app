<?php
// app/Tournaments/Http/Resources/TournamentTableWidgetResource.php

namespace App\Tournaments\Http\Resources;

use App\Core\Http\Resources\ClubTableResource;
use App\Tournaments\Models\TournamentTableWidget;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin TournamentTableWidget */
class TournamentTableWidgetResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'tournament_id'     => $this->tournament_id,
            'club_table_id'     => $this->club_table_id,
            'current_match_id'  => $this->current_match_id,
            'widget_url'        => $this->widget_url,
            'player_widget_url' => $this->player_widget_url,
            'is_active'         => $this->is_active,
            'created_at'        => $this->created_at,
            'updated_at'        => $this->updated_at,

            // Relations
            'club_table'        => $this->whenLoaded('clubTable', fn() => new ClubTableResource($this->clubTable)),
            'current_match'     => $this->whenLoaded('currentMatch',
                fn() => new TournamentMatchResource($this->currentMatch)),
            'tournament'        => $this->whenLoaded('tournament', function () {
                return [
                    'id'   => $this->tournament->id,
                    'name' => $this->tournament->name,
                ];
            }),
        ];
    }
}
