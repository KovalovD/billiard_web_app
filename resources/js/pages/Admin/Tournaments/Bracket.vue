<!-- resources/js/pages/Admin/Tournaments/Bracket.vue -->
<script lang="ts" setup>
import {Button, Card, CardContent, CardDescription, CardHeader, CardTitle, Spinner} from '@/Components/ui';
import {useLocale} from '@/composables/useLocale';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import {apiClient} from '@/lib/apiClient';
import type {Tournament, TournamentBracket, TournamentMatch} from '@/types/api';
import {Head, router} from '@inertiajs/vue3';
import {
    ArrowLeftIcon,
    ExpandIcon,
    MinusIcon,
    PlayIcon,
    PlusIcon,
    RefreshCwIcon,
    RotateCcwIcon,
    ShrinkIcon,
    TrophyIcon
} from 'lucide-vue-next';
import {computed, onMounted, onUnmounted, ref} from 'vue';

defineOptions({layout: AuthenticatedLayout});

const props = defineProps<{
    tournamentId: number | string;
}>();

const {t} = useLocale();

// Data
const tournament = ref<Tournament | null>(null);
const brackets = ref<TournamentBracket[]>([]);
const matches = ref<TournamentMatch[]>([]);

// Loading states
const isLoading = ref(true);
const isGenerating = ref(false);

// Error handling
const error = ref<string | null>(null);
const successMessage = ref<string | null>(null);

// Zoom and fullscreen states
const zoomLevel = ref(1);
const isFullscreen = ref(false);
const bracketContainerRef = ref<HTMLDivElement | null>(null);

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
}

const bracketMatches = computed<BracketMatch[]>(() => {
    const roundMap: Record<string, number> = {
        'round_128': 0,
        'round_64': 1,
        'round_32': 2,
        'round_16': 3,
        'quarterfinals': 4,
        'semifinals': 5,
        'finals': 6
    };

    return matches.value
        .filter(m => m.round && roundMap[m.round] !== undefined)
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
            status: m.status
        }))
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

const hasGeneratedBracket = computed(() => matches.value.length > 0);

// Zoom functions
const setZoom = (newZoom: number) => {
    zoomLevel.value = Math.max(0.3, Math.min(2, newZoom));
};

const zoomIn = () => {
    setZoom(zoomLevel.value + 0.1);
};

const zoomOut = () => {
    setZoom(zoomLevel.value - 0.1);
};

const resetZoom = () => {
    setZoom(1);
};

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

// Methods
const loadData = async () => {
    isLoading.value = true;
    error.value = null;

    try {
        tournament.value = await apiClient<Tournament>(`/api/tournaments/${props.tournamentId}`);

        const matchesResponse = await apiClient<TournamentMatch[]>(`/api/admin/tournaments/${props.tournamentId}/matches`);
        matches.value = matchesResponse || [];

        try {
            const bracketsResponse = await apiClient<TournamentBracket[]>(`/api/tournaments/${props.tournamentId}/brackets`);
            brackets.value = bracketsResponse || [];
        } catch {
            brackets.value = [];
        }
    } catch (err: any) {
        error.value = err.message || t('Failed to load tournament data');
    } finally {
        isLoading.value = false;
    }
};

const generateBracket = async () => {
    if (!confirm(t('Are you sure you want to generate the bracket? This will create all matches based on current seeding.'))) {
        return;
    }

    isGenerating.value = true;
    error.value = null;

    try {
        await apiClient(`/api/admin/tournaments/${props.tournamentId}/bracket/generate`, {
            method: 'POST'
        });

        successMessage.value = t('Bracket generated successfully');
        await loadData();
    } catch (err: any) {
        error.value = err.message || t('Failed to generate bracket');
    } finally {
        isGenerating.value = false;
    }
};

const startTournament = async () => {
    if (!confirm(t('Are you sure you want to start the tournament?'))) {
        return;
    }

    try {
        await apiClient(`/api/admin/tournaments/${props.tournamentId}/status`, {
            method: 'POST',
            data: {status: 'active'}
        });

        successMessage.value = t('Tournament started successfully');
        router.visit(`/admin/tournaments/${props.tournamentId}/matches`);
    } catch (err: any) {
        error.value = err.message || t('Failed to start tournament');
    }
};

const goToMatch = (matchId: number) => {
    router.visit(`/admin/tournaments/${props.tournamentId}/matches/${matchId}`);
};

const getStatusColor = (status: string) => {
    switch (status) {
        case 'completed':
            return '#16a34a';
        case 'in_progress':
            return '#eab308';
        default:
            return '#6b7280';
    }
};

const getMatchClass = (match: PositionedMatch) => {
    if (match.status === 'completed') return 'match-completed';
    if (match.status === 'in_progress') return 'match-active';
    return 'match-pending';
};

onMounted(() => {
    loadData();
    document.addEventListener('keydown', handleKeyboard);
    document.addEventListener('fullscreenchange', handleFullscreenChange);
});

onUnmounted(() => {
    document.removeEventListener('keydown', handleKeyboard);
    document.removeEventListener('fullscreenchange', handleFullscreenChange);
});
</script>

<template>
    <Head :title="tournament ? `${t('Bracket')}: ${tournament.name}` : t('Tournament Bracket')"/>

    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">{{
                            t('Tournament Bracket')
                        }}</h1>
                    <p class="text-gray-600 dark:text-gray-400">
                        {{ tournament ? tournament.name : t('Loading...') }}
                    </p>
                </div>
                <div class="flex space-x-3">
                    <Button :disabled="isLoading" variant="outline" @click="loadData">
                        <RefreshCwIcon :class="{ 'animate-spin': isLoading }" class="mr-2 h-4 w-4"/>
                        {{ t('Refresh') }}
                    </Button>
                    <Button variant="outline" @click="router.visit(`/tournaments/${props.tournamentId}`)">
                        <ArrowLeftIcon class="mr-2 h-4 w-4"/>
                        {{ t('Back to Tournament') }}
                    </Button>
                </div>
            </div>

            <!-- Messages -->
            <div v-if="error" class="mb-6 rounded bg-red-100 p-4 text-red-600 dark:bg-red-900/30 dark:text-red-400">
                {{ error }}
            </div>
            <div v-if="successMessage"
                 class="mb-6 rounded bg-green-100 p-4 text-green-600 dark:bg-green-900/30 dark:text-green-400">
                {{ successMessage }}
            </div>

            <!-- Loading -->
            <div v-if="isLoading" class="flex justify-center py-12">
                <Spinner class="h-8 w-8 text-primary"/>
            </div>

            <!-- Generate Bracket -->
            <Card v-else-if="!hasGeneratedBracket" class="mb-6">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <TrophyIcon class="h-5 w-5"/>
                        {{ t('Generate Tournament Bracket') }}
                    </CardTitle>
                    <CardDescription>
                        {{ t('Create the bracket structure based on seeded players') }}
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="text-center py-8">
                        <p class="mb-4 text-gray-600 dark:text-gray-400">
                            {{ t('The bracket has not been generated yet. Click below to create it.') }}
                        </p>
                        <Button
                            :disabled="isGenerating"
                            size="lg"
                            @click="generateBracket"
                        >
                            <Spinner v-if="isGenerating" class="mr-2 h-4 w-4"/>
                            {{ t('Generate Bracket') }}
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <!-- Bracket Display -->
            <template v-else>
                <!-- Tournament Info -->
                <Card class="mb-6">
                    <CardContent class="pt-6">
                        <div class="grid grid-cols-4 gap-4 text-center">
                            <div>
                                <div class="text-2xl font-bold text-blue-600">
                                    {{ tournament?.confirmed_players_count || 0 }}
                                </div>
                                <div class="text-sm text-gray-600">{{ t('Players') }}</div>
                            </div>
                            <div>
                                <div class="text-2xl font-bold text-green-600">{{ rounds.length }}</div>
                                <div class="text-sm text-gray-600">{{ t('Rounds') }}</div>
                            </div>
                            <div>
                                <div class="text-2xl font-bold text-yellow-600">{{ matches.length }}</div>
                                <div class="text-sm text-gray-600">{{ t('Matches') }}</div>
                            </div>
                            <div>
                                <div class="text-2xl font-bold text-purple-600">{{ tournament?.races_to || 7 }}</div>
                                <div class="text-sm text-gray-600">{{ t('Races To') }}</div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- SVG Bracket Structure -->
                <div ref="bracketContainerRef" class="bracket-fullscreen-container">
                    <Card class="overflow-hidden bracket-card">
                        <CardHeader>
                            <div class="flex items-center justify-between">
                                <div>
                                    <CardTitle>{{ t('Tournament Bracket') }}</CardTitle>
                                    <CardDescription>{{ t('Click on a match to view details') }}</CardDescription>
                                </div>

                                <!-- Zoom and Fullscreen Controls -->
                                <div class="flex items-center gap-2">
                                    <!-- Zoom Controls -->
                                    <div
                                        class="flex items-center gap-1 rounded-lg border border-gray-200 dark:border-gray-700 p-1">
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
                        </CardHeader>
                        <CardContent class="p-0">
                            <div
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
                                                   class="match-group cursor-pointer"
                                                   @click="goToMatch(m.id)">
                                                    <!-- Match background -->
                                                    <rect
                                                        :class="getMatchClass(m)" :height="nodeHeight"
                                                        :width="nodeWidth" :x="m.x"
                                                        :y="m.y"
                                                        rx="8"
                                                    />

                                                    <!-- Match number -->
                                                    <text :x="m.x + 8" :y="m.y + 14" class="match-number">
                                                        {{ t('Match') }} #{{ m.id }}
                                                    </text>

                                                    <!-- Player 1 -->
                                                    <g>
                                                        <rect
                                                            :class="m.winner_id === m.player1?.id ? 'player-winner' : 'player-bg'" :height="30"
                                                            :width="nodeWidth" :x="m.x"
                                                            :y="m.y + 20"
                                                            rx="4"
                                                        />
                                                        <text :x="m.x + 8" :y="m.y + 38" class="player-name">
                                                            {{ m.player1?.name ?? t('TBD') }}
                                                        </text>
                                                        <text :x="m.x + nodeWidth - 25" :y="m.y + 38"
                                                              class="player-score">
                                                            {{ m.player1_score ?? '-' }}
                                                        </text>
                                                    </g>

                                                    <!-- Player 2 -->
                                                    <g>
                                                        <rect
                                                            :class="m.winner_id === m.player2?.id ? 'player-winner' : 'player-bg'" :height="30"
                                                            :width="nodeWidth" :x="m.x"
                                                            :y="m.y + 50"
                                                            rx="4"
                                                        />
                                                        <text :x="m.x + 8" :y="m.y + 68" class="player-name">
                                                            {{ m.player2?.name ?? t('TBD') }}
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

                            <!-- Keyboard shortcuts hint -->
                            <div
                                class="border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2 text-xs text-gray-500 dark:text-gray-400">
                                {{ t('Keyboard shortcuts') }}:
                                <kbd class="px-1 py-0.5 bg-gray-200 dark:bg-gray-700 rounded">Ctrl</kbd> +
                                <kbd class="px-1 py-0.5 bg-gray-200 dark:bg-gray-700 rounded">+/-</kbd> {{ t('zoom') }},
                                <kbd class="px-1 py-0.5 bg-gray-200 dark:bg-gray-700 rounded">Ctrl</kbd> +
                                <kbd class="px-1 py-0.5 bg-gray-200 dark:bg-gray-700 rounded">0</kbd> {{ t('reset') }},
                                <kbd class="px-1 py-0.5 bg-gray-200 dark:bg-gray-700 rounded">F11</kbd>
                                {{ t('fullscreen') }}
                                <span class="ml-2">• {{ t('Pinch to zoom on touch devices') }}</span>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Start Tournament Button -->
                <Card v-if="tournament?.status === 'upcoming'" class="mt-6">
                    <CardContent class="pt-6">
                        <div class="flex items-center justify-between">
                            <p class="text-gray-600 dark:text-gray-400">
                                {{ t('Bracket is ready. Start the tournament to begin matches.') }}
                            </p>
                            <Button size="lg" @click="startTournament">
                                <PlayIcon class="mr-2 h-4 w-4"/>
                                {{ t('Start Tournament') }}
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </template>
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

.match-active {
    fill: #fef3c7;
    stroke: #f59e0b;
    stroke-width: 2;
}

.match-completed {
    fill: #e6ffed;
    stroke: #10b981;
    stroke-width: 1;
}

.match-group:hover .match-pending,
.match-group:hover .match-active,
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

.dark .match-active {
    fill: #451a03;
    stroke: #f59e0b;
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
