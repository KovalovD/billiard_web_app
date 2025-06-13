<!-- resources/js/Pages/Tournaments/Results.vue -->
<script lang="ts" setup>
import {ref, computed, onMounted} from 'vue';
import {Head, router} from '@inertiajs/vue3';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import {
    Button,
    Card,
    CardContent,
    CardHeader,
    CardTitle,
    Modal
} from '@/Components/ui';
import {useTournamentStore} from '@/stores/tournament';
import {useLocale} from '@/composables/useLocale';
import ResultsEntry from '@/Components/Tournament/ResultsEntry.vue';
import TournamentStatistics from '@/Components/Tournament/TournamentStatistics.vue';
import FinalStandings from '@/Components/Tournament/FinalStandings.vue';
import PrizeDistribution from '@/Components/Tournament/PrizeDistribution.vue';
import {
    ArrowLeftIcon,
    TrophyIcon,
    BarChartIcon,
    DollarSignIcon,
    MedalIcon,
    PrinterIcon,
    ShareIcon
} from 'lucide-vue-next';
import tournamentService from '@/services/TournamentService';

defineOptions({layout: AuthenticatedLayout});

interface Props {
    tournamentId: number;
}

const props = defineProps<Props>();

const {t} = useLocale();
const tournamentStore = useTournamentStore();

// State
const activeTab = ref<'standings' | 'statistics' | 'prizes' | 'entry'>('standings');
const showResultsModal = ref(false);
const tournamentResults = ref<any>(null);
const isLoading = ref(true);

// Computed
const tournament = computed(() => tournamentStore.currentTournament);
const players = computed(() => tournamentStore.confirmedPlayers);
const matches = computed(() => tournamentStore.completedMatches);

const tournamentStats = computed(() => {
    if (!tournamentResults.value) return null;

    return {
        totalMatches: matches.value.length,
        totalFrames: matches.value.reduce((sum, match) => {
            return sum + (match.frames?.length || 0);
        }, 0),
        averageMatchDuration: '45 minutes',
        longestMatch: {
            duration: '1h 23m',
            players: ['John Doe', 'Jane Smith']
        },
        highestBreak: {
            points: 147,
            player: 'Mike Johnson',
            match: 'Final'
        },
        mostFramesWon: {
            count: 12,
            player: 'Sarah Connor'
        }
    };
});

const finalStandings = computed(() => {
    if (!tournamentResults.value) return [];

    // Mock final standings - would come from API
    return [
        {
            position: 1,
            player: {id: 1, firstname: 'John', lastname: 'Doe', rating: 1850},
            rating_points: 50,
            prize_amount: 1000,
            bonus_amount: 100,
            achievement_amount: 250,
            total_amount: 1350,
            statistics: {
                matches_played: 5,
                matches_won: 5,
                frames_won: 15,
                frames_lost: 3,
                frame_percentage: 83.3,
                highest_break: 127
            }
        },
        {
            position: 2,
            player: {id: 2, firstname: 'Jane', lastname: 'Smith', rating: 1720},
            rating_points: 35,
            prize_amount: 600,
            bonus_amount: 50,
            achievement_amount: 150,
            total_amount: 800,
            statistics: {
                matches_played: 5,
                matches_won: 4,
                frames_won: 12,
                frames_lost: 8,
                frame_percentage: 60.0,
                highest_break: 89
            }
        },
        {
            position: 3,
            player: {id: 3, firstname: 'Mike', lastname: 'Johnson', rating: 1950},
            rating_points: 25,
            prize_amount: 400,
            bonus_amount: 0,
            achievement_amount: 100,
            total_amount: 500,
            statistics: {
                matches_played: 4,
                matches_won: 3,
                frames_won: 10,
                frames_lost: 6,
                frame_percentage: 62.5,
                highest_break: 147
            }
        }
    ];
});

const prizeDistribution = computed(() => {
    const totalPrizePool = tournament.value?.prize_pool || 0;

    return {
        totalPool: totalPrizePool,
        totalDistributed: finalStandings.value.reduce((sum, s) => sum + s.total_amount, 0),
        breakdown: [
            {position: 1, percentage: 50, amount: totalPrizePool * 0.5},
            {position: 2, percentage: 30, amount: totalPrizePool * 0.3},
            {position: 3, percentage: 20, amount: totalPrizePool * 0.2}
        ]
    };
});

// Methods
const fetchResults = async () => {
    isLoading.value = true;

    try {
        await tournamentStore.fetchTournament(props.tournamentId);
        tournamentResults.value = await tournamentService.getTournamentResults(props.tournamentId);
    } catch (error) {
        console.error('Failed to fetch results:', error);
    } finally {
        isLoading.value = false;
    }
};

const submitMatchResult = async (matchId: number, result: any) => {
    try {
        await tournamentStore.submitMatchResult(props.tournamentId, matchId, result);
        await fetchResults();
    } catch (error) {
        console.error('Failed to submit result:', error);
    }
};

const exportResults = async (format: 'pdf' | 'csv' | 'json') => {
    try {
        // Mock export functionality
        console.log(`Exporting results as ${format}`);

        if (format === 'csv') {
            const csvData = finalStandings.value.map(s => ({
                Position: s.position,
                Player: `${s.player.firstname} ${s.player.lastname}`,
                'Rating Points': s.rating_points,
                'Prize Amount': s.prize_amount,
                'Total Amount': s.total_amount,
                'Matches Won': s.statistics.matches_won,
                'Frames Won': s.statistics.frames_won,
                'Frame %': s.statistics.frame_percentage
            }));

            // Convert to CSV and download
            const csv = [
                Object.keys(csvData[0]).join(','),
                ...csvData.map(row => Object.values(row).join(','))
            ].join('\n');

            const blob = new Blob([csv], {type: 'text/csv'});
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `${tournament.value?.name || 'tournament'}_results.csv`;
            a.click();
            URL.revokeObjectURL(url);
        }
    } catch (error) {
        console.error('Failed to export results:', error);
    }
};

const shareResults = async () => {
    try {
        if (navigator.share) {
            await navigator.share({
                title: `${tournament.value?.name} - Results`,
                text: `Check out the results from ${tournament.value?.name}!`,
                url: window.location.href
            });
        } else {
            // Fallback: copy to clipboard
            await navigator.clipboard.writeText(window.location.href);
            alert(t('Results link copied to clipboard!'));
        }
    } catch (error) {
        console.error('Failed to share results:', error);
    }
};

const goBack = () => {
    router.visit(`/tournaments/${props.tournamentId}`);
};

onMounted(() => {
    fetchResults();
});
</script>

<template>
    <Head :title="t('Tournament Results')"/>

    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-8 flex items-center justify-between">
                <div>
                    <div class="flex items-center space-x-4 mb-2">
                        <Button variant="outline" @click="goBack">
                            <ArrowLeftIcon class="mr-2 h-4 w-4"/>
                            {{ t('Back to Tournament') }}
                        </Button>
                    </div>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">
                        {{ t('Tournament Results') }}
                    </h1>
                    <p class="mt-2 text-gray-600 dark:text-gray-400">
                        {{ tournament?.name }}
                    </p>
                </div>

                <div class="flex items-center space-x-4">
                    <Button variant="outline" @click="shareResults">
                        <ShareIcon class="mr-2 h-4 w-4"/>
                        {{ t('Share') }}
                    </Button>

                    <Button variant="outline" @click="exportResults('pdf')">
                        <PrinterIcon class="mr-2 h-4 w-4"/>
                        {{ t('Export PDF') }}
                    </Button>

                    <Button variant="outline" @click="exportResults('csv')">
                        {{ t('Export CSV') }}
                    </Button>
                </div>
            </div>

            <!-- Tournament Summary -->
            <div v-if="tournament?.status === 'completed'" class="mb-8">
                <Card class="border-green-200 dark:border-green-800 bg-green-50 dark:bg-green-900/20">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2 text-green-700 dark:text-green-300">
                            <TrophyIcon class="h-6 w-6"/>
                            {{ t('Tournament Completed') }}
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                            <div class="text-center">
                                <div class="text-3xl font-bold text-green-600 dark:text-green-400">
                                    {{ finalStandings[0]?.player.firstname }} {{ finalStandings[0]?.player.lastname }}
                                </div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">{{ t('Champion') }}</div>
                            </div>

                            <div class="text-center">
                                <div class="text-3xl font-bold text-blue-600 dark:text-blue-400">
                                    {{ finalStandings.length }}
                                </div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">{{ t('Participants') }}</div>
                            </div>

                            <div class="text-center">
                                <div class="text-3xl font-bold text-purple-600 dark:text-purple-400">
                                    {{ tournamentStats?.totalMatches || 0 }}
                                </div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">{{ t('Matches Played') }}</div>
                            </div>

                            <div class="text-center">
                                <div class="text-3xl font-bold text-yellow-600 dark:text-yellow-400">
                                    {{ prizeDistribution.totalDistributed.toLocaleString() }}₴
                                </div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">{{ t('Prize Money') }}</div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Tab Navigation -->
            <div class="mb-6 border-b border-gray-200 dark:border-gray-700">
                <nav class="-mb-px flex space-x-8">
                    <button
                        :class="[
              'py-4 px-1 text-sm font-medium border-b-2',
              activeTab === 'standings'
                ? 'border-blue-500 text-blue-600 dark:border-blue-400 dark:text-blue-400'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'
            ]"
                        @click="activeTab = 'standings'"
                    >
                        <MedalIcon class="mr-2 h-4 w-4 inline"/>
                        {{ t('Final Standings') }}
                    </button>

                    <button
                        :class="[
              'py-4 px-1 text-sm font-medium border-b-2',
              activeTab === 'statistics'
                ? 'border-blue-500 text-blue-600 dark:border-blue-400 dark:text-blue-400'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'
            ]"
                        @click="activeTab = 'statistics'"
                    >
                        <BarChartIcon class="mr-2 h-4 w-4 inline"/>
                        {{ t('Statistics') }}
                    </button>

                    <button
                        :class="[
              'py-4 px-1 text-sm font-medium border-b-2',
              activeTab === 'prizes'
                ? 'border-blue-500 text-blue-600 dark:border-blue-400 dark:text-blue-400'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'
            ]"
                        @click="activeTab = 'prizes'"
                    >
                        <DollarSignIcon class="mr-2 h-4 w-4 inline"/>
                        {{ t('Prize Distribution') }}
                    </button>

                    <button
                        v-if="tournament?.status !== 'completed'"
                        :class="[
              'py-4 px-1 text-sm font-medium border-b-2',
              activeTab === 'entry'
                ? 'border-blue-500 text-blue-600 dark:border-blue-400 dark:text-blue-400'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'
            ]"
                        @click="activeTab = 'entry'"
                    >
                        <TrophyIcon class="mr-2 h-4 w-4 inline"/>
                        {{ t('Enter Results') }}
                    </button>
                </nav>
            </div>

            <!-- Content -->
            <div>
                <!-- Loading State -->
                <div v-if="isLoading" class="flex justify-center py-12">
                    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
                </div>

                <!-- Final Standings Tab -->
                <div v-else-if="activeTab === 'standings'">
                    <FinalStandings
                        :standings="finalStandings"
                        :tournament="tournament"
                    />
                </div>

                <!-- Statistics Tab -->
                <div v-else-if="activeTab === 'statistics'">
                    <TournamentStatistics
                        :matches="matches"
                        :players="finalStandings"
                        :statistics="tournamentStats"
                    />
                </div>

                <!-- Prize Distribution Tab -->
                <div v-else-if="activeTab === 'prizes'">
                    <PrizeDistribution
                        :distribution="prizeDistribution"
                        :standings="finalStandings"
                        :tournament="tournament"
                    />
                </div>

                <!-- Results Entry Tab -->
                <div v-else-if="activeTab === 'entry'">
                    <ResultsEntry
                        :matches="matches"
                        :tournament="tournament"
                        @result-submitted="submitMatchResult"
                    />
                </div>
            </div>
        </div>
    </div>
</template>
