<script lang="ts" setup>
import {onMounted, ref} from 'vue';
import {useLocale} from '@/composables/useLocale';
import {apiClient} from '@/lib/apiClient';
import {Card, CardContent, CardDescription, CardHeader, CardTitle, Spinner} from '@/Components/ui';
import type {User} from '@/types/api';
import UserAvatar from '@/Components/Core/UserAvatar.vue';

interface RoundRobinStanding {
    position: number;
    player: User;
    wins: number;
    losses: number;
    games_diff: string | number; // Can be either string or number from API
    win_rate: string;
    matches_played: number;
}

interface RoundRobinResponse {
    standings: RoundRobinStanding[];
    progress: {
        completed: number;
        total: number;
        percentage: number;
    };
    is_completed: boolean;
}

const props = defineProps<{
    tournamentId: number | string;
}>();

const {t} = useLocale();

const loading = ref(true);
const standings = ref<RoundRobinStanding[]>([]);
const progress = ref({
    completed: 0,
    total: 0,
    percentage: 0
});
const isCompleted = ref(false);
const error = ref<string | null>(null);

const fetchStandings = async () => {
    try {
        loading.value = true;
        error.value = null;

        const response = await apiClient<RoundRobinResponse>(
            `/api/tournaments/${props.tournamentId}/round-robin/standings`
        );

        standings.value = response.standings;
        progress.value = response.progress;
        isCompleted.value = response.is_completed;
    } catch (err: any) {
        console.error('Failed to fetch standings:', err);
        error.value = err.message || t('Failed to load standings');
    } finally {
        loading.value = false;
    }
};

// Get row class based on position
const getRowClass = (position: number) => {
    switch (position) {
        case 1:
            return 'bg-yellow-50 dark:bg-yellow-900/20';
        case 2:
            return 'bg-gray-50 dark:bg-gray-900/20';
        case 3:
            return 'bg-orange-50 dark:bg-orange-900/20';
        default:
            return '';
    }
};

// Get position badge class
const getPositionBadgeClass = (position: number) => {
    switch (position) {
        case 1:
            return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300';
        case 2:
            return 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200';
        case 3:
            return 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-300';
        default:
            return 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300';
    }
};

// Get games diff color - handle both string and number
const getGamesDiffClass = (diff: string | number) => {
    const diffStr = String(diff); // Convert to string for comparison
    if (diffStr.startsWith('+') || Number(diff) > 0) return 'text-green-600 dark:text-green-400';
    if (diff === '0' || diff === 0) return 'text-gray-500';
    return 'text-red-600 dark:text-red-400';
};

// Format games diff for display
const formatGamesDiff = (diff: string | number): string => {
    const numDiff = typeof diff === 'string' ? parseInt(diff) : diff;
    if (numDiff > 0) return `+${numDiff}`;
    return String(numDiff);
};

onMounted(() => {
    fetchStandings();
});
</script>

<template>
    <Card class="shadow-lg">
        <CardHeader class="bg-gray-50 dark:bg-gray-700/50">
            <CardTitle class="flex items-center justify-between">
                <span>{{ t('Round Robin Standings') }}</span>
                <span v-if="!loading && progress.total > 0"
                      class="text-sm font-normal text-gray-600 dark:text-gray-400">
                    {{ progress.completed }}/{{ progress.total }} {{ t('matches completed') }}
                </span>
            </CardTitle>
            <CardDescription v-if="!loading && progress.percentage < 100">
                {{ t('Tournament in progress') }}
            </CardDescription>
        </CardHeader>

        <CardContent class="p-6">
            <!-- Progress Bar -->
            <div v-if="!loading && progress.total > 0 && progress.percentage < 100" class="mb-6">
                <div class="flex justify-between text-sm text-gray-600 dark:text-gray-400 mb-1">
                    <span>{{ t('Progress') }}</span>
                    <span>{{ progress.percentage }}%</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2 dark:bg-gray-700">
                    <div
                        class="bg-indigo-600 h-2 rounded-full transition-all duration-300"
                        :style="`width: ${progress.percentage}%`"
                    ></div>
                </div>
            </div>

            <!-- Loading state -->
            <div v-if="loading" class="flex justify-center py-8">
                <div class="text-center">
                    <Spinner class="h-8 w-8 text-indigo-600 mx-auto mb-2"/>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ t('Loading standings...') }}</p>
                </div>
            </div>

            <!-- Error state -->
            <div v-else-if="error" class="text-center py-8">
                <p class="text-red-600 dark:text-red-400">{{ error }}</p>
            </div>

            <!-- Empty state -->
            <div v-else-if="standings.length === 0" class="text-center py-8 text-gray-500 dark:text-gray-400">
                {{ t('No matches played yet') }}
            </div>

            <!-- Standings Table - Desktop -->
            <div v-else class="hidden sm:block overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead>
                    <tr>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            {{ t('Pos') }}
                        </th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            {{ t('Player') }}
                        </th>
                        <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                            {{ t('Played') }}
                        </th>
                        <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                            {{ t('W') }}
                        </th>
                        <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                            {{ t('L') }}
                        </th>
                        <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                            {{ t('Diff') }}
                        </th>
                        <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                            {{ t('Win %') }}
                        </th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    <tr
                        v-for="(standing) in standings"
                        :key="standing.player.id"
                        :class="getRowClass(standing.position)"
                    >
                        <td class="px-3 py-4 whitespace-nowrap">
                                <span :class="[
                                    'inline-flex items-center justify-center h-8 w-8 rounded-full text-sm font-medium',
                                    getPositionBadgeClass(standing.position)
                                ]">
                                    {{ standing.position }}
                                </span>
                        </td>
                        <td class="px-3 py-4 whitespace-nowrap">
                            <div class="flex items-center gap-3">
                                <UserAvatar
                                    :user="standing.player"
                                    size="sm"
                                    priority="tournament_picture"
                                    :exclusive-priority="true"
                                />
                                <div>
                                    <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                        {{ standing.player.firstname }} {{ standing.player.lastname }}
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="px-3 py-4 whitespace-nowrap text-sm text-center text-gray-500">
                            {{ standing.matches_played }}
                        </td>
                        <td class="px-3 py-4 whitespace-nowrap text-sm text-center font-medium text-green-600 dark:text-green-400">
                            {{ standing.wins }}
                        </td>
                        <td class="px-3 py-4 whitespace-nowrap text-sm text-center font-medium text-red-600 dark:text-red-400">
                            {{ standing.losses }}
                        </td>
                        <td class="px-3 py-4 whitespace-nowrap text-sm text-center font-medium"
                            :class="getGamesDiffClass(standing.games_diff)"
                        >
                            {{ formatGamesDiff(standing.games_diff) }}
                        </td>
                        <td class="px-3 py-4 whitespace-nowrap text-sm text-center text-gray-500">
                            {{ standing.win_rate }}
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>

            <!-- Standings Cards - Mobile -->
            <div class="sm:hidden space-y-3">
                <div
                    v-for="standing in standings"
                    :key="standing.player.id"
                    :class="[
                        'p-4 rounded-lg border',
                        getRowClass(standing.position),
                        'dark:border-gray-700'
                    ]"
                >
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-3">
                            <span :class="[
                                'inline-flex items-center justify-center h-10 w-10 rounded-full text-base font-bold',
                                getPositionBadgeClass(standing.position)
                            ]">
                                {{ standing.position }}
                            </span>
                            <UserAvatar
                                :user="standing.player"
                                size="md"
                                priority="tournament_picture"
                                :exclusive-priority="true"
                            />
                            <div>
                                <h3 class="font-medium text-gray-900 dark:text-gray-100">
                                    {{ standing.player.firstname }} {{ standing.player.lastname }}
                                </h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ standing.matches_played }} {{ t('matches') }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-4 gap-2 text-center">
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ t('Wins') }}</p>
                            <p class="text-lg font-bold text-green-600 dark:text-green-400">{{ standing.wins }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ t('Losses') }}</p>
                            <p class="text-lg font-bold text-red-600 dark:text-red-400">{{ standing.losses }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ t('Diff') }}</p>
                            <p class="text-lg font-bold" :class="getGamesDiffClass(standing.games_diff)">
                                {{ formatGamesDiff(standing.games_diff) }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ t('Win %') }}</p>
                            <p class="text-lg font-bold text-gray-600 dark:text-gray-400">{{ standing.win_rate }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
