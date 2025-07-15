<!-- resources/js/Components/Tournament/TournamentMatchList.vue -->
<script lang="ts" setup>
import {Card, CardContent, Spinner} from '@/Components/ui';
import {useLocale} from '@/composables/useLocale';
import type {TournamentMatch} from '@/types/api';
import {AlertCircleIcon, CheckCircleIcon, ClockIcon, MonitorIcon, PlayIcon, TrophyIcon,} from 'lucide-vue-next';
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
    return date.toLocaleString('uk-UK', {
        day: '2-digit',
        month: '2-digit',
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
    <div class="space-y-2">
        <!-- Loading State -->
        <div v-if="isLoading" class="flex justify-center py-6">
            <div class="text-center">
                <Spinner class="mx-auto h-5 w-5 text-indigo-600"/>
                <p class="mt-1 text-xs text-gray-500">{{ t('Loading matches...') }}</p>
            </div>
        </div>

        <!-- Empty State -->
        <div v-else-if="matches.length === 0" class="text-center py-6">
            <PlayIcon class="mx-auto h-8 w-8 text-gray-400"/>
            <p class="mt-1.5 text-sm font-medium text-gray-900 dark:text-gray-100">
                {{ t('No matches scheduled') }}
            </p>
            <p class="text-xs text-gray-600 dark:text-gray-400">
                {{ t('Matches will appear here once the tournament starts.') }}
            </p>
        </div>

        <!-- Matches grouped by stage -->
        <div v-else class="space-y-3">
            <div v-for="(stageMatches, stage) in matchesByStage" :key="stage">
                <!-- Stage Header -->
                <div class="mb-1.5">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white flex items-center gap-1.5">
                        <TrophyIcon class="h-3.5 w-3.5 text-indigo-600 dark:text-indigo-400"/>
                        {{ stage }}
                        <span class="text-xs font-normal text-gray-500 dark:text-gray-400">
                            ({{ stageMatches.length }})
                        </span>
                    </h3>
                </div>

                <!-- Matches Grid - Super Compact -->
                <div class="grid gap-1.5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    <Card
                        v-for="match in stageMatches"
                        :key="match.id"
                        :class="[
                            'overflow-hidden transition-all duration-200',
                            isClickable ? 'hover:shadow-sm cursor-pointer' : '',
                            match.status === 'in_progress' ? 'ring-1 ring-yellow-400 dark:ring-yellow-600' : ''
                        ]"
                        @click="handleMatchClick(match)"
                    >
                        <!-- Match Header - Ultra Compact -->
                        <div :class="[
                            'px-2 py-1 flex items-center justify-between text-xs',
                            match.status === 'completed'
                                ? 'bg-gray-50 dark:bg-gray-800'
                                : match.status === 'in_progress'
                                ? 'bg-yellow-50 dark:bg-yellow-900/20'
                                : 'bg-blue-50 dark:bg-blue-900/20'
                        ]">
                            <div class="flex items-center gap-1">
                                <component
                                    :is="getStatusIcon(match.status)"
                                    class="h-3 w-3 text-gray-600 dark:text-gray-400"
                                />
                                <span class="font-medium text-gray-900 dark:text-white">
                                    {{ match.match_code || `#${match.id}` }}
                                </span>
                            </div>
                            <span :class="[
                                'inline-flex px-1.5 py-0.5 text-xs font-medium rounded',
                                getMatchStatusBadgeClass(match.status)
                            ]">
                                {{ match.status_display }}
                            </span>
                        </div>

                        <!-- Match Content - Ultra Compact -->
                        <CardContent class="p-2">
                            <!-- Players Section - Single Line -->
                            <div class="space-y-1">
                                <!-- Player 1 -->
                                <div class="flex items-center justify-between">
                                    <p :class="[
                                        'text-xs font-medium truncate max-w-[70%]',
                                        match.winner_id === match.player1_id
                                            ? 'text-green-600 dark:text-green-400'
                                            : 'text-gray-900 dark:text-white'
                                    ]">
                                        {{
                                            match.player1 ? `${match.player1.firstname} ${match.player1.lastname}` : t('TBD')
                                        }}
                                        <TrophyIcon v-if="match.winner_id === match.player1_id"
                                                    class="inline h-2.5 w-2.5 ml-0.5"/>
                                    </p>
                                    <span class="text-sm font-bold text-gray-900 dark:text-white">
                                        {{ match.player1_score ?? '-' }}
                                    </span>
                                </div>

                                <!-- Player 2 -->
                                <div class="flex items-center justify-between">
                                    <p :class="[
                                        'text-xs font-medium truncate max-w-[70%]',
                                        match.winner_id === match.player2_id
                                            ? 'text-green-600 dark:text-green-400'
                                            : 'text-gray-900 dark:text-white'
                                    ]">
                                        {{
                                            match.player2 ? `${match.player2.firstname} ${match.player2.lastname}` : t('TBD')
                                        }}
                                        <TrophyIcon v-if="match.winner_id === match.player2_id"
                                                    class="inline h-2.5 w-2.5 ml-0.5"/>
                                    </p>
                                    <span class="text-sm font-bold text-gray-900 dark:text-white">
                                        {{ match.player2_score ?? '-' }}
                                    </span>
                                </div>
                            </div>

                            <!-- Match Details - Ultra Compact -->
                            <div v-if="(showTable && match.club_table) || (showScheduledTime && match.scheduled_at)"
                                 class="mt-1.5 pt-1.5 border-t border-gray-100 dark:border-gray-800 flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                                <div v-if="showTable && match.club_table" class="flex items-center gap-0.5">
                                    <MonitorIcon class="h-2.5 w-2.5"/>
                                    <span>{{ match.club_table.name }}</span>
                                </div>
                                <div v-if="showScheduledTime && match.scheduled_at" class="flex items-center gap-0.5">
                                    <ClockIcon class="h-2.5 w-2.5"/>
                                    <span>{{ formatDateTime(match.scheduled_at) }}</span>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </div>
</template>
