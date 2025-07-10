<script lang="ts" setup>
import {Card, CardContent, CardHeader, Spinner} from '@/Components/ui';
import {apiClient} from '@/lib/apiClient';
import type {Tournament, TournamentPlayer} from '@/types/api';
import {Link} from '@inertiajs/vue3';
import {computed, onMounted, ref} from 'vue';
import {useLocale} from '@/composables/useLocale';
import {CalendarIcon, MapPinIcon, TrophyIcon} from 'lucide-vue-next';

interface TournamentWithParticipation {
    tournament: Tournament;
    participation: TournamentPlayer;
}

interface UserTournamentData {
    tournaments: {
        upcoming: TournamentWithParticipation[];
        active: TournamentWithParticipation[];
        completed: TournamentWithParticipation[];
        pending_applications: TournamentWithParticipation[];
        rejected_applications: TournamentWithParticipation[];
    };
    stats: {
        total_tournaments: number;
        total_wins: number;
        total_top_three: number;
        win_rate: number;
        top_three_rate: number;
        pending_applications: number;
    };
}

const tournamentsData = ref<UserTournamentData | null>(null);
const isLoading = ref(true);
const error = ref<string | null>(null);
const {t} = useLocale();

const emit = defineEmits(['pendingApplicationsFound']);

// Compute active tournaments (upcoming + active + pending applications)
const activeTournaments = computed(() => {
    if (!tournamentsData.value) return [];

    return [
        ...tournamentsData.value.tournaments.upcoming,
        ...tournamentsData.value.tournaments.active,
        ...tournamentsData.value.tournaments.pending_applications,
    ];
});

const fetchUserTournaments = async () => {
    isLoading.value = true;
    error.value = null;

    try {
        tournamentsData.value = await apiClient<UserTournamentData>('/api/user/tournaments/my-tournaments-and-applications');

        // Emit if we have pending applications
        if (tournamentsData.value?.stats.pending_applications > 0) {
            emit('pendingApplicationsFound', tournamentsData.value.tournaments.pending_applications);
        }
    } catch (err: any) {
        error.value = err.message || 'Failed to load your tournaments';
    } finally {
        isLoading.value = false;
    }
};

// Get status badge for tournament participation
const getParticipationStatus = (participation: TournamentPlayer) => {
    switch (participation.status) {
        case 'applied':
            return {text: t('Pending'), color: 'text-amber-600 bg-amber-50 dark:text-amber-400 dark:bg-amber-900/20'};
        case 'confirmed':
            if (participation.position) {
                if (participation.position === 1) {
                    return {
                        text: t('Winner'),
                        color: 'text-emerald-600 bg-emerald-50 dark:text-emerald-400 dark:bg-emerald-900/20'
                    };
                } else if (participation.position <= 3) {
                    return {
                        text: `#${participation.position}`,
                        color: 'text-blue-600 bg-blue-50 dark:text-blue-400 dark:bg-blue-900/20'
                    };
                } else {
                    return {
                        text: `#${participation.position}`,
                        color: 'text-gray-600 bg-gray-50 dark:text-gray-400 dark:bg-gray-800'
                    };
                }
            }
            return {
                text: t('Confirmed'),
                color: 'text-emerald-600 bg-emerald-50 dark:text-emerald-400 dark:bg-emerald-900/20'
            };
        case 'rejected':
            return {text: t('Rejected'), color: 'text-red-600 bg-red-50 dark:text-red-400 dark:bg-red-900/20'};
        default:
            return {text: participation.status, color: 'text-gray-600 bg-gray-50 dark:text-gray-400 dark:bg-gray-800'};
    }
};

// Format tournament date
const formatDate = (dateString: string | null) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleDateString('uk-UK', {month: 'short', day: 'numeric', year: 'numeric'});
};

// Refresh data (can be called from parent)
const refreshData = () => {
    fetchUserTournaments();
};

// Expose refreshData to parent components
defineExpose({refreshData});

onMounted(fetchUserTournaments);
</script>

<template>
    <Card class="border-0 shadow-sm hover:shadow-md transition-shadow duration-200">
        <CardHeader class="border-b border-gray-100 dark:border-gray-800 pb-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-indigo-50 dark:bg-indigo-900/20 rounded-lg">
                        <TrophyIcon class="h-5 w-5 text-indigo-600 dark:text-indigo-400"/>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ t('Tournaments') }}</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ t('Your tournament activity') }}</p>
                    </div>
                </div>
                <Link :href="route('tournaments.index.page')"
                      class="text-sm font-medium text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 dark:hover:text-indigo-300 transition-colors">
                    {{ t('View all') }}
                </Link>
            </div>
        </CardHeader>
        <CardContent class="p-0">
            <div v-if="isLoading" class="flex items-center justify-center py-12">
                <Spinner class="h-8 w-8 text-gray-400"/>
            </div>

            <div v-else-if="error" class="py-12 text-center">
                <p class="text-sm text-red-600 dark:text-red-400">{{ error }}</p>
            </div>

            <div v-else-if="!tournamentsData || activeTournaments.length === 0" class="py-12 text-center">
                <TrophyIcon class="mx-auto h-12 w-12 text-gray-300 dark:text-gray-600 mb-3"/>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">{{ t('No active tournaments') }}</p>
                <Link :href="route('tournaments.index.page')"
                      class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 dark:hover:text-indigo-300">
                    {{ t('Browse tournaments') }} →
                </Link>
            </div>

            <div v-else>
                <!-- Stats Bar -->
                <div v-if="tournamentsData.stats.total_tournaments > 0"
                     class="px-6 py-3 bg-gray-50 dark:bg-gray-800/50 border-b border-gray-100 dark:border-gray-800">
                    <div class="flex items-center justify-between text-sm">
                        <div class="flex items-center gap-6">
                            <div class="flex items-center gap-2">
                                <span class="text-gray-500 dark:text-gray-400">{{ t('Total') }}:</span>
                                <span class="font-medium text-gray-900 dark:text-white">{{
                                        tournamentsData.stats.total_tournaments
                                    }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-gray-500 dark:text-gray-400">{{ t('Wins') }}:</span>
                                <span class="font-medium text-gray-900 dark:text-white">{{
                                        tournamentsData.stats.total_wins
                                    }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-gray-500 dark:text-gray-400">{{ t('Win rate') }}:</span>
                                <span class="font-medium text-gray-900 dark:text-white">{{
                                        tournamentsData.stats.win_rate
                                    }}%</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tournaments List -->
                <div class="divide-y divide-gray-100 dark:divide-gray-800">
                    <div v-for="item in activeTournaments.slice(0, 5)"
                         :key="`${item.tournament.id}-${item.participation.id}`"
                         class="px-6 py-4 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 mb-1">
                                    <h4 class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                        {{ item.tournament.name }}
                                    </h4>
                                    <span :class="getParticipationStatus(item.participation).color"
                                          class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium whitespace-nowrap">
                                        {{ getParticipationStatus(item.participation).text }}
                                    </span>
                                </div>
                                <div class="flex items-center gap-4 text-xs text-gray-500 dark:text-gray-400">
                                    <span class="flex items-center gap-1">
                                        <CalendarIcon class="h-3 w-3"/>
                                        {{ formatDate(item.tournament.start_date) }}
                                    </span>
                                    <span v-if="item.tournament.game?.name">
                                        {{ item.tournament.game.name }}
                                    </span>
                                    <span v-if="item.tournament.city" class="flex items-center gap-1">
                                        <MapPinIcon class="h-3 w-3"/>
                                        {{ item.tournament.city.name }}
                                    </span>
                                </div>
                            </div>
                            <Link :href="`/tournaments/${item.tournament.slug}`"
                                  class="text-sm font-medium text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 dark:hover:text-indigo-300 whitespace-nowrap">
                                {{ t('View') }} →
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- View More -->
                <div v-if="activeTournaments.length > 5"
                     class="px-6 py-3 bg-gray-50 dark:bg-gray-800/50 border-t border-gray-100 dark:border-gray-800 text-center">
                    <Link :href="route('tournaments.index.page')"
                          class="text-sm font-medium text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 dark:hover:text-indigo-300">
                        {{ t('View all tournaments') }} ({{ activeTournaments.length }})
                    </Link>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
