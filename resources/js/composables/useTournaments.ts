// resources/js/composables/useTournaments.ts
import {useApi, useApiAction} from '@/composables/useApi';
import {del, get, post, put} from '@/lib/apiClient';
import type {CreateTournamentPayload, Tournament, TournamentPlayer} from '@/types/api';

export function useTournaments() {
    // Отримати всі турніри
    const fetchTournaments = (filters?: Record<string, any>) => {
        const params = new URLSearchParams(filters || {}).toString();
        const url = params ? `/api/tournaments?${params}` : '/api/tournaments';
        return useApi<Tournament[]>(() => get(url));
    };

    // Отримати майбутні турніри
    const fetchUpcomingTournaments = () => {
        return useApi<Tournament[]>(() => get('/api/tournaments/upcoming'));
    };

    // Отримати активні турніри
    const fetchActiveTournaments = () => {
        return useApi<Tournament[]>(() => get('/api/tournaments/active'));
    };

    // Отримати завершені турніри
    const fetchCompletedTournaments = () => {
        return useApi<Tournament[]>(() => get('/api/tournaments/completed'));
    };

    // Отримати конкретний турнір
    const fetchTournament = (tournamentId: number | string) => {
        return useApi<Tournament>(() => get(`/api/tournaments/${tournamentId}`));
    };

    // Отримати гравців турніру
    const fetchTournamentPlayers = (tournamentId: number | string) => {
        return useApi<TournamentPlayer[]>(() => get(`/api/tournaments/${tournamentId}/players`));
    };

    // Отримати результати турніру
    const fetchTournamentResults = (tournamentId: number | string) => {
        return useApi<any>(() => get(`/api/tournaments/${tournamentId}/results`));
    };

    // Дії адміністратора
    const createTournament = () => {
        return useApiAction((payload: CreateTournamentPayload | undefined) =>
            post<Tournament>('/api/admin/tournaments', payload)
        );
    };

    const updateTournament = (tournamentId: number | string) => {
        return useApiAction((payload: Partial<CreateTournamentPayload> | undefined) =>
            put<Tournament>(`/api/admin/tournaments/${tournamentId}`, payload)
        );
    };

    const deleteTournament = (tournamentId: number | string) => {
        return useApiAction(() => del(`/api/admin/tournaments/${tournamentId}`));
    };

    const addPlayerToTournament = (tournamentId: number | string) => {
        return useApiAction((payload: { user_id: number } | undefined) =>
            post(`/api/admin/tournaments/${tournamentId}/players`, payload)
        );
    };

    const addNewPlayerToTournament = (tournamentId: number | string) => {
        return useApiAction((payload: any | undefined) =>
            post(`/api/admin/tournaments/${tournamentId}/players/new`, payload)
        );
    };

    const removePlayerFromTournament = (tournamentId: number | string, playerId: number | string) => {
        return useApiAction(() =>
            del(`/api/admin/tournaments/${tournamentId}/players/${playerId}`)
        );
    };

    const updateTournamentPlayer = (tournamentId: number | string, playerId: number | string) => {
        return useApiAction((payload: any | undefined) =>
            put(`/api/admin/tournaments/${tournamentId}/players/${playerId}`, payload)
        );
    };

    const setTournamentResults = (tournamentId: number | string) => {
        return useApiAction((payload: { results: any[] } | undefined) =>
            post(`/api/admin/tournaments/${tournamentId}/results`, payload)
        );
    };

    const changeTournamentStatus = (tournamentId: number | string) => {
        return useApiAction((payload: { status: string } | undefined) =>
            post(`/api/admin/tournaments/${tournamentId}/status`, payload)
        );
    };

    const searchUsers = (query: string) => {
        return useApi<any[]>(() => get(`/api/admin/tournaments/search-users?query=${encodeURIComponent(query)}`));
    };

    return {
        // Публічний API
        fetchTournaments,
        fetchUpcomingTournaments,
        fetchActiveTournaments,
        fetchCompletedTournaments,
        fetchTournament,
        fetchTournamentPlayers,
        fetchTournamentResults,

        // API адміністратора
        createTournament,
        updateTournament,
        deleteTournament,
        addPlayerToTournament,
        addNewPlayerToTournament,
        removePlayerFromTournament,
        updateTournamentPlayer,
        setTournamentResults,
        changeTournamentStatus,
        searchUsers,
    };
}
