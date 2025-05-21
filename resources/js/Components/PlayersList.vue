<script lang="ts" setup>
import PlayerTurnIndicator from '@/Components/PlayerTurnIndicator.vue';
import { Button } from '@/Components/ui';
import type { MultiplayerGamePlayer } from '@/types/api';
import { MinusIcon, PlusIcon } from 'lucide-vue-next';
import {computed} from "vue";

interface Props {
    players: MultiplayerGamePlayer[];
    highlightCurrentTurn?: boolean;
    showControls?: boolean;
    title?: string;
    selectedPlayerId?: number | null;
    currentTurnPlayerId?: number | null;
    nextTurnPlayerId?: number | null;
}

const props = withDefaults(defineProps<Props>(), {
    highlightCurrentTurn: false,
    showControls: false,
    title: 'Players',
    selectedPlayerId: null,
    currentTurnPlayerId: null,
    nextTurnPlayerId: null,
});

const emit = defineEmits(['select-player', 'increment-lives', 'decrement-lives']);

// Helper to get players sorted by turn order
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

// Determine if a player is the next turn player
const isNextTurn = (player: MultiplayerGamePlayer): boolean => {
    if (props.nextTurnPlayerId) {
        return player.user.id === props.nextTurnPlayerId;
    }

    // Find the current player's index
    const currentPlayerIndex = sortedPlayers.value.findIndex(p => isCurrentTurn(p));
    if (currentPlayerIndex === -1) return false;

    // Next player is the one after the current player in the circular list
    const nextPlayerIndex = (currentPlayerIndex + 1) % sortedPlayers.value.length;
    return player === sortedPlayers.value[nextPlayerIndex];
};

const selectPlayer = (player: MultiplayerGamePlayer) => {
    emit('select-player', player);
};
</script>

<template>
    <div>
        <h3 v-if="title" class="mb-4 font-medium text-lg">{{ title }}</h3>

        <div class="space-y-3">
            <div
                v-for="player in sortedPlayers"
                :key="player.id"
                :class="[
          'rounded-lg border p-3 transition',
          isCurrentTurn(player) ? 'border-green-300 bg-green-50 dark:border-green-800 dark:bg-green-900/20' : '',
          isNextTurn(player) ? 'border-blue-300 bg-blue-50 dark:border-blue-800 dark:bg-blue-900/20' : '',
          selectedPlayerId === player.id
            ? 'ring-2 ring-blue-500 dark:ring-blue-400'
            : 'hover:bg-gray-50 dark:hover:bg-gray-800',
          'cursor-pointer'
        ]"
                @click="selectPlayer(player)"
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

                    <div v-if="showControls" class="flex space-x-1">
                        <Button size="sm" variant="outline" @click.stop="emit('decrement-lives', player.user.id)">
                            <MinusIcon class="h-4 w-4" />
                        </Button>
                        <Button size="sm" variant="outline" @click.stop="emit('increment-lives', player.user.id)">
                            <PlusIcon class="h-4 w-4" />
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
