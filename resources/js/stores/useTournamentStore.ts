import {defineStore} from 'pinia';
import {computed, ref} from 'vue';
import {apiClient} from '@/lib/apiClient';
import type {
    ApplySeedingPayload,
    CreateStagePayload,
    CreateTournamentPayload,
    GenerateBracketPayload,
    OfficialMatch,
    OfficialParticipant,
    OfficialPoolTable,
    OfficialStage,
    OfficialTeam,
    OfficialTournament,
    ScheduleMatchPayload,
    UpdateMatchScorePayload,
    UpdateTournamentPayload
} from '@/types/tournament';

// Type definitions
export interface TournamentState {
    tournaments: OfficialTournament[];
    currentTournament: OfficialTournament | null;
    currentStage: OfficialStage | null;
    stages: OfficialStage[];
    matches: OfficialMatch[];
    participants: OfficialParticipant[];
    teams: OfficialTeam[];
    tables: OfficialPoolTable[];
    isLoading: boolean;
    error: string | null;
}

export interface TournamentFilters {
    status?: 'upcoming' | 'ongoing' | 'completed';
    discipline?: string;
    cityId?: number;
    dateFrom?: string;
    dateTo?: string;
}

export interface StandingsEntry {
    position: number;
    participant: OfficialParticipant;
    matches_played: number;
    matches_won: number;
    matches_lost: number;
    sets_won: number;
    sets_lost: number;
    sets_difference: number;
    games_won: number;
    games_lost: number;
    games_difference: number;
    points: number;
    win_percentage: number;
}

export const useTournamentStore = defineStore('tournament', () => {
    // State
    const tournaments = ref<OfficialTournament[]>([]);
    const currentTournament = ref<OfficialTournament | null>(null);
    const currentStage = ref<OfficialStage | null>(null);
    const stages = ref<OfficialStage[]>([]);
    const matches = ref<OfficialMatch[]>([]);
    const participants = ref<OfficialParticipant[]>([]);
    const teams = ref<OfficialTeam[]>([]);
    const tables = ref<OfficialPoolTable[]>([]);
    const isLoading = ref(false);
    const error = ref<string | null>(null);

    // Computed
    const upcomingTournaments = computed(() =>
        tournaments.value.filter(t => t.status === 'upcoming')
    );

    const ongoingTournaments = computed(() =>
        tournaments.value.filter(t => t.status === 'ongoing')
    );

    const completedTournaments = computed(() =>
        tournaments.value.filter(t => t.status === 'completed')
    );

    const currentStageMatches = computed(() => {
        if (!currentStage.value) return [];
        return matches.value.filter(m => m.stage_id === currentStage.value!.id);
    });

    const currentStageParticipants = computed(() => {
        if (!currentStage.value) return [];
        return participants.value.filter(p => p.stage_id === currentStage.value!.id);
    });

    const pendingMatches = computed(() =>
        matches.value.filter(m => m.status === 'pending')
    );

    const ongoingMatches = computed(() =>
        matches.value.filter(m => m.status === 'ongoing')
    );

    const scheduledMatches = computed(() =>
        matches.value.filter(m => m.scheduled_at !== null)
    );

    const unscheduledMatches = computed(() =>
        matches.value.filter(m => m.scheduled_at === null && m.status === 'pending')
    );

    // Actions - Tournament Management
    async function fetchTournaments(filters?: TournamentFilters) {
        isLoading.value = true;
        error.value = null;

        try {
            const params = new URLSearchParams();
            if (filters?.status) params.append('status', filters.status);
            if (filters?.discipline) params.append('discipline', filters.discipline);
            if (filters?.cityId) params.append('city_id', filters.cityId.toString());
            if (filters?.dateFrom) params.append('date_from', filters.dateFrom);
            if (filters?.dateTo) params.append('date_to', filters.dateTo);

            tournaments.value = await apiClient<OfficialTournament[]>(
                `/api/tournaments?${params.toString()}`
            );
        } catch (err: any) {
            error.value = err.message || 'Failed to load tournaments';
            throw err;
        } finally {
            isLoading.value = false;
        }
    }

    async function fetchTournament(id: number | string) {
        isLoading.value = true;
        error.value = null;

        try {
            const response = await apiClient<OfficialTournament>(
                `/api/tournaments/${id}`
            );

            currentTournament.value = response;

            // Update related data if included
            if (response.stages) {
                stages.value = response.stages;
            }
            if (response.pool_tables) {
                tables.value = response.pool_tables;
            }

            return response;
        } catch (err: any) {
            error.value = err.message || 'Failed to load tournament';
            throw err;
        } finally {
            isLoading.value = false;
        }
    }

    async function createTournament(payload: CreateTournamentPayload) {
        isLoading.value = true;
        error.value = null;

        try {
            const response = await apiClient<OfficialTournament>(
                '/api/tournaments',
                {
                    method: 'POST',
                    data: payload
                }
            );

            tournaments.value.push(response);
            return response;
        } catch (err: any) {
            error.value = err.message || 'Failed to create tournament';
            throw err;
        } finally {
            isLoading.value = false;
        }
    }

    async function updateTournament(id: number | string, payload: UpdateTournamentPayload) {
        isLoading.value = true;
        error.value = null;

        try {
            const response = await apiClient<OfficialTournament>(
                `/api/tournaments/${id}`,
                {
                    method: 'PUT',
                    data: payload
                }
            );

            // Update in list
            const index = tournaments.value.findIndex(t => t.id === response.id);
            if (index !== -1) {
                tournaments.value[index] = response;
            }

            // Update current if same
            if (currentTournament.value?.id === response.id) {
                currentTournament.value = response;
            }

            return response;
        } catch (err: any) {
            error.value = err.message || 'Failed to update tournament';
            throw err;
        } finally {
            isLoading.value = false;
        }
    }

    async function deleteTournament(id: number | string) {
        isLoading.value = true;
        error.value = null;

        try {
            await apiClient(`/api/tournaments/${id}`, {method: 'DELETE'});

            // Remove from list
            tournaments.value = tournaments.value.filter(t => t.id !== id);

            // Clear current if same
            if (currentTournament.value?.id === id) {
                currentTournament.value = null;
            }
        } catch (err: any) {
            error.value = err.message || 'Failed to delete tournament';
            throw err;
        } finally {
            isLoading.value = false;
        }
    }

    async function duplicateTournament(id: number | string) {
        isLoading.value = true;
        error.value = null;

        try {
            const response = await apiClient<OfficialTournament>(
                `/api/tournaments/${id}/duplicate`,
                {method: 'POST'}
            );

            tournaments.value.push(response);
            return response;
        } catch (err: any) {
            error.value = err.message || 'Failed to duplicate tournament';
            throw err;
        } finally {
            isLoading.value = false;
        }
    }

    // Actions - Stage Management
    async function fetchStages(tournamentId: number | string) {
        isLoading.value = true;
        error.value = null;

        try {
            const response = await apiClient<OfficialStage[]>(
                `/api/tournaments/${tournamentId}/stages`
            );

            stages.value = response;
            return response;
        } catch (err: any) {
            error.value = err.message || 'Failed to load stages';
            throw err;
        } finally {
            isLoading.value = false;
        }
    }

    async function fetchStage(tournamentId: number | string, stageId: number | string) {
        isLoading.value = true;
        error.value = null;

        try {
            const response = await apiClient<OfficialStage>(
                `/api/tournaments/${tournamentId}/stages/${stageId}`
            );

            currentStage.value = response;

            // Update related data if included
            if (response.participants) {
                participants.value = response.participants;
            }
            if (response.matches) {
                matches.value = response.matches;
            }

            return response;
        } catch (err: any) {
            error.value = err.message || 'Failed to load stage';
            throw err;
        } finally {
            isLoading.value = false;
        }
    }

    async function createStage(tournamentId: number | string, payload: CreateStagePayload) {
        isLoading.value = true;
        error.value = null;

        try {
            const response = await apiClient<OfficialStage>(
                `/api/tournaments/${tournamentId}/stages`,
                {
                    method: 'POST',
                    data: payload
                }
            );

            stages.value.push(response);
            return response;
        } catch (err: any) {
            error.value = err.message || 'Failed to create stage';
            throw err;
        } finally {
            isLoading.value = false;
        }
    }

    async function generateBracket(
        tournamentId: number | string,
        stageId: number | string,
        options?: GenerateBracketPayload
    ) {
        isLoading.value = true;
        error.value = null;

        //data used
        try {
            const response = await apiClient<{
                message: string;
                data: { matches_created: number; matches: OfficialMatch[] }
            }>(
                `/api/tournaments/${tournamentId}/stages/${stageId}/generate-bracket`,
                {
                    method: 'POST',
                    data: options || {}
                }
            );

            // Add new matches to store
            matches.value = [...matches.value, ...response.data.matches];

            return response.data;
        } catch (err: any) {
            error.value = err.message || 'Failed to generate bracket';
            throw err;
        } finally {
            isLoading.value = false;
        }
    }

    async function fetchStandings(tournamentId: number | string, stageId: number | string) {
        isLoading.value = true;
        error.value = null;

        try {
            const response = await apiClient<{
                data: Array<{ group: string; standings: StandingsEntry[] }>
            }>(
                `/api/tournaments/${tournamentId}/stages/${stageId}/standings`
            );

            return response.data;
        } catch (err: any) {
            error.value = err.message || 'Failed to load standings';
            throw err;
        } finally {
            isLoading.value = false;
        }
    }

    async function resetStage(tournamentId: number | string, stageId: number | string) {
        isLoading.value = true;
        error.value = null;

        try {
            await apiClient(
                `/api/tournaments/${tournamentId}/stages/${stageId}/reset`,
                {method: 'POST'}
            );

            // Clear matches for this stage
            matches.value = matches.value.filter(m => m.stage_id !== stageId);
        } catch (err: any) {
            error.value = err.message || 'Failed to reset stage';
            throw err;
        } finally {
            isLoading.value = false;
        }
    }

    // Actions - Participant Management
    async function fetchParticipants(tournamentId: number | string, stageId: number | string) {
        isLoading.value = true;
        error.value = null;

        try {
            const response = await apiClient<OfficialParticipant[]>(
                `/api/tournaments/${tournamentId}/stages/${stageId}/participants`
            );

            participants.value = response;
            return response;
        } catch (err: any) {
            error.value = err.message || 'Failed to load participants';
            throw err;
        } finally {
            isLoading.value = false;
        }
    }

    async function addParticipant(
        tournamentId: number | string,
        stageId: number | string,
        payload: { user_id?: number; team_id?: number; seed?: number; rating_snapshot?: number }
    ) {
        isLoading.value = true;
        error.value = null;

        try {
            const response = await apiClient<OfficialParticipant>(
                `/api/tournaments/${tournamentId}/stages/${stageId}/participants`,
                {
                    method: 'POST',
                    data: payload
                }
            );

            participants.value.push(response);
            return response;
        } catch (err: any) {
            error.value = err.message || 'Failed to add participant';
            throw err;
        } finally {
            isLoading.value = false;
        }
    }

    async function removeParticipant(
        tournamentId: number | string,
        stageId: number | string,
        participantId: number | string
    ) {
        isLoading.value = true;
        error.value = null;

        try {
            await apiClient(
                `/api/tournaments/${tournamentId}/stages/${stageId}/participants/${participantId}`,
                {method: 'DELETE'}
            );

            participants.value = participants.value.filter(p => p.id !== participantId);
        } catch (err: any) {
            error.value = err.message || 'Failed to remove participant';
            throw err;
        } finally {
            isLoading.value = false;
        }
    }

    async function batchAddParticipants(
        tournamentId: number | string,
        stageId: number | string,
        userIds: number[]
    ) {
        isLoading.value = true;
        error.value = null;

        //data used
        try {
            const response = await apiClient<{
                message: string;
                data: { added: number; skipped: number; participant_ids: number[] }
            }>(
                `/api/tournaments/${tournamentId}/stages/${stageId}/participants/batch`,
                {
                    method: 'POST',
                    data: {user_ids: userIds}
                }
            );

            // Refresh participants after batch add
            await fetchParticipants(tournamentId, stageId);

            return response.data;
        } catch (err: any) {
            error.value = err.message || 'Failed to batch add participants';
            throw err;
        } finally {
            isLoading.value = false;
        }
    }

    async function applySeeding(
        tournamentId: number | string,
        stageId: number | string,
        payload: ApplySeedingPayload
    ) {
        isLoading.value = true;
        error.value = null;

        //data used
        try {
            const response = await apiClient<{
                message: string;
                data: { method: string; participants: OfficialParticipant[] }
            }>(
                `/api/tournaments/${tournamentId}/stages/${stageId}/participants/seeding`,
                {
                    method: 'POST',
                    data: payload
                }
            );

            // Update participants with new seeding
            participants.value = response.data.participants;

            return response.data;
        } catch (err: any) {
            error.value = err.message || 'Failed to apply seeding';
            throw err;
        } finally {
            isLoading.value = false;
        }
    }

    // Actions - Match Management
    async function fetchMatches(tournamentId: number | string, filters?: any) {
        isLoading.value = true;
        error.value = null;

        try {
            const params = new URLSearchParams();
            if (filters?.stage_id) params.append('stage_id', filters.stage_id.toString());
            if (filters?.status) params.append('status', filters.status);
            if (filters?.round) params.append('round', filters.round.toString());
            if (filters?.bracket) params.append('bracket', filters.bracket);
            if (filters?.date) params.append('date', filters.date);

            const response = await apiClient<OfficialMatch[]>(
                `/api/tournaments/${tournamentId}/matches?${params.toString()}`
            );

            matches.value = response;
            return response;
        } catch (err: any) {
            error.value = err.message || 'Failed to load matches';
            throw err;
        } finally {
            isLoading.value = false;
        }
    }

    async function fetchMatch(tournamentId: number | string, matchId: number | string) {
        isLoading.value = true;
        error.value = null;

        try {
            const response = await apiClient<OfficialMatch>(
                `/api/tournaments/${tournamentId}/matches/${matchId}`
            );

            // Update match in list
            const index = matches.value.findIndex(m => m.id === response.id);
            if (index !== -1) {
                matches.value[index] = response;
            } else {
                matches.value.push(response);
            }

            return response;
        } catch (err: any) {
            error.value = err.message || 'Failed to load match';
            throw err;
        } finally {
            isLoading.value = false;
        }
    }

    async function updateMatchScore(
        tournamentId: number | string,
        matchId: number | string,
        payload: UpdateMatchScorePayload
    ) {
        isLoading.value = true;
        error.value = null;

        try {
            const response = await apiClient<OfficialMatch>(
                `/api/tournaments/${tournamentId}/matches/${matchId}/score`,
                {
                    method: 'PUT',
                    data: payload
                }
            );

            // Update match in list
            const index = matches.value.findIndex(m => m.id === response.id);
            if (index !== -1) {
                matches.value[index] = response;
            }

            return response;
        } catch (err: any) {
            error.value = err.message || 'Failed to update match score';
            throw err;
        } finally {
            isLoading.value = false;
        }
    }

    async function scheduleMatch(
        tournamentId: number | string,
        matchId: number | string,
        payload: ScheduleMatchPayload
    ) {
        isLoading.value = true;
        error.value = null;

        try {
            const response = await apiClient<OfficialMatch>(
                `/api/tournaments/${tournamentId}/matches/${matchId}/schedule`,
                {
                    method: 'POST',
                    data: payload
                }
            );

            // Update match in list
            const index = matches.value.findIndex(m => m.id === response.id);
            if (index !== -1) {
                matches.value[index] = response;
            }

            return response;
        } catch (err: any) {
            error.value = err.message || 'Failed to schedule match';
            throw err;
        } finally {
            isLoading.value = false;
        }
    }

    async function setWalkover(
        tournamentId: number | string,
        matchId: number | string,
        winnerId: number
    ) {
        isLoading.value = true;
        error.value = null;

        try {
            const response = await apiClient<OfficialMatch>(
                `/api/tournaments/${tournamentId}/matches/${matchId}/walkover`,
                {
                    method: 'POST',
                    data: {winner_id: winnerId}
                }
            );

            // Update match in list
            const index = matches.value.findIndex(m => m.id === response.id);
            if (index !== -1) {
                matches.value[index] = response;
            }

            return response;
        } catch (err: any) {
            error.value = err.message || 'Failed to set walkover';
            throw err;
        } finally {
            isLoading.value = false;
        }
    }

    async function autoSchedule(
        tournamentId: number | string,
        payload: {
            stage_id?: number;
            start_time: string;
            match_duration?: number;
            rest_time?: number
        }
    ) {
        isLoading.value = true;
        error.value = null;

        //data used
        try {
            const response = await apiClient<{
                message: string;
                data: { matches_scheduled: number; matches: OfficialMatch[] }
            }>(
                `/api/tournaments/${tournamentId}/auto-schedule`,
                {
                    method: 'POST',
                    data: payload
                }
            );

            // Update scheduled matches
            response.data.matches.forEach(match => {
                const index = matches.value.findIndex(m => m.id === match.id);
                if (index !== -1) {
                    matches.value[index] = match;
                }
            });

            return response.data;
        } catch (err: any) {
            error.value = err.message || 'Failed to auto-schedule';
            throw err;
        } finally {
            isLoading.value = false;
        }
    }

    // WebSocket event handlers
    function handleMatchUpdate(matchData: OfficialMatch) {
        const index = matches.value.findIndex(m => m.id === matchData.id);
        if (index !== -1) {
            matches.value[index] = matchData;
        }
    }

    function handleScheduleUpdate(data: { match_id: number; table_id: number; start_at: string }) {
        const match = matches.value.find(m => m.id === data.match_id);
        if (match) {
            match.table_id = data.table_id;
            match.scheduled_at = data.start_at;
        }
    }

    function handleParticipantUpdate(participantData: OfficialParticipant) {
        const index = participants.value.findIndex(p => p.id === participantData.id);
        if (index !== -1) {
            participants.value[index] = participantData;
        }
    }

    // Utility functions
    function clearCurrentTournament() {
        currentTournament.value = null;
        currentStage.value = null;
        stages.value = [];
        matches.value = [];
        participants.value = [];
        teams.value = [];
        tables.value = [];
    }

    function getMatchById(matchId: number): OfficialMatch | undefined {
        return matches.value.find(m => m.id === matchId);
    }

    function getParticipantById(participantId: number): OfficialParticipant | undefined {
        return participants.value.find(p => p.id === participantId);
    }

    function getStageById(stageId: number): OfficialStage | undefined {
        return stages.value.find(s => s.id === stageId);
    }

    return {
        // State
        tournaments,
        currentTournament,
        currentStage,
        stages,
        matches,
        participants,
        teams,
        tables,
        isLoading,
        error,

        // Computed
        upcomingTournaments,
        ongoingTournaments,
        completedTournaments,
        currentStageMatches,
        currentStageParticipants,
        pendingMatches,
        ongoingMatches,
        scheduledMatches,
        unscheduledMatches,

        // Actions - Tournament
        fetchTournaments,
        fetchTournament,
        createTournament,
        updateTournament,
        deleteTournament,
        duplicateTournament,

        // Actions - Stage
        fetchStages,
        fetchStage,
        createStage,
        generateBracket,
        fetchStandings,
        resetStage,

        // Actions - Participants
        fetchParticipants,
        addParticipant,
        removeParticipant,
        batchAddParticipants,
        applySeeding,

        // Actions - Matches
        fetchMatches,
        fetchMatch,
        updateMatchScore,
        scheduleMatch,
        setWalkover,
        autoSchedule,

        // WebSocket handlers
        handleMatchUpdate,
        handleScheduleUpdate,
        handleParticipantUpdate,

        // Utilities
        clearCurrentTournament,
        getMatchById,
        getParticipantById,
        getStageById
    };
});
