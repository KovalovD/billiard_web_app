<!-- resources/js/Components/Tournament/Match/MatchSchedule.vue -->
<script lang="ts" setup>
import {Badge, Button, Card, CardContent, CardHeader, CardTitle, Input, Label, Modal} from '@/Components/ui';
import DataTable from '@/Components/ui/data-table/DataTable.vue';
import {useLocale} from '@/composables/useLocale';
import type {TournamentMatch} from '@/types/tournament';
import {Calendar, Clock, Edit2, MapPin, Play, XCircle} from 'lucide-vue-next';
import {computed, ref} from 'vue';

const props = defineProps<{
    matches: TournamentMatch[];
    clubs?: any[];
    canEdit?: boolean;
}>();

const emit = defineEmits<{
    'update-match': [matchId: number, data: any];
    'start-match': [matchId: number];
    'cancel-match': [matchId: number, reason?: string];
    'reschedule-match': [matchId: number, data: { scheduled_at: string; table_number?: number; club_id?: number }];
    'bulk-reschedule': [matches: any[]];
}>();

const {t} = useLocale();

const showRescheduleModal = ref(false);
const showBulkRescheduleModal = ref(false);
const selectedMatch = ref<TournamentMatch | null>(null);
const selectedMatches = ref<number[]>([]);

const rescheduleForm = ref({
    scheduled_at: '',
    table_number: undefined as number | undefined,
    club_id: undefined as number | undefined
});

const bulkRescheduleDate = ref('');
const bulkStartTime = ref('09:00');
const matchDuration = ref(30); // minutes

const filteredStatus = ref<string>('all');
const filteredRound = ref<number | null>(null);

const statusFilters = [
    {value: 'all', label: t('All Matches')},
    {value: 'pending', label: t('Pending')},
    {value: 'in_progress', label: t('In Progress')},
    {value: 'completed', label: t('Completed')},
    {value: 'cancelled', label: t('Cancelled')}
];

const uniqueRounds = computed(() => {
    const rounds = new Set(props.matches.map(m => m.round_number));
    return Array.from(rounds).sort();
});

const filteredMatches = computed(() => {
    return props.matches.filter(match => {
        if (filteredStatus.value !== 'all' && match.status !== filteredStatus.value) {
            return false;
        }
        if (filteredRound.value !== null && match.round_number !== filteredRound.value) {
            return false;
        }
        return true;
    });
});

const matchesByDate = computed(() => {
    const grouped = new Map<string, TournamentMatch[]>();

    filteredMatches.value.forEach(match => {
        if (match.scheduled_at) {
            const date = new Date(match.scheduled_at).toDateString();
            if (!grouped.has(date)) {
                grouped.set(date, []);
            }
            grouped.get(date)!.push(match);
        }
    });

    // Sort by time within each date
    grouped.forEach(matches => {
        matches.sort((a, b) =>
            new Date(a.scheduled_at!).getTime() - new Date(b.scheduled_at!).getTime()
        );
    });

    return grouped;
});

const columns = computed(() => [
    {
        key: 'select',
        label: '',
        width: '40px',
        render: (match: TournamentMatch) => ({id: match.id})
    },
    {
        key: 'match',
        label: t('Match'),
        render: (match: TournamentMatch) => ({
            display_name: match.display_name,
            match_type: match.match_type,
            round: match.round_number
        })
    },
    {
        key: 'participants',
        label: t('Participants'),
        render: (match: TournamentMatch) => ({
            p1: match.participant_1_name,
            p2: match.participant_2_name
        })
    },
    {
        key: 'time',
        label: t('Time'),
        render: (match: TournamentMatch) => ({
            scheduled_at: match.scheduled_at,
            table_number: match.table_number
        })
    },
    {
        key: 'status',
        label: t('Status'),
        align: 'center' as const,
        render: (match: TournamentMatch) => ({
            status: match.status,
            status_display: match.status_display
        })
    },
    {
        key: 'actions',
        label: t('Actions'),
        align: 'right' as const,
        width: '120px'
    }
]);

const getStatusClass = (status: string) => {
    switch (status) {
        case 'pending':
            return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
        case 'in_progress':
            return 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300';
        case 'completed':
            return 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300';
        case 'cancelled':
            return 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300';
        default:
            return 'bg-gray-100 text-gray-800';
    }
};

const formatTime = (dateString: string) => {
    return new Date(dateString).toLocaleTimeString('en-US', {
        hour: '2-digit',
        minute: '2-digit'
    });
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
};

const openRescheduleModal = (match: TournamentMatch) => {
    selectedMatch.value = match;
    rescheduleForm.value = {
        scheduled_at: match.scheduled_at ? match.scheduled_at.slice(0, 16) : '',
        table_number: match.table_number,
        club_id: match.club?.id
    };
    showRescheduleModal.value = true;
};

const handleReschedule = () => {
    if (selectedMatch.value && rescheduleForm.value.scheduled_at) {
        emit('reschedule-match', selectedMatch.value.id, rescheduleForm.value);
        showRescheduleModal.value = false;
    }
};

const handleBulkReschedule = () => {
    if (!bulkRescheduleDate.value || selectedMatches.value.length === 0) return;

    const matches = selectedMatches.value.map((matchId, index) => {
        const startTime = new Date(`${bulkRescheduleDate.value}T${bulkStartTime.value}`);
        startTime.setMinutes(startTime.getMinutes() + (index * matchDuration.value));

        return {
            match_id: matchId,
            scheduled_at: startTime.toISOString()
        };
    });

    emit('bulk-reschedule', matches);
    showBulkRescheduleModal.value = false;
    selectedMatches.value = [];
};

const toggleMatchSelection = (matchId: number) => {
    const index = selectedMatches.value.indexOf(matchId);
    if (index > -1) {
        selectedMatches.value.splice(index, 1);
    } else {
        selectedMatches.value.push(matchId);
    }
};

const selectAllInDate = (matches: TournamentMatch[]) => {
    const matchIds = matches.map(m => m.id);
    const allSelected = matchIds.every(id => selectedMatches.value.includes(id));

    if (allSelected) {
        selectedMatches.value = selectedMatches.value.filter(id => !matchIds.includes(id));
    } else {
        matchIds.forEach(id => {
            if (!selectedMatches.value.includes(id)) {
                selectedMatches.value.push(id);
            }
        });
    }
};
</script>

<template>
    <div class="space-y-6">
        <!-- Filters -->
        <Card>
            <CardContent class="pt-6">
                <div class="flex flex-wrap gap-4">
                    <div class="flex-1 min-w-[200px]">
                        <Label>{{ t('Status') }}</Label>
                        <select
                            v-model="filteredStatus"
                            class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        >
                            <option v-for="filter in statusFilters" :key="filter.value" :value="filter.value">
                                {{ filter.label }}
                            </option>
                        </select>
                    </div>

                    <div class="flex-1 min-w-[200px]">
                        <Label>{{ t('Round') }}</Label>
                        <select
                            v-model="filteredRound"
                            class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        >
                            <option :value="null">{{ t('All Rounds') }}</option>
                            <option v-for="round in uniqueRounds" :key="round" :value="round">
                                {{ t('Round :number', {number: round}) }}
                            </option>
                        </select>
                    </div>

                    <div v-if="canEdit && selectedMatches.length > 0" class="flex items-end">
                        <Button variant="outline" @click="showBulkRescheduleModal = true">
                            <Calendar class="mr-2 h-4 w-4"/>
                            {{ t('Bulk Reschedule (:count)', {count: selectedMatches.length}) }}
                        </Button>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Schedule by Date -->
        <div v-if="matchesByDate.size > 0" class="space-y-6">
            <div v-for="[date, matches] in matchesByDate" :key="date">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                        {{ formatDate(date) }}
                    </h3>
                    <Button
                        v-if="canEdit"
                        size="sm"
                        variant="outline"
                        @click="selectAllInDate(matches)"
                    >
                        {{ t('Select All') }}
                    </Button>
                </div>

                <DataTable
                    :columns="columns"
                    :compact-mode="true"
                    :data="matches"
                    class="mb-6"
                >
                    <template #cell-select="{ item }">
                        <input
                            v-if="canEdit && item.status === 'pending'"
                            :checked="selectedMatches.includes(item.id)"
                            class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                            type="checkbox"
                            @change="toggleMatchSelection(item.id)"
                        />
                    </template>

                    <template #cell-match="{ value }">
                        <div>
                            <p class="font-medium">{{ value.display_name }}</p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                {{
                                    value.match_type === 'group' ? t('Group Stage') : t('Round :number', {number: value.round})
                                }}
                            </p>
                        </div>
                    </template>

                    <template #cell-participants="{ value }">
                        <div class="text-sm">
                            <div>{{ value.p1 || t('TBD') }}</div>
                            <div class="text-gray-500">vs</div>
                            <div>{{ value.p2 || t('TBD') }}</div>
                        </div>
                    </template>

                    <template #cell-time="{ value }">
                        <div class="text-sm">
                            <div class="flex items-center gap-1">
                                <Clock class="h-4 w-4 text-gray-400"/>
                                {{ value.scheduled_at ? formatTime(value.scheduled_at) : t('Not scheduled') }}
                            </div>
                            <div v-if="value.table_number"
                                 class="flex items-center gap-1 text-gray-600 dark:text-gray-400">
                                <MapPin class="h-3 w-3"/>
                                {{ t('Table :number', {number: value.table_number}) }}
                            </div>
                        </div>
                    </template>

                    <template #cell-status="{ value }">
                        <Badge :class="getStatusClass(value.status)">
                            {{ value.status_display }}
                        </Badge>
                    </template>

                    <template #cell-actions="{ item }">
                        <div v-if="canEdit" class="flex justify-end space-x-1">
                            <Button
                                v-if="item.status === 'pending'"
                                size="sm"
                                variant="ghost"
                                @click="openRescheduleModal(item)"
                            >
                                <Edit2 class="h-4 w-4"/>
                            </Button>
                            <Button
                                v-if="item.status === 'pending' && item.scheduled_at"
                                size="sm"
                                variant="ghost"
                                @click="emit('start-match', item.id)"
                            >
                                <Play class="h-4 w-4"/>
                            </Button>
                            <Button
                                v-if="item.status !== 'completed'"
                                class="text-red-600 hover:text-red-700"
                                size="sm"
                                variant="ghost"
                                @click="emit('cancel-match', item.id)"
                            >
                                <XCircle class="h-4 w-4"/>
                            </Button>
                        </div>
                    </template>
                </DataTable>
            </div>
        </div>

        <!-- Unscheduled Matches -->
        <Card v-if="filteredMatches.some(m => !m.scheduled_at)">
            <CardHeader>
                <CardTitle>{{ t('Unscheduled Matches') }}</CardTitle>
            </CardHeader>
            <CardContent>
                <div class="space-y-2">
                    <div
                        v-for="match in filteredMatches.filter(m => !m.scheduled_at)"
                        :key="match.id"
                        class="flex items-center justify-between p-3 border rounded-lg"
                    >
                        <div>
                            <p class="font-medium">{{ match.display_name }}</p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                {{ match.participant_1_name }} vs {{ match.participant_2_name }}
                            </p>
                        </div>
                        <Button
                            v-if="canEdit"
                            size="sm"
                            @click="openRescheduleModal(match)"
                        >
                            <Calendar class="mr-2 h-4 w-4"/>
                            {{ t('Schedule') }}
                        </Button>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Reschedule Modal -->
        <Modal :show="showRescheduleModal" :title="t('Reschedule Match')" @close="showRescheduleModal = false">
            <div class="space-y-4">
                <div>
                    <Label for="scheduled_at">{{ t('Date & Time') }}</Label>
                    <Input
                        id="scheduled_at"
                        v-model="rescheduleForm.scheduled_at"
                        required
                        type="datetime-local"
                    />
                </div>

                <div>
                    <Label for="table_number">{{ t('Table Number') }}</Label>
                    <Input
                        id="table_number"
                        v-model.number="rescheduleForm.table_number"
                        min="1"
                        type="number"
                    />
                </div>

                <div v-if="clubs && clubs.length > 0">
                    <Label for="club_id">{{ t('Club') }}</Label>
                    <select
                        id="club_id"
                        v-model="rescheduleForm.club_id"
                        class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    >
                        <option :value="undefined">{{ t('Select Club') }}</option>
                        <option v-for="club in clubs" :key="club.id" :value="club.id">
                            {{ club.name }}
                        </option>
                    </select>
                </div>
            </div>

            <template #footer>
                <Button variant="outline" @click="showRescheduleModal = false">
                    {{ t('Cancel') }}
                </Button>
                <Button @click="handleReschedule">
                    {{ t('Reschedule') }}
                </Button>
            </template>
        </Modal>

        <!-- Bulk Reschedule Modal -->
        <Modal :show="showBulkRescheduleModal" :title="t('Bulk Reschedule Matches')"
               @close="showBulkRescheduleModal = false">
            <div class="space-y-4">
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    {{
                        t('Schedule :count matches starting from the specified date and time.', {count: selectedMatches.length})
                    }}
                </p>

                <div>
                    <Label for="bulk_date">{{ t('Start Date') }}</Label>
                    <Input
                        id="bulk_date"
                        v-model="bulkRescheduleDate"
                        required
                        type="date"
                    />
                </div>

                <div>
                    <Label for="bulk_time">{{ t('Start Time') }}</Label>
                    <Input
                        id="bulk_time"
                        v-model="bulkStartTime"
                        required
                        type="time"
                    />
                </div>

                <div>
                    <Label for="match_duration">{{ t('Minutes Between Matches') }}</Label>
                    <Input
                        id="match_duration"
                        v-model.number="matchDuration"
                        max="120"
                        min="10"
                        step="5"
                        type="number"
                    />
                </div>
            </div>

            <template #footer>
                <Button variant="outline" @click="showBulkRescheduleModal = false">
                    {{ t('Cancel') }}
                </Button>
                <Button @click="handleBulkReschedule">
                    {{ t('Schedule Matches') }}
                </Button>
            </template>
        </Modal>
    </div>
</template>
