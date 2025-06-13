<!-- resources/js/Components/Tournament/TournamentStatistics.vue -->
<script lang="ts" setup>
import {computed} from 'vue';
import {Card, CardContent, CardHeader, CardTitle} from '@/Components/ui';
import {useLocale} from '@/composables/useLocale';
import {BarChartIcon, ClockIcon, StarIcon, TrendingUpIcon, TrophyIcon, ZapIcon} from 'lucide-vue-next';

interface TournamentStats {
    totalMatches: number;
    totalFrames: number;
    averageMatchDuration: string;
    longestMatch: {
        duration: string;
        players: string[];
    };
    highestBreak: {
        points: number;
        player: string;
        match: string;
    };
    mostFramesWon: {
        count: number;
        player: string;
    };
}

interface MatchData {
    id: number;
    playerA: any;
    playerB: any;
    scoreA: number;
    scoreB: number;
    frames?: any[];
    duration?: string;
}

interface PlayerData {
    player: {
        firstname: string;
        lastname: string;
    };
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
    statistics: TournamentStats | null;
    matches: MatchData[];
    players: PlayerData[];
}

const props = defineProps<Props>();

const {t} = useLocale();

// Computed statistics
const advancedStats = computed(() => {
    if (!props.matches.length || !props.players.length) return null;

    const totalFrames = props.players.reduce((sum, p) => sum + p.statistics.frames_won + p.statistics.frames_lost, 0) / 2;
    const totalMatches = props.matches.length;

    // Calculate win rates distribution
    const winRates = props.players.map(p =>
        p.statistics.matches_played > 0 ? (p.statistics.matches_won / p.statistics.matches_played) * 100 : 0
    );

    // Frame percentage distribution
    const framePercentages = props.players.map(p => p.statistics.frame_percentage);

    // Most competitive matches (closest scores)
    const competitiveMatches = props.matches
        .filter(m => m.scoreA > 0 && m.scoreB > 0)
        .sort((a, b) => Math.abs(a.scoreA - a.scoreB) - Math.abs(b.scoreA - b.scoreB))
        .slice(0, 3);

    // Dominant performances (largest score differences)
    const dominantMatches = props.matches
        .filter(m => m.scoreA > 0 && m.scoreB > 0)
        .sort((a, b) => Math.abs(b.scoreA - b.scoreB) - Math.abs(a.scoreA - a.scoreB))
        .slice(0, 3);

    return {
        averageWinRate: winRates.reduce((a, b) => a + b, 0) / winRates.length,
        averageFramePercentage: framePercentages.reduce((a, b) => a + b, 0) / framePercentages.length,
        totalFrames,
        totalMatches,
        framesPerMatch: totalFrames / totalMatches,
        competitiveMatches,
        dominantMatches,
        highestWinRate: Math.max(...winRates),
        lowestWinRate: Math.min(...winRates),
        mostFramesInMatch: Math.max(...props.matches.map(m => m.scoreA + m.scoreB)),
        fewestFramesInMatch: Math.min(...props.matches.filter(m => m.scoreA + m.scoreB > 0).map(m => m.scoreA + m.scoreB))
    };
});

const playerRecords = computed(() => {
    if (!props.players.length) return null;

    // Best win rate
    const bestWinRate = props.players.reduce((best, current) => {
        const currentRate = current.statistics.matches_played > 0 ?
            (current.statistics.matches_won / current.statistics.matches_played) * 100 : 0;
        const bestRate = best.statistics.matches_played > 0 ?
            (best.statistics.matches_won / best.statistics.matches_played) * 100 : 0;
        return currentRate > bestRate ? current : best;
    });

    // Best frame percentage
    const bestFramePercentage = props.players.reduce((best, current) =>
        current.statistics.frame_percentage > best.statistics.frame_percentage ? current : best
    );

    // Most frames won
    const mostFrames = props.players.reduce((best, current) =>
        current.statistics.frames_won > best.statistics.frames_won ? current : best
    );

    // Most matches played
    const mostMatches = props.players.reduce((best, current) =>
        current.statistics.matches_played > best.statistics.matches_played ? current : best
    );

    return {
        bestWinRate,
        bestFramePercentage,
        mostFrames,
        mostMatches
    };
});

// Methods
const getPlayerName = (player: any): string => {
    return `${player.firstname} ${player.lastname}`;
};

const getMatchDescription = (match: MatchData): string => {
    return `${getPlayerName(match.playerA)} ${match.scoreA}-${match.scoreB} ${getPlayerName(match.playerB)}`;
};

const formatPercentage = (value: number): string => {
    return `${Math.round(value)}%`;
};
</script>

<template>
    <div class="space-y-8">
        <!-- Overview Statistics -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <Card>
                <CardContent class="p-6 text-center">
                    <BarChartIcon class="mx-auto h-12 w-12 text-blue-600 mb-4"/>
                    <div class="text-3xl font-bold text-gray-900 dark:text-gray-100">
                        {{ statistics?.totalMatches || 0 }}
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">{{ t('Total Matches') }}</div>
                </CardContent>
            </Card>

            <Card>
                <CardContent class="p-6 text-center">
                    <ZapIcon class="mx-auto h-12 w-12 text-green-600 mb-4"/>
                    <div class="text-3xl font-bold text-gray-900 dark:text-gray-100">
                        {{ statistics?.totalFrames || 0 }}
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">{{ t('Total Frames') }}</div>
                </CardContent>
            </Card>

            <Card>
                <CardContent class="p-6 text-center">
                    <ClockIcon class="mx-auto h-12 w-12 text-purple-600 mb-4"/>
                    <div class="text-3xl font-bold text-gray-900 dark:text-gray-100">
                        {{ statistics?.averageMatchDuration || '—' }}
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">{{ t('Avg Match Time') }}</div>
                </CardContent>
            </Card>

            <Card>
                <CardContent class="p-6 text-center">
                    <TrendingUpIcon class="mx-auto h-12 w-12 text-orange-600 mb-4"/>
                    <div class="text-3xl font-bold text-gray-900 dark:text-gray-100">
                        {{ advancedStats ? Math.round(advancedStats.framesPerMatch * 10) / 10 : '—' }}
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">{{ t('Frames per Match') }}</div>
                </CardContent>
            </Card>
        </div>

        <!-- Records and Achievements -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Tournament Records -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <TrophyIcon class="h-5 w-5 text-yellow-600"/>
                        {{ t('Tournament Records') }}
                    </CardTitle>
                </CardHeader>
                <CardContent class="space-y-6">
                    <div v-if="statistics?.highestBreak"
                         class="p-4 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg border border-yellow-200 dark:border-yellow-800">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="font-medium text-yellow-800 dark:text-yellow-200">{{
                                        t('Highest Break')
                                    }}
                                </div>
                                <div class="text-sm text-yellow-700 dark:text-yellow-300">
                                    {{ statistics.highestBreak.player }} • {{ statistics.highestBreak.match }}
                                </div>
                            </div>
                            <div class="text-3xl font-bold text-yellow-600">
                                {{ statistics.highestBreak.points }}
                            </div>
                        </div>
                    </div>

                    <div v-if="statistics?.longestMatch"
                         class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="font-medium text-blue-800 dark:text-blue-200">{{ t('Longest Match') }}</div>
                                <div class="text-sm text-blue-700 dark:text-blue-300">
                                    {{ statistics.longestMatch.players.join(' vs ') }}
                                </div>
                            </div>
                            <div class="text-2xl font-bold text-blue-600">
                                {{ statistics.longestMatch.duration }}
                            </div>
                        </div>
                    </div>

                    <div v-if="advancedStats" class="grid grid-cols-2 gap-4">
                        <div class="text-center p-3 bg-gray-50 dark:bg-gray-800 rounded">
                            <div class="text-lg font-bold text-gray-900 dark:text-gray-100">
                                {{ advancedStats.mostFramesInMatch }}
                            </div>
                            <div class="text-xs text-gray-600 dark:text-gray-400">{{ t('Most Frames in Match') }}</div>
                        </div>

                        <div class="text-center p-3 bg-gray-50 dark:bg-gray-800 rounded">
                            <div class="text-lg font-bold text-gray-900 dark:text-gray-100">
                                {{ advancedStats.fewestFramesInMatch }}
                            </div>
                            <div class="text-xs text-gray-600 dark:text-gray-400">{{
                                    t('Fewest Frames in Match')
                                }}
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Player Performance Records -->
            <Card v-if="playerRecords">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <StarIcon class="h-5 w-5 text-purple-600"/>
                        {{ t('Player Records') }}
                    </CardTitle>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div
                        class="p-4 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800">
                        <div class="font-medium text-green-800 dark:text-green-200 mb-1">{{ t('Best Win Rate') }}</div>
                        <div class="flex items-center justify-between">
              <span class="text-sm text-green-700 dark:text-green-300">
                {{ getPlayerName(playerRecords.bestWinRate.player) }}
              </span>
                            <span class="font-bold text-green-600">
                {{
                                    formatPercentage((playerRecords.bestWinRate.statistics.matches_won / playerRecords.bestWinRate.statistics.matches_played) * 100)
                                }}
              </span>
                        </div>
                    </div>

                    <div
                        class="p-4 bg-purple-50 dark:bg-purple-900/20 rounded-lg border border-purple-200 dark:border-purple-800">
                        <div class="font-medium text-purple-800 dark:text-purple-200 mb-1">{{
                                t('Best Frame Percentage')
                            }}
                        </div>
                        <div class="flex items-center justify-between">
              <span class="text-sm text-purple-700 dark:text-purple-300">
                {{ getPlayerName(playerRecords.bestFramePercentage.player) }}
              </span>
                            <span class="font-bold text-purple-600">
                {{ formatPercentage(playerRecords.bestFramePercentage.statistics.frame_percentage) }}
              </span>
                        </div>
                    </div>

                    <div
                        class="p-4 bg-orange-50 dark:bg-orange-900/20 rounded-lg border border-orange-200 dark:border-orange-800">
                        <div class="font-medium text-orange-800 dark:text-orange-200 mb-1">{{
                                t('Most Frames Won')
                            }}
                        </div>
                        <div class="flex items-center justify-between">
              <span class="text-sm text-orange-700 dark:text-orange-300">
                {{ getPlayerName(playerRecords.mostFrames.player) }}
              </span>
                            <span class="font-bold text-orange-600">
                {{ playerRecords.mostFrames.statistics.frames_won }}
              </span>
                        </div>
                    </div>

                    <div
                        class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                        <div class="font-medium text-blue-800 dark:text-blue-200 mb-1">{{
                                t('Most Active Player')
                            }}
                        </div>
                        <div class="flex items-center justify-between">
              <span class="text-sm text-blue-700 dark:text-blue-300">
                {{ getPlayerName(playerRecords.mostMatches.player) }}
              </span>
                            <span class="font-bold text-blue-600">
                {{ playerRecords.mostMatches.statistics.matches_played }} {{ t('matches') }}
              </span>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Match Analysis -->
        <div v-if="advancedStats" class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Most Competitive Matches -->
            <Card>
                <CardHeader>
                    <CardTitle>{{ t('Most Competitive Matches') }}</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="space-y-3">
                        <div
                            v-for="(match, index) in advancedStats.competitiveMatches"
                            :key="match.id"
                            class="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg"
                        >
                            <div class="flex items-center justify-between">
                                <div class="text-sm font-medium">
                                    #{{ index + 1 }} {{ getMatchDescription(match) }}
                                </div>
                                <div class="text-xs text-gray-500">
                                    {{ t('Diff') }}: {{ Math.abs(match.scoreA - match.scoreB) }}
                                </div>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Most Dominant Matches -->
            <Card>
                <CardHeader>
                    <CardTitle>{{ t('Most Dominant Performances') }}</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="space-y-3">
                        <div
                            v-for="(match, index) in advancedStats.dominantMatches"
                            :key="match.id"
                            class="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg"
                        >
                            <div class="flex items-center justify-between">
                                <div class="text-sm font-medium">
                                    #{{ index + 1 }} {{ getMatchDescription(match) }}
                                </div>
                                <div class="text-xs text-gray-500">
                                    {{ t('Margin') }}: {{ Math.abs(match.scoreA - match.scoreB) }}
                                </div>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Tournament Overview Stats -->
        <Card v-if="advancedStats">
            <CardHeader>
                <CardTitle>{{ t('Tournament Overview') }}</CardTitle>
            </CardHeader>
            <CardContent>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-6 text-center">
                    <div>
                        <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                            {{ formatPercentage(advancedStats.averageWinRate) }}
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">{{ t('Avg Win Rate') }}</div>
                    </div>

                    <div>
                        <div class="text-2xl font-bold text-green-600 dark:text-green-400">
                            {{ formatPercentage(advancedStats.averageFramePercentage) }}
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">{{ t('Avg Frame %') }}</div>
                    </div>

                    <div>
                        <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">
                            {{ formatPercentage(advancedStats.highestWinRate) }}
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">{{ t('Highest Win Rate') }}</div>
                    </div>

                    <div>
                        <div class="text-2xl font-bold text-orange-600 dark:text-orange-400">
                            {{ Math.round(advancedStats.framesPerMatch * 10) / 10 }}
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">{{ t('Frames/Match') }}</div>
                    </div>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
