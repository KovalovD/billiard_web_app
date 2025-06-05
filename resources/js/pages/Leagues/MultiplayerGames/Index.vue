// resources/js/pages/Leagues/MultiplayerGames/Index.vue
<script lang="ts" setup>
import CreateMultiplayerGameModal from '@/Components/CreateMultiplayerGameModal.vue';
import {Button, Card, CardContent, CardHeader, CardTitle, Spinner} from '@/Components/ui';
import {useAuth} from '@/composables/useAuth';
import {useMultiplayerGames} from '@/composables/useMultiplayerGames';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import {apiClient} from '@/lib/apiClient';
import type {League, MultiplayerGame} from '@/types/api';
import {Head, Link} from '@inertiajs/vue3';
import {
    ArrowLeftIcon,
    CalendarIcon,
    EyeIcon,
    GamepadIcon,
    LogInIcon,
    PlayIcon,
    PlusIcon,
    SettingsIcon,
    UsersIcon,
    XIcon
} from 'lucide-vue-next';
import {computed, onMounted, ref} from 'vue';
import {useLocale} from '@/composables/useLocale';

defineOptions({layout: AuthenticatedLayout});

const props = defineProps<{
    leagueId: string | number;
}>();

const {isAdmin, isAuthenticated} = useAuth();
const { t } = useLocale();
const {getMultiplayerGames, error, startMultiplayerGame} = useMultiplayerGames();

const league = ref<League | null>(null);
const multiplayerGames = ref<MultiplayerGame[]>([]);
const isLoadingLeague = ref(true);
const isLoadingGames = ref(true);
const showCreateModal = ref(false);
const selectedStatus = ref<string>('all');

const statusOptions = [
    {value: 'all', label: 'All Games'},
    {value: 'registration', label: 'Registration'},
    {value: 'in_progress', label: 'In Progress'},
    {value: 'completed', label: 'Completed'},
    {value: 'cancelled', label: 'Cancelled'}
];

const filteredGames = computed(() => {
    if (selectedStatus.value === 'all') {
        return multiplayerGames.value;
    }
    return multiplayerGames.value.filter(game => game.status === selectedStatus.value);
});

// Load league and games
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

const fetchGames = async () => {
    isLoadingGames.value = true;
    try {
        multiplayerGames.value = await getMultiplayerGames(props.leagueId);
    } catch (err) {
        console.error('Failed to fetch multiplayer games:', err);
    } finally {
        isLoadingGames.value = false;
    }
};

const handleGameCreated = () => {
    fetchGames();
};

const openCreateModal = () => {
    showCreateModal.value = true;
};

const getStatusBadgeClass = (status: string): string => {
    switch (status) {
        case 'registration':
            return 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300';
        case 'in_progress':
            return 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300';
        case 'completed':
            return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
        case 'cancelled':
            return 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300';
        default:
            return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
    }
};

const formatDate = (dateString: string | null): string => {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleDateString();
};

const formatDateTime = (dateString: string | null): string => {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleString('uk-UA', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const formatPrizePool = (amount: number): string => {
    if (amount <= 0) return 'N/A';
    return amount.toLocaleString('uk-UA', {
        style: 'currency',
        currency: 'UAH'
    }).replace('UAH', '₴');
};

const getPlayersText = (game: MultiplayerGame): number => {
    return game.total_players_count;
};

const getGameDateInfo = (game: MultiplayerGame): string => {
    if (game.completed_at) {
        return `Completed: ${formatDateTime(game.completed_at)}`;
    } else if (game.started_at) {
        return `Started: ${formatDateTime(game.started_at)}`;
    } else if (game.registration_ends_at) {
        return `Reg. ends: ${formatDateTime(game.registration_ends_at)}`;
    } else {
        return `Created: ${formatDate(game.created_at)}`;
    }
};

const handleStart = async (game: MultiplayerGame) => {
    if (!isAuthenticated.value || !isAdmin.value) return;

    try {
        await startMultiplayerGame(props.leagueId, game.id);

        fetchGames();
        // eslint-disable-next-line
    } catch (err) {
        // Error is handled by the composable
    }
};

onMounted(() => {
    fetchLeague();
    fetchGames();
});
</script>

<template>
    <Head :title="league ? `Multiplayer Games - ${league.name}` : 'Multiplayer Games'"/>
    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <!-- Header with back button -->
            <div class="mb-6 flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Link :href="`/leagues/${leagueId}`">
                        <Button variant="outline">
                            <ArrowLeftIcon class="mr-2 h-4 w-4"/>
                            {{ t('Back to League') }}
                        </Button>
                    </Link>
                    <div>
                        <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">
                            {{ t('Multiplayer Games') }}
                        </h1>
                        <p v-if="league" class="text-gray-600 dark:text-gray-400">
                            {{ league.name }}
                        </p>
                    </div>
                </div>

                <!-- Admin create button -->
                <Button v-if="isAuthenticated && isAdmin && league?.game_multiplayer" @click="openCreateModal">
                    <PlusIcon class="mr-2 h-4 w-4"/>
                    {{ t('Create Game') }}
                </Button>
            </div>

            <!-- Error message -->
            <div v-if="error" class="mb-6 rounded bg-red-100 p-4 text-red-600 dark:bg-red-900/30 dark:text-red-400">
                {{ error }}
            </div>

            <!-- Filters -->
            <div class="mb-6 flex flex-wrap gap-2">
                <button
                    v-for="option in statusOptions"
                    :key="option.value"
                    :class="[
                        'px-4 py-2 rounded-md text-sm font-medium transition-colors',
                        selectedStatus === option.value
                            ? 'bg-blue-600 text-white'
                            : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700'
                    ]"
                    @click="selectedStatus = option.value"
                >
                    {{ option.label }}
                </button>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <GamepadIcon class="h-5 w-5"/>
                        Multiplayer Games
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <!-- Loading state -->
                    <div v-if="isLoadingGames" class="flex items-center justify-center py-10">
                        <Spinner class="text-primary h-8 w-8"/>
                        <span class="ml-2 text-gray-500 dark:text-gray-400">Loading games...</span>
                    </div>

                    <!-- No multiplayer support message -->
                    <div v-else-if="league && !league.game_multiplayer"
                         class="py-10 text-center text-gray-500 dark:text-gray-400">
                        <GamepadIcon class="mx-auto h-12 w-12 mb-4 opacity-50"/>
                        <p class="text-lg">{{ t('Multiplayer Not Supported') }}</p>
                        <p class="text-sm">{{ t('This league does not support multiplayer games.') }}</p>
                    </div>

                    <!-- Empty state -->
                    <div v-else-if="filteredGames.length === 0"
                         class="py-10 text-center text-gray-500 dark:text-gray-400">
                        <GamepadIcon class="mx-auto h-12 w-12 mb-4 opacity-50"/>
                        <p class="text-lg">{{ t('No games found') }}</p>
                        <p class="text-sm">
                            {{
                                selectedStatus === 'all' ? t('No multiplayer games for this league.') : t('No :status games.', { status: selectedStatus })
                            }}
                            <span v-if="isAuthenticated && isAdmin"> Create one to get started!</span>
                            <span v-else-if="!isAuthenticated">
                                <Link :href="route('login')" class="text-blue-600 hover:underline dark:text-blue-400">
                                    {{ t('Login to create games.') }}
                                </Link>
                            </span>
                        </p>
                    </div>

                    <!-- Games Table -->
                    <div v-else class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">
                                    Game
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">
                                    Status
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">
                                    Players
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">
                                    Date Info
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">
                                    Entry Fee
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">
                                    Registration
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">
                                    Actions
                                </th>
                            </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-900 dark:divide-gray-700">
                            <tr
                                v-for="game in filteredGames"
                                :key="game.id"
                                class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors"
                            >
                                <!-- Game Name -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-8 w-8">
                                            <div
                                                class="h-8 w-8 rounded-full bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center">
                                                <GamepadIcon class="h-4 w-4 text-purple-600 dark:text-purple-400"/>
                                            </div>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                {{ game.name }}
                                            </div>
                                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                                ID: {{ game.id }}
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Status -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                        <span
                                            :class="[
                                                'inline-flex px-2 py-1 text-xs font-medium rounded-full',
                                                getStatusBadgeClass(game.status)
                                            ]"
                                        >
                                            {{ game.status.replace('_', ' ').toUpperCase() }}
                                        </span>
                                </td>

                                <!-- Players -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center text-sm text-gray-900 dark:text-gray-100">
                                        <UsersIcon class="h-4 w-4 mr-2 text-gray-400"/>
                                        <div>
                                            {{ getPlayersText(game) }}
                                        </div>
                                    </div>
                                </td>

                                <!-- Date Info -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center text-sm text-gray-500 dark:text-gray-400">
                                        <CalendarIcon class="h-4 w-4 mr-2"/>
                                        <div class="text-xs">
                                            {{ getGameDateInfo(game) }}
                                        </div>
                                    </div>
                                </td>

                                <!-- Entry Fee -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center text-sm">
                                        <span class="text-green-600 dark:text-green-400 font-medium">
                                                {{ formatPrizePool(game.entrance_fee || 0) }}
                                            </span>
                                    </div>
                                </td>

                                <!-- Registration Status -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div v-if="game.is_registration_open" class="flex items-center">
                                        <div class="h-2 w-2 bg-green-400 rounded-full mr-2"></div>
                                        <span class="text-sm text-green-600 dark:text-green-400">Open</span>
                                    </div>
                                    <div v-else class="flex items-center">
                                        <div class="h-2 w-2 bg-red-400 rounded-full mr-2"></div>
                                        <span class="text-sm text-red-600 dark:text-red-400">Closed</span>
                                    </div>
                                </td>

                                <!-- Actions -->
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex justify-end space-x-2">
                                        <!-- Everyone can view -->
                                        <Link :href="`/leagues/${leagueId}/multiplayer-games/${game.id}`">
                                            <Button size="sm" title="View Game" variant="outline">
                                                <EyeIcon class="h-4 w-4"/>
                                            </Button>
                                        </Link>

                                        <!-- Admin only actions -->
                                        <template v-if="isAuthenticated && isAdmin">
                                            <Button
                                                v-if="game.status === 'registration'"
                                                :disabled="game.total_players_count < 2"
                                                size="sm"
                                                title="Start Game"
                                                variant="outline"
                                                @click="handleStart(game)"
                                            >
                                                <PlayIcon class="h-4 w-4"/>
                                            </Button>

                                            <Button
                                                v-if="['registration', 'in_progress'].includes(game.status)"
                                                size="sm"
                                                title="Manage Game"
                                                variant="outline"
                                            >
                                                <SettingsIcon class="h-4 w-4"/>
                                            </Button>

                                            <Button
                                                v-if="game.status === 'registration'"
                                                size="sm"
                                                title="Cancel Game"
                                                variant="destructive"
                                            >
                                                <XIcon class="h-4 w-4"/>
                                            </Button>
                                        </template>

                                        <!-- Guest login prompt for admin actions -->
                                        <div v-else-if="!isAuthenticated && game.status === 'registration'"
                                             class="text-center">
                                            <Link :href="route('login')" :title="t('Login to manage')">
                                                <Button size="sm" variant="outline">
                                                    <LogInIcon class="h-4 w-4"/>
                                                </Button>
                                            </Link>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>

    <!-- Create Game Modal - Only for authenticated admins -->
    <CreateMultiplayerGameModal
        v-if="isAuthenticated && isAdmin"
        :league-id="leagueId"
        :show="showCreateModal"
        @close="showCreateModal = false"
        @created="handleGameCreated"
    />
</template>
