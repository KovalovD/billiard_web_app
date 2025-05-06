// resources/js/composables/useLeagues.ts
import {del, get, post, put} from '@/lib/apiClient';
import {useApi, useApiAction} from '@/composables/useApi';
import type {League, LeaguePayload, MatchGame, Rating} from '@/types/api';

/**
 * Composable for working with Leagues API endpoints.
 * Centralizes all league-related API calls in one place.
 */
export function useLeagues() {
    // Fetch all leagues
    const fetchLeagues = () => {
        return useApi<League[]>(() => get('/api/leagues'));
    };

    // Fetch a single league by ID
    const fetchLeague = (leagueId: number | string) => {
        return useApi<League>(() => get(`/api/leagues/${leagueId}`));
    };

    const loadUserRating = (leagueId: number | string) => {
        return useApi<Rating>(() => get(`/api/leagues/${leagueId}/load-user-rating`));
    };

    // Create a new league
    const createLeague = () => {
        return useApiAction((payload: LeaguePayload) => post<League>('/api/leagues', payload));
    };

    // Update an existing league
    const updateLeague = (leagueId: number | string) => {
        return useApiAction((payload: LeaguePayload) => put<League>(`/api/leagues/${leagueId}`, payload));
    };

    // Delete a league
    const deleteLeague = (leagueId: number | string) => {
        return useApiAction(() => del(`/api/leagues/${leagueId}`));
    };

    // Fetch players/ratings for a league
    const fetchLeaguePlayers = (leagueId: number | string) => {
        return useApi<Rating[]>(() => get(`/api/leagues/${leagueId}/players`));
    };

    // Fetch matches for a league
    const fetchLeagueMatches = (leagueId: number | string) => {
        return useApi<MatchGame[]>(() => get(`/api/leagues/${leagueId}/games`));
    };

    // Join a league as a player
    const joinLeague = (leagueId: number | string) => {
        return useApiAction(() => post(`/api/leagues/${leagueId}/players/enter`));
    };

    // Leave a league
    const leaveLeague = (leagueId: number | string) => {
        return useApiAction(() => post(`/api/leagues/${leagueId}/players/leave`));
    };

    // Send a match challenge to another player
    const sendChallenge = (leagueId: number | string, playerId: number | string, payload: any) => {
        return useApiAction(() => post(`/api/leagues/${leagueId}/players/${playerId}/send-match-game`, payload));
    };

    // Accept a match invitation
    const acceptMatch = (leagueId: number | string, matchId: number | string) => {
        return useApiAction(() => post(`/api/leagues/${leagueId}/players/match-games/${matchId}/accept`));
    };

    // Decline a match invitation
    const declineMatch = (leagueId: number | string, matchId: number | string) => {
        return useApiAction(() => post(`/api/leagues/${leagueId}/players/match-games/${matchId}/decline`));
    };

    // Submit match results
    const submitMatchResult = (leagueId: number | string, matchId: number | string, payload: any) => {
        return useApiAction(() => post(`/api/leagues/${leagueId}/players/match-games/${matchId}/send-result`, payload));
    };

    // Utility function to check if the current user is in a league
    const isPlayerInLeague = (players: Rating[], userId: number | null): boolean => {
        if (!userId || !players || !players.length) return false;
        return players.some(rating => rating.player?.id === userId);
    };

    return {
        // API composables
        fetchLeagues,
        fetchLeague,
        createLeague,
        updateLeague,
        deleteLeague,
        fetchLeaguePlayers,
        fetchLeagueMatches,
        joinLeague,
        leaveLeague,
        sendChallenge,
        acceptMatch,
        declineMatch,
        submitMatchResult,
        loadUserRating,

        // Utility functions
        isPlayerInLeague,
    };
}
