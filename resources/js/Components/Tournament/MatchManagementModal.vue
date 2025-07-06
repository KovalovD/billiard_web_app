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
import type {ClubTable, Tournament, TournamentMatch} from '@/types/api';
import {
    AlertCircleIcon,
    CalendarIcon,
    CheckCircleIcon,
    ClockIcon,
    MonitorIcon,
    PlayIcon,
    SaveIcon,
    TrophyIcon,
    UserIcon,
    UserXIcon,
    VideoIcon,
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
    player1_score: 0,
    player2_score: 0,
    club_table_id: null as number | null,
    stream_url: '',
    status: 'pending' as string,
    scheduled_at: '',
    admin_notes: ''
});

// Local state
const isUpdatingMatch = ref(false);
const matchError = ref<string | null>(null);

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
    const racesTo = props.tournament?.races_to || 7;
    return matchForm.value.player1_score >= racesTo || matchForm.value.player2_score >= racesTo;
});

const matchWinner = computed(() => {
    if (!canFinishMatch.value) return null;
    const racesTo = props.tournament?.races_to || 7;
    if (matchForm.value.player1_score >= racesTo) return props.match?.player1_id;
    if (matchForm.value.player2_score >= racesTo) return props.match?.player2_id;
    return null;
});

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
        default:
            return 'text-gray-600 bg-gray-50 dark:bg-gray-800 dark:text-gray-400';
    }
};

// Watch for match changes to update form
watch(() => props.match, (newMatch) => {
    if (newMatch) {
        matchForm.value = {
            player1_score: newMatch.player1_score || 0,
            player2_score: newMatch.player2_score || 0,
            club_table_id: newMatch.club_table_id || null,
            stream_url: newMatch.stream_url || '',
            status: newMatch.status,
            scheduled_at: newMatch.scheduled_at ? new Date(newMatch.scheduled_at).toISOString().slice(0, 16) : '',
            admin_notes: newMatch.admin_notes || ''
        };
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
    emit('close');
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
        emit('update-match', matchForm.value);
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
        max-width="2xl"
        @close="handleClose"
    >
        <!-- Loading state -->
        <div v-if="!match && show" class="flex items-center justify-center py-12">
            <div class="text-center">
                <Spinner class="h-8 w-8 text-primary mx-auto mb-2"/>
                <p class="text-sm text-gray-500">{{ t('Loading match details...') }}</p>
            </div>
        </div>

        <div v-else-if="match" class="space-y-4">
            <!-- Compact Header with Status -->
            <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800/50 rounded-lg">
                <div class="flex items-center gap-3">
                    <TrophyIcon class="h-5 w-5 text-gray-400"/>
                    <div>
                        <div class="font-medium text-sm">
                            {{ match.round_display }}
                            <span v-if="match.bracket_side" class="text-gray-500 text-xs ml-1">
                                ({{ match.bracket_side === 'upper' ? t('Upper') : t('Lower') }})
                            </span>
                        </div>
                        <div class="text-xs text-gray-500">{{ t('Race to') }} {{ tournament?.races_to || 7 }}</div>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <span v-if="isWalkoverMatch"
                          class="px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300">
                        <UserXIcon class="inline h-3 w-3 mr-1"/>
                        {{ t('Walkover') }}
                    </span>
                    <span :class="['px-2 py-1 rounded-full text-xs font-medium', getStatusColor(match.status)]">
                        {{ match.status_display }}
                    </span>
                </div>
            </div>

            <!-- Score Section - Compact Design -->
            <div class="bg-white dark:bg-gray-900 rounded-lg border dark:border-gray-700 overflow-hidden">
                <div class="grid grid-cols-2 divide-x dark:divide-gray-700">
                    <!-- Player 1 -->
                    <div class="p-4">
                        <div class="flex items-center gap-2 mb-2">
                            <UserIcon class="h-4 w-4 text-gray-400"/>
                            <span :class="[
                                'text-sm font-medium',
                                matchWinner === match.player1_id && 'text-green-600 dark:text-green-400'
                            ]">
                                {{ match.player1?.firstname }} {{ match.player1?.lastname }}
                                <span v-if="!match.player1" class="text-gray-400">{{ t('TBD') }}</span>
                            </span>
                        </div>
                        <Input
                            v-model.number="matchForm.player1_score"
                            :disabled="match.status === 'completed' || isWalkoverMatch"
                            :max="tournament?.races_to || 7"
                            min="0"
                            type="number"
                            class="text-center text-2xl font-bold h-12"
                        />
                    </div>

                    <!-- Player 2 -->
                    <div class="p-4">
                        <div class="flex items-center gap-2 mb-2">
                            <UserIcon class="h-4 w-4 text-gray-400"/>
                            <span :class="[
                                'text-sm font-medium',
                                matchWinner === match.player2_id && 'text-green-600 dark:text-green-400'
                            ]">
                                {{ match.player2?.firstname }} {{ match.player2?.lastname }}
                                <span v-if="!match.player2" class="text-gray-400">{{ t('TBD') }}</span>
                            </span>
                        </div>
                        <Input
                            v-model.number="matchForm.player2_score"
                            :disabled="match.status === 'completed' || isWalkoverMatch"
                            :max="tournament?.races_to || 7"
                            min="0"
                            type="number"
                            class="text-center text-2xl font-bold h-12"
                        />
                    </div>
                </div>

                <!-- Winner Display -->
                <div v-if="canFinishMatch && !isWalkoverMatch"
                     class="px-4 py-2 bg-green-50 dark:bg-green-900/20 border-t dark:border-gray-700">
                    <p class="text-sm text-center">
                        <TrophyIcon class="inline h-4 w-4 text-green-600 mr-1"/>
                        <span class="font-medium text-green-700 dark:text-green-300">{{ t('Winner') }}:</span>
                        <span v-if="matchWinner === match.player1_id" class="ml-1">
                            {{ match.player1?.firstname }} {{ match.player1?.lastname }}
                        </span>
                        <span v-else-if="matchWinner === match.player2_id" class="ml-1">
                            {{ match.player2?.firstname }} {{ match.player2?.lastname }}
                        </span>
                    </p>
                </div>
            </div>

            <!-- Walkover Notice -->
            <div v-if="isWalkoverMatch && match.status === 'pending'"
                 class="rounded-lg bg-yellow-50 p-3 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800">
                <div class="flex items-start gap-2">
                    <UserXIcon class="h-4 w-4 text-yellow-600 mt-0.5"/>
                    <div class="text-sm">
                        <p class="font-medium text-yellow-800 dark:text-yellow-300">{{ t('Walkover Match') }}</p>
                        <p class="text-yellow-700 dark:text-yellow-400 text-xs mt-1">
                            {{ t('Click "Process Walkover" to advance the present player.') }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Match Details - Compact Grid -->
            <div v-if="!isWalkoverMatch" class="grid grid-cols-2 gap-3">
                <!-- Table Assignment -->
                <div>
                    <Label class="text-xs mb-1 flex items-center gap-1">
                        <MonitorIcon class="h-3 w-3"/>
                        {{ t('Table') }} *
                    </Label>
                    <Select v-model="matchForm.club_table_id" :disabled="match.status !== 'ready'">
                        <SelectTrigger class="h-9">
                            <SelectValue
                                :placeholder="isLoadingTables ? t('Select table') : match.club_table?.name || t('Select table')"/>
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

                <!-- Stream URL -->
                <div>
                    <Label class="text-xs mb-1 flex items-center gap-1">
                        <VideoIcon class="h-3 w-3"/>
                        {{ t('Stream URL') }}
                    </Label>
                    <Input
                        v-model="matchForm.stream_url"
                        :placeholder="t('Optional')"
                        type="url"
                        class="h-9"
                    />
                </div>

                <!-- Scheduled Time -->
                <div>
                    <Label class="text-xs mb-1 flex items-center gap-1">
                        <CalendarIcon class="h-3 w-3"/>
                        {{ t('Scheduled') }}
                    </Label>
                    <Input
                        v-model="matchForm.scheduled_at"
                        type="datetime-local"
                        class="h-9"
                    />
                </div>

                <!-- Timeline Info -->
                <div v-if="match.started_at || match.completed_at"
                     class="flex items-center gap-2 text-xs text-gray-500">
                    <ClockIcon class="h-3 w-3"/>
                    <span v-if="match.started_at">
                        {{ t('Started') }}: {{ formatDateTime(match.started_at) }}
                    </span>
                    <span v-else-if="match.completed_at">
                        {{ t('Completed') }}: {{ formatDateTime(match.completed_at) }}
                    </span>
                </div>
            </div>

            <!-- Admin Notes - Collapsible -->
            <div>
                <Label class="text-xs mb-1">{{ t('Admin Notes') }}</Label>
                <Textarea
                    v-model="matchForm.admin_notes"
                    :placeholder="t('Internal notes...')"
                    rows="2"
                    class="text-sm"
                />
            </div>

            <!-- Status Alerts - Compact -->
            <div v-if="match.status === 'verification'"
                 class="rounded-lg bg-purple-50 p-3 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-800">
                <div class="flex items-start gap-2">
                    <AlertCircleIcon class="h-4 w-4 text-purple-600 mt-0.5"/>
                    <div class="text-sm">
                        <p class="font-medium text-purple-800 dark:text-purple-300">{{ t('Verification Required') }}</p>
                        <p class="text-purple-700 dark:text-purple-400 text-xs mt-1">
                            {{ t('Please verify the submitted result and finish the match.') }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Error Display -->
            <div v-if="matchError"
                 class="rounded-lg bg-red-50 p-3 text-red-600 dark:bg-red-900/20 dark:text-red-400 border border-red-200 dark:border-red-800">
                <div class="flex items-start gap-2">
                    <AlertCircleIcon class="h-4 w-4 mt-0.5"/>
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

                    <!-- Start Match -->
                    <Button
                        v-if="match?.status === 'ready' && !isWalkoverMatch"
                        :disabled="!canStartMatch || isUpdatingMatch"
                        size="sm"
                        @click="startMatch"
                    >
                        <PlayIcon class="mr-1.5 h-3.5 w-3.5"/>
                        {{ t('Start') }}
                    </Button>

                    <!-- Update Match -->
                    <Button
                        v-if="match?.status === 'in_progress'"
                        :disabled="isUpdatingMatch"
                        variant="outline"
                        size="sm"
                        @click="updateMatch"
                    >
                        <SaveIcon class="mr-1.5 h-3.5 w-3.5"/>
                        {{ t('Save') }}
                    </Button>

                    <!-- Finish Match -->
                    <Button
                        v-if="match?.status === 'in_progress' || match?.status === 'verification'"
                        :disabled="!canFinishMatch || isUpdatingMatch"
                        size="sm"
                        @click="finishMatch"
                    >
                        <CheckCircleIcon class="mr-1.5 h-3.5 w-3.5"/>
                        {{ t('Finish') }}
                    </Button>
                </div>
            </div>
        </template>
    </Modal>
</template>
