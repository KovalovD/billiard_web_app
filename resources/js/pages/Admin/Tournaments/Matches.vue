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
    Modal,
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
    Spinner,
    Textarea
} from '@/Components/ui';
import {useLocale} from '@/composables/useLocale';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import {apiClient} from '@/lib/apiClient';
import type {ClubTable, Tournament, TournamentGroup, TournamentMatch} from '@/types/api';
import {Head, router} from '@inertiajs/vue3';
import {
    AlertCircleIcon,
    ArrowLeftIcon,
    CheckCircleIcon,
    ClockIcon,
    FilterIcon,
    PlayIcon,
    RefreshCwIcon,
    SaveIcon,
    UserXIcon,
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
const isUpdatingMatch = ref(false);
const isLoadingTables = ref(false);

// Error handling
const error = ref<string | null>(null);
const successMessage = ref<string | null>(null);
const matchError = ref<string | null>(null);

// Filter states
const filterStatus = ref<string>('all');
const filterStage = ref<string>('all');
const filterGroup = ref<string>('all');
const filterRound = ref<string>('all');
const searchQuery = ref('');

// Match modal state
const showMatchModal = ref(false);
const selectedMatch = ref<TournamentMatch | null>(null);
const matchForm = ref({
    player1_score: 0,
    player2_score: 0,
    club_table_id: null as number | null,
    stream_url: '',
    status: 'pending' as string,
    scheduled_at: '',
    admin_notes: ''
});

// Computed properties
const canEditTournament = computed(() => {
    return tournament.value?.status === 'active';
});

const isWalkoverMatch = computed(() => {
    return selectedMatch.value && (
        (!selectedMatch.value.player1_id && selectedMatch.value.player2_id) ||
        (selectedMatch.value.player1_id && !selectedMatch.value.player2_id)
    );
});

const canStartMatch = computed(() => {
    if (!selectedMatch.value || !canEditTournament.value) return false;
    return selectedMatch.value.status === 'ready' &&
        selectedMatch.value.player1_id &&
        selectedMatch.value.player2_id &&
        matchForm.value.club_table_id !== null;
});

const canFinishMatch = computed(() => {
    if (!selectedMatch.value || !canEditTournament.value) return false;
    if (selectedMatch.value.status !== 'in_progress' && selectedMatch.value.status !== 'verification') return false;
    const racesTo = tournament.value?.races_to || 7;
    return matchForm.value.player1_score >= racesTo || matchForm.value.player2_score >= racesTo;
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

    selectedMatch.value = match;
    matchForm.value = {
        player1_score: match.player1_score || 0,
        player2_score: match.player2_score || 0,
        club_table_id: match.club_table_id || null,
        stream_url: match.stream_url || '',
        status: match.status,
        scheduled_at: match.scheduled_at ? new Date(match.scheduled_at).toISOString().slice(0, 16) : '',
        admin_notes: match.admin_notes || ''
    };

    showMatchModal.value = true;
    await loadAvailableTables();
};

const closeMatchModal = () => {
    showMatchModal.value = false;
    selectedMatch.value = null;
    matchError.value = null;
};

const startMatch = async () => {
    if (!canStartMatch.value || !selectedMatch.value) return;

    isUpdatingMatch.value = true;
    matchError.value = null;

    try {
        const response = await apiClient<TournamentMatch>(`/api/admin/tournaments/${props.tournamentId}/matches/${selectedMatch.value.id}/start`, {
            method: 'POST',
            data: {
                club_table_id: matchForm.value.club_table_id,
                stream_url: matchForm.value.stream_url
            }
        });

        // Update match in list
        const index = matches.value.findIndex(m => m.id === response.id);
        if (index !== -1) {
            matches.value[index] = response;
        }

        closeMatchModal();
        successMessage.value = t('Match started successfully');
    } catch (err: any) {
        matchError.value = err.message || t('Failed to start match');
    } finally {
        isUpdatingMatch.value = false;
    }
};

const updateMatch = async () => {
    if (!selectedMatch.value || !canEditTournament.value) return;

    isUpdatingMatch.value = true;
    matchError.value = null;

    try {
        const response = await apiClient<TournamentMatch>(`/api/admin/tournaments/${props.tournamentId}/matches/${selectedMatch.value.id}`, {
            method: 'PUT',
            data: matchForm.value
        });

        // Update match in list
        const index = matches.value.findIndex(m => m.id === response.id);
        if (index !== -1) {
            matches.value[index] = response;
        }

        closeMatchModal();
        successMessage.value = t('Match updated successfully');
    } catch (err: any) {
        matchError.value = err.message || t('Failed to update match');
    } finally {
        isUpdatingMatch.value = false;
    }
};

const finishMatch = async () => {
    if (!canFinishMatch.value || !selectedMatch.value) return;

    isUpdatingMatch.value = true;
    matchError.value = null;

    try {
        const response = await apiClient<{
            match: TournamentMatch;
            affected_matches: number[];
        }>(`/api/admin/tournaments/${props.tournamentId}/matches/${selectedMatch.value.id}/finish`, {
            method: 'POST',
            data: {
                player1_score: matchForm.value.player1_score,
                player2_score: matchForm.value.player2_score
            }
        });

        // Update the finished match
        const index = matches.value.findIndex(m => m.id === response.match.id);
        if (index !== -1) {
            matches.value[index] = response.match;
        }

        // Reload data to get updated matches
        await loadData();

        closeMatchModal();
        successMessage.value = t('Match finished successfully');
    } catch (err: any) {
        matchError.value = err.message || t('Failed to finish match');
    } finally {
        isUpdatingMatch.value = false;
    }
};

const processWalkover = async () => {
    if (!selectedMatch.value || !isWalkoverMatch.value) return;

    isUpdatingMatch.value = true;
    matchError.value = null;

    try {
        const racesTo = tournament.value?.races_to || 7;

        const response = await apiClient<{
            match: TournamentMatch;
            affected_matches: number[];
        }>(`/api/admin/tournaments/${props.tournamentId}/matches/${selectedMatch.value.id}/finish`, {
            method: 'POST',
            data: {
                player1_score: selectedMatch.value.player1_id ? racesTo : 0,
                player2_score: selectedMatch.value.player2_id ? racesTo : 0
            }
        });

        // Update match in list
        const index = matches.value.findIndex(m => m.id === response.match.id);
        if (index !== -1) {
            matches.value[index] = response.match;
        }

        // Reload data to get updated matches
        await loadData();

        closeMatchModal();
        successMessage.value = t('Walkover processed successfully');
    } catch (err: any) {
        matchError.value = err.message || t('Failed to process walkover');
    } finally {
        isUpdatingMatch.value = false;
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

    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">
                        {{ t('Tournament Matches') }}
                    </h1>
                    <p class="text-gray-600 dark:text-gray-400">
                        {{ tournament ? tournament.name : '' }}
                    </p>
                </div>
                <div class="flex space-x-3">
                    <Button
                        size="sm"
                        variant="outline"
                        @click="router.visit(`/tournaments/${tournament?.slug}`)"
                    >
                        <ArrowLeftIcon class="mr-2 h-4 w-4"/>
                        {{ t('Back to Tournament') }}
                    </Button>
                    <Button size="sm" variant="outline" @click="loadData">
                        <RefreshCwIcon class="mr-2 h-4 w-4"/>
                        {{ t('Refresh') }}
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
                <!-- Stats Cards -->
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
                    <Card>
                        <CardContent class="pt-6">
                            <div class="text-center">
                                <p class="text-2xl font-bold">{{ matchStats.total }}</p>
                                <p class="text-sm text-gray-600 dark:text-gray-400">{{ t('Total Matches') }}</p>
                            </div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent class="pt-6">
                            <div class="text-center">
                                <p class="text-2xl font-bold text-green-600">{{ matchStats.completed }}</p>
                                <p class="text-sm text-gray-600 dark:text-gray-400">{{ t('Completed') }}</p>
                            </div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent class="pt-6">
                            <div class="text-center">
                                <p class="text-2xl font-bold text-yellow-600">{{ matchStats.inProgress }}</p>
                                <p class="text-sm text-gray-600 dark:text-gray-400">{{ t('In Progress') }}</p>
                            </div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent class="pt-6">
                            <div class="text-center">
                                <p class="text-2xl font-bold text-blue-600">{{ matchStats.ready }}</p>
                                <p class="text-sm text-gray-600 dark:text-gray-400">{{ t('Ready') }}</p>
                            </div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent class="pt-6">
                            <div class="text-center">
                                <p class="text-2xl font-bold text-gray-600">{{ matchStats.pending }}</p>
                                <p class="text-sm text-gray-600 dark:text-gray-400">{{ t('Pending') }}</p>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Filters -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2 text-lg">
                            <FilterIcon class="h-5 w-5"/>
                            {{ t('Filters') }}
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-5">
                            <!-- Search -->
                            <div>
                                <Label for="search">{{ t('Search') }}</Label>
                                <Input
                                    id="search"
                                    v-model="searchQuery"
                                    :placeholder="t('Player name or match code')"
                                    type="text"
                                />
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

                        <div v-else class="space-y-4">
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
    <Modal :show="showMatchModal"
           :title="selectedMatch ? `${t('Match')} ${selectedMatch.match_code || `#${selectedMatch.id}`}` : ''"
           max-width="2xl" @close="closeMatchModal">
        <div v-if="selectedMatch" class="space-y-6">
            <!-- Match Header -->
            <div class="border-b pb-4">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-lg font-semibold">
                        {{ selectedMatch.stage_display }}
                        <span v-if="selectedMatch.round_display"> - {{ selectedMatch.round_display }}</span>
                    </h3>
                    <div class="flex items-center gap-2">
                        <span v-if="isWalkoverMatch"
                              class="px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300">
                            <UserXIcon class="inline h-4 w-4 mr-1"/>
                            {{ t('Walkover') }}
                        </span>
                        <span :class="[
                            'px-3 py-1 rounded-full text-sm font-medium',
                            getMatchStatusBadgeClass(selectedMatch.status)
                        ]">
                            {{ selectedMatch.status_display }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Error Message -->
            <div v-if="matchError" class="rounded bg-red-100 p-4 text-red-700">
                <AlertCircleIcon class="inline h-4 w-4 mr-1"/>
                {{ matchError }}
            </div>

            <!-- Walkover Notice -->
            <div v-if="isWalkoverMatch"
                 class="rounded bg-yellow-50 p-4 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-200">
                <p class="font-medium">{{ t('This is a walkover match.') }}</p>
                <p class="text-sm mt-1">
                    {{ t('One player is missing. Click "Process Walkover" to advance the present player.') }}
                </p>
            </div>

            <!-- Players and Scores -->
            <div class="grid grid-cols-2 gap-4">
                <div class="space-y-2">
                    <Label>
                        {{ selectedMatch.player1?.firstname }} {{ selectedMatch.player1?.lastname }}
                        <span v-if="!selectedMatch.player1" class="text-gray-500">{{ t('TBD') }}</span>
                    </Label>
                    <Input
                        v-model.number="matchForm.player1_score"
                        :disabled="selectedMatch.status === 'completed' || isWalkoverMatch"
                        :max="tournament?.races_to || 7"
                        min="0"
                        type="number"
                    />
                </div>
                <div class="space-y-2">
                    <Label>
                        {{ selectedMatch.player2?.firstname }} {{ selectedMatch.player2?.lastname }}
                        <span v-if="!selectedMatch.player2" class="text-gray-500">{{ t('TBD') }}</span>
                    </Label>
                    <Input
                        v-model.number="matchForm.player2_score"
                        :disabled="selectedMatch.status === 'completed' || isWalkoverMatch"
                        :max="tournament?.races_to || 7"
                        min="0"
                        type="number"
                    />
                </div>
            </div>

            <!-- Table Assignment -->
            <div v-if="!isWalkoverMatch" class="space-y-2">
                <Label for="table">{{ t('Table Assignment') }} *</Label>
                <Select v-model="matchForm.club_table_id" :disabled="selectedMatch.status !== 'ready'">
                    <SelectTrigger>
                        <SelectValue :placeholder="t('Select a table')"/>
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem v-for="table in availableTables" :key="table.id" :value="table.id">
                            {{ table.name }}
                            <span v-if="table.location" class="text-gray-500"> - {{ table.location }}</span>
                        </SelectItem>
                    </SelectContent>
                </Select>
                <p v-if="isLoadingTables" class="text-sm text-gray-500">
                    <Spinner class="inline h-3 w-3"/>
                    {{ t('Loading tables...') }}
                </p>
            </div>

            <!-- Stream URL -->
            <div v-if="!isWalkoverMatch" class="space-y-2">
                <Label for="stream">{{ t('Stream URL') }}</Label>
                <Input
                    id="stream"
                    v-model="matchForm.stream_url"
                    :placeholder="t('https://...')"
                    type="url"
                />
            </div>

            <!-- Scheduled Time -->
            <div class="space-y-2">
                <Label for="scheduled">{{ t('Scheduled Time') }}</Label>
                <Input
                    id="scheduled"
                    v-model="matchForm.scheduled_at"
                    type="datetime-local"
                />
            </div>

            <!-- Admin Notes -->
            <div class="space-y-2">
                <Label for="notes">{{ t('Admin Notes') }}</Label>
                <Textarea
                    id="notes"
                    v-model="matchForm.admin_notes"
                    :placeholder="t('Internal notes about this match...')"
                    rows="3"
                />
            </div>

            <!-- Actions -->
            <div class="flex justify-between border-t pt-4">
                <Button variant="outline" @click="closeMatchModal">
                    {{ t('Cancel') }}
                </Button>

                <div class="flex gap-2">
                    <!-- Process Walkover -->
                    <Button
                        v-if="isWalkoverMatch && selectedMatch.status !== 'completed'"
                        :disabled="isUpdatingMatch"
                        variant="destructive"
                        @click="processWalkover"
                    >
                        <UserXIcon class="mr-2 h-4 w-4"/>
                        {{ t('Process Walkover') }}
                    </Button>

                    <!-- Update Match -->
                    <Button
                        v-if="selectedMatch.status !== 'pending' && selectedMatch.status !== 'completed' && !isWalkoverMatch"
                        :disabled="isUpdatingMatch"
                        variant="outline"
                        @click="updateMatch"
                    >
                        <SaveIcon class="mr-2 h-4 w-4"/>
                        {{ t('Update') }}
                    </Button>

                    <!-- Start Match -->
                    <Button
                        v-if="canStartMatch && !isWalkoverMatch"
                        :disabled="isUpdatingMatch"
                        @click="startMatch"
                    >
                        <PlayIcon class="mr-2 h-4 w-4"/>
                        {{ t('Start Match') }}
                    </Button>

                    <!-- Finish Match -->
                    <Button
                        v-if="canFinishMatch && !isWalkoverMatch"
                        :disabled="isUpdatingMatch"
                        variant="default"
                        @click="finishMatch"
                    >
                        <CheckCircleIcon class="mr-2 h-4 w-4"/>
                        {{ t('Finish Match') }}
                    </Button>
                </div>
            </div>

            <!-- Loading indicator -->
            <div v-if="isUpdatingMatch" class="text-center text-gray-500">
                <Spinner class="mx-auto h-6 w-6"/>
                <p class="mt-2 text-sm">{{ t('Processing...') }}</p>
            </div>
        </div>
    </Modal>
</template>
