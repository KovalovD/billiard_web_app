<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'; // Импорт для defineOptions
import { Head, Link } from '@inertiajs/vue3';
import { useAuth } from '@/composables/useAuth';
import { useApi, useApiAction } from '@/composables/useApi'; // useApiAction нужен для удаления
import { apiClient } from '@/lib/apiClient';
import type { League, ApiError } from '@/Types/api';
import { Button, Card, CardContent, CardHeader, CardTitle, Spinner } from '@/Components/ui';
import { PlusIcon, EyeIcon, PencilIcon, TrashIcon } from 'lucide-vue-next';
import { onMounted } from 'vue';

// --- Указываем лейаут ТОЛЬКО ЗДЕСЬ ---
defineOptions({ layout: AuthenticatedLayout });
// -----------------------------------------

// header лучше передавать из контроллера: Inertia::render('...', ['header' => 'Leagues'])
defineProps<{ header?: string }>();
const { isAdmin } = useAuth(); // Нужен для условного отображения кнопок

// Ожидаем прямой массив League[] от API эндпоинта /api/leagues
const fetchLeaguesFn = () => apiClient<League[]>('/api/leagues');

// Получаем данные и состояние загрузки/ошибки
const { data: leagues, isLoading, error: loadingError, execute: fetchLeagues } = useApi<League[]>(fetchLeaguesFn);

// Логика для кнопки удаления (раскомментируй при необходимости)
/*
const { execute: deleteLeagueAction, isActing: isDeleting, error: deleteError } = useApiAction(
    (leagueId: number) => apiClient<void>(`/api/leagues/${leagueId}`, { method: 'DELETE' })
);

const handleDelete = async (league: League | null | undefined) => {
    if (!league || !league.id) {
         alert('Error: Cannot delete league without ID.');
         return;
    }
    if (!confirm(`Are you sure you want to delete league "${league.name}"? This cannot be undone.`)) return;
    const success = await deleteLeagueAction(league.id);
    if (success) {
        await fetchLeagues(); // Обновляем список после успешного удаления
    } else {
        alert(`Failed to delete league: ${deleteError.value?.message || 'Unknown error'}`);
    }
};
*/

// Безопасная функция для генерации URL с параметрами
const getLeagueUrl = (routeName: 'leagues.show' | 'leagues.edit', leagueId: number | undefined | null): string | null => {
    // Проверяем, что leagueId передан и является числом
    if (typeof leagueId === 'number') {
        try {
            // Передаем параметры как объект { league: leagueId }
            return route(routeName, { league: leagueId });
        } catch (e) {
            console.error(`Ziggy error generating ${routeName} route with ID ${leagueId}:`, e);
            return null; // Возвращаем null в случае ошибки
        }
    }
    console.warn(`Invalid leagueId (${leagueId}) passed to getLeagueUrl for route ${routeName}`);
    return null;
};

// Загружаем лиги при монтировании компонента
onMounted(() => {
    fetchLeagues();
});

</script>

<template>
    <Head title="Leagues" />
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">Available Leagues</h1>
                <Link v-if="isAdmin" :href="route('leagues.create')">
                    <Button>
                        <PlusIcon class="w-4 h-4 mr-2" />
                        Create New League
                    </Button>
                </Link>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>League List</CardTitle>
                </CardHeader>
                <CardContent>
                    <div v-if="isLoading" class="flex justify-center items-center py-10">
                        <Spinner class="w-8 h-8 text-primary" />
                        <span class="ml-2 text-gray-500 dark:text-gray-400">Loading leagues...</span>
                    </div>

                    <div v-else-if="loadingError" class="text-center text-red-600 dark:text-red-400 bg-red-100 dark:bg-red-900/30 p-4 rounded">
                        Error loading leagues: {{ loadingError.message }}
                        <pre v-if="loadingError.data?.errors" class="text-xs text-left mt-2 whitespace-pre-wrap">{{ JSON.stringify(loadingError.data.errors, null, 2) }}</pre>
                    </div>

                    <div v-else-if="!leagues || leagues.length === 0" class="text-center text-gray-500 dark:text-gray-400 py-10">
                        No leagues found.
                        <span v-if="isAdmin"> Start by creating one!</span>
                    </div>

                    <ul v-else class="space-y-4">
                        <li v-for="(league, index) in leagues" :key="`league-${league?.id || index}`"
                            class="border dark:border-gray-700 rounded-lg p-4 flex flex-col sm:flex-row justify-between items-start sm:items-center hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">

                            <div v-if="league" class="mb-4 sm:mb-0">
                                <h2 class="text-lg font-semibold text-blue-700 dark:text-blue-400">{{ league.name ?? 'Unnamed League' }}</h2>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Game: {{ league.game ?? 'N/A' }}</p>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Matches Played: {{ league.matches_count ?? 0 }}</p>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Rating Enabled: <span :class="league.has_rating ? 'text-green-600' : 'text-red-600'">{{ league.has_rating ? 'Yes' : 'No' }}</span></p>
                            </div>
                            <div v-else class="text-red-500 dark:text-red-400">Invalid league data</div>

                            <div v-if="league" class="flex space-x-2 flex-shrink-0">
                                <Link v-if="getLeagueUrl('leagues.show', league.id)" :href="getLeagueUrl('leagues.show', league.id)!" title="View Details">
                                    <Button variant="outline" size="icon"> <EyeIcon class="w-4 h-4" /> </Button>
                                </Link>
                                <template v-if="isAdmin">
                                    <Link v-if="getLeagueUrl('leagues.edit', league.id)" :href="getLeagueUrl('leagues.edit', league.id)!" title="Edit League">
                                        <Button variant="outline" size="icon"> <PencilIcon class="w-4 h-4" /> </Button>
                                    </Link>
                                </template>
                            </div>
                        </li>
                    </ul>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
