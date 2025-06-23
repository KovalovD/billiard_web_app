// resources/js/pages/Leagues/Show.vue
<script lang="ts" setup>
import ChallengeModal from '@/Components/ChallengeModal.vue';
import PendingConfirmationBanner from '@/Components/PendingConfirmationBanner.vue';
import PlayerList from '@/Components/PlayerList.vue';
import ResultModal from '@/Components/ResultModal.vue';
import {Button, Card, CardContent, CardDescription, CardHeader, CardTitle, Modal, Spinner} from '@/Components/ui';
import {useAuth} from '@/composables/useAuth';
import {useLeagues} from '@/composables/useLeagues';
import {useLeagueStatus} from '@/composables/useLeagueStatus';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import {apiClient} from '@/lib/apiClient';
import type {ApiError, MatchGame, Player} from '@/types/api';
import {Head, Link} from '@inertiajs/vue3';
import {
    ArrowLeftIcon,
    ChevronDownIcon,
    GamepadIcon,
    LogInIcon,
    LogOutIcon,
    PencilIcon,
    SmileIcon,
    TrophyIcon,
    UserPlusIcon,
    UsersIcon,
    WalletIcon
} from 'lucide-vue-next';
import {computed, onMounted, ref, watch} from 'vue';
import AddPlayerModal from "@/Components/AddPlayerModal.vue";
import {useLocale} from '@/composables/useLocale';

const adminDropdownOpen = ref(false);
const adminDropdownRef = ref(null);

defineOptions({layout: AuthenticatedLayout});

const props = defineProps<{
    leagueId: number | string;
}>();

const {user, isAuthenticated, isAdmin} = useAuth();
const leagues = useLeagues();
const { t } = useLocale();
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

const {data: authUserRating, execute: loadUserRating} = leagues.loadUserRating(props.leagueId);

const {
    data: players,
    isLoading: isLoadingPlayers,
    error: playersError,
    execute: fetchPlayers
} = leagues.fetchLeaguePlayers(props.leagueId);

const {execute: joinLeagueAction, isActing: isJoining, error: joinError} = leagues.joinLeague(props.leagueId);

const {execute: leaveLeagueAction, isActing: isLeaving, error: leaveError} = leagues.leaveLeague(props.leagueId);

// Matches
const {
    data: matches,
    isLoading: isLoadingMatches,
    error: matchesError,
    execute: fetchMatches
} = leagues.fetchLeagueMatches(props.leagueId);

// Check if current user is in the league (only for authenticated users)
const isCurrentUserInLeague = computed(() => {
    if (!user.value || !players.value || !isAuthenticated.value) return false;
    return leagues.isPlayerInLeague(players.value, user.value.id);
});

// Page title
const pageTitle = computed(() => {
    if (!league.value) return t('League Details');
    const status = getLeagueStatus(league.value);
    const suffix = status ? ` (${status.text})` : '';
    return t('League: :name:suffix', {name: league.value.name, suffix});
});

const leagueStatus = computed(() => getLeagueStatus(league.value));
const canUserJoinLeague = computed(() => canJoinLeague(league.value));
const joinErrorMessage = computed(() => getJoinErrorMessage(league.value));

// Format currency
const formatCurrency = (amount: number): string => {
    return amount.toLocaleString('uk-UA', {
        style: 'currency',
        currency: 'UAH'
    }).replace('UAH', '₴');
};

// Find an active match by ID from query params
const findMatchById = (id: string): MatchGame | null => {
    if (!matches.value) return null;
    return matches.value.find((m) => m.id.toString() === id) || null;
};

// Get match status display text
const getMatchStatusDisplay = (status: string): string => {
    switch (status) {
        case 'in_progress':
            return t('In Progress');
        case 'completed':
            return t('Completed');
        case 'must_be_confirmed':
            return t('Needs Confirmation');
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

// Check if current user needs to confirm a result (authenticated users only)
const needsConfirmation = (match: MatchGame): boolean => {
    if (!user.value || !isAuthenticated.value || match.status !== 'must_be_confirmed' || !match.result_confirmed || !Array.isArray(match.result_confirmed)) return false;

    let userRatingId: number | null = null;
    if (match.firstPlayer?.user?.id === user.value.id) {
        userRatingId = match.first_rating_id;
    } else if (match.secondPlayer?.user?.id === user.value.id) {
        userRatingId = match.second_rating_id;
    }

    if (!userRatingId) return false;

    const userConfirmation = match.result_confirmed.find(
        (confirmation) => confirmation && typeof confirmation === 'object' && confirmation.key === userRatingId,
    );

    return !userConfirmation;
};

// Utility function to display messages
const displayMessage = (message: string) => {
    genericModalMessage.value = t(message);
    showGenericModal.value = true;
};

// Check if user is the sender of the match (authenticated users only)
const isMatchSender = (match: MatchGame): boolean => {
    if (!isAuthenticated.value || !user.value) return false;
    return match.firstPlayer?.user?.id === user.value.id;
};

// Actions (only for authenticated users)
const handleJoinLeague = async () => {
    if (!isAuthenticated.value) {
        displayMessage(t('You must be logged in to join this league.'));
        return;
    }

    const success = await joinLeagueAction();
    if (success) {
        await fetchPlayers();
    } else if (joinError.value) {
        displayMessage(`${t('Failed to join league')}: ${joinError.value.message || t('Unknown error')}`);
    }
};

// State for Add Player modal
const showAddPlayerModal = ref(false);

// Handle player added (admin only)
const handlePlayerAdded = async () => {
    if (!isAuthenticated.value || !isAdmin.value) return;
    await fetchPlayers();
    displayMessage(t('Player added successfully'));
};

const handleLeaveLeague = async () => {
    if (!isAuthenticated.value || !isCurrentUserInLeague.value) return;

    const success = await leaveLeagueAction();
    if (success) {
        await fetchPlayers();
    } else if (leaveError.value) {
        displayMessage(`${t('Failed to leave league')}: ${leaveError.value.message || t('Unknown error')}`);
    }
};

const openChallengeModal = (player: Player) => {
    if (!isAuthenticated.value) {
        displayMessage(t('You must be logged in to challenge players.'));
        return;
    }
    targetPlayerForChallenge.value = player;
    showChallengeModal.value = true;
};

const handleChallengeSuccess = (message: string) => {
    displayMessage(message);
    fetchMatches();
    fetchPlayers();
};

const openResultModal = (match: MatchGame) => {
    if (!isAuthenticated.value) {
        displayMessage(t('You must be logged in to submit results.'));
        return;
    }
    matchForResults.value = match;
    showResultModal.value = true;
};

// Decline match function (authenticated users only)
const declineMatch = async (match: MatchGame) => {
    if (!isAuthenticated.value) {
        displayMessage(t('You must be logged in to decline matches.'));
        return;
    }

    if (isProcessingAction.value || !match.id) return;

    isProcessingAction.value = true;
    try {
        await apiClient(`/api/leagues/${props.leagueId}/players/match-games/${match.id}/decline`, {
            method: 'post',
        });
        displayMessage(t('Match declined successfully.'));
        await fetchMatches();
        await fetchPlayers();
    } catch (error: any) {
        displayMessage(`${t('Failed to decline match')}: ${error.message || t('Unknown error')}`);
    } finally {
        isProcessingAction.value = false;
    }
};

const handleResultSuccess = (message: string) => {
    displayMessage(message);

    if (matchForResults.value && isAuthenticated.value && user.value) {
        let userRatingId: number | null = null;
        if (matchForResults.value.firstPlayer?.user?.id === user.value.id) {
            userRatingId = matchForResults.value.first_rating_id;
        } else if (matchForResults.value.secondPlayer?.user?.id === user.value.id) {
            userRatingId = matchForResults.value.second_rating_id;
        }

        if (userRatingId) {
            const confirmSignature = `${matchForResults.value.first_user_score}-${matchForResults.value.second_user_score}`;

            if (!matchForResults.value.result_confirmed) {
                matchForResults.value.result_confirmed = [];
            }

            const existingConfirmIndex = matchForResults.value.result_confirmed.findIndex(
                (conf) => conf && typeof conf === 'object' && conf.key === userRatingId,
            );

            const userConfirmation = {
                key: userRatingId,
                score: confirmSignature,
            };

            if (existingConfirmIndex >= 0) {
                matchForResults.value.result_confirmed[existingConfirmIndex] = userConfirmation;
            } else {
                matchForResults.value.result_confirmed.push(userConfirmation);
            }

            if (matches.value) {
                const matchInList = matches.value.find((m) => m.id === matchForResults.value?.id);
                if (matchInList) {
                    if (!matchInList.result_confirmed) {
                        matchInList.result_confirmed = [];
                    }

                    const listConfirmIndex = matchInList.result_confirmed.findIndex(
                        (conf) => conf && typeof conf === 'object' && conf.key === userRatingId,
                    );

                    if (listConfirmIndex >= 0) {
                        matchInList.result_confirmed[listConfirmIndex] = userConfirmation;
                    } else {
                        matchInList.result_confirmed.push(userConfirmation);
                    }
                }
            }
        }
    }

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

    // Only load user rating if authenticated
    if (isAuthenticated.value) {
        loadUserRating();
    }

    const url = new URL(window.location.href);
    routeMatchId.value = url.searchParams.get('matchId');

    document.addEventListener('click', (event) => {
        if (adminDropdownRef.value && !adminDropdownRef.value.contains(event.target)) {
            adminDropdownOpen.value = false;
        }
    });
});

// Watch for matches to be loaded, then check for routeMatchId (authenticated users only)
watch(
    [matches, routeMatchId],
    ([currentMatches, currentMatchId]) => {
        if (currentMatches && currentMatchId && isAuthenticated.value) {
            const matchToOpen = findMatchById(currentMatchId);
            if (matchToOpen) {
                matchForResults.value = matchToOpen;
                showResultModal.value = true;
                const url = new URL(window.location.href);
                url.searchParams.delete('matchId');
                window.history.replaceState({}, document.title, url);
                routeMatchId.value = null;
            }
        }
    },
    {immediate: true},
);
</script>

<template>
    <Head :title="pageTitle"/>

    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <!-- Header with actions -->
            <div class="mb-6 flex items-center justify-between">
                <Link :href="route('leagues.index.page')">
                    <Button variant="outline">
                        <ArrowLeftIcon class="mr-2 h-4 w-4"/>
                        {{ t('Back to Leagues') }}
                    </Button>
                </Link>

                <div v-if="isAuthenticated && isAdmin && league" class="flex space-x-2">
                    <Link :href="route('leagues.edit', { league: league.id })">
                        <Button variant="secondary">
                            <PencilIcon class="mr-2 h-4 w-4"/>
                            {{ t('Edit League') }}
                        </Button>
                    </Link>

                    <div ref="adminDropdownRef" class="relative">
                        <Button variant="secondary" @click="adminDropdownOpen = !adminDropdownOpen">
                            <UsersIcon class="mr-2 h-4 w-4"/>
                            {{ t('Manage Players') }}
                            <ChevronDownIcon class="ml-1 h-4 w-4"/>
                        </Button>

                        <div
                            v-if="adminDropdownOpen"
                            class="ring-opacity-5 absolute right-0 z-10 mt-2 w-56 origin-top-right rounded-md bg-white shadow-lg ring-1 ring-black focus:outline-none dark:bg-gray-800 dark:ring-gray-700"
                            role="menu"
                        >
                            <div class="py-1" role="none">
                                <Link
                                    :href="`/admin/leagues/${league.id}/pending-players`"
                                    class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700"
                                    role="menuitem"
                                >
                                    {{ t('Pending Players') }}
                                </Link>
                                <Link
                                    :href="`/admin/leagues/${league.id}/confirmed-players`"
                                    class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700"
                                    role="menuitem"
                                >
                                    {{ t('Confirmed Players') }}
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Login prompt for guests -->
                <div v-else-if="!isAuthenticated" class="text-center">
                    <Link :href="route('login')" class="text-sm text-blue-600 hover:underline dark:text-blue-400">
                        <LogInIcon class="mr-1 inline h-4 w-4"/>
                        {{ t('Login to participate') }}
                    </Link>
                </div>
            </div>

            <!-- Only show confirmation banner for authenticated users -->
            <PendingConfirmationBanner
                v-if="isAuthenticated && isCurrentUserInLeague && authUserRating"
                :is-confirmed="authUserRating.is_confirmed"
                :league-name="league?.name"
            />

            <!-- League Loading State -->
            <div v-if="isLoadingLeague" class="p-10 text-center">
                <Spinner class="text-primary mx-auto h-8 w-8"/>
                <p class="mt-2 text-gray-500">{{ t('Loading league information...') }}</p>
            </div>

            <!-- League Error State -->
            <div v-else-if="leagueError" class="mb-6 rounded bg-red-100 p-4 text-red-500">
                {{ t('Error loading league: :error', { error: leagueError.message }) }}
            </div>

            <!-- League Content -->
            <template v-else-if="league">
                <!-- League Info Card -->
                <Card class="mb-8">
                    <CardHeader>
                        <div class="flex items-start justify-between">
                            <div>
                                <CardTitle class="flex items-center gap-2">
                                    {{ league.name }}
                                    <span v-if="leagueStatus"
                                          :class="['rounded-full px-2 py-1 text-xs font-semibold', leagueStatus.class]">
                                       <component :is="leagueStatus.icon" class="mr-1 inline h-3 w-3"/>
                                       {{ leagueStatus.text }}
                                   </span>
                                </CardTitle>
                                <CardDescription class="mt-2">
                                    <div class="mt-2 flex flex-wrap gap-4">
                                       <span class="flex items-center gap-1">
                                           <TrophyIcon class="h-4 w-4"/>
                                           {{ t('Game') }}: {{ league.game ?? t('N/A') }}
                                       </span>
                                        <span class="flex items-center gap-1">
                                           <UsersIcon class="h-4 w-4"/>
                                           {{ t('Players') }}: {{
                                                league.active_players ?? 0
                                            }}{{ league.max_players ? `/${league.max_players}` : '' }}
                                       </span>
                                        <span class="flex items-center gap-1">
                                           <SmileIcon class="h-4 w-4"/>
                                           {{ t('Rating') }}: {{
                                                league.has_rating ? `Enabled (${league.start_rating})` : 'Disabled'
                                            }}
                                       </span>
                                    </div>
                                </CardDescription>
                            </div>
                            <div v-if="league.picture" class="hidden sm:block">
                                <img :alt="league.name" :src="league.picture"
                                     class="h-24 w-24 rounded-lg object-cover"/>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <p v-if="league.details" class="mb-4 whitespace-pre-wrap">{{ league.details }}</p>
                        <p v-else class="mb-4 text-gray-500 italic">{{ t('No details provided for this league.') }}</p>

                        <div class="mt-4 grid grid-cols-1 gap-4 text-sm sm:grid-cols-2 lg:grid-cols-4">
                            <div v-if="league.started_at" class="rounded-lg bg-gray-50 p-3 dark:bg-gray-800">
                                <span class="font-medium text-gray-600 dark:text-gray-400">{{ t('Start Date') }}</span>
                                <p class="text-gray-900 dark:text-gray-200">
                                    {{ new Date(league.started_at).toLocaleDateString() }}
                                </p>
                            </div>
                            <div v-if="league.finished_at" class="rounded-lg bg-gray-50 p-3 dark:bg-gray-800">
                                <span class="font-medium text-gray-600 dark:text-gray-400">{{ t('End Date') }}</span>
                                <p class="text-gray-900 dark:text-gray-200">
                                    {{ new Date(league.finished_at).toLocaleDateString() }}
                                </p>
                            </div>
                            <div v-if="!league.game_multiplayer" class="rounded-lg bg-gray-50 p-3 dark:bg-gray-800">
                                <span class="font-medium text-gray-600 dark:text-gray-400">{{ t('Max Score') }}</span>
                                <p class="text-gray-900 dark:text-gray-200">{{ league.max_score || 'N/A' }}</p>
                            </div>
                            <div v-if="!league.game_multiplayer" class="rounded-lg bg-gray-50 p-3 dark:bg-gray-800">
                                <span class="font-medium text-gray-600 dark:text-gray-400">{{ t('Invite Expiry') }}</span>
                                <p class="text-gray-900 dark:text-gray-200">{{ league.invite_days_expire || 'N/A' }}
                                    days</p>
                            </div>
                            <!-- Grand Final Fund accumulated - only for multiplayer games -->
                            <div v-if="league.game_multiplayer && league.grand_final_fund_accumulated !== undefined"
                                 class="rounded-lg bg-yellow-50 p-3 dark:bg-yellow-900/20">
                                <span class="flex items-center gap-1 font-medium text-yellow-800 dark:text-yellow-300">
                                    <WalletIcon class="h-4 w-4"/>
                                    {{ t('Grand Final Fund') }}
                                </span>
                                <p class="text-lg font-bold text-yellow-800 dark:text-yellow-300">
                                    {{ formatCurrency(league.grand_final_fund_accumulated) }}
                                </p>
                                <p class="text-xs text-yellow-700 dark:text-yellow-400">
                                    {{ t('Accumulated from finished games') }}
                                </p>
                            </div>
                        </div>

                        <!-- Multiplayer Games Section - Available to everyone -->
                        <div v-if="league?.game_multiplayer" class="mb-6 mt-4">
                            <Link :href="`/leagues/${leagueId}/multiplayer-games`">
                                <Button variant="secondary">
                                    <GamepadIcon class="mr-2 h-4 w-4"/>
                                    {{ t('View Multiplayer Games') }}
                                </Button>
                            </Link>
                        </div>

                        <!-- Join/Leave Actions - Only for authenticated users -->
                        <div v-if="isAuthenticated" class="mt-6">
                            <template v-if="!isCurrentUserInLeague">
                                <Button v-if="canUserJoinLeague" :disabled="isJoining" @click="handleJoinLeague">
                                    <Spinner v-if="isJoining" class="mr-2 h-4 w-4"/>
                                    <UserPlusIcon v-else class="mr-2 h-4 w-4"/>
                                    {{ t('Join League') }}
                                </Button>
                                <div v-else class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ joinErrorMessage }}
                                </div>
                            </template>

                            <Button v-else :disabled="isLeaving" variant="secondary" @click="handleLeaveLeague">
                                <Spinner v-if="isLeaving" class="mr-2 h-4 w-4"/>
                                <LogOutIcon v-else class="mr-2 h-4 w-4"/>
                                {{ t('Leave League') }}
                            </Button>
                        </div>

                        <!-- Guest prompts -->
                        <div v-else class="mt-6 space-y-2">
                            <div class="rounded-md bg-blue-50 p-4 dark:bg-blue-900/20">
                                <p class="text-blue-800 dark:text-blue-300">
                                    <LogInIcon class="mr-2 inline h-4 w-4"/>
                                    <Link :href="route('login')" class="font-medium hover:underline">
                                        {{ t('Login to join this league') }}
                                    </Link>
                                    {{ t('and participate in matches.') }}
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Players & Ratings Card -->
                <Card class="mb-8">
                    <CardHeader>
                        <div class="flex items-center justify-between w-full">
                            <CardTitle>{{ t('Players & Ratings') }}</CardTitle>
                            <!-- Only show add player button to authenticated admins -->
                            <Button v-if="isAuthenticated && isAdmin" variant="outline"
                                    @click="showAddPlayerModal = true">
                                <UserPlusIcon class="mr-2 h-4 w-4"/>
                                {{ t('Add Player') }}
                            </Button>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div v-if="isLoadingPlayers" class="py-4 text-center">
                            <Spinner class="text-primary mx-auto h-6 w-6"/>
                            <p class="mt-2 text-gray-500">{{ t('Loading players...') }}</p>
                        </div>

                        <div v-else-if="playersError" class="rounded bg-red-100 p-4 text-red-500">
                            {{ t('Error loading players: :error', { error: playersError.message }) }}
                        </div>

                        <PlayerList
                            v-else
                            :currentUserId="user?.id ?? null"
                            :isAuthenticated="isAuthenticated"
                            :multiplayer-game="league.game_multiplayer"
                            :leagueId="Number(league.id)"
                            :players="players || []"
                            :auth-user-have-ongoing-match="authUserRating?.hasOngoingMatches"
                            :auth-user-is-confirmed="authUserIsConfirmed"
                            :authUserRating="authUserRating"
                            @challenge="openChallengeModal"
                        />
                    </CardContent>
                </Card>

                <!-- Matches Card - Show to everyone but limit actions for guests -->
                <Card v-if="!league.game_multiplayer">
                    <CardHeader>
                        <CardTitle>{{ t('Matches') }}</CardTitle>
                        <CardDescription>{{ t('Recent challenges and games.') }}</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div v-if="isLoadingMatches">
                            <Spinner class="text-primary mx-auto h-6 w-6"/>
                            <p class="mt-2 text-center text-gray-500">{{ t('Loading matches...') }}</p>
                        </div>

                        <div v-else-if="matchesError" class="rounded bg-red-100 p-4 text-red-500">
                            {{ t('Error loading matches: :error', { error: matchesError.message }) }}
                        </div>

                        <div v-else-if="!matches || matches.length === 0" class="py-4 text-center text-gray-500">
                            {{ t('No matches found for this league.') }}
                        </div>

                        <ul v-else class="space-y-3">
                            <li
                                v-for="match in matches"
                                :key="match.id"
                                :class="isAuthenticated && needsConfirmation(match) ? 'border-amber-300 bg-amber-50 dark:border-amber-700 dark:bg-amber-900/20' : ''"
                                class="rounded-lg border p-4 hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800/50"
                            >
                                <div class="flex items-start justify-between">
                                    <div>
                                        <div class="flex items-center space-x-2">
                                            <span class="text-sm text-gray-500">{{ new Date(match.created_at).toLocaleDateString() }}</span>
                                            <span class="rounded-full px-2 py-0.5 text-xs"
                                                  :class="getMatchStatusClass(match.status)">
                                               {{ getMatchStatusDisplay(match.status) }}
                                           </span>
                                            <span
                                                v-if="isAuthenticated && match.status === 'must_be_confirmed' && needsConfirmation(match)"
                                                class="rounded-full bg-amber-100 px-2 py-0.5 text-xs text-amber-800 dark:bg-amber-900/30 dark:text-amber-300"
                                            >
                                               {{ t('Needs your confirmation') }}
                                           </span>
                                            <span
                                                v-else-if="
                                                   isAuthenticated &&
                                                   match.status === 'must_be_confirmed' &&
                                                   !needsConfirmation(match) &&
                                                   (match.firstPlayer?.user?.id === user?.id || match.secondPlayer?.user?.id === user?.id)
                                               "
                                                class="rounded-full bg-green-100 px-2 py-0.5 text-xs text-green-800 dark:bg-green-900/30 dark:text-green-300"
                                            >
                                               {{ t('Waiting for opponent to confirm') }}
                                           </span>
                                        </div>
                                        <h3 class="mt-1 font-medium">
                                           <span
                                               :class="{
                                                   'text-red-600 dark:text-red-400':
                                                       match.status === 'completed' && match.winner_rating_id !== match.first_rating_id,
                                                   'text-green-600 dark:text-green-400':
                                                       match.status === 'completed' && match.winner_rating_id === match.first_rating_id,
                                               }"
                                           >
                                               <span v-if="match.status === 'completed'" class="font-semibold">
                                                   ({{
                                                       match.winner_rating_id === match.first_rating_id
                                                           ? '+' + match.rating_change_for_winner
                                                           : match.rating_change_for_loser
                                                   }})
                                               </span>
                                               {{
                                                   match.firstPlayer?.user?.lastname + ' ' + match.firstPlayer?.user?.firstname.charAt(0) + '.' ||
                                                   'Player 1'
                                               }}
                                           </span>
                                            <span class="mx-2 font-semibold"
                                            >{{ match.first_user_score || 0 }} VS {{
                                                    match.second_user_score || 0
                                                }}</span
                                            >
                                            <span
                                                :class="{
                                                   'text-red-600 dark:text-red-400':
                                                       match.status === 'completed' && match.winner_rating_id !== match.second_rating_id,
                                                   'text-green-600 dark:text-green-400':
                                                       match.status === 'completed' && match.winner_rating_id === match.second_rating_id,
                                               }"
                                            >
                                               {{
                                                    match.secondPlayer?.user?.lastname + ' ' + match.secondPlayer?.user?.firstname.charAt(0) + '.' ||
                                                    'Player 2'
                                                }}
                                               <span v-if="match.status === 'completed'" class="font-semibold">
                                                   ({{
                                                       match.winner_rating_id === match.second_rating_id
                                                           ? '+' + match.rating_change_for_winner
                                                           : match.rating_change_for_loser
                                                   }})
                                               </span>
                                           </span>
                                        </h3>

                                        <p v-if="match.details" class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                            {{ match.details }}
                                        </p>
                                    </div>
                                    <div>
                                        <!-- Match actions based on state - Only for authenticated users -->
                                        <div
                                            v-if="
                                               isAuthenticated &&
                                               (match.status === 'in_progress' || match.status === 'must_be_confirmed') &&
                                               (match.firstPlayer?.user?.id === user?.id || match.secondPlayer?.user?.id === user?.id)
                                           "
                                            class="space-y-2"
                                        >
                                            <Button
                                                size="sm"
                                                variant="outline"
                                                :class="
                                                   needsConfirmation(match) ? 'animate-pulse border-amber-300 bg-amber-100 hover:bg-amber-200' : ''
                                               "
                                                @click="openResultModal(match)"
                                            >
                                                {{ needsConfirmation(match) ? t('Confirm Result') : t('Submit Result') }}
                                            </Button>

                                            <!-- Only receivers can decline -->
                                            <Button
                                                v-if="!isMatchSender(match) && match.status === 'in_progress'"
                                                :disabled="isProcessingAction"
                                                class="ml-2 border-red-300 text-red-600 hover:bg-red-50"
                                                size="sm"
                                                variant="outline"
                                                @click="declineMatch(match)"
                                            >
                                                {{ isProcessingAction ? t('Processing...') : t('Decline') }}
                                            </Button>
                                        </div>
                                        <!-- Login prompt for guests to participate -->
                                        <div
                                            v-else-if="
                                               !isAuthenticated &&
                                               (match.status === 'in_progress' || match.status === 'must_be_confirmed')
                                           "
                                            class="text-center"
                                        >
                                            <Link :href="route('login')"
                                                  class="text-xs text-blue-600 hover:underline dark:text-blue-400">
                                                {{ t('Login to participate') }}
                                            </Link>
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

    <!-- Challenge Modal - Only for authenticated users -->
    <ChallengeModal
        v-if="isAuthenticated"
        :league="league"
        :show="showChallengeModal"
        :targetPlayer="targetPlayerForChallenge"
        @close="showChallengeModal = false"
        @error="(error: ApiError) => displayMessage(error.message)"
        @success="handleChallengeSuccess"
    />

    <!-- Result Modal - Only for authenticated users -->
    <ResultModal
        v-if="isAuthenticated"
        :currentUser="user"
        :matchGame="matchForResults"
        :show="showResultModal"
        :max-score="league?.max_score"
        @close="showResultModal = false"
        @error="(error: ApiError) => displayMessage(error.message)"
        @success="handleResultSuccess"
    />

    <!-- Add Player Modal - Only for authenticated admins -->
    <AddPlayerModal
        v-if="isAuthenticated && isAdmin"
        :entity-id="leagueId"
        :entity-type="'league'"
        :show="showAddPlayerModal"
        @added="handlePlayerAdded"
        @close="showAddPlayerModal = false"
    />

    <!-- Generic Message Modal -->
    <Modal :show="showGenericModal" @close="showGenericModal = false">
        <div class="p-6">
            <h3 class="mb-3 text-lg font-medium">{{ t('Notification') }}</h3>
            <p>{{ genericModalMessage }}</p>
            <div class="mt-6 flex justify-end">
                <Button @click="showGenericModal = false">{{ t('Close') }}</Button>
            </div>
        </div>
    </Modal>
</template>
