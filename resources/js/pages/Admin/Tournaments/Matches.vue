<!-- resources/js/pages/Admin/Tournaments/Matches.vue -->
<script lang="ts" setup>
import {
    Button,
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
    Input,
    Label,
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
    Spinner
} from '@/Components/ui';
import MatchManagementModal from "@/Components/Tournament/MatchManagementModal.vue";
import MatchesList from '@/Components/Tournament/MatchesList.vue';
import {useLocale} from '@/composables/useLocale';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import {apiClient} from '@/lib/apiClient';
import type {ClubTable, Tournament, TournamentGroup, TournamentMatch} from '@/types/api';
import {Head, Link} from '@inertiajs/vue3';
import {
    ActivityIcon,
    AlertCircleIcon,
    ArrowLeftIcon,
    CheckCircleIcon,
    ClockIcon,
    FilterIcon,
    PlayIcon,
    RefreshCwIcon,
    SearchIcon,
    TrophyIcon
} from 'lucide-vue-next';
import {computed, onMounted, onUnmounted, ref, watch} from 'vue';

defineOptions({layout: AuthenticatedLayout});

const props = defineProps<{
    tournamentId: number | string;
}>();

const {t} = useLocale();

// Data
const tournament = ref<Tournament | null>(null);
const matches = ref<TournamentMatch[]>([]);
const groups = ref<TournamentGroup[]>([]);
const availableTables = ref<ClubTable[]>([]);

// Loading states
const isLoading = ref(true);
const isLoadingTables = ref(false);

// Error handling
const error = ref<string | null>(null);
const successMessage = ref<string | null>(null);

// Filter states
const filterStatus = ref<string>('all');
const filterStage = ref<string>('all');
const filterGroup = ref<string>('all');
const filterRound = ref<string>('all');
const searchQuery = ref('');

// Match modal state
const showMatchModal = ref(false);
const selectedMatch = ref<TournamentMatch | null>(null);
const matchModalRef = ref<InstanceType<typeof MatchManagementModal> | null>(null);

// Computed properties
const canEditTournament = computed(() => {
    return tournament.value?.status === 'active';
});

const uniqueRounds = computed(() => {
    return [...new Set(matches.value.map(m => m.round_display).filter(Boolean))];
});

const filteredMatches = computed(() => {
    let filtered = matches.value;

    // Status filter
    if (filterStatus.value !== 'all') {
        filtered = filtered.filter(m => m.status === filterStatus.value);
    }

    // Stage filter
    if (filterStage.value !== 'all') {
        filtered = filtered.filter(m => m.stage === filterStage.value);
    }

    // Group filter (for group stage matches)
    if (filterGroup.value !== 'all' && filterStage.value === 'groups') {
        filtered = filtered.filter(m => {
            // Find group for this match
            const group = groups.value.find(g =>
                g.players?.some(p => p.player_id === m.player1_id || p.player_id === m.player2_id)
            );
            return group?.group_code === filterGroup.value;
        });
    }

    // Round filter (for bracket matches)
    if (filterRound.value !== 'all' && filterStage.value !== 'groups') {
        filtered = filtered.filter(m => m.round_display === filterRound.value);
    }

    // Search query
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        filtered = filtered.filter(m => {
            const player1Name = `${m.player1?.firstname || ''} ${m.player1?.lastname || ''}`.toLowerCase();
            const player2Name = `${m.player2?.firstname || ''} ${m.player2?.lastname || ''}`.toLowerCase();
            return player1Name.includes(query) ||
                player2Name.includes(query) ||
                m.match_code?.toLowerCase().includes(query);
        });
    }

    return filtered;
});

const matchStats = computed(() => {
    return {
        total: matches.value.length,
        completed: matches.value.filter(m => m.status === 'completed').length,
        inProgress: matches.value.filter(m => m.status === 'in_progress' || m.status === 'verification').length,
        ready: matches.value.filter(m => m.status === 'ready').length,
        pending: matches.value.filter(m => m.status === 'pending').length
    };
});

// Methods
const loadData = async () => {
    isLoading.value = true;
    error.value = null;

    try {
        const [tournamentData, matchesData, groupsData] = await Promise.all([
            apiClient<Tournament>(`/api/tournaments/${props.tournamentId}`),
            apiClient<TournamentMatch[]>(`/api/admin/tournaments/${props.tournamentId}/matches`),
            apiClient<TournamentGroup[]>(`/api/tournaments/${props.tournamentId}/groups`).catch(() => [])
        ]);

        tournament.value = tournamentData;
        matches.value = matchesData || [];
        groups.value = groupsData || [];
    } catch (err: any) {
        error.value = err.message || t('Failed to load tournament data');
    } finally {
        isLoading.value = false;
    }
};

// Update specific matches in the list
const updateMatchesInList = (updatedMatches: TournamentMatch[]) => {
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
        updateMatchesInList(updatedMatches);
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

const openMatchModal = async (match: TournamentMatch) => {
    if (!canEditTournament.value) {
        error.value = t('Tournament must be active to edit matches');
        return;
    }

    // Show loading state
    showMatchModal.value = true;
    selectedMatch.value = null;

    try {
        // Load fresh match data from the server
        const freshMatch = await apiClient<TournamentMatch>(`/api/admin/tournaments/${props.tournamentId}/matches/${match.id}`);

        if (!freshMatch) {
            throw new Error('Match not found');
        }

        selectedMatch.value = freshMatch;

        // Update the match in the local matches array as well
        const index = matches.value.findIndex(m => m.id === match.id);
        if (index !== -1) {
            matches.value[index] = freshMatch;
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

        updateMatchesInList([response]);
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

        updateMatchesInList([response]);
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

        updateMatchesInList([response.match]);

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

        updateMatchesInList([response.match]);

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

// Auto-refresh for active matches
const refreshInterval = ref<number | null>(null);

watch(() => canEditTournament.value, (canEdit) => {
    if (canEdit && matchStats.value.inProgress > 0) {
        refreshInterval.value = window.setInterval(() => {
            loadData();
        }, 30000); // Refresh every 30 seconds
    } else if (refreshInterval.value) {
        clearInterval(refreshInterval.value);
        refreshInterval.value = null;
    }
});

onMounted(() => {
    if (canEditTournament.value && matchStats.value.inProgress > 0) {
        refreshInterval.value = window.setInterval(() => {
            loadData();
        }, 30000);
    }
});

onUnmounted(() => {
    if (refreshInterval.value) {
        clearInterval(refreshInterval.value);
    }
});
</script>

<template>
    <Head :title="tournament ? `${t('Matches')}: ${tournament.name}` : t('Tournament Matches')"/>

    <div class="py-6 sm:py-8 lg:py-12">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <Link :href="`/tournaments/${tournament?.slug}`">
                    <Button variant="outline" size="sm">
                        <ArrowLeftIcon class="mr-2 h-4 w-4"/>
                        <span class="hidden sm:inline">{{ t('Back to Tournament') }}</span>
                        <span class="sm:hidden">{{ t('Back') }}</span>
                    </Button>
                </Link>

                <div class="flex flex-wrap gap-2">
                    <Button
                        size="sm"
                        variant="secondary"
                        @click="loadData"
                        :disabled="isLoading"
                    >
                        <RefreshCwIcon :class="{ 'animate-spin': isLoading }" class="mr-2 h-4 w-4"/>
                        <span class="hidden sm:inline">{{ t('Refresh') }}</span>
                    </Button>
                </div>
            </div>

            <!-- Messages -->
            <div v-if="successMessage"
                 class="mb-6 rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 p-4">
                <p class="text-green-600 dark:text-green-400">{{ successMessage }}</p>
            </div>
            <div v-if="error"
                 class="mb-6 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 p-4">
                <p class="text-red-600 dark:text-red-400">{{ error }}</p>
            </div>

            <!-- Loading State -->
            <div v-if="isLoading" class="flex justify-center py-12">
                <div class="text-center">
                    <Spinner class="mx-auto h-8 w-8 text-indigo-600"/>
                    <p class="mt-2 text-gray-500">{{ t('Loading tournament data...') }}</p>
                </div>
            </div>

            <!-- Content -->
            <div v-else class="space-y-6">
                <!-- Tournament Header Card -->
                <Card class="shadow-lg">
                    <div
                        class="bg-gradient-to-r from-gray-50 to-indigo-50 dark:from-gray-800 dark:to-gray-700 p-6 sm:p-8">
                        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                            <div class="flex-1">
                                <div class="flex items-center gap-3 mb-3">
                                    <div
                                        class="h-12 w-12 rounded-full bg-indigo-600 flex items-center justify-center shadow-md">
                                        <PlayIcon class="h-6 w-6 text-white"/>
                                    </div>
                                    <div>
                                        <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white">
                                            {{ t('Match Management') }}
                                        </h1>
                                        <p class="text-gray-600 dark:text-gray-400 mt-1">
                                            {{ tournament?.name }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Auto-refresh indicator -->
                            <div v-if="matchStats.inProgress > 0"
                                 class="flex items-center gap-2 px-3 py-2 bg-yellow-100 dark:bg-yellow-900/30 rounded-lg">
                                <ActivityIcon class="h-4 w-4 text-yellow-600 dark:text-yellow-400 animate-pulse"/>
                                <span class="text-sm text-yellow-700 dark:text-yellow-300">
                                    {{ t('Auto-refreshing every 30s') }}
                                </span>
                            </div>
                        </div>
                    </div>
                </Card>

                <!-- Stats Cards -->
                <div class="grid grid-cols-2 sm:grid-cols-5 gap-4">
                    <Card class="shadow-md hover:shadow-lg transition-shadow">
                        <CardContent class="pt-6">
                            <div class="text-center">
                                <div class="inline-flex p-3 rounded-full bg-gray-100 dark:bg-gray-800 mb-3">
                                    <TrophyIcon class="h-6 w-6 text-gray-600 dark:text-gray-400"/>
                                </div>
                                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ matchStats.total }}</p>
                                <p class="text-sm text-gray-600 dark:text-gray-400">{{ t('Total Matches') }}</p>
                            </div>
                        </CardContent>
                    </Card>

                    <Card class="shadow-md hover:shadow-lg transition-shadow">
                        <CardContent class="pt-6">
                            <div class="text-center">
                                <div class="inline-flex p-3 rounded-full bg-green-100 dark:bg-green-900/30 mb-3">
                                    <CheckCircleIcon class="h-6 w-6 text-green-600 dark:text-green-400"/>
                                </div>
                                <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{
                                        matchStats.completed
                                    }}</p>
                                <p class="text-sm text-gray-600 dark:text-gray-400">{{ t('Completed') }}</p>
                            </div>
                        </CardContent>
                    </Card>

                    <Card class="shadow-md hover:shadow-lg transition-shadow">
                        <CardContent class="pt-6">
                            <div class="text-center">
                                <div class="inline-flex p-3 rounded-full bg-yellow-100 dark:bg-yellow-900/30 mb-3">
                                    <PlayIcon class="h-6 w-6 text-yellow-600 dark:text-yellow-400"/>
                                </div>
                                <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">
                                    {{ matchStats.inProgress }}</p>
                                <p class="text-sm text-gray-600 dark:text-gray-400">{{ t('In Progress') }}</p>
                            </div>
                        </CardContent>
                    </Card>

                    <Card class="shadow-md hover:shadow-lg transition-shadow">
                        <CardContent class="pt-6">
                            <div class="text-center">
                                <div class="inline-flex p-3 rounded-full bg-blue-100 dark:bg-blue-900/30 mb-3">
                                    <ClockIcon class="h-6 w-6 text-blue-600 dark:text-blue-400"/>
                                </div>
                                <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{
                                        matchStats.ready
                                    }}</p>
                                <p class="text-sm text-gray-600 dark:text-gray-400">{{ t('Ready') }}</p>
                            </div>
                        </CardContent>
                    </Card>

                    <Card class="col-span-2 sm:col-span-1 shadow-md hover:shadow-lg transition-shadow">
                        <CardContent class="pt-6">
                            <div class="text-center">
                                <div class="inline-flex p-3 rounded-full bg-gray-100 dark:bg-gray-800 mb-3">
                                    <AlertCircleIcon class="h-6 w-6 text-gray-600 dark:text-gray-400"/>
                                </div>
                                <p class="text-2xl font-bold text-gray-600 dark:text-gray-400">{{
                                        matchStats.pending
                                    }}</p>
                                <p class="text-sm text-gray-600 dark:text-gray-400">{{ t('Pending') }}</p>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Filters -->
                <Card class="shadow-lg">
                    <CardHeader class="bg-gray-50 dark:bg-gray-800/50">
                        <CardTitle class="flex items-center gap-2">
                            <FilterIcon class="h-5 w-5 text-indigo-600 dark:text-indigo-400"/>
                            {{ t('Filters') }}
                        </CardTitle>
                        <CardDescription>
                            {{ t('Filter matches to find what you need') }}
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="p-6">
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                            <!-- Search -->
                            <div class="sm:col-span-2 lg:col-span-1">
                                <Label for="search">{{ t('Search') }}</Label>
                                <div class="relative">
                                    <SearchIcon
                                        class="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400"/>
                                    <Input
                                        id="search"
                                        v-model="searchQuery"
                                        :placeholder="t('Player name or match code')"
                                        type="text"
                                        class="pl-10"
                                    />
                                </div>
                            </div>

                            <!-- Status Filter -->
                            <div>
                                <Label for="status">{{ t('Status') }}</Label>
                                <Select v-model="filterStatus">
                                    <SelectTrigger>
                                        <SelectValue :placeholder="t('All statuses')"/>
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">{{ t('All statuses') }}</SelectItem>
                                        <SelectItem value="pending">{{ t('Pending') }}</SelectItem>
                                        <SelectItem value="ready">{{ t('Ready') }}</SelectItem>
                                        <SelectItem value="in_progress">{{ t('In Progress') }}</SelectItem>
                                        <SelectItem value="verification">{{ t('Verification') }}</SelectItem>
                                        <SelectItem value="completed">{{ t('Completed') }}</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <!-- Stage Filter -->
                            <div>
                                <Label for="stage">{{ t('Stage') }}</Label>
                                <Select v-model="filterStage">
                                    <SelectTrigger>
                                        <SelectValue :placeholder="t('All stages')"/>
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">{{ t('All stages') }}</SelectItem>
                                        <SelectItem value="groups">{{ t('Groups') }}</SelectItem>
                                        <SelectItem value="bracket">{{ t('Bracket') }}</SelectItem>
                                        <SelectItem value="lower_bracket">{{ t('Lower Bracket') }}</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <!-- Group Filter (for group stage) -->
                            <div v-if="filterStage === 'groups' && groups.length > 0">
                                <Label for="group">{{ t('Group') }}</Label>
                                <Select v-model="filterGroup">
                                    <SelectTrigger>
                                        <SelectValue :placeholder="t('All groups')"/>
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">{{ t('All groups') }}</SelectItem>
                                        <SelectItem v-for="group in groups" :key="group.id" :value="group.group_code">
                                            {{ t('Group :code', {code: group.group_code}) }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <!-- Round Filter (for bracket) -->
                            <div v-if="filterStage !== 'groups' && uniqueRounds.length > 0">
                                <Label for="round">{{ t('Round') }}</Label>
                                <Select v-model="filterRound">
                                    <SelectTrigger>
                                        <SelectValue :placeholder="t('All rounds')"/>
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">{{ t('All rounds') }}</SelectItem>
                                        <SelectItem v-for="round in uniqueRounds" :key="round" :value="round">
                                            {{ round }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Matches List -->
                <MatchesList
                    :matches="filteredMatches"
                    :is-loading="false"
                    :show-table="true"
                    :show-scheduled-time="true"
                    :show-completed-time="true"
                    :is-clickable="canEditTournament"
                    :on-match-click="openMatchModal"
                />
            </div>
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
