<!-- resources/js/pages/Admin/Tournaments/Structure/Schedule.vue -->
<script lang="ts" setup>
import {
    Alert,
    AlertDescription,
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
    Spinner
} from '@/Components/ui';
import MatchSchedule from '@/Components/Tournament/Match/MatchSchedule.vue';
import MatchResultForm from '@/Components/Tournament/Match/MatchResultForm.vue';
import {useTournamentStructure} from '@/composables/useTournamentStructure';
import {useTournaments} from '@/composables/useTournaments';
import {useProfileApi} from '@/composables/useProfileApi';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import {useLocale} from '@/composables/useLocale';
import {useToastStore} from '@/stores/toast';
import type {Tournament, TournamentMatch} from '@/types/tournament';
import type {Club} from '@/types/api';
import {Head} from '@inertiajs/vue3';
import {AlertTriangle} from 'lucide-vue-next';
import {computed, onMounted, ref, watch} from 'vue';

defineOptions({layout: AuthenticatedLayout});

const props = defineProps<{
    tournamentId: number | string;
}>();

const {t} = useLocale();
const toast = useToastStore();
const {fetchTournament} = useTournaments();
const {fetchClubs} = useProfileApi();
const {
    fetchMatches,
    enterMatchResult,
    startMatch,
    cancelMatch,
    rescheduleMatch,
    bulkRescheduleMatches
} = useTournamentStructure();

const tournament = ref<Tournament | null>(null);
const matches = ref<TournamentMatch[]>([]);
const clubs = ref<Club[]>([]);
const isLoading = ref(true);
const error = ref<string | null>(null);

const selectedMatch = ref<TournamentMatch | null>(null);
const showResultModal = ref(false);

const tournamentApi = fetchTournament(props.tournamentId);
const matchesApi = fetchMatches(props.tournamentId);
const clubsApi = fetchClubs();

const matchStats = computed(() => {
    const stats = {
        total: matches.value.length,
        pending: 0,
        in_progress: 0,
        completed: 0,
        cancelled: 0,
        scheduled: 0,
        unscheduled: 0
    };

    matches.value.forEach(match => {
        stats[match.status as keyof typeof stats]++;
        if (match.scheduled_at) {
            stats.scheduled++;
        } else {
            stats.unscheduled++;
        }
    });

    return stats;
});

const loadData = async () => {
    isLoading.value = true;
    error.value = null;

    try {
        await Promise.all([
            tournamentApi.execute(),
            matchesApi.execute(),
            clubsApi.execute()
        ]);

        if (tournamentApi.data.value) {
            tournament.value = tournamentApi.data.value;
        }
        if (matchesApi.data.value) {
            matches.value = matchesApi.data.value;
        }
        if (clubsApi.data.value) {
            clubs.value = clubsApi.data.value;
        }
    } catch (err: any) {
        error.value = err.message || 'Failed to load data';
    } finally {
        isLoading.value = false;
    }
};

// Match operations
const handleStartMatch = async (matchId: number) => {
    const startApi = startMatch(props.tournamentId, matchId);
    const success = await startApi.execute();

    if (success) {
        toast.success(t('Match started'));
        await matchesApi.execute();
        if (matchesApi.data.value) {
            matches.value = matchesApi.data.value;
        }
    } else {
        toast.error(t('Failed to start match'));
    }
};

const handleCancelMatch = async (matchId: number, reason?: string) => {
    if (!confirm(t('Are you sure you want to cancel this match?'))) {
        return;
    }

    const cancelApi = cancelMatch(props.tournamentId, matchId);
    const success = await cancelApi.execute(reason);

    if (success) {
        toast.success(t('Match cancelled'));
        await matchesApi.execute();
        if (matchesApi.data.value) {
            matches.value = matchesApi.data.value;
        }
    } else {
        toast.error(t('Failed to cancel match'));
    }
};

const handleRescheduleMatch = async (matchId: number, data: any) => {
    const rescheduleApi = rescheduleMatch(props.tournamentId, matchId);
    const success = await rescheduleApi.execute(data);

    if (success) {
        toast.success(t('Match rescheduled'));
        await matchesApi.execute();
        if (matchesApi.data.value) {
            matches.value = matchesApi.data.value;
        }
    } else {
        toast.error(t('Failed to reschedule match'));
    }
};

const handleBulkReschedule = async (matchData: any[]) => {
    const bulkApi = bulkRescheduleMatches(props.tournamentId);
    const success = await bulkApi.execute(matchData);

    if (success) {
        toast.success(t('Matches rescheduled'));
        await matchesApi.execute();
        if (matchesApi.data.value) {
            matches.value = matchesApi.data.value;
        }
    } else {
        toast.error(t('Failed to reschedule matches'));
    }
};

const openResultModal = (match: TournamentMatch) => {
    selectedMatch.value = match;
    showResultModal.value = true;
};

const handleMatchResult = async (result: any) => {
    if (!selectedMatch.value) return;

    const resultApi = enterMatchResult(props.tournamentId, selectedMatch.value.id);
    const success = await resultApi.execute(result);

    if (success) {
        toast.success(t('Match result saved'));
        showResultModal.value = false;
        selectedMatch.value = null;
        await matchesApi.execute();
        if (matchesApi.data.value) {
            matches.value = matchesApi.data.value;
        }
    } else {
        toast.error(t('Failed to save match result'));
    }
};

// Watch for matches that just became 'in_progress' to show result entry
watch(matches, (newMatches) => {
    const inProgressMatches = newMatches.filter(m => m.status === 'in_progress');
    if (inProgressMatches.length === 1 && !showResultModal.value) {
        // Auto-open result modal for single in-progress match
        openResultModal(inProgressMatches[0]);
    }
});

onMounted(() => {
    loadData();
});
</script>

<template>
    <Head :title="tournament ? `${t('Match Schedule')}: ${tournament.name}` : t('Match Schedule')"/>

    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-6">
                <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">{{ t('Match Schedule') }}</h1>
                <p class="text-gray-600 dark:text-gray-400">
                    {{ tournament ? tournament.name : t('Loading...') }}
                </p>
            </div>

            <!-- Loading State -->
            <div v-if="isLoading" class="flex items-center justify-center py-12">
                <Spinner class="h-8 w-8 text-primary"/>
                <span class="ml-2 text-gray-500 dark:text-gray-400">{{ t('Loading schedule...') }}</span>
            </div>

            <!-- Error State -->
            <Alert v-else-if="error" class="mb-6" variant="destructive">
                <AlertTriangle class="h-4 w-4"/>
                <AlertDescription>{{ error }}</AlertDescription>
            </Alert>

            <!-- Content -->
            <template v-else-if="tournament">
                <!-- Match Statistics -->
                <Card class="mb-6">
                    <CardHeader>
                        <CardTitle>{{ t('Match Overview') }}</CardTitle>
                        <CardDescription>
                            {{ t(':total total matches across all rounds', {total: matchStats.total}) }}
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                            <div class="text-center">
                                <div class="text-2xl font-bold text-gray-600 dark:text-gray-400">
                                    {{ matchStats.pending }}
                                </div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">{{ t('Pending') }}</div>
                            </div>
                            <div class="text-center">
                                <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                                    {{ matchStats.in_progress }}
                                </div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">{{ t('In Progress') }}</div>
                            </div>
                            <div class="text-center">
                                <div class="text-2xl font-bold text-green-600 dark:text-green-400">
                                    {{ matchStats.completed }}
                                </div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">{{ t('Completed') }}</div>
                            </div>
                            <div class="text-center">
                                <div class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">
                                    {{ matchStats.scheduled }}
                                </div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">{{ t('Scheduled') }}</div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Match Schedule Component -->
                <MatchSchedule
                    :can-edit="true"
                    :clubs="clubs"
                    :matches="matches"
                    @update-match="(id, data) => console.log('Update match', id, data)"
                    @start-match="handleStartMatch"
                    @cancel-match="handleCancelMatch"
                    @reschedule-match="handleRescheduleMatch"
                    @bulk-reschedule="handleBulkReschedule"
                    @enter-result="openResultModal"
                />

                <!-- Match Result Modal -->
                <MatchResultForm
                    v-if="selectedMatch"
                    :best-of-rule="tournament.best_of_rule || 'best_of_3'"
                    :match="selectedMatch"
                    :show="showResultModal"
                    @close="showResultModal = false; selectedMatch = null"
                    @submit="handleMatchResult"
                />
            </template>
        </div>
    </div>
</template>
