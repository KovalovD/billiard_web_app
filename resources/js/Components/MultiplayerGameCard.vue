// resources/js/Components/MultiplayerGameCard.vue
<script lang="ts" setup>
import {Button} from '@/Components/ui';
import {useAuth} from '@/composables/useAuth';
import {useMultiplayerGames} from '@/composables/useMultiplayerGames';
import type {MultiplayerGame} from '@/types/api';
import {Link} from '@inertiajs/vue3';
import {computed} from 'vue';

interface Props {
    game: MultiplayerGame;
    leagueId: number | string;
}

const props = defineProps<Props>();
const emit = defineEmits(['updated']);

const {isAdmin, user} = useAuth();
const {
    joinMultiplayerGame,
    leaveMultiplayerGame,
    startMultiplayerGame,
    cancelMultiplayerGame,
    isLoading
} = useMultiplayerGames();

// Check if current user is in the game
const isUserInGame = computed(() => {
    const userId = user.value?.id;
    if (!userId) return false;

    const playerExists = props.game.active_players.some(player => player.user.id === userId) ||
        props.game.eliminated_players.some(player => player.user.id === userId);

    return playerExists;
});

// Format date for display
const formatDate = (dateString: string | null): string => {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleString();
};

// Get game status display text
const statusText = computed(() => {
    switch (props.game.status) {
        case 'registration':
            return 'Registration Open';
        case 'in_progress':
            return 'In Progress';
        case 'completed':
            return 'Completed';
        default:
            return props.game.status;
    }
});

// Join a game
const handleJoin = async () => {
    try {
        await joinMultiplayerGame(props.leagueId, props.game.id);
        emit('updated');
        // eslint-disable-next-line
    } catch (err) {
        // Error is handled by the composable
    }
};

// Leave a game
const handleLeave = async () => {
    try {
        await leaveMultiplayerGame(props.leagueId, props.game.id);
        emit('updated');
        // eslint-disable-next-line
    } catch (err) {
        // Error is handled by the composable
    }
};

// Start a game (admin only)
const handleStart = async () => {
    try {
        await startMultiplayerGame(props.leagueId, props.game.id);
        emit('updated');
        // eslint-disable-next-line
    } catch (err) {
        // Error is handled by the composable
    }
};

// Cancel a game (admin only)
const handleCancel = async () => {
    if (!confirm('Are you sure you want to cancel this game?')) {
        return;
    }

    try {
        await cancelMultiplayerGame(props.leagueId, props.game.id);
        emit('updated');
        // eslint-disable-next-line
    } catch (err) {
        // Error is handled by the composable
    }
};

// Get status badge color class
const statusBadgeClass = computed(() => {
    switch (props.game.status) {
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
</script>

<template>
    <div class="rounded-lg border p-4 transition hover:shadow-md dark:border-gray-700">
        <div class="mb-4 flex items-start justify-between">
            <div class="flex-1">
                <h2 class="text-lg font-semibold text-blue-700 dark:text-blue-400">
                    {{ game.name }}
                    <span
                        :class="['ml-2 rounded-full px-2 py-1 text-xs font-semibold', statusBadgeClass]"
                    >
                        {{ statusText }}
                    </span>
                </h2>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    Players: {{ game.total_players_count }} / {{ game.max_players || 'Unlimited' }}
                </p>
            </div>
        </div>

        <div class="space-y-2 text-sm">
            <div v-if="game.status === 'registration'" class="flex items-center justify-between">
                <span class="text-gray-600 dark:text-gray-400">Registration ends:</span>
                <span>{{ formatDate(game.registration_ends_at) }}</span>
            </div>
            <div v-if="game.status === 'in_progress' || game.status === 'completed'"
                 class="flex items-center justify-between">
                <span class="text-gray-600 dark:text-gray-400">Started:</span>
                <span>{{ formatDate(game.started_at) }}</span>
            </div>
            <div v-if="game.status === 'completed'" class="flex items-center justify-between">
                <span class="text-gray-600 dark:text-gray-400">Completed:</span>
                <span>{{ formatDate(game.completed_at) }}</span>
            </div>
            <div v-if="game.status === 'in_progress'" class="flex items-center justify-between">
                <span class="text-gray-600 dark:text-gray-400">Active players:</span>
                <span>{{ game.active_players_count }} / {{ game.total_players_count }}</span>
            </div>
        </div>

        <div class="mt-4 flex space-x-2">
            <Link
                :href="`/leagues/${leagueId}/multiplayer-games/${game.id}`"
                class="flex-1"
            >
                <Button class="w-full" variant="outline">
                    {{ game.status === 'registration' ? 'View Registration' : 'View Game' }}
                </Button>
            </Link>

            <template v-if="game.status === 'registration'">
                <Button
                    v-if="!isUserInGame && game.is_registration_open"
                    :disabled="isLoading"
                    class="flex-1"
                    @click="handleJoin"
                >
                    Join
                </Button>
                <Button
                    v-else-if="isUserInGame"
                    :disabled="isLoading"
                    class="flex-1"
                    variant="destructive"
                    @click="handleLeave"
                >
                    Leave
                </Button>
            </template>

            <template v-if="isAdmin && game.status === 'registration'">
                <Button
                    :disabled="isLoading || game.total_players_count < 2"
                    class="flex-1"
                    @click="handleStart"
                >
                    Start Game
                </Button>
                <Button
                    :disabled="isLoading"
                    class="flex-1"
                    variant="destructive"
                    @click="handleCancel"
                >
                    Cancel
                </Button>
            </template>
        </div>
    </div>
</template>
