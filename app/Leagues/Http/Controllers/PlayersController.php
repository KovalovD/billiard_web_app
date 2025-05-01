<?php

namespace App\Leagues\Http\Controllers;

use App\Leagues\Models\League;
use App\Leagues\Services\RatingService;
use Auth;
use Throwable;

/**
 * @group Players
 */
readonly class PlayersController
{

    public function __construct(
        private RatingService $ratingService,
    ) {
    }

    /**
     * Enter to the League as a player
     * @authenticated
     * @throws Throwable
     */
    public function enter(League $league): bool
    {
        return $this->ratingService->addPlayer($league, Auth::user());
    }

    /**
     * Leave the League as a player
     * @authenticated
     * @throws Throwable
     */
    public function leave(League $league): true
    {
        $this->ratingService->disablePlayer($league, Auth::user());

        return true;
    }
}
