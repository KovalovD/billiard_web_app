<!-- resources/js/Components/Tournament/FinalStandings.vue -->
<script lang="ts" setup>
import {computed} from 'vue';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
    DataTable
} from '@/Components/ui';
import {useLocale} from '@/composables/useLocale';
import {
    TrophyIcon,
    MedalIcon,
    StarIcon,
    TrendingUpIcon,
    DollarSignIcon
} from 'lucide-vue-next';
import type {Tournament} from '@/types/api';

interface PlayerStanding {
    position: number;
    player: {
        id: number;
        firstname: string;
        lastname: string;
        rating?: number;
    };
    rating_points: number;
    prize_amount: number;
    bonus_amount: number;
    achievement_amount: number;
    total_amount: number;
    statistics: {
        matches_played: number;
        matches_won: number;
        frames_won: number;
        frames_lost: number;
        frame_percentage: number;
        highest_break?: number;
    };
}

interface Props {
    standings: PlayerStanding[];
    tournament: Tournament | null;
}

const props = defineProps<Props>();

const {t} = useLocale();

// Computed
const podiumPlayers = computed(() => props.standings.slice(0, 3));
const otherPlayers = computed(() => props.standings.slice(3));

const columns = computed(() => [
    {
        key: 'position',
        label: t('Position'),
        align: 'center' as const,
        render: (standing: PlayerStanding) => ({
            position: standing.position,
            isPodium: standing.position <= 3
        })
    },
    {
        key: 'player',
        label: t('Player'),
        align: 'left' as const,
        render: (standing: PlayerStanding) => ({
            name: `${standing.player.firstname} ${standing.player.lastname}`,
            rating: standing.player.rating,
            isWinner: standing.position === 1
        })
    },
    {
        key: 'performance',
        label: t('Performance'),
        align: 'center' as const,
        render: (standing: PlayerStanding) => ({
            wins: standing.statistics.matches_won,
            total: standing.statistics.matches_played,
            winRate: Math.round((standing.statistics.matches_won / standing.statistics.matches_played) * 100)
        })
    },
    {
        key: 'frames',
        label: t('Frames'),
        align: 'center' as const,
        render: (standing: PlayerStanding) => ({
            won: standing.statistics.frames_won,
            lost: standing.statistics.frames_lost,
            percentage: standing.statistics.frame_percentage
        })
    },
    {
        key: 'rating',
        label: t('Rating Points'),
        align: 'center' as const,
        render: (standing: PlayerStanding) => ({
            points: standing.rating_points
        })
    },
    {
        key: 'earnings',
        label: t('Total Earnings'),
        align: 'right' as const,
        render: (standing: PlayerStanding) => ({
            total: standing.total_amount,
            prize: standing.prize_amount,
            bonus: standing.bonus_amount,
            achievement: standing.achievement_amount
        })
    }
]);

// Methods
const getPositionIcon = (position: number) => {
    switch (position) {
        case 1:
            return TrophyIcon;
        case 2:
        case 3:
            return MedalIcon;
        default:
            return null;
    }
};

const getPositionClass = (position: number): string => {
    switch (position) {
        case 1:
            return 'bg-gradient-to-r from-yellow-400 to-yellow-600 text-white';
        case 2:
            return 'bg-gradient-to-r from-gray-300 to-gray-500 text-white';
        case 3:
            return 'bg-gradient-to-r from-orange-400 to-orange-600 text-white';
        default:
            return 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300';
    }
};

const getPodiumHeight = (position: number): string => {
    switch (position) {
        case 1:
            return 'h-32';
        case 2:
            return 'h-24';
        case 3:
            return 'h-20';
        default:
            return 'h-16';
    }
};

const formatCurrency = (amount: number): string => {
    return amount.toLocaleString('uk-UA', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }) + '₴';
};
</script>

<template>
    <div class="space-y-8">
        <!-- Podium Display -->
        <Card class="overflow-hidden">
            <CardHeader class="text-center">
                <CardTitle class="flex items-center justify-center gap-2">
                    <TrophyIcon class="h-6 w-6 text-yellow-600"/>
                    {{ t('Tournament Champions') }}
                </CardTitle>
            </CardHeader>
            <CardContent>
                <div class="flex justify-center items-end space-x-8 py-8">
                    <!-- 2nd Place -->
                    <div v-if="podiumPlayers[1]" class="text-center">
                        <div class="mb-4">
                            <div
                                class="w-16 h-16 mx-auto bg-gray-300 rounded-full flex items-center justify-center mb-2">
                                <span class="text-xl font-bold text-white">2</span>
                            </div>
                            <div class="font-medium">{{ podiumPlayers[1].player.firstname }}
                                {{ podiumPlayers[1].player.lastname }}
                            </div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">
                                {{ formatCurrency(podiumPlayers[1].total_amount) }}
                            </div>
                        </div>
                        <div :class="['bg-gray-300 w-24 h-24 rounded-t-lg flex items-end justify-center pb-2']">
                            <MedalIcon class="h-8 w-8 text-white"/>
                        </div>
                    </div>

                    <!-- 1st Place (Winner) -->
                    <div v-if="podiumPlayers[0]" class="text-center">
                        <div class="mb-4">
                            <div
                                class="w-20 h-20 mx-auto bg-gradient-to-r from-yellow-400 to-yellow-600 rounded-full flex items-center justify-center mb-2 ring-4 ring-yellow-200">
                                <TrophyIcon class="h-10 w-10 text-white"/>
                            </div>
                            <div class="font-bold text-lg">{{ podiumPlayers[0].player.firstname }}
                                {{ podiumPlayers[0].player.lastname }}
                            </div>
                            <div class="text-sm text-yellow-600 font-medium">🏆 {{ t('Champion') }}</div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">
                                {{ formatCurrency(podiumPlayers[0].total_amount) }}
                            </div>
                        </div>
                        <div
                            :class="['bg-gradient-to-t from-yellow-400 to-yellow-600 w-24 h-32 rounded-t-lg flex items-end justify-center pb-2']">
                            <span class="text-2xl font-bold text-white">1</span>
                        </div>
                    </div>

                    <!-- 3rd Place -->
                    <div v-if="podiumPlayers[2]" class="text-center">
                        <div class="mb-4">
                            <div
                                class="w-16 h-16 mx-auto bg-orange-500 rounded-full flex items-center justify-center mb-2">
                                <span class="text-xl font-bold text-white">3</span>
                            </div>
                            <div class="font-medium">{{ podiumPlayers[2].player.firstname }}
                                {{ podiumPlayers[2].player.lastname }}
                            </div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">
                                {{ formatCurrency(podiumPlayers[2].total_amount) }}
                            </div>
                        </div>
                        <div :class="['bg-orange-500 w-24 h-20 rounded-t-lg flex items-end justify-center pb-2']">
                            <MedalIcon class="h-6 w-6 text-white"/>
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Detailed Results Table -->
        <Card>
            <CardHeader>
                <CardTitle>{{ t('Complete Standings') }}</CardTitle>
            </CardHeader>
            <CardContent class="p-0">
                <DataTable
                    :columns="columns"
                    :compact-mode="true"
                    :data="standings"
                    :empty-message="t('No results available')"
                >
                    <!-- Position Column -->
                    <template #cell-position="{ value }">
                        <div class="flex items-center justify-center">
              <span
                  :class="[
                  'inline-flex items-center justify-center w-8 h-8 rounded-full text-sm font-bold',
                  getPositionClass(value.position)
                ]"
              >
                {{ value.position }}
              </span>
                            <component
                                :is="getPositionIcon(value.position)"
                                v-if="getPositionIcon(value.position)"
                                class="ml-2 h-4 w-4 text-yellow-600"
                            />
                        </div>
                    </template>

                    <!-- Player Column -->
                    <template #cell-player="{ value }">
                        <div class="flex items-center space-x-3">
                            <div>
                                <div
                                    :class="['font-medium', value.isWinner ? 'text-yellow-600 dark:text-yellow-400' : '']">
                                    {{ value.name }}
                                    <TrophyIcon v-if="value.isWinner" class="inline h-4 w-4 ml-1"/>
                                </div>
                                <div v-if="value.rating" class="text-sm text-gray-500 flex items-center">
                                    <StarIcon class="h-3 w-3 mr-1"/>
                                    {{ t('Rating') }}: {{ value.rating }}
                                </div>
                            </div>
                        </div>
                    </template>

                    <!-- Performance Column -->
                    <template #cell-performance="{ value }">
                        <div class="text-center">
                            <div class="font-medium">{{ value.wins }}/{{ value.total }}</div>
                            <div class="text-sm text-gray-500">{{ value.winRate }}% {{ t('wins') }}</div>
                        </div>
                    </template>

                    <!-- Frames Column -->
                    <template #cell-frames="{ value }">
                        <div class="text-center">
                            <div class="font-medium">{{ value.won }}-{{ value.lost }}</div>
                            <div class="text-sm text-gray-500">{{ value.percentage }}%</div>
                        </div>
                    </template>

                    <!-- Rating Points Column -->
                    <template #cell-rating="{ value }">
                        <div class="text-center">
              <span
                  v-if="value.points > 0"
                  class="inline-flex items-center px-2 py-1 text-sm font-medium text-green-800 bg-green-100 rounded-full dark:bg-green-900/30 dark:text-green-300"
              >
                <TrendingUpIcon class="h-3 w-3 mr-1"/>
                +{{ value.points }}
              </span>
                            <span v-else class="text-gray-400">—</span>
                        </div>
                    </template>

                    <!-- Earnings Column -->
                    <template #cell-earnings="{ value }">
                        <div class="text-right">
                            <div class="font-bold text-green-600 dark:text-green-400">
                                {{ formatCurrency(value.total) }}
                            </div>
                            <div class="text-xs text-gray-500 space-y-1">
                                <div v-if="value.prize > 0">{{ t('Prize') }}: {{ formatCurrency(value.prize) }}</div>
                                <div v-if="value.bonus > 0">{{ t('Bonus') }}: {{ formatCurrency(value.bonus) }}</div>
                                <div v-if="value.achievement > 0">{{ t('Achievement') }}:
                                    {{ formatCurrency(value.achievement) }}
                                </div>
                            </div>
                        </div>
                    </template>
                </DataTable>
            </CardContent>
        </Card>

        <!-- Tournament Summary -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <Card>
                <CardContent class="p-6 text-center">
                    <TrophyIcon class="mx-auto h-12 w-12 text-yellow-600 mb-4"/>
                    <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                        {{ podiumPlayers[0]?.player.firstname }} {{ podiumPlayers[0]?.player.lastname }}
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">{{ t('Tournament Champion') }}</div>
                    <div class="mt-2 text-lg font-bold text-yellow-600">
                        {{ formatCurrency(podiumPlayers[0]?.total_amount || 0) }}
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardContent class="p-6 text-center">
                    <DollarSignIcon class="mx-auto h-12 w-12 text-green-600 mb-4"/>
                    <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                        {{ formatCurrency(standings.reduce((sum, s) => sum + s.total_amount, 0)) }}
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">{{ t('Total Prize Money') }}</div>
                    <div class="mt-2 text-sm text-gray-500">
                        {{
                            t('Distributed among :count players', {count: standings.filter(s => s.total_amount > 0).length})
                        }}
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardContent class="p-6 text-center">
                    <StarIcon class="mx-auto h-12 w-12 text-purple-600 mb-4"/>
                    <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                        {{ standings.reduce((sum, s) => sum + s.rating_points, 0) }}
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">{{ t('Total Rating Points') }}</div>
                    <div class="mt-2 text-sm text-gray-500">
                        {{ t('Awarded to participants') }}
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
