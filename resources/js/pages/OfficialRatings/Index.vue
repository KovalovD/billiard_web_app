// resources/js/pages/OfficialRatings/Index.vue
<script lang="ts" setup>
import {Button, Card, CardContent, CardDescription, CardHeader, CardTitle, Spinner} from '@/Components/ui';
import {useAuth} from '@/composables/useAuth';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import {apiClient} from '@/lib/apiClient';
import type {OfficialRating} from '@/types/api';
import {Head, Link} from '@inertiajs/vue3';
import {PlusIcon, StarIcon, TrophyIcon, UsersIcon} from 'lucide-vue-next';
import {computed, onMounted, ref} from 'vue';

defineOptions({layout: AuthenticatedLayout});

const {isAdmin} = useAuth();

const ratings = ref<OfficialRating[]>([]);
const isLoading = ref(true);
const error = ref<string | null>(null);
const showInactiveRatings = ref(false);

const filteredRatings = computed(() => {
    if (showInactiveRatings.value) {
        return ratings.value;
    }
    return ratings.value.filter(rating => rating.is_active);
});

const fetchRatings = async () => {
    isLoading.value = true;
    error.value = null;

    try {
        ratings.value = await apiClient<OfficialRating[]>('/api/official-ratings');
    } catch (err: any) {
        error.value = err.message || 'Failed to load official ratings';
    } finally {
        isLoading.value = false;
    }
};

const getCalculationMethodDisplay = (method: string): string => {
    switch (method) {
        case 'tournament_points':
            return 'Tournament Points';
        case 'elo':
            return 'ELO Rating';
        case 'custom':
            return 'Custom';
        default:
            return method;
    }
};

onMounted(() => {
    fetchRatings();
});
</script>

<template>
    <Head title="Official Ratings"/>

    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">Official Ratings</h1>
                    <p class="text-gray-600 dark:text-gray-400">Professional billiard player rankings</p>
                </div>

                <Link v-if="isAdmin" href="/admin/official-ratings/create">
                    <Button>
                        <PlusIcon class="mr-2 h-4 w-4"/>
                        Create Rating
                    </Button>
                </Link>
            </div>

            <!-- Filters -->
            <div class="mb-6 flex items-center gap-4">
                <label class="flex items-center gap-2">
                    <input
                        v-model="showInactiveRatings"
                        class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                        type="checkbox"
                    />
                    <span class="text-sm text-gray-700 dark:text-gray-300">Show inactive ratings</span>
                </label>
            </div>

            <!-- Loading State -->
            <div v-if="isLoading" class="flex items-center justify-center py-10">
                <Spinner class="text-primary h-8 w-8"/>
                <span class="ml-2 text-gray-500 dark:text-gray-400">Loading official ratings...</span>
            </div>

            <!-- Error State -->
            <div v-else-if="error"
                 class="rounded bg-red-100 p-4 text-center text-red-600 dark:bg-red-900/30 dark:text-red-400">
                Error loading official ratings: {{ error }}
            </div>

            <!-- Empty State -->
            <div v-else-if="filteredRatings.length === 0" class="py-10 text-center text-gray-500 dark:text-gray-400">
                <StarIcon class="mx-auto h-12 w-12 mb-4 opacity-50"/>
                <p class="text-lg">No official ratings found</p>
                <p class="text-sm">
                    {{ showInactiveRatings ? 'No ratings have been created yet.' : 'No active ratings available.' }}</p>
            </div>

            <!-- Ratings Grid -->
            <div v-else class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                <Card
                    v-for="rating in filteredRatings"
                    :key="rating.id"
                    :class="[
                        'hover:shadow-lg transition-shadow cursor-pointer',
                        !rating.is_active && 'opacity-60'
                    ]"
                >
                    <CardHeader>
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <CardTitle class="flex items-center gap-2 text-lg">
                                    {{ rating.name }}
                                    <span v-if="!rating.is_active"
                                          class="text-xs px-2 py-1 bg-gray-100 text-gray-600 rounded-full dark:bg-gray-800 dark:text-gray-400">
                                        Inactive
                                    </span>
                                </CardTitle>
                                <CardDescription class="mt-1">
                                    <div class="flex items-center gap-1">
                                        <TrophyIcon class="h-3 w-3"/>
                                        {{ rating.game?.name || 'N/A' }}
                                    </div>
                                </CardDescription>
                            </div>
                            <StarIcon class="h-6 w-6 text-yellow-500"/>
                        </div>
                    </CardHeader>

                    <CardContent>
                        <div class="space-y-3">
                            <!-- Description -->
                            <p v-if="rating.description" class="text-sm text-gray-600 dark:text-gray-400 line-clamp-2">
                                {{ rating.description }}
                            </p>

                            <!-- Stats -->
                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div class="flex items-center gap-2 text-gray-600 dark:text-gray-400">
                                    <UsersIcon class="h-4 w-4"/>
                                    <span>{{ rating.players_count }} players</span>
                                </div>
                                <div class="flex items-center gap-2 text-gray-600 dark:text-gray-400">
                                    <TrophyIcon class="h-4 w-4"/>
                                    <span>{{ rating.tournaments_count }} tournaments</span>
                                </div>
                            </div>

                            <!-- Rating Details -->
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Initial Rating:</span>
                                    <span class="font-medium">{{ rating.initial_rating }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Method:</span>
                                    <span class="font-medium">{{
                                            getCalculationMethodDisplay(rating.calculation_method)
                                        }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 flex justify-between items-center">
                            <Link :href="`/official-ratings/${rating.id}`">
                                <Button size="sm" variant="outline">
                                    View Rating
                                </Button>
                            </Link>

                            <Link v-if="isAdmin" :href="`/admin/official-ratings/${rating.id}/edit`">
                                <Button size="sm" variant="ghost">
                                    Edit
                                </Button>
                            </Link>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </div>
</template>
