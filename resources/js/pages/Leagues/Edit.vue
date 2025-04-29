<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'; // Импорт для defineOptions
import { Head, Link, router } from '@inertiajs/vue3';
import { useAuth } from '@/composables/useAuth';
import { useApi } from '@/composables/useApi';
import { apiClient } from '@/lib/apiClient';
import type { League, ApiError, ApiItemResponse } from '@/Types/api'; // ApiItemResponse, если API оборачивает в data
import LeagueForm from '@/Components/LeagueForm.vue'; // Импортируем компонент формы
import { Button, Spinner } from '@/Components/ui';
import { ArrowLeftIcon } from 'lucide-vue-next';
import { onMounted, computed, watchEffect } from 'vue'; // Добавляем watchEffect

// --- ПРИМЕНЯЕМ ЛЕЙАУТ ТОЛЬКО ЗДЕСЬ ---
defineOptions({ layout: AuthenticatedLayout });
// ------------------------------------

const props = defineProps<{
    leagueId: number | string; // ID лиги из роута
    header?: string; // Заголовок из пропсов (не используется)
}>();

const { isAdmin } = useAuth();

// Редирект, если пользователь не админ
watchEffect(() => {
    if (isAdmin.value === false) {
        console.warn('Non-admin user tried to access Edit League page. Redirecting.');
        router.visit(route('leagues.index'), { replace: true });
    }
});

// Загрузка данных лиги
// Проверяем, возвращает ли /api/leagues/{id} объект напрямую или обернутый в data
// LeaguesController::show использует `new LeagueResource($league)`, что обычно оборачивает в data
const fetchLeagueFn = () => apiClient<ApiItemResponse<League>>(`/api/leagues/${props.leagueId}`);
const { data: leagueResponse, isLoading, error: loadingError, execute: fetchLeague } = useApi<ApiItemResponse<League>>(fetchLeagueFn);

// Извлекаем лигу из обертки data
const league = computed(() => leagueResponse.value?.data || null);

onMounted(() => {
    // Загружаем только если пользователь админ (доп. проверка)
    if (isAdmin.value === true) {
        fetchLeague();
    }
});

const handleSuccess = (updatedLeague: League) => {
    console.log('League updated:', updatedLeague);
    // alert('League updated successfully!'); // Уведомление
    router.visit(route('leagues.show', { league: updatedLeague.id }));
};

const handleError = (error: ApiError) => {
    console.error('Failed to update league:', error);
    // Ошибки формы обрабатываются внутри LeagueForm
};

// Динамический заголовок страницы
const pageTitle = computed(() => league.value ? `Edit ${league.value.name}` : 'Edit League');

</script>

<template>
    <Head :title="pageTitle" />
    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-6">
                <Link :href="route('leagues.index')">
                    <Button variant="outline">
                        <ArrowLeftIcon class="w-4 h-4 mr-2"/>
                        Back to Leagues
                    </Button>
                </Link>
                <Link v-if="league" :href="route('leagues.show', { league: league.id })" class="ml-4">
                    <Button variant="outline">View League</Button>
                </Link>
            </div>

            <div v-if="isAdmin">
                <div v-if="isLoading" class="text-center p-10">
                    <Spinner class="w-8 h-8 text-primary mx-auto" /> Loading league data...
                </div>
                <div v-else-if="loadingError" class="text-center text-red-600 bg-red-100 p-4 rounded">
                    Error loading league data: {{ loadingError.message }}
                </div>
                <LeagueForm
                    v-else-if="league"
                    :league="league"
                    :is-edit-mode="true"
                    @submitted="handleSuccess"
                    @error="handleError"
                />
                <div v-else class="text-center text-gray-500 p-10">
                    League not found or failed to load.
                </div>
            </div>
            <div v-else class="text-center text-red-500 bg-red-100 p-4 rounded">
                You do not have permission to edit leagues. Redirecting...
            </div>
        </div>
    </div>
</template>
