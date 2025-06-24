<script lang="ts" setup>
import {Button, Card, CardContent, CardHeader, CardTitle} from '@/Components/ui';
import DataTable from '@/Components/ui/data-table/DataTable.vue';
import TableActions, {type ActionItem} from '@/Components/TableActions.vue';
import {useAuth} from '@/composables/useAuth';
import {useLeagues} from '@/composables/useLeagues';
import {useLeagueStatus} from '@/composables/useLeagueStatus';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import type {ApiError, League} from '@/types/api';
import {Head, Link} from '@inertiajs/vue3';
import {CalendarIcon, EyeIcon, GamepadIcon, PencilIcon, PlusIcon, TrophyIcon, UsersIcon} from 'lucide-vue-next';
import {computed, onMounted, ref} from 'vue';
import {useLocale} from '@/composables/useLocale';

const {t} = useLocale();

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

// Define table columns
const columns = computed(() => [
    {
        key: 'name',
        label: t('League'),
        align: 'left' as const,
        render: (league: League) => ({
            name: league.name || t('Unnamed League'),
            details: league.details
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
    },
    {
        key: 'actions',
        label: t('Actions'),
        align: 'right' as const,
        sticky: true,
        width: '80px'
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

const getLeagueUrl = (routeName: 'leagues.show.page' | 'leagues.edit', leagueId: number | undefined | null): string | null => {
    if (typeof leagueId !== 'number') return null;

    try {
        return route(routeName, {league: leagueId});
// eslint-disable-next-line
    } catch (e) {
        return null;
    }
};

const getActions = (league: League): ActionItem[] => {
    const actions: ActionItem[] = [];

    const viewUrl = getLeagueUrl('leagues.show.page', league.id);
    if (viewUrl) {
        actions.push({
            label: t('View Details'),
            icon: EyeIcon,
            href: viewUrl,
            show: true
        });
    }

    if (isAuthenticated.value && isAdmin.value) {
        const editUrl = getLeagueUrl('leagues.edit', league.id);
        if (editUrl) {
            actions.push({
                label: t('Edit League'),
                icon: PencilIcon,
                href: editUrl,
                show: true
            });
        }
    }

    return actions;
};

onMounted(() => {
    fetchLeagues();
});
</script>

<template>
    <Head :title="t('Leagues')"/>
    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">{{
                            t('Available Leagues')
                        }}</h1>
                    <p class="text-gray-600 dark:text-gray-400">{{
                            t('Manage and participate in competitive leagues')
                        }}</p>
                </div>
                <!-- Only show create button to authenticated admins -->
                <Link v-if="isAuthenticated && isAdmin" :href="route('leagues.create')">
                    <Button>
                        <PlusIcon class="mr-2 h-4 w-4"/>
                        {{ t('Create New League') }}
                    </Button>
                </Link>
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
                        <TrophyIcon class="h-5 w-5"/>
                        {{ t('Leagues Directory') }}
                    </CardTitle>
                </CardHeader>
                <CardContent class="p-0">
                    <DataTable
                        :columns="columns"
                        :compact-mode="true"
                        :data="filteredAndSortedLeagues"
                        :empty-message="selectedStatus === 'all' ? t('No leagues have been created yet.') : t('No :status leagues available.', {status: selectedStatus})"
                        :loading="isLoading"
                    >
                        <!-- Custom cell renderers -->
                        <template #cell-name="{ value }">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-8 w-8">
                                    <div
                                        class="h-8 w-8 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                                        <TrophyIcon class="h-4 w-4 text-blue-600 dark:text-blue-400"/>
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
                                <GamepadIcon class="h-4 w-4 mr-2 text-gray-400"/>
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
                            <span v-else class="text-sm text-gray-500 dark:text-gray-400">{{ t('Unknown') }}</span>
                        </template>

                        <template #cell-players="{ value }">
                            <div class="flex items-center text-sm text-gray-900 dark:text-gray-100">
                                <UsersIcon class="h-4 w-4 mr-2 text-gray-400"/>
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
                                <CalendarIcon class="h-4 w-4 mr-2"/>
                                {{ formatDate(value) }}
                            </div>
                        </template>

                        <template #cell-actions="{ item }">
                            <TableActions :actions="getActions(item)"/>
                        </template>
                    </DataTable>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
