// resources/js/Components/MultiplayerGameSummary.vue
<script lang="ts" setup>
import {Card, CardContent, CardHeader, CardTitle} from '@/Components/ui';
import type {MultiplayerGame} from '@/types/api';
import {computed} from 'vue';

interface Props {
    game: MultiplayerGame;
}

const props = defineProps<Props>();

// Format date for display
const formatDate = (dateString: string | null): string => {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleString();
};

// Format currency amount
const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('uk-UA', {style: 'currency', currency: 'UAH'})
        .format(amount)
        .replace('UAH', '₴');
};

// Get status badge color class
const statusBadgeClass = computed(() => {
    switch (props.game.status) {
        case 'registration':
            return 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300';
        case 'in_progress':
            return 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300';
        case 'completed':
            return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
        default:
            return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
    }
});

// Get status display text
const statusText = computed(() => {
    switch (props.game.status) {
        case 'registration':
            return 'Registration Open';
        case 'in_progress':
            return 'In Progress';
        case 'completed':
            return 'Completed';
        default:
            return props.game.status;
    }
});

// Get moderator name
const moderatorName = computed(() => {
    if (!props.game.moderator_user_id) return 'No moderator assigned';

    const moderator = props.game.active_players.find(p => p.user.id === props.game.moderator_user_id);
    if (!moderator) return 'Unknown moderator';

    return `${moderator.user.firstname} ${moderator.user.lastname}`;
});

// Get winner name if game is completed
const winnerName = computed(() => {
    if (props.game.status !== 'completed') return null;

    const winner = [...props.game.eliminated_players, ...props.game.active_players]
        .find(p => p.finish_position === 1);

    if (!winner) return 'No winner determined';

    return `${winner.user.firstname} ${winner.user.lastname}`;
});

// Calculate total time fund
const timeFundTotal = computed(() => {
    const penaltyPlayers = props.game.eliminated_players.filter(p => p.penalty_paid);
    return penaltyPlayers.length * props.game.penalty_fee;
});
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>Game Summary</CardTitle>
        </CardHeader>
        <CardContent>
            <div class="space-y-4">
                <div class="flex flex-wrap items-center justify-between">
                    <h2 class="text-xl font-semibold">{{ props.game.name }}</h2>
                    <span :class="['rounded-full px-3 py-1 text-sm font-medium', statusBadgeClass]">
                        {{ statusText }}
                    </span>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div class="rounded-lg bg-gray-50 p-3 dark:bg-gray-800">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Players</p>
                        <p class="font-medium">
                            {{ props.game.active_players_count }} active / {{ props.game.total_players_count }} total
                        </p>
                    </div>

                    <div class="rounded-lg bg-gray-50 p-3 dark:bg-gray-800">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Initial Lives</p>
                        <p class="font-medium">{{ props.game.initial_lives }}</p>
                    </div>

                    <div class="rounded-lg bg-gray-50 p-3 dark:bg-gray-800">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Moderator</p>
                        <p class="font-medium">{{ moderatorName }}</p>
                    </div>

                    <div v-if="props.game.status !== 'registration'" class="rounded-lg bg-gray-50 p-3 dark:bg-gray-800">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Started</p>
                        <p class="font-medium">{{ formatDate(props.game.started_at) }}</p>
                    </div>

                    <div v-if="props.game.status === 'completed'" class="rounded-lg bg-gray-50 p-3 dark:bg-gray-800">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Completed</p>
                        <p class="font-medium">{{ formatDate(props.game.completed_at) }}</p>
                    </div>

                    <div v-if="winnerName" class="rounded-lg bg-gray-50 p-3 dark:bg-gray-800">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Winner</p>
                        <p class="font-medium">{{ winnerName }}</p>
                    </div>
                </div>

                <div class="rounded-lg bg-gray-50 p-4 dark:bg-gray-800">
                    <h3 class="mb-2 font-medium">Financial Summary</h3>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <p class="text-sm">
                                <span class="text-gray-500 dark:text-gray-400">Entrance Fee:</span>
                                {{ formatCurrency(props.game.entrance_fee) }}
                            </p>
                            <p class="text-sm">
                                <span class="text-gray-500 dark:text-gray-400">Total Prize Pool:</span>
                                {{ formatCurrency(props.game.entrance_fee * props.game.total_players_count) }}
                            </p>
                            <p class="text-sm">
                                <span class="text-gray-500 dark:text-gray-400">Distribution:</span>
                                1st: {{ props.game.first_place_percent }}%,
                                2nd: {{ props.game.second_place_percent }}%,
                                Grand Final: {{ props.game.grand_final_percent }}%
                            </p>
                        </div>

                        <div>
                            <p class="text-sm">
                                <span class="text-gray-500 dark:text-gray-400">Penalty Fee:</span>
                                {{ formatCurrency(props.game.penalty_fee) }}
                            </p>
                            <p class="text-sm">
                                <span class="text-gray-500 dark:text-gray-400">Penalty Players:</span>
                                {{ props.game.eliminated_players.filter(p => p.penalty_paid).length }}
                            </p>
                            <p class="text-sm">
                                <span class="text-gray-500 dark:text-gray-400">Time Fund:</span>
                                {{ formatCurrency(timeFundTotal) }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
