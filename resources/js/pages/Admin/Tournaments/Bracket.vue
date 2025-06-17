<!-- resources/js/pages/Admin/Tournaments/Bracket.vue -->
<script lang="ts" setup>
import {Button, Card, CardContent, CardDescription, CardHeader, CardTitle, Spinner} from '@/Components/ui';
import {useLocale} from '@/composables/useLocale';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import {apiClient} from '@/lib/apiClient';
import type {Tournament, TournamentBracket, TournamentMatch} from '@/types/api';
import {Head, router} from '@inertiajs/vue3';
import {ArrowLeftIcon, PlayIcon, RefreshCwIcon, TrophyIcon} from 'lucide-vue-next';
import {computed, onMounted, ref} from 'vue';

defineOptions({layout: AuthenticatedLayout});

const props = defineProps<{
    tournamentId: number | string;
}>();

const {t} = useLocale();

// Data
const tournament = ref<Tournament | null>(null);
const brackets = ref<TournamentBracket[]>([]);
const matches = ref<TournamentMatch[]>([]);

// Loading states
const isLoading = ref(true);
const isGenerating = ref(false);

// Error handling
const error = ref<string | null>(null);
const successMessage = ref<string | null>(null);

// Computed bracket structure
const bracketData = computed(() => {
    if (!matches.value.length) return [];

    // Group matches by round
    const rounds = new Map<string, TournamentMatch[]>();

    matches.value.forEach(match => {
        const roundKey = match.round || 'unknown';
        if (!rounds.has(roundKey)) {
            rounds.set(roundKey, []);
        }
        rounds.get(roundKey)!.push(match);
    });

    // Define round order
    const roundOrder = [
        'round_128', 'round_64', 'round_32', 'round_16',
        'quarterfinals', 'semifinals', 'finals'
    ];

    // Build ordered rounds array
    const orderedRounds = [];
    for (const roundName of roundOrder) {
        if (rounds.has(roundName)) {
            const roundMatches = rounds.get(roundName)!;
            // Sort by bracket position
            roundMatches.sort((a, b) => (a.bracket_position || 0) - (b.bracket_position || 0));
            orderedRounds.push({
                name: roundName,
                matches: roundMatches
            });
        }
    }

    return orderedRounds;
});

const hasGeneratedBracket = computed(() => matches.value.length > 0);

// Methods
const loadData = async () => {
    isLoading.value = true;
    error.value = null;

    try {
        // Load tournament
        tournament.value = await apiClient<Tournament>(`/api/tournaments/${props.tournamentId}`);

        // Load matches
        const matchesResponse = await apiClient<TournamentMatch[]>(`/api/admin/tournaments/${props.tournamentId}/matches`);
        matches.value = matchesResponse || [];

        // Try to load brackets
        try {
            const bracketsResponse = await apiClient<TournamentBracket[]>(`/api/tournaments/${props.tournamentId}/brackets`);
            brackets.value = bracketsResponse || [];
        } catch {
            brackets.value = [];
        }
    } catch (err: any) {
        error.value = err.message || t('Failed to load tournament data');
    } finally {
        isLoading.value = false;
    }
};

const generateBracket = async () => {
    if (!confirm(t('Are you sure you want to generate the bracket? This will create all matches based on current seeding.'))) {
        return;
    }

    isGenerating.value = true;
    error.value = null;

    try {
        await apiClient(`/api/admin/tournaments/${props.tournamentId}/bracket/generate`, {
            method: 'POST'
        });

        successMessage.value = t('Bracket generated successfully');
        await loadData();
    } catch (err: any) {
        error.value = err.message || t('Failed to generate bracket');
    } finally {
        isGenerating.value = false;
    }
};

const startTournament = async () => {
    if (!confirm(t('Are you sure you want to start the tournament?'))) {
        return;
    }

    try {
        await apiClient(`/api/admin/tournaments/${props.tournamentId}/status`, {
            method: 'POST',
            data: {status: 'active'}
        });

        successMessage.value = t('Tournament started successfully');
        router.visit(`/admin/tournaments/${props.tournamentId}/matches`);
    } catch (err: any) {
        error.value = err.message || t('Failed to start tournament');
    }
};

const getRoundDisplayName = (round: string): string => {
    const names: Record<string, string> = {
        'finals': t('Finals'),
        'semifinals': t('Semifinals'),
        'quarterfinals': t('Quarterfinals'),
        'round_16': t('Round of 16'),
        'round_32': t('Round of 32'),
        'round_64': t('Round of 64'),
        'round_128': t('Round of 128')
    };
    return names[round] || round;
};

const goToMatch = (matchId: number) => {
    router.visit(`/admin/tournaments/${props.tournamentId}/matches/${matchId}`);
};

onMounted(() => {
    loadData();
});
</script>

<template>
    <Head :title="tournament ? `${t('Bracket')}: ${tournament.name}` : t('Tournament Bracket')"/>

    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">{{
                            t('Tournament Bracket')
                        }}</h1>
                    <p class="text-gray-600 dark:text-gray-400">
                        {{ tournament ? tournament.name : t('Loading...') }}
                    </p>
                </div>
                <div class="flex space-x-3">
                    <Button :disabled="isLoading" variant="outline" @click="loadData">
                        <RefreshCwIcon :class="{ 'animate-spin': isLoading }" class="mr-2 h-4 w-4"/>
                        {{ t('Refresh') }}
                    </Button>
                    <Button variant="outline" @click="router.visit(`/tournaments/${props.tournamentId}`)">
                        <ArrowLeftIcon class="mr-2 h-4 w-4"/>
                        {{ t('Back to Tournament') }}
                    </Button>
                </div>
            </div>

            <!-- Messages -->
            <div v-if="error" class="mb-6 rounded bg-red-100 p-4 text-red-600 dark:bg-red-900/30 dark:text-red-400">
                {{ error }}
            </div>
            <div v-if="successMessage"
                 class="mb-6 rounded bg-green-100 p-4 text-green-600 dark:bg-green-900/30 dark:text-green-400">
                {{ successMessage }}
            </div>

            <!-- Loading -->
            <div v-if="isLoading" class="flex justify-center py-12">
                <Spinner class="h-8 w-8 text-primary"/>
            </div>

            <!-- Generate Bracket -->
            <Card v-else-if="!hasGeneratedBracket" class="mb-6">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <TrophyIcon class="h-5 w-5"/>
                        {{ t('Generate Tournament Bracket') }}
                    </CardTitle>
                    <CardDescription>
                        {{ t('Create the bracket structure based on seeded players') }}
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="text-center py-8">
                        <p class="mb-4 text-gray-600 dark:text-gray-400">
                            {{ t('The bracket has not been generated yet. Click below to create it.') }}
                        </p>
                        <Button
                            :disabled="isGenerating"
                            size="lg"
                            @click="generateBracket"
                        >
                            <Spinner v-if="isGenerating" class="mr-2 h-4 w-4"/>
                            {{ t('Generate Bracket') }}
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <!-- Bracket Display -->
            <template v-else>
                <!-- Tournament Info -->
                <Card class="mb-6">
                    <CardContent class="pt-6">
                        <div class="grid grid-cols-4 gap-4 text-center">
                            <div>
                                <div class="text-2xl font-bold text-blue-600">
                                    {{ tournament?.confirmed_players_count || 0 }}
                                </div>
                                <div class="text-sm text-gray-600">{{ t('Players') }}</div>
                            </div>
                            <div>
                                <div class="text-2xl font-bold text-green-600">{{ bracketData.length }}</div>
                                <div class="text-sm text-gray-600">{{ t('Rounds') }}</div>
                            </div>
                            <div>
                                <div class="text-2xl font-bold text-yellow-600">{{ matches.length }}</div>
                                <div class="text-sm text-gray-600">{{ t('Matches') }}</div>
                            </div>
                            <div>
                                <div class="text-2xl font-bold text-purple-600">{{ tournament?.races_to || 7 }}</div>
                                <div class="text-sm text-gray-600">{{ t('Races To') }}</div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Bracket Structure -->
                <Card>
                    <CardHeader>
                        <CardTitle>{{ t('Tournament Bracket') }}</CardTitle>
                        <CardDescription>{{ t('Tournament progression from first round to finals') }}</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="overflow-x-auto">
                            <div class="bracket-wrapper flex gap-8 min-w-max pb-4">
                                <!-- Each Round -->
                                <div v-for="(round, roundIndex) in bracketData" :key="round.name" class="bracket-round">
                                    <!-- Round Header -->
                                    <h3 class="text-center font-semibold text-gray-700 dark:text-gray-300 mb-4">
                                        {{ getRoundDisplayName(round.name) }}
                                    </h3>

                                    <!-- Round Matches -->
                                    <div class="space-y-4">
                                        <div v-for="(match, matchIndex) in round.matches" :key="match.id"
                                             class="match-wrapper">
                                            <!-- Match Card -->
                                            <div
                                                :class="{
                                                     'border-green-500': match.status === 'completed',
                                                     'border-yellow-500': match.status === 'in_progress',
                                                     'border-gray-300 dark:border-gray-600': match.status === 'pending'
                                                 }"
                                                class="match-card bg-white dark:bg-gray-800 border rounded-lg p-3 cursor-pointer hover:shadow-lg transition-shadow"
                                                @click="goToMatch(match.id)">
                                                <!-- Match Number -->
                                                <div class="text-xs text-gray-500 mb-2">
                                                    {{ t('Match') }} #{{ match.id }}
                                                </div>

                                                <!-- Players -->
                                                <div class="space-y-2">
                                                    <!-- Player 1 -->
                                                    <div :class="{'font-bold text-green-600': match.winner_id === match.player1_id}"
                                                         class="flex justify-between items-center py-1">
                                                        <span class="text-sm truncate max-w-[140px]">
                                                            {{
                                                                match.player1 ? `${match.player1.firstname} ${match.player1.lastname}` : t('TBD')
                                                            }}
                                                        </span>
                                                        <span class="text-sm font-mono ml-2">
                                                            {{ match.player1_score ?? '-' }}
                                                        </span>
                                                    </div>

                                                    <div class="border-t dark:border-gray-700"></div>

                                                    <!-- Player 2 -->
                                                    <div :class="{'font-bold text-green-600': match.winner_id === match.player2_id}"
                                                         class="flex justify-between items-center py-1">
                                                        <span class="text-sm truncate max-w-[140px]">
                                                            {{
                                                                match.player2 ? `${match.player2.firstname} ${match.player2.lastname}` : t('TBD')
                                                            }}
                                                        </span>
                                                        <span class="text-sm font-mono ml-2">
                                                            {{ match.player2_score ?? '-' }}
                                                        </span>
                                                    </div>
                                                </div>

                                                <!-- Match Status -->
                                                <div class="mt-2 text-xs text-center">
                                                    <span v-if="match.status === 'completed'"
                                                          class="text-green-600">{{ t('Completed') }}</span>
                                                    <span v-else-if="match.status === 'in_progress'"
                                                          class="text-yellow-600">{{ t('In Progress') }}</span>
                                                    <span v-else class="text-gray-500">{{ t('Pending') }}</span>
                                                </div>
                                            </div>

                                            <!-- Connection Line -->
                                            <div v-if="roundIndex < bracketData.length - 1"
                                                 class="bracket-connector hidden lg:block"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Start Tournament Button -->
                <Card v-if="tournament?.status === 'upcoming'" class="mt-6">
                    <CardContent class="pt-6">
                        <div class="flex items-center justify-between">
                            <p class="text-gray-600 dark:text-gray-400">
                                {{ t('Bracket is ready. Start the tournament to begin matches.') }}
                            </p>
                            <Button size="lg" @click="startTournament">
                                <PlayIcon class="mr-2 h-4 w-4"/>
                                {{ t('Start Tournament') }}
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </template>
        </div>
    </div>
</template>

<style scoped>
.bracket-wrapper {
    padding: 20px;
}

.bracket-round {
    min-width: 200px;
}

.match-card {
    width: 200px;
    position: relative;
}

.match-wrapper {
    position: relative;
    margin-bottom: 20px;
}

/* Simple connector lines */
.bracket-connector {
    position: absolute;
    top: 50%;
    left: 100%;
    width: 40px;
    height: 2px;
    background-color: #e5e7eb;
}

.dark .bracket-connector {
    background-color: #374151;
}

/* Spacing for bracket alignment */
.bracket-round:nth-child(2) .match-wrapper {
    margin-bottom: 60px;
}

.bracket-round:nth-child(3) .match-wrapper {
    margin-bottom: 140px;
}

.bracket-round:nth-child(4) .match-wrapper {
    margin-bottom: 300px;
}
</style>
