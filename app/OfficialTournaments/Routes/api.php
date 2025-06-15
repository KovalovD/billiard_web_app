<?php

use App\Core\Http\Middleware\AdminMiddleware;
use App\OfficialTournaments\Http\Controllers\MatchController;
use App\OfficialTournaments\Http\Controllers\ParticipantController;
use App\OfficialTournaments\Http\Controllers\StageController;
use App\OfficialTournaments\Http\Controllers\TournamentController;
use Illuminate\Support\Facades\Route;

// Tournament routes
Route::middleware(['api', 'auth:sanctum', AdminMiddleware::class])->group(function () {

    // Tournament management
    Route::apiResource('tournaments', TournamentController::class);
    Route::post('tournaments/{tournament}/duplicate', [TournamentController::class, 'duplicate']);
    Route::get('tournaments/{tournament}/statistics', [TournamentController::class, 'statistics']);

    // Stage management
    Route::apiResource('tournaments.stages', StageController::class)->shallow();
    Route::post('tournaments/{tournament}/stages/{stage}/generate-bracket',
        [StageController::class, 'generateBracket']);
    Route::get('tournaments/{tournament}/stages/{stage}/standings', [StageController::class, 'standings']);
    Route::post('tournaments/{tournament}/stages/{stage}/reset', [StageController::class, 'reset']);

    // Participant management
    Route::apiResource('tournaments.stages.participants', ParticipantController::class)
        ->except(['show', 'update'])
        ->shallow()
    ;
    Route::post('tournaments/{tournament}/stages/{stage}/participants/batch',
        [ParticipantController::class, 'batchAdd']);
    Route::post('tournaments/{tournament}/stages/{stage}/participants/seeding',
        [ParticipantController::class, 'applySeedingMethod']);
    Route::get('tournaments/{tournament}/stages/{stage}/participants/preview-groups',
        [ParticipantController::class, 'previewGroups']);

    // Match management
    Route::get('tournaments/{tournament}/matches', [MatchController::class, 'index']);
    Route::get('tournaments/{tournament}/matches/{match}', [MatchController::class, 'show']);
    Route::put('tournaments/{tournament}/matches/{match}/score', [MatchController::class, 'updateScore']);
    Route::post('tournaments/{tournament}/matches/{match}/schedule', [MatchController::class, 'schedule']);
    Route::post('tournaments/{tournament}/matches/{match}/walkover', [MatchController::class, 'walkover']);
    Route::get('tournaments/{tournament}/matches/{match}/statistics', [MatchController::class, 'statistics']);
    Route::post('tournaments/{tournament}/auto-schedule', [MatchController::class, 'autoSchedule']);
});

// Public routes (no auth required)
// Public tournament viewing
Route::get('tournaments', [TournamentController::class, 'index']);
Route::get('tournaments/{tournament}', [TournamentController::class, 'show']);
Route::get('tournaments/{tournament}/matches', [MatchController::class, 'index']);
Route::get('tournaments/{tournament}/stages/{stage}/standings', [StageController::class, 'standings']);
