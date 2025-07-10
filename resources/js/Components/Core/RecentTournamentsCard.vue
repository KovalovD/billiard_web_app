<script lang="ts" setup>
import {Card, CardContent, CardHeader, Spinner} from '@/Components/ui';
import {apiClient} from '@/lib/apiClient';
import type {Tournament} from '@/types/api';
import {Link} from '@inertiajs/vue3';
import {onMounted, ref} from 'vue';
import {useLocale} from '@/composables/useLocale';
import {CalendarIcon, ClockIcon, MapPinIcon, TrophyIcon, UsersIcon} from 'lucide-vue-next';

const recentTournaments = ref<Tournament[]>([]);
const upcomingTournaments = ref<Tournament[]>([]);
const isLoadingRecent = ref(true);
const isLoadingUpcoming = ref(true);
const errorRecent = ref<string | null>(null);
const errorUpcoming = ref<string | null>(null);
const {t} = useLocale();

// Get status badge for tournament
const getStatusInfo = (tournament: Tournament) => {
    switch (tournament.status) {
        case 'upcoming':
            return {text: t('Upcoming'), color: 'text-blue-600 bg-blue-50 dark:text-blue-400 dark:bg-blue-900/20'};
        case 'active':
            return {
                text: t('Active'),
                color: 'text-emerald-600 bg-emerald-50 dark:text-emerald-400 dark:bg-emerald-900/20'
            };
        case 'completed':
            return {text: t('Completed'), color: 'text-gray-600 bg-gray-50 dark:text-gray-400 dark:bg-gray-800'};
        case 'cancelled':
            return {text: t('Cancelled'), color: 'text-red-600 bg-red-50 dark:text-red-400 dark:bg-red-900/20'};
        default:
            return {text: tournament.status, color: 'text-gray-600 bg-gray-50 dark:text-gray-400 dark:bg-gray-800'};
    }
};

// Format tournament date
const formatDate = (dateString: string | null) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    const today = new Date();
    const diffTime = date.getTime() - today.getTime();
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

    if (diffDays === 0) return t('Today');
    if (diffDays === 1) return t('Tomorrow');
    if (diffDays > 0 && diffDays <= 7) return t('In :days days', {days: diffDays});

    return date.toLocaleDateString('uk-UK', {month: 'short', day: 'numeric', year: 'numeric'});
};

// Get registration info
const getRegistrationInfo = (tournament: Tournament) => {
    if (!tournament.is_registration_open) {
        return {text: t('Closed'), color: 'text-gray-500'};
    }

    const percentage = tournament.max_participants
        ? Math.round((tournament.confirmed_players_count || 0) / tournament.max_participants * 100)
        : 0;

    if (percentage >= 90) {
        return {text: t('Almost full'), color: 'text-red-600 dark:text-red-400'};
    } else if (percentage >= 70) {
        return {text: t('Filling up'), color: 'text-amber-600 dark:text-amber-400'};
    }

    return {text: t('Open'), color: 'text-emerald-600 dark:text-emerald-400'};
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
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <!-- Recent Tournaments -->
        <Card class="border-0 shadow-sm hover:shadow-md transition-shadow duration-200">
            <CardHeader class="border-b border-gray-100 dark:border-gray-800 pb-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-gray-50 dark:bg-gray-800 rounded-lg">
                            <ClockIcon class="h-5 w-5 text-gray-600 dark:text-gray-400"/>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ t('Recent') }}</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ t('Latest tournaments') }}</p>
                        </div>
                    </div>
                </div>
            </CardHeader>
            <CardContent class="p-0">
                <div v-if="isLoadingRecent" class="flex items-center justify-center py-12">
                    <Spinner class="h-8 w-8 text-gray-400"/>
                </div>

                <div v-else-if="errorRecent" class="py-12 text-center">
                    <p class="text-sm text-red-600 dark:text-red-400">{{ errorRecent }}</p>
                </div>

                <div v-else-if="recentTournaments.length === 0" class="py-12 text-center">
                    <TrophyIcon class="mx-auto h-12 w-12 text-gray-300 dark:text-gray-600 mb-3"/>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ t('No recent tournaments found') }}</p>
                </div>

                <div v-else class="divide-y divide-gray-100 dark:divide-gray-800">
                    <div v-for="tournament in recentTournaments.slice(0, 5)" :key="tournament.slug"
                         class="px-6 py-4 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 mb-1">
                                    <h4 class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                        {{ tournament.name }}
                                    </h4>
                                    <span :class="getStatusInfo(tournament).color"
                                          class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium">
                                        {{ getStatusInfo(tournament).text }}
                                    </span>
                                </div>
                                <div class="flex items-center gap-4 text-xs text-gray-500 dark:text-gray-400">
                                    <span class="flex items-center gap-1">
                                        <CalendarIcon class="h-3 w-3"/>
                                        {{ formatDate(tournament.start_date) }}
                                    </span>
                                    <span v-if="tournament.city" class="flex items-center gap-1">
                                        <MapPinIcon class="h-3 w-3"/>
                                        {{ tournament.city.name }}
                                    </span>
                                </div>
                            </div>
                            <Link :href="`/tournaments/${tournament.slug}`"
                                  class="text-sm font-medium text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 dark:hover:text-indigo-300 whitespace-nowrap">
                                {{ t('View') }} →
                            </Link>
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Upcoming Tournaments -->
        <Card class="border-0 shadow-sm hover:shadow-md transition-shadow duration-200">
            <CardHeader class="border-b border-gray-100 dark:border-gray-800 pb-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-emerald-50 dark:bg-emerald-900/20 rounded-lg">
                            <CalendarIcon class="h-5 w-5 text-emerald-600 dark:text-emerald-400"/>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ t('Upcoming') }}</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ t('Open for registration') }}</p>
                        </div>
                    </div>
                </div>
            </CardHeader>
            <CardContent class="p-0">
                <div v-if="isLoadingUpcoming" class="flex items-center justify-center py-12">
                    <Spinner class="h-8 w-8 text-gray-400"/>
                </div>

                <div v-else-if="errorUpcoming" class="py-12 text-center">
                    <p class="text-sm text-red-600 dark:text-red-400">{{ errorUpcoming }}</p>
                </div>

                <div v-else-if="upcomingTournaments.length === 0" class="py-12 text-center">
                    <CalendarIcon class="mx-auto h-12 w-12 text-gray-300 dark:text-gray-600 mb-3"/>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">{{ t('No upcoming tournaments') }}</p>
                    <Link :href="route('tournaments.index.page')"
                          class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 dark:hover:text-indigo-300">
                        {{ t('Browse all') }} →
                    </Link>
                </div>

                <div v-else class="divide-y divide-gray-100 dark:divide-gray-800">
                    <div v-for="tournament in upcomingTournaments.slice(0, 5)" :key="tournament.slug"
                         class="px-6 py-4 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 mb-1">
                                    <h4 class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                        {{ tournament.name }}
                                    </h4>
                                    <span :class="`${getRegistrationInfo(tournament).color} text-xs font-medium`">
                                        {{ getRegistrationInfo(tournament).text }}
                                    </span>
                                </div>
                                <div class="flex items-center gap-4 text-xs text-gray-500 dark:text-gray-400">
                                    <span class="flex items-center gap-1">
                                        <CalendarIcon class="h-3 w-3"/>
                                        {{ formatDate(tournament.start_date) }}
                                    </span>
                                    <span v-if="tournament.city" class="flex items-center gap-1">
                                        <MapPinIcon class="h-3 w-3"/>
                                        {{ tournament.city.name }}
                                    </span>
                                    <span v-if="tournament.max_participants" class="flex items-center gap-1">
                                        <UsersIcon class="h-3 w-3"/>
                                        {{ tournament.confirmed_players_count || 0 }}/{{ tournament.max_participants }}
                                    </span>
                                </div>
                            </div>
                            <Link :href="`/tournaments/${tournament.slug}`"
                                  class="text-sm font-medium text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 dark:hover:text-indigo-300 whitespace-nowrap">
                                {{ t('View') }} →
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- View More -->
                <div v-if="upcomingTournaments.length > 5"
                     class="px-6 py-3 bg-gray-50 dark:bg-gray-800/50 border-t border-gray-100 dark:border-gray-800 text-center">
                    <Link :href="route('tournaments.index.page')"
                          class="text-sm font-medium text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 dark:hover:text-indigo-300">
                        {{ t('View all upcoming') }} ({{ upcomingTournaments.length }})
                    </Link>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
