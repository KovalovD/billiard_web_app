<script lang="ts" setup>
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { useAuth } from '@/composables/useAuth';
import { ref, onMounted, computed } from 'vue';
import { apiClient } from '@/lib/apiClient';
import type { MatchGame, Rating } from '@/types/api';
import { Card, CardContent, CardDescription, CardHeader, CardTitle, Spinner } from '@/Components/ui';
import { TrophyIcon, SwordIcon, PercentIcon, BarChart4Icon } from 'lucide-vue-next';

defineOptions({ layout: AuthenticatedLayout });

const { user } = useAuth();

// State for user stats
const userRatings = ref<Rating[]>([]);
const userMatches = ref<MatchGame[]>([]);
const isLoadingRatings = ref(false);
const isLoadingMatches = ref(false);
const errorMessage = ref('');

// Computed stats
const totalMatches = computed(() => userMatches.value.length);
const totalWins = computed(() => userMatches.value.filter(match => {
    return match.winner_rating_id &&
        ((match.firstRating?.user_id === user.value?.id && match.winner_rating_id === match.first_rating_id) ||
            (match.secondRating?.user_id === user.value?.id && match.winner_rating_id === match.second_rating_id));
}).length);
const winRate = computed(() => {
    if (totalMatches.value === 0) return 0;
    return Math.round((totalWins.value / totalMatches.value) * 100);
});

// Fetch user ratings across all leagues
const fetchUserRatings = async () => {
    if (!user.value?.id) return;

    isLoadingRatings.value = true;
    try {
        // This endpoint would need to be implemented on the backend
        const response = await apiClient<Rating[]>(`/api/user/ratings`);
        userRatings.value = response;
    } catch (error) {
        console.error('Failed to fetch user ratings:', error);
        errorMessage.value = 'Failed to load your league ratings';
    } finally {
        isLoadingRatings.value = false;
    }
};

// Fetch user match history across all leagues
const fetchUserMatches = async () => {
    if (!user.value?.id) return;

    isLoadingMatches.value = true;
    try {
        // This endpoint would need to be implemented on the backend
        const response = await apiClient<MatchGame[]>(`/api/user/matches`);
        userMatches.value = response;
    } catch (error) {
        console.error('Failed to fetch user matches:', error);
        errorMessage.value = 'Failed to load your match history';
    } finally {
        isLoadingMatches.value = false;
    }
};

// Format date for better display
const formatDate = (dateString: string | undefined | null) => {
    if (!dateString) return 'N/A';

    return new Date(dateString).toLocaleDateString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
};

// Load data on component mount
onMounted(() => {
    fetchUserRatings();
    fetchUserMatches();
});
</script>

<template>
    <Head title="Profile Statistics" />

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div v-if="errorMessage" class="bg-red-100 text-red-600 p-4 rounded-md dark:bg-red-900/30 dark:text-red-400">
                {{ errorMessage }}
            </div>

            <!-- User Stats Overview -->
            <Card>
                <CardHeader>
                    <CardTitle>Statistics Overview</CardTitle>
                    <CardDescription>
                        Your performance across all leagues
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div v-if="isLoadingMatches" class="flex justify-center py-8">
                        <Spinner class="w-8 h-8 text-primary" />
                    </div>
                    <div v-else class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div class="p-4 bg-blue-50 rounded-md dark:bg-blue-900/20">
                            <div class="flex items-center space-x-2 mb-2">
                                <SwordIcon class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                                <h3 class="font-medium text-blue-800 dark:text-blue-300">Total Matches</h3>
                            </div>
                            <p class="text-3xl font-bold text-blue-600 dark:text-blue-400">{{ totalMatches }}</p>
                        </div>

                        <div class="p-4 bg-green-50 rounded-md dark:bg-green-900/20">
                            <div class="flex items-center space-x-2 mb-2">
                                <TrophyIcon class="w-5 h-5 text-green-600 dark:text-green-400" />
                                <h3 class="font-medium text-green-800 dark:text-green-300">Wins</h3>
                            </div>
                            <p class="text-3xl font-bold text-green-600 dark:text-green-400">{{ totalWins }}</p>
                        </div>

                        <div class="p-4 bg-amber-50 rounded-md dark:bg-amber-900/20">
                            <div class="flex items-center space-x-2 mb-2">
                                <PercentIcon class="w-5 h-5 text-amber-600 dark:text-amber-400" />
                                <h3 class="font-medium text-amber-800 dark:text-amber-300">Win Rate</h3>
                            </div>
                            <p class="text-3xl font-bold text-amber-600 dark:text-amber-400">{{ winRate }}%</p>
                        </div>

                        <div class="p-4 bg-purple-50 rounded-md dark:bg-purple-900/20">
                            <div class="flex items-center space-x-2 mb-2">
                                <BarChart4Icon class="w-5 h-5 text-purple-600 dark:text-purple-400" />
                                <h3 class="font-medium text-purple-800 dark:text-purple-300">League Memberships</h3>
                            </div>
                            <p class="text-3xl font-bold text-purple-600 dark:text-purple-400">{{ userRatings.length }}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- League Ratings -->
            <Card>
                <CardHeader>
                    <CardTitle>League Ratings</CardTitle>
                    <CardDescription>
                        Your current ratings across different leagues
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div v-if="isLoadingRatings" class="flex justify-center py-8">
                        <Spinner class="w-8 h-8 text-primary" />
                    </div>
                    <div v-else-if="userRatings.length === 0" class="text-center py-6 text-gray-500 dark:text-gray-400">
                        You haven't joined any leagues yet.
                    </div>
                    <div v-else class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                            <tr class="border-b dark:border-gray-700">
                                <th class="py-3 text-left font-medium">League</th>
                                <th class="py-3 text-left font-medium">Rating</th>
                                <th class="py-3 text-left font-medium">Position</th>
                                <th class="py-3 text-left font-medium">Status</th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr v-for="rating in userRatings" :key="rating.id" class="border-b dark:border-gray-700">
                                <td class="py-3">{{ rating.league?.name || 'Unknown League' }}</td>
                                <td class="py-3 font-semibold">{{ rating.rating }}</td>
                                <td class="py-3">#{{ rating.position }}</td>
                                <td class="py-3">
                                        <span
                                            class="px-2 py-1 text-xs rounded-full"
                                            :class="rating.is_active ?
                                                'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' :
                                                'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-300'"
                                        >
                                            {{ rating.is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>

            <!-- Recent Matches -->
            <Card>
                <CardHeader>
                    <CardTitle>Recent Matches</CardTitle>
                    <CardDescription>
                        Your most recent match results
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div v-if="isLoadingMatches" class="flex justify-center py-8">
                        <Spinner class="w-8 h-8 text-primary" />
                    </div>
                    <div v-else-if="userMatches.length === 0" class="text-center py-6 text-gray-500 dark:text-gray-400">
                        You haven't played any matches yet.
                    </div>
                    <div v-else>
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                <tr class="border-b dark:border-gray-700">
                                    <th class="py-3 text-left font-medium">Date</th>
                                    <th class="py-3 text-left font-medium">Opponent</th>
                                    <th class="py-3 text-left font-medium">Score</th>
                                    <th class="py-3 text-left font-medium">Result</th>
                                    <th class="py-3 text-left font-medium">Rating Change</th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr v-for="match in userMatches.slice(0, 10)" :key="match.id" class="border-b dark:border-gray-700">
                                    <td class="py-3">{{ formatDate(match.finished_at || match.created_at) }}</td>
                                    <td class="py-3">
                                        {{
                                            user?.id === match.firstRating?.user_id ?
                                                match.secondPlayer?.user?.firstname || 'Opponent' :
                                                match.firstPlayer?.user?.firstname || 'Opponent'
                                        }}
                                    </td>
                                    <td class="py-3 font-medium">
                                        {{ match.first_user_score }} - {{ match.second_user_score }}
                                    </td>
                                    <td class="py-3">
                                            <span
                                                class="px-2 py-1 text-xs rounded-full"
                                                :class="
                                                    (user?.id === match.firstRating?.user_id && match.winner_rating_id === match.first_rating_id) ||
                                                    (user?.id === match.secondRating?.user_id && match.winner_rating_id === match.second_rating_id)
                                                    ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300'
                                                    : match.status === 'completed'
                                                    ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300'
                                                    : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300'
                                                "
                                            >
                                                {{
                                                    (user?.id === match.firstRating?.user_id && match.winner_rating_id === match.first_rating_id) ||
                                                    (user?.id === match.secondRating?.user_id && match.winner_rating_id === match.second_rating_id)
                                                        ? 'Win'
                                                        : match.status === 'completed'
                                                            ? 'Loss'
                                                            : match.status === 'pending'
                                                                ? 'Pending'
                                                                : 'In Progress'
                                                }}
                                            </span>
                                    </td>
                                    <td class="py-3">
                                            <span
                                                :class="
                                                    (user?.id === match.firstRating?.user_id && match.winner_rating_id === match.first_rating_id) ||
                                                    (user?.id === match.secondRating?.user_id && match.winner_rating_id === match.second_rating_id)
                                                    ? 'text-green-600 dark:text-green-400'
                                                    : match.status === 'completed'
                                                    ? 'text-red-600 dark:text-red-400'
                                                    : 'text-gray-500 dark:text-gray-400'
                                                "
                                            >
                                                {{
                                                    match.status === 'completed'
                                                        ? (
                                                            (user?.id === match.firstRating?.user_id && match.winner_rating_id === match.first_rating_id) ||
                                                            (user?.id === match.secondRating?.user_id && match.winner_rating_id === match.second_rating_id)
                                                                ? '+' + (match.rating_change_for_winner || 0)
                                                                : (match.rating_change_for_loser || 0)
                                                        )
                                                        : '—'
                                                }}
                                            </span>
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
