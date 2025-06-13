<!-- resources/js/Components/Tournament/MatchScheduleCalendar.vue -->
<script lang="ts" setup>
import {computed, onMounted, ref} from 'vue';
import {Button, Card, CardContent, CardHeader, CardTitle} from '@/Components/ui';
import {useLocale} from '@/composables/useLocale';
import {CalendarIcon, ChevronLeftIcon, ChevronRightIcon, ClockIcon, PauseIcon, PlayIcon} from 'lucide-vue-next';

interface Match {
    id: number;
    scheduledAt: string;
    tableNumber?: number;
    playerA: any;
    playerB: any;
    status: string;
    scoreA?: number;
    scoreB?: number;
}

interface Table {
    id: number;
    name: string;
    location?: string;
    available: boolean;
}

interface Props {
    matches: Match[];
    tables: Table[];
}

const props = defineProps<Props>();
const emit = defineEmits<{
    'match-updated': [];
    'date-selected': [date: string];
}>();

const {t} = useLocale();

// State
const currentDate = ref(new Date());
const selectedDate = ref<string | null>(null);
const viewMode = ref<'month' | 'week' | 'day'>('week');

// Computed
const currentMonth = computed(() => {
    return currentDate.value.toLocaleDateString('uk-UA', {
        month: 'long',
        year: 'numeric'
    });
});

const weekDays = computed(() => {
    const start = getWeekStart(currentDate.value);
    const days = [];

    for (let i = 0; i < 7; i++) {
        const date = new Date(start);
        date.setDate(start.getDate() + i);
        days.push({
            date: date.toISOString().split('T')[0],
            dayName: date.toLocaleDateString('uk-UA', {weekday: 'short'}),
            dayNumber: date.getDate(),
            isToday: isToday(date),
            isSelected: selectedDate.value === date.toISOString().split('T')[0]
        });
    }

    return days;
});

const timeSlots = computed(() => {
    const slots = [];
    for (let hour = 9; hour <= 21; hour++) {
        slots.push({
            time: `${hour.toString().padStart(2, '0')}:00`,
            hour
        });
    }
    return slots;
});

const matchesByDateAndTime = computed(() => {
    const grouped: Record<string, Record<string, Match[]>> = {};

    props.matches.forEach(match => {
        if (!match.scheduledAt) return;

        const date = match.scheduledAt.split('T')[0];
        const time = match.scheduledAt.split('T')[1]?.substring(0, 5) || '09:00';
        const hour = time.split(':')[0] + ':00';

        if (!grouped[date]) grouped[date] = {};
        if (!grouped[date][hour]) grouped[date][hour] = [];

        grouped[date][hour].push(match);
    });

    return grouped;
});

const todaysMatches = computed(() => {
    const today = new Date().toISOString().split('T')[0];
    return props.matches.filter(match =>
        match.scheduledAt?.startsWith(today)
    );
});

// Methods
const getWeekStart = (date: Date): Date => {
    const start = new Date(date);
    const day = start.getDay();
    const diff = start.getDate() - day + (day === 0 ? -6 : 1); // Monday start
    start.setDate(diff);
    start.setHours(0, 0, 0, 0);
    return start;
};

const isToday = (date: Date): boolean => {
    const today = new Date();
    return date.toDateString() === today.toDateString();
};

const navigateWeek = (direction: 'prev' | 'next') => {
    const newDate = new Date(currentDate.value);
    newDate.setDate(newDate.getDate() + (direction === 'next' ? 7 : -7));
    currentDate.value = newDate;
};

const selectDate = (date: string) => {
    selectedDate.value = date;
    emit('date-selected', date);
};

const getMatchesForDateAndTime = (date: string, time: string): Match[] => {
    return matchesByDateAndTime.value[date]?.[time] || [];
};

const getMatchStatusColor = (status: string): string => {
    switch (status) {
        case 'scheduled':
            return 'bg-blue-500';
        case 'in_progress':
            return 'bg-green-500';
        case 'completed':
            return 'bg-gray-500';
        case 'cancelled':
            return 'bg-red-500';
        default:
            return 'bg-gray-400';
    }
};

const formatTime = (dateString: string): string => {
    return new Date(dateString).toLocaleTimeString([], {
        hour: '2-digit',
        minute: '2-digit'
    });
};

const getPlayerName = (player: any): string => {
    if (!player) return t('TBD');
    return player.name || `${player.firstname || ''} ${player.lastname || ''}`.trim() || t('Unknown');
};

onMounted(() => {
    // Set initial selected date to today
    selectDate(new Date().toISOString().split('T')[0]);
});
</script>

<template>
    <div class="space-y-6">
        <!-- Calendar Header -->
        <Card>
            <CardHeader>
                <div class="flex items-center justify-between">
                    <CardTitle class="flex items-center gap-2">
                        <CalendarIcon class="h-5 w-5"/>
                        {{ t('Match Calendar') }}
                    </CardTitle>

                    <div class="flex items-center space-x-4">
                        <!-- View Mode Selector -->
                        <div class="flex rounded-lg border border-gray-200 dark:border-gray-700">
                            <button
                                :class="[
                  'px-3 py-1 text-sm',
                  viewMode === 'week'
                    ? 'bg-blue-600 text-white'
                    : 'text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800'
                ]"
                                @click="viewMode = 'week'"
                            >
                                {{ t('Week') }}
                            </button>
                            <button
                                :class="[
                  'px-3 py-1 text-sm',
                  viewMode === 'day'
                    ? 'bg-blue-600 text-white'
                    : 'text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800'
                ]"
                                @click="viewMode = 'day'"
                            >
                                {{ t('Day') }}
                            </button>
                        </div>

                        <!-- Navigation -->
                        <div class="flex items-center space-x-2">
                            <Button size="sm" variant="outline" @click="navigateWeek('prev')">
                                <ChevronLeftIcon class="h-4 w-4"/>
                            </Button>
                            <span class="font-medium text-gray-900 dark:text-gray-100 min-w-[120px] text-center">
                {{ currentMonth }}
              </span>
                            <Button size="sm" variant="outline" @click="navigateWeek('next')">
                                <ChevronRightIcon class="h-4 w-4"/>
                            </Button>
                        </div>
                    </div>
                </div>
            </CardHeader>
        </Card>

        <!-- Week View -->
        <Card v-if="viewMode === 'week'">
            <CardContent class="p-0">
                <div class="grid grid-cols-8 border-b border-gray-200 dark:border-gray-700">
                    <!-- Time column header -->
                    <div class="p-4 border-r border-gray-200 dark:border-gray-700">
                        <ClockIcon class="h-4 w-4 text-gray-400"/>
                    </div>

                    <!-- Day headers -->
                    <div
                        v-for="day in weekDays"
                        :key="day.date"
                        :class="[
              'p-4 text-center border-r border-gray-200 dark:border-gray-700 cursor-pointer transition-colors',
              day.isSelected ? 'bg-blue-50 dark:bg-blue-900/20' : 'hover:bg-gray-50 dark:hover:bg-gray-800'
            ]"
                        @click="selectDate(day.date)"
                    >
                        <div class="font-medium">{{ day.dayName }}</div>
                        <div
                            :class="[
                'text-lg font-bold mt-1',
                day.isToday ? 'text-blue-600 dark:text-blue-400' : 'text-gray-900 dark:text-gray-100'
              ]"
                        >
                            {{ day.dayNumber }}
                        </div>
                    </div>
                </div>

                <!-- Time slots grid -->
                <div class="max-h-96 overflow-y-auto">
                    <div
                        v-for="slot in timeSlots"
                        :key="slot.time"
                        class="grid grid-cols-8 border-b border-gray-100 dark:border-gray-800"
                    >
                        <!-- Time label -->
                        <div
                            class="p-2 text-sm text-gray-600 dark:text-gray-400 border-r border-gray-200 dark:border-gray-700 text-center">
                            {{ slot.time }}
                        </div>

                        <!-- Day cells -->
                        <div
                            v-for="day in weekDays"
                            :key="`${day.date}-${slot.time}`"
                            class="p-1 border-r border-gray-200 dark:border-gray-700 min-h-[60px]"
                        >
                            <div
                                v-for="match in getMatchesForDateAndTime(day.date, slot.time)"
                                :key="match.id"
                                :class="[
                  'p-2 rounded text-xs text-white mb-1 cursor-pointer hover:opacity-80',
                  getMatchStatusColor(match.status)
                ]"
                                @click="$emit('match-updated')"
                            >
                                <div class="font-medium truncate">
                                    {{ getPlayerName(match.playerA) }} vs {{ getPlayerName(match.playerB) }}
                                </div>
                                <div class="flex items-center justify-between mt-1">
                  <span v-if="match.tableNumber" class="text-xs opacity-75">
                    T{{ match.tableNumber }}
                  </span>
                                    <span class="text-xs">
                    {{ formatTime(match.scheduledAt) }}
                  </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Day View -->
        <Card v-else-if="viewMode === 'day' && selectedDate">
            <CardHeader>
                <CardTitle>
                    {{
                        new Date(selectedDate + 'T00:00:00').toLocaleDateString('uk-UA', {
                            weekday: 'long',
                            year: 'numeric',
                            month: 'long',
                            day: 'numeric'
                        })
                    }}
                </CardTitle>
            </CardHeader>
            <CardContent>
                <div class="space-y-4">
                    <div
                        v-for="slot in timeSlots"
                        :key="slot.time"
                        class="flex"
                    >
                        <div class="w-16 text-sm text-gray-600 dark:text-gray-400 py-2">
                            {{ slot.time }}
                        </div>
                        <div class="flex-1 min-h-[60px] border-l border-gray-200 dark:border-gray-700 pl-4">
                            <div
                                v-for="match in getMatchesForDateAndTime(selectedDate, slot.time)"
                                :key="match.id"
                                class="p-3 border border-gray-200 dark:border-gray-700 rounded-lg mb-2 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors"
                            >
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-4">
                                        <div
                                            :class="[
                        'w-3 h-3 rounded-full',
                        getMatchStatusColor(match.status)
                      ]"
                                        />
                                        <div>
                                            <div class="font-medium">
                                                {{ getPlayerName(match.playerA) }} vs {{ getPlayerName(match.playerB) }}
                                            </div>
                                            <div class="text-sm text-gray-600 dark:text-gray-400">
                                                {{ formatTime(match.scheduledAt) }}
                                                <span v-if="match.tableNumber" class="ml-2">
                          • {{ t('Table') }} {{ match.tableNumber }}
                        </span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="flex items-center space-x-2">
                    <span v-if="match.scoreA !== undefined && match.scoreB !== undefined" class="text-sm font-bold">
                      {{ match.scoreA }} - {{ match.scoreB }}
                    </span>

                                        <Button
                                            v-if="match.status === 'scheduled'"
                                            size="sm"
                                            variant="outline"
                                        >
                                            <PlayIcon class="h-3 w-3 mr-1"/>
                                            {{ t('Start') }}
                                        </Button>

                                        <Button
                                            v-else-if="match.status === 'in_progress'"
                                            size="sm"
                                            variant="outline"
                                        >
                                            <PauseIcon class="h-3 w-3 mr-1"/>
                                            {{ t('Pause') }}
                                        </Button>
                                    </div>
                                </div>
                            </div>

                            <div v-if="getMatchesForDateAndTime(selectedDate, slot.time).length === 0"
                                 class="text-gray-400 text-sm py-4">
                                {{ t('No matches scheduled') }}
                            </div>
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Quick Stats -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <Card>
                <CardContent class="p-6 text-center">
                    <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                        {{ todaysMatches.length }}
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">{{ t("Today's Matches") }}</div>
                </CardContent>
            </Card>

            <Card>
                <CardContent class="p-6 text-center">
                    <div class="text-2xl font-bold text-green-600 dark:text-green-400">
                        {{ todaysMatches.filter(m => m.status === 'in_progress').length }}
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">{{ t('Live Now') }}</div>
                </CardContent>
            </Card>

            <Card>
                <CardContent class="p-6 text-center">
                    <div class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">
                        {{ todaysMatches.filter(m => m.status === 'scheduled').length }}
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">{{ t('Upcoming') }}</div>
                </CardContent>
            </Card>

            <Card>
                <CardContent class="p-6 text-center">
                    <div class="text-2xl font-bold text-gray-600 dark:text-gray-400">
                        {{ todaysMatches.filter(m => m.status === 'completed').length }}
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">{{ t('Completed') }}</div>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
