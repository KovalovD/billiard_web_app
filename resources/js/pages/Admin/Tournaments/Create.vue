<!-- resources/js/Pages/Tournaments/Create.vue -->
<script lang="ts" setup>
import {ref, reactive, computed, onMounted} from 'vue';
import {Head, router} from '@inertiajs/vue3';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import {
    Button,
    Card,
    CardContent,
    CardHeader,
    CardTitle,
    Input,
    Label,
    Textarea,
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue
} from '@/Components/ui';
import {useProfileApi} from '@/composables/useProfileApi';
import {useTournamentStore} from '@/stores/tournament';
import {useLocale} from '@/composables/useLocale';
import FormatSelector from '@/Components/Tournament/FormatSelector.vue';
import SeedingSelector from '@/Components/Tournament/SeedingSelector.vue';
import TeamConfigSection from '@/Components/Tournament/TeamConfigSection.vue';
import type {City, Club, Game} from '@/types/api';

defineOptions({layout: AuthenticatedLayout});

const {t} = useLocale();
const {fetchCities, fetchClubs} = useProfileApi();
const tournamentStore = useTournamentStore();

// Form data
const form = reactive({
    // Basic info
    name: '',
    discipline: '',
    organizer: '',
    description: '',

    // Location
    city_id: null as number | null,
    club_id: null as number | null,
    venue_details: '',

    // Dates and limits
    start_date: '',
    end_date: '',
    registration_deadline: '',
    max_participants: 32,

    // Financial
    entry_fee: 0,
    prize_pool: 0,

    // Tournament format
    format: {
        type: 'single_elimination' as 'single_elimination' | 'double_elimination' | 'group_stage' | 'group_playoff',
        best_of: 3,
        rounds: 0,
        group_count: 4,
        playoff_size: 8,
        third_place_match: false
    },

    // Seeding
    seeding: {
        method: 'random' as 'manual' | 'random' | 'rating_based',
        custom_order: [] as number[]
    },

    // Teams (optional)
    teams: {
        enabled: false,
        team_size: 2,
        max_teams: 16,
        allow_mixed: true
    },

    // Schedule settings
    schedule: {
        start_time: '09:00',
        end_time: '18:00',
        break_duration: 15,
        match_duration: 45,
        tables_count: 4,
        auto_schedule: true
    }
});

// Data
const games = ref<Game[]>([]);
const cities = ref<City[]>([]);
const clubs = ref<Club[]>([]);
const filteredClubs = ref<Club[]>([]);

// Loading states
const isLoading = ref(false);
const isSubmitting = ref(false);

// Validation
const errors = ref<Record<string, string>>({});

// Computed
const isFormValid = computed(() => {
    return form.name.trim() !== '' &&
        form.start_date !== '' &&
        form.end_date !== '' &&
        form.max_participants > 0 &&
        Object.keys(errors.value).length === 0;
});

const formatRequiredPlayers = computed(() => {
    if (form.teams.enabled) {
        return form.teams.max_teams * form.teams.team_size;
    }

    switch (form.format.type) {
        case 'single_elimination':
        case 'double_elimination':
            // Round up to next power of 2
            return Math.pow(2, Math.ceil(Math.log2(form.max_participants)));
        case 'group_stage':
            return form.format.group_count * Math.floor(form.max_participants / form.format.group_count);
        case 'group_playoff':
            return Math.max(form.format.playoff_size, form.format.group_count * 3);
        default:
            return form.max_participants;
    }
});

// Methods
const validateForm = () => {
    errors.value = {};

    if (!form.name.trim()) {
        errors.value.name = t('Tournament name is required');
    }

    if (!form.start_date) {
        errors.value.start_date = t('Start date is required');
    }

    if (!form.end_date) {
        errors.value.end_date = t('End date is required');
    }

    if (form.start_date && form.end_date && new Date(form.start_date) >= new Date(form.end_date)) {
        errors.value.end_date = t('End date must be after start date');
    }

    if (form.registration_deadline && form.start_date &&
        new Date(form.registration_deadline) >= new Date(form.start_date)) {
        errors.value.registration_deadline = t('Registration deadline must be before start date');
    }

    if (form.max_participants < 2) {
        errors.value.max_participants = t('At least 2 participants required');
    }

    if (form.format.type === 'group_stage' || form.format.type === 'group_playoff') {
        if (form.format.group_count < 2) {
            errors.value.group_count = t('At least 2 groups required');
        }

        if (form.max_participants < form.format.group_count * 2) {
            errors.value.max_participants = t('Not enough participants for selected group count');
        }
    }

    if (form.entry_fee < 0) {
        errors.value.entry_fee = t('Entry fee cannot be negative');
    }

    if (form.prize_pool < 0) {
        errors.value.prize_pool = t('Prize pool cannot be negative');
    }
};

const fetchGames = async () => {
    try {
        // Mock games data
        games.value = [
            {id: 1, name: '8-Ball', type: 'pool'},
            {id: 2, name: '9-Ball', type: 'pool'},
            {id: 3, name: '10-Ball', type: 'pool'},
            {id: 4, name: 'Straight Pool', type: 'pool'},
            {id: 5, name: 'Bank Pool', type: 'pool'}
        ] as Game[];
    } catch (error) {
        console.error('Failed to fetch games:', error);
    }
};

const loadCitiesAndClubs = async () => {
    try {
        const [citiesResponse, clubsResponse] = await Promise.all([
            fetchCities(),
            fetchClubs()
        ]);

        await citiesResponse.execute();
        await clubsResponse.execute();

        if (citiesResponse.data.value) cities.value = citiesResponse.data.value;
        if (clubsResponse.data.value) clubs.value = clubsResponse.data.value;
    } catch (error) {
        console.error('Failed to fetch cities and clubs:', error);
    }
};

const updateFilteredClubs = () => {
    if (form.city_id) {
        const selectedCity = cities.value.find(c => c.id === form.city_id);
        if (selectedCity) {
            filteredClubs.value = clubs.value.filter(club =>
                club.city === selectedCity.name
            );
        }
    } else {
        filteredClubs.value = [];
        form.club_id = null;
    }
};

const handleCityChange = () => {
    form.club_id = null;
    updateFilteredClubs();
};

const handleFormatChange = (newFormat: typeof form.format) => {
    form.format = {...newFormat};

    // Auto-adjust max participants based on format
    if (newFormat.type === 'single_elimination' || newFormat.type === 'double_elimination') {
        // Ensure power of 2
        const nextPowerOf2 = Math.pow(2, Math.ceil(Math.log2(form.max_participants)));
        if (form.max_participants !== nextPowerOf2) {
            form.max_participants = nextPowerOf2;
        }
    }
};

const handleSeedingChange = (newSeeding: typeof form.seeding) => {
    form.seeding = {...newSeeding};
};

const handleTeamConfigChange = (newTeamConfig: typeof form.teams) => {
    form.teams = {...newTeamConfig};

    if (newTeamConfig.enabled) {
        form.max_participants = newTeamConfig.max_teams * newTeamConfig.team_size;
    }
};

const handleSubmit = async () => {
    validateForm();

    if (!isFormValid.value) {
        return;
    }

    isSubmitting.value = true;

    try {
        const payload = {
            ...form,
            game_id: games.value.find(g => g.name === form.discipline)?.id || 1
        };

        const tournament = await tournamentStore.createTournament(payload);

        // Redirect to tournament detail page
        router.visit(`/tournaments/${tournament.id}`);
    } catch (error: any) {
        console.error('Failed to create tournament:', error);
        errors.value.submit = error.message || t('Failed to create tournament');
    } finally {
        isSubmitting.value = false;
    }
};

const handleCancel = () => {
    router.visit('/tournaments');
};

onMounted(() => {
    fetchGames();
    loadCitiesAndClubs();
});
</script>

<template>
    <Head :title="t('Create Tournament')"/>

    <div class="py-12">
        <div class="mx-auto max-w-4xl sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">
                    {{ t('Create Tournament') }}
                </h1>
                <p class="mt-2 text-gray-600 dark:text-gray-400">
                    {{ t('Set up a new billiard tournament with custom format and rules') }}
                </p>
            </div>

            <form class="space-y-8" @submit.prevent="handleSubmit">
                <!-- Basic Information -->
                <Card>
                    <CardHeader>
                        <CardTitle>{{ t('Basic Information') }}</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <Label for="name">{{ t('Tournament Name') }} *</Label>
                                <Input
                                    id="name"
                                    v-model="form.name"
                                    :class="{ 'border-red-500': errors.name }"
                                    :placeholder="t('e.g., Spring Open 2024')"
                                    required
                                />
                                <p v-if="errors.name" class="text-sm text-red-600">{{ errors.name }}</p>
                            </div>

                            <div class="space-y-2">
                                <Label for="discipline">{{ t('Discipline') }}</Label>
                                <Select v-model="form.discipline">
                                    <SelectTrigger>
                                        <SelectValue :placeholder="t('Select game type')"/>
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="game in games" :key="game.id" :value="game.name">
                                            {{ game.name }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <Label for="organizer">{{ t('Organizer') }}</Label>
                            <Input
                                id="organizer"
                                v-model="form.organizer"
                                :placeholder="t('Tournament organizer name')"
                            />
                        </div>

                        <div class="space-y-2">
                            <Label for="description">{{ t('Description') }}</Label>
                            <Textarea
                                id="description"
                                v-model="form.description"
                                :placeholder="t('Tournament description, rules, and additional information')"
                                rows="3"
                            />
                        </div>
                    </CardContent>
                </Card>

                <!-- Location -->
                <Card>
                    <CardHeader>
                        <CardTitle>{{ t('Location') }}</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <Label for="city">{{ t('City') }}</Label>
                                <Select v-model="form.city_id" @update:model-value="handleCityChange">
                                    <SelectTrigger>
                                        <SelectValue :placeholder="t('Select city')"/>
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="city in cities" :key="city.id" :value="city.id">
                                            {{ city.name }}, {{ city.country.name }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <div class="space-y-2">
                                <Label for="club">{{ t('Club/Venue') }}</Label>
                                <Select v-model="form.club_id" :disabled="!form.city_id">
                                    <SelectTrigger>
                                        <SelectValue :placeholder="t('Select club')"/>
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="club in filteredClubs" :key="club.id" :value="club.id">
                                            {{ club.name }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <Label for="venue_details">{{ t('Venue Details') }}</Label>
                            <Input
                                id="venue_details"
                                v-model="form.venue_details"
                                :placeholder="t('Address, room number, additional location info')"
                            />
                        </div>
                    </CardContent>
                </Card>

                <!-- Schedule & Limits -->
                <Card>
                    <CardHeader>
                        <CardTitle>{{ t('Schedule & Limits') }}</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div class="space-y-2">
                                <Label for="start_date">{{ t('Start Date') }} *</Label>
                                <Input
                                    id="start_date"
                                    v-model="form.start_date"
                                    :class="{ 'border-red-500': errors.start_date }"
                                    required
                                    type="datetime-local"
                                />
                                <p v-if="errors.start_date" class="text-sm text-red-600">{{ errors.start_date }}</p>
                            </div>

                            <div class="space-y-2">
                                <Label for="end_date">{{ t('End Date') }} *</Label>
                                <Input
                                    id="end_date"
                                    v-model="form.end_date"
                                    :class="{ 'border-red-500': errors.end_date }"
                                    required
                                    type="datetime-local"
                                />
                                <p v-if="errors.end_date" class="text-sm text-red-600">{{ errors.end_date }}</p>
                            </div>

                            <div class="space-y-2">
                                <Label for="registration_deadline">{{ t('Registration Deadline') }}</Label>
                                <Input
                                    id="registration_deadline"
                                    v-model="form.registration_deadline"
                                    :class="{ 'border-red-500': errors.registration_deadline }"
                                    type="datetime-local"
                                />
                                <p v-if="errors.registration_deadline" class="text-sm text-red-600">
                                    {{ errors.registration_deadline }}</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <Label for="max_participants">{{ t('Max Participants') }} *</Label>
                                <Input
                                    id="max_participants"
                                    v-model.number="form.max_participants"
                                    :class="{ 'border-red-500': errors.max_participants }"
                                    min="2"
                                    required
                                    type="number"
                                />
                                <p v-if="errors.max_participants" class="text-sm text-red-600">
                                    {{ errors.max_participants }}</p>
                                <p class="text-sm text-gray-500">
                                    {{ t('Format requires') }}: {{ formatRequiredPlayers }} {{ t('participants') }}
                                </p>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div class="space-y-2">
                                    <Label for="entry_fee">{{ t('Entry Fee') }} (₴)</Label>
                                    <Input
                                        id="entry_fee"
                                        v-model.number="form.entry_fee"
                                        :class="{ 'border-red-500': errors.entry_fee }"
                                        min="0"
                                        step="0.01"
                                        type="number"
                                    />
                                    <p v-if="errors.entry_fee" class="text-sm text-red-600">{{ errors.entry_fee }}</p>
                                </div>

                                <div class="space-y-2">
                                    <Label for="prize_pool">{{ t('Prize Pool') }} (₴)</Label>
                                    <Input
                                        id="prize_pool"
                                        v-model.number="form.prize_pool"
                                        :class="{ 'border-red-500': errors.prize_pool }"
                                        min="0"
                                        step="0.01"
                                        type="number"
                                    />
                                    <p v-if="errors.prize_pool" class="text-sm text-red-600">{{ errors.prize_pool }}</p>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Tournament Format -->
                <FormatSelector
                    :format="form.format"
                    @update:format="handleFormatChange"
                />

                <!-- Seeding Options -->
                <SeedingSelector
                    :seeding="form.seeding"
                    @update:seeding="handleSeedingChange"
                />

                <!-- Team Configuration -->
                <TeamConfigSection
                    :config="form.teams"
                    @update:config="handleTeamConfigChange"
                />

                <!-- Submit Actions -->
                <div class="flex justify-end space-x-4 pt-6 border-t">
                    <Button
                        :disabled="isSubmitting"
                        type="button"
                        variant="outline"
                        @click="handleCancel"
                    >
                        {{ t('Cancel') }}
                    </Button>

                    <Button
                        :class="{ 'opacity-50 cursor-not-allowed': !isFormValid || isSubmitting }"
                        :disabled="!isFormValid || isSubmitting"
                        type="submit"
                    >
            <span v-if="isSubmitting" class="mr-2">
              <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" fill="none" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                      fill="currentColor"/>
              </svg>
            </span>
                        {{ isSubmitting ? t('Creating...') : t('Create Tournament') }}
                    </Button>
                </div>

                <!-- Error Display -->
                <div v-if="errors.submit" class="rounded-md bg-red-50 p-4 border border-red-200">
                    <p class="text-sm text-red-600">{{ errors.submit }}</p>
                </div>
            </form>
        </div>
    </div>
</template>
