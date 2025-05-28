<script lang="ts" setup>
import {Button, Card, CardContent, CardHeader, CardTitle, Spinner} from '@/Components/ui';
import {useAuth} from '@/composables/useAuth';
import {useLeagues} from '@/composables/useLeagues';
import {useLeagueStatus} from '@/composables/useLeagueStatus';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import type {ApiError, League} from '@/types/api';
import {Head, Link} from '@inertiajs/vue3';
import {CalendarIcon, EyeIcon, GamepadIcon, PencilIcon, PlusIcon, TrophyIcon, UsersIcon} from 'lucide-vue-next';
import {computed, onMounted, ref} from 'vue';

defineOptions({layout: AuthenticatedLayout});

const {isAdmin} = useAuth();
const leagues = useLeagues();
const {getLeagueStatus, getPlayersText} = useLeagueStatus();

const leaguesData = ref<League[]>([]);
const isLoading = ref(false);
const loadingError = ref<ApiError | null>(null);
const selectedStatus = ref<string>('all');

const statusOptions = [
    {value: 'all', label: 'All Leagues'},
    {value: 'active', label: 'Active'},
    {value: 'upcoming', label: 'Upcoming'},
    {value: 'ended', label: 'Ended'}
];

// Sort leagues by status and filter
const filteredAndSortedLeagues = computed(() => {
    if (!leaguesData.value) return [];

    let filtered = [...leaguesData.value];

    // Filter by status
    if (selectedStatus.value !== 'all') {
        filtered = filtered.filter(league => {
            const status = getLeagueStatus(league);
            return status?.text.toLowerCase() === selectedStatus.value;
        });
    }

    // Sort by status priority: Active > Upcoming > Ended
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
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleDateString();
};

const getLeagueUrl = (routeName: 'leagues.show.page' | 'leagues.edit', leagueId: number | undefined | null): string | null => {
    if (typeof leagueId !== 'number') return null;

    try {
        return route(routeName, {league: leagueId});
    } catch (e) {
        console.error(`Error generating ${routeName} route with ID ${leagueId}:`, e);
        return null;
    }
};

onMounted(() => {
    fetchLeagues();
});
</script>

<template>
    <Head title="Leagues"/>
    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">Available Leagues</h1>
                    <p class="text-gray-600 dark:text-gray-400">Manage and participate in competitive leagues</p>
                </div>
                <Link v-if="isAdmin" :href="route('leagues.create')">
                    <Button>
                        <PlusIcon class="mr-2 h-4 w-4"/>
                        Create New League
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
                        Leagues Directory
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <!-- Loading State -->
                    <div v-if="isLoading" class="flex items-center justify-center py-10">
                        <Spinner class="text-primary h-8 w-8"/>
                        <span class="ml-2 text-gray-500 dark:text-gray-400">Loading leagues...</span>
                    </div>

                    <!-- Error State -->
                    <div v-else-if="loadingError"
                         class="rounded bg-red-100 p-4 text-center text-red-600 dark:bg-red-900/30 dark:text-red-400">
                        Error loading leagues: {{ loadingError.message }}
                    </div>

                    <!-- Empty State -->
                    <div v-else-if="!leaguesData || leaguesData.length === 0"
                         class="py-10 text-center text-gray-500 dark:text-gray-400">
                        <TrophyIcon class="mx-auto h-12 w-12 mb-4 opacity-50"/>
                        <p class="text-lg">No leagues found</p>
                        <p class="text-sm">
                            {{
                                selectedStatus === 'all' ? 'No leagues have been created yet.' : `No ${selectedStatus} leagues available.`
                            }}
                            <span v-if="isAdmin"> Start by creating one!</span>
                        </p>
                    </div>

                    <!-- Leagues Table -->
                    <div v-else class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">
                                    League
                                </th>
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
                                    Matches
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">
                                    Start Date
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">
                                    Actions
                                </th>
                            </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-900 dark:divide-gray-700">
                            <tr
                                v-for="league in filteredAndSortedLeagues"
                                :key="league.id"
                                class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors"
                            >
                                <!-- League Name -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-8 w-8">
                                            <div
                                                class="h-8 w-8 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                                                <TrophyIcon class="h-4 w-4 text-blue-600 dark:text-blue-400"/>
                                            </div>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                {{ league.name ?? 'Unnamed League' }}
                                            </div>
                                            <div v-if="league.details"
                                                 class="text-sm text-gray-500 dark:text-gray-400 truncate max-w-xs">
                                                {{ league.details }}
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Game -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center text-sm text-gray-900 dark:text-gray-100">
                                        <GamepadIcon class="h-4 w-4 mr-2 text-gray-400"/>
                                        {{ league.game ?? 'N/A' }}
                                    </div>
                                </td>

                                <!-- Status -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                        <span
                                            v-if="getLeagueStatus(league)"
                                            :class="[
                                                'inline-flex px-2 py-1 text-xs font-medium rounded-full',
                                                getLeagueStatus(league)?.class
                                            ]"
                                        >
                                            {{ getLeagueStatus(league)?.text }}
                                        </span>
                                    <span v-else class="text-sm text-gray-500 dark:text-gray-400">Unknown</span>
                                </td>

                                <!-- Players -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center text-sm text-gray-900 dark:text-gray-100">
                                        <UsersIcon class="h-4 w-4 mr-2 text-gray-400"/>
                                        {{ getPlayersText(league) }}
                                    </div>
                                </td>

                                <!-- Matches -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900 dark:text-gray-100">
                                        {{ league.matches_count || 0 }} matches
                                    </div>
                                </td>

                                <!-- Start Date -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center text-sm text-gray-500 dark:text-gray-400">
                                        <CalendarIcon class="h-4 w-4 mr-2"/>
                                        {{ formatDate(league.started_at) }}
                                    </div>
                                </td>

                                <!-- Actions -->
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex justify-end space-x-2">
                                        <Link
                                            v-if="getLeagueUrl('leagues.show.page', league.id)"
                                            :href="getLeagueUrl('leagues.show.page', league.id)!"
                                            title="View Details"
                                        >
                                            <Button size="sm" variant="outline">
                                                <EyeIcon class="h-4 w-4"/>
                                            </Button>
                                        </Link>

                                        <Link
                                            v-if="isAdmin && getLeagueUrl('leagues.edit', league.id)"
                                            :href="getLeagueUrl('leagues.edit', league.id)!"
                                            title="Edit League"
                                        >
                                            <Button size="sm" variant="outline">
                                                <PencilIcon class="h-4 w-4"/>
                                            </Button>
                                        </Link>
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
</template>
