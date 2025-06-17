<!-- resources/js/pages/Admin/Tournaments/Groups.vue -->
<script lang="ts" setup>
import {Button, Card, CardContent, CardDescription, CardHeader, CardTitle, Spinner} from '@/Components/ui';
import {useLocale} from '@/composables/useLocale';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import {apiClient} from '@/lib/apiClient';
import type {Tournament, TournamentGroup, TournamentPlayer} from '@/types/api';
import {Head, router} from '@inertiajs/vue3';
import {ArrowLeftIcon, PlayIcon, RefreshCwIcon, UsersIcon} from 'lucide-vue-next';
import {onMounted, ref} from 'vue';

defineOptions({layout: AuthenticatedLayout});

const props = defineProps<{
    tournamentId: number | string;
}>();

const {t} = useLocale();

// Data
const tournament = ref<Tournament | null>(null);
const groups = ref<TournamentGroup[]>([]);
const players = ref<TournamentPlayer[]>([]);

// Loading states
const isLoading = ref(true);
const isGenerating = ref(false);

// Error handling
const error = ref<string | null>(null);
const successMessage = ref<string | null>(null);

// Methods
const loadData = async () => {
    isLoading.value = true;
    error.value = null;

    try {
        const [tournamentData, groupsData, playersData] = await Promise.all([
            apiClient<Tournament>(`/api/tournaments/${props.tournamentId}`),
            apiClient<TournamentGroup[]>(`/api/tournaments/${props.tournamentId}/groups`).catch(() => []),
            apiClient<TournamentPlayer[]>(`/api/tournaments/${props.tournamentId}/players`)
        ]);

        tournament.value = tournamentData;
        groups.value = groupsData;
        players.value = playersData;
    } catch (err: any) {
        error.value = err.message || t('Failed to load tournament data');
    } finally {
        isLoading.value = false;
    }
};

const generateGroups = async () => {
    if (!confirm(t('Are you sure you want to generate groups? This will distribute all confirmed players into groups.'))) {
        return;
    }

    isGenerating.value = true;
    error.value = null;
    successMessage.value = null;

    try {
        await apiClient(`/api/admin/tournaments/${props.tournamentId}/groups/generate`, {
            method: 'POST'
        });

        successMessage.value = t('Groups generated successfully');
        await loadData();
    } catch (err: any) {
        error.value = err.message || t('Failed to generate groups');
    } finally {
        isGenerating.value = false;
    }
};

const proceedToBracket = async () => {
    if (!confirm(t('Are you sure you want to proceed to bracket stage? Make sure all group matches are completed.'))) {
        return;
    }

    try {
        await apiClient(`/api/admin/tournaments/${props.tournamentId}/stage`, {
            method: 'POST',
            data: {stage: 'bracket'}
        });

        router.visit(`/admin/tournaments/${props.tournamentId}/bracket`);
    } catch (err: any) {
        error.value = err.message || t('Failed to proceed to bracket stage');
    }
};

const getPlayersInGroup = (groupCode: string) => {
    return players.value.filter(p => p.group_code === groupCode);
};

onMounted(() => {
    loadData();
});
</script>

<template>
    <Head :title="tournament ? `${t('Groups')}: ${tournament.name}` : t('Tournament Groups')"/>

    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">{{
                            t('Tournament Groups')
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

            <!-- Error Display -->
            <div v-if="error" class="mb-6 rounded bg-red-100 p-4 text-red-600 dark:bg-red-900/30 dark:text-red-400">
                {{ error }}
            </div>

            <!-- Success Message -->
            <div v-if="successMessage"
                 class="mb-6 rounded bg-green-100 p-4 text-green-600 dark:bg-green-900/30 dark:text-green-400">
                {{ successMessage }}
            </div>

            <!-- Generate Groups Card -->
            <Card v-if="groups.length === 0 && !isLoading" class="mb-6">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <UsersIcon class="h-5 w-5"/>
                        {{ t('Generate Tournament Groups') }}
                    </CardTitle>
                    <CardDescription>
                        {{ t('Distribute players into groups for round-robin play') }}
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="text-center py-8">
                        <p class="mb-4 text-gray-600 dark:text-gray-400">
                            {{ t('Groups have not been generated yet. Click below to create them.') }}
                        </p>
                        <Button
                            :disabled="isGenerating"
                            size="lg"
                            @click="generateGroups"
                        >
                            <Spinner v-if="isGenerating" class="mr-2 h-4 w-4"/>
                            {{ t('Generate Groups') }}
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <!-- Groups Display -->
            <div v-if="groups.length > 0 && !isLoading" class="space-y-6">
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                    <Card v-for="group in groups" :key="group.id">
                        <CardHeader>
                            <CardTitle>{{ t('Group :code', {code: group.group_code}) }}</CardTitle>
                            <CardDescription>
                                {{ t(':count players', {count: group.group_size}) }} •
                                {{ t(':count advance', {count: group.advance_count}) }}
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-2">
                                <div
                                    v-for="player in getPlayersInGroup(group.group_code)"
                                    :key="player.id"
                                    class="flex items-center justify-between p-2 rounded hover:bg-gray-50 dark:hover:bg-gray-800"
                                >
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm font-medium text-gray-500">
                                            {{ player.seed_number }}
                                        </span>
                                        <span>{{ player.user?.firstname }} {{ player.user?.lastname }}</span>
                                    </div>
                                    <div class="text-sm text-gray-600 dark:text-gray-400">
                                        <span v-if="player.group_wins > 0 || player.group_losses > 0">
                                            {{ player.group_wins }}W - {{ player.group_losses }}L
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Actions -->
                <Card>
                    <CardContent class="pt-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    {{
                                        tournament?.tournament_type === 'groups_playoff'
                                            ? t('Groups are ready. Start group matches or proceed to playoff bracket.')
                                            : t('Groups are ready. Start group matches.')
                                    }}
                                </p>
                            </div>
                            <div class="flex space-x-3">
                                <Button
                                    variant="outline"
                                    @click="router.visit(`/admin/tournaments/${props.tournamentId}/matches`)"
                                >
                                    {{ t('Manage Matches') }}
                                </Button>
                                <Button
                                    v-if="tournament?.tournament_type === 'groups_playoff'"
                                    @click="proceedToBracket"
                                >
                                    <PlayIcon class="mr-2 h-4 w-4"/>
                                    {{ t('Proceed to Playoff') }}
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </div>
</template>
