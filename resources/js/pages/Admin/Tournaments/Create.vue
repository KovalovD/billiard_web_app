<!-- resources/js/pages/Admin/Tournaments/Create.vue -->
<script lang="ts" setup>
import {
    Accordion,
    AccordionContent,
    AccordionItem,
    AccordionTrigger,
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
import {useLocale} from '@/composables/useLocale';
import type {City, Club, CreateTournamentPayload, Game, OfficialRating} from '@/types/api';
import {Head, router} from '@inertiajs/vue3';
import {
    ArrowLeftIcon,
    DollarSignIcon,
    FileTextIcon,
    MapPinIcon,
    SettingsIcon,
    TrophyIcon,
    UsersIcon
} from 'lucide-vue-next';
import {computed, onMounted, ref, watch} from 'vue';

defineOptions({layout: AuthenticatedLayout});

const {t} = useLocale();

const {createTournament} = useTournaments();
const {fetchCities, fetchClubs} = useProfileApi();

// Form data with new fields
const form = ref<CreateTournamentPayload & {
    official_rating_id?: number;
    rating_coefficient?: number;
    application_deadline?: string;
    requires_application?: boolean;
    auto_approve_applications?: boolean;
}>({
    name: '',
    regulation: '',
    details: '',
    game_id: 0,
    city_id: undefined,
    club_id: undefined,
    start_date: '',
    end_date: '',
    application_deadline: '',
    max_participants: undefined,
    entry_fee: 0,
    prize_pool: 0,
    prize_distribution: [],
    place_prizes: [],
    place_bonuses: [],
    place_rating_points: [],
    organizer: '',
    format: '',
    tournament_type: 'single_elimination',
    group_size_min: 3,
    group_size_max: 5,
    playoff_players_per_group: 2,
    races_to: 7,
    has_third_place_match: false,
    seeding_method: 'random',
    requires_application: true,
    auto_approve_applications: false,
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

// Loading states
const isLoadingGames = ref(true);
const isLoadingRatings = ref(true);
const isSubmitting = ref(false);

// Tournament type options
const tournamentTypes = [
    {value: 'single_elimination', label: t('Single Elimination')},
    {value: 'double_elimination', label: t('Double Elimination')},
    {value: 'double_elimination_full', label: t('Double Elimination (All Places)')},
    {value: 'round_robin', label: t('Round Robin')},
    {value: 'groups', label: t('Groups')},
    {value: 'groups_playoff', label: t('Groups + Playoff')},
    {value: 'team_groups_playoff', label: t('Team Groups + Playoff')},
    {value: 'killer_pool', label: t('Killer Pool')},
];

const seedingMethods = [
    {value: 'random', label: t('Random')},
    {value: 'rating', label: t('By Rating')},
    {value: 'manual', label: t('Manual')},
];

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

const showGroupSettings = computed(() => {
    return ['groups', 'groups_playoff', 'team_groups_playoff'].includes(form.value.tournament_type);
});

const showPlayoffSettings = computed(() => {
    return ['groups_playoff', 'team_groups_playoff'].includes(form.value.tournament_type);
});

const showThirdPlaceOption = computed(() => {
    return ['single_elimination', 'double_elimination', 'double_elimination_full'].includes(form.value.tournament_type);
});

// Watch for official rating changes to filter games
watch(() => form.value.official_rating_id, (newRatingId) => {
    form.value.game_id = 0;
    if (newRatingId) {
        const selectedRating = officialRatings.value.find(r => r.id === newRatingId);
        if (selectedRating) {
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
    <Head :title="t('Create Tournament')"/>

    <div class="py-12">
        <div class="mx-auto max-w-4xl sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">{{ t('Create Tournament') }}</h1>
                    <p class="text-gray-600 dark:text-gray-400">{{ t('Set up a new billiard tournament') }}</p>
                </div>
                <Button variant="outline" @click="handleCancel">
                    <ArrowLeftIcon class="mr-2 h-4 w-4"/>
                    {{ t('Back to Tournaments') }}
                </Button>
            </div>

            <!-- Main Form -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <TrophyIcon class="h-5 w-5"/>
                        {{ t('Tournament Details') }}
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <form class="space-y-6" @submit.prevent="handleSubmit">
                        <!-- Basic Information - Always visible -->
                        <div class="space-y-6">
                            <div class="grid grid-cols-1 gap-6">
                                <div class="space-y-2">
                                    <Label for="name">{{ t('Tournament Name') }} *</Label>
                                    <Input
                                        id="name"
                                        v-model="form.name"
                                        :placeholder="t('Enter tournament name')"
                                        required
                                    />
                                </div>
                            </div>

                            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                                <div class="space-y-2">
                                    <Label for="official_rating_id">{{ t('Official Rating') }}</Label>
                                    <Select v-model="form.official_rating_id">
                                        <SelectTrigger>
                                            <SelectValue :placeholder="t('Select official rating (optional)')"/>
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem v-if="isLoadingRatings" :value="0">
                                                {{ t('Loading ratings...') }}
                                            </SelectItem>
                                            <SelectItem
                                                v-for="rating in officialRatings"
                                                v-else
                                                :key="rating.id"
                                                :value="rating.id"
                                            >
                                                {{ rating.name }} ({{ rating.game_type_name }})
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ t('Associate this tournament with an official rating system') }}
                                    </p>
                                </div>

                                <div class="space-y-2">
                                    <Label for="game_id">{{ t('Game') }} *</Label>
                                    <Select v-model="form.game_id" required>
                                        <SelectTrigger>
                                            <SelectValue :placeholder="t('Select specific game')"/>
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem v-if="isLoadingGames" :value="0">
                                                {{ t('Loading games...') }}
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
                                        {{ t('Games filtered by selected rating type') }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Accordion sections -->
                        <Accordion>
                            <!-- Tournament Structure -->
                            <AccordionItem value="structure">
                                <AccordionTrigger value="structure">
                                    <div class="flex items-center gap-2">
                                        <SettingsIcon class="h-5 w-5"/>
                                        {{ t('Tournament Structure') }}
                                    </div>
                                </AccordionTrigger>
                                <AccordionContent value="structure">
                                    <div class="space-y-6 pt-4">
                                        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                                            <div class="space-y-2">
                                                <Label for="tournament_type">{{ t('Tournament Type') }} *</Label>
                                                <Select v-model="form.tournament_type" required>
                                                    <SelectTrigger>
                                                        <SelectValue/>
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        <SelectItem
                                                            v-for="type in tournamentTypes"
                                                            :key="type.value"
                                                            :value="type.value"
                                                        >
                                                            {{ type.label }}
                                                        </SelectItem>
                                                    </SelectContent>
                                                </Select>
                                            </div>

                                            <div class="space-y-2">
                                                <Label for="races_to">{{ t('Races To') }}</Label>
                                                <Input
                                                    id="races_to"
                                                    v-model.number="form.races_to"
                                                    :placeholder="t('Default: 7')"
                                                    min="1"
                                                    type="number"
                                                />
                                            </div>

                                            <div class="space-y-2">
                                                <Label for="seeding_method">{{ t('Seeding Method') }}</Label>
                                                <Select v-model="form.seeding_method">
                                                    <SelectTrigger>
                                                        <SelectValue/>
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        <SelectItem
                                                            v-for="method in seedingMethods"
                                                            :key="method.value"
                                                            :value="method.value"
                                                        >
                                                            {{ method.label }}
                                                        </SelectItem>
                                                    </SelectContent>
                                                </Select>
                                            </div>

                                            <div v-if="showThirdPlaceOption" class="flex items-center space-x-2">
                                                <input
                                                    id="has_third_place_match"
                                                    v-model="form.has_third_place_match"
                                                    class="rounded border-gray-300 text-primary focus:ring-primary"
                                                    type="checkbox"
                                                />
                                                <Label for="has_third_place_match">{{
                                                        t('Include third place match')
                                                    }}</Label>
                                            </div>
                                        </div>

                                        <!-- Group Settings -->
                                        <div v-if="showGroupSettings" class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                                            <div class="space-y-2">
                                                <Label for="group_size_min">{{ t('Min Group Size') }}</Label>
                                                <Input
                                                    id="group_size_min"
                                                    v-model.number="form.group_size_min"
                                                    max="5"
                                                    min="3"
                                                    type="number"
                                                />
                                            </div>

                                            <div class="space-y-2">
                                                <Label for="group_size_max">{{ t('Max Group Size') }}</Label>
                                                <Input
                                                    id="group_size_max"
                                                    v-model.number="form.group_size_max"
                                                    max="5"
                                                    min="3"
                                                    type="number"
                                                />
                                            </div>

                                            <div v-if="showPlayoffSettings" class="space-y-2">
                                                <Label for="playoff_players_per_group">{{
                                                        t('Players to Playoff')
                                                    }}</Label>
                                                <Input
                                                    id="playoff_players_per_group"
                                                    v-model.number="form.playoff_players_per_group"
                                                    :max="form.group_size_min - 1"
                                                    min="1"
                                                    type="number"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                </AccordionContent>
                            </AccordionItem>

                            <!-- Registration & Dates -->
                            <AccordionItem value="registration">
                                <AccordionTrigger value="registration">
                                    <div class="flex items-center gap-2">
                                        <UsersIcon class="h-5 w-5"/>
                                        {{ t('Registration & Dates') }}
                                    </div>
                                </AccordionTrigger>
                                <AccordionContent value="registration">
                                    <div class="space-y-6 pt-4">
                                        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                                            <div class="space-y-2">
                                                <Label for="start_date">{{ t('Start Date') }} *</Label>
                                                <Input
                                                    id="start_date"
                                                    v-model="form.start_date"
                                                    required
                                                    type="datetime-local"
                                                />
                                            </div>

                                            <div class="space-y-2">
                                                <Label for="end_date">{{ t('End Date') }} *</Label>
                                                <Input
                                                    id="end_date"
                                                    v-model="form.end_date"
                                                    required
                                                    type="datetime-local"
                                                />
                                            </div>

                                            <div class="space-y-2">
                                                <Label for="application_deadline">{{
                                                        t('Application Deadline')
                                                    }}</Label>
                                                <Input
                                                    id="application_deadline"
                                                    v-model="form.application_deadline"
                                                    type="datetime-local"
                                                />
                                            </div>
                                        </div>

                                        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                                            <div class="space-y-2">
                                                <Label for="max_participants">{{ t('Max Participants') }}</Label>
                                                <Input
                                                    id="max_participants"
                                                    v-model.number="form.max_participants"
                                                    :placeholder="t('Unlimited')"
                                                    min="2"
                                                    type="number"
                                                />
                                            </div>

                                            <div class="flex items-center space-x-2">
                                                <input
                                                    id="requires_application"
                                                    v-model="form.requires_application"
                                                    class="rounded border-gray-300 text-primary focus:ring-primary"
                                                    type="checkbox"
                                                />
                                                <Label for="requires_application">{{
                                                        t('Require application approval')
                                                    }}</Label>
                                            </div>

                                            <div class="flex items-center space-x-2">
                                                <input
                                                    id="auto_approve_applications"
                                                    v-model="form.auto_approve_applications"
                                                    :disabled="!form.requires_application"
                                                    class="rounded border-gray-300 text-primary focus:ring-primary disabled:opacity-50"
                                                    type="checkbox"
                                                />
                                                <Label for="auto_approve_applications">{{
                                                        t('Auto-approve applications')
                                                    }}</Label>
                                            </div>
                                        </div>
                                    </div>
                                </AccordionContent>
                            </AccordionItem>

                            <!-- Location -->
                            <AccordionItem value="location">
                                <AccordionTrigger value="location">
                                    <div class="flex items-center gap-2">
                                        <MapPinIcon class="h-5 w-5"/>
                                        {{ t('Location') }}
                                    </div>
                                </AccordionTrigger>
                                <AccordionContent value="location">
                                    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2 pt-4">
                                        <div class="space-y-2">
                                            <Label for="city_id">{{ t('City') }}</Label>
                                            <Select v-model="form.city_id">
                                                <SelectTrigger>
                                                    <SelectValue :placeholder="t('Select city')"/>
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
                                            <Label for="club_id">{{ t('Club') }}</Label>
                                            <Select v-model="form.club_id" :disabled="!form.city_id">
                                                <SelectTrigger>
                                                    <SelectValue :placeholder="t('Select club')"/>
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
                                </AccordionContent>
                            </AccordionItem>

                            <!-- Financial Details -->
                            <AccordionItem value="financial">
                                <AccordionTrigger value="financial">
                                    <div class="flex items-center gap-2">
                                        <DollarSignIcon class="h-5 w-5"/>
                                        {{ t('Financial Details') }}
                                    </div>
                                </AccordionTrigger>
                                <AccordionContent value="financial">
                                    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2 pt-4">
                                        <div class="space-y-2">
                                            <Label for="entry_fee">{{ t('Entry Fee') }} (₴)</Label>
                                            <Input
                                                id="entry_fee"
                                                v-model.number="form.entry_fee"
                                                min="0"
                                                step="0.01"
                                                type="number"
                                            />
                                        </div>

                                        <div class="space-y-2">
                                            <Label for="prize_pool">{{ t('Prize Pool') }} (₴)</Label>
                                            <Input
                                                id="prize_pool"
                                                v-model.number="form.prize_pool"
                                                min="0"
                                                step="0.01"
                                                type="number"
                                            />
                                        </div>

                                        <div v-if="form.official_rating_id" class="space-y-2">
                                            <Label for="rating_coefficient">{{ t('Rating Coefficient') }}</Label>
                                            <Input
                                                id="rating_coefficient"
                                                v-model.number="form.rating_coefficient"
                                                max="5.0"
                                                min="0.1"
                                                step="0.1"
                                                type="number"
                                            />
                                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                                {{ t('Multiplier for rating points (0.1 - 5.0)') }}
                                            </p>
                                        </div>
                                    </div>
                                </AccordionContent>
                            </AccordionItem>

                            <!-- Additional Information -->
                            <AccordionItem value="additional">
                                <AccordionTrigger value="additional">
                                    <div class="flex items-center gap-2">
                                        <FileTextIcon class="h-5 w-5"/>
                                        {{ t('Additional Information') }}
                                    </div>
                                </AccordionTrigger>
                                <AccordionContent value="additional">
                                    <div class="space-y-6 pt-4">
                                        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                                            <div class="space-y-2">
                                                <Label for="organizer">{{ t('Organizer') }}</Label>
                                                <Input
                                                    id="organizer"
                                                    v-model="form.organizer"
                                                    :placeholder="t('Tournament organizer')"
                                                />
                                            </div>

                                            <div class="space-y-2">
                                                <Label for="format">{{ t('Format') }}</Label>
                                                <Input
                                                    id="format"
                                                    v-model="form.format"
                                                    :placeholder="t('Additional format details')"
                                                />
                                            </div>
                                        </div>

                                        <div class="space-y-2">
                                            <Label for="details">{{ t('Description') }}</Label>
                                            <Textarea
                                                id="details"
                                                v-model="form.details"
                                                :placeholder="t('Tournament description and additional details')"
                                                rows="3"
                                            />
                                        </div>

                                        <div class="space-y-2">
                                            <Label for="regulation">{{ t('Regulation') }}</Label>
                                            <Textarea
                                                id="regulation"
                                                v-model="form.regulation"
                                                :placeholder="t('Tournament rules and regulations')"
                                                rows="4"
                                            />
                                        </div>
                                    </div>
                                </AccordionContent>
                            </AccordionItem>
                        </Accordion>

                        <!-- Form Actions -->
                        <div class="flex justify-end space-x-4 border-t pt-6">
                            <Button type="button" variant="outline" @click="handleCancel">
                                {{ t('Cancel') }}
                            </Button>
                            <Button
                                :disabled="!isFormValid || isSubmitting"
                                type="submit"
                            >
                                <Spinner v-if="isSubmitting" class="mr-2 h-4 w-4"/>
                                {{ isSubmitting ? t('Creating...') : t('Create Tournament') }}
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>

            <!-- Error Display -->
            <div v-if="createApi.error.value"
                 class="mt-4 rounded bg-red-100 p-4 text-red-600 dark:bg-red-900/30 dark:text-red-400">
                {{ t('Error creating tournament') }}: {{ createApi.error.value.message }}
            </div>
        </div>
    </div>
</template>
