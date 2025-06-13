<!-- resources/js/Components/Tournament/ScheduleConflicts.vue -->
<script lang="ts" setup>
import {computed} from 'vue';
import {Button, Card, CardContent, CardHeader, CardTitle} from '@/Components/ui';
import {useLocale} from '@/composables/useLocale';
import {AlertTriangleIcon, CheckIcon, ClockIcon, TableIcon, UserIcon} from 'lucide-vue-next';

interface Conflict {
    type: 'player_conflict' | 'table_conflict' | 'time_conflict';
    message: string;
    match_ids: number[];
}

interface Match {
    id: number;
    scheduledAt: string;
    tableNumber?: number;
    playerA: any;
    playerB: any;
    status: string;
}

interface Props {
    conflicts: Conflict[];
    matches: Match[];
}

const props = defineProps<Props>();
const emit = defineEmits<{
    'resolve-conflict': [conflictIndex: number, resolution: string];
}>();

const {t} = useLocale();

// Computed
const conflictsByType = computed(() => {
    const grouped = {
        player_conflict: [] as Conflict[],
        table_conflict: [] as Conflict[],
        time_conflict: [] as Conflict[]
    };

    props.conflicts.forEach(conflict => {
        grouped[conflict.type].push(conflict);
    });

    return grouped;
});

const conflictSeverity = computed(() => {
    const playerConflicts = conflictsByType.value.player_conflict.length;
    const tableConflicts = conflictsByType.value.table_conflict.length;
    const timeConflicts = conflictsByType.value.time_conflict.length;

    if (playerConflicts > 0) return 'high';
    if (tableConflicts > 0) return 'medium';
    if (timeConflicts > 0) return 'low';
    return 'none';
});

// Methods
const getConflictIcon = (type: string) => {
    switch (type) {
        case 'player_conflict':
            return UserIcon;
        case 'table_conflict':
            return TableIcon;
        case 'time_conflict':
            return ClockIcon;
        default:
            return AlertTriangleIcon;
    }
};

const getConflictColor = (type: string): string => {
    switch (type) {
        case 'player_conflict':
            return 'text-red-600 dark:text-red-400';
        case 'table_conflict':
            return 'text-orange-600 dark:text-orange-400';
        case 'time_conflict':
            return 'text-yellow-600 dark:text-yellow-400';
        default:
            return 'text-gray-600 dark:text-gray-400';
    }
};

const getConflictBgColor = (type: string): string => {
    switch (type) {
        case 'player_conflict':
            return 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800';
        case 'table_conflict':
            return 'bg-orange-50 dark:bg-orange-900/20 border-orange-200 dark:border-orange-800';
        case 'time_conflict':
            return 'bg-yellow-50 dark:bg-yellow-900/20 border-yellow-200 dark:border-yellow-800';
        default:
            return 'bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700';
    }
};

const getMatchDetails = (matchId: number) => {
    const match = props.matches.find(m => m.id === matchId);
    if (!match) return {players: 'Unknown', time: 'Unknown', table: null};

    const playerA = match.playerA?.name || `${match.playerA?.firstname || ''} ${match.playerA?.lastname || ''}`.trim() || 'Player A';
    const playerB = match.playerB?.name || `${match.playerB?.firstname || ''} ${match.playerB?.lastname || ''}`.trim() || 'Player B';

    return {
        players: `${playerA} vs ${playerB}`,
        time: match.scheduledAt ? new Date(match.scheduledAt).toLocaleString() : 'Not scheduled',
        table: match.tableNumber || null
    };
};

const resolveConflict = (conflictIndex: number, resolution: 'auto' | 'manual') => {
    emit('resolve-conflict', conflictIndex, resolution);
};

const getSeverityColor = (): string => {
    switch (conflictSeverity.value) {
        case 'high':
            return 'text-red-600 dark:text-red-400';
        case 'medium':
            return 'text-orange-600 dark:text-orange-400';
        case 'low':
            return 'text-yellow-600 dark:text-yellow-400';
        default:
            return 'text-green-600 dark:text-green-400';
    }
};

const getSeverityText = (): string => {
    switch (conflictSeverity.value) {
        case 'high':
            return t('High Priority');
        case 'medium':
            return t('Medium Priority');
        case 'low':
            return t('Low Priority');
        default:
            return t('No Conflicts');
    }
};
</script>

<template>
    <div class="space-y-6">
        <!-- Conflict Summary -->
        <Card>
            <CardHeader>
                <div class="flex items-center justify-between">
                    <CardTitle class="flex items-center gap-2">
                        <AlertTriangleIcon :class="['h-5 w-5', getSeverityColor()]"/>
                        {{ t('Schedule Conflicts') }}
                    </CardTitle>

                    <div class="flex items-center space-x-2">
            <span :class="['px-3 py-1 text-sm font-medium rounded-full', getSeverityColor()]">
              {{ getSeverityText() }}
            </span>
                        <span class="text-sm text-gray-600 dark:text-gray-400">
              {{ conflicts.length }} {{ t('total conflicts') }}
            </span>
                    </div>
                </div>
            </CardHeader>

            <CardContent v-if="conflicts.length === 0">
                <div class="text-center py-8">
                    <CheckIcon class="mx-auto h-12 w-12 text-green-600 mb-4"/>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">
                        {{ t('No Schedule Conflicts') }}
                    </h3>
                    <p class="text-gray-600 dark:text-gray-400">
                        {{ t('All matches are properly scheduled without conflicts') }}
                    </p>
                </div>
            </CardContent>
        </Card>

        <!-- Player Conflicts (Highest Priority) -->
        <Card v-if="conflictsByType.player_conflict.length > 0">
            <CardHeader>
                <CardTitle class="flex items-center gap-2 text-red-600 dark:text-red-400">
                    <UserIcon class="h-5 w-5"/>
                    {{ t('Player Conflicts') }}
                    <span class="text-sm font-normal">({{ conflictsByType.player_conflict.length }})</span>
                </CardTitle>
            </CardHeader>
            <CardContent>
                <div class="space-y-4">
                    <div
                        v-for="(conflict, index) in conflictsByType.player_conflict"
                        :key="`player-${index}`"
                        :class="['p-4 rounded-lg border', getConflictBgColor('player_conflict')]"
                    >
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-center space-x-2 mb-2">
                                    <UserIcon :class="['h-4 w-4', getConflictColor('player_conflict')]"/>
                                    <span class="font-medium text-red-800 dark:text-red-200">
                    {{ t('Player Double-Booking') }}
                  </span>
                                </div>

                                <p class="text-sm text-red-700 dark:text-red-300 mb-3">
                                    {{ conflict.message }}
                                </p>

                                <div class="space-y-2">
                                    <h5 class="text-sm font-medium text-red-800 dark:text-red-200">
                                        {{ t('Affected Matches') }}:
                                    </h5>
                                    <div class="space-y-1">
                                        <div
                                            v-for="matchId in conflict.match_ids"
                                            :key="matchId"
                                            class="text-sm text-red-600 dark:text-red-400 pl-4"
                                        >
                                            <span class="font-mono">#{{ matchId }}</span> -
                                            {{ getMatchDetails(matchId).players }}
                                            <span class="text-xs opacity-75">
                        ({{ getMatchDetails(matchId).time }})
                      </span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="flex space-x-2 ml-4">
                                <Button
                                    class="text-red-600 border-red-300 hover:bg-red-50"
                                    size="sm"
                                    variant="outline"
                                    @click="resolveConflict(index, 'auto')"
                                >
                                    {{ t('Auto Resolve') }}
                                </Button>
                                <Button
                                    class="bg-red-600 hover:bg-red-700"
                                    size="sm"
                                    @click="resolveConflict(index, 'manual')"
                                >
                                    {{ t('Manually Fix') }}
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Table Conflicts -->
        <Card v-if="conflictsByType.table_conflict.length > 0">
            <CardHeader>
                <CardTitle class="flex items-center gap-2 text-orange-600 dark:text-orange-400">
                    <TableIcon class="h-5 w-5"/>
                    {{ t('Table Conflicts') }}
                    <span class="text-sm font-normal">({{ conflictsByType.table_conflict.length }})</span>
                </CardTitle>
            </CardHeader>
            <CardContent>
                <div class="space-y-4">
                    <div
                        v-for="(conflict, index) in conflictsByType.table_conflict"
                        :key="`table-${index}`"
                        :class="['p-4 rounded-lg border', getConflictBgColor('table_conflict')]"
                    >
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-center space-x-2 mb-2">
                                    <TableIcon :class="['h-4 w-4', getConflictColor('table_conflict')]"/>
                                    <span class="font-medium text-orange-800 dark:text-orange-200">
                    {{ t('Table Double-Booking') }}
                  </span>
                                </div>

                                <p class="text-sm text-orange-700 dark:text-orange-300 mb-3">
                                    {{ conflict.message }}
                                </p>

                                <div class="space-y-2">
                                    <h5 class="text-sm font-medium text-orange-800 dark:text-orange-200">
                                        {{ t('Conflicting Matches') }}:
                                    </h5>
                                    <div class="space-y-1">
                                        <div
                                            v-for="matchId in conflict.match_ids"
                                            :key="matchId"
                                            class="text-sm text-orange-600 dark:text-orange-400 pl-4"
                                        >
                                            <span class="font-mono">#{{ matchId }}</span> -
                                            {{ getMatchDetails(matchId).players }}
                                            <span v-if="getMatchDetails(matchId).table" class="text-xs opacity-75">
                        ({{ t('Table') }} {{ getMatchDetails(matchId).table }})
                      </span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="flex space-x-2 ml-4">
                                <Button
                                    class="text-orange-600 border-orange-300 hover:bg-orange-50"
                                    size="sm"
                                    variant="outline"
                                    @click="resolveConflict(index, 'auto')"
                                >
                                    {{ t('Reassign Tables') }}
                                </Button>
                                <Button
                                    class="bg-orange-600 hover:bg-orange-700"
                                    size="sm"
                                    @click="resolveConflict(index, 'manual')"
                                >
                                    {{ t('Manual Fix') }}
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Time Conflicts -->
        <Card v-if="conflictsByType.time_conflict.length > 0">
            <CardHeader>
                <CardTitle class="flex items-center gap-2 text-yellow-600 dark:text-yellow-400">
                    <ClockIcon class="h-5 w-5"/>
                    {{ t('Time Conflicts') }}
                    <span class="text-sm font-normal">({{ conflictsByType.time_conflict.length }})</span>
                </CardTitle>
            </CardHeader>
            <CardContent>
                <div class="space-y-4">
                    <div
                        v-for="(conflict, index) in conflictsByType.time_conflict"
                        :key="`time-${index}`"
                        :class="['p-4 rounded-lg border', getConflictBgColor('time_conflict')]"
                    >
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-center space-x-2 mb-2">
                                    <ClockIcon :class="['h-4 w-4', getConflictColor('time_conflict')]"/>
                                    <span class="font-medium text-yellow-800 dark:text-yellow-200">
                    {{ t('Timing Issue') }}
                  </span>
                                </div>

                                <p class="text-sm text-yellow-700 dark:text-yellow-300 mb-3">
                                    {{ conflict.message }}
                                </p>

                                <div class="space-y-2">
                                    <h5 class="text-sm font-medium text-yellow-800 dark:text-yellow-200">
                                        {{ t('Affected Matches') }}:
                                    </h5>
                                    <div class="space-y-1">
                                        <div
                                            v-for="matchId in conflict.match_ids"
                                            :key="matchId"
                                            class="text-sm text-yellow-600 dark:text-yellow-400 pl-4"
                                        >
                                            <span class="font-mono">#{{ matchId }}</span> -
                                            {{ getMatchDetails(matchId).players }}
                                            <span class="text-xs opacity-75">
                        ({{ getMatchDetails(matchId).time }})
                      </span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="flex space-x-2 ml-4">
                                <Button
                                    class="text-yellow-600 border-yellow-300 hover:bg-yellow-50"
                                    size="sm"
                                    variant="outline"
                                    @click="resolveConflict(index, 'auto')"
                                >
                                    {{ t('Reschedule') }}
                                </Button>
                                <Button
                                    class="bg-yellow-600 hover:bg-yellow-700"
                                    size="sm"
                                    @click="resolveConflict(index, 'manual')"
                                >
                                    {{ t('Manual Fix') }}
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Resolution Tips -->
        <Card v-if="conflicts.length > 0">
            <CardHeader>
                <CardTitle class="text-blue-600 dark:text-blue-400">
                    {{ t('Resolution Tips') }}
                </CardTitle>
            </CardHeader>
            <CardContent>
                <div class="space-y-3 text-sm">
                    <div class="flex items-start space-x-2">
                        <CheckIcon class="h-4 w-4 text-green-600 mt-0.5"/>
                        <span>{{
                                t('Auto resolve will automatically reschedule conflicts based on availability')
                            }}</span>
                    </div>
                    <div class="flex items-start space-x-2">
                        <CheckIcon class="h-4 w-4 text-green-600 mt-0.5"/>
                        <span>{{ t('Manual fix allows you to customize the resolution for specific needs') }}</span>
                    </div>
                    <div class="flex items-start space-x-2">
                        <CheckIcon class="h-4 w-4 text-green-600 mt-0.5"/>
                        <span>{{ t('Player conflicts take priority over table and time conflicts') }}</span>
                    </div>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
