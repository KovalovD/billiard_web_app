<!-- resources/js/Components/TournamentBrackets/DoubleEliminationBracketViewer.vue -->
<script lang="ts" setup>
import {Button} from '@/Components/ui';
import {useLocale} from '@/composables/useLocale';
import type {Tournament, TournamentMatch} from '@/types/api';
import {ExpandIcon, MinusIcon, PlusIcon, RotateCcwIcon, ShrinkIcon, UserIcon} from 'lucide-vue-next';
import {computed, nextTick, onMounted, onUnmounted, ref, watch} from 'vue';

const props = defineProps<{
    matches: TournamentMatch[];
    tournament: Tournament;
    canEdit: boolean;
    currentUserId?: number;
}>();

const emit = defineEmits<{
    'open-match': [matchId: number];
}>();

const {t} = useLocale();

// Zoom and fullscreen states
const zoomLevel = ref(0.8);
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
const hGap = 140;
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
    bracketSide: 'upper' | 'lower' | null;
    stage: string;
    next_match_id?: number;
    previous_match1_id?: number;
    previous_match2_id?: number;
    loser_next_match_id?: number;
    loser_next_match_position?: number;
}

// Transform match data with all relationships
const transformMatch = (m: TournamentMatch, bracketSide: 'upper' | 'lower' | null, round: number): BracketMatch => ({
    id: m.id,
    round,
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
    isWalkover: m.status === 'completed' && (
        (!!m.player1_id && !m.player2_id) || (!m.player1_id && !!m.player2_id)
    ),
    bracketSide,
    stage: m.stage,
    next_match_id: m.next_match_id,
    previous_match1_id: m.previous_match1_id,
    previous_match2_id: m.previous_match2_id,
    loser_next_match_id: m.loser_next_match_id,
    loser_next_match_position: m.loser_next_match_position
});

// Separate upper and lower bracket matches
const upperBracketMatches = computed<BracketMatch[]>(() => {
    const roundMap: Record<string, number> = {
        'round_128': 0,
        'round_64': 1,
        'round_32': 2,
        'round_16': 3,
        'quarterfinals': 4,
        'semifinals': 5,
        'finals': 6
    };

    return props.matches
        .filter(m => m.bracket_side === 'upper' && m.round && roundMap[m.round] !== undefined)
        .map(m => transformMatch(m, 'upper', roundMap[m.round!]))
        .sort((a, b) => a.round - b.round || a.slot - b.slot);
});

const lowerBracketMatches = computed<BracketMatch[]>(() => {
    return props.matches
        .filter(m => m.bracket_side === 'lower')
        .map(m => transformMatch(m, 'lower', parseInt(m.match_code.split('_R')[1]?.split('M')[0] || '0') - 1))
        .sort((a, b) => a.round - b.round || a.slot - b.slot);
});

const grandFinals = computed<BracketMatch[]>(() => {
    return props.matches
        .filter(m => m.match_code === 'GF' || m.match_code === 'GF_RESET')
        .map(m => transformMatch(m, null, 0))
        .sort((a, b) => a.slot - b.slot);
});

// Group matches by round
const upperRounds = computed(() => {
    const map = new Map<number, BracketMatch[]>();
    upperBracketMatches.value.forEach(m => {
        (map.get(m.round) || map.set(m.round, []).get(m.round)!).push(m);
    });
    return [...map.entries()]
        .sort(([a], [b]) => a - b)
        .map(([, ms]) => ms.sort((a, b) => a.slot - b.slot));
});

const lowerRounds = computed(() => {
    const map = new Map<number, BracketMatch[]>();
    lowerBracketMatches.value.forEach(m => {
        (map.get(m.round) || map.set(m.round, []).get(m.round)!).push(m);
    });
    return [...map.entries()]
        .sort(([a], [b]) => a - b)
        .map(([, ms]) => ms.sort((a, b) => a.slot - b.slot));
});

// Calculate upper bracket height
const upperBracketHeight = computed(() => {
    if (upperRounds.value.length === 0) return 0;
    const firstRoundMatches = upperRounds.value[0]?.length || 0;
    return firstRoundMatches * (nodeHeight + vGap) - vGap + 60;
});

// Calculate positions for upper bracket
const positionedUpperMatches = computed(() => {
    const list: Array<BracketMatch & { x: number; y: number }> = [];

    upperRounds.value.forEach((roundMatches, roundIndex) => {
        const spacing = Math.pow(2, roundIndex);

        roundMatches.forEach((match, matchIndex) => {
            const x = roundIndex * (nodeWidth + hGap);
            const y = 40 + matchIndex * spacing * (nodeHeight + vGap) + (spacing - 1) * (nodeHeight + vGap) / 2;
            list.push({...match, x, y});
        });
    });

    return list;
});

// Calculate positions for lower bracket with proper alignment
const positionedLowerMatches = computed(() => {
    const list: Array<BracketMatch & { x: number; y: number }> = [];
    const baseY = upperBracketHeight.value + 100;
    const totalLowerRounds = lowerRounds.value.length;

    // Calculate the target Y position for grand finals
    const upperFinal = positionedUpperMatches.value.find(m => m.round === upperRounds.value.length - 1);
    const targetY = upperFinal ? upperFinal.y : 200;

    // Create a map to track positioned matches
    const positionedMatchesMap = new Map<number, { x: number; y: number }>();

    // Create a map of all lower matches by ID for quick lookup
    const lowerMatchesById = new Map<number, BracketMatch>();
    lowerBracketMatches.value.forEach(match => {
        lowerMatchesById.set(match.id, match);
    });

    lowerRounds.value.forEach((roundMatches, roundIndex) => {
        const remainingRounds = totalLowerRounds - roundIndex;
        const stepUpFactor = remainingRounds <= 4 ? (4 - remainingRounds) * 0.15 : 0;

        roundMatches.forEach((match, matchIndex) => {
            const x = roundIndex * (nodeWidth + hGap);
            let y = baseY + 40;

            // Check if this match has previous matches in the same bracket
            const hasPrevMatch1 = match.previous_match1_id && lowerMatchesById.has(match.previous_match1_id);
            const hasPrevMatch2 = match.previous_match2_id && lowerMatchesById.has(match.previous_match2_id);

            if (hasPrevMatch1 && hasPrevMatch2) {
                // Position between two previous matches
                const prev1Pos = positionedMatchesMap.get(match.previous_match1_id!);
                const prev2Pos = positionedMatchesMap.get(match.previous_match2_id!);

                if (prev1Pos && prev2Pos) {
                    // Calculate middle position
                    y = (prev1Pos.y + prev2Pos.y) / 2;

                    // Apply step-up factor towards grand finals
                    const stepUp = (targetY - y) * stepUpFactor;
                    y += stepUp;
                }
            } else if (hasPrevMatch1 || hasPrevMatch2) {
                // Position next to single previous match
                const prevMatchId = hasPrevMatch1 ? match.previous_match1_id : match.previous_match2_id;
                const prevPos = positionedMatchesMap.get(prevMatchId!);

                if (prevPos) {
                    y = prevPos.y;

                    // Apply step-up factor
                    const stepUp = (targetY - y) * stepUpFactor;
                    y += stepUp;
                }
            } else {
                // No previous matches in same bracket (first round or receiving from upper bracket)
                // Use default spacing based on round
                const spacing = Math.pow(2, Math.floor((roundIndex + 1) / 2)) * (nodeHeight + vGap);
                y = baseY + 40 + matchIndex * spacing;
            }

            const positionedMatch = {...match, x, y};
            list.push(positionedMatch);
            positionedMatchesMap.set(match.id, {x, y});
        });
    });

    return list;
});

// Calculate grand finals position
const positionedGrandFinals = computed(() => {
    const maxUpperX = Math.max(...positionedUpperMatches.value.map(m => m.x), 0);
    const maxLowerX = Math.max(...positionedLowerMatches.value.map(m => m.x), 0);
    const grandFinalX = Math.max(maxUpperX, maxLowerX) + nodeWidth + hGap * 1.5;

    const upperFinal = positionedUpperMatches.value.find(m => m.round === upperRounds.value.length - 1);
    const lowerFinal = positionedLowerMatches.value[positionedLowerMatches.value.length - 1];

    let centerY = 40;
    if (upperFinal && lowerFinal) {
        centerY = (upperFinal.y + lowerFinal.y) / 2;
    } else if (upperFinal) {
        centerY = upperFinal.y;
    }

    return grandFinals.value.map((match, index) => ({
        ...match,
        x: grandFinalX,
        y: centerY + index * (nodeHeight + vGap * 2) - (grandFinals.value.length - 1) * (nodeHeight + vGap) / 2
    }));
});

// All positioned matches
const allPositionedMatches = computed(() => [
    ...positionedUpperMatches.value,
    ...positionedLowerMatches.value,
    ...positionedGrandFinals.value
]);

// Create a map for quick position lookup by match ID
const matchPositionMap = computed(() => {
    const map = new Map<number, { x: number; y: number }>();
    allPositionedMatches.value.forEach(match => {
        map.set(match.id, {x: match.x, y: match.y});
    });
    return map;
});

// Find current user's non-completed matches
const currentUserActiveMatches = computed(() => {
    if (!props.currentUserId) return [];

    return allPositionedMatches.value.filter(match =>
        (match.player1?.id === props.currentUserId || match.player2?.id === props.currentUserId) &&
        match.status !== 'completed'
    );
});

const hasCurrentUserActiveMatch = computed(() => currentUserActiveMatches.value.length > 0);

// Find and focus on user's match
const findMyMatch = () => {
    if (currentUserActiveMatches.value.length === 0) return;

    // Find the most relevant match (active > ready > pending)
    const priorityOrder = ['in_progress', 'verification', 'ready', 'pending'];
    const sortedMatches = [...currentUserActiveMatches.value].sort((a, b) => {
        const aIndex = priorityOrder.indexOf(a.status);
        const bIndex = priorityOrder.indexOf(b.status);
        return aIndex - bIndex;
    });

    const targetMatch = sortedMatches[0];
    if (!targetMatch) return;

    // Set zoom to comfortable level
    setZoom(1.2);

    // Scroll to match position
    nextTick(() => {
        if (bracketScrollContainerRef.value) {
            const container = bracketScrollContainerRef.value;
            const matchX = targetMatch.x * zoomLevel.value;
            const matchY = targetMatch.y * zoomLevel.value;

            // Center the match in viewport
            container.scrollLeft = matchX - container.clientWidth / 2 + (nodeWidth * zoomLevel.value) / 2;
            container.scrollTop = matchY - container.clientHeight / 2 + (nodeHeight * zoomLevel.value) / 2;
        }
    });
};

// Calculate connector lines for progression paths
const connectorSegments = computed(() => {
    const segs: any[] = [];

    allPositionedMatches.value.forEach(match => {
        // Winner progression only
        if (match.next_match_id) {
            const nextPos = matchPositionMap.value.get(match.next_match_id);
            if (nextPos) {
                const fromX = match.x + nodeWidth;
                const fromY = match.y + nodeHeight / 2;
                const toX = nextPos.x;
                const toY = nextPos.y + nodeHeight / 2;

                const midX = fromX + (toX - fromX) / 2;

                segs.push({
                    id: `${match.id}-h1`,
                    x1: fromX,
                    y1: fromY,
                    x2: midX,
                    y2: fromY,
                    type: match.bracketSide === 'lower' ? 'lower' : 'upper'
                });

                segs.push({
                    id: `${match.id}-v`,
                    x1: midX,
                    y1: fromY,
                    x2: midX,
                    y2: toY,
                    type: match.bracketSide === 'lower' ? 'lower' : 'upper'
                });

                segs.push({
                    id: `${match.id}-h2`,
                    x1: midX,
                    y1: toY,
                    x2: toX,
                    y2: toY,
                    type: match.bracketSide === 'lower' ? 'lower' : 'upper'
                });
            }
        }
    });

    return segs;
});

// Calculate SVG dimensions
const svgWidth = computed(() => {
    const maxX = Math.max(
        ...positionedUpperMatches.value.map(m => m.x),
        ...positionedLowerMatches.value.map(m => m.x),
        ...positionedGrandFinals.value.map(m => m.x),
        0
    );
    return maxX + nodeWidth + 80;
});

const svgHeight = computed(() => {
    const maxY = Math.max(
        ...positionedUpperMatches.value.map(m => m.y),
        ...positionedLowerMatches.value.map(m => m.y),
        ...positionedGrandFinals.value.map(m => m.y),
        0
    );
    return maxY + nodeHeight + 80;
});

// Zoom functions
const setZoom = (newZoom: number) => {
    zoomLevel.value = Math.max(0.3, Math.min(2, newZoom));
};

const zoomIn = () => setZoom(zoomLevel.value + 0.1);
const zoomOut = () => setZoom(zoomLevel.value - 0.1);
const resetZoom = () => setZoom(0.8);

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

// Scroll to center
const scrollToCenter = () => {
    nextTick(() => {
        if (bracketScrollContainerRef.value) {
            const container = bracketScrollContainerRef.value;
            container.scrollLeft = 100;
            container.scrollTop = 100;
        }
    });
};

const getMatchClass = (match: any) => {
    if (match.status === 'completed') return 'match-completed';
    if (match.status === 'in_progress') return 'match-active';
    if (match.status === 'verification') return 'match-verification';
    if (match.status === 'ready') return 'match-ready';
    return 'match-pending';
};

const isCurrentUserMatch = (match: any) => {
    return props.currentUserId &&
        (match.player1?.id === props.currentUserId || match.player2?.id === props.currentUserId);
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

// Watch for bracket data changes to scroll to center
watch(() => allPositionedMatches.value.length, () => {
    if (allPositionedMatches.value.length > 0) {
        scrollToCenter();
    }
});

onMounted(() => {
    document.addEventListener('keydown', handleKeyboard);
    document.addEventListener('fullscreenchange', handleFullscreenChange);
    scrollToCenter();
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
                            {{ t('Double Elimination Bracket') }}
                        </h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            {{
                                canEdit ? t('Click on a match to view details') : t('View only mode - tournament not active')
                            }}
                        </p>
                    </div>

                    <!-- Zoom and Fullscreen Controls -->
                    <div class="flex items-center gap-2">
                        <!-- Find Me Button -->
                        <Button
                            v-if="hasCurrentUserActiveMatch"
                            :title="t('Find my match')"
                            size="sm"
                            @click="findMyMatch"
                        >
                            <UserIcon class="h-4 w-4 mr-1"/>
                            {{ t('Find Me') }}
                        </Button>

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
                            <!-- Upper Bracket Label -->
                            <text class="bracket-label" x="20" y="25">{{ t('Upper Bracket') }}</text>

                            <!-- Lower Bracket Label -->
                            <text :y="upperBracketHeight + 85" class="bracket-label" x="20">{{
                                    t('Lower Bracket')
                                }}
                            </text>

                            <!-- Grand Finals Label -->
                            <text v-if="positionedGrandFinals.length > 0"
                                  :x="positionedGrandFinals[0].x"
                                  :y="positionedGrandFinals[0].y - 15"
                                  class="bracket-label text-center">{{ t('Grand Finals') }}
                            </text>

                            <!-- Connector lines -->
                            <g class="connectors">
                                <line
                                    v-for="seg in connectorSegments"
                                    :key="seg.id"
                                    :class="[
                                        'connector-line',
                                        seg.type === 'lower' ? 'connector-line-lower' : ''
                                    ]" :x1="seg.x1" :x2="seg.x2" :y1="seg.y1"
                                    :y2="seg.y2"
                                />
                            </g>

                            <!-- All Matches -->
                            <g class="matches">
                                <g v-for="m in allPositionedMatches" :key="m.id"
                                   :class="[canEdit ? 'cursor-pointer' : 'cursor-not-allowed']"
                                   class="match-group"
                                   @click="handleMatchClick(m.id)">
                                    <!-- Match background -->
                                    <rect
                                        :class="[
                                            getMatchClass(m),
                                            m.bracketSide === 'lower' ? 'lower-bracket-match' : '',
                                            isCurrentUserMatch(m) ? 'user-match' : ''
                                        ]"
                                        :height="nodeHeight"
                                        :width="nodeWidth"
                                        :x="m.x"
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
                                        <text :x="m.x + 14" :y="m.y + 13" class="walkover-text" text-anchor="middle">
                                            W/O
                                        </text>
                                    </g>

                                    <!-- Match number -->
                                    <text :x="m.x + 30" :y="m.y + 14" class="match-number">
                                        {{ m.match_code }}
                                    </text>

                                    <!-- Player 1 -->
                                    <g>
                                        <rect
                                            :class="m.winner_id === m.player1?.id ? 'player-winner' : 'player-bg'"
                                            :height="30"
                                            :width="nodeWidth"
                                            :x="m.x"
                                            :y="m.y + 20"
                                            rx="4"
                                        />
                                        <text :x="m.x + 8" :y="m.y + 38" class="player-name">
                                            {{ getPlayerDisplay(m.player1, m.isWalkover, !!m.player2) }}
                                        </text>
                                        <text :x="m.x + nodeWidth - 25" :y="m.y + 38" class="player-score">
                                            {{ m.player1_score ?? '-' }}
                                        </text>
                                    </g>

                                    <!-- Player 2 -->
                                    <g>
                                        <rect
                                            :class="m.winner_id === m.player2?.id ? 'player-winner' : 'player-bg'"
                                            :height="30"
                                            :width="nodeWidth"
                                            :x="m.x"
                                            :y="m.y + 50"
                                            rx="4"
                                        />
                                        <text :x="m.x + 8" :y="m.y + 68" class="player-name">
                                            {{ getPlayerDisplay(m.player2, m.isWalkover, !!m.player1) }}
                                        </text>
                                        <text :x="m.x + nodeWidth - 25" :y="m.y + 68" class="player-score">
                                            {{ m.player2_score ?? '-' }}
                                        </text>
                                    </g>

                                    <!-- Status indicator - Only for in_progress matches -->
                                    <circle
                                        v-if="m.status === 'in_progress'"
                                        :cx="m.x + nodeWidth - 10"
                                        :cy="m.y + 10"
                                        class="status-in-progress"
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
    min-height: 600px;
}

/* Fullscreen adjustments */
.bracket-fullscreen-container:fullscreen {
    background: white;
    padding: 1rem;
}

.bracket-fullscreen-container:fullscreen .bracket-container {
    max-height: calc(100vh - 120px);
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

/* Bracket labels */
.bracket-label {
    font-size: 14px;
    font-weight: 600;
    fill: #374151;
}

/* Match styles */
.match-pending {
    fill: #ffffff;
    stroke: #d1d5db;
    stroke-width: 1;
}

.match-ready {
    fill: rgba(230, 255, 237, 1);
    stroke: #10b981;
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
    fill: rgba(219, 234, 254, 0.2);
    stroke: #3b82f6;
    stroke-width: 1;
}

/* Current user match highlight */
.user-match {
    stroke-width: 2 !important;
    stroke: rgba(255, 198, 109, 0.7) !important;
}

/* Lower bracket match styles */
.lower-bracket-match {
    opacity: 0.9;
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
    fill: #2b81fd;
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

.connector-line-lower {
    stroke: #a78bfa;
    stroke-dasharray: 4 2;
}

/* In-progress status indicator with breathing animation */
.status-in-progress {
    fill: #ef4444;
    animation: breathe 2s ease-in-out infinite;
}

@keyframes breathe {
    0%, 100% {
        opacity: 1;
        r: 4;
    }
    50% {
        opacity: 0.6;
        r: 5;
    }
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

.bracket-container::-webkit-scrollbar-thumb {
    background: #9ca3af;
    border-radius: 4px;
}

.bracket-container::-webkit-scrollbar-thumb:hover {
    background: #6b7280;
}
</style>
