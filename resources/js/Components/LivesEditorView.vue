<script lang="ts" setup>
import PlayerTurnIndicator from '@/Components/PlayerTurnIndicator.vue';
import { Button, Card, CardContent, CardHeader, CardTitle } from '@/Components/ui';
import type { MultiplayerGamePlayer } from '@/types/api';
import { MinusIcon, PlusIcon, CornerRightDownIcon } from 'lucide-vue-next';
import {computed} from "vue";

interface Props {
    players: MultiplayerGamePlayer[];
    isLoading?: boolean;
    currentTurnPlayerId?: number | null;
}

const props = withDefaults(defineProps<Props>(), {
    isLoading: false,
    currentTurnPlayerId: null,
});

const emit = defineEmits(['increment-lives', 'decrement-lives', 'set-turn']);

// Helper to sort players by turn order
const sortedPlayers = computed(() => {
    return [...props.players].sort((a, b) => {
        if (a.turn_order === null && b.turn_order !== null) return 1;
        if (a.turn_order !== null && b.turn_order === null) return -1;
        if (a.turn_order === null && b.turn_order === null) return 0;
        return (a.turn_order || 0) - (b.turn_order || 0);
    });
});

// Determine if a player is the current turn player
const isCurrentTurn = (player: MultiplayerGamePlayer): boolean => {
    if (props.currentTurnPlayerId) {
        return player.user.id === props.currentTurnPlayerId;
    }
    return player.is_current_turn || false;
};

// Find the next player in turn order
const getNextPlayerId = computed(() => {
    if (!props.players.length) return null;

    // Find the current player's index
    const currentPlayerIndex = sortedPlayers.value.findIndex(p => isCurrentTurn(p));
    if (currentPlayerIndex === -1) return null;

    // Next player is the one after the current player in the circular list
    const nextPlayerIndex = (currentPlayerIndex + 1) % sortedPlayers.value.length;
    return sortedPlayers.value[nextPlayerIndex].user.id;
});

// Determine if a player is the next turn player
const isNextTurn = (player: MultiplayerGamePlayer): boolean => {
    return player.user.id === getNextPlayerId.value;
};
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>Players Lives Editor</CardTitle>
        </CardHeader>
        <CardContent>
            <div class="space-y-4">
                <div
                    v-for="player in sortedPlayers"
                    :key="player.id"
                    :class="[
            'rounded-lg border p-3',
            isCurrentTurn(player) ? 'border-green-300 bg-green-50 dark:border-green-800 dark:bg-green-900/20' : '',
            isNextTurn(player) ? 'border-blue-300 bg-blue-50 dark:border-blue-800 dark:bg-blue-900/20' : '',
          ]"
                >
                    <div class="flex items-start justify-between">
                        <div class="flex items-center space-x-3">
                            <div
                                :class="[
                  'flex h-8 w-8 items-center justify-center rounded-full text-sm font-medium',
                  isCurrentTurn(player)
                    ? 'bg-green-200 text-green-800 dark:bg-green-800 dark:text-green-200'
                    : isNextTurn(player)
                    ? 'bg-blue-200 text-blue-800 dark:bg-blue-800 dark:text-blue-200'
                    : 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200'
                ]"
                            >
                                {{ player.turn_order || '-' }}
                            </div>
                            <div>
                                <p class="font-medium">{{ player.user.firstname }} {{ player.user.lastname }}</p>
                                <div class="flex items-center space-x-2 mt-1">
                                    <PlayerTurnIndicator
                                        :is-current-turn="isCurrentTurn(player)"
                                        :is-next-turn="isNextTurn(player)"
                                    />

                                    <span
                                        class="inline-flex items-center rounded-full bg-gray-100 px-2 py-1 text-xs font-medium text-gray-800 dark:bg-gray-800 dark:text-gray-200"
                                    >
                    Lives: {{ player.lives }}
                  </span>
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center space-x-2">
                            <Button
                                :disabled="isLoading"
                                size="sm"
                                variant="outline"
                                class="text-red-600 border-red-300 hover:bg-red-50 dark:text-red-400 dark:border-red-700 dark:hover:bg-red-900/20"
                                @click="emit('decrement-lives', player.user.id)"
                            >
                                <MinusIcon class="h-4 w-4" />
                            </Button>
                            <Button
                                :disabled="isLoading"
                                size="sm"
                                variant="outline"
                                class="text-green-600 border-green-300 hover:bg-green-50 dark:text-green-400 dark:border-green-700 dark:hover:bg-green-900/20"
                                @click="emit('increment-lives', player.user.id)"
                            >
                                <PlusIcon class="h-4 w-4" />
                            </Button>
                            <Button
                                v-if="!isCurrentTurn(player)"
                                :disabled="isLoading"
                                size="sm"
                                variant="outline"
                                class="text-blue-600 border-blue-300 hover:bg-blue-50 dark:text-blue-400 dark:border-blue-700 dark:hover:bg-blue-900/20"
                                @click="emit('set-turn', player.user.id)"
                            >
                                <CornerRightDownIcon class="h-4 w-4" />
                                <span class="sr-only">Set as Current Turn</span>
                            </Button>
                        </div>
                    </div>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
