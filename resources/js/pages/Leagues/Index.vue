<script lang="ts" setup>
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import {Head, Link} from '@inertiajs/vue3';
import {useAuth} from '@/composables/useAuth';
import {useLeagues} from '@/composables/useLeagues';
import {useLeagueStatus} from '@/composables/useLeagueStatus';
import type {ApiError, League} from '@/types/api';
import {Button, Card, CardContent, CardHeader, CardTitle, Spinner} from '@/Components/ui';
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
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">Available Leagues</h1>
                <Link v-if="isAdmin" :href="route('leagues.create')">
                    <Button>
                        <PlusIcon class="w-4 h-4 mr-2"/>
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
                    <div v-if="isLoading" class="flex justify-center items-center py-10">
                        <Spinner class="w-8 h-8 text-primary"/>
                        <span class="ml-2 text-gray-500 dark:text-gray-400">Loading leagues...</span>
                    </div>

                    <!-- Error State -->
                    <div v-else-if="loadingError"
                         class="text-center text-red-600 dark:text-red-400 bg-red-100 dark:bg-red-900/30 p-4 rounded">
                        Error loading leagues: {{ loadingError.message }}
                    </div>

                    <!-- Empty State -->
                    <div v-else-if="!leaguesData || leaguesData.length === 0"
                         class="text-center text-gray-500 dark:text-gray-400 py-10">
                        No leagues found.
                        <span v-if="isAdmin"> Start by creating one!</span>
                    </div>

                    <!-- League Grid -->
                    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <div v-for="league in sortedLeagues" :key="league.id"
                             class="border dark:border-gray-700 rounded-lg p-4 hover:shadow-md transition">

                            <div class="flex justify-between items-start mb-4">
                                <div class="flex-1">
                                    <h2 class="text-lg font-semibold text-blue-700 dark:text-blue-400 flex items-center gap-2">
                                        {{ league.name ?? 'Unnamed League' }}
                                        <span v-if="getLeagueStatus(league)"
                                              :class="['px-2 py-1 text-xs rounded-full font-semibold', getLeagueStatus(league)?.class]">
                                              {{ getLeagueStatus(league)?.text }}
                                        </span>
                                    </h2>
                                    <p class="text-sm text-gray-600 dark:text-gray-400 flex items-center gap-1 mt-1">
                                        <TrophyIcon class="w-3 h-3"/>
                                        {{ league.game ?? 'N/A' }}
                                    </p>
                                </div>
                            </div>

                            <div class="space-y-2 mb-4">
                                <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                                    <component :is="getLeagueStatus(league)?.icon" class="w-3 h-3"/>
                                    <span>{{ getPlayersText(league) }}</span>
                                </div>

                                <div v-if="league.started_at"
                                     class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                                    <component :is="getLeagueStatus(league)?.icon" class="w-3 h-3"/>
                                    <span>Starts: {{ new Date(league.started_at).toLocaleDateString() }}</span>
                                </div>
                            </div>

                            <div class="flex space-x-2">
                                <Link v-if="getLeagueUrl('leagues.show', league.id)"
                                      :href="getLeagueUrl('leagues.show', league.id)!" class="flex-1"
                                      title="View Details">
                                    <Button class="w-full" size="sm" variant="outline">
                                        <EyeIcon class="w-4 h-4 mr-2"/>
                                        View
                                    </Button>
                                </Link>

                                <Link v-if="isAdmin && getLeagueUrl('leagues.edit', league.id)"
                                      :href="getLeagueUrl('leagues.edit', league.id)!" class="flex-1"
                                      title="Edit League">
                                    <Button class="w-full" size="sm" variant="outline">
                                        <PencilIcon class="w-4 h-4 mr-2"/>
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
