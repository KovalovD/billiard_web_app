// resources/js/Components/GameRegistry.vue
<script lang="ts" setup>
import {Button, Card, CardContent, CardHeader, CardTitle} from '@/Components/ui';
import {useAuth} from '@/composables/useAuth';
import {useMultiplayerGames} from '@/composables/useMultiplayerGames';
import type {MultiplayerGame} from '@/types/api';
import {Link} from '@inertiajs/vue3';
import {LogInIcon, UserPlusIcon, UsersIcon} from 'lucide-vue-next';
import {computed} from 'vue';

interface Props {
    game: MultiplayerGame;
    leagueId: number | string;
}

const props = defineProps<Props>();
const emit = defineEmits(['updated']);

const {user, isAuthenticated} = useAuth();
const {joinMultiplayerGame, leaveMultiplayerGame, isLoading} = useMultiplayerGames();

// Check if current user is registered (authenticated users only)
const isUserRegistered = computed(() => {
    if (!isAuthenticated.value || !user.value) return false;
    return props.game.active_players.some(player => player.user.id === user.value!.id);
});

// Format currency
const formatCurrency = (amount: number): string => {
    return amount.toLocaleString('uk-UA', {
        style: 'currency',
        currency: 'UAH'
    }).replace('UAH', '₴');
};

// Format date
const formatDateTime = (dateString: string | null): string => {
    if (!dateString) return 'No deadline set';
    return new Date(dateString).toLocaleString('uk-UA', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

// Calculate total prize pool
const totalPrizePool = computed(() => {
    return props.game.entrance_fee * props.game.total_players_count;
});

// Check if registration is still open
const canRegister = computed(() => {
    if (!isAuthenticated.value) return false;
    return props.game.is_registration_open && !isUserRegistered.value;
});

// Handle join game (authenticated users only)
const handleJoin = async () => {
    if (!isAuthenticated.value) return;

    try {
        await joinMultiplayerGame(props.leagueId, props.game.id);
        emit('updated');
// eslint-disable-next-line
    } catch (err) {
        // Error is handled by the composable
    }
};

// Handle leave game (authenticated users only)
const handleLeave = async () => {
    if (!isAuthenticated.value) return;

    try {
        await leaveMultiplayerGame(props.leagueId, props.game.id);
        emit('updated');
// eslint-disable-next-line
    } catch (err) {
        // Error is handled by the composable
    }
};
</script>

<template>
    <div class="space-y-6">
        <!-- Game Info Card -->
        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <UsersIcon class="h-5 w-5"/>
                    Game Registration
                </CardTitle>
            </CardHeader>
            <CardContent>
                <div class="space-y-4">
                    <!-- Registration Status -->
                    <div class="rounded-lg bg-blue-50 p-4 dark:bg-blue-900/20">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="font-medium text-blue-800 dark:text-blue-300">Registration Open</h3>
                                <p class="text-sm text-blue-600 dark:text-blue-400">
                                    {{ game.total_players_count }}
                                    {{ game.total_players_count === 1 ? 'player' : 'players' }} registered
                                    {{ game.max_players ? ` (${game.max_players} max)` : '' }}
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm text-blue-600 dark:text-blue-400">Deadline:</p>
                                <p class="font-medium text-blue-800 dark:text-blue-300">
                                    {{ formatDateTime(game.registration_ends_at) }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Game Details -->
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="rounded-lg bg-gray-50 p-3 dark:bg-gray-800">
                            <h4 class="font-medium text-gray-900 dark:text-gray-100">Entry Fee</h4>
                            <p class="text-lg font-bold text-green-600 dark:text-green-400">
                                {{ formatCurrency(game.entrance_fee) }}
                            </p>
                        </div>
                        <div class="rounded-lg bg-gray-50 p-3 dark:bg-gray-800">
                            <h4 class="font-medium text-gray-900 dark:text-gray-100">Total Prize Pool</h4>
                            <p class="text-lg font-bold text-yellow-600 dark:text-yellow-400">
                                {{ formatCurrency(totalPrizePool) }}
                            </p>
                        </div>
                        <div class="rounded-lg bg-gray-50 p-3 dark:bg-gray-800">
                            <h4 class="font-medium text-gray-900 dark:text-gray-100">Starting Lives</h4>
                            <p class="text-lg font-bold">{{ game.initial_lives }}</p>
                        </div>
                        <div class="rounded-lg bg-gray-50 p-3 dark:bg-gray-800">
                            <h4 class="font-medium text-gray-900 dark:text-gray-100">Player Targeting</h4>
                            <p class="text-sm">
                                {{ game.allow_player_targeting ? 'Allowed' : 'Moderator Only' }}
                            </p>
                        </div>
                    </div>

                    <!-- Prize Distribution -->
                    <div class="rounded-lg bg-gray-50 p-4 dark:bg-gray-800">
                        <h4 class="mb-3 font-medium text-gray-900 dark:text-gray-100">Prize Distribution</h4>
                        <div class="grid grid-cols-1 gap-2 sm:grid-cols-3">
                            <div class="text-center">
                                <p class="text-sm text-gray-600 dark:text-gray-400">1st Place</p>
                                <p class="font-bold text-yellow-600 dark:text-yellow-400">{{
                                        game.first_place_percent
                                    }}%</p>
                            </div>
                            <div class="text-center">
                                <p class="text-sm text-gray-600 dark:text-gray-400">2nd Place</p>
                                <p class="font-bold text-gray-600 dark:text-gray-400">{{
                                        game.second_place_percent
                                    }}%</p>
                            </div>
                            <div class="text-center">
                                <p class="text-sm text-gray-600 dark:text-gray-400">Grand Final Fund</p>
                                <p class="font-bold text-blue-600 dark:text-blue-400">{{
                                        game.grand_final_percent
                                    }}%</p>
                            </div>
                        </div>
                    </div>

                    <!-- Registration Actions -->
                    <div class="flex justify-center">
                        <template v-if="isAuthenticated">
                            <template v-if="!isUserRegistered">
                                <Button
                                    v-if="canRegister"
                                    :disabled="isLoading"
                                    size="lg"
                                    @click="handleJoin"
                                >
                                    <UserPlusIcon class="mr-2 h-4 w-4"/>
                                    {{ isLoading ? 'Joining...' : 'Join Game' }}
                                </Button>
                                <div v-else class="text-center text-gray-500 dark:text-gray-400">
                                    <p>Registration is closed for this game.</p>
                                </div>
                            </template>
                            <template v-else>
                                <div class="text-center">
                                    <div class="mb-3 rounded-lg bg-green-50 p-3 dark:bg-green-900/20">
                                        <p class="font-medium text-green-800 dark:text-green-300">
                                            ✓ You are registered for this game!
                                        </p>
                                    </div>
                                    <Button
                                        :disabled="isLoading"
                                        variant="outline"
                                        @click="handleLeave"
                                    >
                                        {{ isLoading ? 'Leaving...' : 'Leave Game' }}
                                    </Button>
                                </div>
                            </template>
                        </template>
                        <template v-else>
                            <div class="text-center">
                                <div class="mb-4 rounded-lg bg-yellow-50 p-4 dark:bg-yellow-900/20">
                                    <p class="text-yellow-800 dark:text-yellow-300">
                                        You need to be logged in to join this game.
                                    </p>
                                </div>
                                <Link :href="route('login')">
                                    <Button size="lg">
                                        <LogInIcon class="mr-2 h-4 w-4"/>
                                        Login to Join
                                    </Button>
                                </Link>
                            </div>
                        </template>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Registered Players List -->
        <Card>
            <CardHeader>
                <CardTitle>Registered Players ({{ game.total_players_count }})</CardTitle>
            </CardHeader>
            <CardContent>
                <div v-if="game.total_players_count === 0" class="py-8 text-center text-gray-500 dark:text-gray-400">
                    No players registered yet. Be the first to join!
                </div>
                <div v-else class="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-3">
                    <div
                        v-for="player in game.active_players"
                        :key="player.id"
                        :class="[
                            'flex items-center justify-between rounded-lg border p-3',
                            isAuthenticated && player.user.id === user?.id
                                ? 'border-blue-200 bg-blue-50 dark:border-blue-800 dark:bg-blue-900/20'
                                : 'border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-800/50'
                        ]"
                    >
                        <div>
                            <p class="font-medium">{{ player.user.firstname }} {{ player.user.lastname }}</p>
                            <p v-if="isAuthenticated && player.user.id === user?.id"
                               class="text-xs text-blue-600 dark:text-blue-400">
                                (You)
                            </p>
                        </div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">
                            Joined: {{ new Date(player.joined_at).toLocaleDateString() }}
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
