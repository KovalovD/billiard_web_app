// resources/js/Components/Core/UserLeaguesCard.vue
<script lang="ts" setup>
import {Card, CardContent, CardHeader, Spinner} from '@/Components/ui';
import {apiClient} from '@/lib/apiClient';
import type {League, MatchGame, Rating} from '@/types/api';
import {Link} from '@inertiajs/vue3';
import {computed, onMounted, ref} from 'vue';
import {useLocale} from '@/composables/useLocale';
import {ActivityIcon, StarIcon, UsersIcon} from 'lucide-vue-next';

interface LeagueWithMatches {
    league: League;
    activeMatches: MatchGame[];
    rating: Rating;
}

const leagues = ref<Record<string, LeagueWithMatches>>({});
const isLoading = ref(true);
const error = ref<string | null>(null);
const {t} = useLocale();

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

// Get total leagues count
const totalLeagues = computed(() => Object.keys(leagues.value).length);

// Get total active matches count
const totalActiveMatches = computed(() => {
    let count = 0;
    Object.values(leagues.value).forEach((leagueData) => {
        count += leagueData.activeMatches.filter((match) => match.status === 'in_progress').length;
    });
    return count;
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
    <Card
        class="border-0 shadow-sm hover:shadow-md transition-shadow duration-200 dark:bg-gray-800 dark:border-gray-700">
        <CardHeader class="border-b border-gray-100 dark:border-gray-800 p-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="p-2 bg-purple-50 dark:bg-purple-900/20 rounded-lg">
                        <UsersIcon class="h-5 w-5 text-purple-600 dark:text-purple-400"/>
                    </div>
                    <div>
                        <h3 class="text-base font-semibold text-gray-900 dark:text-white">{{ t('Leagues') }}</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ t('Your active leagues') }}</p>
                    </div>
                </div>
                <Link :href="route('leagues.index.page')"
                      class="text-sm font-medium text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 dark:hover:text-indigo-300 transition-colors">
                    {{ t('View all') }}
                </Link>
            </div>
        </CardHeader>
        <CardContent class="p-0">
            <div v-if="isLoading" class="flex items-center justify-center py-10">
                <Spinner class="h-7 w-7 text-gray-400"/>
            </div>

            <div v-else-if="error" class="py-10 text-center">
                <p class="text-sm text-red-600 dark:text-red-400">{{ error }}</p>
            </div>

            <div v-else-if="Object.keys(leagues).length === 0" class="py-10 text-center">
                <UsersIcon class="mx-auto h-10 w-10 text-gray-300 dark:text-gray-600 mb-3"/>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">{{
                        t("You haven't joined any leagues yet")
                    }}</p>
                <Link :href="route('leagues.index.page')"
                      class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 dark:hover:text-indigo-300">
                    {{ t('Browse leagues') }} →
                </Link>
            </div>

            <div v-else>
                <!-- Stats Bar -->
                <div v-if="totalLeagues > 0"
                     class="px-5 py-3 bg-gray-50 dark:bg-gray-800/50 border-b border-gray-100 dark:border-gray-800">
                    <div class="flex items-center justify-between text-sm">
                        <div class="flex items-center gap-5">
                            <span class="text-gray-500 dark:text-gray-400">
                                {{ t('Leagues') }}: <strong class="text-gray-900 dark:text-white">{{ totalLeagues }}</strong>
                            </span>
                            <span v-if="totalActiveMatches > 0" class="text-gray-500 dark:text-gray-400">
                                {{ t('Active matches') }}: <strong class="text-gray-900 dark:text-white">{{ totalActiveMatches }}</strong>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Leagues List -->
                <div class="divide-y divide-gray-100 dark:divide-gray-800">
                    <div v-for="(item, leagueId) in Object.fromEntries(Object.entries(leagues).slice(0, 4))" :key="leagueId"
                         class="px-5 py-3.5 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 mb-1">
                                    <h4 class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                        {{ item.league.name }}
                                    </h4>
                                    <span v-if="getActiveMatchesCount(item) > 0"
                                          class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-50 text-blue-600 dark:bg-blue-900/20 dark:text-blue-400">
                                        <ActivityIcon class="h-3 w-3 mr-1"/>
                                        {{ getActiveMatchesCount(item) }} {{ t('active') }}
                                    </span>
                                </div>
                                <div class="flex items-center gap-4 text-xs text-gray-500 dark:text-gray-400">
                                    <span class="flex items-center gap-1">
                                        <StarIcon class="h-3 w-3"/>
                                        {{ t('Rating') }}: <strong
                                        class="text-gray-700 dark:text-gray-300">{{ item.rating.rating }}</strong>
                                    </span>
                                    <span class="flex items-center gap-1">
                                        {{ t('Position') }}: <strong
                                        class="text-gray-700 dark:text-gray-300">#{{ item.rating.position }}</strong>
                                    </span>
                                    <span v-if="item.activeMatches.length">
                                        {{ t(':count matches', {count: item.activeMatches.length}) }}
                                    </span>
                                </div>
                            </div>
                            <Link :href="`/leagues/${item.league.slug}`"
                                  class="text-sm font-medium text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 dark:hover:text-indigo-300 whitespace-nowrap">
                                {{ t('View') }} →
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- View More -->
                <div v-if="Object.keys(leagues).length > 4"
                     class="px-5 py-3 bg-gray-50 dark:bg-gray-800/50 border-t border-gray-100 dark:border-gray-800 text-center">
                    <Link :href="route('leagues.index.page')"
                          class="text-sm font-medium text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 dark:hover:text-indigo-300">
                        {{ t('View all leagues') }} ({{ Object.keys(leagues).length }})
                    </Link>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
