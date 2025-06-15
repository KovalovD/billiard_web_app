<template>
    <div class="w-full h-full flex flex-col bg-gray-50 dark:bg-gray-900">
        <!-- Header with controls -->
        <div class="flex items-center justify-between p-4 bg-white dark:bg-gray-800 shadow-sm">
            <div class="flex items-center gap-4">
                <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">Match Schedule</h2>
                <div class="flex items-center gap-2">
                    <button
                        class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
                        @click="previousDay"
                    >
                        <ChevronLeftIcon class="w-5 h-5"/>
                    </button>
                    <div class="px-3 py-1 bg-gray-100 dark:bg-gray-700 rounded-lg">
                        <span class="font-medium">{{ formatDate(currentDate) }}</span>
                    </div>
                    <button
                        class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
                        @click="nextDay"
                    >
                        <ChevronRightIcon class="w-5 h-5"/>
                    </button>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <button
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center gap-2"
                    @click="autoSchedule"
                >
                    <CalendarIcon class="w-4 h-4"/>
                    Auto Schedule
                </button>
                <button
                    :class="{ 'bg-red-100 dark:bg-red-900/20 border-red-300 dark:border-red-700': hasConflicts }"
                    class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                    @click="showConflicts = !showConflicts"
                >
                    <AlertCircleIcon :class="{ 'text-red-600': hasConflicts }" class="w-4 h-4"/>
                </button>
            </div>
        </div>

        <!-- Schedule Grid -->
        <div class="flex-1 overflow-auto">
            <div class="min-w-max">
                <!-- Time slots header -->
                <div class="sticky top-0 z-10 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                    <div
                        class="grid grid-cols-[120px_repeat(var(--table-count),1fr)] gap-px bg-gray-200 dark:bg-gray-700">
                        <div class="bg-white dark:bg-gray-800 p-3 font-medium text-center">Time</div>
                        <div
                            v-for="table in tables"
                            :key="table.id"
                            class="bg-white dark:bg-gray-800 p-3 font-medium text-center"
                        >
                            {{ table.name }}
                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                {{ table.cloth_speed }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Time slots body -->
                <div class="grid grid-cols-[120px_repeat(var(--table-count),1fr)] gap-px bg-gray-200 dark:bg-gray-700">
                    <!-- Time column -->
                    <div class="bg-gray-50 dark:bg-gray-900">
                        <div
                            v-for="slot in timeSlots"
                            :key="slot.time"
                            class="h-20 border-b border-gray-200 dark:border-gray-700 p-2 text-sm font-medium text-gray-600 dark:text-gray-400"
                        >
                            {{ slot.label }}
                        </div>
                    </div>

                    <!-- Table columns -->
                    <div
                        v-for="table in tables"
                        :key="`col-${table.id}`"
                        class="bg-white dark:bg-gray-800 relative"
                    >
                        <div
                            v-for="slot in timeSlots"
                            :key="`${table.id}-${slot.time}`"
                            :class="{
                'bg-red-50 dark:bg-red-900/10': getConflict(table.id, slot.time) && showConflicts,
                'bg-blue-50 dark:bg-blue-900/10': isDragOver === `${table.id}-${slot.time}`
              }"
                            class="h-20 border-b border-gray-200 dark:border-gray-700 relative"
                            @drop="handleDrop($event, table.id, slot.time)"
                            @dragover.prevent
                            @dragenter.prevent
                        >
                            <!-- Scheduled match -->
                            <div
                                v-if="getMatch(table.id, slot.time)"
                                :class="getMatchClass(getMatch(table.id, slot.time)!)"
                                :draggable="true"
                                class="absolute inset-1 p-2 rounded-lg cursor-move transition-all hover:shadow-lg"
                                @dragend="endDrag"
                                @dragstart="startDrag($event, getMatch(table.id, slot.time)!)"
                            >
                                <div class="text-xs font-semibold mb-1">
                                    Match #{{
                                        getMatch(table.id, slot.time)!.metadata?.match_number || getMatch(table.id, slot.time)!.id
                                    }}
                                </div>
                                <div class="text-xs text-gray-600 dark:text-gray-400">
                                    {{ getParticipantNames(getMatch(table.id, slot.time)!) }}
                                </div>
                                <div class="text-xs mt-1 flex items-center gap-1">
                  <span
                      :class="getStatusDot(getMatch(table.id, slot.time)!)"
                      class="w-2 h-2 rounded-full"
                  ></span>
                                    <span class="capitalize">{{ getMatch(table.id, slot.time)!.status }}</span>
                                </div>
                            </div>

                            <!-- Conflict indicator -->
                            <div
                                v-if="getConflict(table.id, slot.time)"
                                class="absolute top-1 right-1"
                            >
                                <AlertTriangleIcon
                                    :title="getConflict(table.id, slot.time)"
                                    class="w-4 h-4 text-red-600 animate-pulse"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Unscheduled matches drawer -->
        <div
            v-if="unscheduledMatches.length > 0"
            class="border-t border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4"
        >
            <h3 class="font-medium mb-3">Unscheduled Matches ({{ unscheduledMatches.length }})</h3>
            <div class="flex gap-2 overflow-x-auto pb-2">
                <div
                    v-for="match in unscheduledMatches"
                    :key="match.id"
                    :draggable="true"
                    class="flex-shrink-0 p-3 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 rounded-lg cursor-move hover:shadow-md transition-shadow"
                    @dragstart="startDrag($event, match)"
                >
                    <div class="text-sm font-semibold mb-1">
                        Match #{{ match.metadata?.match_number || match.id }}
                    </div>
                    <div class="text-xs text-gray-600 dark:text-gray-400">
                        {{ getParticipantNames(match) }}
                    </div>
                    <div class="text-xs mt-1 text-gray-500">
                        Round {{ match.round }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Conflict Modal -->
        <Teleport to="body">
            <div
                v-if="showConflictModal"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
                @click="showConflictModal = false"
            >
                <div
                    class="bg-white dark:bg-gray-800 rounded-lg shadow-xl p-6 max-w-md w-full mx-4"
                    @click.stop
                >
                    <h3 class="text-lg font-semibold mb-4 text-red-600">Schedule Conflict Detected</h3>
                    <p class="text-gray-600 dark:text-gray-400 mb-4">{{ conflictMessage }}</p>
                    <div class="flex justify-end gap-2">
                        <button
                            class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700"
                            @click="cancelReschedule"
                        >
                            Cancel
                        </button>
                        <button
                            class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700"
                            @click="confirmReschedule"
                        >
                            Override & Schedule
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>

<script lang="ts" setup>
import {computed, onMounted, ref, watch} from 'vue';
import {AlertCircleIcon, AlertTriangleIcon, CalendarIcon, ChevronLeftIcon, ChevronRightIcon} from 'lucide-vue-next';

// Types
interface Match {
    id: number;
    stage_id: number;
    table_id?: number;
    round: number;
    bracket: string;
    scheduled_at?: string;
    status: string;
    metadata: {
        match_number?: number;
        participant1_id?: number;
        participant2_id?: number;
        [key: string]: any;
    };
}

interface PoolTable {
    id: number;
    name: string;
    cloth_speed: string;
}

interface TimeSlot {
    time: string;
    label: string;
}

interface Participant {
    id: number;
    display_name: string;
}

// Props & Emits
const props = defineProps<{
    matches: Match[];
    tables: PoolTable[];
    participants: Participant[];
    startTime?: string;
    endTime?: string;
    slotDuration?: number;
}>();

const emit = defineEmits<{
    reschedule: [payload: { matchId: number; tableId: number; newStart: string }];
    'auto-schedule': [date: string];
}>();

// State
const currentDate = ref(new Date());
const draggedMatch = ref<Match | null>(null);
const isDragOver = ref<string | null>(null);
const showConflicts = ref(false);
const showConflictModal = ref(false);
const conflictMessage = ref('');
const pendingReschedule = ref<{ match: Match; tableId: number; time: string } | null>(null);

// Constants
const START_HOUR = 9;
const END_HOUR = 22;
const SLOT_DURATION = props.slotDuration || 45; // minutes

// Computed
const timeSlots = computed<TimeSlot[]>(() => {
    const slots: TimeSlot[] = [];
    const start = new Date(currentDate.value);
    start.setHours(START_HOUR, 0, 0, 0);

    const end = new Date(currentDate.value);
    end.setHours(END_HOUR, 0, 0, 0);

    let current = new Date(start);
    while (current < end) {
        slots.push({
            time: current.toISOString(),
            label: formatTime(current)
        });
        current = new Date(current.getTime() + SLOT_DURATION * 60000);
    }

    return slots;
});

const scheduledMatches = computed(() => {
    const dateStr = formatDateOnly(currentDate.value);
    return props.matches.filter(match => {
        if (!match.scheduled_at) return false;
        return formatDateOnly(new Date(match.scheduled_at)) === dateStr;
    });
});

const unscheduledMatches = computed(() => {
    return props.matches.filter(match =>
        !match.scheduled_at && match.status === 'pending'
    );
});

const hasConflicts = computed(() => {
    return scheduledMatches.value.some(match => {
        const conflicts = findConflicts(match);
        return conflicts.length > 0;
    });
});

const participantMap = computed(() => {
    const map = new Map<number, Participant>();
    props.participants.forEach(p => map.set(p.id, p));
    return map;
});

// Methods
function formatDate(date: Date): string {
    return date.toLocaleDateString('en-US', {
        weekday: 'short',
        month: 'short',
        day: 'numeric',
        year: 'numeric'
    });
}

function formatDateOnly(date: Date): string {
    return date.toISOString().split('T')[0];
}

function formatTime(date: Date): string {
    return date.toLocaleTimeString('en-US', {
        hour: 'numeric',
        minute: '2-digit',
        hour12: true
    });
}

function previousDay() {
    currentDate.value = new Date(currentDate.value.getTime() - 24 * 60 * 60 * 1000);
}

function nextDay() {
    currentDate.value = new Date(currentDate.value.getTime() + 24 * 60 * 60 * 1000);
}

function getMatch(tableId: number, time: string): Match | null {
    const targetTime = new Date(time);

    return scheduledMatches.value.find(match => {
        if (match.table_id !== tableId) return false;
        if (!match.scheduled_at) return false;

        const matchTime = new Date(match.scheduled_at);
        const timeDiff = Math.abs(matchTime.getTime() - targetTime.getTime());

        // Match if within 5 minutes of slot start
        return timeDiff < 5 * 60 * 1000;
    }) || null;
}

function getMatchClass(match: Match): string {
    const baseClass = 'border';

    switch (match.status) {
        case 'finished':
            return `${baseClass} bg-green-100 dark:bg-green-900/20 border-green-300 dark:border-green-700`;
        case 'ongoing':
            return `${baseClass} bg-yellow-100 dark:bg-yellow-900/20 border-yellow-300 dark:border-yellow-700`;
        case 'walkover':
            return `${baseClass} bg-red-100 dark:bg-red-900/20 border-red-300 dark:border-red-700`;
        default:
            return `${baseClass} bg-gray-100 dark:bg-gray-700 border-gray-300 dark:border-gray-600`;
    }
}

function getStatusDot(match: Match): string {
    switch (match.status) {
        case 'finished':
            return 'bg-green-500';
        case 'ongoing':
            return 'bg-yellow-500 animate-pulse';
        case 'walkover':
            return 'bg-red-500';
        default:
            return 'bg-gray-400';
    }
}

function getParticipantNames(match: Match): string {
    const p1 = match.metadata?.participant1_id ?
        participantMap.value.get(match.metadata.participant1_id)?.display_name || 'TBD' : 'TBD';
    const p2 = match.metadata?.participant2_id ?
        participantMap.value.get(match.metadata.participant2_id)?.display_name || 'TBD' : 'TBD';

    return `${p1} vs ${p2}`;
}

function getConflict(tableId: number, time: string): string | null {
    if (!showConflicts.value) return null;

    const match = getMatch(tableId, time);
    if (!match) return null;

    const conflicts = findConflicts(match);
    if (conflicts.length === 0) return null;

    return conflicts.map(c => c.message).join(', ');
}

function findConflicts(match: Match): Array<{ type: string; message: string }> {
    const conflicts: Array<{ type: string; message: string }> = [];

    if (!match.scheduled_at) return conflicts;

    const matchTime = new Date(match.scheduled_at);
    const matchEnd = new Date(matchTime.getTime() + SLOT_DURATION * 60000);

    // Check for player conflicts
    const p1Id = match.metadata?.participant1_id;
    const p2Id = match.metadata?.participant2_id;

    scheduledMatches.value.forEach(other => {
        if (other.id === match.id || !other.scheduled_at) return;

        const otherTime = new Date(other.scheduled_at);
        const otherEnd = new Date(otherTime.getTime() + SLOT_DURATION * 60000);

        // Check time overlap
        const overlaps = !(matchEnd <= otherTime || otherEnd <= matchTime);
        if (!overlaps) return;

        // Check player conflicts
        const otherP1 = other.metadata?.participant1_id;
        const otherP2 = other.metadata?.participant2_id;

        if ((p1Id && (p1Id === otherP1 || p1Id === otherP2)) ||
            (p2Id && (p2Id === otherP1 || p2Id === otherP2))) {
            conflicts.push({
                type: 'player',
                message: 'Player already scheduled'
            });
        }
    });

    return conflicts;
}

// Drag & Drop
function startDrag(event: DragEvent, match: Match) {
    draggedMatch.value = match;
    event.dataTransfer!.effectAllowed = 'move';
    event.dataTransfer!.setData('text/plain', match.id.toString());

    // Add visual feedback
    const target = event.target as HTMLElement;
    target.style.opacity = '0.5';
}

function endDrag(event: DragEvent) {
    const target = event.target as HTMLElement;
    target.style.opacity = '';
    draggedMatch.value = null;
    isDragOver.value = null;
}

function handleDrop(event: DragEvent, tableId: number, time: string) {
    event.preventDefault();
    isDragOver.value = null;

    if (!draggedMatch.value) return;

    const match = draggedMatch.value;
    const newTime = new Date(time);

    // Check for conflicts
    const testMatch = {...match, scheduled_at: newTime.toISOString(), table_id: tableId};
    const conflicts = findConflicts(testMatch);

    if (conflicts.length > 0) {
        conflictMessage.value = conflicts.map(c => c.message).join('. ');
        pendingReschedule.value = {match, tableId, time: newTime.toISOString()};
        showConflictModal.value = true;
        return;
    }

    // No conflicts, proceed with reschedule
    emit('reschedule', {
        matchId: match.id,
        tableId: tableId,
        newStart: newTime.toISOString()
    });
}

function cancelReschedule() {
    showConflictModal.value = false;
    pendingReschedule.value = null;
    conflictMessage.value = '';
}

function confirmReschedule() {
    if (!pendingReschedule.value) return;

    emit('reschedule', {
        matchId: pendingReschedule.value.match.id,
        tableId: pendingReschedule.value.tableId,
        newStart: pendingReschedule.value.time
    });

    showConflictModal.value = false;
    pendingReschedule.value = null;
    conflictMessage.value = '';
}

function autoSchedule() {
    emit('auto-schedule', currentDate.value.toISOString());
}

// Lifecycle
onMounted(() => {
    // Set CSS variable for dynamic grid columns
    document.documentElement.style.setProperty('--table-count', props.tables.length.toString());
});

// Watch for table changes
watch(() => props.tables.length, (newCount) => {
    document.documentElement.style.setProperty('--table-count', newCount.toString());
});
</script>

<style scoped>
/* Ensure grid columns work properly */
:root {
    --table-count: 4;
}
</style>
