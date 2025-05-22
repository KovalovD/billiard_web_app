// resources/js/pages/Admin/Tournaments/Edit.vue
<script lang="ts" setup>
import {
    Button,
    Card,
    CardContent,
    CardHeader,
    CardTitle,
    Input,
    Label,
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
    Spinner,
    Textarea
} from '@/Components/ui';
import {useProfileApi} from '@/composables/useProfileApi';
import {useTournaments} from '@/composables/useTournaments';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import {apiClient} from '@/lib/apiClient';
import type {City, Club, Game, Tournament} from '@/types/api';
import {Head, router} from '@inertiajs/vue3';
import {ArrowLeftIcon, MapPinIcon, TrophyIcon} from 'lucide-vue-next';
import {computed, onMounted, ref, watch} from 'vue';

defineOptions({layout: AuthenticatedLayout});

const props = defineProps<{
    tournamentId: number | string;
}>();

const {updateTournament, fetchTournament} = useTournaments();
const {fetchCities, fetchClubs} = useProfileApi();

// Form data
const form = ref({
    name: '',
    regulation: '',
    details: '',
    game_id: 0,
    city_id: undefined as number | undefined,
    club_id: undefined as number | undefined,
    start_date: '',
    end_date: '',
    max_participants: undefined as number | undefined,
    entry_fee: 0,
    prize_pool: 0,
    organizer: '',
    format: '',
    status: 'upcoming' as 'upcoming' | 'active' | 'completed' | 'cancelled',
});

// Data
const tournament = ref<Tournament | null>(null);
const games = ref<Game[]>([]);
const cities = ref<City[]>([]);
const clubs = ref<Club[]>([]);
const filteredClubs = ref<Club[]>([]);

// Loading states
const isLoadingTournament = ref(true);
const isLoadingGames = ref(true);
const isSubmitting = ref(false);

// API calls
const tournamentApi = fetchTournament(props.tournamentId);
const citiesApi = fetchCities();
const clubsApi = fetchClubs();
const updateApi = updateTournament(props.tournamentId);

// Computed
const isFormValid = computed(() => {
    return form.value.name.trim() !== '' &&
        form.value.game_id > 0 &&
        form.value.start_date !== '' &&
        form.value.end_date !== '';
});

const statusOptions = [
    {value: 'upcoming', label: 'Upcoming'},
    {value: 'active', label: 'Active'},
    {value: 'completed', label: 'Completed'},
    {value: 'cancelled', label: 'Cancelled'}
];

// Watch for city changes to filter clubs
watch(() => form.value.city_id, (newCityId) => {
    if (newCityId !== tournament.value?.city?.id) {
        form.value.club_id = undefined;
    }

    if (newCityId) {
        filteredClubs.value = clubs.value.filter(club =>
            club.city === cities.value.find(city => city.id === newCityId)?.name
        );
    } else {
        filteredClubs.value = [];
    }
});

const fetchGames = async () => {
    isLoadingGames.value = true;
    try {
        games.value = await apiClient<Game[]>('/api/available-games');
    } catch (error) {
        console.error('Failed to load games:', error);
    } finally {
        isLoadingGames.value = false;
    }
};

const loadTournament = async () => {
    isLoadingTournament.value = true;

    const success = await tournamentApi.execute();

    if (success && tournamentApi.data.value) {
        tournament.value = tournamentApi.data.value;

        // Populate form with tournament data
        form.value = {
            name: tournament.value.name,
            regulation: tournament.value.regulation || '',
            details: tournament.value.details || '',
            game_id: tournament.value.game?.id || 0,
            city_id: tournament.value.city?.id,
            club_id: tournament.value.club?.id,
            start_date: tournament.value.start_date,
            end_date: tournament.value.end_date,
            max_participants: tournament.value.max_participants || undefined,
            entry_fee: tournament.value.entry_fee || 0,
            prize_pool: tournament.value.prize_pool || 0,
            organizer: tournament.value.organizer || '',
            format: tournament.value.format || '',
            status: tournament.value.status as any,
        };
    }

    isLoadingTournament.value = false;
};

const loadCitiesAndClubs = async () => {
    await Promise.all([
        citiesApi.execute(),
        clubsApi.execute()
    ]);

    if (citiesApi.data.value) cities.value = citiesApi.data.value;
    if (clubsApi.data.value) clubs.value = clubsApi.data.value;

    // Filter clubs after loading
    if (form.value.city_id) {
        filteredClubs.value = clubs.value.filter(club =>
            club.city === cities.value.find(city => city.id === form.value.city_id)?.name
        );
    }
};

const handleSubmit = async () => {
    if (!isFormValid.value) return;

    isSubmitting.value = true;

    const success = await updateApi.execute(form.value);

    if (success) {
        router.visit(`/tournaments/${props.tournamentId}`);
    }

    isSubmitting.value = false;
};

const handleCancel = () => {
    router.visit(`/tournaments/${props.tournamentId}`);
};

const formatDate = (dateString: string): string => {
    return dateString;
};

onMounted(() => {
    loadTournament();
    fetchGames();
    loadCitiesAndClubs();
});
</script>

<template>
    <Head :title="tournament ? `Edit: ${tournament.name}` : 'Edit Tournament'"/>

    <div class="py-12">
        <div class="mx-auto max-w-4xl sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">Edit Tournament</h1>
                    <p class="text-gray-600 dark:text-gray-400">
                        {{ tournament ? tournament.name : 'Loading...' }}
                    </p>
                </div>
                <Button variant="outline" @click="handleCancel">
                    <ArrowLeftIcon class="mr-2 h-4 w-4"/>
                    Back to Tournament
                </Button>
            </div>

            <!-- Loading State -->
            <div v-if="isLoadingTournament" class="flex items-center justify-center py-10">
                <Spinner class="text-primary h-8 w-8"/>
                <span class="ml-2 text-gray-500 dark:text-gray-400">Loading tournament...</span>
            </div>

            <!-- Error State -->
            <div v-else-if="tournamentApi.error.value"
                 class="rounded bg-red-100 p-4 text-red-600 dark:bg-red-900/30 dark:text-red-400">
                Error loading tournament: {{ tournamentApi.error.value.message }}
            </div>

            <!-- Main Form -->
            <Card v-else>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <TrophyIcon class="h-5 w-5"/>
                        Tournament Details
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <form class="space-y-6" @submit.prevent="handleSubmit">
                        <!-- Status and Basic Information -->
                        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                            <div class="space-y-2">
                                <Label for="status">Status</Label>
                                <Select v-model="form.status">
                                    <SelectTrigger>
                                        <SelectValue/>
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem
                                            v-for="option in statusOptions"
                                            :key="option.value"
                                            :value="option.value"
                                        >
                                            {{ option.label }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <div class="space-y-2 lg:col-span-2">
                                <Label for="name">Tournament Name *</Label>
                                <Input
                                    id="name"
                                    v-model="form.name"
                                    placeholder="Enter tournament name"
                                    required
                                />
                            </div>
                        </div>

                        <!-- Game Type and Dates -->
                        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                            <div class="space-y-2">
                                <Label for="game_id">Game Type *</Label>
                                <Select v-model="form.game_id" required>
                                    <SelectTrigger>
                                        <SelectValue placeholder="Select game type"/>
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-if="isLoadingGames" :value="0">
                                            Loading games...
                                        </SelectItem>
                                        <SelectItem
                                            v-for="game in games"
                                            v-else
                                            :key="game.id"
                                            :value="game.id"
                                        >
                                            {{ game.name }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <div class="space-y-2">
                                <Label for="start_date">Start Date *</Label>
                                <Input
                                    id="start_date"
                                    v-model="form.start_date"
                                    required
                                    type="date"
                                />
                            </div>

                            <div class="space-y-2">
                                <Label for="end_date">End Date *</Label>
                                <Input
                                    id="end_date"
                                    v-model="form.end_date"
                                    required
                                    type="date"
                                />
                            </div>
                        </div>

                        <!-- Location -->
                        <div class="space-y-4">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 flex items-center gap-2">
                                <MapPinIcon class="h-5 w-5"/>
                                Location
                            </h3>

                            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                                <div class="space-y-2">
                                    <Label for="city_id">City</Label>
                                    <Select v-model="form.city_id">
                                        <SelectTrigger>
                                            <SelectValue placeholder="Select city"/>
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem
                                                v-for="city in cities"
                                                :key="city.id"
                                                :value="city.id"
                                            >
                                                {{ city.name }}, {{ city.country.name }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div class="space-y-2">
                                    <Label for="club_id">Club</Label>
                                    <Select v-model="form.club_id" :disabled="!form.city_id">
                                        <SelectTrigger>
                                            <SelectValue placeholder="Select club"/>
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem
                                                v-for="club in filteredClubs"
                                                :key="club.id"
                                                :value="club.id"
                                            >
                                                {{ club.name }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                            </div>
                        </div>

                        <!-- Financial Information -->
                        <div class="space-y-4">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Financial Details</h3>

                            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                                <div class="space-y-2">
                                    <Label for="entry_fee">Entry Fee (₴)</Label>
                                    <Input
                                        id="entry_fee"
                                        v-model.number="form.entry_fee"
                                        min="0"
                                        step="0.01"
                                        type="number"
                                    />
                                </div>

                                <div class="space-y-2">
                                    <Label for="prize_pool">Prize Pool (₴)</Label>
                                    <Input
                                        id="prize_pool"
                                        v-model.number="form.prize_pool"
                                        min="0"
                                        step="0.01"
                                        type="number"
                                    />
                                </div>

                                <div class="space-y-2">
                                    <Label for="max_participants">Max Participants</Label>
                                    <Input
                                        id="max_participants"
                                        v-model.number="form.max_participants"
                                        min="2"
                                        placeholder="Unlimited"
                                        type="number"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- Additional Details -->
                        <div class="space-y-4">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Additional Information</h3>

                            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                                <div class="space-y-2">
                                    <Label for="organizer">Organizer</Label>
                                    <Input
                                        id="organizer"
                                        v-model="form.organizer"
                                        placeholder="Tournament organizer"
                                    />
                                </div>

                                <div class="space-y-2">
                                    <Label for="format">Format</Label>
                                    <Input
                                        id="format"
                                        v-model="form.format"
                                        placeholder="e.g., Single Elimination, Round Robin"
                                    />
                                </div>
                            </div>

                            <div class="space-y-2">
                                <Label for="details">Description</Label>
                                <Textarea
                                    id="details"
                                    v-model="form.details"
                                    placeholder="Tournament description and additional details"
                                    rows="3"
                                />
                            </div>

                            <div class="space-y-2">
                                <Label for="regulation">Regulation</Label>
                                <Textarea
                                    id="regulation"
                                    v-model="form.regulation"
                                    placeholder="Tournament rules and regulations"
                                    rows="4"
                                />
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="flex justify-end space-x-4 border-t pt-6">
                            <Button type="button" variant="outline" @click="handleCancel">
                                Cancel
                            </Button>
                            <Button
                                :disabled="!isFormValid || isSubmitting"
                                type="submit"
                            >
                                <Spinner v-if="isSubmitting" class="mr-2 h-4 w-4"/>
                                {{ isSubmitting ? 'Updating...' : 'Update Tournament' }}
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>

            <!-- Error Display -->
            <div v-if="updateApi.error.value"
                 class="mt-4 rounded bg-red-100 p-4 text-red-600 dark:bg-red-900/30 dark:text-red-400">
                Error updating tournament: {{ updateApi.error.value.message }}
            </div>
        </div>
    </div>
</template>
