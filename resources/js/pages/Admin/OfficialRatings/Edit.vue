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
import type {CreateOfficialRatingPayload, OfficialRating} from '@/types/api';
import {Head, Link, router} from '@inertiajs/vue3';
import {ArrowLeftIcon, PencilIcon, SaveIcon, TrashIcon} from 'lucide-vue-next';
import {computed, onMounted, ref} from 'vue';

defineOptions({layout: AuthenticatedLayout});

const props = defineProps<{
    ratingId: number | string;
}>();

const {
    fetchOfficialRating,
    updateOfficialRating,
    deleteOfficialRating
} = useOfficialRatings();

const rating = ref<OfficialRating | null>(null);
const form = ref<Partial<CreateOfficialRatingPayload>>({
    name: '',
    description: '',
    game_type: '',
    is_active: true,
    initial_rating: 1000,
    calculation_method: 'tournament_points',
    rating_rules: []
});

const isLoading = ref(true);
const isSaving = ref(false);
const isDeleting = ref(false);
const showDeleteConfirm = ref(false);
const error = ref<string | null>(null);
const validationErrors = ref<Record<string, string[]>>({});
const successMessage = ref<string | null>(null);

const isFormValid = computed(() => {
    return form.value.name?.trim() !== '' &&
        form.value.game_type !== '' &&
        form.value.initial_rating && form.value.initial_rating > 0;
});

const gameTypes = [
    {value: 'pool', label: 'Pool'},
    {value: 'pyramid', label: 'Pyramid'},
    {value: 'snooker', label: 'Snooker'}
];

const calculationMethods = [
    {value: 'tournament_points', label: 'Tournament Points'},
    {value: 'elo', label: 'ELO Rating'},
    {value: 'custom', label: 'Custom'}
];

const fetchData = async () => {
    isLoading.value = true;
    error.value = null;

    try {
        const ratingApi = fetchOfficialRating(props.ratingId);
        await ratingApi.execute();

        const r = ratingApi.data.value;

        if (r) {
            rating.value = r;

            form.value = {
                name: r.name,
                description: r.description,
                game_type: r.game_type,
                is_active: r.is_active,
                initial_rating: r.initial_rating,
                calculation_method: r.calculation_method,
                rating_rules: r.rating_rules,
            };
        }
    } catch (err: any) {
        error.value = err.message || 'Failed to load rating data';
    } finally {
        isLoading.value = false;
    }
};

const handleSubmit = async () => {
    if (!isFormValid.value) return;

    isSaving.value = true;
    error.value = null;
    validationErrors.value = {};
    successMessage.value = null;

    try {
        const updateAction = updateOfficialRating(props.ratingId);
        const success = await updateAction.execute(form.value);

        if (success) {
            successMessage.value = 'Official rating updated successfully!';
            await fetchData(); // Refresh data
        } else if (updateAction.error.value) {
            const apiError = updateAction.error.value;
            if (apiError.data?.errors) {
                validationErrors.value = apiError.data.errors;
            } else {
                error.value = apiError.message || 'Failed to update official rating';
            }
        }
    } catch (err: any) {
        error.value = err.message || 'Failed to update official rating';
    } finally {
        isSaving.value = false;
    }
};

const handleDelete = async () => {
    if (!rating.value) return;

    isDeleting.value = true;
    error.value = null;

    try {
        const deleteAction = deleteOfficialRating(props.ratingId);
        const success = await deleteAction.execute();

        if (success) {
            router.visit('/official-ratings');
        } else if (deleteAction.error.value) {
            error.value = deleteAction.error.value.message || 'Failed to delete official rating';
        }
    } catch (err: any) {
        error.value = err.message || 'Failed to delete official rating';
    } finally {
        isDeleting.value = false;
        showDeleteConfirm.value = false;
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
    if (successMessage.value) {
        successMessage.value = null;
    }
};

onMounted(() => {
    fetchData();
});
</script>

<template>
    <Head :title="rating ? `Edit Rating: ${rating.name}` : 'Edit Official Rating'"/>

    <div class="py-12">
        <div class="mx-auto max-w-2xl sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-6 flex items-center justify-between">
                <Link :href="`/official-ratings/${rating?.slug}`">
                    <Button variant="outline">
                        <ArrowLeftIcon class="mr-2 h-4 w-4"/>
                        Back to Rating
                    </Button>
                </Link>

                <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">
                    Edit Official Rating
                </h1>
            </div>

            <!-- Loading State -->
            <div v-if="isLoading" class="flex items-center justify-center py-10">
                <Spinner class="text-primary h-8 w-8"/>
                <span class="ml-2 text-gray-500 dark:text-gray-400">Loading rating...</span>
            </div>

            <!-- Error Message -->
            <div v-else-if="error"
                 class="mb-6 rounded bg-red-100 p-4 text-red-500 dark:bg-red-900/30 dark:text-red-400">
                {{ error }}
            </div>

            <!-- Success Message -->
            <div v-if="successMessage"
                 class="mb-6 rounded bg-green-100 p-4 text-green-600 dark:bg-green-900/30 dark:text-green-400">
                {{ successMessage }}
            </div>

            <!-- Edit Form -->
            <Card v-if="!isLoading">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <PencilIcon class="h-5 w-5"/>
                        Edit {{ rating?.name }}
                    </CardTitle>
                    <CardDescription>
                        Update the official rating system settings
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

                            <!-- Game Type -->
                            <div class="space-y-2">
                                <Label for="game_type">Game Type *</Label>
                                <Select
                                    v-model="form.game_type"
                                    @update:modelValue="clearValidationError('game_type')"
                                >
                                    <SelectTrigger :class="{ 'border-red-300': getValidationError('game_type') }">
                                        <SelectValue placeholder="Select a game type"/>
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem
                                            v-for="gameType in gameTypes"
                                            :key="gameType.value"
                                            :value="gameType.value"
                                        >
                                            {{ gameType.label }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="getValidationError('game_type')" class="text-sm text-red-600">
                                    {{ getValidationError('game_type') }}
                                </p>
                            </div>

                            <!-- Active Status -->
                            <div class="space-y-2">
                                <Label class="flex items-center gap-2">
                                    <input
                                        v-model="form.is_active"
                                        class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                        type="checkbox"
                                        @change="clearValidationError('is_active')"
                                    />
                                    Active Rating
                                </Label>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    Only active ratings will be visible to players and count towards rankings
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

                        <!-- Rating Stats -->
                        <div v-if="rating" class="border-t pt-6">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                                Rating Statistics
                            </h3>

                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                <div class="text-center p-4 bg-gray-50 rounded-lg dark:bg-gray-800">
                                    <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                                        {{ rating.players_count }}
                                    </div>
                                    <div class="text-sm text-gray-600 dark:text-gray-400">Players</div>
                                </div>
                                <div class="text-center p-4 bg-gray-50 rounded-lg dark:bg-gray-800">
                                    <div class="text-2xl font-bold text-green-600 dark:text-green-400">
                                        {{ rating.tournaments_count }}
                                    </div>
                                    <div class="text-sm text-gray-600 dark:text-gray-400">Tournaments</div>
                                </div>
                                <div class="text-center p-4 bg-gray-50 rounded-lg dark:bg-gray-800">
                                    <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">
                                        {{ rating.is_active ? 'Active' : 'Inactive' }}
                                    </div>
                                    <div class="text-sm text-gray-600 dark:text-gray-400">Status</div>
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex justify-between pt-6 border-t">
                            <Button
                                type="button"
                                variant="destructive"
                                @click="showDeleteConfirm = true"
                            >
                                <TrashIcon class="mr-2 h-4 w-4"/>
                                Delete Rating
                            </Button>

                            <div class="flex space-x-4">
                                <Link :href="`/official-ratings/${props.ratingId}`">
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
                                    {{ isSaving ? 'Updating...' : 'Update Rating' }}
                                </Button>
                            </div>
                        </div>
                    </form>
                </CardContent>
            </Card>

            <!-- Delete Confirmation Modal -->
            <div
                v-if="showDeleteConfirm"
                class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 flex items-center justify-center"
                @click="showDeleteConfirm = false"
            >
                <div
                    class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-xl max-w-md w-full mx-4"
                    @click.stop
                >
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                        Delete Official Rating
                    </h3>
                    <p class="text-gray-600 dark:text-gray-400 mb-6">
                        Are you sure you want to delete "{{ rating?.name }}"? This action cannot be undone and will
                        remove all associated player data.
                    </p>
                    <div class="flex justify-end space-x-4">
                        <Button
                            variant="outline"
                            @click="showDeleteConfirm = false"
                        >
                            Cancel
                        </Button>
                        <Button
                            :disabled="isDeleting"
                            variant="destructive"
                            @click="handleDelete"
                        >
                            <Spinner v-if="isDeleting" class="mr-2 h-4 w-4"/>
                            {{ isDeleting ? 'Deleting...' : 'Delete' }}
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
