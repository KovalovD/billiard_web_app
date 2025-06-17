<?php

namespace App\Tournaments\Http\Controllers;

use App\Tournaments\Models\Tournament;
use Inertia\Inertia;
use Inertia\Response;

/**
 * @group Admin Tournament Groups
 */
readonly class AdminTournamentGroupsController
{
    /**
     * Show groups management page
     * @admin
     */
    public function index(Tournament $tournament): Response
    {
        return Inertia::render('Admin/Tournaments/Groups', [
            'tournamentId' => $tournament->id,
        ]);
    }
}
