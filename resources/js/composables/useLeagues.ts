// resources/js/composables/useLeagues.ts
import {useApi, useApiAction} from '@/composables/useApi';
import {del, get, post, put} from '@/lib/apiClient';
import type {League, LeaguePayload, MatchGame, Rating} from '@/types/api';

/**
 * Composable for working with Leagues API endpoints.
 * Centralizes all league-related API calls in one place.
 */
export function useLeagues() {
    // Отримати всі ліги
    const fetchLeagues = () => {
        return useApi<League[]>(() => get('/api/leagues'));
    };

    // Отримати одну лігу за ID
    const fetchLeague = (leagueId: number | string) => {
        return useApi<League>(() => get(`/api/leagues/${leagueId}`));
    };

    const loadUserRating = (leagueId: number | string) => {
        return useApi<Rating>(() => get(`/api/leagues/${leagueId}/load-user-rating`));
    };

    // Створити нову лігу
    const createLeague = () => {
        return useApiAction((payload: LeaguePayload | undefined) => post<League>('/api/leagues', payload));
    };

    // Оновити існуючу лігу
    const updateLeague = (leagueId: number | string) => {
        return useApiAction((payload: LeaguePayload | undefined) => put<League>(`/api/leagues/${leagueId}`, payload));
    };

    // Видалити лігу
    const deleteLeague = (leagueId: number | string) => {
        return useApiAction(() => del(`/api/leagues/${leagueId}`));
    };

    // Отримати гравців/рейтинги ліги
    const fetchLeaguePlayers = (leagueId: number | string) => {
        return useApi<Rating[]>(() => get(`/api/leagues/${leagueId}/players`));
    };

    // Отримати ігри ліги
    const fetchLeagueMatches = (leagueId: number | string) => {
        return useApi<MatchGame[]>(() => get(`/api/leagues/${leagueId}/games`));
    };

    // Приєднатися до ліги як гравець
    const joinLeague = (leagueId: number | string) => {
        return useApiAction(() => post(`/api/leagues/${leagueId}/players/enter`));
    };

    // Покинути лігу
    const leaveLeague = (leagueId: number | string) => {
        return useApiAction(() => post(`/api/leagues/${leagueId}/players/leave`));
    };

    // Надіслати виклик на матч іншому гравцю
    const sendChallenge = (leagueId: number | string, playerId: number | string, payload: any) => {
        return useApiAction(() => post(`/api/leagues/${leagueId}/players/${playerId}/send-match-game`, payload));
    };

    // Прийняти запрошення на матч
    const acceptMatch = (leagueId: number | string, matchId: number | string) => {
        return useApiAction(() => post(`/api/leagues/${leagueId}/players/match-games/${matchId}/accept`));
    };

    // Відхилити запрошення на матч
    const declineMatch = (leagueId: number | string, matchId: number | string) => {
        return useApiAction(() => post(`/api/leagues/${leagueId}/players/match-games/${matchId}/decline`));
    };

    // Надіслати результати матчу
    const submitMatchResult = (leagueId: number | string, matchId: number | string, payload: any) => {
        return useApiAction(() => post(`/api/leagues/${leagueId}/players/match-games/${matchId}/send-result`, payload));
    };

    // Допоміжна функція для перевірки, чи є поточний користувач у лізі
    const isPlayerInLeague = (players: Rating[], userId: number | null): boolean => {
        if (!userId || !players || !players.length) return false;
        return players.some((rating) => rating.player?.id === userId);
    };

    return {
        // API-композабли
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

        // Допоміжні функції
        isPlayerInLeague,
    };
}
