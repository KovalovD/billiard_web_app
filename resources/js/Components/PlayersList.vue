// resources/js/Components/PlayersList.vue
<script lang="ts" setup>
import LivesTracker from '@/Components/LivesTracker.vue';
import type {MultiplayerGamePlayer} from '@/types/api';
import {CheckIcon} from 'lucide-vue-next';
import {computed} from 'vue';

interface Props {
    players: MultiplayerGamePlayer[];
    title: string;
    emptyMessage?: string;
    showControls?: boolean;
    highlightCurrentTurn?: boolean;
    selectedPlayerId?: number | null;
}

const props = defineProps<Props>();
const emit = defineEmits(['select-player', 'increment-lives', 'decrement-lives']);

const sortedPlayers = computed(() => {
    return [...props.players].sort((a, b) => {
        // If highlighting current turn is enabled, put current turn player first
        if (props.highlightCurrentTurn) {
            if (a.is_current_turn && !b.is_current_turn) return -1;
            if (!a.is_current_turn && b.is_current_turn) return 1;
        }

        // Otherwise, sort by turn order
        return (a.turn_order || 999) - (b.turn_order || 999);
    });
});

const selectPlayer = (player: MultiplayerGamePlayer) => {
    emit('select-player', player);
};
</script>

<template>
    <div>
        <h3 class="mb-2 font-medium">{{ title }}</h3>

        <div v-if="players.length === 0" class="py-4 text-center text-gray-500 dark:text-gray-400">
            {{ emptyMessage || 'No players' }}
        </div>

        <div v-else class="divide-y">
            <div
                v-for="player in sortedPlayers"
                :key="player.id"
                :class="[
                    'flex items-center justify-between py-3',
                    player.is_current_turn && highlightCurrentTurn ? 'bg-green-50 dark:bg-green-900/10' : '',
                    selectedPlayerId === player.id ? 'border-l-4 border-blue-500 pl-2' : ''
                ]"
                class="cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800/50"
                @click="selectPlayer(player)"
            >
                <div class="flex items-center space-x-3">
                    <div
                        v-if="player.is_current_turn && highlightCurrentTurn"
                        class="flex h-6 w-6 items-center justify-center rounded-full bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300"
                    >
                        <CheckIcon class="h-4 w-4"/>
                    </div>
                    <div v-else class="h-6 w-6 text-center text-sm text-gray-500">
                        {{ player.turn_order || '-' }}
                    </div>
                    <div>
                        <p class="font-medium">{{ player.user.firstname }} {{ player.user.lastname }}</p>
                    </div>
                </div>

                <div class="flex items-center space-x-2">
                    <LivesTracker :lives="player.lives" :size="'sm'"/>

                </div>
            </div>
        </div>
    </div>
</template>
