<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { useAuth } from '@/composables/useAuth';
import { useLeagues } from '@/composables/useLeagues';
import type { League } from '@/Types/api';
import { Button, Card, CardContent, CardHeader, CardTitle, Spinner } from '@/Components/ui';
import { PlusIcon, EyeIcon, PencilIcon } from 'lucide-vue-next';
import { onMounted } from 'vue';

defineOptions({ layout: AuthenticatedLayout });

const { isAdmin } = useAuth();
const leagues = useLeagues();

// Get leagues data from our composable
const { data: leaguesData, isLoading, error: loadingError, execute: fetchLeagues } = leagues.fetchLeagues();

// Safer url generation for route params
const getLeagueUrl = (routeName: 'leagues.show' | 'leagues.edit', leagueId: number | undefined | null): string | null => {
    if (typeof leagueId !== 'number') return null;

    try {
        return route(routeName, { league: leagueId });
    } catch (e) {
        console.error(`Error generating ${routeName} route with ID ${leagueId}:`, e);
        return null;
    }
};

// Load leagues on component mount
onMounted(() => {
    fetchLeagues();
});
</script>

<template>
    <Head title="Leagues" />
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">Available Leagues</h1>
                <Link v-if="isAdmin" :href="route('leagues.create')">
                    <Button>
                        <PlusIcon class="w-4 h-4 mr-2" />
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
                        <Spinner class="w-8 h-8 text-primary" />
                        <span class="ml-2 text-gray-500 dark:text-gray-400">Loading leagues...</span>
                    </div>

                    <!-- Error State -->
                    <div v-else-if="loadingError" class="text-center text-red-600 dark:text-red-400 bg-red-100 dark:bg-red-900/30 p-4 rounded">
                        Error loading leagues: {{ loadingError.message }}
                    </div>

                    <!-- Empty State -->
                    <div v-else-if="!leaguesData || leaguesData.length === 0" class="text-center text-gray-500 dark:text-gray-400 py-10">
                        No leagues found.
                        <span v-if="isAdmin"> Start by creating one!</span>
                    </div>

                    <!-- League List -->
                    <ul v-else class="space-y-4">
                        <li v-for="league in leaguesData" :key="league.id"
                            class="border dark:border-gray-700 rounded-lg p-4 flex flex-col sm:flex-row justify-between items-start sm:items-center hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">

                            <div class="mb-4 sm:mb-0">
                                <h2 class="text-lg font-semibold text-blue-700 dark:text-blue-400">{{ league.name ?? 'Unnamed League' }}</h2>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Game: {{ league.game ?? 'N/A' }}</p>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Matches Played: {{ league.matches_count ?? 0 }}</p>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    Rating Enabled:
                                    <span :class="league.has_rating ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'">
                                        {{ league.has_rating ? 'Yes' : 'No' }}
                                    </span>
                                </p>
                            </div>

                            <div class="flex space-x-2 flex-shrink-0">
                                <Link v-if="getLeagueUrl('leagues.show', league.id)" :href="getLeagueUrl('leagues.show', league.id)!" title="View Details">
                                    <Button variant="outline" size="icon">
                                        <EyeIcon class="w-4 h-4" />
                                    </Button>
                                </Link>

                                <Link v-if="isAdmin && getLeagueUrl('leagues.edit', league.id)" :href="getLeagueUrl('leagues.edit', league.id)!" title="Edit League">
                                    <Button variant="outline" size="icon">
                                        <PencilIcon class="w-4 h-4" />
                                    </Button>
                                </Link>
                            </div>
                        </li>
                    </ul>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
