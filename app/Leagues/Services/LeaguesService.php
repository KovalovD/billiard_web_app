<?php

namespace App\Leagues\Services;

use App\Leagues\DataTransferObjects\PutLeagueDTO;
use App\Leagues\Models\League;
use App\Matches\Enums\GameStatus;
use App\Matches\Models\MatchGame;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class LeaguesService
{
    /**
     * @return Collection<League>
     */
    public function index(bool $shouldBeActive = false): Collection
    {
        return League::query()
            ->when($shouldBeActive, function (Builder $query) {
                $query->whereNotNull('finished_at');
            })
            ->get()
        ;
    }

    public function store(PutLeagueDTO $leagueDTO): League
    {
        return League::create(
            $leagueDTO->toArray(),
        );
    }

    public function update(PutLeagueDTO $leagueDTO, League $league): League
    {
        $league->update($leagueDTO->toArray());

        return League::find($league->id);
    }

    public function destroy(League $league): void
    {
        $league->delete();
    }

    /**
     * @return Collection<MatchGame>
     */
    public function games(League $league): Collection
    {
        return MatchGame::query()
            ->with('firstRating.user', 'secondRating.user', 'game', 'club')
            ->where('league_id', $league->id)
            ->whereIn('status', [GameStatus::IN_PROGRESS, GameStatus::COMPLETED])
            ->orderBy('finished_at', 'desc')
            ->orderBy('created_at', 'desc')
            ->get()
        ;
    }
}
