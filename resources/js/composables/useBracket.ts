// composables/useBracket.ts
import {nextTick, onMounted, onUnmounted, ref} from 'vue';
import type {TournamentMatch} from '@/types/api';
import {useLocale} from '@/composables/useLocale';

export interface BracketMatch {
    id: number;
    round: number;
    slot: number;
    player1: { id: number; name: string } | null;
    player2: { id: number; name: string } | null;
    player1_score: number;
    player2_score: number;
    winner_id?: number;
    status: string;
    match_code: string;
    displayNumber: string; // New field for user-friendly display
    isWalkover: boolean;
    bracketSide?: 'upper' | 'lower' | null;
    stage: string;
    next_match_id?: number;
    previous_match1_id?: number;
    previous_match2_id?: number;
    loser_next_match_id?: number;
    loser_next_match_position?: number;
}

export interface BracketConfig {
    nodeWidth?: number;
    nodeHeight?: number;
    hGap?: number;
    vGap?: number;
    initialZoom?: number;
}

export function useBracket(
    currentUserId?: number,
    config: BracketConfig = {}
) {
    const {t} = useLocale();

    // Configuration with defaults
    const nodeWidth = config.nodeWidth ?? 200;
    const nodeHeight = config.nodeHeight ?? 80;
    const hGap = config.hGap ?? 120;
    const vGap = config.vGap ?? 40;

    // Zoom and fullscreen states
    const zoomLevel = ref(config.initialZoom ?? 1);
    const isFullscreen = ref(false);
    const bracketContainerRef = ref<HTMLDivElement | null | undefined>(null);
    const bracketScrollContainerRef = ref<HTMLDivElement | null | undefined>(null);

    // Highlight state
    const highlightMatchId = ref<number | null>(null);

    // Touch gesture states
    const touchState = ref({
        isPinching: false,
        isPanning: false,
        touchStartDistance: 0,
        touchStartZoom: 1,
        lastTouchX: 0,
        lastTouchY: 0,
        startScrollLeft: 0,
        startScrollTop: 0
    });

    // Transform match data
    const transformMatch = (
        m: TournamentMatch,
        bracketSide: 'upper' | 'lower' | null,
        round: number
    ): BracketMatch => {
        const slot = m.bracket_position || 0;

        // Create display number based on round and slot
        // Special handling for grand finals
        let displayNumber: string;
        if (m.match_code === 'GF') {
            displayNumber = 'GF';
        } else if (m.match_code === 'GF_RESET') {
            displayNumber = 'GF2';
        } else {
            // Regular matches: Round-Match format (1-based)
            displayNumber = `${round + 1}-${slot + 1}`;
            // Add bracket side indicator for lower bracket
            if (bracketSide === 'lower') {
                displayNumber = `L${displayNumber}`;
            }
        }

        return {
            id: m.id,
            round,
            slot,
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
            displayNumber,
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
        };
    };

    // Zoom functions
    const setZoom = (newZoom: number) => {
        zoomLevel.value = Math.max(0.3, Math.min(2, newZoom));
    };

    const zoomIn = () => setZoom(zoomLevel.value + 0.1);
    const zoomOut = () => setZoom(zoomLevel.value - 0.1);
    const resetZoom = () => setZoom(config.initialZoom ?? 1);

    // Touch gesture handlers
    const getTouchDistance = (touches: TouchList): number => {
        if (touches.length < 2) return 0;
        const dx = touches[0].clientX - touches[1].clientX;
        const dy = touches[0].clientY - touches[1].clientY;
        return Math.sqrt(dx * dx + dy * dy);
    };

    const handleTouchStart = (e: TouchEvent) => {
        if (!bracketScrollContainerRef.value) return;

        if (e.touches.length === 2) {
            // Pinch zoom start
            touchState.value.isPinching = true;
            touchState.value.isPanning = false;
            touchState.value.touchStartDistance = getTouchDistance(e.touches);
            touchState.value.touchStartZoom = zoomLevel.value;
            e.preventDefault();
        } else if (e.touches.length === 1) {
            // Pan start
            touchState.value.isPanning = true;
            touchState.value.isPinching = false;
            touchState.value.lastTouchX = e.touches[0].clientX;
            touchState.value.lastTouchY = e.touches[0].clientY;
            touchState.value.startScrollLeft = bracketScrollContainerRef.value.scrollLeft;
            touchState.value.startScrollTop = bracketScrollContainerRef.value.scrollTop;
        }
    };

    const handleTouchMove = (e: TouchEvent) => {
        if (!bracketScrollContainerRef.value) return;

        if (e.touches.length === 2 && touchState.value.isPinching) {
            // Pinch zoom
            e.preventDefault();
            const currentDistance = getTouchDistance(e.touches);
            const scale = currentDistance / touchState.value.touchStartDistance;
            setZoom(touchState.value.touchStartZoom * scale);
        } else if (e.touches.length === 1 && touchState.value.isPanning && !touchState.value.isPinching) {
            // Single finger pan
            const touch = e.touches[0];
            const deltaX = touchState.value.lastTouchX - touch.clientX;
            const deltaY = touchState.value.lastTouchY - touch.clientY;

            bracketScrollContainerRef.value.scrollLeft = touchState.value.startScrollLeft + deltaX;
            bracketScrollContainerRef.value.scrollTop = touchState.value.startScrollTop + deltaY;
        }
    };

    const handleTouchEnd = () => {
        touchState.value.isPinching = false;
        touchState.value.isPanning = false;
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

    // Generic scroll to match function
    const scrollToMatch = (targetMatch: BracketMatch & { x: number; y: number }) => {
        if (!targetMatch || !bracketScrollContainerRef.value) return;

        // Set zoom to comfortable level
        //setZoom(1.2);

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

    // Highlight match function
    const highlightMatch = (match: BracketMatch & { x: number; y: number }) => {
        if (!match) return;

        // Scroll to match
        scrollToMatch(match);

        // Set highlight
        highlightMatchId.value = match.id;

        // Remove highlight after 3 seconds
        setTimeout(() => {
            highlightMatchId.value = null;
        }, 3000);
    };

    // Find and focus on user's match
    const findMyMatch = (positionedMatches: Array<BracketMatch & { x: number; y: number }>) => {
        if (!currentUserId) return null;

        // 1. Look for active or upcoming match
        const targetMatch = positionedMatches.find(match =>
            (match.player1?.id === currentUserId || match.player2?.id === currentUserId) &&
            match.status !== 'completed'
        );

        if (targetMatch) {
            highlightMatch(targetMatch);
            return targetMatch;
        }

        // 2. If no active matches, find last played match
        const userMatches = positionedMatches.filter(match =>
            (match.player1?.id === currentUserId || match.player2?.id === currentUserId) &&
            match.status === 'completed'
        );

        if (!userMatches.length) return null;

        // Determine the latest round the user participated in
        // Priority: Grand finals (GF_RESET > GF), then lower bracket (max round), then upper bracket (max round)
        let lastMatch = userMatches.find(m => m.match_code === 'GF_RESET') ||
            userMatches.find(m => m.match_code === 'GF') ||
            null;

        if (!lastMatch) {
            // Choose match from lower bracket with max round number, otherwise from upper
            const lowerMatches = userMatches.filter(m => m.bracketSide === 'lower');
            if (lowerMatches.length) {
                lastMatch = lowerMatches.reduce((max, m) => m.round > max.round ? m : max);
            } else {
                const upperMatches = userMatches.filter(m => m.bracketSide === 'upper');
                if (upperMatches.length) {
                    lastMatch = upperMatches.reduce((max, m) => m.round > max.round ? m : max);
                }
            }
        }

        if (lastMatch) {
            highlightMatch(lastMatch);
        }

        return lastMatch;
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

    // Match display helpers
    const getMatchClass = (match: any) => {
        if (match.status === 'completed') return 'match-completed';
        if (match.status === 'in_progress') return 'match-active';
        if (match.status === 'verification') return 'match-verification';
        if (match.status === 'ready') return 'match-ready';
        return 'match-pending';
    };

    const isCurrentUserMatch = (match: any) => {
        return currentUserId && (match.player1?.id === currentUserId || match.player2?.id === currentUserId);
    };

    const getPlayerDisplay = (player: {
        id: number;
        name: string
    } | null, isWalkover: boolean, hasOpponent: boolean) => {
        if (player) return player.name;
        if (isWalkover && !hasOpponent) return t('Walkover');
        return t('TBD');
    };

    // Event listeners
    onMounted(() => {
        document.addEventListener('keydown', handleKeyboard);
        document.addEventListener('fullscreenchange', handleFullscreenChange);
        scrollToCenter();
    });

    onUnmounted(() => {
        document.removeEventListener('keydown', handleKeyboard);
        document.removeEventListener('fullscreenchange', handleFullscreenChange);
    });

    return {
        // Config
        nodeWidth,
        nodeHeight,
        hGap,
        vGap,

        // State
        zoomLevel,
        isFullscreen,
        bracketContainerRef,
        bracketScrollContainerRef,
        highlightMatchId,

        // Methods
        transformMatch,
        setZoom,
        zoomIn,
        zoomOut,
        resetZoom,
        getTouchDistance,
        handleTouchStart,
        handleTouchMove,
        handleTouchEnd,
        handleWheel,
        toggleFullscreen,
        handleKeyboard,
        handleFullscreenChange,
        findMyMatch,
        scrollToMatch,
        scrollToCenter,
        getMatchClass,
        isCurrentUserMatch,
        getPlayerDisplay,
        highlightMatch,
    };
}
