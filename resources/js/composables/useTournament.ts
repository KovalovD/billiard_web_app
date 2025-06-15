// resources/js/composables/useTournament.ts

import {computed, onMounted, onUnmounted, ref, watch} from 'vue';
import {storeToRefs} from 'pinia';
import {useTournamentStore} from '@/stores/useTournamentStore';
//import { TournamentChannels } from '@/lib/echo';
import {useToastStore} from '@/stores/toast';
import type {MatchStatus,} from '@/types/tournament';

export interface UseTournamentOptions {
    tournamentId?: number | string;
    stageId?: number | string;
    autoLoadTournament?: boolean;
    autoLoadStage?: boolean;
    autoLoadMatches?: boolean;
    autoSubscribe?: boolean;
}

export function useTournament(options: UseTournamentOptions = {}) {
    const store = useTournamentStore();
    const toastStore = useToastStore();

    // Extract reactive state from store
    const {
        currentTournament,
        currentStage,
        stages,
        matches,
        participants,
        isLoading,
        error,
        currentStageMatches,
        currentStageParticipants,
        pendingMatches,
        scheduledMatches,
        unscheduledMatches
    } = storeToRefs(store);

    // Local state
    const isSubscribed = ref(false);
    const channels = ref<any[]>([]);

    // Computed properties
    const hasStarted = computed(() => {
        if (!currentTournament.value?.start_at) return false;
        return new Date(currentTournament.value.start_at) <= new Date();
    });

    const hasEnded = computed(() => {
        if (!currentTournament.value?.end_at) return false;
        return new Date(currentTournament.value.end_at) < new Date();
    });

    const isOngoing = computed(() => hasStarted.value && !hasEnded.value);

    const canEditBracket = computed(() => {
        if (!currentStage.value) return false;
        // Can edit if no matches have been played yet
        return !matches.value.some(m =>
            m.stage_id === currentStage.value!.id &&
            m.status !== 'pending'
        );
    });

    const stageProgress = computed(() => {
        if (!currentStage.value || currentStageMatches.value.length === 0) return 0;

        const completed = currentStageMatches.value.filter(m =>
            m.status === 'finished' || m.status === 'walkover'
        ).length;

        return Math.round((completed / currentStageMatches.value.length) * 100);
    });

    // Methods
    async function loadTournament(id?: number | string) {
        const tournamentId = id || options.tournamentId;
        if (!tournamentId) return;

        try {
            await store.fetchTournament(tournamentId);

            // Auto-load stages if tournament loaded successfully
            if (currentTournament.value) {
                await store.fetchStages(tournamentId);
            }
        } catch (error) {
            toastStore.error('Failed to load tournament');
        }
    }

    async function loadStage(id?: number | string) {
        const stageId = id || options.stageId;
        const tournamentId = options.tournamentId || currentTournament.value?.id;

        if (!tournamentId || !stageId) return;

        try {
            await store.fetchStage(tournamentId, stageId);
        } catch (error) {
            toastStore.error('Failed to load stage');
        }
    }

    async function loadMatches() {
        const tournamentId = options.tournamentId || currentTournament.value?.id;
        if (!tournamentId) return;

        try {
            await store.fetchMatches(tournamentId, {
                stage_id: options.stageId || currentStage.value?.id
            });
        } catch (error) {
            toastStore.error('Failed to load matches');
        }
    }

    async function generateBracket(options: { include_third_place?: boolean; group_count?: number } = {}) {
        const tournamentId = currentTournament.value?.id;
        const stageId = currentStage.value?.id;

        if (!tournamentId || !stageId) {
            toastStore.error('No tournament or stage selected');
            return;
        }

        try {
            const result = await store.generateBracket(tournamentId, stageId, options);
            toastStore.success(`Generated ${result.matches_created} matches`);

            // Reload stage to get updated matches
            await loadStage();
        } catch (error) {
            toastStore.error('Failed to generate bracket');
        }
    }

    async function updateMatchScore(
        matchId: number,
        status: MatchStatus,
        sets: Array<{ winner_id: number; score1: number; score2: number }>
    ) {
        const tournamentId = currentTournament.value?.id;
        if (!tournamentId) return;

        try {
            await store.updateMatchScore(tournamentId, matchId, {status, sets});
            toastStore.success('Match score updated');
        } catch (error) {
            toastStore.error('Failed to update match score');
        }
    }

    async function scheduleMatch(matchId: number, tableId: number, scheduledAt: string) {
        const tournamentId = currentTournament.value?.id;
        if (!tournamentId) return;

        try {
            await store.scheduleMatch(tournamentId, matchId, {
                table_id: tableId,
                scheduled_at: scheduledAt
            });
            toastStore.success('Match scheduled');
        } catch (error) {
            toastStore.error('Failed to schedule match');
        }
    }

    async function autoSchedule(startTime: string, options: any = {}) {
        const tournamentId = currentTournament.value?.id;
        if (!tournamentId) return;

        try {
            const result = await store.autoSchedule(tournamentId, {
                stage_id: currentStage.value?.id,
                start_time: startTime,
                ...options
            });
            toastStore.success(`Scheduled ${result.matches_scheduled} matches`);
        } catch (error) {
            toastStore.error('Failed to auto-schedule matches');
        }
    }

    async function applySeeding(method: 'manual' | 'random' | 'rating' | 'previous', options: any = {}) {
        const tournamentId = currentTournament.value?.id;
        const stageId = currentStage.value?.id;

        if (!tournamentId || !stageId) return;

        try {
            await store.applySeeding(tournamentId, stageId, {
                method,
                ...options
            });
            toastStore.success('Seeding applied successfully');
        } catch (error) {
            toastStore.error('Failed to apply seeding');
        }
    }

    // WebSocket subscription
    function subscribe() {
        if (isSubscribed.value) return;

        const tournamentId = options.tournamentId || currentTournament.value?.id;
        const stageId = options.stageId || currentStage.value?.id;

        /*if (tournamentId) {
            const tournamentChannel = TournamentChannels.tournament(tournamentId);
            if (tournamentChannel) {
                channels.value.push(tournamentChannel);
            }
        }

        if (stageId) {
            const stageChannel = TournamentChannels.stage(stageId);
            if (stageChannel) {
                channels.value.push(stageChannel);
            }
        }*/

        isSubscribed.value = true;

        // Listen to custom events
        /* window.addEventListener('echo:match.updated', handleMatchUpdate);
         window.addEventListener('echo:schedule.updated', handleScheduleUpdate);
         window.addEventListener('echo:standings.updated', handleStandingsUpdate);*/
    }

    function unsubscribe() {
        if (!isSubscribed.value) return;

        // Leave channels
        /*TournamentChannels.leaveAll(
            options.tournamentId || currentTournament.value?.id,
            options.stageId || currentStage.value?.id
        );*/

        channels.value = [];
        isSubscribed.value = false;

        // Remove event listeners
        /*window.removeEventListener('echo:match.updated', handleMatchUpdate);
        window.removeEventListener('echo:schedule.updated', handleScheduleUpdate);
        window.removeEventListener('echo:standings.updated', handleStandingsUpdate);*/
    }

    // Event handlers
    function handleStandingsUpdate(event: CustomEvent) {
        console.log('Standings updated via WebSocket:', event.detail);

        // Reload standings if we're viewing them
        if (currentStage.value?.type === 'group' || currentStage.value?.type === 'round_robin') {
            const tournamentId = currentTournament.value?.id;
            const stageId = currentStage.value?.id;

            if (tournamentId && stageId) {
                store.fetchStandings(tournamentId, stageId);
            }
        }
    }

    // Lifecycle
    onMounted(() => {
        // Auto-load tournament if ID provided
        if (options.autoLoadTournament !== false && options.tournamentId) {
            loadTournament();
        }

        // Auto-load stage if ID provided
        if (options.autoLoadStage !== false && options.stageId) {
            loadStage();
        }

        // Auto-load matches
        if (options.autoLoadMatches && (options.tournamentId || currentTournament.value)) {
            loadMatches();
        }

        // Auto-subscribe to WebSocket updates
        if (options.autoSubscribe !== false) {
            subscribe();
        }
    });

    onUnmounted(() => {
        unsubscribe();
    });

    // Watch for tournament/stage changes
    watch(() => currentTournament.value?.id, (newId) => {
        if (newId && options.autoSubscribe !== false) {
            unsubscribe();
            subscribe();
        }
    });

    watch(() => currentStage.value?.id, (newId) => {
        if (newId && options.autoSubscribe !== false) {
            unsubscribe();
            subscribe();
        }
    });

    return {
        // State
        currentTournament,
        currentStage,
        stages,
        matches,
        participants,
        isLoading,
        error,
        currentStageMatches,
        currentStageParticipants,
        pendingMatches,
        scheduledMatches,
        unscheduledMatches,
        isSubscribed,

        // Computed
        hasStarted,
        hasEnded,
        isOngoing,
        canEditBracket,
        stageProgress,

        // Methods
        loadTournament,
        loadStage,
        loadMatches,
        generateBracket,
        updateMatchScore,
        scheduleMatch,
        autoSchedule,
        applySeeding,
        subscribe,
        unsubscribe,

        // Store actions (for convenience)
        createTournament: store.createTournament,
        updateTournament: store.updateTournament,
        deleteTournament: store.deleteTournament,
        createStage: store.createStage,
        addParticipant: store.addParticipant,
        removeParticipant: store.removeParticipant,
        batchAddParticipants: store.batchAddParticipants,
        setWalkover: store.setWalkover
    };
}
