<!-- resources/js/Components/Tournaments/FrameScores.vue -->
<script lang="ts" setup>
import {computed, ref, watch} from 'vue';
import {Button, Input, Label} from '@/Components/ui';
import {PlusIcon, TrashIcon} from 'lucide-vue-next';
import {useLocale} from '@/composables/useLocale';

interface FrameScore {
    player1: number;
    player2: number;
}

interface Props {
    modelValue: FrameScore[];
    player1Score: number;
    player2Score: number;
    gameType: 'pyramid' | 'snooker' | 'pool';
    disabled?: boolean;
}

interface Emits {
    (e: 'update:modelValue', value: FrameScore[]): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

const { t } = useLocale();

// Local copy of frame scores
const frameScores = ref<FrameScore[]>([]);

// Game type configurations
const gameConfigs = {
    pyramid: {
        maxPerFrame: 8,
        winScore: 8,
        label: t('Balls potted'),
        description: t('First to 8 balls wins the frame')
    },
    snooker: {
        maxPerFrame: 147,
        winScore: null, // No fixed win score
        label: t('Points'),
        description: t('Maximum 147 points per frame')
    },
    pool: {
        maxPerFrame: null, // No specific limit
        winScore: null,
        label: t('Score'),
        description: t('Frame score')
    }
};

const currentConfig = computed(() => gameConfigs[props.gameType] || gameConfigs.pool);

// Validate frame scores match the current match score
const isValid = computed(() => {
    const p1Frames = frameScores.value.filter(f => {
        if (props.gameType === 'pyramid') {
            return f.player1 === 8;
        }
        return f.player1 > f.player2;
    }).length;

    const p2Frames = frameScores.value.filter(f => {
        if (props.gameType === 'pyramid') {
            return f.player2 === 8;
        }
        return f.player2 > f.player1;
    }).length;

    return p1Frames === props.player1Score && p2Frames === props.player2Score;
});

// Add a new frame
const addFrame = () => {
    frameScores.value.push({ player1: 0, player2: 0 });
    updateValue();
};

// Remove a frame
const removeFrame = (index: number) => {
    frameScores.value.splice(index, 1);
    updateValue();
};

// Update parent
const updateValue = () => {
    emit('update:modelValue', [...frameScores.value]);
};

// Validate individual frame score
const validateFrameScore = (frame: FrameScore, playerNum: 1 | 2): boolean => {
    const score = playerNum === 1 ? frame.player1 : frame.player2;
    const opponentScore = playerNum === 1 ? frame.player2 : frame.player1;

    if (props.gameType === 'pyramid') {
        // In pyramid, one player must reach 8 to win
        if (score === 8) return opponentScore < 8;
        if (opponentScore === 8) return score < 8;
        return score < 8 && opponentScore < 8;
    }

    if (props.gameType === 'snooker') {
        // In snooker, max score is 147
        return score >= 0 && score <= 147;
    }

    // For pool, just ensure non-negative
    return score >= 0;
};

// Get frame winner
const getFrameWinner = (frame: FrameScore): 1 | 2 | null => {
    if (props.gameType === 'pyramid') {
        if (frame.player1 === 8) return 1;
        if (frame.player2 === 8) return 2;
        return null;
    }

    if (frame.player1 > frame.player2) return 1;
    if (frame.player2 > frame.player1) return 2;
    return null;
};

// Initialize from props
watch(() => props.modelValue, (newValue) => {
    frameScores.value = newValue ? [...newValue] : [];
}, { immediate: true });

// Auto-add frames based on match score
watch([() => props.player1Score, () => props.player2Score], () => {
    const totalFrames = props.player1Score + props.player2Score;
    while (frameScores.value.length < totalFrames) {
        addFrame();
    }
});
</script>

<template>
    <div class="space-y-3">
        <div class="flex items-center justify-between">
            <div>
                <Label class="text-sm font-medium">{{ t('Frame Scores') }}</Label>
                <p class="text-xs text-gray-500 mt-0.5">{{ currentConfig.description }}</p>
            </div>
            <Button
                v-if="!disabled"
                size="sm"
                variant="outline"
                @click="addFrame"
                :disabled="frameScores.length >= (player1Score + player2Score)"
            >
                <PlusIcon class="h-3 w-3 mr-1" />
                {{ t('Add Frame') }}
            </Button>
        </div>

        <div v-if="!isValid" class="text-xs text-red-600 dark:text-red-400">
            {{ t('Frame scores must match the match score') }}
        </div>

        <div v-if="frameScores.length > 0" class="space-y-2">
            <div
                v-for="(frame, index) in frameScores"
                :key="index"
                class="flex items-center gap-2 p-2 rounded border dark:border-gray-700"
                :class="{
                    'bg-green-50 dark:bg-green-900/20 border-green-300 dark:border-green-700': getFrameWinner(frame) === 1,
                    'bg-blue-50 dark:bg-blue-900/20 border-blue-300 dark:border-blue-700': getFrameWinner(frame) === 2,
                }"
            >
                <span class="text-xs font-medium text-gray-500 w-14">
                    {{ t('Frame') }} {{ index + 1 }}
                </span>

                <div class="flex-1 grid grid-cols-2 gap-2">
                    <Input
                        v-model.number="frame.player1"
                        type="number"
                        min="0"
                        :max="currentConfig.maxPerFrame"
                        :disabled="disabled"
                        class="h-8 text-center"
                        :class="{
                            'border-red-300': !validateFrameScore(frame, 1)
                        }"
                        @input="updateValue"
                    />
                    <Input
                        v-model.number="frame.player2"
                        type="number"
                        min="0"
                        :max="currentConfig.maxPerFrame"
                        :disabled="disabled"
                        class="h-8 text-center"
                        :class="{
                            'border-red-300': !validateFrameScore(frame, 2)
                        }"
                        @input="updateValue"
                    />
                </div>

                <Button
                    v-if="!disabled"
                    size="icon"
                    variant="ghost"
                    class="h-7 w-7"
                    @click="removeFrame(index)"
                >
                    <TrashIcon class="h-3.5 w-3.5" />
                </Button>
            </div>
        </div>

        <div v-else class="text-center py-4 text-sm text-gray-500">
            {{ t('No frame scores recorded yet') }}
        </div>
    </div>
</template>
