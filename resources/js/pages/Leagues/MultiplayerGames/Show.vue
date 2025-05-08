// resources/js/pages/Leagues/MultiplayerGames/Show.vue
<script lang="ts" setup>
import {Button, Card, CardContent, CardHeader, CardTitle, Spinner} from '@/Components/ui';
import {useAuth} from '@/composables/useAuth';
import {useMultiplayerGames} from '@/composables/useMultiplayerGames';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import {apiClient} from '@/lib/apiClient';
import type {League, MultiplayerGame, MultiplayerGamePlayer} from '@/types/api';
import {Head, Link} from '@inertiajs/vue3';
import {ArrowLeftIcon, CheckIcon, HeartIcon, MinusIcon, PlusIcon, TrophyIcon, UserIcon} from 'lucide-vue-next';
import {computed, onMounted, ref} from 'vue';
import FinishGameModal from '@/Components/FinishGameModal.vue';
import SetModeratorModal from '@/Components/SetModeratorModal.vue';

defineOptions({layout: AuthenticatedLayout});

const props = defineProps<{
    leagueId: string | number;
    gameId: string | number;
}>();

const {isAdmin, user} = useAuth();
const {getMultiplayerGame, performGameAction, finishGame, setGameModerator, error} = useMultiplayerGames();

const league = ref<League | null>(null);
const game = ref<MultiplayerGame | null>(null);
const isLoadingLeague = ref(true);
const isLoadingGame = ref(true);
const selectedCardType = ref<'skip_turn' | 'pass_turn' | 'hand_shot' | null>(null);
const selectedTargetPlayer = ref<MultiplayerGamePlayer | null>(null);
const selectedActingPlayer = ref<MultiplayerGamePlayer | null>(null);
const showFinishModal = ref(false);
const showModeratorModal = ref(false);

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

const isInGame = computed(() => {
    if (!game.value || !user.value) return false;
    return [...game.value.active_players, ...game.value.eliminated_players]
        .some(p => p.user.id === user.value?.id);
});

const currentUserPlayer = computed(() => {
    if (!game.value) return null;
    return game.value.current_user_player;
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
        default:
            return game.value.status;
    }
});

// Fetch data
const fetchLeague = async () => {
    isLoadingLeague.value = true;
    try {
        league.value = await apiClient<League>(`/api/leagues/${props.leagueId}`);
    } catch (err) {
        console.error('Failed to fetch league:', err);
    } finally {
        isLoadingLeague.value = false;
    }
};

const fetchGame = async () => {
    isLoadingGame.value = true;
    try {
        game.value = await getMultiplayerGame(props.leagueId, props.gameId);
    } catch (err) {
        console.error('Failed to fetch multiplayer game:', err);
    } finally {
        isLoadingGame.value = false;
    }
};

// Handle game actions
const handleAction = async (
    action: 'increment_lives' | 'decrement_lives' | 'record_turn',
    targetUserId?: number,
    actingUserId?: number
) => {
    if (!game.value) return;

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
    } catch (err) {
        console.error(`Failed to perform ${action}:`, err);
    }
};

const handleUseCard = async (cardType: 'skip_turn' | 'pass_turn' | 'hand_shot', targetUserId?: number, actingUserId?: number) => {
    if (!game.value) return;

    try {
        await performGameAction(
            props.leagueId,
            props.gameId,
            'use_card',
            targetUserId,
            cardType,
            actingUserId
        );

        // Reset selections
        selectedCardType.value = null;
        selectedTargetPlayer.value = null;
        selectedActingPlayer.value = null;

        await fetchGame();
    } catch (err) {
        console.error(`Failed to use card ${cardType}:`, err);
    }
};

const handleFinishGame = async (positions: { player_id: number, position: number }[]) => {
    if (!game.value) return;

    try {
        await finishGame(props.leagueId, props.gameId, positions);
        showFinishModal.value = false;
        await fetchGame();
    } catch (err) {
        console.error('Failed to finish game:', err);
    }
};

const handleSetModerator = async (userId: number) => {
    if (!game.value) return;

    try {
        await setGameModerator(props.leagueId, props.gameId, userId);
        showModeratorModal.value = false;
        await fetchGame();
    } catch (err) {
        console.error('Failed to set moderator:', err);
    }
};

// User interface selection handlers
const selectPlayer = (player: MultiplayerGamePlayer, type: 'target' | 'acting') => {
    if (type === 'target') {
        selectedTargetPlayer.value = selectedTargetPlayer.value?.id === player.id ? null : player;
    } else {
        selectedActingPlayer.value = selectedActingPlayer.value?.id === player.id ? null : player;
    }
};

const selectCard = (cardType: 'skip_turn' | 'pass_turn' | 'hand_shot', player: MultiplayerGamePlayer) => {
    if (!player.cards[cardType]) return; // Card unavailable

    if (selectedCardType.value === cardType && selectedActingPlayer.value?.id === player.id) {
        selectedCardType.value = null;
        selectedActingPlayer.value = null;
    } else {
        selectedCardType.value = cardType;
        selectedActingPlayer.value = player;

        // If not pass_turn, no target needed
        if (cardType !== 'pass_turn') {
            selectedTargetPlayer.value = null;
        }
    }
};

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
                        v-if="game?.status === 'in_progress' && (isAdmin || isModerator)"
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
                </div>
            </div>

            <!-- Status Message -->
            <div
                v-if="formattedStatusMessage"
                class="mb-6 rounded-md border border-blue-100 bg-blue-50 p-4 text-blue-800 dark:border-blue-800 dark:bg-blue-900/20 dark:text-blue-300"
            >
                {{ formattedStatusMessage }}
            </div>

            <!-- Error message -->
            <div v-if="error" class="mb-6 rounded bg-red-100 p-4 text-red-600">
                {{ error }}
            </div>

            <!-- Loading state -->
            <div v-if="isLoadingGame" class="flex justify-center py-8">
                <Spinner class="text-primary h-8 w-8"/>
            </div>

            <div v-else-if="game" class="space-y-6">
                <!-- Game Info Card -->
                <Card>
                    <CardHeader>
                        <CardTitle>Game Information</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                            <div class="space-y-1">
                                <p class="text-sm text-gray-500">Status</p>
                                <p class="font-medium">{{ statusText }}</p>
                            </div>
                            <div class="space-y-1">
                                <p class="text-sm text-gray-500">Players</p>
                                <p class="font-medium">{{ game.active_players_count }} active /
                                    {{ game.total_players_count }} total</p>
                            </div>
                            <div class="space-y-1">
                                <p class="text-sm text-gray-500">Initial Lives</p>
                                <p class="font-medium">{{ game.initial_lives }}</p>
                            </div>

                            <div v-if="game.status === 'registration'" class="space-y-1">
                                <p class="text-sm text-gray-500">Registration Ends</p>
                                <p class="font-medium">{{ formatDate(game.registration_ends_at) }}</p>
                            </div>
                            <div v-if="game.status === 'in_progress' || game.status === 'completed'" class="space-y-1">
                                <p class="text-sm text-gray-500">Started</p>
                                <p class="font-medium">{{ formatDate(game.started_at) }}</p>
                            </div>
                            <div v-if="game.status === 'completed'" class="space-y-1">
                                <p class="text-sm text-gray-500">Completed</p>
                                <p class="font-medium">{{ formatDate(game.completed_at) }}</p>
                            </div>
                            <div v-if="game.moderator_user_id" class="space-y-1">
                                <p class="text-sm text-gray-500">Game Moderator</p>
                                <p class="font-medium">
                                    {{
                                        game.active_players.find(p => p.user.id === game.moderator_user_id)?.user.firstname || 'Unknown'
                                    }}
                                    {{
                                        game.active_players.find(p => p.user.id === game.moderator_user_id)?.user.lastname || ''
                                    }}
                                    <span v-if="game.moderator_user_id === user?.id" class="ml-1 text-xs text-blue-600">(You)</span>
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Central Game Management Section (Only for in-progress games) -->
                <Card v-if="game.status === 'in_progress' && (isInGame || isAdmin || isModerator)">
                    <CardHeader>
                        <CardTitle>Game Management</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="mb-4">
                            <h3 class="mb-2 font-medium">Select Player to Act As:</h3>
                            <div class="flex flex-wrap gap-2">
                                <Button
                                    v-for="player in game.active_players"
                                    :key="player.id"
                                    :disabled="!isModerator && player.user.id !== user?.id"
                                    :variant="selectedActingPlayer?.id === player.id ? 'default' : 'outline'"
                                    size="sm"
                                    @click="selectPlayer(player, 'acting')"
                                >
                                    {{ player.user.firstname }} {{ player.user.lastname }}
                                    <span v-if="player.is_current_turn" class="ml-1 text-xs">(Current Turn)</span>
                                </Button>
                            </div>
                        </div>

                        <div v-if="selectedActingPlayer" class="space-y-4">
                            <div class="rounded-lg border p-4">
                                <h3 class="mb-2 font-medium">
                                    Actions for {{ selectedActingPlayer.user.firstname }}
                                    {{ selectedActingPlayer.user.lastname }}
                                </h3>

                                <div class="mb-4 flex items-center space-x-4">
                                    <div class="flex items-center">
                                        <HeartIcon class="mr-2 h-5 w-5 text-red-500"/>
                                        <span class="font-medium">{{ selectedActingPlayer.lives }} lives</span>
                                    </div>

                                    <Button
                                        size="sm"
                                        variant="outline"
                                        @click="handleAction('decrement_lives', selectedActingPlayer.user.id, selectedActingPlayer.user.id)"
                                    >
                                        <MinusIcon class="h-4 w-4"/>
                                    </Button>

                                    <Button
                                        size="sm"
                                        variant="outline"
                                        @click="handleAction('increment_lives', selectedActingPlayer.user.id, selectedActingPlayer.user.id)"
                                    >
                                        <PlusIcon class="h-4 w-4"/>
                                    </Button>
                                </div>

                                <div class="mb-4">
                                    <h4 class="mb-2 text-sm font-medium">Available Cards:</h4>
                                    <div class="flex flex-wrap gap-2">
                                        <Button
                                            v-if="selectedActingPlayer.cards.skip_turn"
                                            :variant="selectedCardType === 'skip_turn' ? 'default' : 'outline'"
                                            size="sm"
                                            @click="selectCard('skip_turn', selectedActingPlayer)"
                                        >
                                            Skip Turn
                                        </Button>
                                        <Button
                                            v-if="selectedActingPlayer.cards.pass_turn"
                                            :variant="selectedCardType === 'pass_turn' ? 'default' : 'outline'"
                                            size="sm"
                                            @click="selectCard('pass_turn', selectedActingPlayer)"
                                        >
                                            Pass Turn
                                        </Button>
                                        <Button
                                            v-if="selectedActingPlayer.cards.hand_shot"
                                            :variant="selectedCardType === 'hand_shot' ? 'default' : 'outline'"
                                            size="sm"
                                            @click="selectCard('hand_shot', selectedActingPlayer)"
                                        >
                                            Hand Shot
                                        </Button>
                                        <p v-if="!selectedActingPlayer.cards.skip_turn && !selectedActingPlayer.cards.pass_turn && !selectedActingPlayer.cards.hand_shot"
                                           class="text-sm text-gray-500">
                                            No cards available
                                        </p>
                                    </div>
                                </div>

                                <div v-if="selectedCardType === 'pass_turn'" class="mb-4">
                                    <h4 class="mb-2 text-sm font-medium">Select Target Player:</h4>
                                    <div class="flex flex-wrap gap-2">
                                        <Button
                                            v-for="player in game.active_players.filter(p => p.id !== selectedActingPlayer.id)"
                                            :key="player.id"
                                            :variant="selectedTargetPlayer?.id === player.id ? 'default' : 'outline'"
                                            size="sm"
                                            @click="selectPlayer(player, 'target')"
                                        >
                                            {{ player.user.firstname }} {{ player.user.lastname }}
                                        </Button>
                                    </div>
                                </div>

                                <div class="flex justify-between">
                                    <Button
                                        v-if="selectedCardType"
                                        :disabled="selectedCardType === 'pass_turn' && !selectedTargetPlayer"
                                        @click="handleUseCard(
                      selectedCardType,
                      selectedTargetPlayer?.user.id,
                      selectedActingPlayer.user.id
                    )"
                                    >
                                        Use {{
                                            selectedCardType === 'skip_turn' ? 'Skip Turn' : selectedCardType === 'pass_turn' ? 'Pass Turn' : 'Hand Shot'
                                        }} Card
                                    </Button>

                                    <Button
                                        v-if="selectedActingPlayer.is_current_turn"
                                        @click="handleAction('record_turn', undefined, selectedActingPlayer.user.id)"
                                    >
                                        Record Turn
                                    </Button>
                                </div>
                            </div>
                        </div>

                        <div v-else class="text-center text-gray-500">
                            <p>Select a player to manage their actions</p>
                        </div>
                    </CardContent>
                </Card>

                <!-- Active Players Card -->
                <Card>
                    <CardHeader>
                        <CardTitle>Active Players</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div v-if="game.active_players.length === 0" class="py-4 text-center text-gray-500">
                            No active players remaining.
                        </div>

                        <div v-else class="divide-y">
                            <div
                                v-for="player in game.active_players"
                                :key="player.id"
                                :class="[
                  'flex items-center justify-between py-3',
                  player.is_current_turn ? 'bg-green-50 dark:bg-green-900/10' : '',
                ]"
                            >
                                <div class="flex items-center space-x-3">
                                    <div
                                        v-if="player.is_current_turn"
                                        class="flex h-6 w-6 items-center justify-center rounded-full bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300"
                                    >
                                        <CheckIcon class="h-4 w-4"/>
                                    </div>
                                    <div v-else class="h-6 w-6 text-center text-sm text-gray-500">
                                        {{ player.turn_order }}
                                    </div>
                                    <div>
                                        <p class="font-medium">{{ player.user.firstname }} {{
                                                player.user.lastname
                                            }}</p>
                                        <p v-if="player.user.id === user?.id" class="text-xs text-blue-600">(You)</p>
                                        <p v-if="player.user.id === game.moderator_user_id"
                                           class="text-xs text-purple-600">(Moderator)</p>
                                    </div>
                                </div>

                                <div class="flex items-center space-x-2">
                                    <div class="flex items-center space-x-1">
                                        <HeartIcon class="h-5 w-5 text-red-500"/>
                                        <span class="font-medium">{{ player.lives }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Eliminated Players Card -->
                <Card v-if="game.eliminated_players.length > 0">
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
                                        <p v-if="player.user.id === user?.id" class="text-xs text-blue-600">(You)</p>
                                    </div>
                                </div>

                                <div class="text-sm text-gray-500">
                                    Eliminated: {{ formatDate(player.eliminated_at) }}
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </div>

    <!-- Finish Game Modal -->
    <FinishGameModal
        v-if="game"
        :game="game"
        :show="showFinishModal"
        @close="showFinishModal = false"
        @finish="handleFinishGame"
    />

    <!-- Set Moderator Modal -->
    <SetModeratorModal
        v-if="game"
        :game="game"
        :show="showModeratorModal"
        @close="showModeratorModal = false"
        @set-moderator="handleSetModerator"
    />
</template>
