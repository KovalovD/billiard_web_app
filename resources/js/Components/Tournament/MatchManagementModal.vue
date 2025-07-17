<script lang="ts" setup>
import {
    Button,
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
import {apiClient} from '@/lib/apiClient';
import type {ClubTable, Tournament, TournamentMatch, TournamentPlayer} from '@/types/api';
import {
    AlertCircleIcon,
    CheckCircleIcon,
    EditIcon,
    MonitorIcon,
    PlayIcon,
    SaveIcon,
    TrophyIcon,
    UserIcon,
    UserXIcon,
} from 'lucide-vue-next';
import {computed, ref, watch} from 'vue';

interface Props {
    show: boolean;
    match: TournamentMatch | null;
    tournament: Tournament | null;
    availableTables: ClubTable[];
    isLoadingTables: boolean;
    canEditTournament: boolean;
}

interface Emits {
    (e: 'close'): void;
    (e: 'start-match', data: { club_table_id: number | null; stream_url: string; admin_notes: string | null }): void;
    (e: 'update-match', data: any): void;
    (e: 'finish-match', data: { player1_score: number; player2_score: number; admin_notes: string | null }): void;
    (e: 'process-walkover'): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

const {t} = useLocale();

// Form state
const matchForm = ref({
    player1_id: null as number | null,
    player2_id: null as number | null,
    player1_score: 0,
    player2_score: 0,
    club_table_id: null as number | null,
    stream_url: '',
    status: 'pending' as string,
    scheduled_at: '',
    admin_notes: ''
});

// Track original scores to detect changes
const originalScores = ref({
    player1_score: 0,
    player2_score: 0
});

// Local state
const isUpdatingMatch = ref(false);
const matchError = ref<string | null>(null);
const availablePlayers = ref<TournamentPlayer[]>([]);
const isLoadingPlayers = ref(false);
const isEditMode = ref(false);

// Computed properties
const isWalkoverMatch = computed(() => {
    if (!props.match) return false;
    return (props.match.player1_id && !props.match.player2_id) ||
        (!props.match.player1_id && props.match.player2_id);
});

const canStartMatch = computed(() => {
    if (!props.match || !props.canEditTournament) return false;
    return props.match.status === 'ready' &&
        props.match.player1_id &&
        props.match.player2_id &&
        matchForm.value.club_table_id !== null;
});

const canFinishMatch = computed(() => {
    if (!props.match || !props.canEditTournament) return false;
    if (props.match.status !== 'in_progress' && props.match.status !== 'verification') return false;
    const racesTo = props.match?.races_to || 7;
    return matchForm.value.player1_score >= racesTo || matchForm.value.player2_score >= racesTo;
});

const scoresChanged = computed(() => {
    return matchForm.value.player1_score !== originalScores.value.player1_score ||
        matchForm.value.player2_score !== originalScores.value.player2_score;
});

const canUpdateScores = computed(() => {
    if (!props.match || !props.canEditTournament) return false;
    return (props.match.status === 'in_progress' || props.match.status === 'verification') &&
        scoresChanged.value &&
        !canFinishMatch.value;
});

const matchWinner = computed(() => {
    const racesTo = props.match?.races_to || 7;
    if (matchForm.value.player1_score >= racesTo) return matchForm.value.player1_id || props.match?.player1_id;
    if (matchForm.value.player2_score >= racesTo) return matchForm.value.player2_id || props.match?.player2_id;
    return null;
});

const statusOptions = [
    {value: 'pending', label: t('Pending')},
    {value: 'ready', label: t('Ready')},
    {value: 'in_progress', label: t('In Progress')},
    {value: 'verification', label: t('Verification')},
    {value: 'completed', label: t('Completed')},
    {value: 'cancelled', label: t('Cancelled')}
];

const getStatusColor = (status: string) => {
    switch (status) {
        case 'completed':
            return 'text-green-600 bg-green-50 dark:bg-green-900/20 dark:text-green-400';
        case 'in_progress':
            return 'text-yellow-600 bg-yellow-50 dark:bg-yellow-900/20 dark:text-yellow-400';
        case 'verification':
            return 'text-purple-600 bg-purple-50 dark:bg-purple-900/20 dark:text-purple-400';
        case 'ready':
            return 'text-blue-600 bg-blue-50 dark:bg-blue-900/20 dark:text-blue-400';
        case 'cancelled':
            return 'text-red-600 bg-red-50 dark:bg-red-900/20 dark:text-red-400';
        default:
            return 'text-gray-600 bg-gray-50 dark:bg-gray-800 dark:text-gray-400';
    }
};

// Load available players
const loadAvailablePlayers = async () => {
    if (!props.tournament || !isEditMode.value) return;

    isLoadingPlayers.value = true;
    try {
        const response = await apiClient<TournamentPlayer[]>(`/api/tournaments/${props.tournament.id}/players`);
        availablePlayers.value = response.filter(p => p.status === 'confirmed');
    } catch (err) {
        console.error('Failed to load players:', err);
        availablePlayers.value = [];
    } finally {
        isLoadingPlayers.value = false;
    }
};

// Watch for match changes to update form
watch(() => props.match, (newMatch) => {
    if (newMatch) {
        matchForm.value = {
            player1_id: newMatch.player1_id,
            player2_id: newMatch.player2_id,
            player1_score: newMatch.player1_score || 0,
            player2_score: newMatch.player2_score || 0,
            club_table_id: newMatch.club_table_id || null,
            stream_url: newMatch.stream_url || '',
            status: newMatch.status,
            scheduled_at: newMatch.scheduled_at ? new Date(newMatch.scheduled_at).toISOString().slice(0, 16) : '',
            admin_notes: newMatch.admin_notes || ''
        };
        // Store original scores
        originalScores.value = {
            player1_score: newMatch.player1_score || 0,
            player2_score: newMatch.player2_score || 0
        };
        isEditMode.value = false;
    }
});

// Watch for edit mode to load players
watch(() => isEditMode.value, (newValue) => {
    if (newValue && props.tournament) {
        loadAvailablePlayers();
    }
});

// Watch for club table selection to update stream URL
watch(() => matchForm.value.club_table_id, (newTableId) => {
    if (newTableId && props.availableTables) {
        const table = props.availableTables.find(t => t.id === newTableId);
        if (table?.stream_url) {
            matchForm.value.stream_url = table.stream_url;
        }
    }
});

// Methods
const handleClose = () => {
    matchError.value = null;
    isEditMode.value = false;
    emit('close');
};

const toggleEditMode = () => {
    isEditMode.value = !isEditMode.value;
};

const startMatch = async () => {
    if (!canStartMatch.value || !props.match) return;

    isUpdatingMatch.value = true;
    matchError.value = null;

    try {
        emit('start-match', {
            club_table_id: matchForm.value.club_table_id,
            stream_url: matchForm.value.stream_url,
            admin_notes: matchForm.value.admin_notes
        });
    } finally {
        isUpdatingMatch.value = false;
    }
};

const updateMatch = async () => {
    if (!props.match || !props.canEditTournament) return;

    isUpdatingMatch.value = true;
    matchError.value = null;

    try {
        emit('update-match', {
            player1_id: matchForm.value.player1_id,
            player2_id: matchForm.value.player2_id,
            player1_score: matchForm.value.player1_score,
            player2_score: matchForm.value.player2_score,
            club_table_id: matchForm.value.club_table_id,
            stream_url: matchForm.value.stream_url,
            status: matchForm.value.status,
            scheduled_at: matchForm.value.scheduled_at,
            admin_notes: matchForm.value.admin_notes
        });
    } finally {
        isUpdatingMatch.value = false;
    }
};

const updateScores = async () => {
    if (!props.match || !props.canEditTournament) return;

    isUpdatingMatch.value = true;
    matchError.value = null;

    try {
        emit('update-match', {
            player1_score: matchForm.value.player1_score,
            player2_score: matchForm.value.player2_score,
        });
        // Update original scores after successful update
        originalScores.value = {
            player1_score: matchForm.value.player1_score,
            player2_score: matchForm.value.player2_score
        };
    } finally {
        isUpdatingMatch.value = false;
    }
};

const finishMatch = async () => {
    if (!canFinishMatch.value || !props.match) return;

    isUpdatingMatch.value = true;
    matchError.value = null;

    try {
        emit('finish-match', {
            player1_score: matchForm.value.player1_score,
            player2_score: matchForm.value.player2_score,
            admin_notes: matchForm.value.admin_notes
        });
    } finally {
        isUpdatingMatch.value = false;
    }
};

const processWalkover = async () => {
    if (!props.match || !isWalkoverMatch.value) return;

    isUpdatingMatch.value = true;
    matchError.value = null;

    try {
        emit('process-walkover');
    } finally {
        isUpdatingMatch.value = false;
    }
};

const formatDateTime = (dateString: string | undefined): string => {
    if (!dateString) return '';
    return new Date(dateString).toLocaleString('uk-UK', {
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const getPlayerDisplay = (playerId: number | null, playerData: any) => {
    if (!playerId) return t('TBD');
    if (playerData) {
        return `${playerData.firstname} ${playerData.lastname}`;
    }
    const player = availablePlayers.value.find(p => p.user_id === playerId);
    if (player?.user) {
        return `${player.user.firstname} ${player.user.lastname}`;
    }
    return t('Unknown');
};

// Expose error setter for parent component
const setError = (error: string | null) => {
    matchError.value = error;
};

defineExpose({setError});
</script>

<template>
    <Modal
        :show="show"
        :title="match ? `${t('Match')} ${match.match_code || `#${match.id}`}` : t('Loading...')"
        max-width="lg"
        @close="handleClose"
    >
        <!-- Loading state -->
        <div v-if="!match && show" class="flex items-center justify-center py-12">
            <div class="text-center">
                <Spinner class="h-8 w-8 text-primary mx-auto mb-2"/>
                <p class="text-sm text-gray-500">{{ t('Loading match details...') }}</p>
            </div>
        </div>

        <div v-else-if="match" class="space-y-3">
            <!-- Compact Header -->
            <div class="flex items-center justify-between p-2 bg-gray-50 dark:bg-gray-800/50 rounded">
                <div class="flex items-center gap-2">
                    <TrophyIcon class="h-4 w-4 text-gray-400"/>
                    <span class="text-sm font-medium">
                        {{ match.round_display }}
                        <span v-if="match.bracket_side" class="text-gray-500 text-xs">
                            ({{ match.bracket_side === 'upper' ? t('Upper') : t('Lower') }})
                        </span>
                    </span>
                </div>
                <div class="flex items-center gap-2">
                    <span :class="['px-2 py-0.5 rounded text-xs font-medium', getStatusColor(match.status)]">
                        {{ match.status_display }}
                    </span>
                    <Button
                        v-if="canEditTournament"
                        variant="ghost"
                        size="icon"
                        class="h-7 w-7"
                        @click="toggleEditMode"
                        :title="t('Advanced Edit')"
                    >
                        <EditIcon class="h-3.5 w-3.5"/>
                    </Button>
                </div>
            </div>

            <!-- Quick Score Entry (Always visible for in_progress/verification) -->
            <div v-if="!isEditMode" class="bg-white dark:bg-gray-900 rounded border dark:border-gray-700">
                <div class="grid grid-cols-2 divide-x dark:divide-gray-700">
                    <!-- Player 1 -->
                    <div class="p-3">
                        <div class="flex items-center gap-2 mb-2">
                            <UserIcon class="h-4 w-4 text-gray-400"/>
                            <span :class="[
                                'text-sm font-medium truncate',
                                matchWinner === match.player1_id && 'text-green-600 dark:text-green-400'
                            ]">
                                {{ getPlayerDisplay(match.player1_id, match.player1) }}
                            </span>
                        </div>
                        <Input
                            v-model.number="matchForm.player1_score"
                            :disabled="match.status === 'completed' || match.status === 'pending' || isWalkoverMatch"
                            :max="match?.races_to || 7"
                            min="0"
                            type="number"
                            class="w-full text-center text-2xl font-bold h-12"
                        />
                    </div>

                    <!-- Player 2 -->
                    <div class="p-3">
                        <div class="flex items-center gap-2 mb-2">
                            <UserIcon class="h-4 w-4 text-gray-400"/>
                            <span :class="[
                                'text-sm font-medium truncate',
                                matchWinner === match.player2_id && 'text-green-600 dark:text-green-400'
                            ]">
                                {{ getPlayerDisplay(match.player2_id, match.player2) }}
                            </span>
                        </div>
                        <Input
                            v-model.number="matchForm.player2_score"
                            :disabled="match.status === 'completed' || match.status === 'pending' || isWalkoverMatch"
                            :max="match?.races_to || 7"
                            min="0"
                            type="number"
                            class="w-full text-center text-2xl font-bold h-12"
                        />
                    </div>
                </div>

                <!-- Winner Display -->
                <div v-if="canFinishMatch && !isWalkoverMatch"
                     class="px-3 py-2 bg-green-50 dark:bg-green-900/20 border-t dark:border-gray-700">
                    <p class="text-sm text-center">
                        <TrophyIcon class="inline h-4 w-4 text-green-600 mr-1"/>
                        <span class="font-medium text-green-700 dark:text-green-300">{{ t('Winner') }}:</span>
                        <span v-if="matchWinner === match.player1_id" class="ml-1">
                            {{ getPlayerDisplay(match.player1_id, match.player1) }}
                        </span>
                        <span v-else-if="matchWinner === match.player2_id" class="ml-1">
                            {{ getPlayerDisplay(match.player2_id, match.player2) }}
                        </span>
                    </p>
                </div>

                <!-- Update Scores Notice -->
                <div v-if="canUpdateScores && !isWalkoverMatch"
                     class="px-3 py-2 bg-blue-50 dark:bg-blue-900/20 border-t dark:border-gray-700">
                    <p class="text-xs text-center text-blue-700 dark:text-blue-300">
                        {{ t('Scores have been changed. Click "Update Scores" to save.') }}
                    </p>
                </div>
            </div>

            <!-- Quick Table Selection for Ready matches -->
            <div v-if="!isEditMode && match.status === 'ready' && !isWalkoverMatch">
                <Label class="text-xs mb-1 flex items-center gap-1">
                    <MonitorIcon class="h-3 w-3"/>
                    {{ t('Select Table') }} *
                </Label>
                <Select v-model="matchForm.club_table_id">
                    <SelectTrigger class="h-9">
                        <SelectValue :placeholder="t('Select table')"/>
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem
                            v-for="table in availableTables"
                            :key="table.id"
                            :value="table.id"
                        >
                            {{ table.name }}
                        </SelectItem>
                    </SelectContent>
                </Select>
            </div>

            <!-- Match Info Display (non-edit mode) -->
            <div v-if="!isEditMode && (match.club_table || match.started_at || match.completed_at)"
                 class="text-xs text-gray-500 space-y-1">
                <div v-if="match.club_table" class="flex items-center gap-1">
                    <MonitorIcon class="h-3 w-3"/>
                    {{ t('Table') }}: {{ match.club_table.name }}
                </div>
                <div v-if="match.started_at" class="flex items-center gap-1">
                    <PlayIcon class="h-3 w-3"/>
                    {{ t('Started') }}: {{ formatDateTime(match.started_at) }}
                </div>
                <div v-if="match.completed_at" class="flex items-center gap-1">
                    <CheckCircleIcon class="h-3 w-3"/>
                    {{ t('Completed') }}: {{ formatDateTime(match.completed_at) }}
                </div>
            </div>

            <!-- Advanced Edit Mode -->
            <div v-if="isEditMode" class="space-y-3 border-t pt-3">
                <div class="text-sm font-medium text-orange-600 dark:text-orange-400 mb-2">
                    {{ t('Advanced Edit Mode') }}
                </div>

                <!-- Player Selection -->
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <Label class="text-xs mb-0.5">{{ t('Player 1') }}</Label>
                        <Select v-model="matchForm.player1_id" :disabled="isLoadingPlayers">
                            <SelectTrigger class="h-8 text-sm">
                                <SelectValue :placeholder="t('Select player')"/>
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem :value="null">{{ t('TBD') }}</SelectItem>
                                <SelectItem
                                    v-for="player in availablePlayers"
                                    :key="player.user_id"
                                    :value="player.user_id"
                                    :disabled="player.user_id === matchForm.player2_id"
                                >
                                    {{ player.user?.firstname }} {{ player.user?.lastname }}
                                    <span class="text-xs text-gray-500 ml-1">(#{{ player.seed_number }})</span>
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div>
                        <Label class="text-xs mb-0.5">{{ t('Player 2') }}</Label>
                        <Select v-model="matchForm.player2_id" :disabled="isLoadingPlayers">
                            <SelectTrigger class="h-8 text-sm">
                                <SelectValue :placeholder="t('Select player')"/>
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem :value="null">{{ t('TBD') }}</SelectItem>
                                <SelectItem
                                    v-for="player in availablePlayers"
                                    :key="player.user_id"
                                    :value="player.user_id"
                                    :disabled="player.user_id === matchForm.player1_id"
                                >
                                    {{ player.user?.firstname }} {{ player.user?.lastname }}
                                    <span class="text-xs text-gray-500 ml-1">(#{{ player.seed_number }})</span>
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </div>

                <!-- Status & Score Override -->
                <div class="grid grid-cols-3 gap-2">
                    <div>
                        <Label class="text-xs mb-0.5">{{ t('Status') }}</Label>
                        <Select v-model="matchForm.status">
                            <SelectTrigger class="h-8 text-sm">
                                <SelectValue/>
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="option in statusOptions"
                                    :key="option.value"
                                    :value="option.value"
                                >
                                    {{ option.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div>
                        <Label class="text-xs mb-0.5">{{ t('Score P1') }}</Label>
                        <Input
                            v-model.number="matchForm.player1_score"
                            type="number"
                            min="0"
                            class="h-8 text-sm text-center"
                        />
                    </div>
                    <div>
                        <Label class="text-xs mb-0.5">{{ t('Score P2') }}</Label>
                        <Input
                            v-model.number="matchForm.player2_score"
                            type="number"
                            min="0"
                            class="h-8 text-sm text-center"
                        />
                    </div>
                </div>

                <!-- Additional Details -->
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <Label class="text-xs mb-0.5">{{ t('Table') }}</Label>
                        <Select v-model="matchForm.club_table_id">
                            <SelectTrigger class="h-8 text-sm">
                                <SelectValue :placeholder="t('Select table')"/>
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem :value="null">{{ t('None') }}</SelectItem>
                                <SelectItem
                                    v-for="table in availableTables"
                                    :key="table.id"
                                    :value="table.id"
                                >
                                    {{ table.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div>
                        <Label class="text-xs mb-0.5">{{ t('Scheduled') }}</Label>
                        <Input
                            v-model="matchForm.scheduled_at"
                            type="datetime-local"
                            class="h-8 text-sm"
                        />
                    </div>
                </div>

                <div>
                    <Label class="text-xs mb-0.5">{{ t('Stream URL') }}</Label>
                    <Input
                        v-model="matchForm.stream_url"
                        :placeholder="t('Optional')"
                        type="url"
                        class="h-8 text-sm"
                    />
                </div>

                <div>
                    <Label class="text-xs mb-0.5">{{ t('Admin Notes') }}</Label>
                    <Textarea
                        v-model="matchForm.admin_notes"
                        :placeholder="t('Internal notes...')"
                        rows="2"
                        class="text-sm"
                    />
                </div>
            </div>

            <!-- Walkover Notice -->
            <div v-if="isWalkoverMatch && match.status === 'pending'"
                 class="rounded bg-yellow-50 p-2 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800">
                <div class="flex items-start gap-2">
                    <UserXIcon class="h-3.5 w-3.5 text-yellow-600 mt-0.5"/>
                    <div class="text-xs">
                        <p class="font-medium text-yellow-800 dark:text-yellow-300">{{ t('Walkover Match') }}</p>
                        <p class="text-yellow-700 dark:text-yellow-400 mt-0.5">
                            {{ t('Click "Process Walkover" to advance the present player.') }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Error Display -->
            <div v-if="matchError"
                 class="rounded bg-red-50 p-2 text-red-600 dark:bg-red-900/20 dark:text-red-400 border border-red-200 dark:border-red-800">
                <div class="flex items-start gap-2">
                    <AlertCircleIcon class="h-3.5 w-3.5 mt-0.5"/>
                    <p class="text-sm">{{ matchError }}</p>
                </div>
            </div>
        </div>

        <template #footer>
            <div class="flex justify-between items-center">
                <Button variant="outline" size="sm" @click="handleClose">
                    {{ t('Cancel') }}
                </Button>

                <div class="flex gap-2">
                    <!-- Process Walkover -->
                    <Button
                        v-if="isWalkoverMatch && match?.status === 'pending'"
                        :disabled="isUpdatingMatch"
                        size="sm"
                        @click="processWalkover"
                    >
                        <UserXIcon class="mr-1.5 h-3.5 w-3.5"/>
                        {{ t('Process Walkover') }}
                    </Button>

                    <!-- Save Changes (edit mode) -->
                    <Button
                        v-if="isEditMode"
                        :disabled="isUpdatingMatch"
                        variant="default"
                        size="sm"
                        @click="updateMatch"
                    >
                        <SaveIcon class="mr-1.5 h-3.5 w-3.5"/>
                        {{ t('Save All Changes') }}
                    </Button>

                    <!-- Quick Actions (non-edit mode) -->
                    <template v-else>
                        <!-- Start Match -->
                        <Button
                            v-if="match?.status === 'ready' && !isWalkoverMatch"
                            :disabled="!canStartMatch || isUpdatingMatch"
                            size="sm"
                            @click="startMatch"
                        >
                            <PlayIcon class="mr-1.5 h-3.5 w-3.5"/>
                            {{ t('Start Match') }}
                        </Button>

                        <!-- Update Scores (new button) -->
                        <Button
                            v-if="canUpdateScores && !isWalkoverMatch"
                            :disabled="isUpdatingMatch"
                            variant="outline"
                            size="sm"
                            @click="updateScores"
                        >
                            <SaveIcon class="mr-1.5 h-3.5 w-3.5"/>
                            {{ t('Update Scores') }}
                        </Button>

                        <!-- Finish Match -->
                        <Button
                            v-if="(match?.status === 'in_progress' || match?.status === 'verification') && !isWalkoverMatch"
                            :disabled="!canFinishMatch || isUpdatingMatch"
                            :class="[
                                'transition-all',
                                canFinishMatch ? 'bg-green-600 hover:bg-green-700 text-white' : ''
                            ]"
                            size="sm"
                            @click="finishMatch"
                        >
                            <CheckCircleIcon class="mr-1.5 h-3.5 w-3.5"/>
                            {{ t('Finish Match') }}
                        </Button>
                    </template>
                </div>
            </div>
        </template>
    </Modal>
</template>
