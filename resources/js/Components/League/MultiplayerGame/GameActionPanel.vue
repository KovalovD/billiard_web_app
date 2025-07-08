// resources/js/Components/GameActionPanel.vue
<script lang="ts" setup>
import LivesTracker from '@/Components/League/MultiplayerGame/LivesTracker.vue';
import PlayerCards from '@/Components/League/MultiplayerGame/PlayerCards.vue';
import {Button, Spinner} from '@/Components/ui';
import type {MultiplayerGamePlayer} from '@/types/api';
import {computed, ref, watch} from 'vue';
import {useLocale} from '@/composables/useLocale';

interface Props {
    player: MultiplayerGamePlayer;
    targetPlayers?: MultiplayerGamePlayer[];
    isCurrentTurn?: boolean;
    isLoading?: boolean;
    maxLives?: number;
}

const props = defineProps<Props>();
const emit = defineEmits([
    'increment-lives',
    'decrement-lives',
    'use-card',
    'record-turn'
]);
const {t} = useLocale();

// Local state
const selectedCardType = ref<'skip_turn' | 'pass_turn' | 'hand_shot' | 'handicap' | null>(null);
const selectedTargetPlayer = ref<MultiplayerGamePlayer | null>(null);
const selectedHandicapAction = ref<'skip_turn' | 'take_life' | null>(null);

// Reset selections when player changes
watch(() => props.player, () => {
    selectedCardType.value = null;
    selectedTargetPlayer.value = null;
    selectedHandicapAction.value = null;
}, {immediate: true});

// Computed properties
const needsTarget = computed(() => {
    if (selectedCardType.value === 'pass_turn') return true;
    return selectedCardType.value === 'handicap' && selectedHandicapAction.value === 'take_life';
});

const eligibleTargetsForTakeLife = computed(() => {
    if (!props.targetPlayers) return [];
    return props.targetPlayers.filter(player => {
        // Must have 3+ lives (removed division check)
        return player.lives >= 3;
    });
});

const availableTargets = computed(() => {
    if (selectedCardType.value === 'handicap' && selectedHandicapAction.value === 'take_life') {
        return eligibleTargetsForTakeLife.value;
    }
    return props.targetPlayers || [];
});

const canPerformAction = computed(() => {
    if (!selectedCardType.value) return true;
    if (selectedCardType.value === 'handicap' && !selectedHandicapAction.value) return false;
    return !(needsTarget.value && !selectedTargetPlayer.value);
});

// Methods
const handleSelectCard = (cardType: 'skip_turn' | 'pass_turn' | 'hand_shot' | 'handicap') => {
    if (props.isLoading) return;

    // Toggle the card if it's already selected
    if (selectedCardType.value === cardType) {
        selectedCardType.value = null;
        selectedTargetPlayer.value = null;
        selectedHandicapAction.value = null;
        return;
    }

    selectedCardType.value = cardType;

    // Reset target player and handicap action when changing card type
    selectedTargetPlayer.value = null;
    selectedHandicapAction.value = null;
};

const handleSelectHandicapAction = (action: 'skip_turn' | 'take_life') => {
    if (props.isLoading) return;

    selectedHandicapAction.value = selectedHandicapAction.value === action ? null : action;
    selectedTargetPlayer.value = null; // Reset target when changing handicap action
};

const handleSelectTarget = (player: MultiplayerGamePlayer) => {
    if (props.isLoading) return;

    // Only set target player if we need one
    if (!needsTarget.value) return;

    // Toggle the player if it's already selected
    if (selectedTargetPlayer.value?.id === player.id) {
        selectedTargetPlayer.value = null;
    } else {
        selectedTargetPlayer.value = player;
    }
};

const handleIncrementLives = () => {
    emit('increment-lives');
};

const handleDecrementLives = () => {
    emit('decrement-lives');
};

const getButtonText = computed(() => {
    if (selectedCardType.value === 'handicap') {
        switch (selectedHandicapAction.value) {
            case 'skip_turn':
                return t('Skip Turn');
            case 'take_life':
                return t('Take Life');
            default:
                return t('Handicap');
        }
    }

    switch (selectedCardType.value) {
        case 'skip_turn':
            return t('Skip Turn');
        case 'pass_turn':
            return t('Pass Turn');
        case 'hand_shot':
            return t('Hand Shot');
        case 'handicap':
            return t('Handicap');
    }

    return '';
});

const handleUseCard = () => {
    if (!selectedCardType.value) return;

    // For handicap cards, ensure an action is selected
    if (selectedCardType.value === 'handicap' && !selectedHandicapAction.value) {
        return;
    }

    emit('use-card', {
        cardType: selectedCardType.value,
        targetPlayerId: selectedTargetPlayer.value?.user.id,
        handicapAction: selectedHandicapAction.value
    });

    // Reset selections after using card
    selectedCardType.value = null;
    selectedTargetPlayer.value = null;
    selectedHandicapAction.value = null;
};

const handleRecordTurn = () => {
    emit('record-turn');
};
</script>

<template>
    <div :class="{'bg-green-50 dark:bg-green-900/10': isCurrentTurn}" class="space-y-4 rounded-lg border p-4">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="font-medium flex items-center">
                    {{ player.user.firstname }} {{ player.user.lastname }} ({{ player.division }})
                    <span v-if="isCurrentTurn"
                          class="ml-2 px-2 py-0.5 text-xs text-white bg-green-600 rounded-full font-semibold dark:bg-green-700">{{
                            t('Current Turn')
                        }}</span>
                </h3>
            </div>
        </div>

        <div class="border-t pt-3">
            <div class="flex items-center space-x-4 mb-2">
                <LivesTracker :lives="player.lives" :max-lives="props.maxLives"/>

                <div class="flex space-x-1">
                    <Button
                        :disabled="isLoading"
                        size="sm"
                        variant="outline"
                        @click="handleDecrementLives"
                    >
                        -
                    </Button>

                    <Button
                        :disabled="isLoading"
                        size="sm"
                        variant="outline"
                        @click="handleIncrementLives"
                    >
                        +
                    </Button>

                    <Button
                        v-if="isCurrentTurn"
                        :disabled="isLoading"
                        @click="handleRecordTurn"
                    >
                        <Spinner v-if="isLoading" class="mr-2 h-4 w-4"/>
                        {{ t('End Turn') }}
                    </Button>
                </div>
            </div>
            <h4 class="mb-2 text-sm font-medium">{{ t('Available Cards:') }}</h4>
            <PlayerCards
                :disabled="isLoading"
                :player="player"
                :selectedCard="selectedCardType"
                @select-card="handleSelectCard"
            />
        </div>

        <!-- Handicap Action Selection -->
        <div v-if="selectedCardType === 'handicap'" class="border-t pt-3">
            <h4 class="mb-2 text-sm font-medium">{{ t('Choose Handicap Action:') }}</h4>
            <div class="flex flex-wrap gap-2">
                <Button
                    :disabled="isLoading"
                    :variant="selectedHandicapAction === 'skip_turn' ? 'default' : 'outline'"
                    size="sm"
                    @click="handleSelectHandicapAction('skip_turn')"
                >
                    {{ t('Skip Turn') }}
                </Button>
                <Button
                    :disabled="isLoading || eligibleTargetsForTakeLife.length === 0"
                    :variant="selectedHandicapAction === 'take_life' ? 'default' : 'outline'"
                    size="sm"
                    @click="handleSelectHandicapAction('take_life')"
                >
                    {{ t('Take Life') }}
                    <span v-if="eligibleTargetsForTakeLife.length === 0" class="ml-1 text-xs">({{
                            t('No targets')
                        }})</span>
                </Button>
            </div>
            <div v-if="selectedHandicapAction"
                 class="mt-2 rounded-md bg-green-50 p-2 text-sm text-green-800 dark:bg-green-900/20 dark:text-green-300">
                <p v-if="selectedHandicapAction === 'skip_turn'">{{
                        t('Skip your turn, game moves to the next player')
                    }}</p>
                <p v-else-if="selectedHandicapAction === 'take_life'">
                    {{ t('Take a life from any player with 3+ lives') }}</p>
            </div>
        </div>

        <div v-if="needsTarget && availableTargets.length" class="border-t pt-3">
            <h4 class="mb-2 text-sm font-medium">
                <span v-if="selectedHandicapAction === 'take_life'">{{
                        t('Select Target (3+ lives):')
                    }}</span>
                <span v-else>{{ t('Select Target Player:') }}</span>
            </h4>
            <div class="flex flex-wrap gap-2">
                <Button
                    v-for="targetPlayer in availableTargets"
                    :key="targetPlayer.id"
                    :disabled="isLoading || targetPlayer.id === player.id"
                    :variant="selectedTargetPlayer?.id === targetPlayer.id ? 'default' : 'outline'"
                    size="sm"
                    @click="handleSelectTarget(targetPlayer)"
                >
                    {{ targetPlayer.user.firstname }} {{ targetPlayer.user.lastname }}
                    <span v-if="selectedHandicapAction === 'take_life'" class="ml-1 text-xs">
                        ({{ targetPlayer.division }}, {{ targetPlayer.lives }} {{ t('lives') }})
                    </span>
                </Button>
            </div>
        </div>

        <div v-if="needsTarget && availableTargets.length === 0" class="border-t pt-3">
            <div
                class="rounded-md bg-yellow-50 p-3 text-center text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-300">
                <p v-if="selectedHandicapAction === 'take_life'">
                    {{ t('No eligible targets for taking life (need players with 3+ lives)') }}
                </p>
                <p v-else>{{ t('No target players available') }}</p>
            </div>
        </div>

        <div class="flex justify-between pt-3">
            <Button
                v-if="selectedCardType"
                :disabled="!canPerformAction || isLoading || !isCurrentTurn"
                :variant="selectedCardType === 'skip_turn' || (selectedCardType === 'handicap' && selectedHandicapAction === 'skip_turn') ? 'destructive' : 'default'"
                @click="handleUseCard"
            >
                <Spinner v-if="isLoading" class="mr-2 h-4 w-4"/>
                {{ t('Use') }} {{ getButtonText }} {{ t('Card') }}
            </Button>
        </div>
    </div>
</template>
