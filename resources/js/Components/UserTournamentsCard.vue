<script lang="ts" setup>
import {Card, CardContent, CardDescription, CardHeader, CardTitle, Spinner} from '@/Components/ui';
import {apiClient} from '@/lib/apiClient';
import type {Tournament, TournamentPlayer} from '@/types/api';
import {Link} from '@inertiajs/vue3';
import {computed, onMounted, ref} from 'vue';

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
const getParticipationBadge = (participation: TournamentPlayer) => {
    switch (participation.status) {
        case 'applied':
            return {text: 'Pending', class: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400'};
        case 'confirmed':
            if (participation.position) {
                if (participation.position === 1) {
                    return {
                        text: `Winner (#${participation.position})`,
                        class: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
                    };
                } else if (participation.position <= 3) {
                    return {
                        text: `Top 3 (#${participation.position})`,
                        class: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400'
                    };
                } else {
                    return {
                        text: `#${participation.position}`,
                        class: 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300'
                    };
                }
            }
            return {text: 'Confirmed', class: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'};
        case 'rejected':
            return {text: 'Rejected', class: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'};
        default:
            return {text: participation.status, class: 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300'};
    }
};

// Format tournament date
const formatDate = (dateString: string | null) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {month: 'short', day: 'numeric'});
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
    <Card>
        <CardHeader>
            <CardTitle>Your Tournaments</CardTitle>
            <CardDescription>Tournaments you've participated in or applied to</CardDescription>
        </CardHeader>
        <CardContent>
            <div v-if="isLoading" class="py-4 text-center text-gray-500 dark:text-gray-400">
                <Spinner class="text-primary mx-auto mb-2 h-6 w-6"/>
                <span>Loading your tournaments...</span>
            </div>

            <div v-else-if="error" class="py-4 text-center text-red-500 dark:text-red-400">
                {{ error }}
            </div>

            <div v-else-if="!tournamentsData || activeTournaments.length === 0"
                 class="py-4 text-center text-gray-500 dark:text-gray-400">
                <p>No active tournament participations.</p>
                <Link :href="route('tournaments.index.page')"
                      class="mt-2 block text-blue-600 hover:underline dark:text-blue-400">
                    Browse tournaments to join
                </Link>
            </div>

            <div v-else>
                <!-- Quick Stats -->
                <div v-if="tournamentsData.stats.total_tournaments > 0"
                     class="mb-4 rounded-lg bg-gray-50 p-3 dark:bg-gray-800/50">
                    <div class="grid grid-cols-2 gap-2 text-sm">
                        <div>
                            <span class="text-gray-600 dark:text-gray-400">Tournaments:</span>
                            <span class="ml-1 font-semibold">{{ tournamentsData.stats.total_tournaments }}</span>
                        </div>
                        <div>
                            <span class="text-gray-600 dark:text-gray-400">Wins:</span>
                            <span class="ml-1 font-semibold">{{ tournamentsData.stats.total_wins }}</span>
                        </div>
                        <div>
                            <span class="text-gray-600 dark:text-gray-400">Win Rate:</span>
                            <span class="ml-1 font-semibold">{{ tournamentsData.stats.win_rate }}%</span>
                        </div>
                        <div>
                            <span class="text-gray-600 dark:text-gray-400">Top 3:</span>
                            <span class="ml-1 font-semibold">{{ tournamentsData.stats.top_three_rate }}%</span>
                        </div>
                    </div>
                </div>

                <!-- Active Tournaments List -->
                <ul class="divide-y divide-gray-200 dark:divide-gray-700">
                    <li v-for="item in activeTournaments" :key="`${item.tournament.id}-${item.participation.id}`"
                        class="py-3">
                        <div class="flex items-center justify-between">
                            <div class="flex-1">
                                <div class="flex items-center gap-2">
                                    <h4 class="font-medium text-gray-900 dark:text-white">
                                        {{ item.tournament.name }}
                                    </h4>
                                    <span
                                        :class="getParticipationBadge(item.participation).class"
                                        class="rounded-full px-2 py-0.5 text-xs font-medium"
                                    >
                                        {{ getParticipationBadge(item.participation).text }}
                                    </span>
                                </div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ item.tournament.game?.name }} • {{ formatDate(item.tournament.start_date) }}
                                    <span v-if="item.tournament.city" class="ml-1">• {{
                                            item.tournament.city.name
                                        }}</span>
                                </p>
                            </div>
                            <Link :href="`/tournaments/${item.tournament.id}`"
                                  class="text-sm text-blue-600 hover:underline dark:text-blue-400">
                                View
                            </Link>
                        </div>
                    </li>
                </ul>

                <!-- Link to see all tournaments -->
                <div v-if="activeTournaments.length >= 5" class="mt-4 text-center">
                    <Link :href="route('tournaments.index.page')"
                          class="text-sm text-blue-600 hover:underline dark:text-blue-400">
                        View all tournaments →
                    </Link>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
