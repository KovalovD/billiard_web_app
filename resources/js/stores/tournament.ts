import {defineStore} from 'pinia';
import {computed, ref} from 'vue';
import tournamentService from '@/services/TournamentService';
import type {Tournament, TournamentPlayer} from '@/types/api';

export const useTournamentStore = defineStore('tournament', () => {
    // State
    const currentTournament = ref<Tournament | null>(null);
    const tournaments = ref<Tournament[]>([]);
    const players = ref<TournamentPlayer[]>([]);
    const matches = ref<any[]>([]);
    const groups = ref<any[]>([]);
    const bracket = ref<any[]>([]);

    // Loading states
    const isLoading = ref(false);
    const isSaving = ref(false);
    const isGenerating = ref(false);
    const error = ref<string | null>(null);

    // Getters
    const confirmedPlayers = computed(() =>
        players.value.filter(p => p.status === 'confirmed')
    );

    const pendingPlayers = computed(() =>
        players.value.filter(p => p.status === 'applied')
    );

    const scheduledMatches = computed(() =>
        matches.value.filter(m => m.status === 'scheduled')
    );

    const completedMatches = computed(() =>
        matches.value.filter(m => m.status === 'completed')
    );

    const groupStandings = computed(() => {
        return groups.value.map(group => ({
            ...group,
            standings: group.standings?.sort((a: any, b: any) => {
                if (a.points !== b.points) return b.points - a.points;
                if (a.frame_difference !== b.frame_difference) return b.frame_difference - a.frame_difference;
                return b.frames_won - a.frames_won;
            }) || []
        }));
    });

    const bracketRounds = computed(() => {
        if (!bracket.value.length) return [];

        const rounds = new Map<number, any[]>();
        bracket.value.forEach(node => {
            if (!rounds.has(node.round)) {
                rounds.set(node.round, []);
            }
            rounds.get(node.round)!.push(node);
        });

        return Array.from(rounds.entries()).map(([round, nodes]) => ({
            round,
            nodes: nodes.sort((a, b) => a.position - b.position)
        }));
    });

    // Actions
    const fetchTournaments = async (filters?: Record<string, any>) => {
        isLoading.value = true;
        error.value = null;

        try {
            tournaments.value = await tournamentService.fetchTournaments(filters);
        } catch (err: any) {
            error.value = err.message || 'Failed to fetch tournaments';
        } finally {
            isLoading.value = false;
        }
    };

    const fetchTournament = async (id: number) => {
        isLoading.value = true;
        error.value = null;

        try {
            // Mock for now - replace with actual API call
            currentTournament.value = tournaments.value.find(t => t.id === id) || null;
        } catch (err: any) {
            error.value = err.message || 'Failed to fetch tournament';
        } finally {
            isLoading.value = false;
        }
    };

    const createTournament = async (payload: any) => {
        isSaving.value = true;
        error.value = null;

        try {
            const tournament = await tournamentService.createTournament(payload);
            tournaments.value.push(tournament);
            currentTournament.value = tournament;
            return tournament;
        } catch (err: any) {
            error.value = err.message || 'Failed to create tournament';
            throw err;
        } finally {
            isSaving.value = false;
        }
    };

    const updateTournament = async (id: number, payload: any) => {
        isSaving.value = true;
        error.value = null;

        try {
            // Mock update
            const index = tournaments.value.findIndex(t => t.id === id);
            if (index !== -1) {
                tournaments.value[index] = {...tournaments.value[index], ...payload};
                if (currentTournament.value?.id === id) {
                    currentTournament.value = tournaments.value[index];
                }
            }
        } catch (err: any) {
            error.value = err.message || 'Failed to update tournament';
            throw err;
        } finally {
            isSaving.value = false;
        }
    };

    const fetchPlayers = async (tournamentId: number) => {
        isLoading.value = true;
        error.value = null;

        try {
            // Mock implementation - replace with actual API call
            players.value = [];
        } catch (err: any) {
            error.value = err.message || 'Failed to fetch players';
        } finally {
            isLoading.value = false;
        }
    };

    const addPlayer = async (tournamentId: number, playerId: number) => {
        isSaving.value = true;
        error.value = null;

        try {
            const player = await tournamentService.addPlayerToTournament(tournamentId, playerId);
            players.value.push(player);
            return player;
        } catch (err: any) {
            error.value = err.message || 'Failed to add player';
            throw err;
        } finally {
            isSaving.value = false;
        }
    };

    const removePlayer = async (tournamentId: number, playerId: number) => {
        isSaving.value = true;
        error.value = null;

        try {
            await tournamentService.removePlayerFromTournament(tournamentId, playerId);
            players.value = players.value.filter(p => p.user_id !== playerId);
        } catch (err: any) {
            error.value = err.message || 'Failed to remove player';
            throw err;
        } finally {
            isSaving.value = false;
        }
    };

    const generateBracket = async (tournamentId: number, format: any, seeding: any) => {
        isGenerating.value = true;
        error.value = null;

        try {
            bracket.value = await tournamentService.generateBracket(tournamentId, format, seeding);
        } catch (err: any) {
            error.value = err.message || 'Failed to generate bracket';
            throw err;
        } finally {
            isGenerating.value = false;
        }
    };

    const updateBracketNode = async (tournamentId: number, nodeId: number, update: any) => {
        error.value = null;

        try {
            const updatedNode = await tournamentService.updateBracketNode(tournamentId, nodeId, update);
            const index = bracket.value.findIndex(n => n.id === nodeId);
            if (index !== -1) {
                bracket.value[index] = updatedNode;
            }
        } catch (err: any) {
            error.value = err.message || 'Failed to update bracket';
            throw err;
        }
    };

    const createGroups = async (tournamentId: number, groupCount: number) => {
        isGenerating.value = true;
        error.value = null;

        try {
            groups.value = await tournamentService.createGroups(tournamentId, groupCount, confirmedPlayers.value);
        } catch (err: any) {
            error.value = err.message || 'Failed to create groups';
            throw err;
        } finally {
            isGenerating.value = false;
        }
    };

    const generateGroupMatches = async (tournamentId: number, groupId: number, format: 'round_robin' | 'swiss') => {
        isGenerating.value = true;
        error.value = null;

        try {
            const groupMatches = await tournamentService.generateGroupMatches(tournamentId, groupId, format);
            matches.value.push(...groupMatches);
        } catch (err: any) {
            error.value = err.message || 'Failed to generate group matches';
            throw err;
        } finally {
            isGenerating.value = false;
        }
    };

    const fetchSchedule = async (tournamentId: number, date?: string) => {
        isLoading.value = true;
        error.value = null;

        try {
            matches.value = await tournamentService.fetchSchedule(tournamentId, date);
        } catch (err: any) {
            error.value = err.message || 'Failed to fetch schedule';
        } finally {
            isLoading.value = false;
        }
    };

    const updateMatchSchedule = async (tournamentId: number, matchId: number, updates: any) => {
        error.value = null;

        try {
            const updatedMatch = await tournamentService.updateMatchSchedule(tournamentId, matchId, updates);
            const index = matches.value.findIndex(m => m.id === matchId);
            if (index !== -1) {
                matches.value[index] = updatedMatch;
            }
        } catch (err: any) {
            error.value = err.message || 'Failed to update match';
            throw err;
        }
    };

    const submitMatchResult = async (tournamentId: number, matchId: number, result: any) => {
        isSaving.value = true;
        error.value = null;

        try {
            const updatedMatch = await tournamentService.submitMatchResult(tournamentId, matchId, result);
            const index = matches.value.findIndex(m => m.id === matchId);
            if (index !== -1) {
                matches.value[index] = updatedMatch;
            }

            // Update group standings if it's a group match
            if (updatedMatch.group_id) {
                await updateGroupStandings(tournamentId, updatedMatch.group_id);
            }
        } catch (err: any) {
            error.value = err.message || 'Failed to submit result';
            throw err;
        } finally {
            isSaving.value = false;
        }
    };

    const updateGroupStandings = async (tournamentId: number, groupId: number) => {
        try {
            const standings = await tournamentService.updateGroupStandings(tournamentId, groupId);
            const groupIndex = groups.value.findIndex(g => g.id === groupId);
            if (groupIndex !== -1) {
                groups.value[groupIndex].standings = standings;
            }
        } catch (err: any) {
            error.value = err.message || 'Failed to update standings';
        }
    };

    const clearError = () => {
        error.value = null;
    };

    const reset = () => {
        currentTournament.value = null;
        players.value = [];
        matches.value = [];
        groups.value = [];
        bracket.value = [];
        error.value = null;
    };

    return {
        // State
        currentTournament,
        tournaments,
        players,
        matches,
        groups,
        bracket,
        isLoading,
        isSaving,
        isGenerating,
        error,

        // Getters
        confirmedPlayers,
        pendingPlayers,
        scheduledMatches,
        completedMatches,
        groupStandings,
        bracketRounds,

        // Actions
        fetchTournaments,
        fetchTournament,
        createTournament,
        updateTournament,
        fetchPlayers,
        addPlayer,
        removePlayer,
        generateBracket,
        updateBracketNode,
        createGroups,
        generateGroupMatches,
        fetchSchedule,
        updateMatchSchedule,
        submitMatchResult,
        updateGroupStandings,
        clearError,
        reset
    };
});
