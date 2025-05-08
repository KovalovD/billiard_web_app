// resources/js/Components/PrizeSummaryCard.vue
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
const totalPlayers = computed(() => props.game.total_players_count || 0);
const totalPrizePool = computed(() => props.game.entrance_fee * totalPlayers.value);

const firstPlaceAmount = computed(() => {
    if (props.game.financial_data) {
        return props.game.financial_data.first_place_prize;
    }
    return Math.round(totalPrizePool.value * (props.game.first_place_percent / 100));
});

const secondPlaceAmount = computed(() => {
    if (props.game.financial_data) {
        return props.game.financial_data.second_place_prize;
    }
    return Math.round(totalPrizePool.value * (props.game.second_place_percent / 100));
});

const grandFinalAmount = computed(() => {
    if (props.game.financial_data) {
        return props.game.financial_data.grand_final_fund;
    }
    return totalPrizePool.value - firstPlaceAmount.value - secondPlaceAmount.value;
});

// Winners if game is completed
const winners = computed(() => {
    if (props.game.status !== 'completed') return [];

    const winnersList = [];

    // Find first place winner
    const firstPlace = props.game.eliminated_players.find(p => p.finish_position === 1);
    if (firstPlace) {
        winnersList.push({
            position: 1,
            player: `${firstPlace.user.firstname} ${firstPlace.user.lastname}`,
            prize: firstPlace.prize_amount || firstPlaceAmount.value
        });
    }

    // Find second place winner
    const secondPlace = props.game.eliminated_players.find(p => p.finish_position === 2);
    if (secondPlace) {
        winnersList.push({
            position: 2,
            player: `${secondPlace.user.firstname} ${secondPlace.user.lastname}`,
            prize: secondPlace.prize_amount || secondPlaceAmount.value
        });
    }

    return winnersList;
});

const showWinners = computed(() => props.game.status === 'completed' || props.game.status === 'finished' && winners.value.length > 0);
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>Prize Summary</CardTitle>
        </CardHeader>
        <CardContent>
            <div class="space-y-4">
                <div class="rounded-md bg-blue-50 p-3 dark:bg-blue-900/20">
                    <p class="text-sm text-blue-800 dark:text-blue-300">
                        Total Prize Pool: <span class="font-medium">{{ formatCurrency(totalPrizePool) }}</span>
                        ({{ totalPlayers }} players × {{ formatCurrency(props.game.entrance_fee) }})
                    </p>
                </div>

                <div v-if="!showWinners" class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div class="rounded-md bg-gray-50 p-3 dark:bg-gray-800">
                        <p class="mb-1 text-sm font-medium">1st Place ({{ props.game.first_place_percent }}%)</p>
                        <p class="text-lg font-bold">{{ formatCurrency(firstPlaceAmount) }}</p>
                    </div>

                    <div class="rounded-md bg-gray-50 p-3 dark:bg-gray-800">
                        <p class="mb-1 text-sm font-medium">2nd Place ({{ props.game.second_place_percent }}%)</p>
                        <p class="text-lg font-bold">{{ formatCurrency(secondPlaceAmount) }}</p>
                    </div>

                    <div class="rounded-md bg-gray-50 p-3 dark:bg-gray-800">
                        <p class="mb-1 text-sm font-medium">Grand Final Fund ({{ props.game.grand_final_percent }}%)</p>
                        <p class="text-lg font-bold">{{ formatCurrency(grandFinalAmount) }}</p>
                    </div>
                </div>

                <div v-else>
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
                                <th class="px-2 py-2 text-right text-sm font-medium text-gray-500 dark:text-gray-400">
                                    Prize Amount
                                </th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr
                                v-for="winner in winners"
                                :key="winner.position"
                                class="border-b dark:border-gray-700"
                            >
                                <td class="px-2 py-2 text-sm">
                                        <span
                                            :class="{
                                                'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300': winner.position === 1,
                                                'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200': winner.position === 2
                                            }"
                                            class="inline-flex h-6 w-6 items-center justify-center rounded-full text-xs font-medium"
                                        >
                                            {{ winner.position }}
                                        </span>
                                </td>
                                <td class="px-2 py-2 text-sm">{{ winner.player }}</td>
                                <td class="px-2 py-2 text-right text-sm font-bold">{{
                                        formatCurrency(winner.prize)
                                    }}
                                </td>
                            </tr>
                            <tr>
                                <td class="px-2 py-2 text-sm text-center" colspan="2">Grand Final Fund</td>
                                <td class="px-2 py-2 text-right text-sm font-bold">{{
                                        formatCurrency(grandFinalAmount)
                                    }}
                                </td>
                            </tr>
                            </tbody>
                            <tfoot>
                            <tr class="border-t dark:border-gray-700">
                                <th class="px-2 py-2 text-right text-sm font-medium text-gray-500 dark:text-gray-400"
                                    colspan="2">
                                    Total Prize Pool
                                </th>
                                <th class="px-2 py-2 text-right text-sm font-bold">
                                    {{ formatCurrency(totalPrizePool) }}
                                </th>
                            </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <div class="text-xs text-gray-500">
                    <p>The Grand Final Fund is accumulated across all games and used for the seasonal final
                        tournament.</p>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
