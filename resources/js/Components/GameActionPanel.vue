// resources/js/Components/GameActionPanel.vue
<script lang="ts" setup>
import LivesTracker from '@/Components/LivesTracker.vue';
import PlayerCards from '@/Components/PlayerCards.vue';
import {Button, Spinner} from '@/Components/ui';
import type {MultiplayerGamePlayer} from '@/types/api';
import {computed, ref, watch} from 'vue';

interface Props {
    player: MultiplayerGamePlayer;
    targetPlayers?: MultiplayerGamePlayer[];
    isCurrentTurn?: boolean;
    isLoading?: boolean;
}

const props = defineProps<Props>();
const emit = defineEmits([
    'increment-lives',
    'decrement-lives',
    'use-card',
    'record-turn'
]);

// Local state
const selectedCardType = ref<'skip_turn' | 'pass_turn' | 'hand_shot' | null>(null);
const selectedTargetPlayer = ref<MultiplayerGamePlayer | null>(null);

// Reset selections when player changes
watch(() => props.player, () => {
    selectedCardType.value = null;
    selectedTargetPlayer.value = null;
}, {immediate: true});

// Computed properties
const needsTarget = computed(() => {
    return selectedCardType.value === 'pass_turn';
});

const canPerformAction = computed(() => {
    if (!selectedCardType.value) return true;
    return !(needsTarget.value && !selectedTargetPlayer.value);
});

// Methods
const handleSelectCard = (cardType: 'skip_turn' | 'pass_turn' | 'hand_shot') => {
    if (props.isLoading) return;

    // Toggle the card if it's already selected
    if (selectedCardType.value === cardType) {
        selectedCardType.value = null;
        selectedTargetPlayer.value = null;
        return;
    }

    selectedCardType.value = cardType;

    // Reset target player when changing card type
    selectedTargetPlayer.value = null;
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

const handleUseCard = () => {
    if (!selectedCardType.value) return;

    emit('use-card', {
        cardType: selectedCardType.value,
        targetPlayerId: selectedTargetPlayer.value?.user.id
    });

    // Reset selections after using card
    selectedCardType.value = null;
    selectedTargetPlayer.value = null;
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
                    {{ player.user.firstname }} {{ player.user.lastname }}
                    <span v-if="isCurrentTurn"
                          class="ml-2 px-2 py-0.5 text-xs text-white bg-green-600 rounded-full font-semibold dark:bg-green-700">Current Turn</span>
                </h3>
            </div>

            <div class="flex items-center space-x-4">
                <LivesTracker :lives="player.lives"/>

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
                </div>
            </div>
        </div>

        <div class="border-t pt-3">
            <h4 class="mb-2 text-sm font-medium">Available Cards:</h4>
            <PlayerCards
                :disabled="isLoading"
                :player="player"
                :selectedCard="selectedCardType"
                @select-card="handleSelectCard"
            />
        </div>

        <div v-if="needsTarget && targetPlayers?.length" class="border-t pt-3">
            <h4 class="mb-2 text-sm font-medium">Select Target Player:</h4>
            <div class="flex flex-wrap gap-2">
                <Button
                    v-for="targetPlayer in targetPlayers"
                    :key="targetPlayer.id"
                    :disabled="isLoading || targetPlayer.id === player.id"
                    :variant="selectedTargetPlayer?.id === targetPlayer.id ? 'default' : 'outline'"
                    size="sm"
                    @click="handleSelectTarget(targetPlayer)"
                >
                    {{ targetPlayer.user.firstname }} {{ targetPlayer.user.lastname }}
                </Button>
            </div>
        </div>

        <div class="flex justify-between border-t pt-3">
            <Button
                v-if="selectedCardType"
                :disabled="!canPerformAction || isLoading || !isCurrentTurn"
                @click="handleUseCard"
                :variant="selectedCardType === 'skip_turn' ? 'destructive' : 'default'"
            >
                <Spinner v-if="isLoading" class="mr-2 h-4 w-4"/>
                Use {{
                    selectedCardType === 'skip_turn' ? 'Skip Turn' : selectedCardType === 'pass_turn' ? 'Pass Turn' : 'Hand Shot'
                }} Card
            </Button>

            <Button
                v-if="isCurrentTurn"
                :disabled="isLoading"
                @click="handleRecordTurn"
            >
                <Spinner v-if="isLoading" class="mr-2 h-4 w-4"/>
                End Turn
            </Button>
        </div>
    </div>
</template>
