<!-- resources/js/pages/Admin/Tournaments/Create.vue -->
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
import type {City, Club, CreateTournamentPayload, Game, OfficialRating} from '@/types/api';
import {Head, router} from '@inertiajs/vue3';
import {ArrowLeftIcon, MapPinIcon, StarIcon, TrophyIcon} from 'lucide-vue-next';
import {computed, onMounted, ref, watch} from 'vue';

defineOptions({layout: AuthenticatedLayout});

const {createTournament} = useTournaments();
const {fetchCities, fetchClubs} = useProfileApi();

// Form data
const form = ref<CreateTournamentPayload & {
    official_rating_id?: number;
    rating_coefficient?: number;
}>({
    name: '',
    regulation: '',
    details: '',
    game_id: 0,
    city_id: undefined,
    club_id: undefined,
    start_date: '',
    end_date: '',
    max_participants: undefined,
    entry_fee: 0,
    prize_pool: 0,
    prize_distribution: [],
    organizer: '',
    format: '',
    official_rating_id: undefined,
    rating_coefficient: 1.0,
});

// Data
const games = ref<Game[]>([]);
const cities = ref<City[]>([]);
const clubs = ref<Club[]>([]);
const filteredClubs = ref<Club[]>([]);
const officialRatings = ref<OfficialRating[]>([]);
const filteredGames = ref<Game[]>([]);
const filteredOfficialRatings = ref<OfficialRating[]>([]);

// Loading states
const isLoadingGames = ref(true);
const isLoadingRatings = ref(true);
const isSubmitting = ref(false);

// API calls
const citiesApi = fetchCities();
const clubsApi = fetchClubs();
const createApi = createTournament();

// Computed
const isFormValid = computed(() => {
    return form.value.name.trim() !== '' &&
        form.value.game_id > 0 &&
        form.value.start_date !== '' &&
        form.value.end_date !== '';
});

// Watch for official rating changes to filter games
watch(() => form.value.official_rating_id, (newRatingId) => {
    form.value.game_id = 0;
    if (newRatingId) {
        const selectedRating = officialRatings.value.find(r => r.id === newRatingId);
        if (selectedRating) {
            // Filter games by the rating's game type
            filteredGames.value = games.value.filter(game =>
                game.type === selectedRating.game_type
            );
        }
    } else {
        filteredGames.value = games.value;
    }
});

// Watch for city changes to filter clubs
watch(() => form.value.city_id, (newCityId) => {
    form.value.club_id = undefined;
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
        filteredGames.value = games.value;
    } catch (error) {
        console.error('Failed to load games:', error);
    } finally {
        isLoadingGames.value = false;
    }
};

const fetchOfficialRatings = async () => {
    isLoadingRatings.value = true;
    try {
        officialRatings.value = await apiClient<OfficialRating[]>('/api/official-ratings/active');
        filteredOfficialRatings.value = officialRatings.value;
    } catch (error) {
        console.error('Failed to load official ratings:', error);
    } finally {
        isLoadingRatings.value = false;
    }
};

const loadCitiesAndClubs = async () => {
    await Promise.all([
        citiesApi.execute(),
        clubsApi.execute()
    ]);

    if (citiesApi.data.value) cities.value = citiesApi.data.value;
    if (clubsApi.data.value) clubs.value = clubsApi.data.value;
};

const handleSubmit = async () => {
    if (!isFormValid.value) return;

    isSubmitting.value = true;

    const success = await createApi.execute(form.value);

    if (success) {
        router.visit('/tournaments');
    }

    isSubmitting.value = false;
};

const handleCancel = () => {
    router.visit('/tournaments');
};

onMounted(() => {
    fetchGames();
    fetchOfficialRatings();
    loadCitiesAndClubs();
});
</script>

<template>
    <Head title="Create Tournament"/>

    <div class="py-12">
        <div class="mx-auto max-w-4xl sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">Create Tournament</h1>
                    <p class="text-gray-600 dark:text-gray-400">Set up a new billiard tournament</p>
                </div>
                <Button variant="outline" @click="handleCancel">
                    <ArrowLeftIcon class="mr-2 h-4 w-4"/>
                    Back to Tournaments
                </Button>
            </div>

            <!-- Main Form -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <TrophyIcon class="h-5 w-5"/>
                        Tournament Details
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <form class="space-y-6" @submit.prevent="handleSubmit">
                        <!-- Basic Information -->
                        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                            <div class="space-y-2">
                                <Label for="name">Tournament Name *</Label>
                                <Input
                                    id="name"
                                    v-model="form.name"
                                    placeholder="Enter tournament name"
                                    required
                                />
                            </div>

                            <div class="space-y-2">
                                <Label for="game_id">Game *</Label>
                                <Select v-model="form.game_id" required>
                                    <SelectTrigger>
                                        <SelectValue placeholder="Select specific game"/>
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-if="isLoadingGames" :value="0">
                                            Loading games...
                                        </SelectItem>
                                        <SelectItem
                                            v-for="game in filteredGames"
                                            v-else
                                            :key="game.id"
                                            :value="game.id"
                                        >
                                            {{ game.name }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.official_rating_id" class="text-sm text-gray-500 dark:text-gray-400">
                                    Games filtered by selected rating type
                                </p>
                            </div>
                        </div>

                        <!-- Dates -->
                        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
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

                        <!-- Official Rating Association -->
                        <div class="space-y-4">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 flex items-center gap-2">
                                <StarIcon class="h-5 w-5"/>
                                Official Rating Association
                            </h3>

                            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                                <div class="space-y-2">
                                    <Label for="official_rating_id">Official Rating</Label>
                                    <Select v-model="form.official_rating_id">
                                        <SelectTrigger>
                                            <SelectValue placeholder="Select official rating (optional)"/>
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem v-if="isLoadingRatings" :value="0">
                                                Loading ratings...
                                            </SelectItem>
                                            <SelectItem
                                                v-for="rating in filteredOfficialRatings"
                                                v-else
                                                :key="rating.id"
                                                :value="rating.id"
                                            >
                                                {{ rating.name }} ({{ rating.game_type_name }})
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        Associate this tournament with an official rating system
                                    </p>
                                </div>

                                <div class="space-y-2">
                                    <Label for="rating_coefficient">Rating Coefficient</Label>
                                    <Input
                                        id="rating_coefficient"
                                        v-model.number="form.rating_coefficient"
                                        :disabled="!form.official_rating_id"
                                        max="5.0"
                                        min="0.1"
                                        step="0.1"
                                        type="number"
                                    />
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        Multiplier for rating points (0.1 - 5.0)
                                    </p>
                                </div>
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
                                {{ isSubmitting ? 'Creating...' : 'Create Tournament' }}
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>

            <!-- Error Display -->
            <div v-if="createApi.error.value"
                 class="mt-4 rounded bg-red-100 p-4 text-red-600 dark:bg-red-900/30 dark:text-red-400">
                Error creating tournament: {{ createApi.error.value.message }}
            </div>
        </div>
    </div>
</template>
