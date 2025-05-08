// resources/js/Components/TimeFundCard.vue
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
const penaltyPlayersCount = computed(() => {
    const totalPlayers = props.game.total_players_count;
    return Math.floor(totalPlayers / 2);
});

const penaltyPlayers = computed(() => {
    if (!props.game.eliminated_players) return [];

    return props.game.eliminated_players
        .filter(player => player.penalty_paid)
        .sort((a, b) => (b.finish_position || 0) - (a.finish_position || 0));  // Sort by position descending
});

const timeFundTotal = computed(() => {
    return penaltyPlayers.value.length * props.game.penalty_fee;
});

const showTimeFundDetails = computed(() => {
    return props.game.status === 'completed' && penaltyPlayers.value.length > 0;
});
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>Time Fund</CardTitle>
        </CardHeader>
        <CardContent>
            <div class="space-y-4">
                <div class="rounded-md bg-blue-50 p-3 dark:bg-blue-900/20">
                    <p class="text-sm text-blue-800 dark:text-blue-300">
                        The Time Fund is collected from the first eliminated players (half of all participants)
                        and is used by the winner to cover the cost of the table time.
                    </p>
                </div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div class="space-y-2">
                        <p class="text-sm">
                            <span class="font-medium">Penalty Fee:</span>
                            {{ formatCurrency(props.game.penalty_fee) }}
                        </p>
                        <p class="text-sm">
                            <span class="font-medium">Players Paying Penalty:</span>
                            {{ penaltyPlayersCount }} (first eliminated)
                        </p>
                        <p class="text-sm font-medium">
                            <span>Estimated Time Fund:</span>
                            {{ formatCurrency(penaltyPlayersCount * props.game.penalty_fee) }}
                        </p>
                    </div>

                    <div v-if="showTimeFundDetails" class="space-y-2">
                        <p class="text-sm font-medium">Contributors:</p>
                        <ul class="space-y-1 text-sm">
                            <li v-for="player in penaltyPlayers" :key="player.id" class="flex justify-between">
                                <span>
                                    {{ player.user.firstname }} {{ player.user.lastname }}
                                    ({{ player.finish_position }})
                                </span>
                                <span>
                                    {{ formatCurrency(props.game.penalty_fee) }}
                                </span>
                            </li>
                        </ul>
                        <p class="mt-2 border-t pt-2 text-sm font-medium">
                            <span>Actual Time Fund:</span>
                            {{ formatCurrency(timeFundTotal) }}
                        </p>
                    </div>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
