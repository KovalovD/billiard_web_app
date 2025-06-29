<script lang="ts" setup>
import CreateMultiplayerGameModal from '@/Components/CreateMultiplayerGameModal.vue';
import {Button, Card, CardContent, CardHeader, CardTitle} from '@/Components/ui';
import DataTable from '@/Components/ui/data-table/DataTable.vue';
import TableActions, {type ActionItem} from '@/Components/TableActions.vue';
import {useAuth} from '@/composables/useAuth';
import {useMultiplayerGames} from '@/composables/useMultiplayerGames';
import {useSeo} from '@/composables/useSeo';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import {apiClient} from '@/lib/apiClient';
import type {League, MultiplayerGame} from '@/types/api';
import {Head, Link, router} from '@inertiajs/vue3';
import {
    ArrowLeftIcon,
    CalendarIcon,
    GamepadIcon,
    PlayIcon,
    PlusIcon,
    SettingsIcon,
    UsersIcon,
    XIcon
} from 'lucide-vue-next';
import {computed, onMounted, onUnmounted, ref} from 'vue';
import {useLocale} from '@/composables/useLocale';

defineOptions({layout: AuthenticatedLayout});

const props = defineProps<{
    leagueId: string | number;
}>();

const {isAdmin, isAuthenticated} = useAuth();
const {t} = useLocale();
const {setSeoMeta, generateBreadcrumbJsonLd} = useSeo();
const {getMultiplayerGames, error, startMultiplayerGame, cancelMultiplayerGame} = useMultiplayerGames();

const league = ref<League | null>(null);
const multiplayerGames = ref<MultiplayerGame[]>([]);
const isLoadingLeague = ref(true);
const isLoadingGames = ref(true);
const showCreateModal = ref(false);
const selectedStatus = ref<string>('all');

const statusOptions = [
    {value: 'all', label: t('All Games')},
    {value: 'registration', label: t('Registration')},
    {value: 'in_progress', label: t('In Progress')},
    {value: 'completed', label: t('Completed')},
    {value: 'cancelled', label: t('Cancelled')}
];

const filteredGames = computed(() => {
    if (selectedStatus.value === 'all') {
        return multiplayerGames.value;
    }
    return multiplayerGames.value.filter(game => game.status === selectedStatus.value);
});

// Define table columns
const columns = computed(() => [
    {
        key: 'name',
        label: t('Game'),
        align: 'left' as const,
        render: (game: MultiplayerGame) => ({
            name: game.name,
            id: game.id
        })
    },
    {
        key: 'status',
        label: t('Status'),
        align: 'center' as const,
        render: (game: MultiplayerGame) => game.status
    },
    {
        key: 'players',
        label: t('Players'),
        align: 'center' as const,
        hideOnMobile: true,
        render: (game: MultiplayerGame) => game.total_players_count
    },
    {
        key: 'date',
        label: t('Date Info'),
        hideOnMobile: true,
        render: (game: MultiplayerGame) => getGameDateInfo(game)
    },
    {
        key: 'entryFee',
        label: t('Entry Fee'),
        align: 'right' as const,
        hideOnTablet: true,
        render: (game: MultiplayerGame) => formatPrizePool(game.entrance_fee || 0)
    },
    {
        key: 'registration',
        label: t('Registration'),
        align: 'center' as const,
        hideOnTablet: true,
        render: (game: MultiplayerGame) => game.is_registration_open
    },
    {
        key: 'actions',
        label: t('Actions'),
        align: 'right' as const,
        sticky: true,
        width: '80px'
    }
]);

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
    if (amount <= 0) return t('N/A');
    return amount.toLocaleString('uk-UA', {
        style: 'currency',
        currency: 'UAH'
    }).replace('UAH', '₴');
};

const getGameDateInfo = (game: MultiplayerGame): string => {
    if (game.completed_at) {
        return `${t('Completed:')} ${formatDateTime(game.completed_at)}`;
    } else if (game.started_at) {
        return `${t('Started:')} ${formatDateTime(game.started_at)}`;
    } else if (game.registration_ends_at) {
        return `${t('Reg. ends:')} ${formatDateTime(game.registration_ends_at)}`;
    } else {
        return `${t('Created:')} ${formatDate(game.created_at)}`;
    }
};

const handleStart = async (game: MultiplayerGame) => {
    if (!isAuthenticated.value || !isAdmin.value) return;

    try {
        await startMultiplayerGame(props.leagueId, game.id);
        await fetchGames();
        // eslint-disable-next-line
    } catch (err) {
        // Error is handled by the composable
    }
};

const handleCancel = async (game: MultiplayerGame) => {
    if (!isAuthenticated.value || !isAdmin.value) return;

    if (!confirm(t('Are you sure you want to cancel this game?'))) {
        return;
    }

    try {
        await cancelMultiplayerGame(props.leagueId, game.id);
        await fetchGames();
        // eslint-disable-next-line
    } catch (err) {
        // Error is handled by the composable
    }
};

const getRowClass = (): string => {
    return 'cursor-pointer transition-colors duration-200';
};

const getActions = (game: MultiplayerGame): ActionItem[] => {
    const actions: ActionItem[] = [];

    if (isAuthenticated.value && isAdmin.value) {
        if (game.status === 'registration') {
            actions.push({
                label: t('Start Game'),
                icon: PlayIcon,
                action: () => handleStart(game),
                show: game.total_players_count >= 2
            });
            actions.push({
                label: t('Manage'),
                icon: SettingsIcon,
                href: `/leagues/${props.leagueId}/multiplayer-games/${game.id}`,
                show: true
            });
            actions.push({
                label: t('Cancel'),
                icon: XIcon,
                action: () => handleCancel(game),
                variant: 'destructive',
                show: true
            });
        } else if (game.status === 'in_progress') {
            actions.push({
                label: t('Manage'),
                icon: SettingsIcon,
                href: `/leagues/${props.leagueId}/multiplayer-games/${game.id}`,
                show: true
            });
        }
    }

    return actions;
};

// Event delegation handler
const handleTableClick = (event: MouseEvent) => {
    const target = event.target as HTMLElement;

    // Don't navigate if clicking on action buttons or their children
    if (target.closest('[data-table-actions]') || target.closest('button')) {
        return;
    }

    const row = target.closest('tr[data-game-id]');

    if (row) {
        const gameId = row.getAttribute('data-game-id');
        if (gameId) {
            router.visit(`/leagues/${props.leagueId}/multiplayer-games/${gameId}`);
        }
    }
};

const handleTableKeydown = (event: KeyboardEvent) => {
    if (event.key === 'Enter' || event.key === ' ') {
        const target = event.target as HTMLElement;

        // Don't navigate if focus is on action buttons
        if (target.closest('[data-table-actions]') || target.closest('button')) {
            return;
        }

        const row = target.closest('tr[data-game-id]');

        if (row) {
            event.preventDefault();
            const gameId = row.getAttribute('data-game-id');
            if (gameId) {
                router.visit(`/leagues/${props.leagueId}/multiplayer-games/${gameId}`);
            }
        }
    }
};

onMounted(() => {
    fetchLeague();
    fetchGames();

    // Set SEO meta after league data is loaded
    const setSeoAfterLoad = () => {
        if (league.value) {
            setSeoMeta({
                title: t('Multiplayer Games - :league', {league: league.value.name}),
                description: t('Join multiplayer billiard games in :league. Compete in elimination games with prize pools and advance through the brackets.', {league: league.value.name}),
                keywords: ['multiplayer billiards', 'elimination games', 'prize pool', 'league games', league.value.name],
                ogType: 'website',
                jsonLd: {
                    ...generateBreadcrumbJsonLd([
                        {name: t('Home'), url: window.location.origin},
                        {name: t('Leagues'), url: `${window.location.origin}/leagues`},
                        {name: league.value.name, url: `${window.location.origin}/leagues/${props.leagueId}`},
                        {
                            name: t('Multiplayer Games'),
                            url: `${window.location.origin}/leagues/${props.leagueId}/multiplayer-games`
                        }
                    ])
                }
            });
        }
    };

    // Watch for league data to be loaded
    const unwatch = setInterval(() => {
        if (!isLoadingLeague.value && league.value) {
            setSeoAfterLoad();
            clearInterval(unwatch);
        }
    }, 100);

    // Add event delegation to the table container
    const tableContainer = document.querySelector('[data-multiplayer-games-table]');
    if (tableContainer) {
        tableContainer.addEventListener('click', handleTableClick);
        tableContainer.addEventListener('keydown', handleTableKeydown);
    }
});

onUnmounted(() => {
    // Clean up event listeners
    const tableContainer = document.querySelector('[data-multiplayer-games-table]');
    if (tableContainer) {
        tableContainer.removeEventListener('click', handleTableClick);
        tableContainer.removeEventListener('keydown', handleTableKeydown);
    }
});
</script>

<template>
    <Head :title="league ? t('Multiplayer Games - :league', { league: league.name }) : t('Multiplayer Games')"/>
    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <!-- Header with back button -->
            <header class="mb-6 flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Link :href="`/leagues/${leagueId}`" aria-label="Navigate back to league page">
                        <Button variant="outline">
                            <ArrowLeftIcon class="mr-2 h-4 w-4" aria-hidden="true"/>
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
                <Button v-if="isAuthenticated && isAdmin && league?.game_multiplayer" @click="openCreateModal"
                        aria-label="Create new multiplayer game">
                    <PlusIcon class="mr-2 h-4 w-4" aria-hidden="true"/>
                    {{ t('Create Game') }}
                </Button>
            </header>

            <!-- Error message -->
            <div v-if="error" class="mb-6 rounded bg-red-100 p-4 text-red-600 dark:bg-red-900/30 dark:text-red-400"
                 role="alert" aria-live="polite">
                {{ error }}
            </div>

            <!-- Filters -->
            <nav class="mb-6 flex flex-wrap gap-2" role="navigation" aria-label="Game status filter">
                <button
                    v-for="option in statusOptions"
                    :key="option.value"
                    :class="[
                        'px-4 py-2 rounded-md text-sm font-medium transition-colors',
                        selectedStatus === option.value
                            ? 'bg-blue-600 text-white'
                            : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700'
                    ]"
                    :aria-pressed="selectedStatus === option.value"
                    @click="selectedStatus = option.value"
                >
                    {{ option.label }}
                </button>
            </nav>

            <main>
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <GamepadIcon class="h-5 w-5" aria-hidden="true"/>
                            {{ t('Multiplayer Games') }}
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="p-0">
                        <!-- No multiplayer support message -->
                        <div v-if="!isLoadingGames && league && !league.game_multiplayer"
                             class="py-10 text-center text-gray-500 dark:text-gray-400">
                            <GamepadIcon class="mx-auto h-12 w-12 mb-4 opacity-50" aria-hidden="true"/>
                            <p class="text-lg">{{ t('Multiplayer Not Supported') }}</p>
                            <p class="text-sm">{{ t('This league does not support multiplayer games.') }}</p>
                        </div>

                        <!-- Data Table -->
                        <div v-else data-multiplayer-games-table>
                            <DataTable
                                :columns="columns"
                                :compact-mode="true"
                                :data="filteredGames"
                                :empty-message="selectedStatus === 'all' ? t('No multiplayer games for this league.') : t('No :status games.', { status: selectedStatus })"
                                :loading="isLoadingGames"
                                :row-class="getRowClass"
                                :row-attributes="(game) => ({
                                    'data-game-id': game.id?.toString(),
                                    'role': 'button',
                                    'tabindex': '0',
                                    'aria-label': `View ${game.name} game details`
                                })"
                            >
                                <!-- Custom cell renderers -->
                                <template #cell-name="{ value }">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-8 w-8">
                                            <div
                                                class="h-8 w-8 rounded-full bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center">
                                                <GamepadIcon class="h-4 w-4 text-purple-600 dark:text-purple-400"
                                                             aria-hidden="true"/>
                                            </div>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                {{ value.name }}
                                            </div>
                                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                                ID: {{ value.id }}
                                            </div>
                                        </div>
                                    </div>
                                </template>

                                <template #cell-status="{ value }">
                                    <span
                                        :class="[
                                            'inline-flex px-2 py-1 text-xs font-medium rounded-full',
                                            getStatusBadgeClass(value)
                                        ]"
                                    >
                                        {{ value.replace('_', ' ').toUpperCase() }}
                                    </span>
                                </template>

                                <template #cell-players="{ value }">
                                    <div class="flex items-center text-sm text-gray-900 dark:text-gray-100">
                                        <UsersIcon class="h-4 w-4 mr-2 text-gray-400" aria-hidden="true"/>
                                        <div>{{ value }}</div>
                                    </div>
                                </template>

                                <template #cell-date="{ value }">
                                    <div class="flex items-center text-sm text-gray-500 dark:text-gray-400">
                                        <CalendarIcon class="h-4 w-4 mr-2" aria-hidden="true"/>
                                        <div class="text-xs">{{ value }}</div>
                                    </div>
                                </template>

                                <template #cell-entryFee="{ value }">
                                    <span class="text-green-600 dark:text-green-400 font-medium">
                                        {{ value }}
                                    </span>
                                </template>

                                <template #cell-registration="{ value }">
                                    <div v-if="value" class="flex items-center">
                                        <div class="h-2 w-2 bg-green-400 rounded-full mr-2" aria-hidden="true"></div>
                                        <span class="text-sm text-green-600 dark:text-green-400">{{ t('Open') }}</span>
                                    </div>
                                    <div v-else class="flex items-center">
                                        <div class="h-2 w-2 bg-red-400 rounded-full mr-2" aria-hidden="true"></div>
                                        <span class="text-sm text-red-600 dark:text-red-400">{{ t('Closed') }}</span>
                                    </div>
                                </template>

                                <template #cell-actions="{ item }">
                                    <div data-table-actions>
                                        <TableActions :actions="getActions(item)"/>
                                    </div>
                                </template>
                            </DataTable>
                        </div>
                    </CardContent>
                </Card>
            </main>
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
