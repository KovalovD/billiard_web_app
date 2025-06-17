<?php

namespace App\Tournaments\Http\Controllers;

use App\Tournaments\Models\Tournament;
use Inertia\Inertia;
use Inertia\Response;

/**
 * @group Admin Tournament Bracket
 */
readonly class AdminTournamentBracketController
{
    /**
     * Show bracket management page
     * @admin
     */
    public function index(Tournament $tournament): Response
    {
        return Inertia::render('Admin/Tournaments/Bracket', [
            'tournamentId' => $tournament->id,
        ]);
    }
}
