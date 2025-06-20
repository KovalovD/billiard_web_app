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
    bracketSide: 'upper' | 'lower' | null;
    stage: string;
}

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
        .map(m => ({
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
            isWalkover: m.status === 'completed' && (
                (!!m.player1_id && !m.player2_id) || (!m.player1_id && !!m.player2_id)
            ),
            bracketSide: 'upper',
            stage: m.stage
        }))
        .sort((a, b) => a.round - b.round || a.slot - b.slot);
});

const lowerBracketMatches = computed<BracketMatch[]>(() => {
    return props.matches
        .filter(m => m.bracket_side === 'lower')
        .map(m => ({
            id: m.id,
            round: parseInt(m.match_code.split('_R')[1]?.split('M')[0] || '0') - 1,
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
            bracketSide: 'lower',
            stage: m.stage
        }))
        .sort((a, b) => a.round - b.round || a.slot - b.slot);
});

const grandFinals = computed<BracketMatch[]>(() => {
    return props.matches
        .filter(m => m.round === 'grand_finals')
        .map(m => ({
            id: m.id,
            round: 0,
            slot: m.match_code === 'GF_RESET' ? 1 : 0,
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
            isWalkover: false,
            bracketSide: null,
            stage: m.stage
        }));
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
    return firstRoundMatches * (nodeHeight + vGap) - vGap + 60; // Extra space for label
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

// Calculate positions for lower bracket with proper spacing
const positionedLowerMatches = computed(() => {
    const list: Array<BracketMatch & { x: number; y: number }> = [];
    const baseY = upperBracketHeight.value + 100;

    // Track positions for connection drawing
    const roundPositions = new Map<number, { matches: any[], spacing: number }>();

    lowerRounds.value.forEach((roundMatches, roundIndex) => {
        const lowerRoundNumber = roundIndex + 1;
        const isDropRound = lowerRoundNumber % 2 === 1;

        let spacing: number;
        let yOffset: number;

        if (lowerRoundNumber === 1) {
            // First round: matches from upper R1 losers
            spacing = (nodeHeight + vGap) * 2; // Space for pairing upper R1 losers
            yOffset = 0;
        } else if (isDropRound) {
            // Drop rounds: spacing based on corresponding upper round
            const upperRoundIndex = Math.floor(lowerRoundNumber / 2);
            spacing = Math.pow(2, upperRoundIndex) * (nodeHeight + vGap);

            // Align with previous round winners
            const prevRound = roundPositions.get(roundIndex - 1);
            if (prevRound && prevRound.matches.length > 0) {
                // Center align with previous round
                const prevFirstY = prevRound.matches[0].y;
                const prevLastY = prevRound.matches[prevRound.matches.length - 1].y;
                const prevCenter = (prevFirstY + prevLastY) / 2;
                const thisHeight = (roundMatches.length - 1) * spacing;
                yOffset = prevCenter - thisHeight / 2 - baseY - 40;
            } else {
                yOffset = 0;
            }
        } else {
            // Regular rounds: half the matches of previous round
            const prevRound = roundPositions.get(roundIndex - 1);
            if (prevRound) {
                spacing = prevRound.spacing * 2;
                // Align with previous round pairs
                yOffset = prevRound.matches[0].y - baseY - 40;
            } else {
                spacing = (nodeHeight + vGap) * 2;
                yOffset = 0;
            }
        }

        const roundMatchPositions: any[] = [];

        roundMatches.forEach((match, matchIndex) => {
            const x = roundIndex * (nodeWidth + hGap);
            const y = baseY + 40 + yOffset + matchIndex * spacing;

            const positionedMatch = {...match, x, y};
            list.push(positionedMatch);
            roundMatchPositions.push(positionedMatch);
        });

        roundPositions.set(roundIndex, {matches: roundMatchPositions, spacing});
    });

    return list;
});

// Calculate grand finals position
const positionedGrandFinals = computed(() => {
    const upperFinalX = Math.max(...positionedUpperMatches.value.map(m => m.x), 0) + nodeWidth + hGap;
    const upperFinal = positionedUpperMatches.value.find(m => m.round === upperRounds.value.length - 1);
    const lowerFinal = positionedLowerMatches.value.find(m => m.round === lowerRounds.value.length - 1);

    let centerY = 40;
    if (upperFinal && lowerFinal) {
        centerY = (upperFinal.y + lowerFinal.y) / 2;
    } else if (upperFinal) {
        centerY = upperFinal.y;
    }

    return grandFinals.value.map((match, index) => ({
        ...match,
        x: upperFinalX,
        y: centerY + index * (nodeHeight + vGap * 2) - (grandFinals.value.length - 1) * (nodeHeight + vGap) / 2
    }));
});

// Find next match in upper bracket
function nextOfUpper(match: BracketMatch): any {
    return positionedUpperMatches.value.find(
        m => m.round === match.round + 1 && m.slot === Math.floor(match.slot / 2)
    );
}

// Calculate connector lines for upper bracket
const upperSegments = computed(() => {
    const segs: any[] = [];
    positionedUpperMatches.value.forEach(m => {
        const n = nextOfUpper(m);
        if (!n) return;

        const midX = n.x - hGap / 2;
        const yFrom = m.y + nodeHeight / 2;
        const yTo = n.y + nodeHeight / 2;

        segs.push({id: `${m.id}-h1`, x1: m.x + nodeWidth, y1: yFrom, x2: midX, y2: yFrom});
        segs.push({id: `${m.id}-v`, x1: midX, y1: yFrom, x2: midX, y2: yTo});
        segs.push({id: `${m.id}-h2`, x1: midX, y1: yTo, x2: n.x, y2: yTo});
    });

    // Connect upper final to grand final
    const upperFinal = positionedUpperMatches.value.find(m => m.round === upperRounds.value.length - 1);
    const gf = positionedGrandFinals.value[0];
    if (upperFinal && gf) {
        const midX = gf.x - hGap / 2;
        const yFrom = upperFinal.y + nodeHeight / 2;
        const yTo = gf.y + nodeHeight / 2;

        segs.push({id: 'uf-gf-h1', x1: upperFinal.x + nodeWidth, y1: yFrom, x2: midX, y2: yFrom});
        segs.push({id: 'uf-gf-v', x1: midX, y1: yFrom, x2: midX, y2: yTo});
        segs.push({id: 'uf-gf-h2', x1: midX, y1: yTo, x2: gf.x, y2: yTo});
    }

    return segs;
});

// Calculate connector lines for lower bracket
const lowerSegments = computed(() => {
    const segs: any[] = [];

    positionedLowerMatches.value.forEach((m) => {
        if (m.round === 0) return; // First round has no previous matches in lower bracket

        const prevRoundMatches = positionedLowerMatches.value.filter(pm => pm.round === m.round - 1);
        const lowerRoundNumber = m.round + 1;
        const isDropRound = lowerRoundNumber % 2 === 1;

        if (!isDropRound) {
            // Regular rounds: connect from two previous lower bracket matches
            const prevMatch1 = prevRoundMatches.find(pm => pm.slot === m.slot * 2);
            const prevMatch2 = prevRoundMatches.find(pm => pm.slot === m.slot * 2 + 1);

            if (prevMatch1) {
                const midX = m.x - hGap / 2;
                const yFrom = prevMatch1.y + nodeHeight / 2;
                const yTo = m.y + nodeHeight / 2;

                segs.push({id: `${prevMatch1.id}-h1`, x1: prevMatch1.x + nodeWidth, y1: yFrom, x2: midX, y2: yFrom});
                segs.push({id: `${prevMatch1.id}-v`, x1: midX, y1: yFrom, x2: midX, y2: yTo});
            }

            if (prevMatch2) {
                const midX = m.x - hGap / 2;
                const yFrom = prevMatch2.y + nodeHeight / 2;
                const yTo = m.y + nodeHeight / 2;

                segs.push({id: `${prevMatch2.id}-h1`, x1: prevMatch2.x + nodeWidth, y1: yFrom, x2: midX, y2: yFrom});
                segs.push({id: `${prevMatch2.id}-v`, x1: midX, y1: yFrom, x2: midX, y2: yTo});
            }

            if (prevMatch1 || prevMatch2) {
                segs.push({
                    id: `${m.id}-h2`,
                    x1: m.x - hGap / 2,
                    y1: m.y + nodeHeight / 2,
                    x2: m.x,
                    y2: m.y + nodeHeight / 2
                });
            }
        } else if (lowerRoundNumber > 1) {
            // Drop rounds (except first): connect from previous lower bracket match
            const prevMatch = prevRoundMatches.find(pm => pm.slot === m.slot);

            if (prevMatch) {
                const midX = m.x - hGap / 2;
                const yFrom = prevMatch.y + nodeHeight / 2;
                const yTo = m.y + nodeHeight / 2;

                segs.push({id: `${prevMatch.id}-h1`, x1: prevMatch.x + nodeWidth, y1: yFrom, x2: midX, y2: yFrom});
                segs.push({id: `${prevMatch.id}-v`, x1: midX, y1: yFrom, x2: midX, y2: yTo});
                segs.push({id: `${prevMatch.id}-h2`, x1: midX, y1: yTo, x2: m.x, y2: yTo});
            }
        }
    });

    // Connect lower final to grand final
    const lowerFinal = positionedLowerMatches.value.find(m => m.round === lowerRounds.value.length - 1);
    const gf = positionedGrandFinals.value[0];
    if (lowerFinal && gf) {
        const midX = gf.x - hGap / 2;
        const yFrom = lowerFinal.y + nodeHeight / 2;
        const yTo = gf.y + nodeHeight / 2;

        segs.push({id: 'lf-gf-h1', x1: lowerFinal.x + nodeWidth, y1: yFrom, x2: midX, y2: yFrom});
        segs.push({id: 'lf-gf-v', x1: midX, y1: yFrom, x2: midX, y2: yTo});
        segs.push({id: 'lf-gf-h2', x1: midX, y1: yTo, x2: gf.x, y2: yTo});
    }

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

// All positioned matches
const allPositionedMatches = computed(() => [
    ...positionedUpperMatches.value,
    ...positionedLowerMatches.value,
    ...positionedGrandFinals.value
]);

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
            container.scrollLeft = (container.scrollWidth - container.clientWidth) / 2;
            container.scrollTop = 100; // Start near top
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

const getMatchClass = (match: any) => {
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

                            <!-- Upper bracket connectors -->
                            <g class="connectors">
                                <line
                                    v-for="seg in upperSegments"
                                    :key="seg.id"
                                    :x1="seg.x1" :x2="seg.x2" :y1="seg.y1" :y2="seg.y2"
                                    class="connector-line"
                                />
                            </g>

                            <!-- Lower bracket connectors -->
                            <g class="connectors">
                                <line
                                    v-for="seg in lowerSegments"
                                    :key="seg.id"
                                    :x1="seg.x1" :x2="seg.x2" :y1="seg.y1" :y2="seg.y2"
                                    class="connector-line connector-line-lower"
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
                                        :class="[getMatchClass(m), m.bracketSide === 'lower' ? 'lower-bracket-match' : '']"
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
                                        <text :x="m.x + 14" :y="m.y + 13" class="walkover-text"
                                              text-anchor="middle">
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

/* Bracket labels */
.bracket-label {
    font-size: 14px;
    font-weight: 600;
    fill: #374151;
}

.dark .bracket-label {
    fill: #d1d5db;
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

.connector-line-lower {
    stroke: #a78bfa;
    stroke-dasharray: 4 2;
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

.dark .connector-line-lower {
    stroke: #7c3aed;
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
