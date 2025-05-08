<?php

namespace App\Matches\Http\Resources;

use App\Core\Http\Resources\UserResource;
use App\Matches\Models\MultiplayerGame;
use App\Matches\Models\MultiplayerGamePlayer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

/** @mixin MultiplayerGame */
class MultiplayerGameResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $activePlayersCount = $this->activePlayers()->count();
        $players = $this->players()->with('user')->get();

        $activePlayers = $players
            ->where('eliminated_at', null)
            ->sortBy('turn_order')
            ->values()
        ;

        $eliminatedPlayers = $players
            ->whereNotNull('eliminated_at')
            ->sortBy('finish_position')
            ->values()
        ;

        $currentTurnIndex = $this->getCurrentTurnPlayerIndex();
        $currentTurnPlayerId = $currentTurnIndex !== null ?
            $activePlayers[$currentTurnIndex]->user_id ?? null : null;

        $user = Auth::user();
        $isModerator = $user && $this->isUserModerator($user);
        $currentUserPlayer = $user ? $players->firstWhere('user_id', $user->id) : null;

        return [
            'id'                        => $this->id,
            'league_id'                 => $this->league_id,
            'game_id'                   => $this->game_id,
            'name'                      => $this->name,
            'status'                    => $this->status,
            'initial_lives'             => $this->initial_lives,
            'max_players'               => $this->max_players,
            'registration_ends_at'      => $this->registration_ends_at,
            'started_at'                => $this->started_at,
            'completed_at'              => $this->completed_at,
            'created_at'                => $this->created_at,
            'active_players_count'      => $activePlayersCount,
            'total_players_count'       => $players->count(),
            'current_turn_player_id'    => $currentTurnPlayerId,
            'is_registration_open'      => $this->isRegistrationOpen(),
            'moderator_user_id'         => $this->moderator_user_id,
            'allow_player_targeting'    => $this->allow_player_targeting,
            'is_current_user_moderator' => $isModerator,
            'current_user_player'       => $currentUserPlayer ? [
                'id'              => $currentUserPlayer->id,
                'lives'           => $currentUserPlayer->lives,
                'turn_order'      => $currentUserPlayer->turn_order,
                'cards'           => $currentUserPlayer->cards,
                'finish_position' => $currentUserPlayer->finish_position,
                'eliminated_at'   => $currentUserPlayer->eliminated_at,
                'joined_at'       => $currentUserPlayer->joined_at,
                'is_current_turn' => $currentUserPlayer->user_id === $currentTurnPlayerId,
            ] : null,
            'active_players'            => $activePlayers->map(function (MultiplayerGamePlayer $player) use (
                $currentTurnPlayerId,
            ) {
                return [
                    'id'              => $player->id,
                    'user'            => new UserResource($player->user),
                    'lives'           => $player->lives,
                    'turn_order'      => $player->turn_order,
                    'cards'           => $player->cards,
                    'joined_at'       => $player->joined_at,
                    'is_current_turn' => $player->user_id === $currentTurnPlayerId,
                ];
            }),
            'eliminated_players'        => $eliminatedPlayers->map(function (MultiplayerGamePlayer $player) {
                return [
                    'id'              => $player->id,
                    'user'            => new UserResource($player->user),
                    'lives'           => $player->lives,
                    'turn_order'      => $player->turn_order,
                    'finish_position' => $player->finish_position,
                    'eliminated_at'   => $player->eliminated_at,
                ];
            }),
        ];
    }
}
