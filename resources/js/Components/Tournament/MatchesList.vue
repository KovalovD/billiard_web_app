<script lang="ts" setup>
import {Card, CardContent, Spinner} from '@/Components/ui';
import {useLocale} from '@/composables/useLocale';
import type {TournamentMatch} from '@/types/api';
import {
    AlertCircleIcon,
    CheckCircleIcon,
    ClockIcon,
    MonitorIcon,
    PlayIcon,
    TrophyIcon,
    UsersIcon
} from 'lucide-vue-next';
import {computed} from 'vue';

interface Props {
    matches: TournamentMatch[];
    isLoading?: boolean;
    showTable?: boolean;
    showScheduledTime?: boolean;
    showCompletedTime?: boolean;
    onMatchClick?: (match: TournamentMatch) => void;
    isClickable?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    isLoading: false,
    showTable: true,
    showScheduledTime: true,
    showCompletedTime: true,
    isClickable: false
});

const {t} = useLocale();

// Group matches by stage
const matchesByStage = computed(() => {
    const grouped: Record<string, TournamentMatch[]> = {};

    props.matches.forEach(match => {
        const stage = match.stage_display || match.stage || 'Other';
        if (!grouped[stage]) {
            grouped[stage] = [];
        }
        grouped[stage].push(match);
    });

    // Sort matches within each stage
    Object.keys(grouped).forEach(stage => {
        grouped[stage].sort((a, b) => {
            // Sort by status priority
            const statusOrder = ['in_progress', 'verification', 'ready', 'pending', 'completed'];
            const statusDiff = statusOrder.indexOf(a.status) - statusOrder.indexOf(b.status);
            if (statusDiff !== 0) return statusDiff;

            // Then by scheduled time
            if (a.scheduled_at && b.scheduled_at) {
                return new Date(a.scheduled_at).getTime() - new Date(b.scheduled_at).getTime();
            }

            // Then by match code
            return (a.match_code || '').localeCompare(b.match_code || '');
        });
    });

    return grouped;
});

const getMatchStatusBadgeClass = (status: string): string => {
    switch (status) {
        case 'completed':
            return 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300';
        case 'in_progress':
            return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300';
        case 'verification':
            return 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300';
        case 'ready':
            return 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300';
        default:
            return 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-300';
    }
};

const getStatusIcon = (status: string) => {
    switch (status) {
        case 'completed':
            return CheckCircleIcon;
        case 'in_progress':
            return PlayIcon;
        case 'verification':
            return AlertCircleIcon;
        case 'ready':
            return ClockIcon;
        default:
            return ClockIcon;
    }
};

const formatDateTime = (dateString: string | undefined): string => {
    if (!dateString) return '';

    const date = new Date(dateString);
    const isMobile = window.innerWidth < 640;

    if (isMobile) {
        return date.toLocaleString('uk-UK', {
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    return date.toLocaleString('uk-UK', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const handleMatchClick = (match: TournamentMatch) => {
    if (props.isClickable && props.onMatchClick) {
        props.onMatchClick(match);
    }
};
</script>

<template>
    <div class="space-y-6">
        <!-- Loading State -->
        <div v-if="isLoading" class="flex justify-center py-12">
            <div class="text-center">
                <Spinner class="mx-auto h-8 w-8 text-indigo-600"/>
                <p class="mt-2 text-gray-500">{{ t('Loading matches...') }}</p>
            </div>
        </div>

        <!-- Empty State -->
        <div v-else-if="matches.length === 0" class="text-center py-12">
            <PlayIcon class="mx-auto h-12 w-12 text-gray-400"/>
            <p class="mt-4 text-lg font-medium text-gray-900 dark:text-gray-100">
                {{ t('No matches scheduled') }}
            </p>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
                {{ t('Matches will appear here once the tournament starts.') }}
            </p>
        </div>

        <!-- Matches grouped by stage -->
        <div v-else class="space-y-8">
            <div v-for="(stageMatches, stage) in matchesByStage" :key="stage">
                <!-- Stage Header -->
                <div class="mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <TrophyIcon class="h-5 w-5 text-indigo-600 dark:text-indigo-400"/>
                        {{ stage }}
                        <span class="text-sm font-normal text-gray-500 dark:text-gray-400">
                            ({{ stageMatches.length }} {{ t('matches') }})
                        </span>
                    </h3>
                </div>

                <!-- Matches Grid -->
                <div class="grid gap-4 lg:grid-cols-2">
                    <Card
                        v-for="match in stageMatches"
                        :key="match.id"
                        :class="[
                            'overflow-hidden transition-all duration-200',
                            isClickable ? 'hover:shadow-lg hover:scale-[1.02] cursor-pointer' : '',
                            match.status === 'in_progress' ? 'ring-2 ring-yellow-400 dark:ring-yellow-600' : ''
                        ]"
                        @click="handleMatchClick(match)"
                    >
                        <!-- Match Header with Gradient -->
                        <div :class="[
                            'px-4 py-2 flex items-center justify-between',
                            match.status === 'completed'
                                ? 'bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-700'
                                : match.status === 'in_progress'
                                ? 'bg-gradient-to-r from-yellow-50 to-orange-50 dark:from-yellow-900/20 dark:to-orange-900/20'
                                : 'bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20'
                        ]">
                            <div class="flex items-center gap-3">
                                <component
                                    :is="getStatusIcon(match.status)"
                                    class="h-4 w-4 text-gray-600 dark:text-gray-400"
                                />
                                <div>
                                    <span class="font-medium text-gray-900 dark:text-white">
                                        {{ match.match_code || `#${match.id}` }}
                                    </span>
                                    <span v-if="match.round_display"
                                          class="ml-2 text-sm text-gray-500 dark:text-gray-400">
                                        • {{ match.round_display }}
                                    </span>
                                </div>
                            </div>
                            <span :class="[
                                'inline-flex px-2.5 py-1 text-xs font-medium rounded-full',
                                getMatchStatusBadgeClass(match.status)
                            ]">
                                {{ match.status_display }}
                            </span>
                        </div>

                        <!-- Match Content -->
                        <CardContent class="p-4">
                            <!-- Players Section -->
                            <div class="space-y-3">
                                <!-- Player 1 -->
                                <div
                                    class="flex items-center justify-between p-3 rounded-lg bg-gray-50 dark:bg-gray-800/50">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="h-10 w-10 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center">
                                            <UsersIcon class="h-5 w-5 text-gray-500 dark:text-gray-400"/>
                                        </div>
                                        <div>
                                            <p :class="[
                                                'font-medium',
                                                match.winner_id === match.player1_id
                                                    ? 'text-green-600 dark:text-green-400'
                                                    : 'text-gray-900 dark:text-white'
                                            ]">
                                                {{ match.player1?.firstname }} {{ match.player1?.lastname }}
                                                <span v-if="!match.player1" class="text-gray-400">{{ t('TBD') }}</span>
                                            </p>
                                            <p v-if="match.winner_id === match.player1_id"
                                               class="text-xs text-green-600 dark:text-green-400">
                                                <TrophyIcon class="inline h-3 w-3 mr-1"/>
                                                {{ t('Winner') }}
                                            </p>
                                        </div>
                                    </div>
                                    <div class="text-2xl font-bold text-gray-900 dark:text-white">
                                        {{ match.player1_score ?? '-' }}
                                    </div>
                                </div>

                                <!-- VS Divider -->
                                <div class="flex items-center gap-2 px-3">
                                    <div class="flex-1 h-px bg-gray-200 dark:bg-gray-700"></div>
                                    <span class="text-xs font-medium text-gray-400 uppercase">{{ t('VS') }}</span>
                                    <div class="flex-1 h-px bg-gray-200 dark:bg-gray-700"></div>
                                </div>

                                <!-- Player 2 -->
                                <div
                                    class="flex items-center justify-between p-3 rounded-lg bg-gray-50 dark:bg-gray-800/50">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="h-10 w-10 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center">
                                            <UsersIcon class="h-5 w-5 text-gray-500 dark:text-gray-400"/>
                                        </div>
                                        <div>
                                            <p :class="[
                                                'font-medium',
                                                match.winner_id === match.player2_id
                                                    ? 'text-green-600 dark:text-green-400'
                                                    : 'text-gray-900 dark:text-white'
                                            ]">
                                                {{ match.player2?.firstname }} {{ match.player2?.lastname }}
                                                <span v-if="!match.player2" class="text-gray-400">{{ t('TBD') }}</span>
                                            </p>
                                            <p v-if="match.winner_id === match.player2_id"
                                               class="text-xs text-green-600 dark:text-green-400">
                                                <TrophyIcon class="inline h-3 w-3 mr-1"/>
                                                {{ t('Winner') }}
                                            </p>
                                        </div>
                                    </div>
                                    <div class="text-2xl font-bold text-gray-900 dark:text-white">
                                        {{ match.player2_score ?? '-' }}
                                    </div>
                                </div>
                            </div>

                            <!-- Match Details -->
                            <div v-if="showTable || showScheduledTime || showCompletedTime"
                                 class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700 flex flex-wrap items-center gap-4 text-sm text-gray-600 dark:text-gray-400">
                                <div v-if="showTable && match.club_table" class="flex items-center gap-1">
                                    <MonitorIcon class="h-4 w-4"/>
                                    <span>{{ match.club_table.name }}</span>
                                </div>
                                <div v-if="showScheduledTime && match.scheduled_at" class="flex items-center gap-1">
                                    <ClockIcon class="h-4 w-4"/>
                                    <span>{{ formatDateTime(match.scheduled_at) }}</span>
                                </div>
                                <div v-if="showCompletedTime && match.completed_at && match.status === 'completed'"
                                     class="flex items-center gap-1 text-green-600 dark:text-green-400">
                                    <CheckCircleIcon class="h-4 w-4"/>
                                    <span>{{ formatDateTime(match.completed_at) }}</span>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </div>
</template>
