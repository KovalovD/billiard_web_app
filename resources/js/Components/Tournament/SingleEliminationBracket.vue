<script lang="ts" setup>
import {Button} from '@/Components/ui';
import {useLocale} from '@/composables/useLocale';
import type {Tournament, TournamentMatch} from '@/types/api';
import {ExpandIcon, MinusIcon, PlusIcon, RotateCcwIcon, ShrinkIcon} from 'lucide-vue-next';
import {computed, nextTick, onMounted, onUnmounted, ref, watch} from 'vue';

const props = defineProps<{
    matches: TournamentMatch[];
    tournament: Tournament;
    canEdit: boolean;
}>();

const emit = defineEmits<{
    'open-match': [matchId: number];
}>();

const {t} = useLocale();

// Zoom and fullscreen states
const zoomLevel = ref(1);
const isFullscreen = ref(false);
const bracketContainerRef = ref<HTMLDivElement | null>(null);
const bracketScrollContainerRef = ref<HTMLDivElement | null>(null);

// Touch gesture states
const touchStartDistance = ref(0);
const touchStartZoom = ref(1);
const isTouching = ref(false);

// Bracket visualization settings
const nodeWidth = 200;
const nodeHeight = 80;
const hGap = 120;
const vGap = 40;

// Transform matches to bracket format
interface BracketMatch {
    id: number;
    round: number;
    slot: number;
    player1: { id: number; name: string } | null;
    player2: { id: number; name: string } | null;
    player1_score: number;
    player2_score: number;
    winner_id: number | null;
    status: string;
    match_code: string;
    isWalkover: boolean;
    isReadyToStart: boolean;
    canBeStarted: boolean;
}

const bracketMatches = computed<BracketMatch[]>(() => {
    const roundMap: Record<string, number> = {
        'round_128': 0,
        'round_64': 1,
        'round_32': 2,
        'round_16': 3,
        'quarterfinals': 4,
        'semifinals': 5,
        'finals': 6,
        'third_place': 6
    };

    return props.matches
        .filter(m => m.round && roundMap[m.round] !== undefined && m.bracket_side !== 'lower')
        .map(m => {
            const hasPlayer1 = !!m.player1_id;
            const hasPlayer2 = !!m.player2_id;
            const isWalkover = m.status === 'completed' && ((hasPlayer1 && !hasPlayer2) || (!hasPlayer1 && hasPlayer2));
            const isReadyToStart = hasPlayer1 && hasPlayer2 && (m.status === 'pending' || m.status === 'ready');
            const canBeStarted = m.status === 'ready';

            return {
                id: m.id,
                round: roundMap[m.round!],
                slot: m.bracket_position || 0,
                player1: m.player1 ? {
                    id: m.player1_id!,
                    name: `${m.player1.firstname} ${m.player1.lastname}`
                } : null,
                player2: m.player2 ? {
                    id: m.player2_id!,
                    name: `${m.player2.firstname} ${m.player2.lastname}`
                } : null,
                player1_score: m.player1_score,
                player2_score: m.player2_score,
                winner_id: m.winner_id,
                match_code: m.match_code,
                status: m.status,
                isWalkover,
                isReadyToStart,
                canBeStarted
            };
        })
        .sort((a, b) => a.round - b.round || a.slot - b.slot);
});

// Group matches by round
const rounds = computed(() => {
    const map = new Map<number, BracketMatch[]>();
    bracketMatches.value.forEach(m => {
        (map.get(m.round) || map.set(m.round, []).get(m.round)!).push(m);
    });
    return [...map.entries()]
        .sort(([a], [b]) => a - b)
        .map(([, ms]) => ms.sort((a, b) => a.slot - b.slot));
});

// Calculate match positions
interface PositionedMatch extends BracketMatch {
    x: number;
    y: number;
}

const positionedMatches = computed<PositionedMatch[]>(() => {
    const list: PositionedMatch[] = [];

    rounds.value.forEach((roundMatches, roundIndex) => {
        const spacing = Math.pow(2, roundIndex);

        roundMatches.forEach((match, matchIndex) => {
            const x = roundIndex * (nodeWidth + hGap);
            const y = matchIndex * spacing * (nodeHeight + vGap) + (spacing - 1) * (nodeHeight + vGap) / 2;
            list.push({...match, x, y});
        });
    });

    return list;
});

// Find next match
function nextOf(match: BracketMatch): PositionedMatch | undefined {
    return positionedMatches.value.find(
        m => m.round === match.round + 1 && m.slot === Math.floor(match.slot / 2)
    );
}

// Calculate connector lines
interface Segment {
    id: string;
    x1: number;
    y1: number;
    x2: number;
    y2: number;
}

const segments = computed<Segment[]>(() => {
    const segs: Segment[] = [];
    positionedMatches.value.forEach(m => {
        const n = nextOf(m);
        if (!n) return;

        const midX = n.x - hGap / 2;
        const yFrom = m.y + nodeHeight / 2;
        const yTo = n.y + nodeHeight / 2;

        segs.push({id: `${m.id}-h1`, x1: m.x + nodeWidth, y1: yFrom, x2: midX, y2: yFrom});
        segs.push({id: `${m.id}-v`, x1: midX, y1: yFrom, x2: midX, y2: yTo});
        segs.push({id: `${m.id}-h2`, x1: midX, y1: yTo, x2: n.x, y2: yTo});
    });
    return segs;
});

// Calculate SVG dimensions
const svgWidth = computed(() => rounds.value.length * (nodeWidth + hGap) + 40);
const svgHeight = computed(() => {
    const maxY = Math.max(...positionedMatches.value.map(m => m.y), 0);
    return maxY + nodeHeight + 40;
});

// Zoom functions
const setZoom = (newZoom: number) => {
    zoomLevel.value = Math.max(0.3, Math.min(2, newZoom));
};

const zoomIn = () => setZoom(zoomLevel.value + 0.1);
const zoomOut = () => setZoom(zoomLevel.value - 0.1);
const resetZoom = () => setZoom(1);

// Touch gesture handlers
const getTouchDistance = (touches: TouchList): number => {
    if (touches.length < 2) return 0;
    const dx = touches[0].clientX - touches[1].clientX;
    const dy = touches[0].clientY - touches[1].clientY;
    return Math.sqrt(dx * dx + dy * dy);
};

const handleTouchStart = (e: TouchEvent) => {
    if (e.touches.length === 2) {
        isTouching.value = true;
        touchStartDistance.value = getTouchDistance(e.touches);
        touchStartZoom.value = zoomLevel.value;
        e.preventDefault();
    }
};

const handleTouchMove = (e: TouchEvent) => {
    if (e.touches.length === 2 && isTouching.value) {
        const currentDistance = getTouchDistance(e.touches);
        const scale = currentDistance / touchStartDistance.value;
        setZoom(touchStartZoom.value * scale);
        e.preventDefault();
    }
};

const handleTouchEnd = () => {
    isTouching.value = false;
};

// Mouse wheel zoom
const handleWheel = (e: WheelEvent) => {
    if (e.ctrlKey || e.metaKey) {
        e.preventDefault();
        const delta = e.deltaY > 0 ? -0.1 : 0.1;
        setZoom(zoomLevel.value + delta);
    }
};

// Fullscreen functions
const toggleFullscreen = async () => {
    if (!bracketContainerRef.value) return;

    if (!document.fullscreenElement) {
        try {
            await bracketContainerRef.value.requestFullscreen();
            isFullscreen.value = true;
        } catch (err) {
            console.error('Error attempting to enable fullscreen:', err);
        }
    } else {
        try {
            await document.exitFullscreen();
            isFullscreen.value = false;
        } catch (err) {
            console.error('Error attempting to exit fullscreen:', err);
        }
    }
};

// Keyboard shortcuts
const handleKeyboard = (e: KeyboardEvent) => {
    if (e.ctrlKey || e.metaKey) {
        switch (e.key) {
            case '+':
            case '=':
                e.preventDefault();
                zoomIn();
                break;
            case '-':
                e.preventDefault();
                zoomOut();
                break;
            case '0':
                e.preventDefault();
                resetZoom();
                break;
        }
    }

    if (e.key === 'F11') {
        e.preventDefault();
        toggleFullscreen();
    }
};

// Fullscreen change handler
const handleFullscreenChange = () => {
    isFullscreen.value = !!document.fullscreenElement;
};

// Scroll to the bottom of the bracket
const scrollToBottom = () => {
    nextTick(() => {
        if (bracketScrollContainerRef.value) {
            const container = bracketScrollContainerRef.value;
            container.scrollTop = container.scrollHeight - container.clientHeight;
        }
    });
};

const getStatusColor = (status: string) => {
    switch (status) {
        case 'completed':
            return '#16a34a';
        case 'in_progress':
            return '#eab308';
        case 'verification':
            return '#9333ea';
        case 'ready':
            return '#3b82f6';
        default:
            return '#6b7280';
    }
};

const getMatchClass = (match: PositionedMatch) => {
    if (match.status === 'completed') return 'match-completed';
    if (match.status === 'in_progress') return 'match-active';
    if (match.status === 'verification') return 'match-verification';
    if (match.status === 'ready') return 'match-ready';
    return 'match-pending';
};

const getPlayerDisplay = (player: { id: number; name: string } | null, isWalkover: boolean, hasOpponent: boolean) => {
    if (player) return player.name;
    if (isWalkover && !hasOpponent) return t('Walkover');
    return t('TBD');
};

const handleMatchClick = (matchId: number) => {
    if (props.canEdit) {
        emit('open-match', matchId);
    }
};

// Watch for bracket data changes to scroll to bottom
watch(() => positionedMatches.value.length, () => {
    if (positionedMatches.value.length > 0) {
        scrollToBottom();
    }
});

onMounted(() => {
    document.addEventListener('keydown', handleKeyboard);
    document.addEventListener('fullscreenchange', handleFullscreenChange);
    scrollToBottom();
});

onUnmounted(() => {
    document.removeEventListener('keydown', handleKeyboard);
    document.removeEventListener('fullscreenchange', handleFullscreenChange);
});
</script>

<template>
    <div ref="bracketContainerRef" class="bracket-fullscreen-container">
        <div
            class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                            {{ t('Tournament Bracket') }}
                        </h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            {{
                                canEdit ? t('Click on a match to view details') : t('View only mode - tournament not active')
                            }}
                        </p>
                    </div>

                    <!-- Zoom and Fullscreen Controls -->
                    <div class="flex items-center gap-2">
                        <!-- Zoom Controls -->
                        <div class="flex items-center gap-1 rounded-lg border border-gray-200 dark:border-gray-700 p-1">
                            <Button
                                :title="t('Zoom Out')"
                                size="sm"
                                variant="ghost"
                                @click="zoomOut"
                            >
                                <MinusIcon class="h-4 w-4"/>
                            </Button>
                            <span class="px-2 text-sm font-medium text-gray-600 dark:text-gray-400">
                                {{ Math.round(zoomLevel * 100) }}%
                            </span>
                            <Button
                                :title="t('Zoom In')"
                                size="sm"
                                variant="ghost"
                                @click="zoomIn"
                            >
                                <PlusIcon class="h-4 w-4"/>
                            </Button>
                            <Button
                                :title="t('Reset Zoom')"
                                size="sm"
                                variant="ghost"
                                @click="resetZoom"
                            >
                                <RotateCcwIcon class="h-4 w-4"/>
                            </Button>
                        </div>

                        <!-- Fullscreen Button -->
                        <Button
                            :title="isFullscreen ? t('Exit Fullscreen') : t('Enter Fullscreen')"
                            size="sm"
                            variant="outline"
                            @click="toggleFullscreen"
                        >
                            <ExpandIcon v-if="!isFullscreen" class="h-4 w-4"/>
                            <ShrinkIcon v-else class="h-4 w-4"/>
                        </Button>
                    </div>
                </div>
            </div>

            <!-- Keyboard shortcuts hint -->
            <div
                class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2 text-xs text-gray-500 dark:text-gray-400">
                {{ t('Keyboard shortcuts') }}:
                <kbd class="px-1 py-0.5 bg-gray-200 dark:bg-gray-700 rounded">Ctrl</kbd> +
                <kbd class="px-1 py-0.5 bg-gray-200 dark:bg-gray-700 rounded">+/-</kbd> {{ t('zoom') }},
                <kbd class="px-1 py-0.5 bg-gray-200 dark:bg-gray-700 rounded">Ctrl</kbd> +
                <kbd class="px-1 py-0.5 bg-gray-200 dark:bg-gray-700 rounded">0</kbd> {{ t('reset') }},
                <kbd class="px-1 py-0.5 bg-gray-200 dark:bg-gray-700 rounded">F11</kbd>
                {{ t('fullscreen') }}
                <span class="ml-2">• {{ t('Pinch to zoom on touch devices') }}</span>
            </div>

            <div
                ref="bracketScrollContainerRef"
                class="bracket-container overflow-auto bg-gray-50 dark:bg-gray-900/50 touch-none"
                @touchend="handleTouchEnd"
                @touchmove="handleTouchMove"
                @touchstart="handleTouchStart"
                @wheel="handleWheel"
            >
                <div :style="{ transform: `scale(${zoomLevel})` }" class="bracket-zoom-wrapper">
                    <div class="p-6">
                        <svg
                            :height="svgHeight"
                            :width="svgWidth"
                            class="bracket-svg"
                            style="min-width: 100%;"
                        >
                            <!-- Connector lines -->
                            <g class="connectors">
                                <line
                                    v-for="seg in segments"
                                    :key="seg.id"
                                    :x1="seg.x1" :x2="seg.x2" :y1="seg.y1" :y2="seg.y2"
                                    class="connector-line"
                                />
                            </g>

                            <!-- Matches -->
                            <g class="matches">
                                <g v-for="m in positionedMatches" :key="m.id"
                                   :class="[canEdit ? 'cursor-pointer' : 'cursor-not-allowed']"
                                   class="match-group"
                                   @click="handleMatchClick(m.id)">
                                    <!-- Match background -->
                                    <rect
                                        :class="getMatchClass(m)" :height="nodeHeight"
                                        :width="nodeWidth" :x="m.x"
                                        :y="m.y"
                                        rx="8"
                                    />

                                    <!-- Walkover indicator -->
                                    <g v-if="m.isWalkover">
                                        <rect
                                            :x="m.x + 2"
                                            :y="m.y + 2"
                                            fill="#fbbf24"
                                            height="16"
                                            rx="2"
                                            width="24"
                                        />
                                        <text :x="m.x + 14" :y="m.y + 13" class="walkover-text"
                                              text-anchor="middle">
                                            W/O
                                        </text>
                                    </g>

                                    <!-- Match number -->
                                    <text :x="m.x + 30" :y="m.y + 14" class="match-number">
                                        {{ t('Match') }} #{{ m.match_code }}
                                    </text>

                                    <!-- Player 1 -->
                                    <g>
                                        <rect
                                            :class="m.winner_id === m.player1?.id ? 'player-winner' : 'player-bg'"
                                            :height="30"
                                            :width="nodeWidth" :x="m.x"
                                            :y="m.y + 20"
                                            rx="4"
                                        />
                                        <text :x="m.x + 8" :y="m.y + 38" class="player-name">
                                            {{ getPlayerDisplay(m.player1, m.isWalkover, !!m.player2) }}
                                        </text>
                                        <text :x="m.x + nodeWidth - 25" :y="m.y + 38"
                                              class="player-score">
                                            {{ m.player1_score ?? '-' }}
                                        </text>
                                    </g>

                                    <!-- Player 2 -->
                                    <g>
                                        <rect
                                            :class="m.winner_id === m.player2?.id ? 'player-winner' : 'player-bg'"
                                            :height="30"
                                            :width="nodeWidth" :x="m.x"
                                            :y="m.y + 50"
                                            rx="4"
                                        />
                                        <text :x="m.x + 8" :y="m.y + 68" class="player-name">
                                            {{ getPlayerDisplay(m.player2, m.isWalkover, !!m.player1) }}
                                        </text>
                                        <text :x="m.x + nodeWidth - 25" :y="m.y + 68"
                                              class="player-score">
                                            {{ m.player2_score ?? '-' }}
                                        </text>
                                    </g>

                                    <!-- Status indicator -->
                                    <circle
                                        :cx="m.x + nodeWidth - 10"
                                        :cy="m.y + 10"
                                        :fill="getStatusColor(m.status)"
                                        r="4"
                                    />
                                </g>
                            </g>
                        </svg>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.bracket-container {
    max-height: calc(100vh - 400px);
    min-height: 500px;
}

/* Fullscreen adjustments */
.bracket-fullscreen-container:fullscreen {
    background: white;
    padding: 1rem;
}

.bracket-fullscreen-container:fullscreen .bracket-container {
    max-height: calc(100vh - 120px);
}

.dark .bracket-fullscreen-container:fullscreen {
    background: #111827;
}

.bracket-zoom-wrapper {
    transform-origin: top left;
    transition: transform 0.2s ease-out;
}

.bracket-svg {
    font-family: system-ui, -apple-system, sans-serif;
}

/* Prevent text selection on touch devices */
.touch-none {
    -webkit-touch-callout: none;
    -webkit-user-select: none;
    user-select: none;
}

/* Match styles */
.match-pending {
    fill: #ffffff;
    stroke: #d1d5db;
    stroke-width: 1;
}

.match-ready {
    fill: #dbeafe;
    stroke: #3b82f6;
    stroke-width: 1.5;
}

.match-active {
    fill: #fee2e2;
    stroke: #ef4444;
    stroke-width: 2;
}

.match-verification {
    fill: #f3e8ff;
    stroke: #9333ea;
    stroke-width: 1.5;
}

.match-completed {
    fill: rgba(230, 255, 237, 0.1);
    stroke: #10b981;
    stroke-width: 1;
}

.match-group:hover .match-pending,
.match-group:hover .match-ready,
.match-group:hover .match-active,
.match-group:hover .match-verification,
.match-group:hover .match-completed {
    filter: brightness(0.95);
}

/* Player backgrounds */
.player-bg {
    fill: transparent;
}

.player-winner {
    fill: #16a34a;
    fill-opacity: 0.1;
}

/* Text styles */
.match-number {
    font-size: 11px;
    fill: #6b7280;
}

.player-name {
    font-size: 13px;
    font-weight: 500;
    fill: #111827;
}

.player-score {
    font-size: 14px;
    font-weight: 600;
    fill: #111827;
    text-anchor: end;
}

.walkover-text {
    font-size: 10px;
    font-weight: bold;
    fill: #78350f;
}

/* Connector lines */
.connector-line {
    stroke: #9ca3af;
    stroke-width: 2;
}

/* Dark mode adjustments */
.dark .match-pending {
    fill: #374151;
    stroke: #4b5563;
}

.dark .match-ready {
    fill: #1e3a8a;
    stroke: #3b82f6;
}

.dark .match-active {
    fill: #7f1d1d;
    stroke: #ef4444;
}

.dark .match-verification {
    fill: #581c87;
    stroke: #9333ea;
}

.dark .match-completed {
    fill: #064e3b;
    stroke: #10b981;
}

.dark .player-name,
.dark .player-score {
    fill: #f3f4f6;
}

.dark .match-number {
    fill: #9ca3af;
}

.dark .connector-line {
    stroke: #4b5563;
}

.dark .walkover-text {
    fill: #fbbf24;
}

/* Scrollbar styling */
.bracket-container::-webkit-scrollbar {
    width: 8px;
    height: 8px;
}

.bracket-container::-webkit-scrollbar-track {
    background: #f3f4f6;
    border-radius: 4px;
}

.dark .bracket-container::-webkit-scrollbar-track {
    background: #1f2937;
}

.bracket-container::-webkit-scrollbar-thumb {
    background: #9ca3af;
    border-radius: 4px;
}

.bracket-container::-webkit-scrollbar-thumb:hover {
    background: #6b7280;
}

.dark .bracket-container::-webkit-scrollbar-thumb {
    background: #4b5563;
}

.dark .bracket-container::-webkit-scrollbar-thumb:hover {
    background: #6b7280;
}
</style>
