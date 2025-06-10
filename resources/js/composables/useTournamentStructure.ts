// resources/js/composables/useTournamentStructure.ts
import {useApi, useApiAction} from '@/composables/useApi';
import {apiClient} from '@/lib/apiClient';
import type {
    CreateGroupPayload,
    CreateTeamPayload,
    MatchResultPayload,
    RescheduleMatchPayload,
    TournamentBracket,
    TournamentGroup,
    TournamentMatch,
    TournamentStructureOverview,
    TournamentTeam
} from '@/types/tournament';

export function useTournamentStructure() {
    // Initialize structure
    const initializeStructure = (tournamentId: number | string) => {
        return useApiAction((forceInitialize?: boolean) =>
            apiClient(`/api/admin/tournaments/${tournamentId}/initialize-structure`, {
                method: 'POST',
                data: {force_initialize: forceInitialize}
            })
        );
    };

    // Reset structure
    const resetStructure = (tournamentId: number | string) => {
        return useApiAction(() =>
            apiClient(`/api/admin/tournaments/${tournamentId}/reset-structure`, {
                method: 'POST'
            })
        );
    };

    // Get structure overview
    const fetchStructureOverview = (tournamentId: number | string) => {
        return useApi<TournamentStructureOverview>(() =>
            apiClient(`/api/admin/tournaments/${tournamentId}/structure-overview`)
        );
    };

    // Groups
    const fetchGroups = (tournamentId: number | string) => {
        return useApi<TournamentGroup[]>(() =>
            apiClient(`/api/admin/tournaments/${tournamentId}/groups`)
        );
    };

    const createGroups = (tournamentId: number | string) => {
        return useApiAction((groups?: CreateGroupPayload[]) =>
            apiClient(`/api/admin/tournaments/${tournamentId}/groups`, {
                method: 'POST',
                data: {groups}
            })
        );
    };

    const assignPlayersToGroups = (tournamentId: number | string) => {
        return useApiAction((payload?: { assignment_method?: string; assignments?: any[] }) =>
            apiClient(`/api/admin/tournaments/${tournamentId}/assign-players-to-groups`, {
                method: 'POST',
                data: payload
            })
        );
    };

    // Teams
    const fetchTeams = (tournamentId: number | string) => {
        return useApi<TournamentTeam[]>(() =>
            apiClient(`/api/admin/tournaments/${tournamentId}/teams`)
        );
    };

    const createTeam = (tournamentId: number | string) => {
        return useApiAction((payload?: CreateTeamPayload) =>
            apiClient(`/api/admin/tournaments/${tournamentId}/teams`, {
                method: 'POST',
                data: payload
            })
        );
    };

    const updateTeam = (tournamentId: number | string, teamId: number | string) => {
        return useApiAction((payload?: CreateTeamPayload) =>
            apiClient(`/api/admin/tournaments/${tournamentId}/teams/${teamId}`, {
                method: 'PUT',
                data: payload
            })
        );
    };

    const deleteTeam = (tournamentId: number | string, teamId: number | string) => {
        return useApiAction(() =>
            apiClient(`/api/admin/tournaments/${tournamentId}/teams/${teamId}`, {
                method: 'DELETE'
            })
        );
    };

    // Brackets
    const fetchBrackets = (tournamentId: number | string) => {
        return useApi<TournamentBracket[]>(() =>
            apiClient(`/api/admin/tournaments/${tournamentId}/brackets`)
        );
    };

    const createBracketMatches = (tournamentId: number | string) => {
        return useApiAction(() =>
            apiClient(`/api/admin/tournaments/${tournamentId}/create-bracket-matches`, {
                method: 'POST'
            })
        );
    };

    // Matches
    const fetchMatches = (tournamentId: number | string, filters?: any) => {
        return useApi<TournamentMatch[]>(() => {
            const params = new URLSearchParams(filters || {}).toString();
            const url = params
                ? `/api/admin/tournaments/${tournamentId}/matches?${params}`
                : `/api/admin/tournaments/${tournamentId}/matches`;
            return apiClient(url);
        });
    };

    const fetchMatchSchedule = (tournamentId: number | string) => {
        return useApi<any>(() =>
            apiClient(`/api/admin/tournaments/${tournamentId}/matches/schedule`)
        );
    };

    const updateMatch = (tournamentId: number | string, matchId: number | string) => {
        return useApiAction((payload: any) =>
            apiClient(`/api/admin/tournaments/${tournamentId}/matches/${matchId}`, {
                method: 'PUT',
                data: payload
            })
        );
    };

    const enterMatchResult = (tournamentId: number | string, matchId: number | string) => {
        return useApiAction((payload?: MatchResultPayload) =>
            apiClient(`/api/admin/tournaments/${tournamentId}/matches/${matchId}/result`, {
                method: 'POST',
                data: payload
            })
        );
    };

    const startMatch = (tournamentId: number | string, matchId: number | string) => {
        return useApiAction(() =>
            apiClient(`/api/admin/tournaments/${tournamentId}/matches/${matchId}/start`, {
                method: 'POST'
            })
        );
    };

    const cancelMatch = (tournamentId: number | string, matchId: number | string) => {
        return useApiAction((reason?: string) =>
            apiClient(`/api/admin/tournaments/${tournamentId}/matches/${matchId}/cancel`, {
                method: 'POST',
                data: {reason}
            })
        );
    };

    const rescheduleMatch = (tournamentId: number | string, matchId: number | string) => {
        return useApiAction((payload?: RescheduleMatchPayload) =>
            apiClient(`/api/admin/tournaments/${tournamentId}/matches/${matchId}/reschedule`, {
                method: 'POST',
                data: payload
            })
        );
    };

    const bulkRescheduleMatches = (tournamentId: number | string) => {
        return useApiAction((matches?: any[]) =>
            apiClient(`/api/admin/tournaments/${tournamentId}/matches/bulk-reschedule`, {
                method: 'POST',
                data: {matches}
            })
        );
    };

    // Public API for viewing structure
    const fetchPublicStructure = (tournamentId: number | string) => {
        return useApi<any>(() =>
            apiClient(`/api/tournaments/${tournamentId}/structure`)
        );
    };

    const fetchPublicGroups = (tournamentId: number | string) => {
        return useApi<TournamentGroup[]>(() =>
            apiClient(`/api/tournaments/${tournamentId}/groups`)
        );
    };

    const fetchPublicBrackets = (tournamentId: number | string) => {
        return useApi<TournamentBracket[]>(() =>
            apiClient(`/api/tournaments/${tournamentId}/brackets`)
        );
    };

    const fetchPublicMatches = (tournamentId: number | string, filters?: any) => {
        return useApi<TournamentMatch[]>(() => {
            const params = new URLSearchParams(filters || {}).toString();
            const url = params
                ? `/api/tournaments/${tournamentId}/matches?${params}`
                : `/api/tournaments/${tournamentId}/matches`;
            return apiClient(url);
        });
    };

    const fetchPublicSchedule = (tournamentId: number | string) => {
        return useApi<any>(() =>
            apiClient(`/api/tournaments/${tournamentId}/schedule`)
        );
    };

    return {
        // Admin API
        initializeStructure,
        resetStructure,
        fetchStructureOverview,
        fetchGroups,
        createGroups,
        assignPlayersToGroups,
        fetchTeams,
        createTeam,
        updateTeam,
        deleteTeam,
        fetchBrackets,
        createBracketMatches,
        fetchMatches,
        fetchMatchSchedule,
        updateMatch,
        enterMatchResult,
        startMatch,
        cancelMatch,
        rescheduleMatch,
        bulkRescheduleMatches,

        // Public API
        fetchPublicStructure,
        fetchPublicGroups,
        fetchPublicBrackets,
        fetchPublicMatches,
        fetchPublicSchedule
    };
}
