<script lang="ts" setup>
import {Button, Card, CardContent, CardHeader, CardTitle, Spinner} from '@/Components/ui';
import {useAuth} from '@/composables/useAuth';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import {apiClient} from '@/lib/apiClient';
import type {OfficialRating} from '@/types/api';
import {Head, Link} from '@inertiajs/vue3';
import {
    CheckCircleIcon,
    EyeIcon,
    PencilIcon,
    PlusIcon,
    SettingsIcon,
    StarIcon,
    TrophyIcon,
    UsersIcon,
    XCircleIcon
} from 'lucide-vue-next';
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

            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <StarIcon class="h-5 w-5"/>
                        Rating Systems
                    </CardTitle>
                </CardHeader>
                <CardContent>
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
                    <div v-else-if="filteredRatings.length === 0"
                         class="py-10 text-center text-gray-500 dark:text-gray-400">
                        <StarIcon class="mx-auto h-12 w-12 mb-4 opacity-50"/>
                        <p class="text-lg">No official ratings found</p>
                        <p class="text-sm">
                            {{
                                showInactiveRatings ? 'No ratings have been created yet.' : 'No active ratings available.'
                            }}
                        </p>
                    </div>

                    <!-- Ratings Table -->
                    <div v-else class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">
                                    Rating System
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">
                                    Game
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">
                                    Status
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">
                                    Players
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">
                                    Tournaments
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">
                                    Actions
                                </th>
                            </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-900 dark:divide-gray-700">
                            <tr
                                v-for="rating in filteredRatings"
                                :key="rating.id"
                                :class="[
                                        'hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors',
                                        !rating.is_active && 'opacity-60'
                                    ]"
                            >
                                <!-- Rating System Name -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-8 w-8">
                                            <div
                                                class="h-8 w-8 rounded-full bg-yellow-100 dark:bg-yellow-900/30 flex items-center justify-center">
                                                <StarIcon class="h-4 w-4 text-yellow-600 dark:text-yellow-400"/>
                                            </div>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                {{ rating.name }}
                                            </div>
                                            <div v-if="rating.description"
                                                 class="text-sm text-gray-500 dark:text-gray-400 truncate max-w-xs">
                                                {{ rating.description }}
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Game -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center text-sm text-gray-900 dark:text-gray-100">
                                        <TrophyIcon class="h-4 w-4 mr-2 text-gray-400"/>
                                        {{ rating.game?.name || 'N/A' }}
                                    </div>
                                </td>

                                <!-- Status -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <CheckCircleIcon v-if="rating.is_active" class="h-4 w-4 text-green-500 mr-2"/>
                                        <XCircleIcon v-else class="h-4 w-4 text-red-500 mr-2"/>
                                        <span
                                            :class="rating.is_active ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'">
                                                {{ rating.is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                    </div>
                                </td>

                                <!-- Players -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center text-sm text-gray-900 dark:text-gray-100">
                                        <UsersIcon class="h-4 w-4 mr-2 text-gray-400"/>
                                        {{ rating.players_count }}
                                    </div>
                                </td>

                                <!-- Tournaments -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center text-sm text-gray-900 dark:text-gray-100">
                                        <TrophyIcon class="h-4 w-4 mr-2 text-gray-400"/>
                                        {{ rating.tournaments_count }}
                                    </div>
                                </td>

                                <!-- Actions -->
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex justify-end space-x-2">
                                        <Link :href="`/official-ratings/${rating.id}`">
                                            <Button size="sm" variant="outline">
                                                <EyeIcon class="h-4 w-4"/>
                                            </Button>
                                        </Link>

                                        <Link v-if="isAdmin" :href="`/admin/official-ratings/${rating.id}/manage`">
                                            <Button size="sm" variant="outline">
                                                <SettingsIcon class="h-4 w-4"/>
                                            </Button>
                                        </Link>

                                        <Link v-if="isAdmin" :href="`/admin/official-ratings/${rating.id}/edit`">
                                            <Button size="sm" variant="outline">
                                                <PencilIcon class="h-4 w-4"/>
                                            </Button>
                                        </Link>
                                    </div>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
