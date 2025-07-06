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

// Use the bracket composable
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
    props.currentUserId
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

// Transform matches to bracket format
const bracketMatches = computed(() => {
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
        .map(m => transformMatch(m, null, roundMap[m.round!]))
        .sort((a, b) => a.round - b.round || a.slot - b.slot);
});

// Group matches by round
const rounds = computed(() => {
    const map = new Map<number, ReturnType<typeof transformMatch>[]>();
    bracketMatches.value.forEach(m => {
        (map.get(m.round) || map.set(m.round, []).get(m.round)!).push(m);
    });
    return [...map.entries()]
        .sort(([a], [b]) => a - b)
        .map(([, ms]) => ms.sort((a, b) => a.slot - b.slot));
});

// Calculate match positions
const positionedMatches = computed(() => {
    const list: Array<ReturnType<typeof transformMatch> & { x: number; y: number }> = [];

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

// Find current user's non-completed matches
const currentUserActiveMatches = computed(() => {
    if (!props.currentUserId) return [];

    return positionedMatches.value.filter(match =>
        (match.player1?.id === props.currentUserId || match.player2?.id === props.currentUserId) &&
        match.status !== 'completed'
    );
});

const hasCurrentUserActiveMatch = computed(() => currentUserActiveMatches.value.length > 0);

// Find next match
function nextOf(match: ReturnType<typeof transformMatch>) {
    return positionedMatches.value.find(
        m => m.round === match.round + 1 && m.slot === Math.floor(match.slot / 2)
    );
}

// Calculate connector lines
const segments = computed(() => {
    const segs: any[] = [];
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

const handleMatchClick = (matchId: number) => {
    if (props.canEdit) {
        emit('open-match', matchId);
    }
};

const handleFindMyMatch = () => {
    findMyMatch(positionedMatches.value);
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
        :title="t('Tournament Bracket')"
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
                <!-- Connector lines -->
                <g class="connectors">
                    <line
                        v-for="seg in segments"
                        :key="seg.id"
                        :x1="seg.x1"
                        :x2="seg.x2"
                        :y1="seg.y1"
                        :y2="seg.y2"
                        class="connector-line"
                    />
                </g>

                <!-- Matches -->
                <g class="matches">
                    <g
                        v-for="m in positionedMatches"
                        :key="m.id"
                        :class="[canEdit ? 'cursor-pointer' : 'cursor-not-allowed']"
                        class="match-group"
                        @click="handleMatchClick(m.id)"
                    >
                        <!-- Match background -->
                        <rect
                            :class="[
                                getMatchClass(m),
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
                            {{ t('Match') }} #{{ m.match_code }}
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
