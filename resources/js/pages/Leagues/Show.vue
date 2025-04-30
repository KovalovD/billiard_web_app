<script lang="ts" setup>
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import {Head, Link} from '@inertiajs/vue3';
import {useAuth} from '@/composables/useAuth';
import {useLeagues} from '@/composables/useLeagues';
import {computed, onMounted, ref} from 'vue';
import type {ApiError, MatchGame, Player} from '@/types/api';
import {Button, Card, CardContent, CardDescription, CardHeader, CardTitle, Modal, Spinner} from '@/Components/ui';
import PlayerList from '@/Components/PlayerList.vue';
import ChallengeModal from '@/Components/ChallengeModal.vue';
import ResultModal from '@/Components/ResultModal.vue';
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
const showGenericErrorModal = ref(false);
const genericErrorMessage = ref('');
const targetPlayerForChallenge = ref<Player | null>(null);
const matchForResults = ref<MatchGame | null>(null);

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

// Matches (would be integrated with real data)
const matches = ref<MatchGame[]>([]);
const isLoadingMatches = ref(false);
const matchesError = ref<ApiError | null>(null);

// Check if current user is in the league
const isCurrentUserInLeague = computed(() => {
    if (!user.value || !players.value) return false;
    return leagues.isPlayerInLeague(players.value, user.value.id);
});

// Page title
const pageTitle = computed(() =>
    league.value ? `League: ${league.value.name}` : 'League Details'
);

// Utility function to display errors
const displayError = (message: string) => {
    genericErrorMessage.value = message;
    showGenericErrorModal.value = true;
};

// Actions
const handleJoinLeague = async () => {
    if (!isAuthenticated.value) {
        displayError('You must be logged in to join this league.');
        return;
    }

    const success = await joinLeagueAction();
    if (success) {
        await fetchPlayers();
    } else if (joinError.value) {
        displayError(`Failed to join league: ${joinError.value.message || 'Unknown error'}`);
    }
};

const handleLeaveLeague = async () => {
    if (!isAuthenticated.value || !isCurrentUserInLeague.value) return;

    const success = await leaveLeagueAction();
    if (success) {
        await fetchPlayers();
    } else if (leaveError.value) {
        displayError(`Failed to leave league: ${leaveError.value.message || 'Unknown error'}`);
    }
};

const openChallengeModal = (player: Player) => {
    targetPlayerForChallenge.value = player;
    showChallengeModal.value = true;
};

const handleChallengeSuccess = (message: string) => {
    displayError(message); // Using error modal as a success modal too
};

// Initialize data
onMounted(() => {
    fetchLeague();
    fetchPlayers();
    // Would fetch matches here too
});
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

                        <div v-else-if="matches.length === 0" class="text-gray-500 text-center py-4">
                            No matches found for this league.
                        </div>

                        <ul v-else class="space-y-3">
                            <!-- Matches would be listed here -->
                        </ul>
                    </CardContent>
                </Card>
            </template>

            <!-- Not Found State -->
            <div v-else-if="!isLoadingLeague" class="text-gray-500 p-10 text-center">
                League not found.
            </div>
        </div>

        <!-- Modals -->
        <ChallengeModal
            :league="league"
            :show="showChallengeModal"
            :targetPlayer="targetPlayerForChallenge"
            @close="showChallengeModal = false"
            @error="(err: ApiError) => displayError(`Challenge error: ${err.message || 'Unknown error'}`)"
            @success="handleChallengeSuccess"
        />

        <ResultModal
            :currentUser="user"
            :matchGame="matchForResults"
            :show="showResultModal"
            @close="showResultModal = false"
            @error="(err: ApiError) => displayError(`Result error: ${err.message || 'Unknown error'}`)"
            @success="() => displayError('Result submitted successfully!')"
        />

        <Modal
            :show="showGenericErrorModal"
            title="Message"
            @close="showGenericErrorModal = false"
        >
            <p class="py-2">{{ genericErrorMessage }}</p>
            <template #footer>
                <Button @click="showGenericErrorModal = false">Close</Button>
            </template>
        </Modal>
    </div>
</template>
