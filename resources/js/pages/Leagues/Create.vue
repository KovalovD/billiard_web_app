<script lang="ts" setup>
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue'; // Импорт для defineOptions
import {Head, Link, router} from '@inertiajs/vue3';
import {useAuth} from '@/composables/useAuth';
import LeagueForm from '@/Components/LeagueForm.vue'; // Импортируем компонент формы
import {Button} from '@/Components/ui';
import {ArrowLeftIcon} from 'lucide-vue-next';
import type {ApiError, League} from '@/types/api';
import {watchEffect} from 'vue'; // Для проверки админа

// --- ПРИМЕНЯЕМ ЛЕЙАУТ ТОЛЬКО ЗДЕСЬ ---
defineOptions({layout: AuthenticatedLayout});
// ------------------------------------

defineProps<{ header?: string }>();

const {isAdmin} = useAuth();

// Редирект, если пользователь не админ (лучше делать на бэкенде через мидлвер)
watchEffect(() => {
    if (isAdmin.value === false) { // Явная проверка на false, т.к. null возможен при инициализации
        console.warn('Non-admin user tried to access Create League page. Redirecting.');
        router.visit(route('leagues.index'), {replace: true});
    }
});

const handleSuccess = (createdLeague: League) => {
    console.log('League created:', createdLeague);
    // alert('League created successfully!'); // Замени на систему уведомлений
    // Переходим на страницу созданной лиги
    router.visit(route('leagues.show', {league: createdLeague.id}));
};

const handleError = (error: ApiError) => {
    console.error('Failed to create league:', error);
    // Ошибки валидации отображаются внутри LeagueForm
    // Можно добавить общее уведомление об ошибке здесь при необходимости
};

</script>

<template>
    <Head title="Create League"/>
    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-6">
                <Link :href="route('leagues.index')">
                    <Button variant="outline">
                        <ArrowLeftIcon class="w-4 h-4 mr-2"/>
                        Back to Leagues
                    </Button>
                </Link>
            </div>

            <LeagueForm
                v-if="isAdmin"
                :is-edit-mode="false"
                @error="handleError"
                @submitted="handleSuccess"
            />
            <div v-else class="text-center text-red-500 bg-red-100 p-4 rounded">
                You do not have permission to create leagues. Redirecting...
            </div>
        </div>
    </div>
</template>
