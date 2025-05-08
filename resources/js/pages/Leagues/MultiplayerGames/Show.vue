<script lang="ts" setup>
import {Button, Card, CardContent, CardHeader, CardTitle, Spinner} from '@/Components/ui';
import {useAuth} from '@/composables/useAuth';
import {useMultiplayerGames} from '@/composables/useMultiplayerGames';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import {apiClient} from '@/lib/apiClient';
import type {League, MultiplayerGame, MultiplayerGamePlayer} from '@/types/api';
import {Head, Link} from '@inertiajs/vue3';
import {ArrowLeftIcon, CheckIcon, HeartIcon, MinusIcon, PlusIcon} from 'lucide-vue-next';
import {computed, onMounted, onUnmounted, ref} from 'vue';

defineOptions({layout: AuthenticatedLayout});

const props = defineProps<{
    leagueId: string | number;
    gameId: string | number;
}>();

const {isAdmin, user} = useAuth();
const {getMultiplayerGame, performGameAction, error, isLoading} = useMultiplayerGames();

const league = ref<League | null>(null);
const game = ref<MultiplayerGame | null>(null);
const isLoadingLeague = ref(true);
const isLoadingGame = ref(true);
const selectedCardType = ref<'skip_turn' | 'pass_turn' | 'hand_shot' | null>(null);
const selectedTargetPlayer = ref<MultiplayerGamePlayer | null>(null);
const refreshInterval = ref<number | null>(null);

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

// Check if current user is in the game
const currentPlayer = computed(() => {
    if (!game.value || !user.value) return null;

    const activePlayer = game.value.active_players.find(p => p.user.id === user.value?.id);
    if (activePlayer) return activePlayer;

    const eliminatedPlayer = game.value.eliminated_players.find(p => p.user.id === user.value?.id);
    return eliminatedPlayer || null;
});

// Check if it's current user's turn
const isCurrentUserTurn = computed(() => {
    if (!game.value || !user.value || game.value.status !== 'in_progress') return false;
    return game.value.current_turn_player_id === user.value.id;
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
const handleIncrementLives = async (playerId: number) => {
    if (!game.value) return;

    try {
        await performGameAction(props.leagueId, props.gameId, 'increment_lives', playerId);
        await fetchGame();
    } catch (err) {
        console.error('Failed to increment lives:', err);
    }
};

const handleDecrementLives = async (playerId: number) => {
    if (!game.value) return;

    try {
        await performGameAction(props.leagueId, props.gameId, 'decrement_lives', playerId);
        await fetchGame();
    } catch (err) {
        console.error('Failed to decrement lives:', err);
    }
};

const handleRecordTurn = async () => {
    if (!game.value || !isCurrentUserTurn.value) return;

    try {
        await performGameAction(props.leagueId, props.gameId, 'record_turn');
        await fetchGame();
    } catch (err) {
        console.error('Failed to record turn:', err);
    }
};

const selectCard = (cardType: 'skip_turn' | 'pass_turn' | 'hand_shot') => {
    if (selectedCardType.value === cardType) {
        selectedCardType.value = null;
    } else {
        selectedCardType.value = cardType;

        // If not pass_turn, no target needed
        if (cardType !== 'pass_turn') {
            selectedTargetPlayer.value = null;
        }
    }
};

const selectTargetPlayer = (player: MultiplayerGamePlayer) => {
    if (!selectedCardType.value || selectedCardType.value !== 'pass_turn') return;

    if (selectedTargetPlayer.value?.id === player.id) {
        selectedTargetPlayer.value = null;
    } else {
        selectedTargetPlayer.value = player;
    }
};

const useCard = async () => {
    if (!game.value || !selectedCardType.value || !currentPlayer.value) return;

    // For pass_turn, we need a target player
    if (selectedCardType.value === 'pass_turn' && !selectedTargetPlayer.value) {
        alert('Please select a target player for the Pass Turn card.');
        return;
    }

    const targetUserId = selectedTargetPlayer.value?.user.id;

    try {
        await performGameAction(
            props.leagueId,
            props.gameId,
            'use_card',
            targetUserId || undefined,
            selectedCardType.value
        );
        selectedCardType.value = null;
        selectedTargetPlayer.value = null;
        await fetchGame();
    } catch (err) {
        console.error('Failed to use card:', err);
    }
};

// Lifecycle hooks
onMounted(() => {
    fetchLeague();
    fetchGame();

    // Set up refresh interval for in-progress games
    refreshInterval.value = window.setInterval(() => {
        if (game.value?.status === 'in_progress') {
            fetchGame();
        }
    }, 10000); // Refresh every 10 seconds
});

// Clean up interval on component unmount
onUnmounted(() => {
    if (refreshInterval.value) {
        clearInterval(refreshInterval.value);
    }
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

                <div>
                    <span
                        v-if="game"
                        :class="['rounded-full px-3 py-1 text-sm font-semibold', statusBadgeClass]"
                    >
                        {{ statusText }}
                    </span>
                </div>
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
                        </div>
                    </CardContent>
                </Card>

                <!-- Cards Section (Only for active players in in-progress games) -->
                <Card v-if="game.status === 'in_progress' && currentPlayer && !currentPlayer.eliminated_at">
                    <CardHeader>
                        <CardTitle>Your Cards</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="mb-4 space-y-1">
                            <p v-if="isCurrentUserTurn" class="font-medium text-green-600">
                                It's your turn! Use your cards or record your turn after playing.
                            </p>
                            <p v-else class="font-medium text-gray-500">
                                Wait for your turn to play.
                            </p>
                        </div>

                        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                            <!-- Skip Turn Card -->
                            <div
                                :class="[
                                    'cursor-pointer rounded-lg border p-4 text-center transition',
                                    currentPlayer.cards.skip_turn
                                        ? 'hover:shadow-md'
                                        : 'cursor-not-allowed opacity-50',
                                    selectedCardType === 'skip_turn'
                                        ? 'border-blue-500 bg-blue-50 dark:border-blue-400 dark:bg-blue-900/20'
                                        : ''
                                ]"
                                @click="currentPlayer.cards.skip_turn && selectCard('skip_turn')"
                            >
                                <h3 class="mb-2 font-semibold">Skip Turn</h3>
                                <p class="text-sm text-gray-600">Skip your turn, play moves to the next player</p>
                            </div>

                            <!-- Pass Turn Card -->
                            <div
                                :class="[
                                    'cursor-pointer rounded-lg border p-4 text-center transition',
                                    currentPlayer.cards.pass_turn
                                        ? 'hover:shadow-md'
                                        : 'cursor-not-allowed opacity-50',
                                    selectedCardType === 'pass_turn'
                                        ? 'border-blue-500 bg-blue-50 dark:border-blue-400 dark:bg-blue-900/20'
                                        : ''
                                ]"
                                @click="currentPlayer.cards.pass_turn && selectCard('pass_turn')"
                            >
                                <h3 class="mb-2 font-semibold">Pass Turn</h3>
                                <p class="text-sm text-gray-600">Force another player to take your turn</p>
                            </div>

                            <!-- Hand Shot Card -->
                            <div
                                :class="[
                                    'cursor-pointer rounded-lg border p-4 text-center transition',
                                    currentPlayer.cards.hand_shot
                                        ? 'hover:shadow-md'
                                        : 'cursor-not-allowed opacity-50',
                                    selectedCardType === 'hand_shot'
                                        ? 'border-blue-500 bg-blue-50 dark:border-blue-400 dark:bg-blue-900/20'
                                        : ''
                                ]"
                                @click="currentPlayer.cards.hand_shot && selectCard('hand_shot')"
                            >
                                <h3 class="mb-2 font-semibold">Hand Shot</h3>
                                <p class="text-sm text-gray-600">Play with ball in hand</p>
                            </div>
                        </div>

                        <div v-if="selectedCardType" class="mt-4 flex items-center justify-between">
                            <p class="text-sm text-gray-600">
                                <span v-if="selectedCardType === 'pass_turn'">
                                    Select a player to pass your turn to.
                                </span>
                                <span v-else>
                                    Click Use Card to use {{
                                        selectedCardType === 'skip_turn' ? 'Skip Turn' : 'Hand Shot'
                                    }} card.
                                </span>
                            </p>
                            <Button
                                :disabled="selectedCardType === 'pass_turn' && !selectedTargetPlayer"
                                @click="useCard"
                            >
                                Use Card
                            </Button>
                        </div>

                        <div v-if="isCurrentUserTurn" class="mt-4 border-t pt-4">
                            <Button class="w-full" @click="handleRecordTurn">
                                Record My Turn
                            </Button>
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
                                    player.is_current_turn
                                        ? 'bg-green-50 dark:bg-green-900/10'
                                        : '',
                                    selectedTargetPlayer?.id === player.id
                                        ? 'border-l-4 border-blue-500 pl-3'
                                        : ''
                                ]"
                                @click="selectTargetPlayer(player)"
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
                                    </div>
                                </div>

                                <div class="flex items-center space-x-2">
                                    <div class="flex items-center space-x-1">
                                        <HeartIcon class="h-5 w-5 text-red-500"/>
                                        <span class="font-medium">{{ player.lives }}</span>
                                    </div>

                                    <div v-if="isAdmin" class="flex space-x-1">
                                        <Button
                                            class="h-8 w-8 p-0"
                                            size="sm"
                                            variant="outline"
                                            @click.stop="handleDecrementLives(player.user.id)"
                                        >
                                            <MinusIcon class="h-4 w-4"/>
                                        </Button>
                                        <Button
                                            class="h-8 w-8 p-0"
                                            size="sm"
                                            variant="outline"
                                            @click.stop="handleIncrementLives(player.user.id)"
                                        >
                                            <PlusIcon class="h-4 w-4"/>
                                        </Button>
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
</template>
