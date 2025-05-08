// resources/js/Components/FinancialSummaryCard.vue
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

// Computed values
const entranceFee = computed(() => props.game.entrance_fee || 300);
const totalPlayers = computed(() => props.game.total_players_count || 0);
const totalPrizePool = computed(() => entranceFee.value * totalPlayers.value);

const firstPlacePercent = computed(() => props.game.first_place_percent || 60);
const secondPlacePercent = computed(() => props.game.second_place_percent || 20);
const grandFinalPercent = computed(() => props.game.grand_final_percent || 20);

const firstPlacePrize = computed(() => (totalPrizePool.value * firstPlacePercent.value) / 100);
const secondPlacePrize = computed(() => (totalPrizePool.value * secondPlacePercent.value) / 100);
const grandFinalFund = computed(() => (totalPrizePool.value * grandFinalPercent.value) / 100);

const penaltyFee = computed(() => props.game.penalty_fee || 50);
const penaltyPlayersCount = computed(() => Math.floor(totalPlayers.value / 2));
const timeFundTotal = computed(() => penaltyFee.value * penaltyPlayersCount.value);

// Show financial data based on game status
const showFullFinancialData = computed(() => props.game.status === 'completed');
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>Financial Summary</CardTitle>
        </CardHeader>
        <CardContent>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div class="space-y-4">
                    <div>
                        <h3 class="font-medium">Entry & Prize Breakdown</h3>
                        <dl class="mt-2 space-y-1">
                            <div class="flex justify-between">
                                <dt class="text-gray-500">Entrance Fee:</dt>
                                <dd>{{ formatCurrency(entranceFee) }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-gray-500">Total Players:</dt>
                                <dd>{{ totalPlayers }}</dd>
                            </div>
                            <div class="flex justify-between font-medium">
                                <dt class="text-gray-500">Total Prize Pool:</dt>
                                <dd>{{ formatCurrency(totalPrizePool) }}</dd>
                            </div>
                        </dl>
                    </div>

                    <div>
                        <h3 class="font-medium">Prize Distribution</h3>
                        <dl class="mt-2 space-y-1">
                            <div class="flex justify-between">
                                <dt class="text-gray-500">1st Place ({{ firstPlacePercent }}%):</dt>
                                <dd>{{ formatCurrency(firstPlacePrize) }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-gray-500">2nd Place ({{ secondPlacePercent }}%):</dt>
                                <dd>{{ formatCurrency(secondPlacePrize) }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-gray-500">Grand Final Fund ({{ grandFinalPercent }}%):</dt>
                                <dd>{{ formatCurrency(grandFinalFund) }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>

                <div class="space-y-4">
                    <div>
                        <h3 class="font-medium">Time Fund</h3>
                        <dl class="mt-2 space-y-1">
                            <div class="flex justify-between">
                                <dt class="text-gray-500">Penalty Fee:</dt>
                                <dd>{{ formatCurrency(penaltyFee) }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-gray-500">Players Paying Penalty:</dt>
                                <dd>{{ penaltyPlayersCount }} (first eliminated)</dd>
                            </div>
                            <div class="flex justify-between font-medium">
                                <dt class="text-gray-500">Time Fund Total:</dt>
                                <dd>{{ formatCurrency(timeFundTotal) }}</dd>
                            </div>
                        </dl>
                        <p class="mt-2 text-xs text-gray-500">
                            The Time Fund is used by the winner to cover the cost of the table time.
                        </p>
                    </div>

                    <div v-if="game.financial_data && showFullFinancialData">
                        <h3 class="font-medium">Final Results</h3>
                        <dl class="mt-2 space-y-1">
                            <div class="flex justify-between">
                                <dt class="text-gray-500">1st Place Prize:</dt>
                                <dd>{{ formatCurrency(game.financial_data.first_place_prize) }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-gray-500">2nd Place Prize:</dt>
                                <dd>{{ formatCurrency(game.financial_data.second_place_prize) }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-gray-500">Grand Final Fund:</dt>
                                <dd>{{ formatCurrency(game.financial_data.grand_final_fund) }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-gray-500">Time Fund Generated:</dt>
                                <dd>{{ formatCurrency(game.financial_data.time_fund_total) }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
