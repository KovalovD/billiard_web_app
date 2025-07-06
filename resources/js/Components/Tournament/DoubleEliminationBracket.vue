<script lang="ts" setup>
import {computed, provide, ref, watch} from 'vue';
import type {Tournament, TournamentMatch} from '@/types/api';
import {useLocale} from '@/composables/useLocale';
import {useBracket} from '@/composables/useBracket';
import BaseBracket from '@/Components/Tournament/BaseBracket.vue';
import BracketStyles from '@/Components/Tournament/BracketStyles.vue';

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

// Use the bracket composable with different config for double elimination
const {
    nodeWidth,
    nodeHeight,
    hGap,
    vGap,
    zoomLevel,
    isFullscreen,
    bracketContainerRef,
    bracketScrollContainerRef,
    transformMatch,
    zoomIn,
    zoomOut,
    resetZoom,
    handleTouchStart,
    handleTouchMove,
    handleTouchEnd,
    handleWheel,
    toggleFullscreen,
    findMyMatch,
    getMatchClass,
    isCurrentUserMatch,
    getPlayerDisplay,
} = useBracket(
    props.currentUserId,
    {
        initialZoom: 0.8,
        hGap: 140 // Slightly wider for double elimination
    }
);

// Provide methods for BaseBracket
provide('zoomIn', zoomIn);
provide('zoomOut', zoomOut);
provide('resetZoom', resetZoom);
provide('toggleFullscreen', toggleFullscreen);
provide('handleTouchStart', handleTouchStart);
provide('handleTouchMove', handleTouchMove);
provide('handleTouchEnd', handleTouchEnd);
provide('handleWheel', handleWheel);

// Separate upper and lower bracket matches
const upperBracketMatches = computed(() => {
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

const lowerBracketMatches = computed(() => {
    return props.matches
        .filter(m => m.bracket_side === 'lower')
        .map(m => transformMatch(m, 'lower', parseInt(m.match_code.split('_R')[1]?.split('M')[0] || '0') - 1))
        .sort((a, b) => a.round - b.round || a.slot - b.slot);
});

const grandFinals = computed(() => {
    return props.matches
        .filter(m => m.match_code === 'GF' || m.match_code === 'GF_RESET')
        .map(m => transformMatch(m, null, 0))
        .sort((a, b) => a.slot - b.slot);
});

// Group matches by round
const upperRounds = computed(() => {
    const map = new Map<number, ReturnType<typeof transformMatch>[]>();
    upperBracketMatches.value.forEach(m => {
        (map.get(m.round) || map.set(m.round, []).get(m.round)!).push(m);
    });
    return [...map.entries()]
        .sort(([a], [b]) => a - b)
        .map(([, ms]) => ms.sort((a, b) => a.slot - b.slot));
});

const lowerRounds = computed(() => {
    const map = new Map<number, ReturnType<typeof transformMatch>[]>();
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
    const list: Array<ReturnType<typeof transformMatch> & { x: number; y: number }> = [];

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
    const list: Array<ReturnType<typeof transformMatch> & { x: number; y: number }> = [];
    const baseY = upperBracketHeight.value + 100;
    const totalLowerRounds = lowerRounds.value.length;

    // Calculate the target Y position for grand finals
    const upperFinal = positionedUpperMatches.value.find(m => m.round === upperRounds.value.length - 1);
    const targetY = upperFinal ? upperFinal.y : 200;

    // Create a map to track positioned matches
    const positionedMatchesMap = new Map<number, { x: number; y: number }>();

    // Create a map of all lower matches by ID for quick lookup
    const lowerMatchesById = new Map<number, ReturnType<typeof transformMatch>>();
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

const handleMatchClick = (matchId: number) => {
    if (props.canEdit) {
        emit('open-match', matchId);
    }
};

const handleFindMyMatch = () => {
    findMyMatch(allPositionedMatches.value);
};

// Set refs from BaseBracket
const baseBracketRef = ref<InstanceType<typeof BaseBracket>>();
watch(baseBracketRef, (newRef) => {
    if (newRef) {
        bracketContainerRef.value = newRef.bracketContainerRef;
        bracketScrollContainerRef.value = newRef.bracketScrollContainerRef;
    }
});
</script>

<template>
    <BaseBracket
        ref="baseBracketRef"
        :title="t('Double Elimination Bracket')"
        :can-edit="canEdit"
        :has-current-user-active-match="hasCurrentUserActiveMatch"
        :zoom-level="zoomLevel"
        :is-fullscreen="isFullscreen"
        @find-my-match="handleFindMyMatch"
    >
        <BracketStyles/>
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
                <text :y="upperBracketHeight + 85" class="bracket-label" x="20">{{ t('Lower Bracket') }}</text>

                <!-- Grand Finals Label -->
                <text
                    v-if="positionedGrandFinals.length > 0"
                    :x="positionedGrandFinals[0].x"
                    :y="positionedGrandFinals[0].y - 15"
                    class="bracket-label text-center"
                >
                    {{ t('Grand Finals') }}
                </text>

                <!-- Connector lines -->
                <g class="connectors">
                    <line
                        v-for="seg in connectorSegments"
                        :key="seg.id"
                        :class="[
                            'connector-line',
                            seg.type === 'lower' ? 'connector-line-lower' : ''
                        ]"
                        :x1="seg.x1"
                        :x2="seg.x2"
                        :y1="seg.y1"
                        :y2="seg.y2"
                    />
                </g>

                <!-- All Matches -->
                <g class="matches">
                    <g
                        v-for="m in allPositionedMatches"
                        :key="m.id"
                        :class="[canEdit ? 'cursor-pointer' : 'cursor-not-allowed']"
                        class="match-group"
                        @click="handleMatchClick(m.id)"
                    >
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
    </BaseBracket>
</template>
