<!-- resources/js/pages/Admin/Tournaments/Seeding.vue -->
<script lang="ts" setup>
import {
    Button,
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
    Input,
    Label,
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
    Spinner
} from '@/Components/ui';
import DataTable from '@/Components/ui/data-table/DataTable.vue';
import {useLocale} from '@/composables/useLocale';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import {apiClient} from '@/lib/apiClient';
import type {Tournament, TournamentPlayer} from '@/types/api';
import {Head, router} from '@inertiajs/vue3';
import {
    ArrowLeftIcon,
    ArrowUpDownIcon,
    CheckCircleIcon,
    DicesIcon,
    PlayIcon,
    RefreshCwIcon,
    SaveIcon,
    StarIcon,
    UsersIcon
} from 'lucide-vue-next';
import {computed, onMounted, ref} from 'vue';

defineOptions({layout: AuthenticatedLayout});

const props = defineProps<{
    tournamentId: number | string;
}>();

const {t} = useLocale();

// Data
const tournament = ref<Tournament | null>(null);
const players = ref<TournamentPlayer[]>([]);
const seedingMethod = ref<'random' | 'rating' | 'manual'>('random');
const manualSeeds = ref<Record<number, number>>({});

// Loading states
const isLoading = ref(true);
const isSaving = ref(false);
const isGenerating = ref(false);

// Error handling
const error = ref<string | null>(null);
const successMessage = ref<string | null>(null);

// Computed
const confirmedPlayers = computed(() =>
    players.value.filter(p => p.status === 'confirmed')
);

const unseededPlayers = computed(() =>
    confirmedPlayers.value.filter(p => !p.seed_number)
);

const seededPlayers = computed(() =>
    confirmedPlayers.value.filter(p => p.seed_number).sort((a, b) => (a.seed_number || 0) - (b.seed_number || 0))
);

const canProceed = computed(() =>
    unseededPlayers.value.length === 0 && confirmedPlayers.value.length >= 2
);

const columns = computed(() => [
    {
        key: 'seed',
        label: t('Seed'),
        align: 'center' as const,
        width: '80px',
        render: (player: TournamentPlayer) => ({
            seed: player.seed_number,
            playerId: player.id
        })
    },
    {
        key: 'player',
        label: t('Player'),
        align: 'left' as const,
        render: (player: TournamentPlayer) => ({
            name: `${player.user?.firstname} ${player.user?.lastname}`,
            email: player.user?.email
        })
    },
    {
        key: 'rating',
        label: t('Rating'),
        align: 'center' as const,
        render: (player: TournamentPlayer) => ({
            rating: player.rating
        })
    },
    {
        key: 'status',
        label: t('Status'),
        align: 'center' as const,
        render: (player: TournamentPlayer) => ({
            status: player.status,
            statusDisplay: player.status_display
        })
    }
]);

// Methods
const loadData = async () => {
    isLoading.value = true;
    error.value = null;

    try {
        const [tournamentData, playersData] = await Promise.all([
            apiClient<Tournament>(`/api/tournaments/${props.tournamentId}`),
            apiClient<TournamentPlayer[]>(`/api/tournaments/${props.tournamentId}/players`)
        ]);

        tournament.value = tournamentData;
        players.value = playersData;
        seedingMethod.value = tournament.value.seeding_method || 'random';

        // Initialize manual seeds
        playersData.forEach(player => {
            if (player.seed_number) {
                manualSeeds.value[player.id] = player.seed_number;
            }
        });
    } catch (err: any) {
        error.value = err.message || t('Failed to load tournament data');
    } finally {
        isLoading.value = false;
    }
};

const generateSeeds = async () => {
    isGenerating.value = true;
    error.value = null;
    successMessage.value = null;

    try {
        await apiClient(`/api/admin/tournaments/${props.tournamentId}/seeding/generate`, {
            method: 'POST',
            data: {method: seedingMethod.value}
        });

        successMessage.value = t('Seeds generated successfully');
        await loadData();
    } catch (err: any) {
        error.value = err.message || t('Failed to generate seeds');
    } finally {
        isGenerating.value = false;
    }
};

const saveSeeds = async () => {
    isSaving.value = true;
    error.value = null;
    successMessage.value = null;

    try {
        const seeds = Object.entries(manualSeeds.value).map(([playerId, seed]) => ({
            player_id: parseInt(playerId),
            seed_number: seed
        }));

        await apiClient(`/api/admin/tournaments/${props.tournamentId}/seeding/update`, {
            method: 'POST',
            data: {seeds}
        });

        successMessage.value = t('Seeds saved successfully');
        await loadData();
    } catch (err: any) {
        error.value = err.message || t('Failed to save seeds');
    } finally {
        isSaving.value = false;
    }
};

const proceedToNextStage = async () => {
    if (!confirm(t('Are you sure you want to complete seeding and proceed? This action cannot be undone.'))) {
        return;
    }

    isSaving.value = true;
    error.value = null;

    try {
        await apiClient(`/api/admin/tournaments/${props.tournamentId}/seeding/complete`, {
            method: 'POST'
        });

        // Redirect based on tournament type
        if (tournament.value?.tournament_type && ['groups', 'groups_playoff', 'team_groups_playoff'].includes(tournament.value.tournament_type)) {
            router.visit(`/admin/tournaments/${tournament.value?.slug}/groups`);
        } else {
            router.visit(`/admin/tournaments/${tournament.value?.slug}/bracket`);
        }
    } catch (err: any) {
        error.value = err.message || t('Failed to complete seeding');
        isSaving.value = false;
    }
};

const updateManualSeed = (playerId: number, seed: number) => {
    manualSeeds.value[playerId] = seed;
};

onMounted(() => {
    loadData();
});
</script>

<template>
    <Head :title="tournament ? `${t('Seeding')}: ${tournament.name}` : t('Tournament Seeding')"/>

    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">{{
                            t('Tournament Seeding')
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
                    <Button variant="outline" @click="router.visit(`/tournaments/${tournament?.slug}`)">
                        <ArrowLeftIcon class="mr-2 h-4 w-4"/>
                        {{ t('Back to Tournament') }}
                    </Button>
                </div>
            </div>

            <!-- Error Display -->
            <div v-if="error" class="mb-6 rounded bg-red-100 p-4 text-red-600 dark:bg-red-900/30 dark:text-red-400">
                {{ error }}
            </div>

            <!-- Success Message -->
            <div v-if="successMessage"
                 class="mb-6 rounded bg-green-100 p-4 text-green-600 dark:bg-green-900/30 dark:text-green-400">
                {{ successMessage }}
            </div>

            <!-- Tournament Info -->
            <Card v-if="tournament" class="mb-6">
                <CardContent class="pt-6">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                        <div class="text-center">
                            <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                                {{ confirmedPlayers.length }}
                            </div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">{{ t('Confirmed Players') }}</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-green-600 dark:text-green-400">
                                {{ seededPlayers.length }}
                            </div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">{{ t('Seeded') }}</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">
                                {{ unseededPlayers.length }}
                            </div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">{{ t('Unseeded') }}</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">
                                {{ tournament.tournament_type_display }}
                            </div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">{{ t('Format') }}</div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Seeding Controls -->
            <Card class="mb-6">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <StarIcon class="h-5 w-5"/>
                        {{ t('Seeding Method') }}
                    </CardTitle>
                    <CardDescription>{{ t('Choose how to assign seeds to players') }}</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="flex items-end gap-4">
                        <div class="flex-1">
                            <Label for="seeding_method">{{ t('Method') }}</Label>
                            <Select v-model="seedingMethod">
                                <SelectTrigger>
                                    <SelectValue/>
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="random">
                                        <div class="flex items-center gap-2">
                                            <DicesIcon class="h-4 w-4"/>
                                            {{ t('Random') }}
                                        </div>
                                    </SelectItem>
                                    <SelectItem value="rating">
                                        <div class="flex items-center gap-2">
                                            <StarIcon class="h-4 w-4"/>
                                            {{ t('By Rating') }}
                                        </div>
                                    </SelectItem>
                                    <SelectItem value="manual">
                                        <div class="flex items-center gap-2">
                                            <ArrowUpDownIcon class="h-4 w-4"/>
                                            {{ t('Manual') }}
                                        </div>
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <Button
                            :disabled="isGenerating || confirmedPlayers.length === 0"
                            @click="generateSeeds"
                        >
                            <Spinner v-if="isGenerating" class="mr-2 h-4 w-4"/>
                            {{ t('Generate Seeds') }}
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <!-- Players List -->
            <Card class="mb-6">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <UsersIcon class="h-5 w-5"/>
                        {{ t('Player Seeding') }}
                    </CardTitle>
                    <CardDescription>
                        {{
                            seedingMethod === 'manual' ? t('Drag players or enter seed numbers manually') : t('Review and adjust player seeds')
                        }}
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div v-if="isLoading" class="flex justify-center py-8">
                        <Spinner class="text-primary h-6 w-6"/>
                    </div>
                    <div v-else-if="confirmedPlayers.length === 0" class="py-8 text-center text-gray-500">
                        {{ t('No confirmed players yet.') }}
                    </div>
                    <div v-else>
                        <DataTable
                            :columns="columns"
                            :data="confirmedPlayers"
                            :empty-message="t('No players found')"
                        >
                            <template #cell-seed="{ value }">
                                <div v-if="seedingMethod === 'manual'" class="flex items-center justify-center">
                                    <Input
                                        v-model.number="manualSeeds[value.playerId]"
                                        :max="confirmedPlayers.length"
                                        class="w-20 text-center"
                                        min="1"
                                        type="number"
                                        @change="updateManualSeed(value.playerId, $event.target.value)"
                                    />
                                </div>
                                <div v-else class="text-center">
                                    <span v-if="value.seed"
                                          class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-blue-100 text-sm font-medium text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">
                                        {{ value.seed }}
                                    </span>
                                    <span v-else class="text-gray-400">—</span>
                                </div>
                            </template>

                            <template #cell-player="{ value }">
                                <div>
                                    <p class="font-medium">{{ value.name }}</p>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ value.email }}</p>
                                </div>
                            </template>

                            <template #cell-rating="{ value }">
                                <span class="text-gray-600 dark:text-gray-400">{{ value.rating }}</span>
                            </template>

                            <template #cell-status="{ value }">
                                <span
                                    class="inline-flex items-center gap-1 rounded-full bg-green-100 px-2 py-1 text-xs font-semibold text-green-800 dark:bg-green-900/30 dark:text-green-300">
                                    <CheckCircleIcon class="h-3 w-3"/>
                                    {{ value.statusDisplay }}
                                </span>
                            </template>
                        </DataTable>
                    </div>
                </CardContent>
            </Card>

            <!-- Actions -->
            <Card>
                <CardContent class="pt-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                {{
                                    unseededPlayers.length > 0
                                        ? t(':count players need seeds before proceeding', {count: unseededPlayers.length})
                                        : t('All players have been seeded. Ready to proceed!')
                                }}
                            </p>
                        </div>
                        <div class="flex space-x-3">
                            <Button
                                v-if="seedingMethod === 'manual'"
                                :disabled="isSaving"
                                variant="outline"
                                @click="saveSeeds"
                            >
                                <SaveIcon class="mr-2 h-4 w-4"/>
                                {{ t('Save Seeds') }}
                            </Button>
                            <Button
                                :disabled="!canProceed || isSaving"
                                @click="proceedToNextStage"
                            >
                                <PlayIcon class="mr-2 h-4 w-4"/>
                                {{ t('Complete Seeding & Proceed') }}
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
