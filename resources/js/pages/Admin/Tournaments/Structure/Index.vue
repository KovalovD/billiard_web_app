<!-- resources/js/pages/Admin/Tournaments/Structure/Index.vue -->
<script lang="ts" setup>
import {
    Alert,
    AlertDescription,
    Button,
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
    Spinner
} from '@/Components/ui';
import {useTournamentStructure} from '@/composables/useTournamentStructure';
import {useTournaments} from '@/composables/useTournaments';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import {useLocale} from '@/composables/useLocale';
import type {Tournament, TournamentStructureOverview} from '@/types/tournament';
import {Head, Link} from '@inertiajs/vue3';
import {
    AlertTriangle,
    ArrowLeftIcon,
    Calendar,
    GitBranch,
    Layers,
    PlayCircle,
    RefreshCw,
    Trophy,
    Users
} from 'lucide-vue-next';
import {computed, onMounted, ref} from 'vue';

defineOptions({layout: AuthenticatedLayout});

const props = defineProps<{
    tournamentId: number | string;
}>();

const {t} = useLocale();
const {fetchTournament} = useTournaments();
const {fetchStructureOverview, initializeStructure, resetStructure} = useTournamentStructure();

const tournament = ref<Tournament | null>(null);
const structure = ref<TournamentStructureOverview | null>(null);
const isLoading = ref(true);
const isInitializing = ref(false);
const isResetting = ref(false);
const error = ref<string | null>(null);

const tournamentApi = fetchTournament(props.tournamentId);
const structureApi = fetchStructureOverview(props.tournamentId);
const initializeApi = initializeStructure(props.tournamentId);
const resetApi = resetStructure(props.tournamentId);

const canInitialize = computed(() => {
    return tournament.value &&
        tournament.value.status === 'upcoming' &&
        tournament.value.confirmed_players_count >= 2 &&
        !structure.value?.tournament.is_initialized;
});

const canReset = computed(() => {
    return tournament.value &&
        tournament.value.status === 'upcoming' &&
        structure.value?.tournament.is_initialized;
});

const loadData = async () => {
    isLoading.value = true;
    error.value = null;

    try {
        await Promise.all([
            tournamentApi.execute(),
            structureApi.execute()
        ]);

        if (tournamentApi.data.value) {
            tournament.value = tournamentApi.data.value;
        }
        if (structureApi.data.value) {
            structure.value = structureApi.data.value;
        }
    } catch (err: any) {
        error.value = err.message || 'Failed to load tournament data';
    } finally {
        isLoading.value = false;
    }
};

const handleInitialize = async () => {
    if (!confirm(t('Initialize tournament structure? This will create groups/brackets based on current settings.'))) {
        return;
    }

    isInitializing.value = true;
    const success = await initializeApi.execute();

    if (success) {
        await loadData();
    }

    isInitializing.value = false;
};

const handleReset = async () => {
    if (!confirm(t('Reset tournament structure? This will delete all groups, brackets, and matches. This action cannot be undone!'))) {
        return;
    }

    isResetting.value = true;
    const success = await resetApi.execute();

    if (success) {
        await loadData();
    }

    isResetting.value = false;
};

const getFormatIcon = (format: string) => {
    switch (format) {
        case 'single_elimination':
        case 'double_elimination':
            return GitBranch;
        case 'group_stage':
        case 'group_playoff':
            return Layers;
        case 'round_robin':
            return Users;
        default:
            return Trophy;
    }
};

onMounted(() => {
    loadData();
});
</script>

<template>
    <Head :title="tournament ? `${t('Tournament Structure')}: ${tournament.name}` : t('Tournament Structure')"/>

    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">{{
                            t('Tournament Structure')
                        }}</h1>
                    <p class="text-gray-600 dark:text-gray-400">
                        {{ tournament ? tournament.name : t('Loading...') }}
                    </p>
                </div>
                <div class="flex space-x-3">
                    <Link :href="`/tournaments/${props.tournamentId}`">
                        <Button variant="outline">
                            <ArrowLeftIcon class="mr-2 h-4 w-4"/>
                            {{ t('Back to Tournament') }}
                        </Button>
                    </Link>
                </div>
            </div>

            <!-- Loading State -->
            <div v-if="isLoading" class="flex items-center justify-center py-12">
                <Spinner class="h-8 w-8 text-primary"/>
                <span class="ml-2 text-gray-500 dark:text-gray-400">{{ t('Loading structure...') }}</span>
            </div>

            <!-- Error State -->
            <Alert v-else-if="error" class="mb-6" variant="destructive">
                <AlertTriangle class="h-4 w-4"/>
                <AlertDescription>{{ error }}</AlertDescription>
            </Alert>

            <!-- Content -->
            <template v-else-if="tournament && structure">
                <!-- Tournament Info Card -->
                <Card class="mb-6">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <component :is="getFormatIcon(structure.tournament.format)" class="h-5 w-5"/>
                            {{ structure.tournament.format_display }}
                        </CardTitle>
                        <CardDescription>
                            {{ t('Seeding') }}: {{
                                structure.tournament.seeding_method === 'manual' ? t('Manual') :
                                    structure.tournament.seeding_method === 'random' ? t('Random') : t('Rating-Based')
                            }}
                            <span v-if="structure.tournament.is_team_tournament" class="ml-2">
                                • {{ t('Team Tournament') }}
                            </span>
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                            <div class="text-center">
                                <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                                    {{ structure.participants.total_confirmed }}
                                </div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">{{ t('Confirmed Players') }}</div>
                            </div>
                            <div v-if="structure.tournament.is_team_tournament" class="text-center">
                                <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">
                                    {{ structure.participants.teams_count }}
                                </div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">{{ t('Teams') }}</div>
                            </div>
                            <div v-if="structure.structure.has_groups" class="text-center">
                                <div class="text-2xl font-bold text-green-600 dark:text-green-400">
                                    {{ structure.structure.groups_count }}
                                </div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">{{ t('Groups') }}</div>
                            </div>
                            <div v-if="structure.structure.has_brackets" class="text-center">
                                <div class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">
                                    {{ structure.structure.brackets_count }}
                                </div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">{{ t('Brackets') }}</div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Initialize/Reset Actions -->
                <Card v-if="!structure.tournament.is_initialized"
                      class="mb-6 border-yellow-200 bg-yellow-50 dark:border-yellow-800 dark:bg-yellow-900/20">
                    <CardHeader>
                        <CardTitle>{{ t('Tournament Not Initialized') }}</CardTitle>
                        <CardDescription>
                            {{ t('The tournament structure needs to be initialized before matches can be scheduled.') }}
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Button
                            :disabled="!canInitialize || isInitializing"
                            class="w-full sm:w-auto"
                            @click="handleInitialize"
                        >
                            <PlayCircle v-if="!isInitializing" class="mr-2 h-4 w-4"/>
                            <Spinner v-else class="mr-2 h-4 w-4"/>
                            {{ isInitializing ? t('Initializing...') : t('Initialize Structure') }}
                        </Button>
                        <p v-if="tournament.confirmed_players_count < 2"
                           class="mt-2 text-sm text-red-600 dark:text-red-400">
                            {{ t('At least 2 confirmed players are required to initialize the tournament.') }}
                        </p>
                    </CardContent>
                </Card>

                <!-- Progress Overview -->
                <Card v-if="structure.tournament.is_initialized" class="mb-6">
                    <CardHeader>
                        <CardTitle>{{ t('Tournament Progress') }}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-4">
                            <!-- Match Progress -->
                            <div>
                                <div class="flex justify-between text-sm mb-1">
                                    <span>{{ t('Matches') }}</span>
                                    <span>{{ structure.progress.matches.completed }}/{{
                                            structure.progress.matches.total
                                        }}</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2 dark:bg-gray-700">
                                    <div
                                        :style="`width: ${structure.progress.matches.total > 0 ? (structure.progress.matches.completed / structure.progress.matches.total * 100) : 0}%`"
                                        class="bg-blue-600 h-2 rounded-full transition-all"
                                    ></div>
                                </div>
                            </div>

                            <!-- Match Stats -->
                            <div class="grid grid-cols-3 gap-4 text-center">
                                <div>
                                    <div class="text-lg font-semibold text-green-600 dark:text-green-400">
                                        {{ structure.progress.matches.completed }}
                                    </div>
                                    <div class="text-sm text-gray-600 dark:text-gray-400">{{ t('Completed') }}</div>
                                </div>
                                <div>
                                    <div class="text-lg font-semibold text-blue-600 dark:text-blue-400">
                                        {{ structure.progress.matches.in_progress }}
                                    </div>
                                    <div class="text-sm text-gray-600 dark:text-gray-400">{{ t('In Progress') }}</div>
                                </div>
                                <div>
                                    <div class="text-lg font-semibold text-gray-600 dark:text-gray-400">
                                        {{ structure.progress.matches.pending }}
                                    </div>
                                    <div class="text-sm text-gray-600 dark:text-gray-400">{{ t('Pending') }}</div>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Structure Management -->
                <div v-if="structure.tournament.is_initialized"
                     class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                    <!-- Groups Card -->
                    <Card v-if="structure.structure.has_groups">
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <Layers class="h-5 w-5"/>
                                {{ t('Groups') }}
                            </CardTitle>
                            <CardDescription>
                                {{ t(':count groups configured', {count: structure.structure.groups_count}) }}
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <Link :href="`/admin/tournaments/${props.tournamentId}/structure/groups`">
                                <Button class="w-full" variant="outline">
                                    {{ t('Manage Groups') }}
                                </Button>
                            </Link>
                        </CardContent>
                    </Card>

                    <!-- Brackets Card -->
                    <Card v-if="structure.structure.has_brackets">
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <GitBranch class="h-5 w-5"/>
                                {{ t('Brackets') }}
                            </CardTitle>
                            <CardDescription>
                                {{ t(':count brackets configured', {count: structure.structure.brackets_count}) }}
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <Link :href="`/admin/tournaments/${props.tournamentId}/structure/brackets`">
                                <Button class="w-full" variant="outline">
                                    {{ t('View Brackets') }}
                                </Button>
                            </Link>
                        </CardContent>
                    </Card>

                    <!-- Schedule Card -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <Calendar class="h-5 w-5"/>
                                {{ t('Match Schedule') }}
                            </CardTitle>
                            <CardDescription>
                                {{ t(':count matches scheduled', {count: structure.progress.matches.total}) }}
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <Link :href="`/admin/tournaments/${props.tournamentId}/structure/schedule`">
                                <Button class="w-full" variant="outline">
                                    {{ t('Manage Schedule') }}
                                </Button>
                            </Link>
                        </CardContent>
                    </Card>
                </div>

                <!-- Reset Button -->
                <div v-if="canReset" class="mt-6 flex justify-end">
                    <Button
                        :disabled="isResetting"
                        variant="destructive"
                        @click="handleReset"
                    >
                        <RefreshCw v-if="!isResetting" class="mr-2 h-4 w-4"/>
                        <Spinner v-else class="mr-2 h-4 w-4"/>
                        {{ isResetting ? t('Resetting...') : t('Reset Structure') }}
                    </Button>
                </div>
            </template>
        </div>
    </div>
</template>
