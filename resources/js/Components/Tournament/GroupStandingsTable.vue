<!-- resources/js/Components/Tournament/GroupStandingsTable.vue -->
<script lang="ts" setup>
import {computed} from 'vue';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
    Button
} from '@/Components/ui';
import {useLocale} from '@/composables/useLocale';
import {
    TrophyIcon,
    ArrowUpIcon,
    ArrowDownIcon,
    CheckCircleIcon,
    XCircleIcon
} from 'lucide-vue-next';
import type {Group, GroupStanding} from '@/types/tournament';

interface Props {
    group: Group;
}

const props = defineProps<Props>();
const emit = defineEmits<{
    'update-standings': [groupId: number];
}>();

const {t} = useLocale();

// Computed
const sortedStandings = computed(() => {
    return [...props.group.standings].sort((a, b) => {
        // Sort by points first
        if (a.points !== b.points) return b.points - a.points;

        // Then by frame difference
        if (a.frame_difference !== b.frame_difference) return b.frame_difference - a.frame_difference;

        // Then by frames won
        if (a.frames_won !== b.frames_won) return b.frames_won - a.frames_won;

        // Finally by matches played (fewer is better for equal records)
        return a.matches_played - b.matches_played;
    });
});

const groupStats = computed(() => {
    const standings = props.group.standings;
    return {
        totalMatches: standings.reduce((sum, s) => sum + s.matches_played, 0) / 2, // Divide by 2 since each match involves 2 players
        completedMatches: standings.reduce((sum, s) => sum + s.matches_played, 0) / 2,
        totalFrames: standings.reduce((sum, s) => sum + s.frames_won + s.frames_lost, 0) / 2,
        averageFramesPerMatch: standings.length > 0 ?
            (standings.reduce((sum, s) => sum + s.frames_won + s.frames_lost, 0) / 2) /
            Math.max(1, standings.reduce((sum, s) => sum + s.matches_played, 0) / 2) : 0
    };
});

// Methods
const getPositionClass = (position: number): string => {
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

const getPositionIcon = (position: number) => {
    switch (position) {
        case 1:
            return TrophyIcon;
        case 2:
            return ArrowUpIcon;
        case 3:
            return ArrowUpIcon;
        default:
            return null;
    }
};

const getWinPercentage = (standing: GroupStanding): number => {
    if (standing.matches_played === 0) return 0;
    return Math.round((standing.wins / standing.matches_played) * 100);
};

const getFramePercentage = (standing: GroupStanding): number => {
    const totalFrames = standing.frames_won + standing.frames_lost;
    if (totalFrames === 0) return 0;
    return Math.round((standing.frames_won / totalFrames) * 100);
};

const isQualificationPosition = (position: number): boolean => {
    return position <= props.group.advance_count;
};

const refreshStandings = () => {
    emit('update-standings', props.group.id);
};
</script>

<template>
    <Card>
        <CardHeader>
            <div class="flex items-center justify-between">
                <CardTitle class="flex items-center gap-2">
                    <div
                        class="w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center text-sm font-bold">
                        {{ group.name.charAt(group.name.length - 1) }}
                    </div>
                    {{ group.name }}
                </CardTitle>

                <div class="flex items-center space-x-4">
                    <!-- Group Stats -->
                    <div class="text-right text-sm text-gray-600 dark:text-gray-400">
                        <div>{{ groupStats.completedMatches }} / {{ groupStats.totalMatches }} {{ t('matches') }}</div>
                        <div>{{ Math.round(groupStats.averageFramesPerMatch) }} {{ t('avg frames/match') }}</div>
                    </div>

                    <Button size="sm" variant="outline" @click="refreshStandings">
                        {{ t('Refresh') }}
                    </Button>
                </div>
            </div>
        </CardHeader>

        <CardContent>
            <!-- Standings Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                    <tr class="border-b dark:border-gray-700">
                        <th class="text-left py-3 px-2">{{ t('Pos') }}</th>
                        <th class="text-left py-3 px-2">{{ t('Player') }}</th>
                        <th class="text-center py-3 px-2">{{ t('MP') }}</th>
                        <th class="text-center py-3 px-2">{{ t('W') }}</th>
                        <th class="text-center py-3 px-2">{{ t('L') }}</th>
                        <th class="text-center py-3 px-2">{{ t('D') }}</th>
                        <th class="text-center py-3 px-2">{{ t('FW') }}</th>
                        <th class="text-center py-3 px-2">{{ t('FL') }}</th>
                        <th class="text-center py-3 px-2">{{ t('FD') }}</th>
                        <th class="text-center py-3 px-2">{{ t('Pts') }}</th>
                        <th class="text-center py-3 px-2">{{ t('Win %') }}</th>
                        <th class="text-center py-3 px-2">{{ t('Status') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr
                        v-for="(standing, index) in sortedStandings"
                        :key="standing.id"
                        :class="[
                'border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800',
                isQualificationPosition(index + 1) ? 'bg-green-50 dark:bg-green-900/10' : ''
              ]"
                    >
                        <!-- Position -->
                        <td class="py-3 px-2">
                            <div class="flex items-center space-x-2">
                  <span
                      :class="[
                      'inline-flex items-center justify-center w-6 h-6 rounded-full text-xs font-bold',
                      getPositionClass(index + 1)
                    ]"
                  >
                    {{ index + 1 }}
                  </span>
                                <component
                                    :is="getPositionIcon(index + 1)"
                                    v-if="getPositionIcon(index + 1)"
                                    class="h-3 w-3 text-yellow-600"
                                />
                            </div>
                        </td>

                        <!-- Player -->
                        <td class="py-3 px-2">
                            <div class="flex items-center space-x-2">
                                <div>
                                    <div class="font-medium">
                                        {{ standing.player.user?.firstname }} {{ standing.player.user?.lastname }}
                                    </div>
                                    <div v-if="standing.player.user?.rating" class="text-xs text-gray-500">
                                        {{ t('Rating') }}: {{ standing.player.user.rating }}
                                    </div>
                                </div>
                            </div>
                        </td>

                        <!-- Matches Played -->
                        <td class="py-3 px-2 text-center font-medium">
                            {{ standing.matches_played }}
                        </td>

                        <!-- Wins -->
                        <td class="py-3 px-2 text-center">
                <span class="text-green-600 dark:text-green-400 font-medium">
                  {{ standing.wins }}
                </span>
                        </td>

                        <!-- Losses -->
                        <td class="py-3 px-2 text-center">
                <span class="text-red-600 dark:text-red-400 font-medium">
                  {{ standing.losses }}
                </span>
                        </td>

                        <!-- Draws -->
                        <td class="py-3 px-2 text-center">
                <span class="text-gray-600 dark:text-gray-400">
                  {{ standing.draws }}
                </span>
                        </td>

                        <!-- Frames Won -->
                        <td class="py-3 px-2 text-center">
                <span class="text-blue-600 dark:text-blue-400 font-medium">
                  {{ standing.frames_won }}
                </span>
                        </td>

                        <!-- Frames Lost -->
                        <td class="py-3 px-2 text-center">
                <span class="text-orange-600 dark:text-orange-400">
                  {{ standing.frames_lost }}
                </span>
                        </td>

                        <!-- Frame Difference -->
                        <td class="py-3 px-2 text-center">
                <span
                    :class="[
                    'font-medium',
                    standing.frame_difference > 0
                      ? 'text-green-600 dark:text-green-400'
                      : standing.frame_difference < 0
                        ? 'text-red-600 dark:text-red-400'
                        : 'text-gray-600 dark:text-gray-400'
                  ]"
                >
                  {{ standing.frame_difference > 0 ? '+' : '' }}{{ standing.frame_difference }}
                </span>
                        </td>

                        <!-- Points -->
                        <td class="py-3 px-2 text-center">
                <span class="text-lg font-bold text-purple-600 dark:text-purple-400">
                  {{ standing.points }}
                </span>
                        </td>

                        <!-- Win Percentage -->
                        <td class="py-3 px-2 text-center">
                            <div class="flex flex-col items-center">
                                <span class="font-medium">{{ getWinPercentage(standing) }}%</span>
                                <span class="text-xs text-gray-500">
                    ({{ getFramePercentage(standing) }}% {{ t('frames') }})
                  </span>
                            </div>
                        </td>

                        <!-- Qualification Status -->
                        <td class="py-3 px-2 text-center">
                            <div class="flex items-center justify-center">
                                <CheckCircleIcon
                                    v-if="standing.qualified"
                                    :title="t('Qualified for playoffs')"
                                    class="h-5 w-5 text-green-600 dark:text-green-400"
                                />
                                <XCircleIcon
                                    v-else-if="!isQualificationPosition(index + 1)"
                                    :title="t('Eliminated')"
                                    class="h-5 w-5 text-red-600 dark:text-red-400"
                                />
                                <div
                                    v-else
                                    :title="t('In qualification position')"
                                    class="h-5 w-5 rounded-full bg-yellow-400"
                                />
                            </div>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>

            <!-- Group Legend -->
            <div class="mt-4 flex items-center justify-between text-xs text-gray-600 dark:text-gray-400">
                <div class="space-x-4">
                    <span>{{ t('MP - Matches Played') }}</span>
                    <span>{{ t('W - Wins') }}</span>
                    <span>{{ t('L - Losses') }}</span>
                    <span>{{ t('D - Draws') }}</span>
                </div>
                <div class="space-x-4">
                    <span>{{ t('FW - Frames Won') }}</span>
                    <span>{{ t('FL - Frames Lost') }}</span>
                    <span>{{ t('FD - Frame Difference') }}</span>
                </div>
            </div>

            <!-- Qualification Info -->
            <div class="mt-4 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                <div class="flex items-center space-x-2 text-sm">
                    <CheckCircleIcon class="h-4 w-4 text-green-600"/>
                    <span class="text-blue-800 dark:text-blue-200">
            {{ t('Top :count players advance to playoffs', {count: group.advance_count}) }}
          </span>
                </div>

                <div class="mt-2 text-xs text-blue-700 dark:text-blue-300">
                    {{ t('Tiebreaker order: Points → Frame Difference → Frames Won → Matches Played') }}
                </div>
            </div>
        </CardContent>
    </Card>
</template>
