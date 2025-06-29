<script lang="ts" setup>
import {Button, Card, CardContent, CardHeader, CardTitle} from '@/Components/ui';
import DataTable from '@/Components/ui/data-table/DataTable.vue';
import {useAuth} from '@/composables/useAuth';
import {useLeagues} from '@/composables/useLeagues';
import {useLeagueStatus} from '@/composables/useLeagueStatus';
import {useSeo} from '@/composables/useSeo';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import type {ApiError, League} from '@/types/api';
import {Head, Link, router} from '@inertiajs/vue3';
import {CalendarIcon, GamepadIcon, PlusIcon, TrophyIcon, UsersIcon} from 'lucide-vue-next';
import {computed, onMounted, onUnmounted, ref} from 'vue';
import {useLocale} from '@/composables/useLocale';

const {t} = useLocale();
const {setSeoMeta, generateBreadcrumbJsonLd} = useSeo();

defineOptions({layout: AuthenticatedLayout});

const {isAdmin, isAuthenticated} = useAuth();
const leagues = useLeagues();
const {getLeagueStatus, getPlayersText} = useLeagueStatus();

const leaguesData = ref<League[]>([]);
const isLoading = ref(false);
const loadingError = ref<ApiError | null>(null);
const selectedStatus = ref<string>('all');

const statusOptions = [
    {value: 'all', label: t('All Leagues')},
    {value: 'active', label: t('Active')},
    {value: 'upcoming', label: t('Upcoming')},
    {value: 'ended', label: t('Ended')}
];

// Sort leagues by status and filter
const filteredAndSortedLeagues = computed(() => {
    if (!leaguesData.value) return [];

    let filtered = [...leaguesData.value];

    if (selectedStatus.value !== 'all') {
        filtered = filtered.filter(league => {
            const status = getLeagueStatus(league);
            return status?.text.toLowerCase() === selectedStatus.value;
        });
    }

    return filtered.sort((a, b) => {
        const statusA = getLeagueStatus(a);
        const statusB = getLeagueStatus(b);

        if (statusA?.text === 'Active' && statusB?.text !== 'Active') return -1;
        if (statusB?.text === 'Active' && statusA?.text !== 'Active') return 1;
        if (statusA?.text === 'Upcoming' && statusB?.text !== 'Upcoming') return -1;
        if (statusB?.text === 'Upcoming' && statusA?.text !== 'Upcoming') return 1;

        return a.name.localeCompare(b.name);
    });
});

// Define table columns (removed actions column)
const columns = computed(() => [
    {
        key: 'name',
        label: t('League'),
        align: 'left' as const,
        render: (league: League) => ({
            name: league.name || t('Unnamed League'),
            details: league.details,
        })
    },
    {
        key: 'game',
        label: t('Game'),
        hideOnMobile: true,
        render: (league: League) => league.game || t('N/A')
    },
    {
        key: 'status',
        label: t('Status'),
        align: 'center' as const,
        render: (league: League) => getLeagueStatus(league)
    },
    {
        key: 'players',
        label: t('Players'),
        align: 'center' as const,
        hideOnMobile: true,
        render: (league: League) => getPlayersText(league)
    },
    {
        key: 'matches',
        label: t('Matches'),
        align: 'center' as const,
        hideOnTablet: true,
        render: (league: League) => league.matches_count || 0
    },
    {
        key: 'startDate',
        label: t('Start Date'),
        hideOnTablet: true,
        render: (league: League) => league.started_at
    }
]);

const fetchLeagues = async () => {
    isLoading.value = true;
    loadingError.value = null;

    try {
        const {data, execute} = leagues.fetchLeagues();
        await execute();
        leaguesData.value = data.value || [];
    } catch (err) {
        loadingError.value = err as ApiError;
    } finally {
        isLoading.value = false;
    }
};

const formatDate = (dateString: string | null | undefined): string => {
    if (!dateString) return t('N/A');
    return new Date(dateString).toLocaleDateString();
};

const getRowClass = (): string => {
    return 'cursor-pointer transition-colors duration-200';
};

// Event delegation handler
const handleTableClick = (event: MouseEvent) => {
    const target = event.target as HTMLElement;
    const row = target.closest('tr[data-league-id]');

    if (row) {
        const leagueId = row.getAttribute('data-league-id');
        if (leagueId) {
            router.visit(`/leagues/${leagueId}`);
        }
    }
};

const handleTableKeydown = (event: KeyboardEvent) => {
    if (event.key === 'Enter' || event.key === ' ') {
        const target = event.target as HTMLElement;
        const row = target.closest('tr[data-league-id]');

        if (row) {
            event.preventDefault();
            const leagueId = row.getAttribute('data-league-id');
            if (leagueId) {
                router.visit(`/leagues/${leagueId}`);
            }
        }
    }
};

onMounted(() => {
    setSeoMeta({
        title: t('Billiard Leagues - Join Competitive Pool Leagues'),
        description: t('Browse and join competitive billiard leagues. Find leagues matching your skill level, track standings, challenge players, and improve your ELO rating.'),
        keywords: ['billiard leagues', 'pool leagues', 'competitive billiards', 'league standings', 'ELO rating', 'billiard competition'],
        ogType: 'website',
        jsonLd: {
            ...generateBreadcrumbJsonLd([
                {name: t('Home'), url: window.location.origin},
                {name: t('Leagues'), url: `${window.location.origin}/leagues`}
            ]),
            "@context": "https://schema.org",
            "@type": "SportsActivityLocation",
            "name": t('WinnerBreak Billiard Leagues'),
            "description": t('Competitive billiard leagues for all skill levels'),
            "sport": "Billiards"
        }
    });

    fetchLeagues();

    // Add event delegation to the table container
    const tableContainer = document.querySelector('[data-league-table]');
    if (tableContainer) {
        tableContainer.addEventListener('click', handleTableClick);
        tableContainer.addEventListener('keydown', handleTableKeydown);
    }
});

onUnmounted(() => {
    // Clean up event listeners
    const tableContainer = document.querySelector('[data-league-table]');
    if (tableContainer) {
        tableContainer.removeEventListener('click', handleTableClick);
        tableContainer.removeEventListener('keydown', handleTableKeydown);
    }
});
</script>

<template>
    <Head :title="t('Billiard Leagues - Join Competitive Pool Leagues')"/>
    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <!-- Header -->
            <header class="mb-6 flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">{{
                            t('Available Leagues')
                        }}</h1>
                    <p class="text-gray-600 dark:text-gray-400">{{
                            t('Manage and participate in competitive leagues')
                        }}</p>
                </div>
                <!-- Only show create button to authenticated admins -->
                <Link v-if="isAuthenticated && isAdmin" :href="route('leagues.create')"
                      aria-label="Create new billiard league">
                    <Button>
                        <PlusIcon class="mr-2 h-4 w-4" aria-hidden="true"/>
                        {{ t('Create New League') }}
                    </Button>
                </Link>
            </header>

            <!-- Filters -->
            <nav class="mb-6 flex flex-wrap gap-2" role="navigation" aria-label="League status filter">
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
                            <TrophyIcon class="h-5 w-5" aria-hidden="true"/>
                            {{ t('Leagues Directory') }}
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="p-0">
                        <div data-league-table>
                            <DataTable
                                :columns="columns"
                                :compact-mode="true"
                                :data="filteredAndSortedLeagues"
                                :empty-message="selectedStatus === 'all' ? t('No leagues have been created yet.') : t('No :status leagues available.', {status: selectedStatus})"
                                :loading="isLoading"
                                :row-class="getRowClass"
                                :row-attributes="(league) => ({
                                    'data-league-id': league.id?.toString(),
                                    'role': 'button',
                                    'tabindex': '0',
                                    'aria-label': `View ${league.name} league details`
                                })"
                            >
                                <!-- Custom cell renderers -->
                                <template #cell-name="{ value }">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-8 w-8">
                                            <div
                                                class="h-8 w-8 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                                                <TrophyIcon class="h-4 w-4 text-blue-600 dark:text-blue-400"
                                                            aria-hidden="true"/>
                                            </div>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                {{ value.name }}
                                            </div>
                                            <div v-if="value.details"
                                                 class="text-sm text-gray-500 dark:text-gray-400 truncate max-w-xs">
                                                {{ value.details }}
                                            </div>
                                        </div>
                                    </div>
                                </template>

                                <template #cell-game="{ value }">
                                    <div class="flex items-center text-sm text-gray-900 dark:text-gray-100">
                                        <GamepadIcon class="h-4 w-4 mr-2 text-gray-400" aria-hidden="true"/>
                                        {{ value }}
                                    </div>
                                </template>

                                <template #cell-status="{ value }">
                                    <span
                                        v-if="value"
                                        :class="[
                                            'inline-flex px-2 py-1 text-xs font-medium rounded-full',
                                            value.class
                                        ]"
                                    >
                                        {{ value.text }}
                                    </span>
                                    <span v-else class="text-sm text-gray-500 dark:text-gray-400">{{
                                            t('Unknown')
                                        }}</span>
                                </template>

                                <template #cell-players="{ value }">
                                    <div class="flex items-center text-sm text-gray-900 dark:text-gray-100">
                                        <UsersIcon class="h-4 w-4 mr-2 text-gray-400" aria-hidden="true"/>
                                        {{ value }}
                                    </div>
                                </template>

                                <template #cell-matches="{ value }">
                                    <div class="text-sm text-gray-900 dark:text-gray-100">
                                        {{ value }} {{ t('Matches') }}
                                    </div>
                                </template>

                                <template #cell-startDate="{ value }">
                                    <div class="flex items-center text-sm text-gray-500 dark:text-gray-400">
                                        <CalendarIcon class="h-4 w-4 mr-2" aria-hidden="true"/>
                                        <time :datetime="value">{{ formatDate(value) }}</time>
                                    </div>
                                </template>
                            </DataTable>
                        </div>
                    </CardContent>
                </Card>
            </main>
        </div>
    </div>
</template>
