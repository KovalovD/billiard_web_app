// resources/js/Components/PrizeDistributionCard.vue
<script lang="ts" setup>
import {Card, CardContent, CardHeader, CardTitle} from '@/Components/ui';
import type {MultiplayerGame} from '@/types/api';
import {computed} from 'vue';

interface Props {
    game: MultiplayerGame;
}

const props = defineProps<Props>();

// Format currency amount
const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('uk-UA', {style: 'currency', currency: 'UAH'})
        .format(amount)
        .replace('UAH', '₴');
};

// Computed properties
const showPrizeData = computed(() => props.game.status === 'completed');

// Get only players with prizes
const playersWithPrizes = computed(() => {
    if (!props.game.eliminated_players) return [];

    return props.game.eliminated_players
        .filter(player => player.prize_amount && player.prize_amount > 0)
        .sort((a, b) => (a.finish_position || Infinity) - (b.finish_position || Infinity));
});

// Get only players who paid penalty
const playersWithPenalty = computed(() => {
    if (!props.game.eliminated_players) return [];

    return props.game.eliminated_players
        .filter(player => player.penalty_paid)
        .sort((a, b) => (b.finish_position || 0) - (a.finish_position || 0));  // Sort by position descending (last eliminated first)
});

// Calculate total prize pool
const totalPrizePool = computed(() => {
    if (!props.game.financial_data) return 0;
    return props.game.financial_data.total_prize_pool || 0;
});

// Calculate total time fund
const totalTimeFund = computed(() => {
    if (!props.game.financial_data) return 0;
    return props.game.financial_data.time_fund_total || 0;
});
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>Prize Distribution</CardTitle>
        </CardHeader>
        <CardContent>
            <div v-if="!showPrizeData" class="text-center text-gray-500 dark:text-gray-400">
                <p>Prizes will be distributed when the game is completed.</p>
            </div>

            <div v-else class="space-y-6">
                <!-- Prize winners -->
                <div>
                    <h3 class="mb-2 font-medium">Prize Winners</h3>

                    <div v-if="playersWithPrizes.length === 0" class="text-center text-gray-500 dark:text-gray-400">
                        <p>No prizes have been distributed yet.</p>
                    </div>

                    <div v-else class="divide-y">
                        <div
                            v-for="player in playersWithPrizes"
                            :key="player.id"
                            class="flex items-center justify-between py-3"
                        >
                            <div class="flex items-center space-x-3">
                                <div
                                    :class="{
                                        'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300': player.finish_position === 1,
                                        'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200': player.finish_position === 2
                                    }"
                                    class="flex h-6 w-6 items-center justify-center rounded-full text-xs font-medium"
                                >
                                    {{ player.finish_position }}
                                </div>
                                <div>
                                    <p class="font-medium">{{ player.user.firstname }} {{ player.user.lastname }}</p>
                                </div>
                            </div>

                            <div class="text-right font-medium">
                                {{ formatCurrency(player.prize_amount || 0) }}
                            </div>
                        </div>
                    </div>

                    <div class="mt-2 flex justify-between border-t pt-2 text-sm">
                        <span class="text-gray-500">Grand Final Fund:</span>
                        <span class="font-medium">
                            {{ formatCurrency(props.game.financial_data?.grand_final_fund || 0) }}
                        </span>
                    </div>

                    <div class="flex justify-between text-sm font-medium">
                        <span class="text-gray-500">Total Prize Pool:</span>
                        <span>{{ formatCurrency(totalPrizePool) }}</span>
                    </div>
                </div>

                <!-- Time Fund -->
                <div>
                    <h3 class="mb-2 font-medium">Time Fund Contributors</h3>

                    <div v-if="playersWithPenalty.length === 0" class="text-center text-gray-500 dark:text-gray-400">
                        <p>No time fund contributions have been recorded yet.</p>
                    </div>

                    <div v-else class="divide-y">
                        <div
                            v-for="player in playersWithPenalty"
                            :key="player.id"
                            class="flex items-center justify-between py-3"
                        >
                            <div class="flex items-center space-x-3">
                                <div class="h-6 w-6 text-center text-sm text-gray-500">
                                    {{ player.finish_position }}
                                </div>
                                <div>
                                    <p class="font-medium">{{ player.user.firstname }} {{ player.user.lastname }}</p>
                                </div>
                            </div>

                            <div class="text-right font-medium">
                                {{ formatCurrency(player.time_fund_contribution || 0) }}
                            </div>
                        </div>
                    </div>

                    <div class="mt-2 flex justify-between border-t pt-2 text-sm font-medium">
                        <span class="text-gray-500">Total Time Fund:</span>
                        <span>{{ formatCurrency(totalTimeFund) }}</span>
                    </div>
                </div>

                <div class="text-xs text-gray-500">
                    <p>The Time Fund is used by the winner to cover the cost of the table time.</p>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
