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
import SingleEliminationBracket from '@/Components/Tournament/SingleEliminationBracket.vue';
import DoubleEliminationBracket from '@/Components/Tournament/DoubleEliminationBracket.vue';
import {useLocale} from '@/composables/useLocale';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import {apiClient} from '@/lib/apiClient';
import {useAuth} from "@/composables/useAuth";

import type {ClubTable, Tournament, TournamentBracket, TournamentMatch} from '@/types/api';
import {Head, router} from '@inertiajs/vue3';
import {
    AlertCircleIcon,
    ArrowLeftIcon,
    CalendarIcon,
    CheckCircleIcon,
    PlayIcon,
    RefreshCwIcon,
    SaveIcon,
    TrophyIcon,
    UserXIcon
} from 'lucide-vue-next';
import {computed, onMounted, ref, watch} from 'vue';


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
const isUpdatingMatch = ref(false);
const isLoadingTables = ref(false);

// Error handling
const error = ref<string | null>(null);
const successMessage = ref<string | null>(null);
const matchError = ref<string | null>(null);

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

// Match modal computed
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

const matchWinner = computed(() => {
    if (!canFinishMatch.value) return null;
    const racesTo = tournament.value?.races_to || 7;
    if (matchForm.value.player1_score >= racesTo) return selectedMatch.value?.player1_id;
    if (matchForm.value.player2_score >= racesTo) return selectedMatch.value?.player2_id;
    return null;
});

const isWalkoverMatch = computed(() => {
    if (!selectedMatch.value) return false;
    return (selectedMatch.value.player1_id && !selectedMatch.value.player2_id) ||
        (!selectedMatch.value.player1_id && selectedMatch.value.player2_id);
});

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

    const match = matches.value.find(m => m.id === matchId);
    if (!match) return;

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

        updateMatchesInBracket([response]);
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

        updateMatchesInBracket([response]);
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

        updateMatchesInBracket([response.match]);

        if (response.affected_matches && response.affected_matches.length > 0) {
            await loadSpecificMatches(response.affected_matches);
        }

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
        matchError.value = err.message || t('Failed to process walkover');
    } finally {
        isUpdatingMatch.value = false;
    }
};

const formatDateTime = (dateString: string | undefined): string => {
    if (!dateString) return '';
    return new Date(dateString).toLocaleString('uk-UK', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

// Watch for club table selection to update stream URL
watch(() => matchForm.value.club_table_id, (newTableId) => {
    if (newTableId) {
        const table = availableTables.value.find(t => t.id === newTableId);
        if (table?.stream_url) {
            matchForm.value.stream_url = table.stream_url;
        }
    }
});

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
                    <Button variant="outline" @click="router.visit(`/tournaments/${props.tournamentId}`)">
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
    <Modal :show="showMatchModal" :title="selectedMatch ? `${t('Match')} #${selectedMatch.match_code}` : ''"
           max-width="2xl" @close="closeMatchModal">
        <div v-if="selectedMatch" class="space-y-6">
            <!-- Match Header -->
            <div class="border-b pb-4">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-lg font-semibold">
                        {{ selectedMatch.round_display }}
                        <span v-if="selectedMatch.bracket_side" class="text-gray-500">
                            ({{ selectedMatch.bracket_side === 'upper' ? t('Upper Bracket') : t('Lower Bracket') }})
                        </span>
                    </h3>
                    <div class="flex items-center gap-2">
                        <span v-if="isWalkoverMatch"
                              class="px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300">
                            <UserXIcon class="inline h-4 w-4 mr-1"/>
                            {{ t('Walkover') }}
                        </span>
                        <span :class="[
                            'px-3 py-1 rounded-full text-sm font-medium',
                            selectedMatch.status === 'completed' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' :
                            selectedMatch.status === 'in_progress' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300' :
                            selectedMatch.status === 'verification' ? 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300' :
                            selectedMatch.status === 'ready' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300' :
                            'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'
                        ]">
                            {{ selectedMatch.status_display }}
                        </span>
                    </div>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    {{ t('Race to') }} {{ tournament?.races_to || 7 }}
                </p>
            </div>

            <!-- Walkover Notice -->
            <div v-if="isWalkoverMatch && selectedMatch.status === 'pending'"
                 class="rounded bg-yellow-100 p-4 dark:bg-yellow-900/30">
                <div class="flex items-center gap-2">
                    <UserXIcon class="h-5 w-5 text-yellow-600"/>
                    <p class="font-medium text-yellow-800 dark:text-yellow-300">{{ t('This is a walkover match') }}</p>
                </div>
                <p class="mt-2 text-sm text-yellow-700 dark:text-yellow-400">
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
                        <SelectItem v-if="isLoadingTables" :value="0">
                            {{ t('Loading tables...') }}
                        </SelectItem>
                        <SelectItem
                            v-for="table in availableTables"
                            v-else
                            :key="table.id"
                            :value="table.id"
                        >
                            {{ table.name }}
                        </SelectItem>
                    </SelectContent>
                </Select>
                <p v-if="selectedMatch.status === 'ready'" class="text-sm text-gray-500">
                    {{ t('Table assignment is required to start the match') }}
                </p>
            </div>

            <!-- Stream URL -->
            <div v-if="!isWalkoverMatch" class="space-y-2">
                <Label for="stream">{{ t('Stream URL') }}</Label>
                <Input
                    v-model="matchForm.stream_url"
                    :placeholder="t('https://youtube.com/watch?v=...')"
                    type="url"
                />
            </div>

            <!-- Scheduled Time -->
            <div class="space-y-2">
                <Label for="scheduled">{{ t('Scheduled At') }}</Label>
                <Input
                    v-model="matchForm.scheduled_at"
                    type="datetime-local"
                />
            </div>

            <!-- Admin Notes -->
            <div class="space-y-2">
                <Label for="notes">{{ t('Admin Notes') }}</Label>
                <Textarea
                    v-model="matchForm.admin_notes"
                    :placeholder="t('Internal notes about this match...')"
                    rows="3"
                />
            </div>

            <!-- Match Timeline -->
            <div v-if="selectedMatch.started_at || selectedMatch.completed_at" class="border-t pt-4">
                <h4 class="font-medium mb-2">{{ t('Timeline') }}</h4>
                <div class="space-y-1 text-sm text-gray-600 dark:text-gray-400">
                    <div v-if="selectedMatch.scheduled_at" class="flex items-center gap-2">
                        <CalendarIcon class="h-4 w-4"/>
                        {{ t('Scheduled') }}: {{ formatDateTime(selectedMatch.scheduled_at) }}
                    </div>
                    <div v-if="selectedMatch.started_at" class="flex items-center gap-2">
                        <PlayIcon class="h-4 w-4"/>
                        {{ t('Started') }}: {{ formatDateTime(selectedMatch.started_at) }}
                    </div>
                    <div v-if="selectedMatch.completed_at" class="flex items-center gap-2">
                        <CheckCircleIcon class="h-4 w-4"/>
                        {{ t('Completed') }}: {{ formatDateTime(selectedMatch.completed_at) }}
                    </div>
                </div>
            </div>

            <!-- Status Note for Verification -->
            <div v-if="selectedMatch.status === 'verification'" class="rounded bg-purple-100 p-3 dark:bg-purple-900/30">
                <div class="flex items-center gap-2">
                    <AlertCircleIcon class="h-5 w-5 text-purple-600"/>
                    <p class="font-medium text-purple-800 dark:text-purple-300">{{ t('Match needs verification') }}</p>
                </div>
                <p class="mt-2 text-sm text-purple-700 dark:text-purple-400">
                    {{ t('Players have submitted the result. Please verify and finish the match.') }}
                </p>
            </div>

            <!-- Error Display -->
            <div v-if="matchError" class="rounded bg-red-100 p-3 text-red-600 dark:bg-red-900/30 dark:text-red-400">
                {{ matchError }}
            </div>

            <!-- Winner Display -->
            <div v-if="canFinishMatch && !isWalkoverMatch" class="rounded bg-blue-100 p-3 dark:bg-blue-900/30">
                <p class="text-sm">
                    <span class="font-medium">{{ t('Winner') }}:</span>
                    <span v-if="matchWinner === selectedMatch.player1_id" class="ml-2">
                        {{ selectedMatch.player1?.firstname }} {{ selectedMatch.player1?.lastname }}
                    </span>
                    <span v-else-if="matchWinner === selectedMatch.player2_id" class="ml-2">
                        {{ selectedMatch.player2?.firstname }} {{ selectedMatch.player2?.lastname }}
                    </span>
                </p>
            </div>
        </div>

        <template #footer>
            <div class="flex justify-between">
                <Button variant="outline" @click="closeMatchModal">
                    {{ t('Cancel') }}
                </Button>

                <div class="flex gap-2">
                    <!-- Process Walkover Button -->
                    <Button
                        v-if="isWalkoverMatch && selectedMatch?.status === 'pending'"
                        :disabled="isUpdatingMatch"
                        @click="processWalkover"
                    >
                        <UserXIcon class="mr-2 h-4 w-4"/>
                        {{ t('Process Walkover') }}
                    </Button>

                    <!-- Start Match Button -->
                    <Button
                        v-if="selectedMatch?.status === 'ready' && !isWalkoverMatch"
                        :disabled="!canStartMatch || isUpdatingMatch"
                        @click="startMatch"
                    >
                        <PlayIcon class="mr-2 h-4 w-4"/>
                        {{ t('Start Match') }}
                    </Button>

                    <!-- Update Match Button -->
                    <Button
                        v-if="selectedMatch?.status === 'in_progress'"
                        :disabled="isUpdatingMatch"
                        variant="outline"
                        @click="updateMatch"
                    >
                        <SaveIcon class="mr-2 h-4 w-4"/>
                        {{ t('Save Changes') }}
                    </Button>

                    <!-- Finish Match Button -->
                    <Button
                        v-if="selectedMatch?.status === 'in_progress' || selectedMatch?.status === 'verification'"
                        :disabled="!canFinishMatch || isUpdatingMatch"
                        @click="finishMatch"
                    >
                        <CheckCircleIcon class="mr-2 h-4 w-4"/>
                        {{ t('Finish Match') }}
                    </Button>
                </div>
            </div>
        </template>
    </Modal>
</template>
