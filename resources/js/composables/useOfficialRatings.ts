// resources/js/composables/useOfficialRatings.ts
import {useApi, useApiAction} from '@/composables/useApi';
import {del, get, post, put} from '@/lib/apiClient';
import type {OfficialRating, OfficialRatingPlayer, CreateOfficialRatingPayload} from '@/types/api';

export function useOfficialRatings() {
    // Отримати всі офіційні рейтинги
    const fetchOfficialRatings = (filters?: Record<string, any>) => {
        const params = new URLSearchParams(filters || {}).toString();
        const url = params ? `/api/official-ratings?${params}` : '/api/official-ratings';
        return useApi<OfficialRating[]>(() => get(url));
    };

    // Отримати активні рейтинги
    const fetchActiveRatings = () => {
        return useApi<OfficialRating[]>(() => get('/api/official-ratings/active'));
    };

    // Отримати конкретний рейтинг
    const fetchOfficialRating = (ratingId: number | string, includeTopPlayers = false) => {
        const params = includeTopPlayers ? '?include_top_players=true' : '';
        return useApi<OfficialRating>(() => get(`/api/official-ratings/${ratingId}${params}`));
    };

    // Отримати гравців рейтингу
    const fetchRatingPlayers = (ratingId: number | string, filters?: Record<string, any>) => {
        const params = new URLSearchParams(filters || {}).toString();
        const url = params ?
            `/api/official-ratings/${ratingId}/players?${params}` :
            `/api/official-ratings/${ratingId}/players`;
        return useApi<OfficialRatingPlayer[]>(() => get(url));
    };

    // Отримати турніри рейтингу
    const fetchRatingTournaments = (ratingId: number | string) => {
        return useApi<any[]>(() => get(`/api/official-ratings/${ratingId}/tournaments`));
    };

    // Отримати топ гравців
    const fetchTopPlayers = (ratingId: number | string, limit = 10) => {
        return useApi<OfficialRatingPlayer[]>(() =>
            get(`/api/official-ratings/${ratingId}/top-players?limit=${limit}`)
        );
    };

    // Отримати інформацію про рейтинг гравця
    const fetchPlayerRating = (ratingId: number | string, userId: number) => {
        return useApi<OfficialRatingPlayer>(() =>
            get(`/api/official-ratings/${ratingId}/players/${userId}`)
        );
    };

    // Дії адміністратора
    const createOfficialRating = () => {
        return useApiAction((payload: CreateOfficialRatingPayload | undefined) =>
            post<OfficialRating>('/api/admin/official-ratings', payload)
        );
    };

    const updateOfficialRating = (ratingId: number | string) => {
        return useApiAction((payload: Partial<CreateOfficialRatingPayload> | undefined) =>
            put<OfficialRating>(`/api/admin/official-ratings/${ratingId}`, payload)
        );
    };

    const deleteOfficialRating = (ratingId: number | string) => {
        return useApiAction(() => del(`/api/admin/official-ratings/${ratingId}`));
    };

    const addTournamentToRating = (ratingId: number | string) => {
        return useApiAction((payload: {
                tournament_id: number;
                rating_coefficient?: number;
                is_counting?: boolean;
            } | undefined) =>
                post(`/api/admin/official-ratings/${ratingId}/tournaments`, payload)
        );
    };

    const removeTournamentFromRating = (ratingId: number | string, tournamentId: number | string) => {
        return useApiAction(() =>
            del(`/api/admin/official-ratings/${ratingId}/tournaments/${tournamentId}`)
        );
    };

    const addPlayerToRating = (ratingId: number | string) => {
        return useApiAction((payload: {
                user_id: number;
                initial_rating?: number;
            } | undefined) =>
                post(`/api/admin/official-ratings/${ratingId}/players`, payload)
        );
    };

    const removePlayerFromRating = (ratingId: number | string, userId: number) => {
        return useApiAction(() =>
            del(`/api/admin/official-ratings/${ratingId}/players/${userId}`)
        );
    };

    const recalculateRatingPositions = (ratingId: number | string) => {
        return useApiAction(() =>
            post(`/api/admin/official-ratings/${ratingId}/recalculate`)
        );
    };

    const updateRatingFromTournament = (ratingId: number | string, tournamentId: number | string) => {
        return useApiAction(() =>
            post(`/api/admin/official-ratings/${ratingId}/update-from-tournament/${tournamentId}`)
        );
    };

    return {
        // Публічний API
        fetchOfficialRatings,
        fetchActiveRatings,
        fetchOfficialRating,
        fetchRatingPlayers,
        fetchRatingTournaments,
        fetchTopPlayers,
        fetchPlayerRating,

        // API адміністратора
        createOfficialRating,
        updateOfficialRating,
        deleteOfficialRating,
        addTournamentToRating,
        removeTournamentFromRating,
        addPlayerToRating,
        removePlayerFromRating,
        recalculateRatingPositions,
        updateRatingFromTournament,
    };
}
