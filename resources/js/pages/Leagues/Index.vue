<script lang="ts" setup>
import {Button, Card, CardContent, CardHeader, CardTitle, Spinner} from '@/Components/ui';
import {useAuth} from '@/composables/useAuth';
import {useLeagues} from '@/composables/useLeagues';
import {useLeagueStatus} from '@/composables/useLeagueStatus';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import type {ApiError, League} from '@/types/api';
import {Head, Link} from '@inertiajs/vue3';
import {EyeIcon, PencilIcon, PlusIcon, TrophyIcon} from 'lucide-vue-next';
import {computed, onMounted, ref} from 'vue';

defineOptions({layout: AuthenticatedLayout});

const {isAdmin} = useAuth();
const leagues = useLeagues();
const {getLeagueStatus, getPlayersText} = useLeagueStatus();

const leaguesData = ref<League[]>([]);
const isLoading = ref(false);
const loadingError = ref<ApiError | null>(null);

// Sort leagues by status
const sortedLeagues = computed(() => {
    if (!leaguesData.value) return [];

    return [...leaguesData.value].sort((a, b) => {
        const statusA = getLeagueStatus(a);
        const statusB = getLeagueStatus(b);

        // Priority: Active > Upcoming > Ended
        if (statusA?.text === 'Active' && statusB?.text !== 'Active') return -1;
        if (statusB?.text === 'Active' && statusA?.text !== 'Active') return 1;
        if (statusA?.text === 'Upcoming' && statusB?.text !== 'Upcoming') return -1;
        if (statusB?.text === 'Upcoming' && statusA?.text !== 'Upcoming') return 1;

        // Sort by name within same status
        return a.name.localeCompare(b.name);
    });
});

const fetchLeagues = async () => {
    isLoading.value = true;
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

onMounted(() => {
    fetchLeagues();
});

const getLeagueUrl = (routeName: 'leagues.show' | 'leagues.edit', leagueId: number | undefined | null): string | null => {
    if (typeof leagueId !== 'number') return null;

    try {
        return route(routeName, {league: leagueId});
    } catch (e) {
        console.error(`Error generating ${routeName} route with ID ${leagueId}:`, e);
        return null;
    }
};
</script>

<template>
    <Head title="Leagues"/>
    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <div class="mb-6 flex items-center justify-between">
                <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">Available Leagues</h1>
                <Link v-if="isAdmin" :href="route('leagues.create')">
                    <Button>
                        <PlusIcon class="mr-2 h-4 w-4"/>
                        Create New League
                    </Button>
                </Link>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>League List</CardTitle>
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
                        No leagues found.
                        <span v-if="isAdmin"> Start by creating one!</span>
                    </div>

                    <!-- League Grid -->
                    <div v-else class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                        <div
                            v-for="league in sortedLeagues"
                            :key="league.id"
                            class="rounded-lg border p-4 transition hover:shadow-md dark:border-gray-700"
                        >
                            <div class="mb-4 flex items-start justify-between">
                                <div class="flex-1">
                                    <h2 class="flex items-center gap-2 text-lg font-semibold text-blue-700 dark:text-blue-400">
                                        {{ league.name ?? 'Unnamed League' }}
                                        <span
                                            v-if="getLeagueStatus(league)"
                                            :class="['rounded-full px-2 py-1 text-xs font-semibold', getLeagueStatus(league)?.class]"
                                        >
                                            {{ getLeagueStatus(league)?.text }}
                                        </span>
                                    </h2>
                                    <p class="mt-1 flex items-center gap-1 text-sm text-gray-600 dark:text-gray-400">
                                        <TrophyIcon class="h-3 w-3"/>
                                        {{ league.game ?? 'N/A' }}
                                    </p>
                                </div>
                            </div>

                            <div class="mb-4 space-y-2">
                                <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                                    <component :is="getLeagueStatus(league)?.icon" class="h-3 w-3"/>
                                    <span>{{ getPlayersText(league) }}</span>
                                </div>

                                <div v-if="league.started_at"
                                     class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                                    <component :is="getLeagueStatus(league)?.icon" class="h-3 w-3"/>
                                    <span>Starts: {{ new Date(league.started_at).toLocaleDateString() }}</span>
                                </div>
                            </div>

                            <div class="flex space-x-2">
                                <Link
                                    v-if="getLeagueUrl('leagues.show', league.id)"
                                    :href="getLeagueUrl('leagues.show', league.id)!"
                                    class="flex-1"
                                    title="View Details"
                                >
                                    <Button class="w-full" size="sm" variant="outline">
                                        <EyeIcon class="mr-2 h-4 w-4"/>
                                        View
                                    </Button>
                                </Link>

                                <Link
                                    v-if="isAdmin && getLeagueUrl('leagues.edit', league.id)"
                                    :href="getLeagueUrl('leagues.edit', league.id)!"
                                    class="flex-1"
                                    title="Edit League"
                                >
                                    <Button class="w-full" size="sm" variant="outline">
                                        <PencilIcon class="mr-2 h-4 w-4"/>
                                        Edit
                                    </Button>
                                </Link>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
