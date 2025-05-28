<script lang="ts" setup>
import {Card, CardContent, CardDescription, CardHeader, CardTitle, Spinner} from '@/Components/ui';
import {apiClient} from '@/lib/apiClient';
import type {League, MatchGame, Rating} from '@/types/api';
import {Link} from '@inertiajs/vue3';
import {computed, onMounted, ref} from 'vue';

interface LeagueWithMatches {
    league: League;
    activeMatches: MatchGame[];
    rating: Rating;
}

const leagues = ref<Record<string, LeagueWithMatches>>({});
const isLoading = ref(true);
const error = ref<string | null>(null);

const emit = defineEmits(['activeMatchesFound']);

// Compute all active matches across all leagues
const activeMatches = computed(() => {
    const matches: MatchGame[] = [];

    Object.values(leagues.value).forEach((leagueData) => {
        // Filter for in_progress matches where user is involved
        const inProgressMatches = leagueData.activeMatches.filter((match) => match.status === 'in_progress' || match.status === 'must_be_confirmed');

        matches.push(...inProgressMatches);
    });

    return matches;
});

const fetchUserLeagues = async () => {
    isLoading.value = true;
    error.value = null;

    try {
        leagues.value = await apiClient<Record<string, LeagueWithMatches>>('/api/my-leagues-and-challenges', {
            method: 'post',
        });

        // Emit if we have active matches
        if (activeMatches.value.length > 0) {
            emit('activeMatchesFound', activeMatches.value);
        }
    } catch (err: any) {
        error.value = err.message || 'Failed to load your leagues';
    } finally {
        isLoading.value = false;
    }
};

// Count active (in_progress) matches for each league
const getActiveMatchesCount = (leagueData: LeagueWithMatches) => {
    return leagueData.activeMatches.filter((match) => match.status === 'in_progress').length;
};

// Refresh data (can be called from parent)
const refreshData = () => {
    fetchUserLeagues();
};

// Expose refreshData to parent components
defineExpose({refreshData});

onMounted(fetchUserLeagues);
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>Your Leagues</CardTitle>
            <CardDescription>Leagues you've joined and are active in</CardDescription>
        </CardHeader>
        <CardContent>
            <div v-if="isLoading" class="py-4 text-center text-gray-500 dark:text-gray-400">
                <Spinner class="text-primary mx-auto mb-2 h-6 w-6"/>
                <span>Loading your leagues...</span>
            </div>

            <div v-else-if="error" class="py-4 text-center text-red-500 dark:text-red-400">
                {{ error }}
            </div>

            <div v-else-if="Object.keys(leagues).length === 0"
                 class="py-4 text-center text-gray-500 dark:text-gray-400">
                <p>You haven't joined any leagues yet.</p>
                <Link :href="route('leagues.index.page')"
                      class="mt-2 block text-blue-600 hover:underline dark:text-blue-400">
                    Browse leagues to join
                </Link>
            </div>

            <ul v-else class="divide-y divide-gray-200 dark:divide-gray-700">
                <li v-for="(item, leagueId) in leagues" :key="leagueId" class="py-3">
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="font-medium text-gray-900 dark:text-white">
                                {{ item.league.name }}
                                <span
                                    v-if="getActiveMatchesCount(item) > 0"
                                    class="ml-2 rounded-full bg-blue-100 px-2 py-0.5 text-xs text-blue-600 dark:bg-blue-900/30 dark:text-blue-400"
                                >
                                    {{ getActiveMatchesCount(item) }} active
                                </span>
                            </h4>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                Your Rating: <span class="font-semibold">{{ item.rating.rating }}</span>
                                <span v-if="item.activeMatches.length" class="ml-2 text-amber-600 dark:text-amber-400">
                                    ({{ item.activeMatches.length }} matches total)
                                </span>
                            </p>
                        </div>
                        <Link :href="`/leagues/${item.league.id}`"
                              class="text-sm text-blue-600 hover:underline dark:text-blue-400"> View
                        </Link>
                    </div>
                </li>
            </ul>
        </CardContent>
    </Card>
</template>
