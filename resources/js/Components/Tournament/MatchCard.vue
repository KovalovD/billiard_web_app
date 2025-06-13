<!-- resources/js/Components/Tournament/MatchCard.vue -->
<script lang="ts" setup>
import {ref, computed} from 'vue';
import {
    Button,
    Input,
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue
} from '@/Components/ui';
import {useLocale} from '@/composables/useLocale';
import {
    PlayIcon,
    PauseIcon,
    ClockIcon,
    EditIcon,
    CheckIcon,
    XIcon,
    TableIcon,
    TrophyIcon
} from 'lucide-vue-next';
import type {MatchSchedule} from '@/Services/TournamentService';

interface Props {
    match: MatchSchedule;
    showControls?: boolean;
    compact?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    showControls: false,
    compact: false
});

const emit = defineEmits<{
    start: [matchId: number];
    pause: [matchId: number];
    'update-time': [matchId: number, newTime: string, tableNumber?: number];
}>();

const {t} = useLocale();

// State
const isEditing = ref(false);
const editTime = ref('');
const editTable = ref<number | null>(null);

// Mock tables data
const tables = [
    {id: 1, name: 'Table 1'},
    {id: 2, name: 'Table 2'},
    {id: 3, name: 'Table 3'},
    {id: 4, name: 'Table 4'}
];

// Computed
const statusClass = computed(() => {
    switch (props.match.status) {
        case 'scheduled':
            return 'border-blue-200 bg-blue-50 dark:border-blue-800 dark:bg-blue-900/20';
        case 'in_progress':
            return 'border-green-200 bg-green-50 dark:border-green-800 dark:bg-green-900/20';
        case 'completed':
            return 'border-gray-200 bg-gray-50 dark:border-gray-600 dark:bg-gray-800';
        case 'cancelled':
            return 'border-red-200 bg-red-50 dark:border-red-800 dark:bg-red-900/20';
        default:
            return 'border-gray-200 bg-white dark:border-gray-600 dark:bg-gray-800';
    }
});

const statusBadge = computed(() => {
    switch (props.match.status) {
        case 'scheduled':
            return {color: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300', text: t('Scheduled')};
        case 'in_progress':
            return {color: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300', text: t('Live')};
        case 'completed':
            return {color: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300', text: t('Completed')};
        case 'cancelled':
            return {color: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300', text: t('Cancelled')};
        default:
            return {color: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300', text: t('Unknown')};
    }
});

const canStart = computed(() => {
    return props.match.status === 'scheduled' && props.match.playerA && props.match.playerB;
});

const canPause = computed(() => {
    return props.match.status === 'in_progress';
});

// Methods
const startEditing = () => {
    isEditing.value = true;
    editTime.value = props.match.scheduledAt ?
        new Date(props.match.scheduledAt).toISOString().slice(0, 16) : '';
    editTable.value = props.match.tableNumber || null;
};

const saveEdit = () => {
    if (editTime.value) {
        emit('update-time', props.match.id, editTime.value, editTable.value || undefined);
    }
    isEditing.value = false;
};

const cancelEdit = () => {
    isEditing.value = false;
    editTime.value = '';
    editTable.value = null;
};

const handleStart = () => {
    emit('start', props.match.id);
};

const handlePause = () => {
    emit('pause', props.match.id);
};

const formatTime = (dateString: string): string => {
    return new Date(dateString).toLocaleTimeString([], {
        hour: '2-digit',
        minute: '2-digit'
    });
};

const formatDate = (dateString: string): string => {
    return new Date(dateString).toLocaleDateString();
};

const getPlayerName = (player: any): string => {
    if (!player) return t('TBD');
    return player.name || `${player.firstname || ''} ${player.lastname || ''}`.trim() || t('Unknown Player');
};

const getScoreDisplay = (): string => {
    if (props.match.scoreA !== undefined && props.match.scoreB !== undefined) {
        return `${props.match.scoreA} - ${props.match.scoreB}`;
    }
    return '';
};

const getWinner = (): any => {
    if (props.match.status !== 'completed') return null;
    if (props.match.scoreA !== undefined && props.match.scoreB !== undefined) {
        return props.match.scoreA > props.match.scoreB ? props.match.playerA : props.match.playerB;
    }
    return null;
};
</script>

<template>
    <div
        :class="[
      'border-2 rounded-lg p-4 transition-all hover:shadow-md',
      statusClass,
      { 'p-3': compact }
    ]"
    >
        <!-- Match Header -->
        <div class="flex items-center justify-between mb-3">
            <div class="flex items-center space-x-2">
        <span class="text-xs font-medium text-gray-600 dark:text-gray-400">
          {{ t('Match') }} #{{ match.id }}
        </span>
                <span class="text-xs text-gray-500">{{ t('Round') }} {{ match.round }}</span>
            </div>

            <div class="flex items-center space-x-2">
                <!-- Status Badge -->
                <span
                    :class="['px-2 py-1 text-xs font-medium rounded-full', statusBadge.color]"
                >
          {{ statusBadge.text }}
        </span>

                <!-- Live Indicator -->
                <div v-if="match.status === 'in_progress'" class="flex items-center">
                    <div class="w-2 h-2 bg-red-500 rounded-full animate-pulse mr-1"></div>
                    <span class="text-xs text-red-600 font-medium">{{ t('LIVE') }}</span>
                </div>
            </div>
        </div>

        <!-- Players -->
        <div class="space-y-2 mb-4">
            <!-- Player A -->
            <div
                :class="[
          'flex items-center justify-between p-2 rounded',
          getWinner() === match.playerA
            ? 'bg-green-100 dark:bg-green-900/30 ring-1 ring-green-500'
            : 'bg-gray-100 dark:bg-gray-700'
        ]"
            >
                <div class="flex items-center space-x-2">
                    <span class="font-medium">{{ getPlayerName(match.playerA) }}</span>
                    <TrophyIcon
                        v-if="getWinner() === match.playerA"
                        class="h-4 w-4 text-yellow-600"
                    />
                </div>
                <span v-if="match.scoreA !== undefined" class="font-bold text-lg">
          {{ match.scoreA }}
        </span>
            </div>

            <!-- VS Divider -->
            <div class="text-center text-xs text-gray-500 font-medium">
                {{ getScoreDisplay() || t('VS') }}
            </div>

            <!-- Player B -->
            <div
                :class="[
          'flex items-center justify-between p-2 rounded',
          getWinner() === match.playerB
            ? 'bg-green-100 dark:bg-green-900/30 ring-1 ring-green-500'
            : 'bg-gray-100 dark:bg-gray-700'
        ]"
            >
                <div class="flex items-center space-x-2">
                    <span class="font-medium">{{ getPlayerName(match.playerB) }}</span>
                    <TrophyIcon
                        v-if="getWinner() === match.playerB"
                        class="h-4 w-4 text-yellow-600"
                    />
                </div>
                <span v-if="match.scoreB !== undefined" class="font-bold text-lg">
          {{ match.scoreB }}
        </span>
            </div>
        </div>

        <!-- Match Details -->
        <div class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
            <!-- Time and Table -->
            <div v-if="!isEditing" class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <div v-if="match.scheduledAt" class="flex items-center space-x-1">
                        <ClockIcon class="h-3 w-3"/>
                        <span>{{ formatTime(match.scheduledAt) }}</span>
                        <span class="text-xs">{{ formatDate(match.scheduledAt) }}</span>
                    </div>

                    <div v-if="match.tableNumber" class="flex items-center space-x-1">
                        <TableIcon class="h-3 w-3"/>
                        <span>{{ t('Table') }} {{ match.tableNumber }}</span>
                    </div>
                </div>

                <Button
                    v-if="showControls"
                    size="sm"
                    variant="ghost"
                    @click="startEditing"
                >
                    <EditIcon class="h-3 w-3"/>
                </Button>
            </div>

            <!-- Edit Mode -->
            <div v-else class="space-y-3">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-xs text-gray-500">{{ t('Time') }}</label>
                        <Input
                            v-model="editTime"
                            class="text-xs"
                            size="sm"
                            type="datetime-local"
                        />
                    </div>

                    <div>
                        <label class="text-xs text-gray-500">{{ t('Table') }}</label>
                        <Select v-model="editTable">
                            <SelectTrigger class="h-8 text-xs">
                                <SelectValue :placeholder="t('Select table')"/>
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem :value="null">{{ t('No table') }}</SelectItem>
                                <SelectItem
                                    v-for="table in tables"
                                    :key="table.id"
                                    :value="table.id"
                                >
                                    {{ table.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </div>

                <div class="flex justify-end space-x-2">
                    <Button size="sm" variant="ghost" @click="cancelEdit">
                        <XIcon class="h-3 w-3"/>
                    </Button>
                    <Button size="sm" @click="saveEdit">
                        <CheckIcon class="h-3 w-3"/>
                    </Button>
                </div>
            </div>

            <!-- Additional Info -->
            <div v-if="match.frames && match.frames.length > 0" class="text-xs">
                {{ t('Frames played') }}: {{ match.frames.length }}
            </div>
        </div>

        <!-- Controls -->
        <div v-if="showControls && !isEditing" class="mt-4 pt-3 border-t border-gray-200 dark:border-gray-600">
            <div class="flex justify-between items-center">
                <div class="flex space-x-2">
                    <Button
                        v-if="canStart"
                        class="bg-green-600 hover:bg-green-700"
                        size="sm"
                        @click="handleStart"
                    >
                        <PlayIcon class="mr-1 h-3 w-3"/>
                        {{ t('Start') }}
                    </Button>

                    <Button
                        v-if="canPause"
                        size="sm"
                        variant="outline"
                        @click="handlePause"
                    >
                        <PauseIcon class="mr-1 h-3 w-3"/>
                        {{ t('Pause') }}
                    </Button>
                </div>

                <div class="text-xs text-gray-500">
          <span v-if="match.status === 'in_progress'">
            {{ t('Started') }}: {{ match.started_at ? formatTime(match.started_at) : '' }}
          </span>
                    <span v-else-if="match.status === 'completed'">
            {{ t('Completed') }}: {{ match.completed_at ? formatTime(match.completed_at) : '' }}
          </span>
                </div>
            </div>
        </div>
    </div>
</template>
