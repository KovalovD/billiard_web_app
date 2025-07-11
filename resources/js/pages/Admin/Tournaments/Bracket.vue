<script lang="ts" setup>
import {Button, Card, CardContent, CardDescription, CardHeader, CardTitle, Spinner} from '@/Components/ui';
import SingleEliminationBracket from '@/Components/Tournament/SingleEliminationBracket.vue';
import DoubleEliminationBracket from '@/Components/Tournament/DoubleEliminationBracket.vue';
import MatchManagementModal from '@/Components/Tournament/MatchManagementModal.vue';
import {useLocale} from '@/composables/useLocale';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import {apiClient} from '@/lib/apiClient';
import {useAuth} from "@/composables/useAuth";
import GenerateBracketModal from '@/Components/Tournament/GenerateBracketModal.vue';
import RoundRobinStandings from '@/Components/Tournament/RoundRobinStandings.vue';

import type {ClubTable, Tournament, TournamentBracket, TournamentMatch} from '@/types/api';
import {Head, router} from '@inertiajs/vue3';
import {
    AlertCircleIcon,
    ArrowLeftIcon,
    GitBranchIcon,
    LayersIcon,
    MonitorIcon,
    PlayIcon,
    RefreshCwIcon,
    TrophyIcon
} from 'lucide-vue-next';
import {computed, onMounted, ref} from 'vue';

defineOptions({layout: AuthenticatedLayout});

const props = defineProps<{
    tournamentId: number | string;
}>();

const {t} = useLocale();

const {user} = useAuth();
const isRoundRobin = computed(() => {
    return tournament.value?.tournament_type === 'round_robin';
});
// Get current user from page props
const currentUserId = user.value?.id;

// Data
const tournament = ref<Tournament | null>(null);
const brackets = ref<TournamentBracket[]>([]);
const matches = ref<TournamentMatch[]>([]);
const availableTables = ref<ClubTable[]>([]);
const showGenerateModal = ref(false);

// Loading states
const isLoading = ref(true);
const isGenerating = ref(false);
const isLoadingTables = ref(false);

// Error handling
const error = ref<string | null>(null);
const successMessage = ref<string | null>(null);

// Match modal state
const showMatchModal = ref(false);
const selectedMatch = ref<TournamentMatch | null>(null);
const matchModalRef = ref<InstanceType<typeof MatchManagementModal> | null>(null);

// Olympic bracket tab state
const activeOlympicTab = ref<'first-stage' | 'olympic-stage'>('first-stage');

// Check if tournament can be edited
const canEditTournament = computed(() => {
    return tournament.value?.status === 'active' && tournament.value?.stage === 'bracket';
});

// Check if tournament is double elimination
const isDoubleElimination = computed(() => {
    return tournament.value?.tournament_type === 'double_elimination' ||
        tournament.value?.tournament_type === 'double_elimination_full';
});

// Check if tournament is Olympic double elimination
const isOlympicDoubleElimination = computed(() => {
    return tournament.value?.tournament_type === 'olympic_double_elimination';
});

const hasGeneratedBracket = computed(() => matches.value.length > 0);

// Split matches by Olympic stage
const firstStageMatches = computed(() => {
    if (!isOlympicDoubleElimination.value) return matches.value;
    return matches.value.filter(m => m.metadata?.olympic_stage === 'first' || m.match_code?.startsWith('FS_'));
});

const olympicStageMatches = computed(() => {
    if (!isOlympicDoubleElimination.value) return [];
    return matches.value.filter(m => m.metadata?.olympic_stage === 'second' || m.match_code?.startsWith('OS_'));
});

// Handle tab change
const switchOlympicTab = (tab: 'first-stage' | 'olympic-stage') => {
    activeOlympicTab.value = tab;
};

// Methods
const loadData = async () => {
    isLoading.value = true;
    error.value = null;

    try {
        tournament.value = await apiClient<Tournament>(`/api/tournaments/${props.tournamentId}`);

        const matchesResponse = await apiClient<TournamentMatch[]>(`/api/admin/tournaments/${props.tournamentId}/matches`);
        matches.value = matchesResponse || [];

        try {
            const bracketsResponse = await apiClient<TournamentBracket[]>(`/api/tournaments/${props.tournamentId}/brackets`);
            brackets.value = bracketsResponse || [];
        } catch {
            brackets.value = [];
        }
    } catch (err: any) {
        error.value = err.message || t('Failed to load tournament data');
    } finally {
        isLoading.value = false;
    }
};

// Update specific matches in the bracket
const updateMatchesInBracket = (updatedMatches: TournamentMatch[]) => {
    updatedMatches.forEach(updatedMatch => {
        const index = matches.value.findIndex(m => m.id === updatedMatch.id);
        if (index !== -1) {
            matches.value[index] = updatedMatch;
        } else {
            matches.value.push(updatedMatch);
        }
    });
    matches.value = [...matches.value];
};

// Load specific matches after an update
const loadSpecificMatches = async (matchIds: number[]) => {
    try {
        const updatedMatches = await Promise.all(
            matchIds.map(id =>
                apiClient<TournamentMatch>(`/api/admin/tournaments/${props.tournamentId}/matches/${id}`)
            )
        );
        updateMatchesInBracket(updatedMatches);
    } catch (err: any) {
        console.error('Failed to load specific matches:', err);
        await loadData();
    }
};

const loadAvailableTables = async () => {
    isLoadingTables.value = true;
    try {
        const tables = await apiClient<ClubTable[]>(`/api/tournaments/${props.tournamentId}/tables`);
        availableTables.value = tables.filter(table => table.is_active);
    } catch (err) {
        console.error('Failed to load tables:', err);
        availableTables.value = [];
    } finally {
        isLoadingTables.value = false;
    }
};

const generateBracket = () => {
    // Instead of showing confirm dialog, show the options modal
    showGenerateModal.value = true;
};

// Handle bracket generation with options
const handleGenerateBracket = async (options: any) => {
    showGenerateModal.value = false;
    isGenerating.value = true;
    error.value = null;

    try {
        await apiClient(`/api/admin/tournaments/${props.tournamentId}/bracket/generate`, {
            method: 'POST',
            data: options
        });

        successMessage.value = t('Bracket generated successfully');
        await loadData();
    } catch (err: any) {
        error.value = err.message || t('Failed to generate bracket');
    } finally {
        isGenerating.value = false;
    }
};
const startTournament = async () => {
    if (!confirm(t('Are you sure you want to start the tournament?'))) {
        return;
    }

    try {
        await apiClient(`/api/admin/tournaments/${props.tournamentId}/status`, {
            method: 'POST',
            data: {status: 'active'}
        });

        successMessage.value = t('Tournament started successfully');
        await loadData();
    } catch (err: any) {
        error.value = err.message || t('Failed to start tournament');
    }
};

const openMatchModal = async (matchId: number) => {
    if (!canEditTournament.value) {
        error.value = t('Tournament must be active and in bracket stage to edit matches');
        return;
    }

    // Show loading state
    showMatchModal.value = true;
    selectedMatch.value = null;

    try {
        // Load fresh match data from the server
        const match = await apiClient<TournamentMatch>(`/api/admin/tournaments/${props.tournamentId}/matches/${matchId}`);

        if (!match) {
            throw new Error('Match not found');
        }

        selectedMatch.value = match;

        // Update the match in the local matches array as well
        const index = matches.value.findIndex(m => m.id === matchId);
        if (index !== -1) {
            matches.value[index] = match;
        } else {
            matches.value.push(match);
        }

        // Load available tables
        await loadAvailableTables();
    } catch (err: any) {
        error.value = err.message || t('Failed to load match details');
        showMatchModal.value = false;
        selectedMatch.value = null;
    }
};

const closeMatchModal = () => {
    showMatchModal.value = false;
    selectedMatch.value = null;
};

// Handle match modal events
const handleStartMatch = async (data: {
    club_table_id: number | null;
    stream_url: string;
    admin_notes: string | null
}) => {
    if (!selectedMatch.value) return;

    try {
        const response = await apiClient<TournamentMatch>(`/api/admin/tournaments/${props.tournamentId}/matches/${selectedMatch.value.id}/start`, {
            method: 'POST',
            data
        });

        updateMatchesInBracket([response]);
        closeMatchModal();
        successMessage.value = t('Match started successfully');
    } catch (err: any) {
        matchModalRef.value?.setError(err.message || t('Failed to start match'));
    }
};

const handleUpdateMatch = async (data: any) => {
    if (!selectedMatch.value) return;

    try {
        const response = await apiClient<TournamentMatch>(`/api/admin/tournaments/${props.tournamentId}/matches/${selectedMatch.value.id}`, {
            method: 'PUT',
            data
        });

        updateMatchesInBracket([response]);
        closeMatchModal();
        successMessage.value = t('Match updated successfully');
    } catch (err: any) {
        matchModalRef.value?.setError(err.message || t('Failed to update match'));
    }
};

const handleFinishMatch = async (data: {
    player1_score: number;
    player2_score: number;
    admin_notes: string | null
}) => {
    if (!selectedMatch.value) return;

    try {
        const response = await apiClient<{
            match: TournamentMatch;
            affected_matches: number[];
        }>(`/api/admin/tournaments/${props.tournamentId}/matches/${selectedMatch.value.id}/finish`, {
            method: 'POST',
            data
        });

        updateMatchesInBracket([response.match]);

        if (response.affected_matches && response.affected_matches.length > 0) {
            await loadSpecificMatches(response.affected_matches);
        }

        closeMatchModal();
        successMessage.value = t('Match finished successfully');
    } catch (err: any) {
        matchModalRef.value?.setError(err.message || t('Failed to finish match'));
    }
};

const handleProcessWalkover = async () => {
    if (!selectedMatch.value || !tournament.value) return;

    try {
        const racesTo = selectedMatch.value.races_to || 7;

        const response = await apiClient<{
            match: TournamentMatch;
            affected_matches: number[];
        }>(`/api/admin/tournaments/${props.tournamentId}/matches/${selectedMatch.value.id}/finish`, {
            method: 'POST',
            data: {
                player1_score: selectedMatch.value.player1_id ? racesTo : 0,
                player2_score: selectedMatch.value.player2_id ? racesTo : 0,
                admin_notes: 'Walkover'
            }
        });

        updateMatchesInBracket([response.match]);

        if (response.affected_matches && response.affected_matches.length > 0) {
            await loadSpecificMatches(response.affected_matches);
        }

        closeMatchModal();
        successMessage.value = t('Walkover processed successfully');
    } catch (err: any) {
        matchModalRef.value?.setError(err.message || t('Failed to process walkover'));
    }
};

onMounted(() => {
    loadData();
});
</script>

<template>
    <Head :title="tournament ? `${t('Bracket')}: ${tournament.name}` : t('Tournament Bracket')"/>

    <div class="py-6 sm:py-12">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <!-- Mobile-optimized Header -->
            <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-xl sm:text-2xl font-semibold text-gray-800 dark:text-gray-200">
                        {{ t('Tournament Bracket') }}
                    </h1>
                    <p class="text-sm sm:text-base text-gray-600 dark:text-gray-400">
                        {{ tournament ? tournament.name : t('Loading...') }}
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <Button :disabled="isLoading" variant="outline" size="sm" @click="loadData">
                        <RefreshCwIcon :class="{ 'animate-spin': isLoading }" class="mr-2 h-4 w-4"/>
                        <span class="hidden sm:inline">{{ t('Refresh') }}</span>
                    </Button>
                    <Button variant="outline" size="sm" @click="router.visit(`/tournaments/${tournament?.slug}`)">
                        <ArrowLeftIcon class="mr-2 h-4 w-4"/>
                        <span class="hidden sm:inline">{{ t('Back to Tournament') }}</span>
                        <span class="sm:hidden">{{ t('Back') }}</span>
                    </Button>
                </div>
            </div>

            <!-- Messages -->
            <div v-if="error" class="mb-6 rounded bg-red-100 p-4 text-red-600 dark:bg-red-900/30 dark:text-red-400">
                {{ error }}
            </div>
            <div v-if="successMessage"
                 class="mb-6 rounded bg-green-100 p-4 text-green-600 dark:bg-green-900/30 dark:text-green-400">
                {{ successMessage }}
            </div>

            <!-- Tournament Status Warning -->
            <div v-if="tournament && !canEditTournament" class="mb-6 rounded bg-yellow-100 p-4 dark:bg-yellow-900/30">
                <div class="flex flex-col sm:flex-row sm:items-center gap-2">
                    <AlertCircleIcon class="h-5 w-5 text-yellow-600 flex-shrink-0"/>
                    <div>
                        <p class="font-medium text-yellow-800 dark:text-yellow-300">
                            {{ t('Tournament must be active and in bracket stage to edit matches') }}
                        </p>
                        <p class="mt-1 text-sm text-yellow-700 dark:text-yellow-400">
                            {{ t('Current status') }}: {{ tournament.status_display }} • {{ t('Current stage') }}:
                            {{ tournament.stage_display }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Loading -->
            <div v-if="isLoading" class="flex justify-center py-12">
                <Spinner class="h-8 w-8 text-primary"/>
            </div>

            <!-- Generate Bracket -->
            <Card v-else-if="!hasGeneratedBracket" class="mb-6">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <TrophyIcon class="h-5 w-5"/>
                        {{ t('Generate Tournament Bracket') }}
                    </CardTitle>
                    <CardDescription>
                        {{ t('Create the bracket structure based on seeded players') }}
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="text-center py-8">
                        <p class="mb-4 text-gray-600 dark:text-gray-400">
                            {{ t('The bracket has not been generated yet. Click below to configure and create it.') }}
                        </p>
                        <Button
                            :disabled="isGenerating"
                            size="lg"
                            @click="generateBracket"
                        >
                            <Spinner v-if="isGenerating" class="mr-2 h-4 w-4"/>
                            {{ t('Configure & Generate Bracket') }}
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <!-- Bracket Display -->
            <template v-else>
                <!-- Start Tournament Button -->
                <Card v-if="tournament?.status === 'upcoming'" class="mt-6">
                    <CardContent class="pt-6">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                            <p class="text-gray-600 dark:text-gray-400">
                                {{ t('Bracket is ready. Start the tournament to begin matches.') }}
                            </p>
                            <Button size="lg" @click="startTournament">
                                <PlayIcon class="mr-2 h-4 w-4"/>
                                {{ t('Start Tournament') }}
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                <!-- Mobile notice for bracket viewing -->
                <Card class="mb-6 sm:hidden">
                    <CardContent class="pt-6">
                        <p class="text-sm text-gray-600 dark:text-gray-400 text-center">
                            {{ t('Rotate your device or scroll horizontally to view the full bracket') }}
                        </p>
                    </CardContent>
                </Card>

                <!-- Olympic Tournament Tab Navigation -->
                <nav v-if="isOlympicDoubleElimination"
                     class="mb-6 border-b border-gray-200 dark:border-gray-700 overflow-x-auto" role="navigation"
                     aria-label="Olympic bracket tabs">
                    <div class="-mb-px flex space-x-6 sm:space-x-8 min-w-max">
                        <button
                            id="tab-first-stage"
                            :class="[
                                'py-4 px-1 text-sm sm:text-base font-medium border-b-2 transition-colors whitespace-nowrap',
                                activeOlympicTab === 'first-stage'
                                    ? 'border-indigo-600 text-indigo-600 dark:border-indigo-400 dark:text-indigo-400'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'
                            ]"
                            :aria-selected="activeOlympicTab === 'first-stage'"
                            role="tab"
                            @click="switchOlympicTab('first-stage')"
                        >
                            <span class="flex items-center gap-2">
                                <GitBranchIcon class="h-4 w-4"/>
                                {{ t('First Stage') }}
                                <span class="text-xs bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded-full">
                                    {{ t('Double Elimination') }}
                                </span>
                            </span>
                        </button>
                        <button
                            id="tab-olympic-stage"
                            :class="[
                                'py-4 px-1 text-sm sm:text-base font-medium border-b-2 transition-colors whitespace-nowrap',
                                activeOlympicTab === 'olympic-stage'
                                    ? 'border-indigo-600 text-indigo-600 dark:border-indigo-400 dark:text-indigo-400'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'
                            ]"
                            :aria-selected="activeOlympicTab === 'olympic-stage'"
                            role="tab"
                            @click="switchOlympicTab('olympic-stage')"
                        >
                            <span class="flex items-center gap-2">
                                <LayersIcon class="h-4 w-4"/>
                                {{ t('Olympic Stage') }}
                                <span class="text-xs bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded-full">
                                    {{ t('Single Elimination') }}
                                </span>
                            </span>
                        </button>
                    </div>
                </nav>

                <!-- Olympic Tournament Tab Content -->
                <main v-if="isOlympicDoubleElimination" role="tabpanel">
                    <!-- First Stage Tab -->
                    <div v-if="activeOlympicTab === 'first-stage'">
                        <Card>
                            <CardHeader>
                                <CardTitle>{{ t('First Stage - Double Elimination') }}</CardTitle>
                                <CardDescription>
                                    {{ t('Top players from upper and lower brackets advance to Olympic stage') }}
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div class="overflow-x-auto pb-4">
                                    <div class="min-w-[800px]">
                                        <DoubleEliminationBracket
                                            :can-edit="canEditTournament"
                                            :current-user-id="currentUserId"
                                            :matches="firstStageMatches"
                                            :tournament="tournament!"
                                            @open-match="openMatchModal"
                                        />
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    <!-- Olympic Stage Tab -->
                    <div v-if="activeOlympicTab === 'olympic-stage'">
                        <Card>
                            <CardHeader>
                                <CardTitle>{{ t('Olympic Stage - Single Elimination') }}</CardTitle>
                                <CardDescription>
                                    {{ t('Final single elimination bracket for top players') }}
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div class="overflow-x-auto pb-4">
                                    <div class="min-w-[800px]">
                                        <SingleEliminationBracket
                                            :can-edit="canEditTournament"
                                            :current-user-id="currentUserId"
                                            :matches="olympicStageMatches"
                                            :tournament="tournament!"
                                            @open-match="openMatchModal"
                                        />
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </main>

                <template v-if="isRoundRobin">
                    <RoundRobinStandings
                        :tournament-id="tournamentId"
                    />

                    <!-- Round Robin Matches Management -->
                    <Card class="mt-6">
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <PlayIcon class="h-5 w-5"/>
                                {{ t('Manage Matches') }}
                            </CardTitle>
                            <CardDescription>
                                {{ t('Click on any match to update results') }}
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div v-if="matches.length === 0" class="text-center py-8 text-gray-500">
                                {{ t('No matches generated yet') }}
                            </div>
                            <div v-else class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                                <div
                                    v-for="match in matches"
                                    :key="match.id"
                                    @click="openMatchModal(match.id)"
                                    class="p-4 border rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors"
                                    :class="{
                                        'border-green-500': match.status === 'completed',
                                        'border-yellow-500': match.status === 'in_progress',
                                        'border-blue-500': match.status === 'ready',
                                        'border-gray-300': match.status === 'pending'
                                    }"
                                >
                                    <div class="flex justify-between items-start mb-2">
                                        <span class="text-xs font-medium text-gray-500">
                                            {{ match.match_code }}
                                        </span>
                                        <span :class="[
                                            'text-xs px-2 py-1 rounded-full',
                                            match.status === 'completed' ? 'bg-green-100 text-green-800' :
                                            match.status === 'in_progress' ? 'bg-yellow-100 text-yellow-800' :
                                            match.status === 'ready' ? 'bg-blue-100 text-blue-800' :
                                            'bg-gray-100 text-gray-800'
                                        ]">
                                            {{ match.status_display }}
                                        </span>
                                    </div>

                                    <div class="space-y-2">
                                        <div class="flex items-center justify-between">
                                            <span class="text-sm"
                                                  :class="{'font-bold': match.winner_id === match.player1_id}">
                                                {{ match.player1?.firstname }} {{ match.player1?.lastname }}
                                            </span>
                                            <span class="text-lg font-bold">
                                                {{ match.player1_score || 0 }}
                                            </span>
                                        </div>
                                        <div class="flex items-center justify-between">
                                            <span class="text-sm"
                                                  :class="{'font-bold': match.winner_id === match.player2_id}">
                                                {{ match.player2?.firstname }} {{ match.player2?.lastname }}
                                            </span>
                                            <span class="text-lg font-bold">
                                                {{ match.player2_score || 0 }}
                                            </span>
                                        </div>
                                    </div>

                                    <div v-if="match.club_table" class="mt-2 text-xs text-gray-500">
                                        <MonitorIcon class="inline h-3 w-3 mr-1"/>
                                        {{ match.club_table.name }}
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </template>

                <!-- Regular Tournament Bracket (non-Olympic) -->
                <div v-else class="overflow-x-auto pb-4">
                    <div class="min-w-[800px]">
                        <SingleEliminationBracket
                            v-if="!isDoubleElimination"
                            :can-edit="canEditTournament"
                            :current-user-id="currentUserId"
                            :matches="matches"
                            :tournament="tournament!"
                            @open-match="openMatchModal"
                        />

                        <DoubleEliminationBracket
                            v-else
                            :can-edit="canEditTournament"
                            :current-user-id="currentUserId"
                            :matches="matches"
                            :tournament="tournament!"
                            @open-match="openMatchModal"
                        />
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- Match Management Modal -->
    <MatchManagementModal
        ref="matchModalRef"
        :show="showMatchModal"
        :match="selectedMatch"
        :tournament="tournament"
        :available-tables="availableTables"
        :is-loading-tables="isLoadingTables"
        :can-edit-tournament="canEditTournament"
        @close="closeMatchModal"
        @start-match="handleStartMatch"
        @update-match="handleUpdateMatch"
        @finish-match="handleFinishMatch"
        @process-walkover="handleProcessWalkover"
    />

    <GenerateBracketModal
        :show="showGenerateModal"
        :tournament-id="tournamentId"
        @close="showGenerateModal = false"
        @generate="handleGenerateBracket"
    />
</template>
