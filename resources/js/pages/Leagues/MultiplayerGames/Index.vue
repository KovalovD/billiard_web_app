// resources/js/Pages/League/MultiplayerGames/Index.vue
<script lang="ts" setup>
import CreateMultiplayerGameModal from '@/Components/League/MultiplayerGame/CreateMultiplayerGameModal.vue';
import {Button, Card, CardContent, CardHeader, CardTitle} from '@/Components/ui';
import DataTable from '@/Components/ui/data-table/DataTable.vue';
import TableActions, {type ActionItem} from '@/Components/League/MultiplayerGame/TableActions.vue';
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
    {value: 'all', label: t('All')},
    {value: 'registration', label: t('Registration')},
    {value: 'in_progress', label: t('Active')},
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
        label: t('Date'),
        hideOnMobile: true,
        render: (game: MultiplayerGame) => getGameDateInfo(game)
    },
    {
        key: 'entryFee',
        label: t('Fee'),
        align: 'right' as const,
        hideOnTablet: true,
        render: (game: MultiplayerGame) => formatPrizePool(game.entrance_fee || 0)
    },
    {
        key: 'registration',
        label: t('Reg'),
        align: 'center' as const,
        hideOnTablet: true,
        render: (game: MultiplayerGame) => game.is_registration_open
    },
    {
        key: 'actions',
        label: t('Actions'),
        align: 'right' as const,
        sticky: true,
        width: '60px'
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
    return new Date(dateString).toLocaleDateString('uk-UK', {day: '2-digit', month: 'short'});
};

const formatDateTime = (dateString: string | null): string => {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleString('uk-UA', {
        day: '2-digit',
        month: '2-digit',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const formatPrizePool = (amount: number): string => {
    if (amount <= 0) return t('Free');
    return amount.toLocaleString('uk-UA', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }) + '₴';
};

const getGameDateInfo = (game: MultiplayerGame): string => {
    if (game.completed_at) {
        return formatDateTime(game.completed_at);
    } else if (game.started_at) {
        return formatDateTime(game.started_at);
    } else if (game.registration_ends_at) {
        return formatDateTime(game.registration_ends_at);
    } else {
        return formatDate(game.created_at);
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
                href: `/leagues/${league.value?.slug}/multiplayer-games/${game.slug}`,
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
                href: `/leagues/${league.value?.slug}/multiplayer-games/${game.slug}`,
                show: true
            });
        }
    }

    return actions;
};

// Event delegation handler
const handleTableClick = (event: Event) => {
    const target = event.target as HTMLElement;

    // Don't navigate if clicking on action buttons or their children
    if (target.closest('[data-table-actions]') || target.closest('button')) {
        return;
    }

    const row = target.closest('tr[data-game-slug]');

    if (row) {
        const gameId = row.getAttribute('data-game-slug');
        if (gameId) {
            router.visit(`/leagues/${league.value?.slug}/multiplayer-games/${gameId}`);
        }
    }
};

const handleTableKeydown = (event: Event) => {
    const keyboardEvent = event as KeyboardEvent;
    if (keyboardEvent.key === 'Enter' || keyboardEvent.key === ' ') {
        const target = event.target as HTMLElement;

        // Don't navigate if focus is on action buttons
        if (target.closest('[data-table-actions]') || target.closest('button')) {
            return;
        }

        const row = target.closest('tr[data-game-slug]');

        if (row) {
            event.preventDefault();
            const gameId = row.getAttribute('data-game-slug');
            if (gameId) {
                router.visit(`/leagues/${league.value?.slug}/multiplayer-games/${gameId}`);
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
                description: t('mp_game_desc', {league: league.value.name}),
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
    <div class="py-4 sm:py-6">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <!-- Header with back button - Compact -->
            <header class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <!-- Left: Title and subtitle -->
                <div class="flex-1 flex flex-col items-start justify-center">
                    <h1 class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white">
                        {{ t('Multiplayer Games') }}
                    </h1>
                    <p v-if="league" class="text-sm text-gray-600 dark:text-gray-400">
                        {{ league.name }}
                    </p>
                </div>

                <!-- Right: Back to League and Create Game buttons -->
                <div class="flex flex-col sm:flex-row items-center justify-end gap-2 flex-1">
                    <Link :href="`/leagues/${league?.slug}`" aria-label="Navigate back to league page">
                        <Button variant="outline" size="sm">
                            <ArrowLeftIcon class="mr-1.5 h-3.5 w-3.5" aria-hidden="true"/>
                            <span class="hidden sm:inline">{{ t('Back to League') }}</span>
                            <span class="sm:hidden">{{ t('Back') }}</span>
                        </Button>
                    </Link>
                    <button
                        v-if="isAuthenticated && isAdmin && league?.game_multiplayer"
                        @click="openCreateModal"
                        aria-label="Create new multiplayer game"
                        class="cursor-pointer inline-flex items-center px-3 py-1.5 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 transition-colors"
                    >
                        <PlusIcon class="mr-1.5 h-3.5 w-3.5" aria-hidden="true"/>
                        {{ t('Create Game') }}
                    </button>
                </div>
            </header>

            <!-- Error message -->
            <div v-if="error"
                 class="mb-4 rounded-md bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 p-3"
                 role="alert" aria-live="polite">
                <p class="text-sm text-red-600 dark:text-red-400">{{ error }}</p>
            </div>

            <!-- Filters - Compact -->
            <nav class="mb-4 flex flex-wrap gap-2">
                <button
                    v-for="option in statusOptions"
                    :key="option.value"
                    :class="[
                        'px-2.5 py-1.5 rounded-md text-sm font-medium transition-colors',
                        selectedStatus === option.value
                            ? 'bg-indigo-600 text-white'
                            : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700'
                    ]"
                    @click="selectedStatus = option.value"
                >
                    {{ option.label }}
                </button>
            </nav>

            <main>
                <Card class="shadow-sm">
                    <CardHeader class="bg-gradient-to-r from-gray-50 to-purple-50 dark:from-gray-800 dark:to-gray-700 p-4">
                        <CardTitle class="flex items-center gap-2 text-base">
                            <GamepadIcon class="h-4 w-4 text-purple-600 dark:text-purple-400" aria-hidden="true"/>
                            {{ t('Multiplayer Games') }}
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="p-0">
                        <!-- No multiplayer support message -->
                        <div v-if="!isLoadingGames && league && !league.game_multiplayer"
                             class="py-8 text-center text-gray-500 dark:text-gray-400">
                            <GamepadIcon class="mx-auto h-10 w-10 mb-3 opacity-50" aria-hidden="true"/>
                            <p class="text-base">{{ t('Multiplayer Not Supported') }}</p>
                            <p class="text-sm">{{ t('This league does not support multiplayer games.') }}</p>
                        </div>

                        <!-- Loading State -->
                        <div v-else-if="isLoadingGames" class="flex justify-center py-8">
                            <div class="text-center">
                                <div
                                    class="animate-spin rounded-full h-6 w-6 border-b-2 border-indigo-600 mx-auto"></div>
                                <p class="mt-2 text-sm text-gray-500">{{ t('Loading games...') }}</p>
                            </div>
                        </div>

                        <!-- Empty State -->
                        <div v-else-if="filteredGames.length === 0" class="p-8 text-center text-sm text-gray-500">
                            {{
                                selectedStatus === 'all' ? t('No multiplayer games for this league.') : t('No :status games.', {status: selectedStatus})
                            }}
                        </div>

                        <!-- Mobile Cards View - Compact -->
                        <div v-else class="block lg:hidden space-y-3 p-3">
                            <div
                                v-for="game in filteredGames"
                                :key="game.id"
                                class="relative rounded-md border p-3 cursor-pointer transition-colors bg-white hover:bg-gray-50 dark:bg-gray-800 dark:hover:bg-gray-700"
                                @click="router.visit(`/leagues/${league?.slug}/multiplayer-games/${game.slug}`)"
                            >
                                <!-- Game Header -->
                                <div class="flex items-start justify-between mb-2">
                                    <div class="flex-1 min-w-0">
                                        <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 truncate">
                                            {{ game.name }}
                                        </h3>
                                        <p class="text-xs text-gray-600 dark:text-gray-400">
                                            ID: {{ game.id }}
                                        </p>
                                    </div>
                                    <span
                                        :class="[
                                            'inline-flex px-1.5 py-0.5 text-xs font-medium rounded-full flex-shrink-0',
                                            getStatusBadgeClass(game.status)
                                        ]"
                                    >
                                        {{ game.status.replace('_', ' ').toUpperCase() }}
                                    </span>
                                </div>

                                <!-- Game Info Grid - Compact -->
                                <div class="grid grid-cols-2 gap-2 text-xs">
                                    <!-- Players & Entry Fee -->
                                    <div>
                                        <div class="flex items-center text-gray-600 dark:text-gray-400">
                                            <UsersIcon class="h-3 w-3 mr-0.5 flex-shrink-0"/>
                                            <span class="truncate">{{ game.total_players_count }} {{
                                                    t('Players')
                                                }}</span>
                                        </div>
                                        <div class="text-green-600 dark:text-green-400 font-medium mt-0.5">
                                            {{ formatPrizePool(game.entrance_fee || 0) }}
                                        </div>
                                    </div>

                                    <!-- Date & Registration -->
                                    <div>
                                        <div class="flex items-center text-gray-600 dark:text-gray-400">
                                            <CalendarIcon class="h-3 w-3 mr-0.5 flex-shrink-0"/>
                                            <span class="truncate">{{ getGameDateInfo(game) }}</span>
                                        </div>
                                        <div class="flex items-center text-gray-600 dark:text-gray-400 mt-0.5">
                                            <div v-if="game.is_registration_open" class="flex items-center">
                                                <div class="h-2 w-2 bg-green-400 rounded-full mr-1"
                                                     aria-hidden="true"></div>
                                                <span class="text-xs text-green-600 dark:text-green-400">{{
                                                        t('Open')
                                                    }}</span>
                                            </div>
                                            <div v-else class="flex items-center">
                                                <div class="h-2 w-2 bg-red-400 rounded-full mr-1"
                                                     aria-hidden="true"></div>
                                                <span class="text-xs text-red-600 dark:text-red-400">{{
                                                        t('Closed')
                                                    }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Desktop Table View - Compact -->
                        <div class="hidden lg:block" data-multiplayer-games-table>
                            <DataTable
                                :columns="columns"
                                :compact-mode="true"
                                :data="filteredGames"
                                :empty-message="selectedStatus === 'all' ? t('No multiplayer games for this league.') : t('No :status games.', { status: selectedStatus })"
                                :loading="isLoadingGames"
                                :row-class="getRowClass"
                                :row-attributes="(game) => ({
                                    'data-game-slug': game.slug?.toString(),
                                    'role': 'button',
                                    'tabindex': '0',
                                    'aria-label': `View ${game.name} game details`
                                })"
                                :row-height="'compact'"
                            >
                                <!-- Custom cell renderers -->
                                <template #cell-name="{ value }">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-7 w-7">
                                            <div
                                                class="h-7 w-7 rounded-full bg-gradient-to-br from-purple-400 to-purple-600 flex items-center justify-center shadow-sm">
                                                <GamepadIcon class="h-3.5 w-3.5 text-white"
                                                             aria-hidden="true"/>
                                            </div>
                                        </div>
                                        <div class="ml-3">
                                            <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                {{ value.name }}
                                            </div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                ID: {{ value.id }}
                                            </div>
                                        </div>
                                    </div>
                                </template>

                                <template #cell-status="{ value }">
                                    <span
                                        :class="[
                                            'inline-flex px-1.5 py-0.5 text-xs font-medium rounded-full',
                                            getStatusBadgeClass(value)
                                        ]"
                                    >
                                        {{ value.replace('_', ' ').toUpperCase() }}
                                    </span>
                                </template>

                                <template #cell-players="{ value }">
                                    <div class="flex items-center justify-center text-sm text-gray-900 dark:text-gray-100">
                                        <UsersIcon class="h-3.5 w-3.5 mr-1.5 text-gray-400" aria-hidden="true"/>
                                        <div>{{ value }}</div>
                                    </div>
                                </template>

                                <template #cell-date="{ value }">
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ value }}
                                    </div>
                                </template>

                                <template #cell-entryFee="{ value }">
                                    <span class="text-sm text-green-600 dark:text-green-400 font-medium">
                                        {{ value }}
                                    </span>
                                </template>

                                <template #cell-registration="{ value }">
                                    <div v-if="value" class="flex items-center justify-center">
                                        <div class="h-2 w-2 bg-green-400 rounded-full" aria-hidden="true"></div>
                                    </div>
                                    <div v-else class="flex items-center justify-center">
                                        <div class="h-2 w-2 bg-red-400 rounded-full" aria-hidden="true"></div>
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
