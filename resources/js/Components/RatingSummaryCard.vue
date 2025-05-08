// resources/js/Components/RatingSummaryCard.vue
<script lang="ts" setup>
import {Card, CardContent, CardHeader, CardTitle} from '@/Components/ui';
import type {MultiplayerGame} from '@/types/api';
import {computed} from 'vue';

interface Props {
    game: MultiplayerGame;
}

const props = defineProps<Props>();

// Computed properties
const showRatingData = computed(() => props.game.status === 'completed' || props.game.status === 'finished');

// Sort players by rating points
const sortedPlayers = computed(() => {
    if (!props.game.eliminated_players) return [];

    return [...props.game.eliminated_players]
        .filter(player => player.rating_points !== undefined && player.rating_points > 0)
        .sort((a, b) => {
            // First by position (winner first)
            if (a.finish_position !== b.finish_position) {
                return (a.finish_position || Infinity) - (b.finish_position || Infinity);
            }

            // Then by rating points (higher first)
            return (b.rating_points || 0) - (a.rating_points || 0);
        });
});
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>Rating Summary</CardTitle>
        </CardHeader>
        <CardContent>
            <div v-if="!showRatingData" class="text-center text-gray-500 dark:text-gray-400">
                <p>Rating points will be calculated when the game is completed.</p>
            </div>

            <div v-else-if="sortedPlayers.length === 0" class="text-center text-gray-500 dark:text-gray-400">
                <p>No rating points have been assigned yet.</p>
            </div>

            <div v-else class="space-y-4">
                <div class="rounded-md bg-blue-50 p-3 dark:bg-blue-900/20">
                    <p class="text-sm text-blue-800 dark:text-blue-300">
                        Rating points are calculated based on finish position:
                        <span
                            class="font-medium">Last position gets 1 point, second-to-last gets 2 points, and so on.</span>
                    </p>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                        <tr class="border-b dark:border-gray-700">
                            <th class="px-2 py-2 text-left text-sm font-medium text-gray-500 dark:text-gray-400">
                                Position
                            </th>
                            <th class="px-2 py-2 text-left text-sm font-medium text-gray-500 dark:text-gray-400">
                                Player
                            </th>
                            <th class="px-2 py-2 text-right text-sm font-medium text-gray-500 dark:text-gray-400">Rating
                                Points
                            </th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr
                            v-for="player in sortedPlayers"
                            :key="player.id"
                            class="border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800/50"
                        >
                            <td class="px-2 py-2 text-sm">
                                    <span
                                        :class="{
                                            'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300': player.finish_position === 1,
                                            'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200': player.finish_position !== 1
                                        }"
                                        class="inline-flex h-6 w-6 items-center justify-center rounded-full text-xs font-medium"
                                    >
                                        {{ player.finish_position }}
                                    </span>
                            </td>
                            <td class="px-2 py-2 text-sm">
                                {{ player.user.firstname }} {{ player.user.lastname }}
                            </td>
                            <td class="px-2 py-2 text-right text-sm font-medium">
                                    <span
                                        class="rounded-full bg-blue-100 px-2 py-1 text-xs text-blue-800 dark:bg-blue-900/30 dark:text-blue-300"
                                    >
                                        +{{ player.rating_points }}
                                    </span>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
