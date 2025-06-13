<!-- resources/js/Pages/Tournaments/Schedule.vue -->
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
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
    Input,
    Modal
} from '@/Components/ui';
import {useTournamentStore} from '@/stores/tournament';
import {useLocale} from '@/composables/useLocale';
import MatchScheduleCalendar from '@/Components/Tournament/MatchScheduleCalendar.vue';
import MatchCard from '@/Components/Tournament/MatchCard.vue';
import ScheduleConflicts from '@/Components/Tournament/ScheduleConflicts.vue';
import {
    ArrowLeftIcon,
    CalendarIcon,
    ClockIcon,
    PlayIcon,
    PauseIcon,
    RefreshCwIcon,
    SettingsIcon,
    TableIcon
} from 'lucide-vue-next';
import type {MatchSchedule} from '@/services/TournamentService';

defineOptions({layout: AuthenticatedLayout});

interface Props {
    tournamentId: number;
}

const props = defineProps<Props>();

const {t} = useLocale();
const tournamentStore = useTournamentStore();

// State
const viewMode = ref<'calendar' | 'list' | 'table'>('list');
const selectedDate = ref(new Date().toISOString().split('T')[0]);
const selectedTable = ref<number | null>(null);
const showScheduleModal = ref(false);
const showConflictsModal = ref(false);
const isGenerating = ref(false);

// Mock data for tables
const tables = ref([
    {id: 1, name: 'Table 1', location: 'Main Hall', available: true},
    {id: 2, name: 'Table 2', location: 'Main Hall', available: true},
    {id: 3, name: 'Table 3', location: 'Side Room', available: false},
    {id: 4, name: 'Table 4', location: 'VIP Area', available: true}
]);

// Mock schedule conflicts
const conflicts = ref([
    {
        type: 'player_conflict' as const,
        message: 'John Doe has overlapping matches',
        match_ids: [1, 3]
    },
    {
        type: 'table_conflict' as const,
        message: 'Table 1 double-booked at 14:00',
        match_ids: [2, 4]
    }
]);

// Computed
const tournament = computed(() => tournamentStore.currentTournament);
const matches = computed(() => tournamentStore.scheduledMatches);

const todaysMatches = computed(() => {
    const today = new Date().toISOString().split('T')[0];
    return matches.value.filter(match =>
        match.scheduledAt && match.scheduledAt.startsWith(today)
    );
});

const upcomingMatches = computed(() => {
    const now = new Date();
    return matches.value.filter(match =>
        match.scheduledAt && new Date(match.scheduledAt) > now
    ).slice(0, 5);
});

const liveMatches = computed(() => {
    return matches.value.filter(match => match.status === 'in_progress');
});

const filteredMatches = computed(() => {
    let filtered = matches.value;

    if (selectedDate.value) {
        filtered = filtered.filter(match =>
            match.scheduledAt?.startsWith(selectedDate.value)
        );
    }

    if (selectedTable.value) {
        filtered = filtered.filter(match =>
            match.tableNumber === selectedTable.value
        );
    }

    return filtered.sort((a, b) =>
        new Date(a.scheduledAt || 0).getTime() - new Date(b.scheduledAt || 0).getTime()
    );
});

const scheduleStats = computed(() => {
    return {
        total: matches.value.length,
        scheduled: matches.value.filter(m => m.status === 'scheduled').length,
        inProgress: matches.value.filter(m => m.status === 'in_progress').length,
        completed: matches.value.filter(m => m.status === 'completed').length,
        conflicts: conflicts.value.length
    };
});

// Methods
const fetchSchedule = async () => {
    try {
        await tournamentStore.fetchTournament(props.tournamentId);
        await tournamentStore.fetchSchedule(props.tournamentId, selectedDate.value);
    } catch (error) {
        console.error('Failed to fetch schedule:', error);
    }
};

const generateAutoSchedule = async () => {
    if (!confirm(t('Generate automatic schedule? This will overwrite existing match times.'))) {
        return;
    }

    isGenerating.value = true;

    try {
        // Mock schedule generation
        await new Promise(resolve => setTimeout(resolve, 2000));

        // This would call the API to generate schedule
        console.log('Generating automatic schedule...');

        await fetchSchedule();
    } catch (error) {
        console.error('Failed to generate schedule:', error);
    } finally {
        isGenerating.value = false;
    }
};

const updateMatchTime = async (matchId: number, newTime: string, tableNumber?: number) => {
    try {
        await tournamentStore.updateMatchSchedule(props.tournamentId, matchId, {
            scheduledAt: newTime,
            tableNumber
        });
    } catch (error) {
        console.error('Failed to update match time:', error);
    }
};

const startMatch = async (matchId: number) => {
    try {
        await tournamentStore.updateMatchSchedule(props.tournamentId, matchId, {
            status: 'in_progress',
            started_at: new Date().toISOString()
        });
    } catch (error) {
        console.error('Failed to start match:', error);
    }
};

const pauseMatch = async (matchId: number) => {
    try {
        await tournamentStore.updateMatchSchedule(props.tournamentId, matchId, {
            status: 'scheduled'
        });
    } catch (error) {
        console.error('Failed to pause match:', error);
    }
};

const goBack = () => {
    router.visit(`/tournaments/${props.tournamentId}`);
};

const formatTime = (dateString: string): string => {
    return new Date(dateString).toLocaleTimeString([], {
        hour: '2-digit',
        minute: '2-digit'
    });
};

const formatDate = (dateString: string): string => {
    return new Date(dateString).toLocaleDateString();
};

onMounted(() => {
    fetchSchedule();
});
</script>

<template>
    <Head :title="t('Match Schedule')"/>

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
                        {{ t('Match Schedule') }}
                    </h1>
                    <p class="mt-2 text-gray-600 dark:text-gray-400">
                        {{ tournament?.name }}
                    </p>
                </div>

                <div class="flex items-center space-x-4">
                    <Button
                        :disabled="conflicts.length === 0"
                        variant="outline"
                        @click="showConflictsModal = true"
                    >
                        <SettingsIcon class="mr-2 h-4 w-4"/>
                        {{ t('Conflicts') }} ({{ conflicts.length }})
                    </Button>

                    <Button
                        :disabled="isGenerating"
                        @click="generateAutoSchedule"
                    >
                        <RefreshCwIcon :class="['mr-2 h-4 w-4', { 'animate-spin': isGenerating }]"/>
                        {{ isGenerating ? t('Generating...') : t('Auto Schedule') }}
                    </Button>
                </div>
            </div>

            <!-- Schedule Stats -->
            <div class="grid grid-cols-1 md:grid-cols-5 gap-6 mb-8">
                <Card>
                    <CardContent class="p-6 text-center">
                        <div class="text-3xl font-bold text-blue-600 dark:text-blue-400">
                            {{ scheduleStats.total }}
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">{{ t('Total Matches') }}</div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent class="p-6 text-center">
                        <div class="text-3xl font-bold text-yellow-600 dark:text-yellow-400">
                            {{ scheduleStats.scheduled }}
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">{{ t('Scheduled') }}</div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent class="p-6 text-center">
                        <div class="text-3xl font-bold text-green-600 dark:text-green-400">
                            {{ scheduleStats.inProgress }}
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">{{ t('Live') }}</div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent class="p-6 text-center">
                        <div class="text-3xl font-bold text-purple-600 dark:text-purple-400">
                            {{ scheduleStats.completed }}
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">{{ t('Completed') }}</div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent class="p-6 text-center">
                        <div class="text-3xl font-bold text-red-600 dark:text-red-400">
                            {{ scheduleStats.conflicts }}
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">{{ t('Conflicts') }}</div>
                    </CardContent>
                </Card>
            </div>

            <!-- Live Matches -->
            <div v-if="liveMatches.length > 0" class="mb-8">
                <Card class="border-green-200 dark:border-green-800">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2 text-green-700 dark:text-green-300">
                            <PlayIcon class="h-5 w-5 animate-pulse"/>
                            {{ t('Live Matches') }}
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <MatchCard
                                v-for="match in liveMatches"
                                :key="match.id"
                                :match="match"
                                :show-controls="true"
                                @pause="pauseMatch"
                                @start="startMatch"
                                @update-time="updateMatchTime"
                            />
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Filters and View Controls -->
            <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between space-y-4 sm:space-y-0">
                <div class="flex items-center space-x-4">
                    <!-- View Mode -->
                    <div class="flex items-center space-x-2">
                        <button
                            :class="[
                'px-3 py-2 text-sm font-medium rounded-md',
                viewMode === 'list'
                  ? 'bg-blue-600 text-white'
                  : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300'
              ]"
                            @click="viewMode = 'list'"
                        >
                            {{ t('List') }}
                        </button>
                        <button
                            :class="[
                'px-3 py-2 text-sm font-medium rounded-md',
                viewMode === 'calendar'
                  ? 'bg-blue-600 text-white'
                  : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300'
              ]"
                            @click="viewMode = 'calendar'"
                        >
                            <CalendarIcon class="mr-2 h-4 w-4 inline"/>
                            {{ t('Calendar') }}
                        </button>
                        <button
                            :class="[
                'px-3 py-2 text-sm font-medium rounded-md',
                viewMode === 'table'
                  ? 'bg-blue-600 text-white'
                  : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300'
              ]"
                            @click="viewMode = 'table'"
                        >
                            <TableIcon class="mr-2 h-4 w-4 inline"/>
                            {{ t('Tables') }}
                        </button>
                    </div>
                </div>

                <div class="flex items-center space-x-4">
                    <!-- Date Filter -->
                    <div class="flex items-center space-x-2">
                        <CalendarIcon class="h-4 w-4 text-gray-400"/>
                        <Input
                            v-model="selectedDate"
                            class="w-40"
                            type="date"
                        />
                    </div>

                    <!-- Table Filter -->
                    <Select v-model="selectedTable">
                        <SelectTrigger class="w-40">
                            <SelectValue :placeholder="t('All Tables')"/>
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem :value="null">{{ t('All Tables') }}</SelectItem>
                            <SelectItem
                                v-for="table in tables.filter(t => t.available)"
                                :key="table.id"
                                :value="table.id"
                            >
                                {{ table.name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>

                    <Button variant="outline" @click="fetchSchedule">
                        <RefreshCwIcon class="mr-2 h-4 w-4"/>
                        {{ t('Refresh') }}
                    </Button>
                </div>
            </div>

            <!-- Schedule Content -->
            <div>
                <!-- List View -->
                <div v-if="viewMode === 'list'" class="space-y-6">
                    <Card v-if="filteredMatches.length === 0">
                        <CardContent class="py-12 text-center">
                            <ClockIcon class="mx-auto h-12 w-12 text-gray-400 mb-4"/>
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">
                                {{ t('No matches scheduled') }}
                            </h3>
                            <p class="text-gray-600 dark:text-gray-400">
                                {{ t('Select a different date or generate an automatic schedule') }}
                            </p>
                        </CardContent>
                    </Card>

                    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <MatchCard
                            v-for="match in filteredMatches"
                            :key="match.id"
                            :match="match"
                            :show-controls="true"
                            @pause="pauseMatch"
                            @start="startMatch"
                            @update-time="updateMatchTime"
                        />
                    </div>
                </div>

                <!-- Calendar View -->
                <div v-else-if="viewMode === 'calendar'">
                    <MatchScheduleCalendar
                        :matches="matches"
                        :tables="tables"
                        @match-updated="fetchSchedule"
                        @date-selected="selectedDate = $event"
                    />
                </div>

                <!-- Table View -->
                <div v-else-if="viewMode === 'table'" class="space-y-6">
                    <div
                        v-for="table in tables.filter(t => t.available)"
                        :key="table.id"
                    >
                        <Card>
                            <CardHeader>
                                <CardTitle class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <TableIcon class="h-5 w-5"/>
                                        {{ table.name }}
                                        <span class="text-sm text-gray-500">({{ table.location }})</span>
                                    </div>
                                    <div class="text-sm text-gray-600 dark:text-gray-400">
                                        {{ filteredMatches.filter(m => m.tableNumber === table.id).length }}
                                        {{ t('matches') }}
                                    </div>
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div v-if="filteredMatches.filter(m => m.tableNumber === table.id).length === 0"
                                     class="text-center py-8 text-gray-500">
                                    {{ t('No matches scheduled for this table') }}
                                </div>

                                <div v-else class="space-y-3">
                                    <MatchCard
                                        v-for="match in filteredMatches.filter(m => m.tableNumber === table.id)"
                                        :key="match.id"
                                        :compact="true"
                                        :match="match"
                                        :show-controls="true"
                                        @pause="pauseMatch"
                                        @start="startMatch"
                                        @update-time="updateMatchTime"
                                    />
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>

            <!-- Schedule Conflicts Modal -->
            <Modal
                :show="showConflictsModal"
                :title="t('Schedule Conflicts')"
                @close="showConflictsModal = false"
            >
                <ScheduleConflicts
                    :conflicts="conflicts"
                    :matches="matches"
                    @resolve-conflict="fetchSchedule"
                />

                <template #footer>
                    <Button @click="showConflictsModal = false">
                        {{ t('Close') }}
                    </Button>
                </template>
            </Modal>
        </div>
    </div>
</template>
