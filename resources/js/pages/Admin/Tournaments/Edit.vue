<!-- resources/js/pages/Admin/Tournaments/Edit.vue -->
<script lang="ts" setup>
import {
    Accordion,
    AccordionContent,
    AccordionItem,
    AccordionTrigger,
    Button,
    Card,
    CardContent,
    Input,
    Label,
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
    Spinner,
    Switch,
    Textarea
} from '@/Components/ui';
import {useProfileApi} from '@/composables/useProfileApi';
import {useTournaments} from '@/composables/useTournaments';
import {useLocale} from '@/composables/useLocale';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import {apiClient} from '@/lib/apiClient';
import type {City, Club, Game, OfficialRating, Tournament} from '@/types/api';
import {Head, router} from '@inertiajs/vue3';
import {
    ArrowLeftIcon,
    DollarSignIcon,
    FileTextIcon,
    InfoIcon,
    LayersIcon,
    MapPinIcon,
    SettingsIcon,
    TrophyIcon,
    UsersIcon
} from 'lucide-vue-next';
import {computed, onMounted, ref, watch} from 'vue';

defineOptions({layout: AuthenticatedLayout});

const props = defineProps<{
    tournamentId: number | string;
}>();

const {t} = useLocale();

const {updateTournament, fetchTournament} = useTournaments();
const {fetchCities, fetchClubs} = useProfileApi();

// Form data with all fields
const form = ref({
    name: '',
    regulation: '',
    details: '',
    game_id: 0,
    city_id: undefined as number | undefined,
    club_id: undefined as number | undefined,
    start_date: '',
    end_date: '',
    application_deadline: '',
    max_participants: undefined as number | undefined,
    entry_fee: 0,
    prize_pool: 0,
    place_prizes: [] as number[],
    place_bonuses: [] as number[],
    place_rating_points: [] as number[],
    organizer: '',
    format: '',
    status: 'upcoming' as 'upcoming' | 'active' | 'completed' | 'cancelled',
    stage: 'registration' as 'registration' | 'seeding' | 'group' | 'bracket' | 'completed',
    tournament_type: 'single_elimination' as any,
    olympic_phase_size: 8,
    olympic_has_third_place: false,
    group_size_min: 4,
    group_size_max: 5,
    playoff_players_per_group: 2,
    races_to: 7,
    round_races_to: {} as Record<string, number>,
    has_third_place_match: false,
    seeding_method: 'random' as any,
    requires_application: true,
    auto_approve_applications: false,
    official_rating_id: undefined as number | undefined,
    rating_coefficient: 1.0,
});

// Data
const tournament = ref<Tournament | null>(null);
const games = ref<Game[]>([]);
const cities = ref<City[]>([]);
const clubs = ref<Club[]>([]);
const filteredClubs = ref<Club[]>([]);
const officialRatings = ref<OfficialRating[]>([]);
const filteredGames = ref<Game[]>([]);
const bracketOptions = ref<any>({});

// Loading states
const isLoadingTournament = ref(true);
const isLoadingGames = ref(true);
const isLoadingRatings = ref(true);
const isLoadingBracketOptions = ref(false);
const isSubmitting = ref(false);

// API calls
const tournamentApi = fetchTournament(props.tournamentId);
const citiesApi = fetchCities();
const clubsApi = fetchClubs();
const updateApi = updateTournament(props.tournamentId);

// Tournament type options
const tournamentTypes = [
    {value: 'single_elimination', label: t('Single Elimination')},
    {value: 'double_elimination', label: t('Double Elimination')},
    {value: 'double_elimination_full', label: t('Double Elimination All Places')},
    {value: 'olympic_double_elimination', label: t('Olympic Double Elimination')},
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

const statusOptions = [
    {value: 'upcoming', label: t('Upcoming')},
    {value: 'active', label: t('Active')},
    {value: 'completed', label: t('Completed')},
    {value: 'cancelled', label: t('Cancelled')}
];

const stageOptions = [
    {value: 'registration', label: t('Registration')},
    {value: 'seeding', label: t('Seeding')},
    {value: 'group', label: t('Group Stage')},
    {value: 'bracket', label: t('Bracket Stage')},
    {value: 'completed', label: t('Completed')}
];

const olympicPhaseSizes = [
    {value: 2, label: '2'},
    {value: 4, label: '4'},
    {value: 8, label: '8'},
    {value: 16, label: '16'},
    {value: 32, label: '32'},
    {value: 64, label: '64'},
];

// Computed
const isFormValid = computed(() => {
    return form.value.name?.trim() !== '' &&
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

const showOlympicSettings = computed(() => {
    return form.value.tournament_type === 'olympic_double_elimination';
});

const isEliminationTournament = computed(() => {
    return ['single_elimination', 'double_elimination', 'double_elimination_full', 'olympic_double_elimination'].includes(form.value.tournament_type);
});

const showRoundRacesEditor = computed(() => {
    return isEliminationTournament.value &&
        tournament.value &&
        (tournament.value.stage === 'bracket' || tournament.value.brackets_generated);
});

// Watch for tournament changes to load bracket options
watch(() => [tournament.value?.id, form.value.tournament_type], async ([tournamentId]) => {
    if (tournamentId && showRoundRacesEditor.value) {
        await fetchBracketOptions();
    }
});

// Watch for official rating changes to filter games
watch(() => form.value.official_rating_id, (newRatingId) => {
    if (!isLoadingTournament.value) {
        form.value.game_id = 0;
    }

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

// Helper function to format datetime for input
const formatDateTimeForInput = (dateString: string | undefined): string => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toISOString().slice(0, 16);
};

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

const fetchBracketOptions = async () => {
    isLoadingBracketOptions.value = true;
    try {
        bracketOptions.value = await apiClient(`/api/admin/tournaments/${props.tournamentId}/bracket-options`);
    } catch (error) {
        console.error('Failed to load bracket options:', error);
    } finally {
        isLoadingBracketOptions.value = false;
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
            game_id: tournament.value.game_id || 0,
            city_id: tournament.value.city?.id,
            club_id: tournament.value.club?.id,
            start_date: formatDateTimeForInput(tournament.value.start_date),
            end_date: formatDateTimeForInput(tournament.value.end_date),
            application_deadline: formatDateTimeForInput(tournament.value.application_deadline),
            max_participants: tournament.value.max_participants || undefined,
            entry_fee: tournament.value.entry_fee || 0,
            prize_pool: tournament.value.prize_pool || 0,
            place_prizes: tournament.value.place_prizes || [],
            place_bonuses: tournament.value.place_bonuses || [],
            place_rating_points: tournament.value.place_rating_points || [],
            organizer: tournament.value.organizer || '',
            format: tournament.value.format || '',
            status: tournament.value.status,
            stage: tournament.value.stage || 'registration',
            tournament_type: tournament.value.tournament_type || 'single_elimination',
            olympic_phase_size: tournament.value.olympic_phase_size || 8,
            olympic_has_third_place: tournament.value.olympic_has_third_place || false,
            group_size_min: tournament.value.group_size_min || 4,
            group_size_max: tournament.value.group_size_max || 5,
            playoff_players_per_group: tournament.value.playoff_players_per_group || 2,
            races_to: tournament.value.races_to || 7,
            round_races_to: tournament.value.round_races_to || {},
            has_third_place_match: tournament.value.has_third_place_match || false,
            seeding_method: tournament.value.seeding_method || 'random',
            requires_application: tournament.value.requires_application ?? true,
            auto_approve_applications: tournament.value.auto_approve_applications || false,
            official_rating_id: tournament.value.official_ratings?.[0]?.id,
            rating_coefficient: tournament.value.official_ratings?.[0]?.rating_coefficient || 1.0,
        };

        // Set up filtered games based on existing rating if any
        if (form.value.official_rating_id) {
            const selectedRating = officialRatings.value.find(r => r.id === form.value.official_rating_id);
            if (selectedRating) {
                filteredGames.value = games.value.filter(game =>
                    game.type === selectedRating.game_type
                );
            }
        }
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
        router.visit(`/tournaments/${tournament.value?.slug}`);
    }

    isSubmitting.value = false;
};

const handleCancel = () => {
    router.visit(`/tournaments/${tournament.value?.slug}`);
};

onMounted(async () => {
    await Promise.all([
        fetchGames(),
        fetchOfficialRatings(),
        loadCitiesAndClubs()
    ]);

    await loadTournament();
});
</script>

<template>
    <Head :title="tournament ? `${t('Edit')}: ${tournament.name}` : t('Edit Tournament')"/>

    <div class="py-6 sm:py-8 lg:py-12">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white">
                        {{ t('Edit Tournament') }}
                    </h1>
                    <p class="text-gray-600 dark:text-gray-400 mt-1">
                        {{ tournament ? tournament.name : t('Loading...') }}
                    </p>
                </div>
                <Button variant="outline" @click="handleCancel">
                    <ArrowLeftIcon class="mr-2 h-4 w-4"/>
                    <span class="hidden sm:inline">{{ t('Back to Tournament') }}</span>
                    <span class="sm:hidden">{{ t('Back') }}</span>
                </Button>
            </div>

            <!-- Loading State -->
            <div v-if="isLoadingTournament" class="flex justify-center py-12">
                <div class="text-center">
                    <Spinner class="mx-auto h-8 w-8 text-indigo-600"/>
                    <p class="mt-2 text-gray-500">{{ t('Loading tournament...') }}</p>
                </div>
            </div>

            <!-- Error State -->
            <div v-else-if="tournamentApi.error.value"
                 class="rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 p-4">
                <p class="text-red-600 dark:text-red-400">
                    {{ t('Error loading tournament') }}: {{ tournamentApi.error.value.message }}
                </p>
            </div>

            <!-- Main Form Card -->
            <Card v-else class="shadow-lg">
                <div class="bg-gradient-to-r from-gray-50 to-indigo-50 dark:from-gray-800 dark:to-gray-700 p-6 sm:p-8">
                    <div class="flex items-center gap-3">
                        <div class="h-12 w-12 rounded-full bg-indigo-600 flex items-center justify-center shadow-md">
                            <TrophyIcon class="h-6 w-6 text-white"/>
                        </div>
                        <div>
                            <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                                {{ t('Tournament Details') }}
                            </h2>
                            <p class="text-gray-600 dark:text-gray-400">
                                {{ t('Update the tournament information below') }}
                            </p>
                        </div>
                    </div>
                </div>

                <CardContent class="p-6 sm:p-8">
                    <form class="space-y-8" @submit.prevent="handleSubmit">
                        <!-- Basic Information - Always visible -->
                        <div class="space-y-6">
                            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                                <div class="space-y-2">
                                    <Label for="status">{{ t('Status') }}</Label>
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

                                <div class="space-y-2">
                                    <Label for="stage">{{ t('Stage') }}</Label>
                                    <Select v-model="form.stage">
                                        <SelectTrigger>
                                            <SelectValue/>
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem
                                                v-for="option in stageOptions"
                                                :key="option.value"
                                                :value="option.value"
                                            >
                                                {{ option.label }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div class="space-y-2">
                                    <Label for="name">{{ t('Tournament Name') }} *</Label>
                                    <Input
                                        id="name"
                                        v-model="form.name"
                                        :placeholder="t('Enter tournament name')"
                                        required
                                        class="w-full"
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
                                        <SettingsIcon class="h-5 w-5 text-indigo-600 dark:text-indigo-400"/>
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
                                                <Label for="races_to">{{ t('Default Races To') }}</Label>
                                                <Input
                                                    id="races_to"
                                                    v-model.number="form.races_to"
                                                    :placeholder="t('Default: 7')"
                                                    min="1"
                                                    type="number"
                                                />
                                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                                    {{ t('Default race length for all rounds') }}
                                                </p>
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

                                            <div v-if="showThirdPlaceOption" class="flex items-center space-x-3 pt-7">
                                                <Switch
                                                    id="has_third_place_match"
                                                    v-model="form.has_third_place_match"
                                                />
                                                <Label for="has_third_place_match" class="cursor-pointer">
                                                    {{ t('Include third place match') }}
                                                </Label>
                                            </div>
                                        </div>

                                        <!-- Olympic Settings -->
                                        <div v-if="showOlympicSettings"
                                             class="grid grid-cols-1 gap-6 lg:grid-cols-2 pt-6 border-t dark:border-gray-700">
                                            <div class="space-y-2">
                                                <Label for="olympic_phase_size">{{ t('Olympic Phase Size') }}</Label>
                                                <Select v-model="form.olympic_phase_size">
                                                    <SelectTrigger>
                                                        <SelectValue/>
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        <SelectItem
                                                            v-for="size in olympicPhaseSizes"
                                                            :key="size.value"
                                                            :value="size.value"
                                                        >
                                                            {{ size.label }} {{ t('players') }}
                                                        </SelectItem>
                                                    </SelectContent>
                                                </Select>
                                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                                    {{ t('Number of players advancing to Olympic stage') }}
                                                </p>
                                            </div>

                                            <div class="flex items-center space-x-3 pt-7">
                                                <Switch
                                                    id="olympic_has_third_place"
                                                    v-model="form.olympic_has_third_place"
                                                />
                                                <Label for="olympic_has_third_place" class="cursor-pointer">
                                                    {{ t('Include Olympic third place match') }}
                                                </Label>
                                            </div>
                                        </div>

                                        <!-- Group Settings -->
                                        <div v-if="showGroupSettings"
                                             class="grid grid-cols-1 gap-6 lg:grid-cols-3 pt-6 border-t dark:border-gray-700">
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

                                        <!-- Round-specific races configuration -->
                                        <div v-if="showRoundRacesEditor && bracketOptions.rounds"
                                             class="pt-6 border-t dark:border-gray-700">
                                            <h4 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4 flex items-center gap-2">
                                                <LayersIcon class="h-5 w-5 text-indigo-600 dark:text-indigo-400"/>
                                                {{ t('Round-specific Race Settings') }}
                                            </h4>
                                            <div
                                                class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4 mb-4">
                                                <div class="flex items-start gap-2">
                                                    <InfoIcon
                                                        class="h-5 w-5 text-blue-600 dark:text-blue-400 mt-0.5 flex-shrink-0"/>
                                                    <p class="text-sm text-blue-800 dark:text-blue-200">
                                                        {{
                                                            t('Set different race lengths for each round. Leave empty to use default.')
                                                        }}
                                                    </p>
                                                </div>
                                            </div>
                                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                                                <div v-for="(roundName, roundKey) in bracketOptions.rounds"
                                                     :key="roundKey" class="space-y-2">
                                                    <Label :for="`round_${roundKey}`" class="text-sm">{{
                                                            roundName
                                                        }}</Label>
                                                    <Input
                                                        :id="`round_${roundKey}`"
                                                        v-model.number="form.round_races_to[roundKey]"
                                                        :placeholder="`${t('Default')}: ${form.races_to}`"
                                                        min="1"
                                                        type="number"
                                                        class="w-full"
                                                    />
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </AccordionContent>
                            </AccordionItem>

                            <!-- Registration & Dates -->
                            <AccordionItem value="registration">
                                <AccordionTrigger value="registration">
                                    <div class="flex items-center gap-2">
                                        <UsersIcon class="h-5 w-5 text-indigo-600 dark:text-indigo-400"/>
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

                                            <div class="flex items-center space-x-3 pt-7">
                                                <Switch
                                                    id="requires_application"
                                                    v-model="form.requires_application"
                                                />
                                                <Label for="requires_application" class="cursor-pointer">
                                                    {{ t('Require application approval') }}
                                                </Label>
                                            </div>

                                            <div class="flex items-center space-x-3 pt-7">
                                                <Switch
                                                    id="auto_approve_applications"
                                                    v-model="form.auto_approve_applications"
                                                    :disabled="!form.requires_application"
                                                />
                                                <Label for="auto_approve_applications" class="cursor-pointer">
                                                    {{ t('Auto-approve applications') }}
                                                </Label>
                                            </div>
                                        </div>
                                    </div>
                                </AccordionContent>
                            </AccordionItem>

                            <!-- Location -->
                            <AccordionItem value="location">
                                <AccordionTrigger value="location">
                                    <div class="flex items-center gap-2">
                                        <MapPinIcon class="h-5 w-5 text-indigo-600 dark:text-indigo-400"/>
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
                                        <DollarSignIcon class="h-5 w-5 text-indigo-600 dark:text-indigo-400"/>
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
                                            <Label for="prize_pool">{{ t('Prize Pool') }}</Label>
                                            <Input
                                                id="prize_pool"
                                                v-model="form.prize_pool"
                                                :placeholder="t('Enter Prize Pool')"
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
                                        <FileTextIcon class="h-5 w-5 text-indigo-600 dark:text-indigo-400"/>
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
                                                class="resize-none"
                                            />
                                        </div>

                                        <div class="space-y-2">
                                            <Label for="regulation">{{ t('Regulation') }}</Label>
                                            <Textarea
                                                id="regulation"
                                                v-model="form.regulation"
                                                :placeholder="t('Tournament rules and regulations')"
                                                rows="4"
                                                class="resize-none"
                                            />
                                        </div>
                                    </div>
                                </AccordionContent>
                            </AccordionItem>
                        </Accordion>

                        <!-- Form Actions -->
                        <div class="flex justify-end space-x-4 border-t dark:border-gray-700 pt-6">
                            <Button type="button" variant="outline" @click="handleCancel">
                                {{ t('Cancel') }}
                            </Button>
                            <Button
                                :disabled="!isFormValid || isSubmitting"
                                type="submit"
                            >
                                <Spinner v-if="isSubmitting" class="mr-2 h-4 w-4"/>
                                {{ isSubmitting ? t('Updating...') : t('Update Tournament') }}
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>

            <!-- Error Display -->
            <div v-if="updateApi.error.value"
                 class="mt-4 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 p-4">
                <p class="text-red-600 dark:text-red-400">
                    {{ t('Error updating tournament') }}: {{ updateApi.error.value.message }}
                </p>
            </div>
        </div>
    </div>
</template>
