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
import {ArrowLeftIcon, LogOutIcon, PencilIcon, SmileIcon, TrophyIcon, UserPlusIcon, UsersIcon} from 'lucide-vue-next';
import {useLeagueStatus} from '@/composables/useLeagueStatus';
import PendingConfirmationBanner from "@/Components/PendingConfirmationBanner.vue";
import {ChevronDownIcon} from 'lucide-vue-next';

const adminDropdownOpen = ref(false);
const adminDropdownRef = ref(null);

defineOptions({layout: AuthenticatedLayout});

const props = defineProps<{
    leagueId: number | string;
}>();

const {user, isAuthenticated, isAdmin} = useAuth();
const leagues = useLeagues();
const {getLeagueStatus, canJoinLeague, getJoinErrorMessage} = useLeagueStatus();

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
    data: authUserRating,
    execute: loadUserRating
} = leagues.loadUserRating(props.leagueId);

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
const pageTitle = computed(() => {
    if (!league.value) return 'League Details';

    const status = getLeagueStatus(league.value);
    const suffix = status ? ` (${status.text})` : '';

    return `League: ${league.value.name}${suffix}`;
});

// Update the status computed property:
const leagueStatus = computed(() => getLeagueStatus(league.value));

// Update the can join check:
const canUserJoinLeague = computed(() => canJoinLeague(league.value));

// Update the error message:
const joinErrorMessage = computed(() => getJoinErrorMessage(league.value));

// Find an active match by ID from query params
const findMatchById = (id: string): MatchGame | null => {
    if (!matches.value) return null;
    return matches.value.find(m => m.id.toString() === id) || null;
};

// Get match status display text
const getMatchStatusDisplay = (status: string): string => {
    switch (status) {
        case 'in_progress':
            return 'In Progress';
        case 'completed':
            return 'Completed';
        case 'must_be_confirmed':
            return 'Needs Confirmation';
        default:
            return status;
    }
};

// Get appropriate status class based on status
const getMatchStatusClass = (status: string): string => {
    switch (status) {
        case 'in_progress':
            return 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300';
        case 'completed':
            return 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300';
        case 'must_be_confirmed':
            return 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300';
        default:
            return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
    }
};

// Check if current user needs to confirm a result
const needsConfirmation = (match: MatchGame): boolean => {
    if (!user.value || match.status !== 'must_be_confirmed' || !match.result_confirmed || !Array.isArray(match.result_confirmed)) return false;

    // Get current user's rating ID
    let userRatingId: number | null = null;
    if (match.firstPlayer?.user?.id === user.value.id) {
        userRatingId = match.first_rating_id;
    } else if (match.secondPlayer?.user?.id === user.value.id) {
        userRatingId = match.second_rating_id;
    }

    if (!userRatingId) return false;

    // Check if user has NOT confirmed yet (not found in result_confirmed array)
    const userConfirmation = match.result_confirmed.find(
        confirmation => confirmation && typeof confirmation === 'object' && confirmation.key === userRatingId
    );

    return !userConfirmation;
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

const handleResultSuccess = (message: string) => {
    displayMessage(message);

    // If the match was in the matchForResults value, update its state locally
    if (matchForResults.value) {
        // Get user's rating ID
        let userRatingId: number | null = null;
        if (matchForResults.value.firstPlayer?.user?.id === user.value?.id) {
            userRatingId = matchForResults.value.first_rating_id;
        } else if (matchForResults.value.secondPlayer?.user?.id === user.value?.id) {
            userRatingId = matchForResults.value.second_rating_id;
        }

        // If we have a valid user rating ID, mark it as confirmed in the local state
        if (userRatingId) {
            // Create a confirmation signature based on the current scores
            const confirmSignature = `${matchForResults.value.first_user_score}-${matchForResults.value.second_user_score}`;

            // Ensure the result_confirmed array exists
            if (!matchForResults.value.result_confirmed) {
                matchForResults.value.result_confirmed = [];
            }

            // Check if this user already has a confirmation
            const existingConfirmIndex = matchForResults.value.result_confirmed.findIndex(
                conf => conf && typeof conf === 'object' && conf.key === userRatingId
            );

            // Either update existing or add new confirmation
            const userConfirmation = {
                key: userRatingId,
                score: confirmSignature
            };

            if (existingConfirmIndex >= 0) {
                matchForResults.value.result_confirmed[existingConfirmIndex] = userConfirmation;
            } else {
                matchForResults.value.result_confirmed.push(userConfirmation);
            }

            // Also update this match in the matches list if it exists there
            if (matches.value) {
                const matchInList = matches.value.find(m => m.id === matchForResults.value?.id);
                if (matchInList) {
                    if (!matchInList.result_confirmed) {
                        matchInList.result_confirmed = [];
                    }

                    // Check if this user already has a confirmation in the list match
                    const listConfirmIndex = matchInList.result_confirmed.findIndex(
                        conf => conf && typeof conf === 'object' && conf.key === userRatingId
                    );

                    // Either update existing or add new confirmation to the list match
                    if (listConfirmIndex >= 0) {
                        matchInList.result_confirmed[listConfirmIndex] = userConfirmation;
                    } else {
                        matchInList.result_confirmed.push(userConfirmation);
                    }
                }
            }
        }
    }

    // Then fetch updated data from server
    fetchMatches();
    fetchPlayers();
};

const authUserIsConfirmed = computed(() => {
    return authUserRating.value?.is_confirmed === true;
});

// Initialize data
onMounted(() => {
    fetchLeague();
    fetchPlayers();
    fetchMatches();
    loadUserRating();

    // Check URL query params for matchId
    const url = new URL(window.location.href);
    routeMatchId.value = url.searchParams.get('matchId');

    document.addEventListener('click', (event) => {
        if (adminDropdownRef.value && !adminDropdownRef.value.contains(event.target)) {
            adminDropdownOpen.value = false;
        }
    });
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

                    <div ref="adminDropdownRef" class="relative">
                        <Button
                            variant="secondary"
                            @click="adminDropdownOpen = !adminDropdownOpen"
                        >
                            <UsersIcon class="w-4 h-4 mr-2"/>
                            Manage Players
                            <ChevronDownIcon class="w-4 h-4 ml-1"/>
                        </Button>

                        <div
                            v-if="adminDropdownOpen"
                            class="absolute right-0 z-10 mt-2 w-56 origin-top-right rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none dark:bg-gray-800 dark:ring-gray-700"
                            role="menu"
                        >
                            <div class="py-1" role="none">
                                <Link
                                    :href="`/admin/leagues/${league.id}/pending-players`"
                                    class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700"
                                    role="menuitem"
                                >
                                    Pending Players
                                </Link>
                                <Link
                                    :href="`/admin/leagues/${league.id}/confirmed-players`"
                                    class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700"
                                    role="menuitem"
                                >
                                    Confirmed Players
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <PendingConfirmationBanner
                v-if="isCurrentUserInLeague && authUserRating"
                :is-confirmed="authUserRating.is_confirmed"
                :league-name="league?.name"
            />

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
                        <div class="flex justify-between items-start">
                            <div>
                                <CardTitle class="flex items-center gap-2">
                                    {{ league.name }}
                                    <span v-if="leagueStatus"
                                          :class="['px-2 py-1 text-xs rounded-full font-semibold', leagueStatus.class]">
                                          <component :is="leagueStatus.icon" class="w-3 h-3 inline mr-1"/>
                                          {{ leagueStatus.text }}
                                    </span>
                                </CardTitle>
                                <CardDescription class="mt-2">
                                    <div class="flex flex-wrap gap-4 mt-2">
                                        <span class="flex items-center gap-1">
                                            <TrophyIcon class="w-4 h-4"/>
                                            Game: {{ league.game ?? 'N/A' }}
                                        </span>
                                        <span class="flex items-center gap-1">
                                            <UsersIcon class="w-4 h-4"/>
                                            Players: {{
                                                league.active_players ?? 0
                                            }}{{ league.max_players ? `/${league.max_players}` : '' }}
                                        </span>
                                        <span class="flex items-center gap-1">
                                            <SmileIcon class="w-4 h-4"/>
                                            Rating: {{
                                                league.has_rating ? `Enabled (${league.start_rating})` : 'Disabled'
                                            }}
                                        </span>
                                    </div>
                                </CardDescription>
                            </div>
                            <div v-if="league.picture" class="hidden sm:block">
                                <img :alt="league.name" :src="league.picture"
                                     class="w-24 h-24 object-cover rounded-lg"/>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <p v-if="league.details" class="mb-4 whitespace-pre-wrap">{{ league.details }}</p>
                        <p v-else class="italic text-gray-500 mb-4">No details provided for this league.</p>

                        <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 text-sm">
                            <div v-if="league.started_at" class="bg-gray-50 dark:bg-gray-800 rounded-lg p-3">
                                <span class="font-medium text-gray-600 dark:text-gray-400">Start Date</span>
                                <p class="text-gray-900 dark:text-gray-200">
                                    {{ new Date(league.started_at).toLocaleDateString() }}</p>
                            </div>
                            <div v-if="league.finished_at" class="bg-gray-50 dark:bg-gray-800 rounded-lg p-3">
                                <span class="font-medium text-gray-600 dark:text-gray-400">End Date</span>
                                <p class="text-gray-900 dark:text-gray-200">
                                    {{ new Date(league.finished_at).toLocaleDateString() }}</p>
                            </div>
                            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-3">
                                <span class="font-medium text-gray-600 dark:text-gray-400">Max Score</span>
                                <p class="text-gray-900 dark:text-gray-200">{{ league.max_score || 'N/A' }}</p>
                            </div>
                            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-3">
                                <span class="font-medium text-gray-600 dark:text-gray-400">Invite Expiry</span>
                                <p class="text-gray-900 dark:text-gray-200">{{ league.invite_days_expire || 'N/A' }}
                                    days</p>
                            </div>
                        </div>

                        <!-- Join/Leave Actions -->
                        <div v-if="isAuthenticated" class="mt-6">
                            <template v-if="!isCurrentUserInLeague">
                                <Button
                                    v-if="canUserJoinLeague"
                                    :disabled="isJoining"
                                    @click="handleJoinLeague"
                                >
                                    <Spinner v-if="isJoining" class="w-4 h-4 mr-2"/>
                                    <UserPlusIcon v-else class="w-4 h-4 mr-2"/>
                                    Join League
                                </Button>
                                <div v-else class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ joinErrorMessage }}
                                </div>
                            </template>

                            <Button
                                v-else
                                :disabled="isLeaving"
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
                            :auth-user-have-ongoing-match="authUserRating?.hasOngoingMatches"
                            :auth-user-is-confirmed="authUserIsConfirmed"
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
                                :class="needsConfirmation(match) ? 'border-amber-300 dark:border-amber-700 bg-amber-50 dark:bg-amber-900/20' : ''"
                                class="border p-4 rounded-lg hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800/50">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <div class="flex items-center space-x-2">
                                            <span class="text-sm text-gray-500">{{ new Date(match.created_at).toLocaleDateString() }}</span>
                                            <span class="px-2 py-0.5 text-xs rounded-full"
                                                  :class="getMatchStatusClass(match.status)">
                                                {{ getMatchStatusDisplay(match.status) }}
                                            </span>
                                            <span
                                                v-if="match.status === 'must_be_confirmed' && needsConfirmation(match)"
                                                class="px-2 py-0.5 text-xs rounded-full bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300">
                                                Needs your confirmation
                                            </span>
                                            <span v-else-if="match.status === 'must_be_confirmed' && !needsConfirmation(match) &&
                                                (match.firstPlayer?.user?.id === user?.id || match.secondPlayer?.user?.id === user?.id)"
                                                  class="px-2 py-0.5 text-xs rounded-full bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300">
                                                Waiting for opponent to confirm
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
                                        <div v-if="(match.status === 'in_progress' || match.status === 'must_be_confirmed') &&
                                            (match.firstPlayer?.user?.id === user?.id ||
                                             match.secondPlayer?.user?.id === user?.id)"
                                             class="space-y-2">
                                            <Button
                                                size="sm"
                                                variant="outline"
                                                :class="needsConfirmation(match) ? 'animate-pulse bg-amber-100 border-amber-300 hover:bg-amber-200' : ''"
                                                @click="openResultModal(match)">
                                                {{ needsConfirmation(match) ? 'Confirm Result' : 'Submit Result' }}
                                            </Button>

                                            <!-- Only receivers can decline -->
                                            <Button
                                                v-if="!isMatchSender(match) && match.status === 'in_progress'"
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
        @success="handleResultSuccess"
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
