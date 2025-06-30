<script lang="ts" setup>
import {computed, onMounted, ref} from 'vue';
import {Card, CardContent, CardDescription, CardHeader, CardTitle, Spinner} from '@/Components/ui';
import {useLocale} from '@/composables/useLocale';
import {apiClient} from '@/lib/apiClient';
import {
    ArcElement,
    BarElement,
    CategoryScale,
    Chart as ChartJS,
    Filler,
    Legend,
    LinearScale,
    LineElement,
    PointElement,
    RadialLinearScale,
    Title,
    Tooltip
} from 'chart.js';
import {Bar, Doughnut, Line, Radar} from 'vue-chartjs';
import {AwardIcon, ChartBarIcon, TrendingDownIcon, TrendingUpIcon,} from 'lucide-vue-next';

// Register Chart.js components
ChartJS.register(
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    BarElement,
    ArcElement, // Add this
    RadialLinearScale,
    Title,
    Tooltip,
    Legend,
    Filler
);

interface Props {
    playerId: number;
}

const props = defineProps<Props>();
const {t} = useLocale();

// State
const isLoading = ref(true);
const stats = ref<any>(null);
const error = ref<string | null>(null);

// Chart options
const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: 'top' as const,
        },
        tooltip: {
            mode: 'index' as const,
            intersect: false,
        },
    },
};

// Computed chart data
const frameProgressionData = computed(() => {
    if (!stats.value?.frame_progression) return null;

    return {
        labels: stats.value.frame_progression.map((f: any) => t('Frame :n', {n: f.frame})),
        datasets: [
            {
                label: t('Win Rate %'),
                data: stats.value.frame_progression.map((f: any) => f.win_rate),
                borderColor: 'rgb(59, 130, 246)',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                tension: 0.1,
                fill: true,
            }
        ]
    };
});

const performanceTrendsData = computed(() => {
    if (!stats.value?.performance_trends) return null;

    return {
        labels: stats.value.performance_trends.map((t: any) => t.month),
        datasets: [
            {
                label: t('Match Win Rate %'),
                data: stats.value.performance_trends.map((t: any) => t.win_rate),
                borderColor: 'rgb(34, 197, 94)',
                backgroundColor: 'rgba(34, 197, 94, 0.1)',
                tension: 0.1,
                fill: true,
                yAxisID: 'y',
            },
            {
                label: t('Frame Win Rate %'),
                data: stats.value.performance_trends.map((t: any) => t.frame_win_rate),
                borderColor: 'rgb(168, 85, 247)',
                backgroundColor: 'rgba(168, 85, 247, 0.1)',
                tension: 0.1,
                fill: true,
                yAxisID: 'y',
            },
            {
                label: t('Matches Played'),
                data: stats.value.performance_trends.map((t: any) => t.matches),
                type: 'bar',
                backgroundColor: 'rgba(156, 163, 175, 0.5)',
                yAxisID: 'y1',
            }
        ]
    };
});

const performanceTrendsOptions = {
    ...chartOptions,
    scales: {
        y: {
            type: 'linear' as const,
            display: true,
            position: 'left' as const,
            min: 0,
            max: 100,
            title: {
                display: true,
                text: t('Win Rate %')
            }
        },
        y1: {
            type: 'linear' as const,
            display: true,
            position: 'right' as const,
            grid: {
                drawOnChartArea: false,
            },
            title: {
                display: true,
                text: t('Matches')
            }
        }
    }
};

const stagePerformanceData = computed(() => {
    if (!stats.value?.performance_by_stage) return null;

    const stages = stats.value.performance_by_stage;
    return {
        labels: stages.map((s: any) => translateStage(s.stage)),
        datasets: [
            {
                label: t('Match Win Rate %'),
                data: stages.map((s: any) => s.win_rate),
                backgroundColor: 'rgba(59, 130, 246, 0.8)',
            },
            {
                label: t('Frame Win Rate %'),
                data: stages.map((s: any) => s.frame_win_rate),
                backgroundColor: 'rgba(168, 85, 247, 0.8)',
            }
        ]
    };
});

const gameTypeStatsData = computed(() => {
    if (!stats.value?.game_type_stats) return null;

    const gameTypes = stats.value.game_type_stats;
    return {
        labels: gameTypes.map((g: any) => g.game_type),
        datasets: [{
            label: t('Win Rate by Game Type'),
            data: gameTypes.map((g: any) => g.win_rate),
            backgroundColor: [
                'rgba(239, 68, 68, 0.8)',
                'rgba(59, 130, 246, 0.8)',
                'rgba(34, 197, 94, 0.8)',
                'rgba(168, 85, 247, 0.8)',
                'rgba(245, 158, 11, 0.8)',
                'rgba(236, 72, 153, 0.8)',
            ],
            borderColor: [
                'rgb(239, 68, 68)',
                'rgb(59, 130, 246)',
                'rgb(34, 197, 94)',
                'rgb(168, 85, 247)',
                'rgb(245, 158, 11)',
                'rgb(236, 72, 153)',
            ],
            borderWidth: 1,
        }]
    };
});

const roundPerformanceData = computed(() => {
    if (!stats.value?.performance_by_round) return null;

    const rounds = stats.value.performance_by_round.filter((r: any) => r.round !== 'unknown');
    return {
        labels: rounds.map((r: any) => translateRound(r.round)),
        datasets: [{
            label: t('Win Rate %'),
            data: rounds.map((r: any) => r.win_rate),
            backgroundColor: 'rgba(59, 130, 246, 0.2)',
            borderColor: 'rgb(59, 130, 246)',
            pointBackgroundColor: 'rgb(59, 130, 246)',
            pointBorderColor: '#fff',
            pointHoverBackgroundColor: '#fff',
            pointHoverBorderColor: 'rgb(59, 130, 246)'
        }]
    };
});

// Helper functions
const translateStage = (stage: string): string => {
    const stageNames: Record<string, string> = {
        'bracket': t('Bracket'),
        'group': t('Group Stage'),
        'third_place': t('3rd Place'),
        'lower_bracket': t('Lower Bracket')
    };
    return stageNames[stage] || stage;
};

const translateRound = (round: string): string => {
    const roundNames: Record<string, string> = {
        'finals': t('Finals'),
        'semifinals': t('Semifinals'),
        'quarterfinals': t('Quarterfinals'),
        'round_16': t('R16'),
        'round_32': t('R32'),
        'round_64': t('R64'),
        'round_128': t('R128'),
        'third_place': t('3rd Place'),
        'grand_finals': t('Grand Finals')
    };
    return roundNames[round] || round;
};

const formatWinRate = (rate: number): string => {
    return `${rate.toFixed(1)}%`;
};

// Fetch data
const fetchStats = async () => {
    isLoading.value = true;
    error.value = null;

    try {
        stats.value = await apiClient(`/api/players/${props.playerId}/match-stats`);
    } catch (err: any) {
        error.value = err.message || t('Failed to load match statistics');
    } finally {
        isLoading.value = false;
    }
};

onMounted(() => {
    fetchStats();
});
</script>

<template>
    <div class="space-y-6">
        <!-- Loading State -->
        <div v-if="isLoading" class="p-10 text-center">
            <Spinner class="text-primary mx-auto h-8 w-8"/>
            <p class="mt-2 text-gray-500">{{ t('Loading detailed statistics...') }}</p>
        </div>

        <!-- Error State -->
        <div v-else-if="error" class="mb-6 rounded bg-red-100 p-4 text-red-500">
            {{ error }}
        </div>

        <!-- Stats Content -->
        <template v-else-if="stats">
            <!-- Overall Statistics -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <ChartBarIcon class="h-5 w-5"/>
                        {{ t('Overall Match Statistics') }}
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div class="text-center p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                            <p class="text-3xl font-bold text-blue-600 dark:text-blue-400">
                                {{ stats.overall_stats.win_rate }}%
                            </p>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                {{ t('Match Win Rate') }}
                            </p>
                            <p class="text-xs text-gray-500 mt-1">
                                {{ stats.overall_stats.wins }}/{{ stats.overall_stats.total_matches }}
                            </p>
                        </div>

                        <div class="text-center p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                            <p class="text-3xl font-bold text-purple-600 dark:text-purple-400">
                                {{ stats.overall_stats.frame_win_rate }}%
                            </p>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                {{ t('Frame Win Rate') }}
                            </p>
                            <p class="text-xs text-gray-500 mt-1">
                                {{
                                    stats.overall_stats.total_frames_won
                                }}/{{ stats.overall_stats.total_frames_won + stats.overall_stats.total_frames_lost }}
                            </p>
                        </div>

                        <div class="text-center p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                            <p class="text-3xl font-bold"
                               :class="stats.overall_stats.avg_score_margin > 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'">
                                {{
                                    stats.overall_stats.avg_score_margin > 0 ? '+' : ''
                                }}{{ stats.overall_stats.avg_score_margin.toFixed(1) }}
                            </p>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                {{ t('Avg Score Margin') }}
                            </p>
                        </div>

                        <div class="text-center p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                            <p class="text-3xl font-bold text-orange-600 dark:text-orange-400">
                                {{ stats.comeback_stats.total_comebacks }}
                            </p>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                {{ t('Comeback Wins') }}
                            </p>
                            <p class="text-xs text-gray-500 mt-1">
                                {{ t('Max deficit: :n', {n: stats.comeback_stats.biggest_comeback_deficit}) }}
                            </p>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Frame Progression -->
            <Card v-if="frameProgressionData">
                <CardHeader>
                    <CardTitle>{{ t('Win Rate by Frame Progression') }}</CardTitle>
                    <CardDescription>
                        {{ t('How win rate changes as matches progress') }}
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="h-80">
                        <Line :data="frameProgressionData" :options="chartOptions"/>
                    </div>
                </CardContent>
            </Card>

            <!-- Performance Trends -->
            <Card v-if="performanceTrendsData">
                <CardHeader>
                    <CardTitle>{{ t('Performance Trends') }}</CardTitle>
                    <CardDescription>
                        {{ t('Win rates and activity over time') }}
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="h-80">
                        <Line :data="performanceTrendsData" :options="performanceTrendsOptions"/>
                    </div>
                </CardContent>
            </Card>

            <!-- Performance by Stage and Round -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Stage Performance -->
                <Card v-if="stagePerformanceData">
                    <CardHeader>
                        <CardTitle>{{ t('Performance by Stage') }}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="h-64">
                            <Bar :data="stagePerformanceData" :options="chartOptions"/>
                        </div>
                    </CardContent>
                </Card>

                <!-- Round Performance -->
                <Card v-if="roundPerformanceData">
                    <CardHeader>
                        <CardTitle>{{ t('Performance by Round') }}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="h-64">
                            <Radar :data="roundPerformanceData" :options="chartOptions"/>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Game Type Statistics -->
            <Card v-if="gameTypeStatsData">
                <CardHeader>
                    <CardTitle>{{ t('Performance by Game Type') }}</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div class="h-64">
                            <Doughnut :data="gameTypeStatsData" :options="chartOptions"/>
                        </div>
                        <div class="space-y-3">
                            <div
                                v-for="game in stats.game_type_stats"
                                :key="game.game_type"
                                class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-lg"
                            >
                                <div>
                                    <p class="font-medium">{{ game.game_type }}</p>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">
                                        {{ game.matches }} {{ t('matches') }} • {{ game.tournaments_played }}
                                        {{ t('tournaments') }}
                                    </p>
                                </div>
                                <div class="text-right">
                                    <p class="text-lg font-bold"
                                       :class="game.win_rate >= 50 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'">
                                        {{ formatWinRate(game.win_rate) }}
                                    </p>
                                    <p class="text-xs text-gray-600 dark:text-gray-400">
                                        {{ t('Frame') }}: {{ formatWinRate(game.frame_win_rate) }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Best and Worst Opponents -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Best Opponents -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <TrendingUpIcon class="h-5 w-5 text-green-600"/>
                            {{ t('Best Opponents') }}
                        </CardTitle>
                        <CardDescription>
                            {{ t('Opponents with highest win rate against (min 3 matches)') }}
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-3">
                            <div
                                v-for="(opponent, index) in stats.best_opponents.slice(0, 5)"
                                :key="opponent.opponent_id"
                                class="flex items-center justify-between p-3 rounded-lg"
                                :class="index === 0 ? 'bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800' : 'bg-gray-50 dark:bg-gray-800'"
                            >
                                <div class="flex items-center gap-3">
                                    <div
                                        class="flex-shrink-0 w-8 h-8 flex items-center justify-center rounded-full bg-green-100 dark:bg-green-900 text-green-700 dark:text-green-300 font-bold text-sm">
                                        {{ index + 1 }}
                                    </div>
                                    <div>
                                        <p class="font-medium">{{ opponent.opponent_name }}</p>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">
                                            {{ opponent.wins }}-{{ opponent.losses }} ({{ opponent.matches }}
                                            {{ t('matches') }})
                                        </p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="text-lg font-bold text-green-600 dark:text-green-400">
                                        {{ formatWinRate(opponent.win_rate) }}
                                    </p>
                                    <p class="text-xs text-gray-600 dark:text-gray-400">
                                        {{ t('Avg') }} {{
                                            opponent.avg_score_margin > 0 ? '+' : ''
                                        }}{{ opponent.avg_score_margin.toFixed(1) }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Worst Opponents -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <TrendingDownIcon class="h-5 w-5 text-red-600"/>
                            {{ t('Toughest Opponents') }}
                        </CardTitle>
                        <CardDescription>
                            {{ t('Opponents with lowest win rate against (min 3 matches)') }}
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-3">
                            <div
                                v-for="(opponent, index) in stats.worst_opponents.slice(0, 5)"
                                :key="opponent.opponent_id"
                                class="flex items-center justify-between p-3 rounded-lg"
                                :class="index === 0 ? 'bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800' : 'bg-gray-50 dark:bg-gray-800'"
                            >
                                <div class="flex items-center gap-3">
                                    <div
                                        class="flex-shrink-0 w-8 h-8 flex items-center justify-center rounded-full bg-red-100 dark:bg-red-900 text-red-700 dark:text-red-300 font-bold text-sm">
                                        {{ index + 1 }}
                                    </div>
                                    <div>
                                        <p class="font-medium">{{ opponent.opponent_name }}</p>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">
                                            {{ opponent.wins }}-{{ opponent.losses }} ({{ opponent.matches }}
                                            {{ t('matches') }})
                                        </p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="text-lg font-bold text-red-600 dark:text-red-400">
                                        {{ formatWinRate(opponent.win_rate) }}
                                    </p>
                                    <p class="text-xs text-gray-600 dark:text-gray-400">
                                        {{ t('Avg') }} {{
                                            opponent.avg_score_margin > 0 ? '+' : ''
                                        }}{{ opponent.avg_score_margin.toFixed(1) }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Score Distribution -->
            <Card v-if="stats.score_distribution && stats.score_distribution.length > 0">
                <CardHeader>
                    <CardTitle>{{ t('Most Common Score Lines') }}</CardTitle>
                    <CardDescription>
                        {{ t('Frequency of different match scores') }}
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                        <div
                            v-for="score in stats.score_distribution.slice(0, 12)"
                            :key="`${score.player_score}-${score.opponent_score}`"
                            class="text-center p-3 rounded-lg"
                            :class="score.won ? 'bg-green-50 dark:bg-green-900/20' : 'bg-red-50 dark:bg-red-900/20'"
                        >
                            <p class="font-bold text-lg">
                                {{ score.player_score }}-{{ score.opponent_score }}
                            </p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                {{ score.count }} {{ t('times') }}
                            </p>
                            <p class="text-xs text-gray-500 mt-1">
                                {{ t('Race to') }} {{ score.races_to }}
                            </p>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Comeback Details -->
            <Card v-if="stats.comeback_stats.recent_comebacks.length > 0">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <AwardIcon class="h-5 w-5"/>
                        {{ t('Notable Comebacks') }}
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="space-y-3">
                        <div
                            v-for="comeback in stats.comeback_stats.recent_comebacks"
                            :key="comeback.match_id"
                            class="p-3 bg-orange-50 dark:bg-orange-900/20 rounded-lg"
                        >
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="font-medium">{{ comeback.tournament }}</p>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">
                                        vs {{ comeback.opponent }} • {{ comeback.score }}
                                    </p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-medium text-orange-600 dark:text-orange-400">
                                        {{ t('Deficit: :n', {n: comeback.estimated_deficit}) }}
                                    </p>
                                    <p class="text-xs text-gray-600 dark:text-gray-400">
                                        {{ new Date(comeback.date).toLocaleDateString() }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </template>
    </div>
</template>
