<script lang="ts" setup>
import {Card, CardContent, CardDescription, CardHeader, CardTitle, Spinner} from '@/Components/ui/index';
import {apiClient} from '@/lib/apiClient';
import type {Tournament} from '@/types/api';
import {Link} from '@inertiajs/vue3';
import {onMounted, ref} from 'vue';
import {useLocale} from '@/composables/useLocale';

const recentTournaments = ref<Tournament[]>([]);
const upcomingTournaments = ref<Tournament[]>([]);
const isLoadingRecent = ref(true);
const isLoadingUpcoming = ref(true);
const errorRecent = ref<string | null>(null);
const errorUpcoming = ref<string | null>(null);
const {t} = useLocale();

// Get status badge for tournament
const getStatusBadge = (tournament: Tournament) => {
    switch (tournament.status) {
        case 'upcoming':
            return {text: t('Upcoming'), class: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400'};
        case 'active':
            return {text: t('Active'), class: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'};
        case 'completed':
            return {text: t('Completed'), class: 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300'};
        case 'cancelled':
            return {text: t('Cancelled'), class: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'};
        default:
            return {text: tournament.status, class: 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300'};
    }
};

// Format tournament date
const formatDate = (dateString: string | null) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {month: 'short', day: 'numeric'});
};

// Format registration info
const getRegistrationInfo = (tournament: Tournament) => {
    if (!tournament.is_registration_open) {
        return t('Registration closed');
    }

    if (tournament.max_participants) {
        return `${tournament.confirmed_players_count || 0}/${tournament.max_participants} players`;
    }

    return `${tournament.confirmed_players_count || 0} players`;
};

const fetchRecentTournaments = async () => {
    isLoadingRecent.value = true;
    errorRecent.value = null;

    try {
        recentTournaments.value = await apiClient<Tournament[]>('/api/user/tournaments/recent');
    } catch (err: any) {
        errorRecent.value = err.message || 'Failed to load recent tournaments';
    } finally {
        isLoadingRecent.value = false;
    }
};

const fetchUpcomingTournaments = async () => {
    isLoadingUpcoming.value = true;
    errorUpcoming.value = null;

    try {
        upcomingTournaments.value = await apiClient<Tournament[]>('/api/user/tournaments/upcoming');
    } catch (err: any) {
        errorUpcoming.value = err.message || 'Failed to load upcoming tournaments';
    } finally {
        isLoadingUpcoming.value = false;
    }
};

onMounted(() => {
    fetchRecentTournaments();
    fetchUpcomingTournaments();
});
</script>

<template>
    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
        <!-- Recent Tournaments -->
        <Card>
            <CardHeader>
                <CardTitle>{{ t('Recent Tournaments') }}</CardTitle>
                <CardDescription>{{ t('Latest completed and active tournaments') }}</CardDescription>
            </CardHeader>
            <CardContent>
                <div v-if="isLoadingRecent" class="py-4 text-center text-gray-500 dark:text-gray-400">
                    <Spinner class="text-primary mx-auto mb-2 h-6 w-6"/>
                    <span>{{ t('Loading tournaments...') }}</span>
                </div>

                <div v-else-if="errorRecent" class="py-4 text-center text-red-500 dark:text-red-400">
                    {{ errorRecent }}
                </div>

                <div v-else-if="recentTournaments.length === 0"
                     class="py-4 text-center text-gray-500 dark:text-gray-400">
                    {{ t('No recent tournaments found.') }}
                </div>

                <ul v-else class="divide-y divide-gray-200 dark:divide-gray-700">
                    <li v-for="tournament in recentTournaments" :key="tournament.id" class="py-3">
                        <div class="flex items-center justify-between">
                            <div class="flex-1">
                                <div class="flex items-center gap-2">
                                    <h4 class="font-medium text-gray-900 dark:text-white">
                                        {{ tournament.name }}
                                    </h4>
                                    <span
                                        :class="getStatusBadge(tournament).class"
                                        class="rounded-full px-2 py-0.5 text-xs font-medium"
                                    >
                                        {{ getStatusBadge(tournament).text }}
                                    </span>
                                </div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ tournament.game?.name }} • {{ formatDate(tournament.start_date) }}
                                    <span v-if="tournament.city" class="ml-1">• {{ tournament.city.name }}</span>
                                </p>
                            </div>
                            <Link :href="`/tournaments/${tournament.id}`"
                                  class="text-sm text-blue-600 hover:underline dark:text-blue-400">
                                {{ t('View') }}
                            </Link>
                        </div>
                    </li>
                </ul>
            </CardContent>
        </Card>

        <!-- Upcoming Tournaments -->
        <Card>
            <CardHeader>
                <CardTitle>{{ t('Upcoming Tournaments') }}</CardTitle>
                <CardDescription>{{ t('Tournaments open for registration') }}</CardDescription>
            </CardHeader>
            <CardContent>
                <div v-if="isLoadingUpcoming" class="py-4 text-center text-gray-500 dark:text-gray-400">
                    <Spinner class="text-primary mx-auto mb-2 h-6 w-6"/>
                    <span>{{ t('Loading tournaments...') }}</span>
                </div>

                <div v-else-if="errorUpcoming" class="py-4 text-center text-red-500 dark:text-red-400">
                    {{ errorUpcoming }}
                </div>

                <div v-else-if="upcomingTournaments.length === 0"
                     class="py-4 text-center text-gray-500 dark:text-gray-400">
                    <p>{{ t('No upcoming tournaments available.') }}</p>
                    <Link :href="route('tournaments.index.page')"
                          class="mt-2 block text-blue-600 hover:underline dark:text-blue-400">
                        {{ t('Check all tournaments →') }}
                    </Link>
                </div>

                <ul v-else class="divide-y divide-gray-200 dark:divide-gray-700">
                    <li v-for="tournament in upcomingTournaments" :key="tournament.id" class="py-3">
                        <div class="flex items-center justify-between">
                            <div class="flex-1">
                                <div class="flex items-center gap-2">
                                    <h4 class="font-medium text-gray-900 dark:text-white">
                                        {{ tournament.name }}
                                    </h4>
                                    <span
                                        v-if="tournament.is_registration_open"
                                        class="rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-700 dark:bg-green-900/30 dark:text-green-400"
                                    >
                                        {{ t('Open') }}
                                    </span>
                                    <span
                                        v-else
                                        class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-700 dark:bg-gray-700 dark:text-gray-300"
                                    >
                                        {{ t('Closed') }}
                                    </span>
                                </div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ tournament.game?.name }} • {{ formatDate(tournament.start_date) }}
                                    <span v-if="tournament.city" class="ml-1">• {{ tournament.city.name }}</span>
                                </p>
                                <p class="text-xs text-gray-400 dark:text-gray-500">
                                    {{ getRegistrationInfo(tournament) }}
                                </p>
                            </div>
                            <Link :href="`/tournaments/${tournament.id}`"
                                  class="text-sm text-blue-600 hover:underline dark:text-blue-400">
                                {{ t('View') }}
                            </Link>
                        </div>
                    </li>
                </ul>

                <!-- Link to see all tournaments -->
                <div v-if="upcomingTournaments.length >= 5" class="mt-4 text-center">
                    <Link :href="route('tournaments.index.page')"
                          class="text-sm text-blue-600 hover:underline dark:text-blue-400">
                        {{ t('View all tournaments →') }}
                    </Link>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
