<script lang="ts" setup>
import {Button, Card, CardContent, CardDescription, CardHeader, CardTitle, Input, Spinner} from '@/Components/ui';
import {useTournaments} from '@/composables/useTournaments';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import type {Tournament, TournamentPlayer} from '@/types/api';
import {Head, Link} from '@inertiajs/vue3';
import {ArrowLeftIcon, SaveIcon, TrophyIcon} from 'lucide-vue-next';
import {computed, onMounted, ref} from 'vue';
import {useToNumber} from "@vueuse/core";

defineOptions({layout: AuthenticatedLayout});

const props = defineProps<{
    tournamentId: number | string;
}>();

const {
    fetchTournament,
    fetchTournamentPlayers,
    setTournamentResults,
    changeTournamentStatus
} = useTournaments();

const tournament = ref<Tournament | null>(null);
const players = ref<TournamentPlayer[]>([]);
const resultForm = ref<Array<{
    player_id: number;
    position: number;
    rating_points: number;
    prize_amount: number;
    bonus_amount: number;
    achievement_amount: number;
}>>([]);

const isLoading = ref(true);
const isSaving = ref(false);
const error = ref<string | null>(null);
const successMessage = ref<string | null>(null);

const isFormValid = computed(() => {
    if (resultForm.value.length === 0) return false;

    const positions = resultForm.value.map(r => r.position);
    const uniquePositions = new Set(positions);

    return uniquePositions.size === positions.length &&
        positions.every(p => p > 0) &&
        resultForm.value.every(r =>
            r.rating_points >= 0 &&
            r.prize_amount >= 0 &&
            r.bonus_amount >= 0 &&
            r.achievement_amount >= 0
        );
});

const totalPrizeDistributed = computed(() => {
    return resultForm.value.reduce((sum, result) => useToNumber(sum).value + useToNumber(result.prize_amount).value, 0);
});

const totalBonusDistributed = computed(() => {
    return resultForm.value.reduce((sum, result) => useToNumber(sum).value + useToNumber(result.bonus_amount).value, 0);
});

const totalAchievementDistributed = computed(() => {
    return resultForm.value.reduce((sum, result) => useToNumber(sum).value + useToNumber(result.achievement_amount).value, 0);
});

const totalMoneyDistributed = computed(() => {
    return totalPrizeDistributed.value + totalBonusDistributed.value + totalAchievementDistributed.value;
});

const fetchData = async () => {
    isLoading.value = true;
    error.value = null;

    try {
        const tournamentApi = fetchTournament(props.tournamentId);
        const playersApi = fetchTournamentPlayers(props.tournamentId);

        await Promise.all([tournamentApi.execute(), playersApi.execute()]);

        if (tournamentApi.data.value && playersApi.data.value) {
            tournament.value = tournamentApi.data.value;
            players.value = playersApi.data.value;

            initializeForm();
        } else {
            throw new Error('Данных нет');
        }
    } catch (err: any) {
        error.value = err.message ?? 'Failed to load tournament data';
    } finally {
        isLoading.value = false;
    }

};

const initializeForm = () => {
    resultForm.value = players.value.map(player => ({
        player_id: player.id,
        position: player.position || 0,
        rating_points: player.rating_points || 0,
        prize_amount: player.prize_amount || 0,
        bonus_amount: player.bonus_amount || 0,
        achievement_amount: player.achievement_amount || 0
    }));
};

const calculateDefaultRatingPoints = (position: number, totalPlayers: number): number => {
    const basePoints = Math.max(0, totalPlayers - position + 1);

    if (position === 1) {
        return basePoints + Math.floor(totalPlayers * 0.5);
    } else if (position === 2) {
        return basePoints + Math.floor(totalPlayers * 0.3);
    } else if (position === 3) {
        return basePoints + Math.floor(totalPlayers * 0.1);
    }

    return basePoints;
};

const calculateDefaultPrizes = () => {
    if (!tournament.value?.prize_pool || tournament.value.prize_pool <= 0) return;

    const prizePool = tournament.value.prize_pool;
    const distribution = tournament.value.prize_distribution || [60, 30, 10]; // Default percentages

    resultForm.value.forEach((result, index) => {
        if (index < distribution.length) {
            result.prize_amount = (prizePool * distribution[index]) / 100;
        }
    });
};

const autoFillPositions = () => {
    resultForm.value.forEach((result, index) => {
        result.position = index + 1;
        result.rating_points = calculateDefaultRatingPoints(index + 1, players.value.length);
    });

    calculateDefaultPrizes();
};

const saveResults = async () => {
    if (!isFormValid.value) return;

    isSaving.value = true;
    error.value = null;
    successMessage.value = null;

    try {
        const saveAction = setTournamentResults(props.tournamentId);
        const success = await saveAction.execute({
            results: resultForm.value.filter(r => r.position > 0)
        });

        if (success) {
            successMessage.value = 'Tournament results saved successfully!';

            // Update tournament status to completed if not already
            if (tournament.value?.status !== 'completed') {
                const statusAction = changeTournamentStatus(props.tournamentId);
                await statusAction.execute({status: 'completed'});
            }

            // Refresh data
            await fetchData();
        }
    } catch (err: any) {
        error.value = err.message || 'Failed to save tournament results';
    } finally {
        isSaving.value = false;
    }
};

const getPlayerName = (playerId: number): string => {
    const player = players.value.find(p => p.id === playerId);
    return player ? `${player.user?.firstname} ${player.user?.lastname}` : 'Unknown Player';
};

onMounted(() => {
    fetchData();
});
</script>

<template>
    <Head :title="tournament ? `Tournament Results: ${tournament.name}` : 'Tournament Results'"/>

    <div class="py-12">
        <div class="mx-auto max-w-6xl sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-6 flex items-center justify-between">
                <Link :href="`/tournaments/${props.tournamentId}`">
                    <Button variant="outline">
                        <ArrowLeftIcon class="mr-2 h-4 w-4"/>
                        Back to Tournament
                    </Button>
                </Link>

                <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">
                    Tournament Results
                </h1>
            </div>

            <!-- Loading State -->
            <div v-if="isLoading" class="flex items-center justify-center py-10">
                <Spinner class="text-primary h-8 w-8"/>
                <span class="ml-2 text-gray-500 dark:text-gray-400">Loading tournament...</span>
            </div>

            <!-- Error State -->
            <div v-else-if="error"
                 class="mb-6 rounded bg-red-100 p-4 text-red-500 dark:bg-red-900/30 dark:text-red-400">
                {{ error }}
            </div>

            <!-- Success Message -->
            <div v-if="successMessage"
                 class="mb-6 rounded bg-green-100 p-4 text-green-600 dark:bg-green-900/30 dark:text-green-400">
                {{ successMessage }}
            </div>

            <!-- Tournament Info -->
            <Card v-if="tournament" class="mb-6">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <TrophyIcon class="h-5 w-5"/>
                        {{ tournament.name }}
                    </CardTitle>
                    <CardDescription>
                        {{ tournament.players_count }} players •
                        Prize Pool: {{
                            tournament.prize_pool.toLocaleString('uk-UA', {
                                style: 'currency',
                                currency: 'UAH'
                            }).replace('UAH', '₴')
                        }}
                    </CardDescription>
                </CardHeader>
            </Card>

            <!-- Results Form -->
            <Card v-if="!isLoading">
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <div>
                            <CardTitle>Tournament Results</CardTitle>
                            <CardDescription>Set positions, rating points, prize money, bonuses and achievements
                            </CardDescription>
                        </div>
                        <Button variant="outline" @click="autoFillPositions">
                            Auto Fill Positions
                        </Button>
                    </div>
                </CardHeader>
                <CardContent>
                    <div class="space-y-6">
                        <!-- Results Table -->
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                <tr class="border-b dark:border-gray-700">
                                    <th class="px-4 py-3 text-left">Player</th>
                                    <th class="px-4 py-3 text-center">Position</th>
                                    <th class="px-4 py-3 text-center">Rating Points</th>
                                    <th class="px-4 py-3 text-right">Prize (₴)</th>
                                    <th class="px-4 py-3 text-right">Bonus (₴)</th>
                                    <th class="px-4 py-3 text-right">Achievement (₴)</th>
                                    <th class="px-4 py-3 text-right">Total (₴)</th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr
                                    v-for="result in resultForm"
                                    :key="result.player_id"
                                    class="border-b dark:border-gray-700"
                                >
                                    <td class="px-4 py-3">
                                        <div class="font-medium">
                                            {{ getPlayerName(result.player_id) }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <Input
                                            v-model.number="result.position"
                                            class="w-20 text-center"
                                            min="0"
                                            placeholder="0"
                                            type="number"
                                        />
                                    </td>
                                    <td class="px-4 py-3">
                                        <Input
                                            v-model.number="result.rating_points"
                                            class="w-24 text-center"
                                            min="0"
                                            placeholder="0"
                                            type="number"
                                        />
                                    </td>
                                    <td class="px-4 py-3">
                                        <Input
                                            v-model.number="result.prize_amount"
                                            class="w-32 text-right"
                                            min="0"
                                            placeholder="0.00"
                                            step="0.01"
                                            type="number"
                                        />
                                    </td>
                                    <td class="px-4 py-3">
                                        <Input
                                            v-model.number="result.bonus_amount"
                                            class="w-32 text-right"
                                            min="0"
                                            placeholder="0.00"
                                            step="0.01"
                                            type="number"
                                        />
                                    </td>
                                    <td class="px-4 py-3">
                                        <Input
                                            v-model.number="result.achievement_amount"
                                            class="w-32 text-right"
                                            min="0"
                                            placeholder="0.00"
                                            step="0.01"
                                            type="number"
                                        />
                                    </td>
                                    <td class="px-4 py-3 text-right font-medium">
                                        {{
                                            (+result.prize_amount + +result.bonus_amount + +result.achievement_amount).toLocaleString('uk-UA', {
                                                minimumFractionDigits: 2,
                                                maximumFractionDigits: 2
                                            })
                                        }}₴
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Summary -->
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-5">
                            <div class="text-center p-4 bg-gray-50 rounded-lg dark:bg-gray-800">
                                <div class="text-lg font-bold text-blue-600 dark:text-blue-400">
                                    {{ resultForm.filter(r => r.position > 0).length }}
                                </div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">Players with Results</div>
                            </div>

                            <div class="text-center p-4 bg-gray-50 rounded-lg dark:bg-gray-800">
                                <div class="text-lg font-bold text-green-600 dark:text-green-400">
                                    {{ totalPrizeDistributed.toLocaleString() }}₴
                                </div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">Prize Money</div>
                            </div>

                            <div class="text-center p-4 bg-gray-50 rounded-lg dark:bg-gray-800">
                                <div class="text-lg font-bold text-orange-600 dark:text-orange-400">
                                    {{ totalBonusDistributed.toLocaleString() }}₴
                                </div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">Bonus Money</div>
                            </div>

                            <div class="text-center p-4 bg-gray-50 rounded-lg dark:bg-gray-800">
                                <div class="text-lg font-bold text-purple-600 dark:text-purple-400">
                                    {{ totalAchievementDistributed.toLocaleString() }}₴
                                </div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">Achievement Money</div>
                            </div>

                            <div class="text-center p-4 bg-gray-50 rounded-lg dark:bg-gray-800">
                                <div class="text-lg font-bold text-indigo-600 dark:text-indigo-400">
                                    {{ totalMoneyDistributed.toLocaleString() }}₴
                                </div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">Total Money</div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex justify-end space-x-4">
                            <Button
                                variant="outline"
                                @click="initializeForm"
                            >
                                Reset
                            </Button>
                            <Button
                                :disabled="!isFormValid || isSaving"
                                @click="saveResults"
                            >
                                <SaveIcon v-if="!isSaving" class="mr-2 h-4 w-4"/>
                                <Spinner v-else class="mr-2 h-4 w-4"/>
                                {{ isSaving ? 'Saving...' : 'Save Results' }}
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
