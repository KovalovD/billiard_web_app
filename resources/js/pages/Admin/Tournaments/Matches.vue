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
import {useLocale} from '@/composables/useLocale';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import {apiClient} from '@/lib/apiClient';
import type {ClubTable, Tournament, TournamentGroup, TournamentMatch} from '@/types/api';
import {Head, router} from '@inertiajs/vue3';
import {ArrowLeftIcon, ClockIcon, FilterIcon, RefreshCwIcon,} from 'lucide-vue-next';
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

    // Sort matches
    return filtered.sort((a, b) => {
        // First by status (ready/in_progress first)
        const statusOrder = ['in_progress', 'verification', 'ready', 'pending', 'completed'];
        const statusDiff = statusOrder.indexOf(a.status) - statusOrder.indexOf(b.status);
        if (statusDiff !== 0) return statusDiff;

        // Then by scheduled time
        if (a.scheduled_at && b.scheduled_at) {
            return new Date(a.scheduled_at).getTime() - new Date(b.scheduled_at).getTime();
        }

        // Then by match code
        return (a.match_code || '').localeCompare(b.match_code || '');
    });
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

const getMatchStatusBadgeClass = (status: string) => {
    switch (status) {
        case 'completed':
            return 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300';
        case 'in_progress':
            return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300';
        case 'verification':
            return 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300';
        case 'ready':
            return 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300';
        default:
            return 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-300';
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

    <div class="py-6 sm:py-12">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <!-- Mobile-optimized Header -->
            <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-xl sm:text-2xl font-semibold text-gray-800 dark:text-gray-200">
                        {{ t('Tournament Matches') }}
                    </h1>
                    <p class="text-sm sm:text-base text-gray-600 dark:text-gray-400">
                        {{ tournament ? tournament.name : '' }}
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <Button
                        size="sm"
                        variant="outline"
                        @click="router.visit(`/tournaments/${tournament?.slug}`)"
                    >
                        <ArrowLeftIcon class="mr-2 h-4 w-4"/>
                        <span class="hidden sm:inline">{{ t('Back to Tournament') }}</span>
                        <span class="sm:hidden">{{ t('Back') }}</span>
                    </Button>
                    <Button size="sm" variant="outline" @click="loadData">
                        <RefreshCwIcon class="mr-2 h-4 w-4"/>
                        <span class="hidden sm:inline">{{ t('Refresh') }}</span>
                    </Button>
                </div>
            </div>

            <!-- Messages -->
            <div v-if="successMessage" class="mb-4 rounded bg-green-100 p-4 text-green-700">
                {{ successMessage }}
            </div>
            <div v-if="error" class="mb-4 rounded bg-red-100 p-4 text-red-700">
                {{ error }}
            </div>

            <!-- Loading State -->
            <div v-if="isLoading" class="flex items-center justify-center p-8">
                <Spinner/>
            </div>

            <!-- Content -->
            <div v-else class="space-y-6">
                <!-- Stats Cards - Mobile optimized -->
                <div class="grid grid-cols-2 sm:grid-cols-5 gap-2 sm:gap-4">
                    <Card>
                        <CardContent class="pt-4 sm:pt-6">
                            <div class="text-center">
                                <p class="text-xl sm:text-2xl font-bold">{{ matchStats.total }}</p>
                                <p class="text-xs sm:text-sm text-gray-600 dark:text-gray-400">{{
                                        t('Total Matches')
                                    }}</p>
                            </div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent class="pt-4 sm:pt-6">
                            <div class="text-center">
                                <p class="text-xl sm:text-2xl font-bold text-green-600">{{ matchStats.completed }}</p>
                                <p class="text-xs sm:text-sm text-gray-600 dark:text-gray-400">{{ t('Completed') }}</p>
                            </div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent class="pt-4 sm:pt-6">
                            <div class="text-center">
                                <p class="text-xl sm:text-2xl font-bold text-yellow-600">{{ matchStats.inProgress }}</p>
                                <p class="text-xs sm:text-sm text-gray-600 dark:text-gray-400">{{
                                        t('In Progress')
                                    }}</p>
                            </div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent class="pt-4 sm:pt-6">
                            <div class="text-center">
                                <p class="text-xl sm:text-2xl font-bold text-blue-600">{{ matchStats.ready }}</p>
                                <p class="text-xs sm:text-sm text-gray-600 dark:text-gray-400">{{ t('Ready') }}</p>
                            </div>
                        </CardContent>
                    </Card>
                    <Card class="col-span-2 sm:col-span-1">
                        <CardContent class="pt-4 sm:pt-6">
                            <div class="text-center">
                                <p class="text-xl sm:text-2xl font-bold text-gray-600">{{ matchStats.pending }}</p>
                                <p class="text-xs sm:text-sm text-gray-600 dark:text-gray-400">{{ t('Pending') }}</p>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Filters - Mobile optimized -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2 text-lg">
                            <FilterIcon class="h-5 w-5"/>
                            {{ t('Filters') }}
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                            <!-- Search -->
                            <div>
                                <Label for="search" class="text-sm">{{ t('Search') }}</Label>
                                <Input
                                    id="search"
                                    v-model="searchQuery"
                                    :placeholder="t('Player name or match code')"
                                    type="text"
                                    class="text-sm"
                                />
                            </div>

                            <!-- Status Filter -->
                            <div>
                                <Label for="status" class="text-sm">{{ t('Status') }}</Label>
                                <Select v-model="filterStatus">
                                    <SelectTrigger class="text-sm">
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
                                <Label for="stage" class="text-sm">{{ t('Stage') }}</Label>
                                <Select v-model="filterStage">
                                    <SelectTrigger class="text-sm">
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
                                <Label for="group" class="text-sm">{{ t('Group') }}</Label>
                                <Select v-model="filterGroup">
                                    <SelectTrigger class="text-sm">
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
                                <Label for="round" class="text-sm">{{ t('Round') }}</Label>
                                <Select v-model="filterRound">
                                    <SelectTrigger class="text-sm">
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

                <!-- Matches List - Mobile optimized -->
                <Card>
                    <CardHeader>
                        <CardTitle>{{ t('Matches') }}</CardTitle>
                        <CardDescription>
                            {{ t('Showing :count matches', {count: filteredMatches.length}) }}
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div v-if="filteredMatches.length === 0" class="py-8 text-center text-gray-500">
                            {{ t('No matches found') }}
                        </div>

                        <!-- Mobile view -->
                        <div class="space-y-4 sm:hidden">
                            <div
                                v-for="match in filteredMatches"
                                :key="match.id"
                                class="rounded-lg border p-4"
                            >
                                <!-- Match header -->
                                <div class="flex items-center justify-between mb-3">
                                    <div>
                                        <p class="font-medium text-sm">
                                            {{ match.match_code || `#${match.id}` }}
                                        </p>
                                        <p class="text-xs text-gray-500">
                                            {{ match.stage_display }}
                                            <span v-if="match.round_display"> - {{ match.round_display }}</span>
                                        </p>
                                    </div>
                                    <span
                                        :class="[
                                            'px-2 py-1 text-xs font-medium rounded-full',
                                            getMatchStatusBadgeClass(match.status)
                                        ]"
                                    >
                                        {{ match.status_display }}
                                    </span>
                                </div>

                                <!-- Players and scores -->
                                <div class="space-y-2 mb-3">
                                    <div class="flex items-center justify-between">
                                        <p :class="{'text-green-600 font-bold': match.winner_id === match.player1_id}"
                                           class="text-sm">
                                            {{ match.player1?.firstname }} {{ match.player1?.lastname }}
                                            <span v-if="!match.player1" class="text-gray-400">{{ t('TBD') }}</span>
                                        </p>
                                        <p class="text-lg font-bold">
                                            {{ match.player1_score ?? '-' }}
                                        </p>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <p :class="{'text-green-600 font-bold': match.winner_id === match.player2_id}"
                                           class="text-sm">
                                            {{ match.player2?.firstname }} {{ match.player2?.lastname }}
                                            <span v-if="!match.player2" class="text-gray-400">{{ t('TBD') }}</span>
                                        </p>
                                        <p class="text-lg font-bold">
                                            {{ match.player2_score ?? '-' }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Match details -->
                                <div class="space-y-1 text-xs text-gray-500">
                                    <p v-if="match.club_table">
                                        {{ t('Table') }}: {{ match.club_table.name }}
                                    </p>
                                    <p v-if="match.scheduled_at">
                                        <ClockIcon class="inline h-3 w-3"/>
                                        {{ new Date(match.scheduled_at).toLocaleString() }}
                                    </p>
                                </div>

                                <!-- Actions -->
                                <div class="mt-3">
                                    <Button
                                        :disabled="!canEditTournament"
                                        size="sm"
                                        variant="outline"
                                        class="w-full"
                                        @click="openMatchModal(match)"
                                    >
                                        {{ t('Manage') }}
                                    </Button>
                                </div>
                            </div>
                        </div>

                        <!-- Desktop view -->
                        <div class="hidden sm:block space-y-4">
                            <div
                                v-for="match in filteredMatches"
                                :key="match.id"
                                class="flex items-center justify-between rounded-lg border p-4 hover:bg-gray-50 dark:hover:bg-gray-800/50"
                            >
                                <!-- Match Info -->
                                <div class="flex-1">
                                    <div class="flex items-center gap-4">
                                        <!-- Match Code & Stage -->
                                        <div class="min-w-[120px]">
                                            <p class="font-medium">
                                                {{ match.match_code || `#${match.id}` }}
                                            </p>
                                            <p class="text-sm text-gray-500">
                                                {{ match.stage_display }}
                                                <span v-if="match.round_display"> - {{ match.round_display }}</span>
                                            </p>
                                        </div>

                                        <!-- Players -->
                                        <div class="flex-1">
                                            <div class="flex items-center justify-between">
                                                <div class="flex-1">
                                                    <p :class="{'text-green-600': match.winner_id === match.player1_id}"
                                                       class="font-medium">
                                                        {{ match.player1?.firstname }} {{ match.player1?.lastname }}
                                                        <span v-if="!match.player1" class="text-gray-400">{{
                                                                t('TBD')
                                                            }}</span>
                                                    </p>
                                                    <p :class="{'text-green-600': match.winner_id === match.player2_id}"
                                                       class="font-medium">
                                                        {{ match.player2?.firstname }} {{ match.player2?.lastname }}
                                                        <span v-if="!match.player2" class="text-gray-400">{{
                                                                t('TBD')
                                                            }}</span>
                                                    </p>
                                                </div>

                                                <!-- Scores -->
                                                <div class="mx-4 text-center">
                                                    <p class="text-xl font-bold">
                                                        {{ match.player1_score ?? '-' }}
                                                    </p>
                                                    <p class="text-xl font-bold">
                                                        {{ match.player2_score ?? '-' }}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Status & Table -->
                                        <div class="text-right">
                                            <span :class="[
                                                'inline-flex px-2 py-1 text-xs font-medium rounded-full',
                                                getMatchStatusBadgeClass(match.status)
                                            ]">
                                                {{ match.status_display }}
                                            </span>
                                            <p v-if="match.club_table" class="mt-1 text-sm text-gray-500">
                                                {{ match.club_table.name }}
                                            </p>
                                            <p v-if="match.scheduled_at" class="mt-1 text-sm text-gray-500">
                                                <ClockIcon class="inline h-3 w-3"/>
                                                {{ new Date(match.scheduled_at).toLocaleString() }}
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Actions -->
                                <div class="ml-4">
                                    <Button
                                        :disabled="!canEditTournament"
                                        size="sm"
                                        variant="outline"
                                        @click="openMatchModal(match)"
                                    >
                                        {{ t('Manage') }}
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
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
