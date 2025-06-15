<template>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden">
        <!-- Match Header -->
        <div class="px-4 py-3 bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
          <span class="text-sm font-medium text-gray-500 dark:text-gray-400">
            {{ getRoundName(match.round) }}
          </span>
                    <span class="text-sm text-gray-400">•</span>
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
            Match #{{ match.metadata?.match_number || match.id }}
          </span>
                </div>
                <div class="flex items-center gap-2">
          <span
              :class="getStatusClass(match.status)"
              class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
          >
            {{ match.status.toUpperCase() }}
          </span>
                    <button
                        v-if="editable && match.status !== 'finished'"
                        class="p-1 hover:bg-gray-200 dark:hover:bg-gray-600 rounded transition-colors"
                        @click="toggleEditMode"
                    >
                        <PencilIcon v-if="!isEditing" class="w-4 h-4"/>
                        <XIcon v-else class="w-4 h-4"/>
                    </button>
                </div>
            </div>
        </div>

        <!-- Participants -->
        <div class="divide-y divide-gray-200 dark:divide-gray-700">
            <!-- Participant 1 -->
            <div
                :class="{ 'bg-green-50 dark:bg-green-900/20': isWinner(1) }"
                class="px-4 py-4 flex items-center justify-between"
            >
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center">
                        <span class="text-xs font-medium">{{ getParticipantSeed(1) }}</span>
                    </div>
                    <div>
                        <div class="font-medium text-gray-900 dark:text-gray-100">
                            {{ getParticipantName(1) }}
                        </div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">
                            Rating: {{ getParticipantRating(1) }}
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <div v-if="!isEditing" class="text-2xl font-bold">
                        {{ getScore(1) }}
                    </div>
                    <input
                        v-else
                        v-model.number="editScores.participant1"
                        class="w-16 px-2 py-1 text-lg font-bold text-center border rounded focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600"
                        max="99"
                        min="0"
                        type="number"
                    />
                    <CheckCircleIcon
                        v-if="isWinner(1)"
                        class="w-5 h-5 text-green-600 dark:text-green-400"
                    />
                </div>
            </div>

            <!-- Participant 2 -->
            <div
                :class="{ 'bg-green-50 dark:bg-green-900/20': isWinner(2) }"
                class="px-4 py-4 flex items-center justify-between"
            >
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center">
                        <span class="text-xs font-medium">{{ getParticipantSeed(2) }}</span>
                    </div>
                    <div>
                        <div class="font-medium text-gray-900 dark:text-gray-100">
                            {{ getParticipantName(2) }}
                        </div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">
                            Rating: {{ getParticipantRating(2) }}
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <div v-if="!isEditing" class="text-2xl font-bold">
                        {{ getScore(2) }}
                    </div>
                    <input
                        v-else
                        v-model.number="editScores.participant2"
                        class="w-16 px-2 py-1 text-lg font-bold text-center border rounded focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600"
                        max="99"
                        min="0"
                        type="number"
                    />
                    <CheckCircleIcon
                        v-if="isWinner(2)"
                        class="w-5 h-5 text-green-600 dark:text-green-400"
                    />
                </div>
            </div>
        </div>

        <!-- Match Details -->
        <div class="px-4 py-3 bg-gray-50 dark:bg-gray-700 border-t border-gray-200 dark:border-gray-600">
            <div class="grid grid-cols-2 gap-4 text-sm">
                <div>
                    <span class="text-gray-500 dark:text-gray-400">Table:</span>
                    <span class="ml-2 font-medium">{{ match.table?.name || 'Not assigned' }}</span>
                </div>
                <div>
                    <span class="text-gray-500 dark:text-gray-400">Time:</span>
                    <span class="ml-2 font-medium">{{ formatScheduledTime() }}</span>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div v-if="isEditing" class="px-4 py-3 bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-600">
            <div class="flex justify-end gap-2">
                <button
                    class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors"
                    @click="cancelEdit"
                >
                    Cancel
                </button>
                <button
                    class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors"
                    @click="saveScore"
                >
                    Save Score
                </button>
                <button
                    v-if="match.status === 'pending'"
                    class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition-colors"
                    @click="setWalkover"
                >
                    Walkover
                </button>
            </div>
        </div>

        <!-- Set Details -->
        <div v-if="showSets && match.match_sets?.length"
             class="px-4 py-3 border-t border-gray-200 dark:border-gray-600">
            <div class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Set Scores</div>
            <div class="space-y-1">
                <div
                    v-for="(set, index) in match.match_sets"
                    :key="index"
                    class="flex items-center justify-between text-sm"
                >
                    <span class="text-gray-500 dark:text-gray-400">Set {{ index + 1 }}:</span>
                    <div class="flex items-center gap-4">
            <span :class="{ 'font-bold': set.winner_participant_id === match.metadata?.participant1_id }">
              {{ set.score_json.participant1 }}
            </span>
                        <span class="text-gray-400">-</span>
                        <span :class="{ 'font-bold': set.winner_participant_id === match.metadata?.participant2_id }">
              {{ set.score_json.participant2 }}
            </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script lang="ts" setup>
import {computed, ref} from 'vue';
import {CheckCircleIcon, PencilIcon, XIcon} from 'lucide-vue-next';

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
    table?: {
        id: number;
        name: string;
    };
    match_sets?: Array<{
        winner_participant_id: number;
        score_json: {
            participant1: number;
            participant2: number;
        };
    }>;
}

interface Participant {
    id: number;
    display_name: string;
    seed: number;
    rating_snapshot: number;
}

// Props & Emits
const props = defineProps<{
    match: Match;
    participants: Participant[];
    editable?: boolean;
    showSets?: boolean;
}>();

const emit = defineEmits<{
    submitScore: [payload: {
        matchId: number;
        status: string;
        sets: Array<{
            winner_id: number;
            score1: number;
            score2: number;
        }>;
    }];
    walkover: [payload: { matchId: number; winnerId: number }];
}>();

// State
const isEditing = ref(false);
const editScores = ref({
    participant1: 0,
    participant2: 0
});

// Computed
const participantMap = computed(() => {
    const map = new Map<number, Participant>();
    props.participants.forEach(p => map.set(p.id, p));
    return map;
});

// Methods
function getRoundName(round: number): string {
    // This should match the logic in BracketCanvas
    const names: Record<string, string> = {
        '999': 'Grand Final',
        '99': 'Final',
        '10': 'Semi-final',
        '9': 'Quarter-final',
        '8': 'Round of 16',
        '7': 'Round of 32',
        '6': 'Round of 64',
    };

    return names[round.toString()] || `Round ${round}`;
}

function getStatusClass(status: string): string {
    const classes: Record<string, string> = {
        pending: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
        ongoing: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300 animate-pulse',
        finished: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
        walkover: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
    };

    return classes[status] || classes.pending;
}

function getParticipant(slot: 1 | 2): Participant | null {
    const participantId = slot === 1 ? props.match.metadata?.participant1_id : props.match.metadata?.participant2_id;
    return participantId ? participantMap.value.get(participantId) || null : null;
}

function getParticipantName(slot: 1 | 2): string {
    const participant = getParticipant(slot);
    return participant?.display_name || 'TBD';
}

function getParticipantSeed(slot: 1 | 2): string {
    const participant = getParticipant(slot);
    return participant?.seed?.toString() || '-';
}

function getParticipantRating(slot: 1 | 2): string {
    const participant = getParticipant(slot);
    return participant?.rating_snapshot?.toString() || '-';
}

function getScore(slot: 1 | 2): string {
    if (!props.match.match_sets || props.match.match_sets.length === 0) return '-';

    const participantId = slot === 1 ? props.match.metadata?.participant1_id : props.match.metadata?.participant2_id;
    if (!participantId) return '-';

    const setsWon = props.match.match_sets.filter(set => set.winner_participant_id === participantId).length;
    return setsWon.toString();
}

function isWinner(slot: 1 | 2): boolean {
    if (props.match.status !== 'finished' || !props.match.match_sets) return false;

    const participantId = slot === 1 ? props.match.metadata?.participant1_id : props.match.metadata?.participant2_id;
    if (!participantId) return false;

    const p1Wins = props.match.match_sets.filter(set => set.winner_participant_id === props.match.metadata?.participant1_id).length;
    const p2Wins = props.match.match_sets.filter(set => set.winner_participant_id === props.match.metadata?.participant2_id).length;

    if (slot === 1) return p1Wins > p2Wins;
    return p2Wins > p1Wins;
}

function formatScheduledTime(): string {
    if (!props.match.scheduled_at) return 'Not scheduled';

    const date = new Date(props.match.scheduled_at);
    return date.toLocaleString('en-US', {
        weekday: 'short',
        month: 'short',
        day: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
        hour12: true
    });
}

function toggleEditMode() {
    isEditing.value = !isEditing.value;
    if (isEditing.value) {
        // Initialize edit scores with current scores
        const p1Wins = props.match.match_sets?.filter(set => set.winner_participant_id === props.match.metadata?.participant1_id).length || 0;
        const p2Wins = props.match.match_sets?.filter(set => set.winner_participant_id === props.match.metadata?.participant2_id).length || 0;

        editScores.value = {
            participant1: p1Wins,
            participant2: p2Wins
        };
    }
}

function cancelEdit() {
    isEditing.value = false;
    editScores.value = {
        participant1: 0,
        participant2: 0
    };
}

function saveScore() {
    const {participant1: p1Score, participant2: p2Score} = editScores.value;

    if (p1Score === 0 && p2Score === 0) {
        alert('Please enter a valid score');
        return;
    }

    const sets = [];
    const p1Id = props.match.metadata?.participant1_id;
    const p2Id = props.match.metadata?.participant2_id;

    if (!p1Id || !p2Id) {
        alert('Match participants not set');
        return;
    }

    // Create simplified sets based on final score
    const totalSets = Math.max(p1Score, p2Score);
    let p1Wins = 0;
    let p2Wins = 0;

    for (let i = 0; i < totalSets; i++) {
        if (p1Wins < p1Score && (i % 2 === 0 || p2Wins >= p2Score)) {
            sets.push({
                winner_id: p1Id,
                score1: 5,
                score2: 3
            });
            p1Wins++;
        } else if (p2Wins < p2Score) {
            sets.push({
                winner_id: p2Id,
                score1: 3,
                score2: 5
            });
            p2Wins++;
        }
    }

    emit('submitScore', {
        matchId: props.match.id,
        status: 'finished',
        sets
    });

    isEditing.value = false;
}

function setWalkover() {
    const winner = prompt('Select winner:\n1 - ' + getParticipantName(1) + '\n2 - ' + getParticipantName(2));

    if (winner === '1' || winner === '2') {
        const winnerId = winner === '1' ? props.match.metadata?.participant1_id : props.match.metadata?.participant2_id;

        if (winnerId) {
            emit('walkover', {
                matchId: props.match.id,
                winnerId
            });
        }
    }

    isEditing.value = false;
}
</script>

<style scoped>
/* Input number spinner buttons */
input[type="number"]::-webkit-inner-spin-button,
input[type="number"]::-webkit-outer-spin-button {
    opacity: 1;
    height: auto;
}
</style>
