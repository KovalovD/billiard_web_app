<script lang="ts" setup>
import SetModeratorModal from '@/Components/SetModeratorModal.vue';
import GameRegistry from '@/Components/GameRegistry.vue';
import LivesEditorView from '@/Components/LivesEditorView.vue';
import PlayersList from '@/Components/PlayersList.vue';
import PrizeSummaryCard from '@/Components/PrizeSummaryCard.vue';
import RatingSummaryCard from '@/Components/RatingSummaryCard.vue';
import GameActionPanel from '@/Components/GameActionPanel.vue';
import TimeFundCard from '@/Components/TimeFundCard.vue';
import MultiplayerGameSummary from '@/Components/MultiplayerGameSummary.vue';
import {ArrowLeftIcon, TrophyIcon, UserIcon, UserPlusIcon} from 'lucide-vue-next';
import {Button, Card, CardContent, CardHeader, CardTitle} from '@/Components/ui';
import {useAuth} from '@/composables/useAuth';
import {useMultiplayerGames} from '@/composables/useMultiplayerGames';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import {apiClient} from '@/lib/apiClient';
import type {League, MultiplayerGame, MultiplayerGamePlayer} from '@/types/api';
import {Head, Link} from '@inertiajs/vue3';
import {computed, onMounted, ref, watch} from 'vue';
import GameFinishModal from "@/Components/GameFinishModal.vue";
import AddPlayerModal from "@/Components/AddPlayerModal.vue";

defineOptions({layout: AuthenticatedLayout});

const props = defineProps<{
    leagueId: string | number;
    gameId: string | number;
}>();

const {isAdmin, user} = useAuth();
const {
    getMultiplayerGame,
    performGameAction,
    setGameModerator,
    error,
    isLoading: isActionLoading
} = useMultiplayerGames();

const league = ref<League | null>(null);
const game = ref<MultiplayerGame | null>(null);
const isLoadingLeague = ref(true);
const isLoadingGame = ref(true);
const selectedCardType = ref<'skip_turn' | 'pass_turn' | 'hand_shot' | null>(null);
const selectedTargetPlayer = ref<MultiplayerGamePlayer | null>(null);
const selectedActingPlayer = ref<MultiplayerGamePlayer | null>(null);
const showFinishModal = ref(false);
const showModeratorModal = ref(false);
const actionFeedback = ref<{ type: 'success' | 'error', message: string } | null>(null);
const activeTab = ref<'game' | 'prizes' | 'ratings' | 'timefund'>('game');

// Add to existing refs
const showAddPlayerModal = ref(false);

// Add method to handle player addition
const handlePlayerAdded = async () => {
    await fetchGame();
    actionFeedback.value = {
        type: 'success',
        message: 'Player added successfully'
    };
};

// Computed properties
const formattedStatusMessage = computed(() => {
    if (!game.value) return '';

    if (game.value.status === 'registration') {
        return 'The game is currently in the registration phase. Players can join until the game starts.';
    } else if (game.value.status === 'in_progress') {
        if (isModerator.value) {
            return 'You are the moderator. You can perform actions for any player.';
        } else if (game.value.current_turn_player_id === user.value?.id) {
            return "It's your turn to play.";
        } else {
            return 'Waiting for the current player to make a move.';
        }
    } else if (game.value.status === 'completed') {
        return 'The game has finished. Check the results below.';
    }

    return '';
});

const isModerator = computed(() => {
    if (!game.value || !user.value) return false;
    return game.value.is_current_user_moderator;
});

// Get the current turn player
const currentTurnPlayer = computed(() => {
    if (!game.value) return null;
    return game.value.active_players.find(player => player.is_current_turn) || null;
});

// Format date for display
const formatDate = (dateString: string | null): string => {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleString();
};

// Get status badge color class
const statusBadgeClass = computed(() => {
    if (!game.value) return '';

    switch (game.value.status) {
        case 'registration':
            return 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300';
        case 'in_progress':
            return 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300';
        case 'completed':
        case 'finished':
            return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
        default:
            return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
    }
});

// Get status display text
const statusText = computed(() => {
    if (!game.value) return '';

    switch (game.value.status) {
        case 'registration':
            return 'Registration Open';
        case 'in_progress':
            return 'In Progress';
        case 'completed':
            return 'Completed';
        case 'finished':
            return 'Finished';
        default:
            return game.value.status;
    }
});

// Fetch data
const fetchLeague = async () => {
    isLoadingLeague.value = true;
    try {
        league.value = await apiClient<League>(`/api/leagues/${props.leagueId}`);
// eslint-disable-next-line
    } catch (err) {
        // Error handling is managed by the composable
    } finally {
        isLoadingLeague.value = false;
    }
};

const fetchGame = async () => {
    isLoadingGame.value = true;
    try {
        game.value = await getMultiplayerGame(props.leagueId, props.gameId);

        // After fetching the game, check if we need to update the selected acting player
        if (selectedActingPlayer.value) {
            updateSelectedActingPlayer();
        } else if (game.value.status === 'in_progress') {
            // Auto-select the current user's player or current turn player when first loading
            selectDefaultActingPlayer();
        }
// eslint-disable-next-line
    } catch (err) {
        // Error handling is managed by the composable
    } finally {
        isLoadingGame.value = false;
    }
};

// Update the selected acting player with fresh data from the game
const updateSelectedActingPlayer = () => {
    selectDefaultActingPlayer();
};

// Select a default acting player (current user or current turn)
const selectDefaultActingPlayer = () => {
    if (!game.value || !user.value) return;

    // First try to select the current user's player
    const currentTurnUser = game.value.active_players.find(p => p.user.id === game.value?.current_turn_player_id);

    if (currentTurnUser) {
        selectedActingPlayer.value = currentTurnUser;
    } else if (isModerator.value && currentTurnPlayer.value) {
        // If user is moderator, select the current turn player
        selectedActingPlayer.value = currentTurnPlayer.value;
    }
};

// Handle game actions
const handleAction = async (
    action: 'increment_lives' | 'decrement_lives' | 'record_turn',
    targetUserId?: number,
    actingUserId?: number
) => {
    if (!game.value) return;

    actionFeedback.value = null;

    try {
        await performGameAction(
            props.leagueId,
            props.gameId,
            action,
            targetUserId,
            undefined,
            actingUserId
        );
        await fetchGame();

        // Show success feedback
        if (action === 'increment_lives' && game.value) {
            actionFeedback.value = {
                type: 'success',
                message: `Life successfully incremented`
            };
        }
        if (action === 'decrement_lives' && game.value) {
            actionFeedback.value = {
                type: 'success',
                message: `Life successfully decremented`
            };
        }


        // If the action was 'record_turn', select the new current turn player
        if (action === 'record_turn' && game.value) {
            const newCurrentTurnPlayer = game.value.active_players.find(p => p.is_current_turn);
            if (newCurrentTurnPlayer) {
                selectedActingPlayer.value = newCurrentTurnPlayer;
                selectedCardType.value = null;
                selectedTargetPlayer.value = null;

                // Show a notification that turn has changed
                actionFeedback.value = {
                    type: 'success',
                    message: `Turn passed to ${newCurrentTurnPlayer.user.firstname} ${newCurrentTurnPlayer.user.lastname}`
                };
            }
        }
    } catch (err: any) {
        // Show error feedback
        actionFeedback.value = {
            type: 'error',
            message: err.message || `Failed to perform ${action}`
        };
    }
};

const handleUseCard = async (cardType: 'skip_turn' | 'pass_turn' | 'hand_shot', targetPlayerId?: number, actingUserId?: number) => {
    if (!game.value) return;

    actionFeedback.value = null;

    try {
        await performGameAction(
            props.leagueId,
            props.gameId,
            'use_card',
            targetPlayerId,
            cardType,
            actingUserId
        );

        // Show success feedback
        let message = `Used ${cardType.replace('_', ' ')} card successfully`;

        // Add more specific messages for each card type
        if (cardType === 'skip_turn') {
            message = 'Skip Turn card used - your turn is skipped, game moves to the next player';
        } else if (cardType === 'pass_turn') {
            const targetPlayer = game.value.active_players.find(p => p.user.id === targetPlayerId);
            message = `Pass Turn card used - turn passed to ${targetPlayer?.user.firstname} ${targetPlayer?.user.lastname}. After they play, the turn will return to you`;
        } else if (cardType === 'hand_shot') {
            message = 'Hand Shot card used - you can place the cue ball anywhere on the table';
        }

        actionFeedback.value = {
            type: 'success',
            message
        };

        // Reset selections
        selectedCardType.value = null;
        selectedTargetPlayer.value = null;

        // Don't clear acting player, just update it with fresh data
        await fetchGame();
    } catch (err) {
        // Show error feedback
        actionFeedback.value = {
            type: 'error',
            message: err.message || `Failed to use card ${cardType}`
        };
    }
};
const handleSetModerator = async (userId: number) => {
    if (!game.value) return;

    actionFeedback.value = null;

    try {
        await setGameModerator(props.leagueId, props.gameId, userId);
        showModeratorModal.value = false;

        actionFeedback.value = {
            type: 'success',
            message: 'Moderator set successfully'
        };

        await fetchGame();
    } catch (err: any) {
        actionFeedback.value = {
            type: 'error',
            message: err.message || 'Failed to set moderator'
        };
    }
};

// User interface selection handlers
const selectPlayer = (player: MultiplayerGamePlayer) => {
    // When selecting a different acting player, clear the card and target selections
    if (selectedActingPlayer.value?.id !== player.id) {
        selectedCardType.value = null;
        selectedTargetPlayer.value = null;
    }
    selectedActingPlayer.value = selectedActingPlayer.value?.id === player.id ? null : player;
};

const handlePlayerAction = (actionData: any) => {
    if (actionData.cardType) {
        handleUseCard(
            actionData.cardType,
            actionData.targetPlayerId,
            selectedActingPlayer.value?.user.id
        );
    }
};

const handleGameFinished = () => {
    fetchLeague();
    fetchGame();
};

// Clear action feedback after 5 seconds
watch(actionFeedback, (newValue) => {
    if (newValue) {
        setTimeout(() => {
            actionFeedback.value = null;
        }, 5000);
    }
});

// Watch for game changes to update UI state
watch(game, (newGame) => {
    if (newGame && newGame.status === 'completed') {
        // Clear selections if game is completed
        selectedActingPlayer.value = null;
        selectedCardType.value = null;
        selectedTargetPlayer.value = null;
    }
}, {deep: true});

// Lifecycle hooks
onMounted(() => {
    fetchLeague();
    fetchGame();
});
</script>

<template>
    <Head :title="game ? `${game.name} - ${league?.name || 'Multiplayer Game'}` : 'Multiplayer Game'"/>
    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <!-- Header with back button -->
            <div class="mb-6 flex items-center justify-between">
                <Link :href="`/leagues/${leagueId}/multiplayer-games`">
                    <Button variant="outline">
                        <ArrowLeftIcon class="mr-2 h-4 w-4"/>
                        Back to Games
                    </Button>
                </Link>

                <h1 class="text-2xl font-semibold">
                    {{ game ? game.name : 'Multiplayer Game' }}
                </h1>

                <div class="flex space-x-2">
                    <span
                        v-if="game"
                        :class="['rounded-full px-3 py-1 text-sm font-semibold', statusBadgeClass]"
                    >
                        {{ statusText }}
                    </span>

                    <Button
                        v-if="game?.status === 'completed' && (isAdmin || isModerator)"
                        variant="outline"
                        @click="showFinishModal = true"
                    >
                        <TrophyIcon class="mr-2 h-4 w-4"/>
                        Finish Game
                    </Button>
                    <Button
                        v-if="game?.status === 'in_progress' && (isAdmin || isModerator)"
                        variant="outline"
                        @click="showModeratorModal = true"
                    >
                        <UserIcon class="mr-2 h-4 w-4"/>
                        Change Moderator
                    </Button>
                    <Button
                        v-if="isAdmin && game?.status === 'registration'"
                        variant="outline"
                        @click="showAddPlayerModal = true"
                    >
                        <UserPlusIcon class="mr-2 h-4 w-4"/>
                        Add Player
                    </Button>
                </div>
            </div>
            <!-- Status Message -->
            <div
                v-if="formattedStatusMessage"
                class="mb-6 rounded-md border border-blue-100 bg-blue-50 p-4 text-blue-800 dark:border-blue-800 dark:bg-blue-900/20 dark:text-blue-300"
            >
                {{ formattedStatusMessage }}
            </div>

            <!-- Action Feedback Message -->
            <div
                v-if="actionFeedback"
                :class="actionFeedback.type === 'success'
                    ? 'border-green-100 bg-green-50 text-green-800 dark:border-green-800 dark:bg-green-900/20 dark:text-green-300'
                    : 'border-red-100 bg-red-50 text-red-800 dark:border-red-800 dark:bg-red-900/20 dark:text-red-300'"
                class="mb-6 rounded-md border p-4"
            >
                {{ actionFeedback.message }}
            </div>

            <!-- Error message from composable -->
            <div v-if="error" class="mb-6 rounded bg-red-100 p-4 text-red-600">
                {{ error }}
            </div>

            <!-- Loading state -->

            <div v-if="game">
                <!-- Registration phase -->
                <div v-if="game.status === 'registration'">
                    <GameRegistry :game="game" :league-id="leagueId" @updated="fetchGame"/>
                </div>

                <!-- Game in progress or completed -->
                <div v-else class="space-y-6">
                    <!-- Game summary -->
                    <MultiplayerGameSummary :game="game"/>

                    <!-- Tab navigation -->
                    <div class="border-b border-gray-200 dark:border-gray-700">
                        <nav class="-mb-px flex space-x-8">
                            <button
                                :class="[
                                    'py-4 px-1 text-sm font-medium border-b-2',
                                    activeTab === 'game'
                                        ? 'border-blue-500 text-blue-600 dark:border-blue-400 dark:text-blue-400'
                                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300 dark:hover:border-gray-600'
                                ]"
                                @click="activeTab = 'game'"
                            >
                                Game
                            </button>
                            <button
                                :class="[
                                    'py-4 px-1 text-sm font-medium border-b-2',
                                    activeTab === 'prizes'
                                        ? 'border-blue-500 text-blue-600 dark:border-blue-400 dark:text-blue-400'
                                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300 dark:hover:border-gray-600'
                                ]"
                                @click="activeTab = 'prizes'"
                            >
                                Prizes
                            </button>
                            <button
                                :class="[
                                    'py-4 px-1 text-sm font-medium border-b-2',
                                    activeTab === 'ratings'
                                        ? 'border-blue-500 text-blue-600 dark:border-blue-400 dark:text-blue-400'
                                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300 dark:hover:border-gray-600'
                                ]"
                                @click="activeTab = 'ratings'"
                            >
                                Rating Points
                            </button>
                            <button
                                :class="[
                                    'py-4 px-1 text-sm font-medium border-b-2',
                                    activeTab === 'timefund'
                                        ? 'border-blue-500 text-blue-600 dark:border-blue-400 dark:text-blue-400'
                                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300 dark:hover:border-gray-600'
                                ]"
                                @click="activeTab = 'timefund'"
                            >
                                Time Fund
                            </button>
                        </nav>
                    </div>

                    <!-- Game tab -->
                    <div v-if="activeTab === 'game'">
                        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                            <!-- Active Players Panel -->
                            <div class="order-2 lg:order-1 lg:col-span-1">
                                <Card v-if="game.active_players.length > 0">
                                    <CardHeader>
                                        <CardTitle>Active Players</CardTitle>
                                    </CardHeader>
                                    <CardContent>
                                        <PlayersList
                                            :highlight-current-turn="true"
                                            :players="game.active_players"
                                            :selected-player-id="selectedActingPlayer?.id"
                                            :show-controls="isModerator && game.status === 'in_progress'"
                                            title=""
                                            @select-player="selectPlayer"
                                        />
                                    </CardContent>
                                </Card>

                                <!-- Eliminated Players if any -->
                                <Card
                                    v-if="game.eliminated_players.length > 0"
                                    :class="game.active_players.length > 0 ? 'mt-6' : ''"
                                >
                                    <CardHeader>
                                        <CardTitle>Eliminated Players</CardTitle>
                                    </CardHeader>
                                    <CardContent>
                                        <div class="divide-y">
                                            <div
                                                v-for="player in game.eliminated_players"
                                                :key="player.id"
                                                class="flex items-center justify-between py-3"
                                            >
                                                <div class="flex items-center space-x-3">
                                                    <div
                                                        class="flex h-6 w-6 items-center justify-center rounded-full bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200">
                                                        {{ player.finish_position }}
                                                    </div>
                                                    <div>
                                                        <p class="font-medium">{{ player.user.firstname }} {{
                                                                player.user.lastname
                                                            }}</p>
                                                        <p v-if="player.user.id === user?.id"
                                                           class="text-xs text-blue-600">(You)</p>
                                                    </div>
                                                </div>

                                                <div class="text-sm text-gray-500">
                                                    Eliminated: {{ formatDate(player?.eliminated_at) }}
                                                </div>
                                            </div>
                                        </div>
                                    </CardContent>
                                </Card>
                            </div>

                            <!-- Game Action Panel -->
                            <div v-if="game.status === 'in_progress'" class="order-1 lg:order-2 lg:col-span-2">
                                <div v-if="selectedActingPlayer" class="space-y-4">
                                    <GameActionPanel
                                        :is-current-turn="selectedActingPlayer.is_current_turn"
                                        :is-loading="isActionLoading"
                                        :player="selectedActingPlayer"
                                        :target-players="game.active_players.filter(p => p.id !== selectedActingPlayer?.id)"
                                        @decrement-lives="() => handleAction('decrement_lives', selectedActingPlayer?.user.id, selectedActingPlayer?.user.id)"
                                        @increment-lives="() => handleAction('increment_lives', selectedActingPlayer?.user.id, selectedActingPlayer?.user.id)"
                                        @record-turn="() => handleAction('record_turn', undefined, selectedActingPlayer?.user.id)"
                                        @use-card="handlePlayerAction"
                                    />
                                </div>

                                <div v-else-if="isModerator" class="space-y-4">
                                    <LivesEditorView
                                        :current-turn-player-id="game.current_turn_player_id"
                                        :is-loading="isActionLoading"
                                        :players="game.active_players"
                                        @decrement-lives="(userId) => handleAction('decrement_lives', userId)"
                                        @increment-lives="(userId) => handleAction('increment_lives', userId)"
                                        @set-turn="(userId) => handleAction('record_turn', undefined, userId)"
                                    />
                                </div>

                                <div v-else class="rounded-lg border p-4 text-center text-gray-500 dark:text-gray-400">
                                    <p>Select a player to view their actions or wait for your turn.</p>
                                </div>
                            </div>

                            <!-- Game Completed Summary -->
                            <div v-if="game.status === 'completed' || game.status === 'finished'"
                                 class="order-1 lg:order-2 lg:col-span-2">
                                <Card>
                                    <CardHeader>
                                        <CardTitle>Game Results</CardTitle>
                                    </CardHeader>
                                    <CardContent>
                                        <div class="space-y-4">
                                            <div class="overflow-auto">
                                                <table class="w-full">
                                                    <thead>
                                                    <tr class="border-b dark:border-gray-700">
                                                        <th class="px-4 py-2 text-left">Position</th>
                                                        <th class="px-4 py-2 text-left">Player</th>
                                                        <th class="px-4 py-2 text-center">Rating Points</th>
                                                        <th class="px-4 py-2 text-right">Prize</th>
                                                    </tr>
                                                    </thead>
                                                    <tbody>
                                                    <tr
                                                        v-for="player in game.eliminated_players.sort((a, b) =>
                                                            (a.finish_position || 999) - (b.finish_position || 999))"
                                                        :key="player.id"
                                                        class="border-b dark:border-gray-700"
                                                    >
                                                        <td class="px-4 py-2">
                                                            <span
                                                                :class="{
                                                                    'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300': player.finish_position === 1,
                                                                    'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200': player.finish_position !== 1
                                                                }"
                                                                class="inline-flex h-6 w-6 items-center justify-center rounded-full text-xs font-medium"
                                                            >
                                                                {{ player.finish_position }}
                                                            </span>
                                                        </td>
                                                        <td class="px-4 py-2">
                                                            {{ player.user.firstname }} {{ player.user.lastname }}
                                                            <span v-if="player.user.id === user?.id"
                                                                  class="ml-1 text-xs text-blue-600">(You)</span>
                                                        </td>
                                                        <td class="px-4 py-2 text-center">
                                                            <span
                                                                v-if="player.rating_points"
                                                                class="rounded-full bg-blue-100 px-2 py-1 text-xs text-blue-800 dark:bg-blue-900/30 dark:text-blue-300"
                                                            >
                                                                +{{ player.rating_points }}
                                                            </span>
                                                            <span v-else>—</span>
                                                        </td>
                                                        <td class="px-4 py-2 text-right">
                                                            <span v-if="player.prize_amount && player.prize_amount > 0"
                                                                  class="font-medium">
                                                                {{
                                                                    (player.prize_amount || 0).toLocaleString('uk-UA', {
                                                                        style: 'currency',
                                                                        currency: 'UAH'
                                                                    }).replace('UAH', '₴')
                                                                }}
                                                            </span>
                                                            <span v-else>—</span>
                                                        </td>
                                                    </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </CardContent>
                                </Card>
                            </div>
                        </div>
                    </div>

                    <!-- Prizes tab -->
                    <div v-if="activeTab === 'prizes'" class="space-y-6">
                        <PrizeSummaryCard :game="game"/>
                    </div>

                    <!-- Ratings tab -->
                    <div v-if="activeTab === 'ratings'" class="space-y-6">
                        <RatingSummaryCard :game="game"/>
                    </div>

                    <!-- Time Fund tab -->
                    <div v-if="activeTab === 'timefund'" class="space-y-6">
                        <TimeFundCard :game="game"/>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Set Moderator Modal -->
    <SetModeratorModal
        v-if="game"
        :game="game"
        :show="showModeratorModal"
        @close="showModeratorModal = false"
        @set-moderator="handleSetModerator"
    />

    <GameFinishModal
        :game="game"
        :leagueId="leagueId"
        :show="showFinishModal"
        @close="showFinishModal = false"
        @finished="handleGameFinished"
    />

    <AddPlayerModal
        v-if="isAdmin && game"
        :entity-id="gameId"
        :entity-type="'match'"
        :league-id="leagueId"
        :show="showAddPlayerModal"
        @added="handlePlayerAdded"
        @close="showAddPlayerModal = false"
    />
</template>
