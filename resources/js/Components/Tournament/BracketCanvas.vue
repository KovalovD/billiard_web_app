<template>
    <div ref="containerRef" class="relative w-full h-full overflow-hidden bg-gray-50 dark:bg-gray-900">
        <!-- Controls -->
        <div class="absolute top-4 right-4 z-10 flex gap-2">
            <button
                class="p-2 bg-white dark:bg-gray-800 rounded-lg shadow-md hover:shadow-lg transition-shadow"
                title="Zoom In"
                @click="zoomIn"
            >
                <ZoomInIcon class="w-5 h-5"/>
            </button>
            <button
                class="p-2 bg-white dark:bg-gray-800 rounded-lg shadow-md hover:shadow-lg transition-shadow"
                title="Zoom Out"
                @click="zoomOut"
            >
                <ZoomOutIcon class="w-5 h-5"/>
            </button>
            <button
                class="p-2 bg-white dark:bg-gray-800 rounded-lg shadow-md hover:shadow-lg transition-shadow"
                title="Reset View"
                @click="resetView"
            >
                <MaximizeIcon class="w-5 h-5"/>
            </button>
        </div>

        <!-- SVG Canvas -->
        <svg
            ref="svgRef"
            :height="viewBox.height"
            :viewBox="`${viewBox.x} ${viewBox.y} ${viewBox.width} ${viewBox.height}`"
            :width="viewBox.width"
            class="w-full h-full cursor-move"
            @mousedown="startPan"
            @mousemove="pan"
            @mouseup="endPan"
            @touchend="handleTouchEnd"
            @touchmove="handleTouchMove"
            @touchstart="handleTouchStart"
            @wheel="handleWheel"
        >
            <!-- Grid Background -->
            <defs>
                <pattern id="grid" height="50" patternUnits="userSpaceOnUse" width="50">
                    <path d="M 50 0 L 0 0 0 50" fill="none" stroke="#e5e7eb" stroke-width="1"/>
                </pattern>
            </defs>
            <rect fill="url(#grid)" height="100%" width="100%"/>

            <!-- Bracket Lines -->
            <g v-for="connection in matchConnections" :key="`line-${connection.from}-${connection.to}`">
                <path
                    :d="connection.path"
                    class="transition-all duration-300"
                    fill="none"
                    stroke="#9ca3af"
                    stroke-width="2"
                />
            </g>

            <!-- Matches -->
            <g v-for="(match, index) in matchPositions" :key="match.id">
                <g
                    :transform="`translate(${match.x}, ${match.y})`"
                    class="cursor-pointer"
                    @click="selectMatch(match)"
                >
                    <!-- Match Background -->
                    <rect
                        :fill="getMatchColor(match)"
                        :height="MATCH_HEIGHT"
                        :stroke="selectedMatch?.id === match.id ? '#3b82f6' : '#d1d5db'"
                        :stroke-width="selectedMatch?.id === match.id ? 3 : 1"
                        :width="MATCH_WIDTH"
                        class="transition-all duration-300"
                        rx="8"
                    />

                    <!-- Match Number -->
                    <text
                        :x="MATCH_WIDTH / 2"
                        class="text-xs font-medium fill-gray-600 dark:fill-gray-400"
                        text-anchor="middle"
                        y="20"
                    >
                        Match {{ match.metadata?.match_number || match.id }}
                    </text>

                    <!-- Participant 1 -->
                    <g
                        :transform="`translate(0, ${MATCH_HEIGHT / 2 - 20})`"
                        @drop="handleDrop($event, match, 1)"
                        @dragover.prevent
                        @dragenter.prevent
                    >
                        <rect
                            :width="MATCH_WIDTH"
                            class="hover:fill-blue-50 dark:hover:fill-blue-900/20"
                            fill="transparent"
                            height="40"
                        />
                        <text
                            :class="getParticipantClass(match, 1)"
                            class="text-sm font-medium"
                            x="10"
                            y="25"
                        >
                            {{ getParticipantName(match, 1) }}
                        </text>
                        <text
                            :class="getScoreClass(match, 1)"
                            :x="MATCH_WIDTH - 10"
                            class="text-sm font-bold"
                            text-anchor="end"
                            y="25"
                        >
                            {{ getScore(match, 1) }}
                        </text>
                    </g>

                    <!-- Divider -->
                    <line
                        :x2="MATCH_WIDTH - 10"
                        :y1="MATCH_HEIGHT / 2"
                        :y2="MATCH_HEIGHT / 2"
                        stroke="#e5e7eb"
                        stroke-width="1"
                        x1="10"
                    />

                    <!-- Participant 2 -->
                    <g
                        :transform="`translate(0, ${MATCH_HEIGHT / 2})`"
                        @drop="handleDrop($event, match, 2)"
                        @dragover.prevent
                        @dragenter.prevent
                    >
                        <rect
                            :width="MATCH_WIDTH"
                            class="hover:fill-blue-50 dark:hover:fill-blue-900/20"
                            fill="transparent"
                            height="40"
                        />
                        <text
                            :class="getParticipantClass(match, 2)"
                            class="text-sm font-medium"
                            x="10"
                            y="25"
                        >
                            {{ getParticipantName(match, 2) }}
                        </text>
                        <text
                            :class="getScoreClass(match, 2)"
                            :x="MATCH_WIDTH - 10"
                            class="text-sm font-bold"
                            text-anchor="end"
                            y="25"
                        >
                            {{ getScore(match, 2) }}
                        </text>
                    </g>

                    <!-- Match Status Indicator -->
                    <circle
                        v-if="match.status === 'ongoing'"
                        :cx="MATCH_WIDTH - 15"
                        :fill="getStatusColor(match)"
                        class="animate-pulse"
                        cy="20"
                        r="5"
                    />
                </g>
            </g>

            <!-- Round Labels -->
            <g v-for="(round, index) in rounds" :key="`round-${index}`">
                <text
                    :x="round.x"
                    :y="30"
                    class="text-lg font-bold fill-gray-700 dark:fill-gray-300"
                    text-anchor="middle"
                >
                    {{ round.name }}
                </text>
            </g>
        </svg>

        <!-- Participant Drawer (for drag source) -->
        <div
            v-if="showParticipantDrawer"
            class="absolute left-0 top-0 h-full w-64 bg-white dark:bg-gray-800 shadow-lg overflow-y-auto"
        >
            <div class="p-4">
                <h3 class="text-lg font-semibold mb-4">Participants</h3>
                <div class="space-y-2">
                    <div
                        v-for="participant in unassignedParticipants"
                        :key="participant.id"
                        :draggable="true"
                        class="p-3 bg-gray-100 dark:bg-gray-700 rounded-lg cursor-move hover:shadow-md transition-shadow"
                        @dragstart="startDrag($event, participant)"
                    >
                        <div class="font-medium">{{ participant.display_name }}</div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">
                            Seed: {{ participant.seed }} | Rating: {{ participant.rating_snapshot }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script lang="ts" setup>
import {computed, onMounted, onUnmounted, ref, watch} from 'vue';
import {MaximizeIcon, ZoomInIcon, ZoomOutIcon} from 'lucide-vue-next';

// Types
interface Match {
    id: number;
    round: number;
    bracket: string;
    status: string;
    metadata: {
        match_number?: number;
        participant1_id?: number;
        participant2_id?: number;
        next_match_winner?: number | string;
        is_final?: boolean;
        is_semifinal?: boolean;
        [key: string]: any;
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
    user?: any;
    team?: any;
}

interface MatchPosition extends Match {
    x: number;
    y: number;
}

// Props & Emits
const props = defineProps<{
    matches: Match[];
    participants: Participant[];
    bracketType?: 'single' | 'double';
    showSeeding?: boolean;
}>();

const emit = defineEmits<{
    'update:match': [payload: { matchId: number; scores?: any }];
    'seed:change': [payload: { matchId: number; participantSlot: 1 | 2; participantId: number }];
}>();

// Constants
const MATCH_WIDTH = 240;
const MATCH_HEIGHT = 100;
const ROUND_SPACING = 320;
const MATCH_SPACING = 120;
const BRACKET_SPACING = 600;

// Refs
const containerRef = ref<HTMLDivElement>();
const svgRef = ref<SVGSVGElement>();
const selectedMatch = ref<Match | null>(null);
const showParticipantDrawer = ref(false);
const draggedParticipant = ref<Participant | null>(null);

// Pan & Zoom state
const viewBox = ref({
    x: 0,
    y: 0,
    width: 1200,
    height: 800
});

const isPanning = ref(false);
const panStart = ref({x: 0, y: 0});
const scale = ref(1);

// Touch support
const touches = ref<Touch[]>([]);
const lastPinchDistance = ref(0);

// Computed
const matchPositions = computed<MatchPosition[]>(() => {
    const positions: MatchPosition[] = [];
    const roundGroups = groupMatchesByRound();

    Object.entries(roundGroups).forEach(([roundKey, roundMatches]) => {
        const [bracket, round] = roundKey.split('-');
        const roundNum = parseInt(round);
        const bracketOffset = bracket === 'L' ? BRACKET_SPACING : 0;

        roundMatches.forEach((match, index) => {
            const x = (roundNum - 1) * ROUND_SPACING + 100;
            const y = index * MATCH_SPACING + 100 + bracketOffset;

            positions.push({
                ...match,
                x,
                y
            });
        });
    });

    return positions;
});

const matchConnections = computed(() => {
    const connections: Array<{ from: number; to: number | string; path: string }> = [];

    matchPositions.value.forEach(match => {
        const nextMatch = match.metadata?.next_match_winner;
        if (nextMatch) {
            const fromPos = match;
            const toPos = matchPositions.value.find(m =>
                m.metadata?.match_number === nextMatch || m.id === nextMatch
            );

            if (toPos) {
                const path = generateConnectionPath(fromPos, toPos);
                connections.push({
                    from: match.id,
                    to: nextMatch,
                    path
                });
            }
        }
    });

    return connections;
});

const rounds = computed(() => {
    const roundSet = new Set<number>();
    props.matches.forEach(m => roundSet.add(m.round));

    return Array.from(roundSet).sort().map(round => ({
        round,
        name: getRoundName(round),
        x: (round - 1) * ROUND_SPACING + 100 + MATCH_WIDTH / 2
    }));
});

const unassignedParticipants = computed(() => {
    const assignedIds = new Set<number>();

    props.matches.forEach(match => {
        if (match.metadata?.participant1_id) assignedIds.add(match.metadata.participant1_id);
        if (match.metadata?.participant2_id) assignedIds.add(match.metadata.participant2_id);
    });

    return props.participants.filter(p => !assignedIds.has(p.id));
});

const participantMap = computed(() => {
    const map = new Map<number, Participant>();
    props.participants.forEach(p => map.set(p.id, p));
    return map;
});

// Methods
function groupMatchesByRound() {
    const groups: Record<string, Match[]> = {};

    props.matches.forEach(match => {
        const key = `${match.bracket}-${match.round}`;
        if (!groups[key]) groups[key] = [];
        groups[key].push(match);
    });

    // Sort matches within each round
    Object.keys(groups).forEach(key => {
        groups[key].sort((a, b) => (a.metadata?.match_number || 0) - (b.metadata?.match_number || 0));
    });

    return groups;
}

function generateConnectionPath(from: MatchPosition, to: MatchPosition): string {
    const startX = from.x + MATCH_WIDTH;
    const startY = from.y + MATCH_HEIGHT / 2;
    const endX = to.x;
    const endY = to.y + MATCH_HEIGHT / 2;

    const midX = (startX + endX) / 2;

    return `M ${startX} ${startY} C ${midX} ${startY}, ${midX} ${endY}, ${endX} ${endY}`;
}

function getRoundName(round: number): string {
    const maxRound = Math.max(...props.matches.map(m => m.round));
    const roundsFromEnd = maxRound - round;

    const names: Record<number, string> = {
        0: 'Final',
        1: 'Semi-finals',
        2: 'Quarter-finals',
        3: 'Round of 16',
        4: 'Round of 32',
        5: 'Round of 64',
        6: 'Round of 128'
    };

    return names[roundsFromEnd] || `Round ${round}`;
}

function getMatchColor(match: Match): string {
    switch (match.status) {
        case 'finished':
            return '#f3f4f6';
        case 'ongoing':
            return '#fef3c7';
        case 'walkover':
            return '#fee2e2';
        default:
            return '#ffffff';
    }
}

function getStatusColor(match: Match): string {
    switch (match.status) {
        case 'ongoing':
            return '#f59e0b';
        case 'finished':
            return '#10b981';
        default:
            return '#6b7280';
    }
}

function getParticipantName(match: Match, slot: 1 | 2): string {
    const participantId = slot === 1 ? match.metadata?.participant1_id : match.metadata?.participant2_id;
    if (!participantId) return slot === 1 ? 'TBD' : 'TBD';

    const participant = participantMap.value.get(participantId);
    return participant?.display_name || `Player ${participantId}`;
}

function getParticipantClass(match: Match, slot: 1 | 2): string {
    const winnerId = getWinnerId(match);
    const participantId = slot === 1 ? match.metadata?.participant1_id : match.metadata?.participant2_id;

    if (!participantId) return 'fill-gray-400';
    if (winnerId === participantId) return 'fill-green-600 font-bold';
    if (winnerId && winnerId !== participantId) return 'fill-gray-400';
    return 'fill-gray-700 dark:fill-gray-300';
}

function getScore(match: Match, slot: 1 | 2): string {
    if (!match.match_sets || match.match_sets.length === 0) return '';

    const participantId = slot === 1 ? match.metadata?.participant1_id : match.metadata?.participant2_id;
    if (!participantId) return '';

    const setsWon = match.match_sets.filter(set => set.winner_participant_id === participantId).length;
    return setsWon.toString();
}

function getScoreClass(match: Match, slot: 1 | 2): string {
    const winnerId = getWinnerId(match);
    const participantId = slot === 1 ? match.metadata?.participant1_id : match.metadata?.participant2_id;

    if (winnerId === participantId) return 'fill-green-600';
    return 'fill-gray-600 dark:fill-gray-400';
}

function getWinnerId(match: Match): number | null {
    if (match.status !== 'finished' || !match.match_sets) return null;

    const p1Id = match.metadata?.participant1_id;
    const p2Id = match.metadata?.participant2_id;

    if (!p1Id || !p2Id) return null;

    const p1Wins = match.match_sets.filter(set => set.winner_participant_id === p1Id).length;
    const p2Wins = match.match_sets.filter(set => set.winner_participant_id === p2Id).length;

    if (p1Wins > p2Wins) return p1Id;
    if (p2Wins > p1Wins) return p2Id;
    return null;
}

// Pan & Zoom handlers
function startPan(event: MouseEvent) {
    if (event.button !== 0) return; // Only left mouse button

    isPanning.value = true;
    panStart.value = {
        x: event.clientX,
        y: event.clientY
    };
}

function pan(event: MouseEvent) {
    if (!isPanning.value) return;

    const dx = event.clientX - panStart.value.x;
    const dy = event.clientY - panStart.value.y;

    viewBox.value.x -= dx / scale.value;
    viewBox.value.y -= dy / scale.value;

    panStart.value = {
        x: event.clientX,
        y: event.clientY
    };
}

function endPan() {
    isPanning.value = false;
}

function handleWheel(event: WheelEvent) {
    event.preventDefault();

    const delta = event.deltaY > 0 ? 0.9 : 1.1;
    zoom(delta, event.clientX, event.clientY);
}

function zoom(delta: number, centerX?: number, centerY?: number) {
    const newScale = scale.value * delta;

    if (newScale < 0.1 || newScale > 5) return;

    if (centerX !== undefined && centerY !== undefined && svgRef.value) {
        const rect = svgRef.value.getBoundingClientRect();
        const x = (centerX - rect.left) / scale.value + viewBox.value.x;
        const y = (centerY - rect.top) / scale.value + viewBox.value.y;

        viewBox.value.x = x - (x - viewBox.value.x) / delta;
        viewBox.value.y = y - (y - viewBox.value.y) / delta;
    }

    viewBox.value.width /= delta;
    viewBox.value.height /= delta;
    scale.value = newScale;
}

function zoomIn() {
    zoom(1.2);
}

function zoomOut() {
    zoom(0.8);
}

function resetView() {
    viewBox.value = {
        x: 0,
        y: 0,
        width: 1200,
        height: 800
    };
    scale.value = 1;
}

// Touch handlers
function handleTouchStart(event: TouchEvent) {
    touches.value = Array.from(event.touches);

    if (touches.value.length === 2) {
        const dx = touches.value[0].clientX - touches.value[1].clientX;
        const dy = touches.value[0].clientY - touches.value[1].clientY;
        lastPinchDistance.value = Math.sqrt(dx * dx + dy * dy);
    }
}

function handleTouchMove(event: TouchEvent) {
    event.preventDefault();

    if (touches.value.length === 1 && event.touches.length === 1) {
        // Pan
        const dx = event.touches[0].clientX - touches.value[0].clientX;
        const dy = event.touches[0].clientY - touches.value[0].clientY;

        viewBox.value.x -= dx / scale.value;
        viewBox.value.y -= dy / scale.value;

        touches.value = Array.from(event.touches);
    } else if (event.touches.length === 2) {
        // Pinch zoom
        const dx = event.touches[0].clientX - event.touches[1].clientX;
        const dy = event.touches[0].clientY - event.touches[1].clientY;
        const distance = Math.sqrt(dx * dx + dy * dy);

        if (lastPinchDistance.value > 0) {
            const delta = distance / lastPinchDistance.value;
            const centerX = (event.touches[0].clientX + event.touches[1].clientX) / 2;
            const centerY = (event.touches[0].clientY + event.touches[1].clientY) / 2;
            zoom(delta, centerX, centerY);
        }

        lastPinchDistance.value = distance;
    }
}

function handleTouchEnd() {
    touches.value = [];
    lastPinchDistance.value = 0;
}

// Match interaction
function selectMatch(match: Match) {
    selectedMatch.value = match;
    emit('update:match', {matchId: match.id});
}

// Drag & Drop for seeding
function startDrag(event: DragEvent, participant: Participant) {
    draggedParticipant.value = participant;
    event.dataTransfer!.effectAllowed = 'move';
    event.dataTransfer!.setData('text/plain', participant.id.toString());
}

function handleDrop(event: DragEvent, match: Match, slot: 1 | 2) {
    event.preventDefault();

    if (!draggedParticipant.value || !props.showSeeding) return;

    emit('seed:change', {
        matchId: match.id,
        participantSlot: slot,
        participantId: draggedParticipant.value.id
    });

    draggedParticipant.value = null;
}

// Lifecycle
onMounted(() => {
    showParticipantDrawer.value = props.showSeeding || false;

    // Fit bracket to container
    if (containerRef.value) {
        const rect = containerRef.value.getBoundingClientRect();
        viewBox.value.width = rect.width;
        viewBox.value.height = rect.height;
    }
});

onUnmounted(() => {
    // Cleanup
});

// Watch for bracket changes
watch(() => props.matches, () => {
    // Auto-adjust view if needed
}, {deep: true});
</script>

<style scoped>
/* Custom styles if needed */
</style>
