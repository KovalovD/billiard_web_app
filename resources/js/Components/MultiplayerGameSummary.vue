<script lang="ts" setup>
import TurnOrderBanner from '@/Components/TurnOrderBanner.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui';
import type { MultiplayerGame, MultiplayerGamePlayer } from '@/types/api';
import { CalendarIcon, PlayIcon, UserIcon, UsersIcon } from 'lucide-vue-next';
import {computed} from "vue";

interface Props {
    game: MultiplayerGame;
}

const props = defineProps<Props>();

// Format date for display
const formatDate = (dateString: string | null): string => {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleString();
};

// Find the next player in turn order
const getNextPlayer = computed(() => {
    if (props.game.status !== 'in_progress' || !props.game.active_players.length) return null;

    // Find the current player's index
    const currentPlayerIndex = props.game.active_players.findIndex(p => p.is_current_turn);
    if (currentPlayerIndex === -1) return null;

    // Next player is the one after the current player in the circular list
    const nextPlayerIndex = (currentPlayerIndex + 1) % props.game.active_players.length;
    return props.game.active_players[nextPlayerIndex];
});

// Find the current turn player
const getCurrentPlayer = computed(() => {
    if (props.game.status !== 'in_progress') return null;
    return props.game.active_players.find(p => p.is_current_turn) || null;
});
</script>

<template>
    <!-- Turn Order Banner -->
    <TurnOrderBanner v-if="props.game.status === 'in_progress'" :current-player="getCurrentPlayer" :next-player="getNextPlayer" />

    <Card>
        <CardHeader>
            <CardTitle>Game Summary</CardTitle>
        </CardHeader>
        <CardContent>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-3">
                <div class="rounded-lg bg-gray-50 p-3 dark:bg-gray-800">
                    <div class="flex items-center gap-2">
                        <UsersIcon class="h-5 w-5 text-gray-500 dark:text-gray-400" />
                        <span class="font-medium text-gray-600 dark:text-gray-400">Players</span>
                    </div>
                    <p class="mt-1 text-gray-900 dark:text-gray-200">
                        {{ game.active_players_count }} active / {{ game.total_players_count }} total
                    </p>
                </div>

                <div class="rounded-lg bg-gray-50 p-3 dark:bg-gray-800">
                    <div class="flex items-center gap-2">
                        <CalendarIcon class="h-5 w-5 text-gray-500 dark:text-gray-400" />
                        <span class="font-medium text-gray-600 dark:text-gray-400">Started</span>
                    </div>
                    <p class="mt-1 text-gray-900 dark:text-gray-200">
                        {{ formatDate(game.started_at) }}
                    </p>
                </div>

                <div class="rounded-lg bg-gray-50 p-3 dark:bg-gray-800" v-if="game.status === 'in_progress'">
                    <div class="flex items-center gap-2">
                        <PlayIcon class="h-5 w-5 text-green-500 dark:text-green-400" />
                        <span class="font-medium text-gray-600 dark:text-gray-400">Current Turn</span>
                    </div>
                    <div v-if="getCurrentPlayer" class="mt-1">
                        <p class="text-gray-900 dark:text-gray-200 font-medium">
                            {{ getCurrentPlayer.user.firstname }} {{ getCurrentPlayer.user.lastname }}
                        </p>
                    </div>
                    <p v-else class="mt-1 text-gray-500 dark:text-gray-400">
                        No active players
                    </p>
                </div>

                <div class="rounded-lg bg-blue-50 p-3 dark:bg-blue-900/20" v-if="game.status === 'in_progress' && getNextPlayer">
                    <div class="flex items-center gap-2">
                        <UserIcon class="h-5 w-5 text-blue-500 dark:text-blue-400" />
                        <span class="font-medium text-blue-600 dark:text-blue-400">Next Turn</span>
                    </div>
                    <div class="mt-1">
                        <p class="text-blue-900 dark:text-blue-200 font-medium">
                            {{ getNextPlayer.user.firstname }} {{ getNextPlayer.user.lastname }}
                        </p>
                    </div>
                </div>

                <div class="rounded-lg bg-gray-50 p-3 dark:bg-gray-800" v-if="game.status === 'completed'">
                    <div class="flex items-center gap-2">
                        <CalendarIcon class="h-5 w-5 text-gray-500 dark:text-gray-400" />
                        <span class="font-medium text-gray-600 dark:text-gray-400">Completed</span>
                    </div>
                    <p class="mt-1 text-gray-900 dark:text-gray-200">
                        {{ formatDate(game.completed_at) }}
                    </p>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
