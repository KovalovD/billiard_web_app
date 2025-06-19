<script lang="ts" setup>
import {
    Button,
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
    Input,
    Label,
    Modal,
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
    Spinner,
    Textarea
} from '@/Components/ui';
import {useLocale} from '@/composables/useLocale';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import {apiClient} from '@/lib/apiClient';
import type {ClubTable, Tournament, TournamentBracket, TournamentMatch} from '@/types/api';
import {Head, router} from '@inertiajs/vue3';
import {
    AlertCircleIcon,
    ArrowLeftIcon,
    CalendarIcon,
    CheckCircleIcon,
    ExpandIcon,
    MinusIcon,
    PlayIcon,
    PlusIcon,
    RefreshCwIcon,
    RotateCcwIcon,
    SaveIcon,
    ShrinkIcon,
    TrophyIcon,
    UserXIcon
} from 'lucide-vue-next';
import {computed, nextTick, onMounted, onUnmounted, ref, watch} from 'vue';

defineOptions({layout: AuthenticatedLayout});

const props = defineProps<{
    tournamentId: number | string;
}>();

const {t} = useLocale();

// Data
const tournament = ref<Tournament | null>(null);
const brackets = ref<TournamentBracket[]>([]);
const matches = ref<TournamentMatch[]>([]);
const availableTables = ref<ClubTable[]>([]);

// Loading states
const isLoading = ref(true);
const isGenerating = ref(false);
const isUpdatingMatch = ref(false);
const isLoadingTables = ref(false);

// Error handling
const error = ref<string | null>(null);
const successMessage = ref<string | null>(null);
const matchError = ref<string | null>(null);

// Zoom and fullscreen states
const zoomLevel = ref(1);
const isFullscreen = ref(false);
const bracketContainerRef = ref<HTMLDivElement | null>(null);
const bracketScrollContainerRef = ref<HTMLDivElement | null>(null);

// Touch gesture states
const touchStartDistance = ref(0);
const touchStartZoom = ref(1);
const isTouching = ref(false);

// Match modal state
const showMatchModal = ref(false);
const selectedMatch = ref<TournamentMatch | null>(null);
const matchForm = ref({
    player1_score: 0,
    player2_score: 0,
    club_table_id: null as number | null,
    stream_url: '',
    status: 'pending' as string,
    scheduled_at: '',
    admin_notes: ''
});

// Bracket visualization settings
const nodeWidth = 200;
const nodeHeight = 80;
const hGap = 120;
const vGap = 40;

// Check if tournament can be edited
const canEditTournament = computed(() => {
    return tournament.value?.status === 'active' && tournament.value?.stage === 'bracket';
});

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
        'finals': 6
    };

    return matches.value
        .filter(m => m.round && roundMap[m.round] !== undefined)
        .map(m => {
            const hasPlayer1 = !!m.player1_id;
            const hasPlayer2 = !!m.player2_id;
            // Only mark as walkover if match is completed and only one player was present
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

const hasGeneratedBracket = computed(() => matches.value.length > 0);

// Match modal computed
const canStartMatch = computed(() => {
    if (!selectedMatch.value || !canEditTournament.value) return false;
    const match = bracketMatches.value.find(m => m.id === selectedMatch.value!.id);
    return match?.canBeStarted && matchForm.value.club_table_id !== null;
});

const canFinishMatch = computed(() => {
    if (!selectedMatch.value || !canEditTournament.value) return false;
    if (selectedMatch.value.status !== 'in_progress' && selectedMatch.value.status !== 'verification') return false;
    const racesTo = tournament.value?.races_to || 7;
    return matchForm.value.player1_score >= racesTo || matchForm.value.player2_score >= racesTo;
});

const matchWinner = computed(() => {
    if (!canFinishMatch.value) return null;
    const racesTo = tournament.value?.races_to || 7;
    if (matchForm.value.player1_score >= racesTo) return selectedMatch.value?.player1_id;
    if (matchForm.value.player2_score >= racesTo) return selectedMatch.value?.player2_id;
    return null;
});

const isWalkoverMatch = computed(() => {
    if (!selectedMatch.value) return false;
    const match = bracketMatches.value.find(m => m.id === selectedMatch.value!.id);
    return match?.isWalkover || false;
});

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

// Scroll to the bottom of the bracket
const scrollToBottom = () => {
    nextTick(() => {
        if (bracketScrollContainerRef.value) {
            const container = bracketScrollContainerRef.value;
            container.scrollTop = container.scrollHeight - container.clientHeight;
        }
    });
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

        // Scroll to bottom after data is loaded and rendered
        await nextTick();
        scrollToBottom();
    } catch (err: any) {
        error.value = err.message || t('Failed to load tournament data');
    } finally {
        isLoading.value = false;
    }
};

// Update specific matches in the bracket
const updateMatchesInBracket = (updatedMatches: TournamentMatch[]) => {
    updatedMatches.forEach(updatedMatch => {
        const index = matches.value.findIndex(m => m.id === updatedMatch.id);
        if (index !== -1) {
            matches.value[index] = updatedMatch;
        } else {
            // If it's a new match (shouldn't happen in this context), add it
            matches.value.push(updatedMatch);
        }
    });
    // Trigger reactivity
    matches.value = [...matches.value];
};

// Load specific matches after an update
const loadSpecificMatches = async (matchIds: number[]) => {
    try {
        // Load each match individually
        const updatedMatches = await Promise.all(
            matchIds.map(id =>
                apiClient<TournamentMatch>(`/api/admin/tournaments/${props.tournamentId}/matches/${id}`)
            )
        );

        // Update the matches in our local state
        updateMatchesInBracket(updatedMatches);
    } catch (err: any) {
        console.error('Failed to load specific matches:', err);
        // Fallback to full reload if partial update fails
        await loadData();
    }
};

const loadAvailableTables = async () => {
    isLoadingTables.value = true;
    try {
        const tables = await apiClient<ClubTable[]>(`/api/tournaments/${props.tournamentId}/tables`);
        availableTables.value = tables.filter(table => table.is_active);
    } catch (err) {
        console.error('Failed to load tables:', err);
        availableTables.value = [];
    } finally {
        isLoadingTables.value = false;
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
        await loadData();
    } catch (err: any) {
        error.value = err.message || t('Failed to start tournament');
    }
};

const openMatchModal = async (matchId: number) => {
    if (!canEditTournament.value) {
        error.value = t('Tournament must be active and in bracket stage to edit matches');
        return;
    }

    const match = matches.value.find(m => m.id === matchId);
    if (!match) return;

    selectedMatch.value = match;
    matchForm.value = {
        player1_score: match.player1_score || 0,
        player2_score: match.player2_score || 0,
        club_table_id: match.club_table_id || null,
        stream_url: match.stream_url || '',
        status: match.status,
        scheduled_at: match.scheduled_at ? new Date(match.scheduled_at).toISOString().slice(0, 16) : '',
        admin_notes: match.admin_notes || ''
    };

    showMatchModal.value = true;
    await loadAvailableTables();
};

const closeMatchModal = () => {
    showMatchModal.value = false;
    selectedMatch.value = null;
    matchError.value = null;
};

const startMatch = async () => {
    if (!canStartMatch.value || !selectedMatch.value) return;

    isUpdatingMatch.value = true;
    matchError.value = null;

    try {
        const response = await apiClient<TournamentMatch>(`/api/admin/tournaments/${props.tournamentId}/matches/${selectedMatch.value.id}/start`, {
            method: 'POST',
            data: {
                club_table_id: matchForm.value.club_table_id,
                stream_url: matchForm.value.stream_url
            }
        });

        // Update only this match
        updateMatchesInBracket([response]);
        closeMatchModal();
        successMessage.value = t('Match started successfully');
    } catch (err: any) {
        matchError.value = err.message || t('Failed to start match');
    } finally {
        isUpdatingMatch.value = false;
    }
};

const updateMatch = async () => {
    if (!selectedMatch.value || !canEditTournament.value) return;

    isUpdatingMatch.value = true;
    matchError.value = null;

    try {
        const response = await apiClient<TournamentMatch>(`/api/admin/tournaments/${props.tournamentId}/matches/${selectedMatch.value.id}`, {
            method: 'PUT',
            data: matchForm.value
        });

        // Update only this match
        updateMatchesInBracket([response]);
        closeMatchModal();
        successMessage.value = t('Match updated successfully');
    } catch (err: any) {
        matchError.value = err.message || t('Failed to update match');
    } finally {
        isUpdatingMatch.value = false;
    }
};

const finishMatch = async () => {
    if (!canFinishMatch.value || !selectedMatch.value) return;

    isUpdatingMatch.value = true;
    matchError.value = null;

    try {
        const response = await apiClient<{
            match: TournamentMatch;
            affected_matches: number[];
        }>(`/api/admin/tournaments/${props.tournamentId}/matches/${selectedMatch.value.id}/finish`, {
            method: 'POST',
            data: {
                player1_score: matchForm.value.player1_score,
                player2_score: matchForm.value.player2_score
            }
        });

        // Update the finished match
        updateMatchesInBracket([response.match]);

        // If there are affected matches (next matches), load them
        if (response.affected_matches && response.affected_matches.length > 0) {
            await loadSpecificMatches(response.affected_matches);
        }

        closeMatchModal();
        successMessage.value = t('Match finished successfully');
    } catch (err: any) {
        matchError.value = err.message || t('Failed to finish match');
    } finally {
        isUpdatingMatch.value = false;
    }
};

const processWalkover = async () => {
    if (!selectedMatch.value || !isWalkoverMatch.value) return;

    isUpdatingMatch.value = true;
    matchError.value = null;

    try {
        // Determine winner (the player who is present)
        const racesTo = tournament.value?.races_to || 7;

        const response = await apiClient<{
            match: TournamentMatch;
            affected_matches: number[];
        }>(`/api/admin/tournaments/${props.tournamentId}/matches/${selectedMatch.value.id}/finish`, {
            method: 'POST',
            data: {
                player1_score: selectedMatch.value.player1_id ? racesTo : 0,
                player2_score: selectedMatch.value.player2_id ? racesTo : 0,
                admin_notes: 'Walkover'
            }
        });

        // Update the finished match
        updateMatchesInBracket([response.match]);

        // If there are affected matches (next matches), load them
        if (response.affected_matches && response.affected_matches.length > 0) {
            await loadSpecificMatches(response.affected_matches);
        }

        closeMatchModal();
        successMessage.value = t('Walkover processed successfully');
    } catch (err: any) {
        matchError.value = err.message || t('Failed to process walkover');
    } finally {
        isUpdatingMatch.value = false;
    }
};

const formatDateTime = (dateString: string | undefined): string => {
    if (!dateString) return '';
    return new Date(dateString).toLocaleString('uk-UK', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
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

// Watch for club table selection to update stream URL
watch(() => matchForm.value.club_table_id, (newTableId) => {
    if (newTableId) {
        const table = availableTables.value.find(t => t.id === newTableId);
        if (table?.stream_url) {
            matchForm.value.stream_url = table.stream_url;
        }
    }
});

// Watch for bracket data changes to scroll to bottom
watch(() => positionedMatches.value.length, () => {
    if (positionedMatches.value.length > 0) {
        scrollToBottom();
    }
});

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

            <!-- Tournament Status Warning -->
            <div v-if="tournament && !canEditTournament" class="mb-6 rounded bg-yellow-100 p-4 dark:bg-yellow-900/30">
                <div class="flex items-center gap-2">
                    <AlertCircleIcon class="h-5 w-5 text-yellow-600"/>
                    <p class="font-medium text-yellow-800 dark:text-yellow-300">
                        {{ t('Tournament must be active and in bracket stage to edit matches') }}
                    </p>
                </div>
                <p class="mt-2 text-sm text-yellow-700 dark:text-yellow-400">
                    {{ t('Current status') }}: {{ tournament.status_display }} • {{ t('Current stage') }}:
                    {{ tournament.stage_display }}
                </p>
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
                                    <CardDescription>
                                        {{
                                            canEditTournament ? t('Click on a match to view details') : t('View only mode - tournament not active')
                                        }}
                                    </CardDescription>
                                </div>

                                <!-- Zoom and Fullscreen Controls -->
                                <div class="flex items-center gap-2">
                                    <!-- Zoom Controls -->
                                    <div
                                        class="flex items-center gap-1 rounded-lg border border-gray-200 dark:border-gray-700 p-1">
                                        <Button
                                            size="sm"
                                            variant="ghost"
                                            @click="zoomOut"
                                            :title="t('Zoom Out')"
                                        >
                                            <MinusIcon class="h-4 w-4"/>
                                        </Button>
                                        <span class="px-2 text-sm font-medium text-gray-600 dark:text-gray-400">
                                            {{ Math.round(zoomLevel * 100) }}%
                                        </span>
                                        <Button
                                            size="sm"
                                            variant="ghost"
                                            @click="zoomIn"
                                            :title="t('Zoom In')"
                                        >
                                            <PlusIcon class="h-4 w-4"/>
                                        </Button>
                                        <Button
                                            size="sm"
                                            variant="ghost"
                                            @click="resetZoom"
                                            :title="t('Reset Zoom')"
                                        >
                                            <RotateCcwIcon class="h-4 w-4"/>
                                        </Button>
                                    </div>

                                    <!-- Fullscreen Button -->
                                    <Button
                                        size="sm"
                                        variant="outline"
                                        @click="toggleFullscreen"
                                        :title="isFullscreen ? t('Exit Fullscreen') : t('Enter Fullscreen')"
                                    >
                                        <ExpandIcon v-if="!isFullscreen" class="h-4 w-4"/>
                                        <ShrinkIcon v-else class="h-4 w-4"/>
                                    </Button>
                                </div>
                            </div>
                        </CardHeader>
                        <CardContent class="p-0">
                            <!-- Keyboard shortcuts hint - moved to top -->
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
                                @touchstart="handleTouchStart"
                                @touchmove="handleTouchMove"
                                @wheel="handleWheel"
                            >
                                <div :style="{ transform: `scale(${zoomLevel})` }" class="bracket-zoom-wrapper">
                                    <div class="p-6">
                                        <svg
                                            :width="svgWidth"
                                            :height="svgHeight"
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
                                                   :class="[canEditTournament ? 'cursor-pointer' : 'cursor-not-allowed']"
                                                   class="match-group"
                                                   @click="openMatchModal(m.id)">
                                                    <!-- Match background -->
                                                    <rect
                                                        :class="getMatchClass(m)" :height="nodeHeight"
                                                        :width="nodeWidth" :x="m.x"
                                                        rx="8"
                                                        :y="m.y"
                                                    />

                                                    <!-- Walkover indicator - more visible -->
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
                                                            :class="m.winner_id === m.player1?.id ? 'player-winner' : 'player-bg'" :height="30"
                                                            :width="nodeWidth" :x="m.x"
                                                            rx="4"
                                                            :y="m.y + 20"
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
                                                            :class="m.winner_id === m.player2?.id ? 'player-winner' : 'player-bg'" :height="30"
                                                            :width="nodeWidth" :x="m.x"
                                                            rx="4"
                                                            :y="m.y + 50"
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
                                                        r="4"
                                                        :fill="getStatusColor(m.status)"
                                                    />
                                                </g>
                                            </g>
                                        </svg>
                                    </div>
                                </div>
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

    <!-- Match Management Modal -->
    <Modal :show="showMatchModal" :title="selectedMatch ? `${t('Match')} #${selectedMatch.id}` : ''"
           max-width="2xl" @close="closeMatchModal">
        <div v-if="selectedMatch" class="space-y-6">
            <!-- Match Header -->
            <div class="border-b pb-4">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-lg font-semibold">
                        {{ selectedMatch.round_display }}
                        <span v-if="selectedMatch.match_code" class="text-gray-500">({{
                                selectedMatch.match_code
                            }})</span>
                    </h3>
                    <div class="flex items-center gap-2">
                        <span v-if="isWalkoverMatch"
                              class="px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300">
                            <UserXIcon class="inline h-4 w-4 mr-1"/>
                            {{ t('Walkover') }}
                        </span>
                        <span :class="[
                            'px-3 py-1 rounded-full text-sm font-medium',
                            selectedMatch.status === 'completed' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' :
                            selectedMatch.status === 'in_progress' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300' :
                            selectedMatch.status === 'verification' ? 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300' :
                            selectedMatch.status === 'ready' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300' :
                            'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'
                        ]">
                            {{ selectedMatch.status_display }}
                        </span>
                    </div>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    {{ t('Race to') }} {{ tournament?.races_to || 7 }}
                </p>
            </div>

            <!-- Walkover Notice -->
            <div v-if="isWalkoverMatch && selectedMatch.status === 'pending'"
                 class="rounded bg-yellow-100 p-4 dark:bg-yellow-900/30">
                <div class="flex items-center gap-2">
                    <UserXIcon class="h-5 w-5 text-yellow-600"/>
                    <p class="font-medium text-yellow-800 dark:text-yellow-300">{{ t('This is a walkover match') }}</p>
                </div>
                <p class="mt-2 text-sm text-yellow-700 dark:text-yellow-400">
                    {{ t('One player is missing. Click "Process Walkover" to advance the present player.') }}
                </p>
            </div>

            <!-- Players and Scores -->
            <div class="grid grid-cols-2 gap-4">
                <div class="space-y-2">
                    <Label>
                        {{ selectedMatch.player1?.firstname }} {{ selectedMatch.player1?.lastname }}
                        <span v-if="!selectedMatch.player1" class="text-gray-500">{{ t('TBD') }}</span>
                    </Label>
                    <Input
                        v-model.number="matchForm.player1_score"
                        :disabled="selectedMatch.status === 'completed' || isWalkoverMatch"
                        :max="tournament?.races_to || 7"
                        min="0"
                        type="number"
                    />
                </div>
                <div class="space-y-2">
                    <Label>
                        {{ selectedMatch.player2?.firstname }} {{ selectedMatch.player2?.lastname }}
                        <span v-if="!selectedMatch.player2" class="text-gray-500">{{ t('TBD') }}</span>
                    </Label>
                    <Input
                        v-model.number="matchForm.player2_score"
                        :disabled="selectedMatch.status === 'completed' || isWalkoverMatch"
                        :max="tournament?.races_to || 7"
                        min="0"
                        type="number"
                    />
                </div>
            </div>

            <!-- Table Assignment -->
            <div v-if="!isWalkoverMatch" class="space-y-2">
                <Label for="table">{{ t('Table Assignment') }} *</Label>
                <Select v-model="matchForm.club_table_id" :disabled="selectedMatch.status !== 'ready'">
                    <SelectTrigger>
                        <SelectValue :placeholder="t('Select a table')"/>
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem v-if="isLoadingTables" :value="0">
                            {{ t('Loading tables...') }}
                        </SelectItem>
                        <SelectItem
                            v-for="table in availableTables"
                            v-else
                            :key="table.id"
                            :value="table.id"
                        >
                            {{ table.name }}
                        </SelectItem>
                    </SelectContent>
                </Select>
                <p v-if="selectedMatch.status === 'ready'" class="text-sm text-gray-500">
                    {{ t('Table assignment is required to start the match') }}
                </p>
            </div>

            <!-- Stream URL -->
            <div v-if="!isWalkoverMatch" class="space-y-2">
                <Label for="stream">{{ t('Stream URL') }}</Label>
                <Input
                    v-model="matchForm.stream_url"
                    :placeholder="t('https://youtube.com/watch?v=...')"
                    type="url"
                />
            </div>

            <!-- Scheduled Time -->
            <div class="space-y-2">
                <Label for="scheduled">{{ t('Scheduled At') }}</Label>
                <Input
                    v-model="matchForm.scheduled_at"
                    type="datetime-local"
                />
            </div>

            <!-- Admin Notes -->
            <div class="space-y-2">
                <Label for="notes">{{ t('Admin Notes') }}</Label>
                <Textarea
                    v-model="matchForm.admin_notes"
                    :placeholder="t('Internal notes about this match...')"
                    rows="3"
                />
            </div>

            <!-- Match Timeline -->
            <div v-if="selectedMatch.started_at || selectedMatch.completed_at" class="border-t pt-4">
                <h4 class="font-medium mb-2">{{ t('Timeline') }}</h4>
                <div class="space-y-1 text-sm text-gray-600 dark:text-gray-400">
                    <div v-if="selectedMatch.scheduled_at" class="flex items-center gap-2">
                        <CalendarIcon class="h-4 w-4"/>
                        {{ t('Scheduled') }}: {{ formatDateTime(selectedMatch.scheduled_at) }}
                    </div>
                    <div v-if="selectedMatch.started_at" class="flex items-center gap-2">
                        <PlayIcon class="h-4 w-4"/>
                        {{ t('Started') }}: {{ formatDateTime(selectedMatch.started_at) }}
                    </div>
                    <div v-if="selectedMatch.completed_at" class="flex items-center gap-2">
                        <CheckCircleIcon class="h-4 w-4"/>
                        {{ t('Completed') }}: {{ formatDateTime(selectedMatch.completed_at) }}
                    </div>
                </div>
            </div>

            <!-- Status Note for Verification -->
            <div v-if="selectedMatch.status === 'verification'" class="rounded bg-purple-100 p-3 dark:bg-purple-900/30">
                <div class="flex items-center gap-2">
                    <AlertCircleIcon class="h-5 w-5 text-purple-600"/>
                    <p class="font-medium text-purple-800 dark:text-purple-300">{{ t('Match needs verification') }}</p>
                </div>
                <p class="mt-2 text-sm text-purple-700 dark:text-purple-400">
                    {{ t('Players have submitted the result. Please verify and finish the match.') }}
                </p>
            </div>

            <!-- Error Display -->
            <div v-if="matchError" class="rounded bg-red-100 p-3 text-red-600 dark:bg-red-900/30 dark:text-red-400">
                {{ matchError }}
            </div>

            <!-- Winner Display -->
            <div v-if="canFinishMatch && !isWalkoverMatch" class="rounded bg-blue-100 p-3 dark:bg-blue-900/30">
                <p class="text-sm">
                    <span class="font-medium">{{ t('Winner') }}:</span>
                    <span v-if="matchWinner === selectedMatch.player1_id" class="ml-2">
                        {{ selectedMatch.player1?.firstname }} {{ selectedMatch.player1?.lastname }}
                    </span>
                    <span v-else-if="matchWinner === selectedMatch.player2_id" class="ml-2">
                        {{ selectedMatch.player2?.firstname }} {{ selectedMatch.player2?.lastname }}
                    </span>
                </p>
            </div>
        </div>

        <template #footer>
            <div class="flex justify-between">
                <Button variant="outline" @click="closeMatchModal">
                    {{ t('Cancel') }}
                </Button>

                <div class="flex gap-2">
                    <!-- Process Walkover Button -->
                    <Button
                        v-if="isWalkoverMatch && selectedMatch?.status === 'pending'"
                        :disabled="isUpdatingMatch"
                        @click="processWalkover"
                    >
                        <UserXIcon class="mr-2 h-4 w-4"/>
                        {{ t('Process Walkover') }}
                    </Button>

                    <!-- Start Match Button -->
                    <Button
                        v-if="selectedMatch?.status === 'ready' && !isWalkoverMatch"
                        :disabled="!canStartMatch || isUpdatingMatch"
                        @click="startMatch"
                    >
                        <PlayIcon class="mr-2 h-4 w-4"/>
                        {{ t('Start Match') }}
                    </Button>

                    <!-- Update Match Button -->
                    <Button
                        v-if="selectedMatch?.status === 'in_progress'"
                        :disabled="isUpdatingMatch"
                        variant="outline"
                        @click="updateMatch"
                    >
                        <SaveIcon class="mr-2 h-4 w-4"/>
                        {{ t('Save Changes') }}
                    </Button>

                    <!-- Finish Match Button -->
                    <Button
                        v-if="selectedMatch?.status === 'in_progress' || selectedMatch?.status === 'verification'"
                        :disabled="!canFinishMatch || isUpdatingMatch"
                        @click="finishMatch"
                    >
                        <CheckCircleIcon class="mr-2 h-4 w-4"/>
                        {{ t('Finish Match') }}
                    </Button>
                </div>
            </div>
        </template>
    </Modal>
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
