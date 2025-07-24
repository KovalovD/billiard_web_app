// Components/Tournament/MatchCard.vue
<template>
    <g
        :class="[canEdit ? 'cursor-pointer' : 'cursor-not-allowed']"
        class="match-group"
        @click="$emit('click', match.id)"
    >
        <!-- Match background -->
        <rect
            :class="[
                getMatchClass(match),
                match.bracketSide === 'lower' ? 'lower-bracket-match' : '',
                isHighlighted ? 'user-match' : ''
            ]"
            :height="cardHeight"
            :width="cardWidth"
            :x="x"
            :y="y"
            rx="6"
        />

        <!-- Walkover indicator -->
        <g v-if="match.isWalkover">
            <rect
                :x="x + 2"
                :y="y + 2"
                fill="#fbbf24"
                height="12"
                rx="2"
                width="20"
            />
            <text :x="x + 12" :y="y + 10" class="walkover-text compact" text-anchor="middle">
                W/O
            </text>
        </g>

        <!-- Match number - using displayNumber instead of match_code -->
        <text :x="x + cardWidth - 25" :y="y + 10" class="match-number compact" text-anchor="end">
            {{ match.displayNumber }}
        </text>

        <!-- Player 1 - compact height -->
        <g>
            <rect
                :class="match.winner_id === match.player1?.id ? 'player-winner' : 'player-bg'"
                :height="playerHeight"
                :width="cardWidth"
                :x="x"
                :y="y + headerHeight"
                rx="3"
            />
            <text :x="x + 4" :y="y + headerHeight + 12" class="player-name compact">
                {{ getPlayerDisplay(match.player1, match.isWalkover, !!match.player2) }}
            </text>
            <text :x="x + cardWidth - 20" :y="y + headerHeight + 12" class="player-score compact">
                {{ match.player1_score ?? '-' }}
            </text>
        </g>

        <!-- Divider line -->
        <line
            :x1="x + 2"
            :x2="x + cardWidth - 2"
            :y1="y + headerHeight + playerHeight"
            :y2="y + headerHeight + playerHeight"
            stroke="#e5e7eb"
            stroke-width="1"
        />

        <!-- Player 2 - compact height -->
        <g>
            <rect
                :class="match.winner_id === match.player2?.id ? 'player-winner' : 'player-bg'"
                :height="playerHeight"
                :width="cardWidth"
                :x="x"
                :y="y + headerHeight + playerHeight"
                rx="3"
            />
            <text :x="x + 4" :y="y + headerHeight + playerHeight + 12" class="player-name compact">
                {{ getPlayerDisplay(match.player2, match.isWalkover, !!match.player1) }}
            </text>
            <text :x="x + cardWidth - 20" :y="y + headerHeight + playerHeight + 12" class="player-score compact">
                {{ match.player2_score ?? '-' }}
            </text>
        </g>

        <!-- Status indicator - Only for in_progress matches -->
        <circle
            v-if="match.status === 'in_progress'"
            :cx="x + cardWidth - 8"
            :cy="y + 8"
            class="status-in-progress compact"
            r="3"
        />

        <!-- Loser drop indicator (for double elimination) -->
        <g v-if="showLoserDrop && match.loser_next_match_id && loserDropTargetMatch">
            <rect
                :x="x"
                :y="y + cardHeight + 3"
                :width="cardWidth"
                height="16"
                fill="#f3f4f6"
                stroke="#e5e7eb"
                stroke-width="1"
                rx="3"
                class="cursor-pointer hover:fill-gray-300 transition-colors"
                @click.stop="$emit('loser-drop-click', match.loser_next_match_id)"
            />
            <text
                :x="x + cardWidth / 2"
                :y="y + cardHeight + 13"
                text-anchor="middle"
                class="loser-drop-text"
                font-size="9"
            >
                {{ t('Drops to') }} {{ loserDropTargetMatch.displayNumber }}
            </text>
        </g>
    </g>
</template>

<script lang="ts" setup>
import {computed} from 'vue';
import {useLocale} from '@/composables/useLocale';
import type {TournamentMatch} from '@/types/api';

interface TransformedMatch {
    id: number;
    match_code: string;
    displayNumber: string;
    round: number;
    slot: number;
    status: string;
    isWalkover: boolean;
    player1?: any;
    player2?: any;
    player1_score?: number | null;
    player2_score?: number | null;
    winner_id?: number | null;
    bracketSide?: string | null;
    loser_next_match_id?: number | null;
}

interface Props {
    match: TransformedMatch;
    x: number;
    y: number;
    canEdit: boolean;
    currentUserId?: number;
    showLoserDrop?: boolean;
    matchesById?: Map<number, TournamentMatch>;
    allPositionedMatches?: Array<TransformedMatch & { x: number; y: number }>;
    cardWidth?: number;
    cardHeight?: number;
    highlightMatchId?: number | null;
}

const props = withDefaults(defineProps<Props>(), {
    showLoserDrop: false,
    cardWidth: 180,
    cardHeight: 50,
    highlightMatchId: null
});

defineEmits<{
    'click': [matchId: number];
    'loser-drop-click': [targetMatchId: number];
}>();

const {t} = useLocale();

// Compact dimensions
const headerHeight = 14;
const playerHeight = 18;

// Check if this match should be highlighted
const isHighlighted = computed(() => {
    // Highlight if this match is specifically highlighted
    if (props.highlightMatchId === props.match.id) return true;

    // Also highlight if it's the user's active match
    if (!props.currentUserId) return false;
    const isUserMatch = props.match.player1?.id === props.currentUserId ||
        props.match.player2?.id === props.currentUserId;
    return isUserMatch && props.match.status !== 'completed';
});

// Get the target match for loser drop display
const loserDropTargetMatch = computed(() => {
    if (!props.match.loser_next_match_id || !props.allPositionedMatches) return null;
    return props.allPositionedMatches.find(m => m.id === props.match.loser_next_match_id);
});

// Helper functions
const getMatchClass = (match: TransformedMatch) => {
    switch (match.status) {
        case 'pending':
            return 'match-pending';
        case 'ready':
            return 'match-ready';
        case 'in_progress':
            return 'match-active';
        case 'verification_pending':
            return 'match-verification';
        case 'completed':
            return 'match-completed';
        default:
            return 'match-pending';
    }
};

const getPlayerDisplay = (player: any, isWalkover: boolean, hasOpponent: boolean) => {
    if (!player) {
        if (isWalkover && !hasOpponent) {
            return t('BYE');
        }
        return t('TBD');
    }

    // Truncate long names for compact view
    return player.name || player.username;
};
</script>

<style scoped>
/* Compact styles for match cards */
.match-number.compact {
    font-size: 9px;
}

.player-name.compact {
    font-size: 11px;
}

.player-score.compact {
    font-size: 11px;
}

.walkover-text.compact {
    font-size: 8px;
}

.status-in-progress.compact {
    r: 3;
}

.loser-drop-text {
    fill: #6b7280;
    pointer-events: none;
}

/* Hover effects */
.hover\:fill-gray-300:hover {
    fill: #d1d5db;
}

.transition-colors {
    transition-property: fill;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
    transition-duration: 150ms;
}
</style>
