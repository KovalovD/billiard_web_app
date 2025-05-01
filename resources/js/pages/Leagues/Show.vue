<script lang="ts" setup>
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import {Head, Link} from '@inertiajs/vue3';
import {useAuth} from '@/composables/useAuth';
import {useLeagues} from '@/composables/useLeagues';
import {computed, onMounted, ref, watch} from 'vue';
import type {ApiError, MatchGame, Player} from '@/types/api';
import {Button, Card, CardContent, CardDescription, CardHeader, CardTitle, Modal, Spinner} from '@/Components/ui';
import PlayerList from '@/Components/PlayerList.vue';
import ChallengeModal from '@/Components/ChallengeModal.vue';
import ResultModal from '@/Components/ResultModal.vue';
import {apiClient} from '@/lib/apiClient';
import {ArrowLeftIcon, LogOutIcon, PencilIcon, UserPlusIcon} from 'lucide-vue-next';

defineOptions({layout: AuthenticatedLayout});

const props = defineProps<{
    leagueId: number | string;
}>();

const {user, isAuthenticated, isAdmin} = useAuth();
const leagues = useLeagues();

// State for modals
const showChallengeModal = ref(false);
const showResultModal = ref(false);
const showGenericModal = ref(false);
const genericModalMessage = ref('');
const targetPlayerForChallenge = ref<Player | null>(null);
const matchForResults = ref<MatchGame | null>(null);
const isProcessingAction = ref(false);

// Check for matched route query params
const routeMatchId = ref<string | null>(null);

// Get API functionality from useLeagues
const {
    data: league,
    isLoading: isLoadingLeague,
    error: leagueError,
    execute: fetchLeague
} = leagues.fetchLeague(props.leagueId);

const {
    data: players,
    isLoading: isLoadingPlayers,
    error: playersError,
    execute: fetchPlayers
} = leagues.fetchLeaguePlayers(props.leagueId);

const {
    execute: joinLeagueAction,
    isActing: isJoining,
    error: joinError
} = leagues.joinLeague(props.leagueId);

const {
    execute: leaveLeagueAction,
    isActing: isLeaving,
    error: leaveError
} = leagues.leaveLeague(props.leagueId);

// Matches
const {
    data: matches,
    isLoading: isLoadingMatches,
    error: matchesError,
    execute: fetchMatches
} = leagues.fetchLeagueMatches(props.leagueId);

// Check if current user is in the league
const isCurrentUserInLeague = computed(() => {
    if (!user.value || !players.value) return false;
    return leagues.isPlayerInLeague(players.value, user.value.id);
});

// Page title
const pageTitle = computed(() =>
    league.value ? `League: ${league.value.name}` : 'League Details'
);

// Find an active match by ID from query params
const findMatchById = (id: string): MatchGame | null => {
    if (!matches.value) return null;
    return matches.value.find(m => m.id.toString() === id) || null;
};

// Utility function to display messages
const displayMessage = (message: string) => {
    genericModalMessage.value = message;
    showGenericModal.value = true;
};

// Check if user is the sender of the match
const isMatchSender = (match: MatchGame): boolean => {
    return match.firstPlayer?.user?.id === user.value?.id;
};

// Actions
const handleJoinLeague = async () => {
    if (!isAuthenticated.value) {
        displayMessage('You must be logged in to join this league.');
        return;
    }

    const success = await joinLeagueAction();
    if (success) {
        await fetchPlayers();
    } else if (joinError.value) {
        displayMessage(`Failed to join league: ${joinError.value.message || 'Unknown error'}`);
    }
};

const handleLeaveLeague = async () => {
    if (!isAuthenticated.value || !isCurrentUserInLeague.value) return;

    const success = await leaveLeagueAction();
    if (success) {
        await fetchPlayers();
    } else if (leaveError.value) {
        displayMessage(`Failed to leave league: ${leaveError.value.message || 'Unknown error'}`);
    }
};

const openChallengeModal = (player: Player) => {
    targetPlayerForChallenge.value = player;
    showChallengeModal.value = true;
};

const handleChallengeSuccess = (message: string) => {
    displayMessage(message);
    fetchMatches();
    fetchPlayers();
};

const openResultModal = (match: MatchGame) => {
    matchForResults.value = match;
    showResultModal.value = true;
};

// Decline match function
const declineMatch = async (match: MatchGame) => {
    if (isProcessingAction.value || !match.id) return;

    isProcessingAction.value = true;
    try {
        await apiClient(`/api/leagues/${props.leagueId}/players/match-games/${match.id}/decline`, {
            method: 'post'
        });
        displayMessage('Match declined successfully.');
        // Refresh data
        await fetchMatches();
        await fetchPlayers();
    } catch (error: any) {
        displayMessage(`Failed to decline match: ${error.message || 'Unknown error'}`);
    } finally {
        isProcessingAction.value = false;
    }
};

// Initialize data
onMounted(() => {
    fetchLeague();
    fetchPlayers();
    fetchMatches();

    // Check URL query params for matchId
    const url = new URL(window.location.href);
    routeMatchId.value = url.searchParams.get('matchId');
});

// Watch for matches to be loaded, then check for routeMatchId
watch([matches, routeMatchId], ([currentMatches, currentMatchId]) => {
    if (currentMatches && currentMatchId) {
        const matchToOpen = findMatchById(currentMatchId);
        if (matchToOpen) {
            matchForResults.value = matchToOpen;
            showResultModal.value = true;
            // Clear the query params without refreshing page
            const url = new URL(window.location.href);
            url.searchParams.delete('matchId');
            window.history.replaceState({}, document.title, url);
            routeMatchId.value = null;
        }
    }
}, {immediate: true});
</script>

<template>
    <Head :title="pageTitle"/>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Header with actions -->
            <div class="mb-6 flex justify-between items-center">
                <Link :href="route('leagues.index')">
                    <Button variant="outline">
                        <ArrowLeftIcon class="w-4 h-4 mr-2"/>
                        Back to Leagues
                    </Button>
                </Link>

                <div v-if="isAdmin && league" class="flex space-x-2">
                    <Link :href="route('leagues.edit', { league: league.id })">
                        <Button variant="secondary">
                            <PencilIcon class="w-4 h-4 mr-2"/>
                            Edit League
                        </Button>
                    </Link>
                </div>
            </div>

            <!-- League Loading State -->
            <div v-if="isLoadingLeague" class="text-center p-10">
                <Spinner class="w-8 h-8 mx-auto text-primary"/>
                <p class="mt-2 text-gray-500">Loading league information...</p>
            </div>

            <!-- League Error State -->
            <div v-else-if="leagueError" class="text-red-500 bg-red-100 p-4 rounded mb-6">
                Error loading league: {{ leagueError.message }}
            </div>

            <!-- League Content -->
            <template v-else-if="league">
                <!-- League Info Card -->
                <Card class="mb-8">
                    <CardHeader>
                        <CardTitle>{{ league.name }}</CardTitle>
                        <CardDescription>
                            Game: {{ league.game ?? 'N/A' }} |
                            Rating: {{ league.has_rating ? `Enabled (${league.start_rating})` : 'Disabled' }}
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <p v-if="league.details" class="mb-4">{{ league.details }}</p>
                        <p v-else class="italic text-gray-500 mb-4">No details provided for this league.</p>

                        <div class="mt-4 text-sm text-gray-500 space-x-4">
                            <span v-if="league.started_at">
                                Starts: {{ new Date(league.started_at).toLocaleDateString() }}
                            </span>
                            <span v-if="league.finished_at">
                                Ends: {{ new Date(league.finished_at).toLocaleDateString() }}
                            </span>
                        </div>

                        <!-- Join/Leave Actions -->
                        <div v-if="isAuthenticated" class="mt-6">
                            <Button
                                v-if="!isCurrentUserInLeague"
                                :disabled="isJoining || !league.has_rating"
                                @click="handleJoinLeague"
                            >
                                <Spinner v-if="isJoining" class="w-4 h-4 mr-2"/>
                                <UserPlusIcon v-else class="w-4 h-4 mr-2"/>
                                Join League
                            </Button>

                            <Button
                                v-else
                                :disabled="isLeaving || !league.has_rating"
                                variant="secondary"
                                @click="handleLeaveLeague"
                            >
                                <Spinner v-if="isLeaving" class="w-4 h-4 mr-2"/>
                                <LogOutIcon v-else class="w-4 h-4 mr-2"/>
                                Leave League
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                <!-- Players & Ratings Card -->
                <Card class="mb-8">
                    <CardHeader>
                        <CardTitle>Players & Ratings</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div v-if="isLoadingPlayers" class="text-center py-4">
                            <Spinner class="w-6 h-6 mx-auto text-primary"/>
                            <p class="mt-2 text-gray-500">Loading players...</p>
                        </div>

                        <div v-else-if="playersError" class="text-red-500 bg-red-100 p-4 rounded">
                            Error loading players: {{ playersError.message }}
                        </div>

                        <PlayerList
                            v-else
                            :currentUserId="user?.id ?? null"
                            :isAuthenticated="isAuthenticated"
                            :leagueId="Number(league.id)"
                            :players="players || []"
                            @challenge="openChallengeModal"
                        />
                    </CardContent>
                </Card>

                <!-- Matches Card -->
                <Card v-if="isAuthenticated">
                    <CardHeader>
                        <CardTitle>Matches</CardTitle>
                        <CardDescription>Recent challenges and games.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div v-if="isLoadingMatches">
                            <Spinner class="w-6 h-6 mx-auto text-primary"/>
                            <p class="mt-2 text-center text-gray-500">Loading matches...</p>
                        </div>

                        <div v-else-if="matchesError" class="text-red-500 bg-red-100 p-4 rounded">
                            Error loading matches: {{ matchesError.message }}
                        </div>

                        <div v-else-if="!matches || matches.length === 0" class="text-gray-500 text-center py-4">
                            No matches found for this league.
                        </div>

                        <ul v-else class="space-y-3">
                            <li v-for="match in matches" :key="match.id"
                                class="border p-4 rounded-lg hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800/50">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <div class="flex items-center space-x-2">
                                            <span class="text-sm text-gray-500">{{ new Date(match.created_at).toLocaleDateString() }}</span>
                                            <span class="px-2 py-0.5 text-xs rounded-full"
                                                  :class="{
                                                    'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300': match.status === 'in_progress',
                                                    'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300': match.status === 'completed',
                                                  }">
                                                {{
                                                    match.status === 'in_progress' ? 'In Progress' : 'Completed'
                                                }}
                                            </span>
                                        </div>
                                        <h3 class="font-medium mt-1">
                                            <span :class="{ 'text-red-600 dark:text-red-400': match.status === 'completed' && match.winner_rating_id !== match.first_rating_id,
                                                           'text-green-600 dark:text-green-400': match.status === 'completed' && match.winner_rating_id === match.first_rating_id }">
                                                <span v-if="match.status === 'completed'" class="font-semibold">
                                                    ({{
                                                        match.winner_rating_id === match.first_rating_id ? '+' + match.rating_change_for_winner : match.rating_change_for_loser
                                                    }})
                                                </span>
                                                {{
                                                    match.firstPlayer?.user?.lastname + ' ' + match.firstPlayer?.user?.firstname.charAt(0) + '.' || 'Player 1'
                                                }}
                                            </span>
                                            <span class="mx-2 font-semibold">{{
                                                    match.first_user_score || 0
                                                }} VS {{ match.second_user_score || 0 }}</span>
                                            <span :class="{ 'text-red-600 dark:text-red-400': match.status === 'completed' && match.winner_rating_id !== match.second_rating_id,
                                                           'text-green-600 dark:text-green-400': match.status === 'completed' && match.winner_rating_id === match.second_rating_id }">
                                                {{
                                                    match.secondPlayer?.user?.lastname + ' ' + match.secondPlayer?.user?.firstname.charAt(0) + '.' || 'Player 2'
                                                }}
                                                <span v-if="match.status === 'completed'" class="font-semibold">
                                                    ({{
                                                        match.winner_rating_id === match.second_rating_id ? '+' + match.rating_change_for_winner : match.rating_change_for_loser
                                                    }})
                                                </span>
                                            </span>
                                        </h3>

                                        <p v-if="match.details" class="text-sm text-gray-600 mt-1 dark:text-gray-400">
                                            {{ match.details }}
                                        </p>
                                    </div>
                                    <div>
                                        <!-- Match actions based on state -->
                                        <div v-if="match.status === 'in_progress' &&
                                            (match.firstPlayer?.user?.id === user?.id ||
                                             match.secondPlayer?.user?.id === user?.id)"
                                             class="space-y-2">
                                            <Button
                                                size="sm"
                                                variant="outline"
                                                @click="openResultModal(match)">
                                                Submit Result
                                            </Button>

                                            <!-- Only receivers can decline -->
                                            <Button
                                                v-if="!isMatchSender(match)"
                                                :disabled="isProcessingAction"
                                                class="text-red-600 border-red-300 hover:bg-red-50 ml-2"
                                                size="sm"
                                                variant="outline"
                                                @click="declineMatch(match)">
                                                {{ isProcessingAction ? 'Processing...' : 'Decline' }}
                                            </Button>
                                        </div>
                                    </div>
                                </div>
                            </li>
                        </ul>
                    </CardContent>
                </Card>
            </template>
        </div>
    </div>

    <!-- Challenge Modal -->
    <ChallengeModal
        :league="league"
        :show="showChallengeModal"
        :targetPlayer="targetPlayerForChallenge"
        @close="showChallengeModal = false"
        @error="(error: ApiError) => displayMessage(error.message)"
        @success="handleChallengeSuccess"
    />

    <!-- Result Modal -->
    <ResultModal
        :currentUser="user"
        :matchGame="matchForResults"
        :show="showResultModal"
        :max-score="league?.max_score"
        @close="showResultModal = false"
        @error="(error: ApiError) => displayMessage(error.message)"
        @success="(message: string) => { displayMessage(message); fetchMatches(); fetchPlayers(); }"
    />

    <!-- Generic Message Modal -->
    <Modal :show="showGenericModal" @close="showGenericModal = false">
        <div class="p-6">
            <h3 class="text-lg font-medium mb-3">Notification</h3>
            <p>{{ genericModalMessage }}</p>
            <div class="mt-6 flex justify-end">
                <Button @click="showGenericModal = false">
                    Close
                </Button>
            </div>
        </div>
    </Modal>
</template>
