<script lang="ts" setup>
import { Button, Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui';
import type { MultiplayerGamePlayer } from '@/types/api';
import { HandIcon, MinusIcon, PlusIcon, SkipForwardIcon, PlayIcon, CornerRightDownIcon, ArrowRightIcon } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface Props {
    player: MultiplayerGamePlayer;
    targetPlayers: MultiplayerGamePlayer[];
    isCurrentTurn: boolean;
    isLoading?: boolean;
    nextPlayer?: MultiplayerGamePlayer | null;
}

const props = withDefaults(defineProps<Props>(), {
    isLoading: false,
    nextPlayer: null,
});

const emit = defineEmits(['increment-lives', 'decrement-lives', 'record-turn', 'use-card']);

const selectedCard = ref<'skip_turn' | 'pass_turn' | 'hand_shot' | null>(null);
const selectedTargetPlayer = ref<MultiplayerGamePlayer | null>(null);

// Reset selections
const resetSelections = () => {
    selectedCard.value = null;
    selectedTargetPlayer.value = null;
};

// Handle card selection
const selectCard = (cardType: 'skip_turn' | 'pass_turn' | 'hand_shot') => {
    if (selectedCard.value === cardType) {
        resetSelections();
    } else {
        selectedCard.value = cardType;
        selectedTargetPlayer.value = null;
    }
};

// Handle target player selection
const selectTargetPlayer = (player: MultiplayerGamePlayer) => {
    selectedTargetPlayer.value = player;
};

// Check if a card is available
const hasCard = (cardType: string): boolean => {
    return props.player.cards && props.player.cards[cardType] === true;
};

// Use selected card
const useCard = () => {
    if (!selectedCard.value) return;

    // Pass turn card requires a target
    if (selectedCard.value === 'pass_turn' && !selectedTargetPlayer.value) return;

    emit('use-card', {
        cardType: selectedCard.value,
        targetPlayerId: selectedTargetPlayer.value?.user.id,
    });

    // Reset selections after emitting
    resetSelections();
};

// Find the next player in the turn order
const nextPlayerInfo = computed(() => {
    if (props.nextPlayer) return props.nextPlayer;

    // If there are no target players, there can't be a next player
    if (!props.targetPlayers.length) return null;

    // Sort target players by turn order
    const sortedTargetPlayers = [...props.targetPlayers].sort((a, b) => {
        if (a.turn_order === null && b.turn_order !== null) return 1;
        if (a.turn_order !== null && b.turn_order === null) return -1;
        if (a.turn_order === null && b.turn_order === null) return 0;
        return (a.turn_order || 0) - (b.turn_order || 0);
    });

    // Find the player with the next turn order after the current player
    const currentTurnOrder = props.player.turn_order || 0;
    const nextPlayer = sortedTargetPlayers.find(p => (p.turn_order || 0) > currentTurnOrder);

    // If no player with higher turn order is found, wrap around to the first player
    return nextPlayer || sortedTargetPlayers[0] || null;
});
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>
        <span v-if="isCurrentTurn" class="text-green-600 dark:text-green-400">
          <PlayIcon class="mr-2 inline-block h-5 w-5" />
          Your Turn
        </span>
                <span v-else>Player Actions</span>
            </CardTitle>
            <CardDescription v-if="isCurrentTurn">
                Play your turn or take an action
            </CardDescription>
            <CardDescription v-else>
                View player details and options
            </CardDescription>
        </CardHeader>
        <CardContent>
            <div class="space-y-4">
                <!-- Player basic info -->
                <div class="rounded-lg border p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-medium">{{ player.user.firstname }} {{ player.user.lastname }}</h3>
                            <div class="mt-1 flex flex-wrap items-center gap-2">
                <span class="rounded-full bg-gray-100 px-2 py-1 text-xs font-medium text-gray-800 dark:bg-gray-800 dark:text-gray-200">
                  Turn Order: {{ player.turn_order || 'N/A' }}
                </span>
                                <span class="rounded-full bg-gray-100 px-2 py-1 text-xs font-medium text-gray-800 dark:bg-gray-800 dark:text-gray-200">
                  Lives: {{ player.lives }}
                </span>
                            </div>
                        </div>
                        <div class="flex space-x-2">
                            <Button
                                :disabled="isLoading"
                                size="sm"
                                variant="outline"
                                class="text-red-600 border-red-300 hover:bg-red-50 dark:text-red-400 dark:border-red-700 dark:hover:bg-red-900/20"
                                @click="emit('decrement-lives')"
                            >
                                <MinusIcon class="h-4 w-4" />
                            </Button>
                            <Button
                                :disabled="isLoading"
                                size="sm"
                                variant="outline"
                                class="text-green-600 border-green-300 hover:bg-green-50 dark:text-green-400 dark:border-green-700 dark:hover:bg-green-900/20"
                                @click="emit('increment-lives')"
                            >
                                <PlusIcon class="h-4 w-4" />
                            </Button>
                        </div>
                    </div>
                </div>

                <!-- Next Player Information -->
                <div v-if="nextPlayerInfo && isCurrentTurn" class="rounded-lg border border-blue-200 bg-blue-50 p-4 dark:border-blue-800 dark:bg-blue-900/20">
                    <div class="flex items-center space-x-2">
                        <ArrowRightIcon class="h-5 w-5 text-blue-500 dark:text-blue-400" />
                        <div>
                            <h3 class="text-sm font-medium text-blue-800 dark:text-blue-300">Next Turn:</h3>
                            <p class="text-blue-700 dark:text-blue-300">
                                {{ nextPlayerInfo.user.firstname }} {{ nextPlayerInfo.user.lastname }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Action buttons for current turn player -->
                <div v-if="isCurrentTurn">
                    <Button
                        :disabled="isLoading"
                        class="w-full"
                        @click="emit('record-turn')"
                    >
                        <CornerRightDownIcon class="mr-2 h-4 w-4" />
                        End Turn
                    </Button>
                </div>

                <!-- Available cards -->
                <div v-if="(player.cards && Object.values(player.cards).some(value => value))">
                    <h3 class="mb-2 font-medium">Available Cards</h3>
                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-3">
                        <Button
                            v-if="hasCard('skip_turn')"
                            :class="selectedCard === 'skip_turn' ? 'ring-2 ring-blue-500' : ''"
                            :disabled="isLoading"
                            variant="outline"
                            @click="selectCard('skip_turn')"
                        >
                            <SkipForwardIcon class="mr-1 h-4 w-4" />
                            Skip Turn
                        </Button>

                        <Button
                            v-if="hasCard('pass_turn')"
                            :class="selectedCard === 'pass_turn' ? 'ring-2 ring-blue-500' : ''"
                            :disabled="isLoading"
                            variant="outline"
                            @click="selectCard('pass_turn')"
                        >
                            <CornerRightDownIcon class="mr-1 h-4 w-4" />
                            Pass Turn
                        </Button>

                        <Button
                            v-if="hasCard('hand_shot')"
                            :class="selectedCard === 'hand_shot' ? 'ring-2 ring-blue-500' : ''"
                            :disabled="isLoading"
                            variant="outline"
                            @click="selectCard('hand_shot')"
                        >
                            <HandIcon class="mr-1 h-4 w-4" />
                            Hand Shot
                        </Button>
                    </div>
                </div>

                <!-- Target player selection for pass_turn card -->
                <div v-if="selectedCard === 'pass_turn'">
                    <h3 class="mb-2 font-medium">Select Target Player</h3>
                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                        <div
                            v-for="targetPlayer in targetPlayers"
                            :key="targetPlayer.id"
                            :class="[
                'rounded-lg border p-2 cursor-pointer',
                selectedTargetPlayer?.id === targetPlayer.id
                  ? 'ring-2 ring-blue-500 border-blue-300 bg-blue-50 dark:border-blue-700 dark:bg-blue-900/20'
                  : 'hover:bg-gray-50 dark:hover:bg-gray-800'
              ]"
                            @click="selectTargetPlayer(targetPlayer)"
                        >
                            <div class="flex items-center space-x-2">
                                <div class="flex h-8 w-8 items-center justify-center rounded-full bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200">
                                    {{ targetPlayer.turn_order || '-' }}
                                </div>
                                <div>
                                    <p class="font-medium">{{ targetPlayer.user.firstname }} {{ targetPlayer.user.lastname }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Lives: {{ targetPlayer.lives }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Use card button -->
                <Button
                    v-if="selectedCard"
                    :disabled="isLoading || (selectedCard === 'pass_turn' && !selectedTargetPlayer)"
                    class="w-full"
                    @click="useCard"
                >
                    <template v-if="selectedCard === 'skip_turn'">
                        <SkipForwardIcon class="mr-2 h-4 w-4" />
                        Use Skip Turn Card
                    </template>
                    <template v-else-if="selectedCard === 'pass_turn'">
                        <CornerRightDownIcon class="mr-2 h-4 w-4" />
                        Use Pass Turn Card
                    </template>
                    <template v-else-if="selectedCard === 'hand_shot'">
                        <HandIcon class="mr-2 h-4 w-4" />
                        Use Hand Shot Card
                    </template>
                </Button>
            </div>
        </CardContent>
    </Card>
</template>
