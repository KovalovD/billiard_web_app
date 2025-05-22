// resources/js/pages/Admin/OfficialRatings/Tournaments.vue
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
    Modal,
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
    Spinner
} from '@/Components/ui';
import {useOfficialRatings} from '@/composables/useOfficialRatings';
import {useTournaments} from '@/composables/useTournaments';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import type {OfficialRating, OfficialRatingTournament, Tournament} from '@/types/api';
import {Head, Link} from '@inertiajs/vue3';
import {
    ArrowLeftIcon,
    CalendarIcon,
    MapPinIcon,
    PlusIcon,
    RefreshCwIcon,
    TrashIcon,
    TrophyIcon,
    UsersIcon
} from 'lucide-vue-next';
import {computed, onMounted, ref, watch} from 'vue';

defineOptions({layout: AuthenticatedLayout});

const props = defineProps<{
    ratingId: number | string;
}>();

const officialRatingsApi = useOfficialRatings();
const tournamentsApi = useTournaments();

// State
const rating = ref<OfficialRating | null>(null);
const tournaments = ref<OfficialRatingTournament[]>([]);
const availableTournaments = ref<Tournament[]>([]);
const isLoading = ref(true);
const isLoadingTournaments = ref(false);
const error = ref<string | null>(null);

// Modal states
const showAddModal = ref(false);
const showUpdateModal = ref(false);
const selectedTournament = ref<OfficialRatingTournament | null>(null);

// Form data
const addForm = ref({
    tournament_id: '',
    rating_coefficient: 1.0,
    is_counting: true
});

const updateForm = ref({
    rating_coefficient: 1.0,
    is_counting: true
});

// API composables
const fetchRatingApi = officialRatingsApi.fetchOfficialRating(props.ratingId);
const fetchTournamentsApi = officialRatingsApi.fetchRatingTournaments(props.ratingId);
const fetchAvailableTournamentsApi = tournamentsApi.fetchTournaments();
const addTournamentApi = officialRatingsApi.addTournamentToRating(props.ratingId);
const removeTournamentApi = officialRatingsApi.removeTournamentFromRating(props.ratingId);
const updateFromTournamentApi = officialRatingsApi.updateRatingFromTournament(props.ratingId);
const recalculateApi = officialRatingsApi.recalculateRatingPositions(props.ratingId);

// Computed
const activeTournaments = computed(() => {
    return tournaments.value.filter(t => t.is_counting);
});

const inactiveTournaments = computed(() => {
    return tournaments.value.filter(t => !t.is_counting);
});

const filteredAvailableTournaments = computed(() => {
    const existingIds = tournaments.value.map(t => t.id);
    return availableTournaments.value.filter(t =>
        !existingIds.includes(t.id) &&
        t.game?.id === rating.value?.game?.id
    );
});

// Methods
const fetchData = async () => {
    isLoading.value = true;
    error.value = null;

    try {
        await Promise.all([
            fetchRatingApi.execute(),
            fetchTournamentsData(),
            fetchAvailableTournamentsApi.execute()
        ]);

        rating.value = fetchRatingApi.data.value;
        availableTournaments.value = fetchAvailableTournamentsApi.data.value || [];
    } catch (err: any) {
        error.value = err.message || 'Failed to load data';
    } finally {
        isLoading.value = false;
    }
};

const fetchTournamentsData = async () => {
    isLoadingTournaments.value = true;
    try {
        await fetchTournamentsApi.execute();
        tournaments.value = fetchTournamentsApi.data.value || [];
    } catch (err: any) {
        console.error('Failed to load tournaments:', err);
    } finally {
        isLoadingTournaments.value = false;
    }
};

const openAddModal = () => {
    addForm.value = {
        tournament_id: '',
        rating_coefficient: 1.0,
        is_counting: true
    };
    showAddModal.value = true;
};

const openUpdateModal = (tournament: OfficialRatingTournament) => {
    selectedTournament.value = tournament;
    updateForm.value = {
        rating_coefficient: tournament.rating_coefficient,
        is_counting: tournament.is_counting
    };
    showUpdateModal.value = true;
};

const addTournament = async () => {
    if (!addForm.value.tournament_id) return;

    const success = await addTournamentApi.execute({
        tournament_id: parseInt(addForm.value.tournament_id),
        rating_coefficient: addForm.value.rating_coefficient,
        is_counting: addForm.value.is_counting
    });

    if (success) {
        showAddModal.value = false;
        await fetchTournamentsData();
    }
};

const removeTournament = async (tournamentId: number) => {
    if (!confirm('Are you sure you want to remove this tournament from the rating?')) return;

    const success = await removeTournamentApi.execute(tournamentId);

    if (success) {
        await fetchTournamentsData();
    }
};

const updateFromTournament = async (tournamentId: number) => {
    if (!confirm('This will update player ratings based on tournament results. Continue?')) return;

    const success = await updateFromTournamentApi.execute(tournamentId);

    if (success) {
        // Refresh data after update
        await fetchData();
    }
};

const recalculatePositions = async () => {
    if (!confirm('This will recalculate all player positions. Continue?')) return;

    const success = await recalculateApi.execute();

    if (success) {
        // Show success message or refresh data if needed
        alert('Positions recalculated successfully');
    }
};

const formatDate = (dateString: string): string => {
    return new Date(dateString).toLocaleDateString();
};

const getTournamentStatusClass = (tournament: OfficialRatingTournament): string => {
    if (!tournament.is_counting) {
        return 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300';
    }

    switch (tournament.status) {
        case 'completed':
            return 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300';
        case 'active':
            return 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300';
        case 'upcoming':
            return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300';
        default:
            return 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300';
    }
};

// Watchers
watch(() => props.ratingId, fetchData, {immediate: false});

onMounted(fetchData);
</script>

<template>
    <Head :title="rating ? `Manage Tournaments - ${rating.name}` : 'Manage Tournaments'"/>

    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-6 flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Link :href="`/admin/official-ratings/${ratingId}/manage`">
                        <Button variant="outline">
                            <ArrowLeftIcon class="mr-2 h-4 w-4"/>
                            Back to Rating
                        </Button>
                    </Link>
                    <div>
                        <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">
                            Manage Tournaments
                        </h1>
                        <p v-if="rating" class="text-gray-600 dark:text-gray-400">
                            {{ rating.name }}
                        </p>
                    </div>
                </div>

                <div class="flex space-x-2">
                    <Button :disabled="recalculateApi.isActing.value" variant="outline" @click="recalculatePositions">
                        <RefreshCwIcon class="mr-2 h-4 w-4"/>
                        Recalculate Positions
                    </Button>
                    <Button @click="openAddModal">
                        <PlusIcon class="mr-2 h-4 w-4"/>
                        Add Tournament
                    </Button>
                </div>
            </div>

            <!-- Loading State -->
            <div v-if="isLoading" class="flex items-center justify-center py-10">
                <Spinner class="text-primary h-8 w-8"/>
                <span class="ml-2 text-gray-500 dark:text-gray-400">Loading tournaments...</span>
            </div>

            <!-- Error State -->
            <div v-else-if="error" class="mb-6 rounded bg-red-100 p-4 text-red-500">
                Error: {{ error }}
            </div>

            <!-- Content -->
            <template v-else>
                <!-- Stats Cards -->
                <div class="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <Card>
                        <CardContent class="p-6">
                            <div class="flex items-center">
                                <TrophyIcon class="h-8 w-8 text-blue-500"/>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total
                                        Tournaments</p>
                                    <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                                        {{ tournaments.length }}
                                    </p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent class="p-6">
                            <div class="flex items-center">
                                <CalendarIcon class="h-8 w-8 text-green-500"/>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Counting
                                        Tournaments</p>
                                    <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                                        {{ activeTournaments.length }}
                                    </p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent class="p-6">
                            <div class="flex items-center">
                                <UsersIcon class="h-8 w-8 text-purple-500"/>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Players</p>
                                    <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                                        {{ rating?.players_count || 0 }}
                                    </p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Tournaments List -->
                <Card>
                    <CardHeader>
                        <CardTitle>Associated Tournaments</CardTitle>
                        <CardDescription>
                            Tournaments that are associated with this rating system
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div v-if="isLoadingTournaments" class="flex justify-center py-8">
                            <Spinner class="text-primary h-6 w-6"/>
                        </div>
                        <div v-else-if="tournaments.length === 0" class="py-8 text-center text-gray-500">
                            No tournaments associated with this rating yet.
                        </div>
                        <div v-else class="space-y-4">
                            <div
                                v-for="tournament in tournaments"
                                :key="tournament.id"
                                class="border rounded-lg p-4 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors"
                            >
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2 mb-2">
                                            <h3 class="font-medium text-gray-900 dark:text-gray-100">
                                                {{ tournament.name }}
                                            </h3>
                                            <span
                                                :class="[
                                                    'px-2 py-1 text-xs rounded-full',
                                                    getTournamentStatusClass(tournament)
                                                ]"
                                            >
                                                {{ tournament.is_counting ? 'Counting' : 'Not Counting' }}
                                            </span>
                                        </div>

                                        <div class="flex flex-wrap gap-4 text-sm text-gray-600 dark:text-gray-400">
                                            <span class="flex items-center gap-1">
                                                <CalendarIcon class="h-3 w-3"/>
                                                {{ formatDate(tournament.start_date) }}
                                                <span v-if="tournament.end_date !== tournament.start_date">
                                                    - {{ formatDate(tournament.end_date) }}
                                                </span>
                                            </span>
                                            <span v-if="tournament.city" class="flex items-center gap-1">
                                                <MapPinIcon class="h-3 w-3"/>
                                                {{ tournament.city }}, {{ tournament.country }}
                                            </span>
                                            <span class="flex items-center gap-1">
                                                <UsersIcon class="h-3 w-3"/>
                                                {{ tournament.players_count }} players
                                            </span>
                                        </div>
                                    </div>

                                    <div class="flex flex-col items-end gap-2">
                                        <div class="text-right">
                                            <div class="text-sm font-medium">
                                                Coefficient: {{ tournament.rating_coefficient }}x
                                            </div>
                                        </div>

                                        <div class="flex gap-2">
                                            <Button
                                                v-if="tournament.status === 'completed' && tournament.is_counting"
                                                :disabled="updateFromTournamentApi.isActing.value"
                                                size="sm"
                                                variant="outline"
                                                @click="updateFromTournament(tournament.id)"
                                            >
                                                Update Rating
                                            </Button>
                                            <Button
                                                :disabled="removeTournamentApi.isActing.value"
                                                size="sm"
                                                variant="destructive"
                                                @click="removeTournament(tournament.id)"
                                            >
                                                <TrashIcon class="h-4 w-4"/>
                                            </Button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </template>
        </div>
    </div>

    <!-- Add Tournament Modal -->
    <Modal :show="showAddModal" title="Add Tournament to Rating" @close="showAddModal = false">
        <div class="space-y-4">
            <div>
                <Label for="tournament">Tournament</Label>
                <Select v-model="addForm.tournament_id">
                    <SelectTrigger>
                        <SelectValue placeholder="Select a tournament"/>
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem
                            v-for="tournament in filteredAvailableTournaments"
                            :key="tournament.id"
                            :value="tournament.id.toString()"
                        >
                            {{ tournament.name }} ({{ formatDate(tournament.start_date) }})
                        </SelectItem>
                    </SelectContent>
                </Select>
            </div>

            <div>
                <Label for="coefficient">Rating Coefficient</Label>
                <Input
                    id="coefficient"
                    v-model.number="addForm.rating_coefficient"
                    max="5.0"
                    min="0.1"
                    placeholder="1.0"
                    step="0.1"
                    type="number"
                />
                <p class="mt-1 text-xs text-gray-500">
                    Multiplier for rating points earned from this tournament
                </p>
            </div>

            <div class="flex items-center space-x-2">
                <input
                    id="counting"
                    v-model="addForm.is_counting"
                    class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                    type="checkbox"
                />
                <Label for="counting">Count towards rating</Label>
            </div>
        </div>

        <template #footer>
            <Button variant="outline" @click="showAddModal = false">Cancel</Button>
            <Button
                :disabled="!addForm.tournament_id || addTournamentApi.isActing.value"
                @click="addTournament"
            >
                <span v-if="addTournamentApi.isActing.value">Adding...</span>
                <span v-else>Add Tournament</span>
            </Button>
        </template>
    </Modal>
</template>
