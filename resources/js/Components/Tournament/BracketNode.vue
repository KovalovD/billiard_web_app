<!-- resources/js/Components/Tournament/BracketNode.vue -->
<script lang="ts" setup>
import {ref, computed} from 'vue';
import {Button} from '@/Components/ui';
import {useLocale} from '@/composables/useLocale';
import {
    PlayIcon,
    CheckIcon,
    TrophyIcon,
    ClockIcon
} from 'lucide-vue-next';
import type {BracketNode as BracketNodeType} from '@/types/tournament';

interface Props {
    node: BracketNodeType;
    dragging?: boolean;
    bestOf?: number;
}

const props = defineProps<Props>();
const emit = defineEmits<{
    update: [nodeId: number, update: Partial<BracketNodeType>];
    'player-drop': [nodeId: number, slot: 'A' | 'B', player: any];
}>();

const {t} = useLocale();

// State
const isDragOver = ref<'A' | 'B' | null>(null);
const showMatchDetails = ref(false);

// Computed
const nodeStatus = computed(() => {
    if (props.node.winner_id) return 'completed';
    if (props.node.player_a && props.node.player_b) return 'ready';
    if (props.node.player_a || props.node.player_b) return 'partial';
    return 'empty';
});

const statusClass = computed(() => {
    switch (nodeStatus.value) {
        case 'completed':
            return 'border-green-500 bg-green-50 dark:bg-green-900/20';
        case 'ready':
            return 'border-blue-500 bg-blue-50 dark:bg-blue-900/20';
        case 'partial':
            return 'border-yellow-500 bg-yellow-50 dark:bg-yellow-900/20';
        default:
            return 'border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800';
    }
});

const canStartMatch = computed(() => {
    return props.node.player_a && props.node.player_b && !props.node.winner_id;
});

// Methods
const handleDragOver = (event: DragEvent, slot: 'A' | 'B') => {
    event.preventDefault();
    isDragOver.value = slot;
};

const handleDragLeave = () => {
    isDragOver.value = null;
};

const handleDrop = (event: DragEvent, slot: 'A' | 'B') => {
    event.preventDefault();
    isDragOver.value = null;

    try {
        const playerData = JSON.parse(event.dataTransfer?.getData('text/plain') || '{}');
        emit('player-drop', props.node.id, slot, playerData);
    } catch (error) {
        console.error('Failed to parse dropped player data:', error);
    }
};

const startMatch = () => {
    if (!canStartMatch.value) return;

    // Emit start match event or navigate to match page
    console.log('Start match for node:', props.node.id);
};

const declareWinner = (winnerId: number) => {
    if (!props.node.player_a || !props.node.player_b) return;

    emit('update', props.node.id, {
        winner_id: winnerId,
        winner: winnerId === props.node.player_a_id ? props.node.player_a : props.node.player_b
    });
};

const resetMatch = () => {
    emit('update', props.node.id, {
        winner_id: undefined,
        winner: undefined
    });
};

const getPlayerDisplayName = (player: any): string => {
    if (!player) return '';
    return player.name || `${player.firstname || ''} ${player.lastname || ''}`.trim();
};

const getPlayerSeed = (playerId: number): string => {
    // In a real implementation, this would look up the player's seed
    return '#1';
};
</script>

<template>
    <div
        :class="[
      'relative w-64 border-2 rounded-lg p-4 transition-all duration-200',
      statusClass,
      { 'transform scale-105': showMatchDetails }
    ]"
    >
        <!-- Node Header -->
        <div class="flex items-center justify-between mb-3">
            <div class="flex items-center space-x-2">
        <span class="text-xs font-medium text-gray-600 dark:text-gray-400">
          {{ t('Match') }} #{{ node.id }}
        </span>

                <!-- Status Icon -->
                <CheckIcon v-if="nodeStatus === 'completed'" class="h-4 w-4 text-green-600"/>
                <PlayIcon v-else-if="nodeStatus === 'ready'" class="h-4 w-4 text-blue-600"/>
                <ClockIcon v-else-if="nodeStatus === 'partial'" class="h-4 w-4 text-yellow-600"/>
            </div>

            <div v-if="bestOf" class="text-xs text-gray-500">
                {{ t('BO:number', {number: bestOf}) }}
            </div>
        </div>

        <!-- Players -->
        <div class="space-y-2">
            <!-- Player A -->
            <div
                :class="[
          'p-3 rounded border-2 border-dashed transition-all',
          isDragOver === 'A'
            ? 'border-blue-500 bg-blue-100 dark:bg-blue-900/30'
            : 'border-gray-200 dark:border-gray-600',
          node.player_a
            ? 'bg-gray-50 dark:bg-gray-700 border-solid'
            : 'bg-gray-100 dark:bg-gray-800',
          node.winner_id === node.player_a_id
            ? 'ring-2 ring-green-500 bg-green-50 dark:bg-green-900/20'
            : ''
        ]"
                @dragleave="handleDragLeave"
                @dragover="handleDragOver($event, 'A')"
                @drop="handleDrop($event, 'A')"
            >
                <div v-if="node.player_a" class="flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <span class="text-xs text-gray-500">{{ getPlayerSeed(node.player_a_id!) }}</span>
                        <span class="font-medium">{{ getPlayerDisplayName(node.player_a) }}</span>
                    </div>

                    <div class="flex items-center space-x-2">
                        <!-- Score display -->
                        <span v-if="node.match?.score_a !== undefined" class="text-sm font-bold">
              {{ node.match.score_a }}
            </span>

                        <!-- Winner indicator -->
                        <TrophyIcon
                            v-if="node.winner_id === node.player_a_id"
                            class="h-4 w-4 text-yellow-600"
                        />

                        <!-- Quick winner button -->
                        <Button
                            v-else-if="canStartMatch"
                            class="h-6 w-6 p-0"
                            size="sm"
                            variant="ghost"
                            @click="declareWinner(node.player_a_id!)"
                        >
                            <CheckIcon class="h-3 w-3"/>
                        </Button>
                    </div>
                </div>

                <div v-else class="text-center text-gray-400 py-2">
                    {{ dragging ? t('Drop here') : t('TBD') }}
                </div>
            </div>

            <!-- VS Divider -->
            <div class="text-center text-xs text-gray-500 font-medium">
                {{ t('VS') }}
            </div>

            <!-- Player B -->
            <div
                :class="[
          'p-3 rounded border-2 border-dashed transition-all',
          isDragOver === 'B'
            ? 'border-blue-500 bg-blue-100 dark:bg-blue-900/30'
            : 'border-gray-200 dark:border-gray-600',
          node.player_b
            ? 'bg-gray-50 dark:bg-gray-700 border-solid'
            : 'bg-gray-100 dark:bg-gray-800',
          node.winner_id === node.player_b_id
            ? 'ring-2 ring-green-500 bg-green-50 dark:bg-green-900/20'
            : ''
        ]"
                @dragleave="handleDragLeave"
                @dragover="handleDragOver($event, 'B')"
                @drop="handleDrop($event, 'B')"
            >
                <div v-if="node.player_b" class="flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <span class="text-xs text-gray-500">{{ getPlayerSeed(node.player_b_id!) }}</span>
                        <span class="font-medium">{{ getPlayerDisplayName(node.player_b) }}</span>
                    </div>

                    <div class="flex items-center space-x-2">
                        <!-- Score display -->
                        <span v-if="node.match?.score_b !== undefined" class="text-sm font-bold">
              {{ node.match.score_b }}
            </span>

                        <!-- Winner indicator -->
                        <TrophyIcon
                            v-if="node.winner_id === node.player_b_id"
                            class="h-4 w-4 text-yellow-600"
                        />

                        <!-- Quick winner button -->
                        <Button
                            v-else-if="canStartMatch"
                            class="h-6 w-6 p-0"
                            size="sm"
                            variant="ghost"
                            @click="declareWinner(node.player_b_id!)"
                        >
                            <CheckIcon class="h-3 w-3"/>
                        </Button>
                    </div>
                </div>

                <div v-else class="text-center text-gray-400 py-2">
                    {{ dragging ? t('Drop here') : t('TBD') }}
                </div>
            </div>
        </div>

        <!-- Match Actions -->
        <div v-if="canStartMatch || nodeStatus === 'completed'"
             class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-600">
            <div class="flex justify-between items-center">
                <Button
                    v-if="canStartMatch"
                    size="sm"
                    @click="startMatch"
                >
                    <PlayIcon class="mr-1 h-3 w-3"/>
                    {{ t('Start') }}
                </Button>

                <Button
                    v-if="nodeStatus === 'completed'"
                    size="sm"
                    variant="outline"
                    @click="resetMatch"
                >
                    {{ t('Reset') }}
                </Button>

                <Button
                    size="sm"
                    variant="ghost"
                    @click="showMatchDetails = !showMatchDetails"
                >
                    {{ showMatchDetails ? t('Hide') : t('Details') }}
                </Button>
            </div>
        </div>

        <!-- Match Details Panel -->
        <div v-if="showMatchDetails" class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-600">
            <div class="space-y-2 text-xs">
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-400">{{ t('Round') }}:</span>
                    <span>{{ node.round }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-400">{{ t('Position') }}:</span>
                    <span>{{ node.position }}</span>
                </div>
                <div v-if="node.match?.scheduled_at" class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-400">{{ t('Scheduled') }}:</span>
                    <span>{{ new Date(node.match.scheduled_at).toLocaleTimeString() }}</span>
                </div>
                <div v-if="node.next_match_id" class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-400">{{ t('Next Match') }}:</span>
                    <span>#{{ node.next_match_id }}</span>
                </div>
            </div>
        </div>

        <!-- Bye Indicator -->
        <div v-if="node.is_bye"
             class="absolute inset-0 bg-gray-200 dark:bg-gray-700 bg-opacity-50 rounded-lg flex items-center justify-center">
            <span class="text-gray-600 dark:text-gray-400 font-medium">{{ t('BYE') }}</span>
        </div>
    </div>
</template>
