// resources/js/pages/Admin/OfficialRatings/Create.vue
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
    Spinner,
    Textarea
} from '@/Components/ui';
import {useOfficialRatings} from '@/composables/useOfficialRatings';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import {apiClient} from '@/lib/apiClient';
import type {CreateOfficialRatingPayload, Game} from '@/types/api';
import {Head, Link, router} from '@inertiajs/vue3';
import {ArrowLeftIcon, PlusIcon, SaveIcon} from 'lucide-vue-next';
import {computed, onMounted, ref} from 'vue';

defineOptions({layout: AuthenticatedLayout});

const {createOfficialRating} = useOfficialRatings();

const form = ref<CreateOfficialRatingPayload>({
    name: '',
    description: '',
    game_id: 0,
    initial_rating: 1000,
    calculation_method: 'tournament_points',
    rating_rules: []
});

const games = ref<Game[]>([]);
const isLoadingGames = ref(true);
const isSaving = ref(false);
const error = ref<string | null>(null);
const validationErrors = ref<Record<string, string[]>>({});

const isFormValid = computed(() => {
    return form.value.name.trim() !== '' &&
        form.value.game_id > 0 &&
        form.value.initial_rating > 0;
});

const calculationMethods = [
    {value: 'tournament_points', label: 'Tournament Points'},
    {value: 'elo', label: 'ELO Rating'},
    {value: 'custom', label: 'Custom'}
];

const fetchGames = async () => {
    isLoadingGames.value = true;
    try {
        games.value = await apiClient<Game[]>('/api/available-games');
    } catch (err: any) {
        error.value = 'Failed to load games';
    } finally {
        isLoadingGames.value = false;
    }
};

const handleSubmit = async () => {
    if (!isFormValid.value) return;

    isSaving.value = true;
    error.value = null;
    validationErrors.value = {};

    try {
        const createAction = createOfficialRating();
        const success = await createAction.execute(form.value);

        if (success) {
            router.visit('/official-ratings');
        } else if (createAction.error.value) {
            const apiError = createAction.error.value;
            if (apiError.data?.errors) {
                validationErrors.value = apiError.data.errors;
            } else {
                error.value = apiError.message || 'Failed to create official rating';
            }
        }
    } catch (err: any) {
        error.value = err.message || 'Failed to create official rating';
    } finally {
        isSaving.value = false;
    }
};

const getValidationError = (field: string): string => {
    if (!validationErrors.value[field]) return '';
    return validationErrors.value[field].join(', ');
};

const clearValidationError = (field: string) => {
    if (validationErrors.value[field]) {
        delete validationErrors.value[field];
    }
};

onMounted(() => {
    fetchGames();
});
</script>

<template>
    <Head title="Create Official Rating"/>

    <div class="py-12">
        <div class="mx-auto max-w-2xl sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-6 flex items-center justify-between">
                <Link href="/official-ratings">
                    <Button variant="outline">
                        <ArrowLeftIcon class="mr-2 h-4 w-4"/>
                        Back to Ratings
                    </Button>
                </Link>

                <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">
                    Create Official Rating
                </h1>
            </div>

            <!-- Error Message -->
            <div v-if="error" class="mb-6 rounded bg-red-100 p-4 text-red-500 dark:bg-red-900/30 dark:text-red-400">
                {{ error }}
            </div>

            <!-- Create Form -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <PlusIcon class="h-5 w-5"/>
                        New Official Rating
                    </CardTitle>
                    <CardDescription>
                        Create a new official rating system for tracking player performance
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <form class="space-y-6" @submit.prevent="handleSubmit">
                        <!-- Basic Information -->
                        <div class="grid grid-cols-1 gap-6">
                            <!-- Name -->
                            <div class="space-y-2">
                                <Label for="name">Rating Name *</Label>
                                <Input
                                    id="name"
                                    v-model="form.name"
                                    :class="{ 'border-red-300': getValidationError('name') }"
                                    placeholder="e.g., Ukrainian Pool Championship Rating"
                                    required
                                    @input="clearValidationError('name')"
                                />
                                <p v-if="getValidationError('name')" class="text-sm text-red-600">
                                    {{ getValidationError('name') }}
                                </p>
                            </div>

                            <!-- Description -->
                            <div class="space-y-2">
                                <Label for="description">Description</Label>
                                <Textarea
                                    id="description"
                                    v-model="form.description"
                                    :class="{ 'border-red-300': getValidationError('description') }"
                                    placeholder="Describe this rating system, its purpose, and rules..."
                                    rows="3"
                                    @input="clearValidationError('description')"
                                />
                                <p v-if="getValidationError('description')" class="text-sm text-red-600">
                                    {{ getValidationError('description') }}
                                </p>
                            </div>

                            <!-- Game -->
                            <div class="space-y-2">
                                <Label for="game">Game Type *</Label>
                                <div v-if="isLoadingGames" class="flex items-center gap-2">
                                    <Spinner class="h-4 w-4"/>
                                    <span class="text-sm text-gray-500">Loading games...</span>
                                </div>
                                <Select
                                    v-else
                                    v-model="form.game_id"
                                    @update:modelValue="clearValidationError('game_id')"
                                >
                                    <SelectTrigger :class="{ 'border-red-300': getValidationError('game_id') }">
                                        <SelectValue placeholder="Select a game type"/>
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem
                                            v-for="game in games"
                                            :key="game.id"
                                            :value="game.id"
                                        >
                                            {{ game.name }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="getValidationError('game_id')" class="text-sm text-red-600">
                                    {{ getValidationError('game_id') }}
                                </p>
                            </div>
                        </div>

                        <!-- Rating Configuration -->
                        <div class="border-t pt-6">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                                Rating Configuration
                            </h3>

                            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                                <!-- Initial Rating -->
                                <div class="space-y-2">
                                    <Label for="initial_rating">Initial Rating *</Label>
                                    <Input
                                        id="initial_rating"
                                        v-model.number="form.initial_rating"
                                        :class="{ 'border-red-300': getValidationError('initial_rating') }"
                                        min="0"
                                        placeholder="1000"
                                        required
                                        step="1"
                                        type="number"
                                        @input="clearValidationError('initial_rating')"
                                    />
                                    <p class="text-sm text-gray-600 dark:text-gray-400">
                                        Starting rating for new players
                                    </p>
                                    <p v-if="getValidationError('initial_rating')" class="text-sm text-red-600">
                                        {{ getValidationError('initial_rating') }}
                                    </p>
                                </div>

                                <!-- Calculation Method -->
                                <div class="space-y-2">
                                    <Label for="calculation_method">Calculation Method</Label>
                                    <Select
                                        v-model="form.calculation_method"
                                        @update:modelValue="clearValidationError('calculation_method')"
                                    >
                                        <SelectTrigger
                                            :class="{ 'border-red-300': getValidationError('calculation_method') }">
                                            <SelectValue placeholder="Select calculation method"/>
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem
                                                v-for="method in calculationMethods"
                                                :key="method.value"
                                                :value="method.value"
                                            >
                                                {{ method.label }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <p v-if="getValidationError('calculation_method')" class="text-sm text-red-600">
                                        {{ getValidationError('calculation_method') }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Method Information -->
                        <div v-if="form.calculation_method" class="bg-gray-50 p-4 rounded-lg dark:bg-gray-800">
                            <h4 class="font-medium text-gray-900 dark:text-gray-100 mb-2">
                                {{ calculationMethods.find(m => m.value === form.calculation_method)?.label }} Method
                            </h4>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                <span v-if="form.calculation_method === 'tournament_points'">
                                    Players earn points based on their tournament placement. Higher placement in larger tournaments yields more points.
                                </span>
                                <span v-else-if="form.calculation_method === 'elo'">
                                    Traditional ELO rating system where players gain/lose rating based on match results and opponent strength.
                                </span>
                                <span v-else-if="form.calculation_method === 'custom'">
                                    Custom rating calculation method. You can define specific rules for rating changes.
                                </span>
                            </p>
                        </div>

                        <!-- Actions -->
                        <div class="flex justify-end space-x-4 pt-6 border-t">
                            <Link href="/official-ratings">
                                <Button variant="outline">
                                    Cancel
                                </Button>
                            </Link>
                            <Button
                                :disabled="!isFormValid || isSaving"
                                type="submit"
                            >
                                <SaveIcon v-if="!isSaving" class="mr-2 h-4 w-4"/>
                                <Spinner v-else class="mr-2 h-4 w-4"/>
                                {{ isSaving ? 'Creating...' : 'Create Rating' }}
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
