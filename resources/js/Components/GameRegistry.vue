// resources/js/Components/GameRegistry.vue
<script lang="ts" setup>
import {Button, Card, CardContent, CardHeader, CardTitle, Spinner} from '@/Components/ui';
import {useAuth} from '@/composables/useAuth';
import {useMultiplayerGames} from '@/composables/useMultiplayerGames';
import type {MultiplayerGame} from '@/types/api';
import {computed, ref, watch} from 'vue';

interface Props {
    game: MultiplayerGame;
    leagueId: string | number;
}

const props = defineProps<Props>();
const emit = defineEmits(['updated']);

const {isAdmin, user} = useAuth();
const {joinMultiplayerGame, leaveMultiplayerGame, startMultiplayerGame, isLoading, error} = useMultiplayerGames();

// Local state
const errorMessage = ref<string | null>(null);
const successMessage = ref<string | null>(null);

// Computed properties
const isUserInGame = computed(() => {
    const userId = user.value?.id;
    if (!userId) return false;

    const playerExists = props.game.active_players.some(player => player.user.id === userId);
    return playerExists;
});

const canJoin = computed(() => {
    if (!props.game.is_registration_open) return false;
    if (isUserInGame.value) return false;

    // Check if game has max players and is full
    if (props.game.max_players && props.game.total_players_count >= props.game.max_players) return false;

    return true;
});

const canStart = computed(() => {
    if (!isAdmin.value) return false;
    if (props.game.status !== 'registration') return false;

    // Need at least 2 players to start
    return props.game.total_players_count >= 2;
});

const registrationUntilText = computed(() => {
    if (!props.game.registration_ends_at) return 'Open until manually closed';

    const endDate = new Date(props.game.registration_ends_at);
    return `Registration until ${endDate.toLocaleString()}`;
});

// Watch for error changes and display as needed
watch(() => error.value, (newError) => {
    if (newError) {
        errorMessage.value = newError;
        setTimeout(() => {
            errorMessage.value = null;
        }, 5000);
    }
});

// Methods
const handleJoin = async () => {
    try {
        await joinMultiplayerGame(props.leagueId, props.game.id);
        successMessage.value = 'You have successfully joined the game!';
        emit('updated');

        setTimeout(() => {
            successMessage.value = null;
        }, 5000);
    } catch (err) {
        // Error is handled by the composable and shown through the errorMessage ref
    }
};

const handleLeave = async () => {
    try {
        await leaveMultiplayerGame(props.leagueId, props.game.id);
        successMessage.value = 'You have successfully left the game.';
        emit('updated');

        setTimeout(() => {
            successMessage.value = null;
        }, 5000);
    } catch (err) {
        // Error is handled by the composable and shown through the errorMessage ref
    }
};

const handleStart = async () => {
    if (!confirm('Are you sure you want to start the game now? This will close registration.')) return;

    try {
        await startMultiplayerGame(props.leagueId, props.game.id);
        successMessage.value = 'Game has been started successfully!';
        emit('updated');

        setTimeout(() => {
            successMessage.value = null;
        }, 5000);
    } catch (err) {
        // Error is handled by the composable and shown through the errorMessage ref
    }
};

const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('uk-UA', {style: 'currency', currency: 'UAH'})
        .format(amount)
        .replace('UAH', '₴');
};
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>Game Registration</CardTitle>
        </CardHeader>
        <CardContent>
            <!-- Success/Error Messages -->
            <div v-if="errorMessage"
                 class="mb-4 rounded-md bg-red-50 p-3 text-red-800 dark:bg-red-900/20 dark:text-red-300">
                {{ errorMessage }}
            </div>

            <div v-if="successMessage"
                 class="mb-4 rounded-md bg-green-50 p-3 text-green-800 dark:bg-green-900/20 dark:text-green-300">
                {{ successMessage }}
            </div>

            <div class="space-y-4">
                <!-- Registration Status -->
                <div>
                    <h3 class="mb-2 font-medium">Registration Status</h3>
                    <div class="rounded-lg bg-gray-50 p-3 dark:bg-gray-800">
                        <p>
                            <span class="font-medium">Current Players:</span>
                            {{ props.game.total_players_count }}
                            {{ props.game.max_players ? `/ ${props.game.max_players}` : '' }}
                        </p>
                        <p>
                            <span class="font-medium">Status:</span>
                            {{ registrationUntilText }}
                        </p>
                    </div>
                </div>

                <!-- Financial Information -->
                <div>
                    <h3 class="mb-2 font-medium">Financial Information</h3>
                    <div class="rounded-lg bg-gray-50 p-3 dark:bg-gray-800">
                        <p>
                            <span class="font-medium">Entrance Fee:</span>
                            {{ formatCurrency(props.game.entrance_fee) }}
                        </p>
                        <p>
                            <span class="font-medium">Prize Distribution:</span>
                            1st Place: {{ props.game.first_place_percent }}%,
                            2nd Place: {{ props.game.second_place_percent }}%,
                            Grand Final Fund: {{ props.game.grand_final_percent }}%
                        </p>
                        <p>
                            <span class="font-medium">Penalty Fee:</span>
                            {{ formatCurrency(props.game.penalty_fee) }} (for first eliminated players)
                        </p>
                    </div>
                </div>

                <!-- Player List -->
                <div>
                    <h3 class="mb-2 font-medium">Registered Players</h3>
                    <div v-if="props.game.active_players.length === 0"
                         class="rounded-lg bg-gray-50 p-3 text-center text-gray-500 dark:bg-gray-800 dark:text-gray-400">
                        No players have registered yet.
                    </div>
                    <div v-else class="divide-y rounded-lg bg-gray-50 dark:bg-gray-800">
                        <div
                            v-for="player in props.game.active_players"
                            :key="player.id"
                            class="flex items-center justify-between p-3"
                        >
                            <div class="font-medium">
                                {{ player.user.firstname }} {{ player.user.lastname }}
                                <span v-if="player.user.id === user?.id" class="ml-1 text-xs text-blue-600">(You)</span>
                            </div>
                            <div class="text-sm text-gray-500">
                                Joined: {{ new Date(player.joined_at).toLocaleString() }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="mt-6 flex justify-end space-x-3">
                    <Button
                        v-if="canJoin"
                        :disabled="isLoading"
                        variant="default"
                        @click="handleJoin"
                    >
                        <Spinner v-if="isLoading" class="mr-2 h-4 w-4"/>
                        Join Game
                    </Button>

                    <Button
                        v-if="isUserInGame"
                        :disabled="isLoading"
                        variant="outline"
                        @click="handleLeave"
                    >
                        <Spinner v-if="isLoading" class="mr-2 h-4 w-4"/>
                        Leave Game
                    </Button>

                    <Button
                        v-if="canStart"
                        :disabled="isLoading"
                        variant="default"
                        @click="handleStart"
                    >
                        <Spinner v-if="isLoading" class="mr-2 h-4 w-4"/>
                        Start Game
                    </Button>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
