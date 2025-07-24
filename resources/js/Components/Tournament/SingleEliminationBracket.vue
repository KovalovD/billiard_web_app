// resources/js/Components/Tournament/SingleElimination.vue
<script lang="ts" setup>
import {computed, provide, ref, watch} from 'vue';
import type {Tournament, TournamentMatch} from '@/types/api';
import {useLocale} from '@/composables/useLocale';
import {useBracket} from '@/composables/useBracket';
import BaseBracket from '@/Components/Tournament/BaseBracket.vue';
import BracketStyles from '@/Components/Tournament/BracketStyles.vue';
import MatchCard from '@/Components/Tournament/MatchCard.vue';

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

// Use more compact dimensions
const nodeWidth = 180;
const nodeHeight = 50;
const hGap = 80;
const vGap = 20;

// Use the bracket composable with compact settings
const {
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
    highlightMatchId,
} = useBracket(
    props.currentUserId,
    {initialZoom: 1}
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

    // Filter matches and get their round indices
    const filteredMatches = props.matches
        .filter(m => m.round && roundMap[m.round] !== undefined && m.bracket_side !== 'lower');

    // Find the minimum round index to normalize from 0
    const roundIndices = filteredMatches.map(m => roundMap[m.round!]);
    const minRoundIndex = roundIndices.length > 0 ? Math.min(...roundIndices) : 0;

    // Check if this is Olympic stage
    const isOlympicStage = props.tournament.stage === 'olympic' ||
        props.matches.some(m => m.stage === 'olympic');

    return filteredMatches
        .map(m => {
            // Pass 'olympic' as bracketSide to get 'O' prefix in display number
            const bracketSide = isOlympicStage ? 'olympic' as any : null;
            return transformMatch(m, bracketSide, roundMap[m.round!] - minRoundIndex);
        })
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
        <div class="p-4">
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
                    <MatchCard
                        v-for="m in positionedMatches"
                        :key="m.id"
                        :match="m"
                        :x="m.x"
                        :y="m.y"
                        :can-edit="canEdit"
                        :current-user-id="currentUserId"
                        :card-width="nodeWidth"
                        :card-height="nodeHeight"
                        :highlight-match-id="highlightMatchId"
                        @click="handleMatchClick"
                    />
                </g>
            </svg>
        </div>
    </BaseBracket>
</template>
