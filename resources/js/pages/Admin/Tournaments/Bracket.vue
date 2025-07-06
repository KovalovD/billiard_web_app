<script lang="ts" setup>
import {Button, Card, CardContent, CardDescription, CardHeader, CardTitle, Spinner} from '@/Components/ui';
import SingleEliminationBracket from '@/Components/Tournament/SingleEliminationBracket.vue';
import DoubleEliminationBracket from '@/Components/Tournament/DoubleEliminationBracket.vue';
import MatchManagementModal from '@/Components/Tournament/MatchManagementModal.vue';
import {useLocale} from '@/composables/useLocale';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import {apiClient} from '@/lib/apiClient';
import {useAuth} from "@/composables/useAuth";

import type {ClubTable, Tournament, TournamentBracket, TournamentMatch} from '@/types/api';
import {Head, router} from '@inertiajs/vue3';
import {AlertCircleIcon, ArrowLeftIcon, PlayIcon, RefreshCwIcon, TrophyIcon} from 'lucide-vue-next';
import {computed, onMounted, ref} from 'vue';

defineOptions({layout: AuthenticatedLayout});

const props = defineProps<{
    tournamentId: number | string;
}>();

const {t} = useLocale();

const {user} = useAuth();

// Get current user from page props
const currentUserId = user.value?.id;

// Data
const tournament = ref<Tournament | null>(null);
const brackets = ref<TournamentBracket[]>([]);
const matches = ref<TournamentMatch[]>([]);
const availableTables = ref<ClubTable[]>([]);

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

// Check if tournament can be edited
const canEditTournament = computed(() => {
    return tournament.value?.status === 'active' && tournament.value?.stage === 'bracket';
});

// Check if tournament is double elimination
const isDoubleElimination = computed(() => {
    return tournament.value?.tournament_type === 'double_elimination' ||
        tournament.value?.tournament_type === 'double_elimination_full';
});

const hasGeneratedBracket = computed(() => matches.value.length > 0);

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

const generateBracket = async () => {
    if (!confirm(t('Are you sure you want to generate the bracket? This will create all matches based on current seeding.'))) {
        return;
    }

    isGenerating.value = true;
    error.value = null;

    try {
        await apiClient(`/api/admin/tournaments/${props.tournamentId}/bracket/generate`, {
            method: 'POST'
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
        const racesTo = tournament.value.races_to || 7;

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

    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">
                        {{ t('Tournament Bracket') }}
                    </h1>
                    <p class="text-gray-600 dark:text-gray-400">
                        {{ tournament ? tournament.name : t('Loading...') }}
                    </p>
                </div>
                <div class="flex space-x-3">
                    <Button :disabled="isLoading" variant="outline" @click="loadData">
                        <RefreshCwIcon :class="{ 'animate-spin': isLoading }" class="mr-2 h-4 w-4"/>
                        {{ t('Refresh') }}
                    </Button>
                    <Button variant="outline" @click="router.visit(`/tournaments/${tournament?.slug}`)">
                        <ArrowLeftIcon class="mr-2 h-4 w-4"/>
                        {{ t('Back to Tournament') }}
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
                <div class="flex items-center gap-2">
                    <AlertCircleIcon class="h-5 w-5 text-yellow-600"/>
                    <p class="font-medium text-yellow-800 dark:text-yellow-300">
                        {{ t('Tournament must be active and in bracket stage to edit matches') }}
                    </p>
                </div>
                <p class="mt-2 text-sm text-yellow-700 dark:text-yellow-400">
                    {{ t('Current status') }}: {{ tournament.status_display }} • {{ t('Current stage') }}:
                    {{ tournament.stage_display }}
                </p>
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
                            {{ t('The bracket has not been generated yet. Click below to create it.') }}
                        </p>
                        <Button
                            :disabled="isGenerating"
                            size="lg"
                            @click="generateBracket"
                        >
                            <Spinner v-if="isGenerating" class="mr-2 h-4 w-4"/>
                            {{ t('Generate Bracket') }}
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <!-- Bracket Display -->
            <template v-else>
                <!-- Tournament Info -->
                <Card class="mb-6">
                    <CardContent class="pt-6">
                        <div class="grid grid-cols-4 gap-4 text-center">
                            <div>
                                <div class="text-2xl font-bold text-blue-600">
                                    {{ tournament?.confirmed_players_count || 0 }}
                                </div>
                                <div class="text-sm text-gray-600">{{ t('Players') }}</div>
                            </div>
                            <div>
                                <div class="text-2xl font-bold text-green-600">
                                    {{ isDoubleElimination ? t('Double Elimination') : t('Single Elimination') }}
                                </div>
                                <div class="text-sm text-gray-600">{{ t('Format') }}</div>
                            </div>
                            <div>
                                <div class="text-2xl font-bold text-yellow-600">{{ matches.length }}</div>
                                <div class="text-sm text-gray-600">{{ t('Matches') }}</div>
                            </div>
                            <div>
                                <div class="text-2xl font-bold text-purple-600">{{ tournament?.races_to || 7 }}</div>
                                <div class="text-sm text-gray-600">{{ t('Races To') }}</div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Bracket Component -->
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

                <!-- Start Tournament Button -->
                <Card v-if="tournament?.status === 'upcoming'" class="mt-6">
                    <CardContent class="pt-6">
                        <div class="flex items-center justify-between">
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
</template>
