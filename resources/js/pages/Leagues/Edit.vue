<script lang="ts" setup>
import LeagueForm from '@/Components/LeagueForm.vue';
import {Button, Spinner} from '@/Components/ui';
import {useApi} from '@/composables/useApi';
import {useAuth} from '@/composables/useAuth';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import {apiClient} from '@/lib/apiClient';
import type {ApiError, League} from '@/types/api';
import {Head, Link, router} from '@inertiajs/vue3';
import {ArrowLeftIcon} from 'lucide-vue-next';
import {computed, onMounted, watchEffect} from 'vue';
import {useLocale} from '@/composables/useLocale';

// Apply the layout
defineOptions({layout: AuthenticatedLayout});

const props = defineProps<{
    leagueId: number | string; // ID of the league to edit
    header?: string; // Header from props (not used)
}>();

const {isAdmin} = useAuth();
const {t} = useLocale();

// Redirect if user is not admin
watchEffect(() => {
    if (isAdmin.value === false) {
        console.warn('Non-admin user tried to access Edit League page. Redirecting.');
        router.visit(route('leagues.index.page'), {replace: true});
    }
});

// Load league data
// Check if API returns object directly or wrapped in data property
const fetchLeagueFn = () => apiClient<League>(`/api/leagues/${props.leagueId}`);
const {data: league, isLoading, error: loadingError, execute: fetchLeague} = useApi<League>(fetchLeagueFn);

onMounted(() => {
    // Load only if user is admin (extra check)
    if (isAdmin.value === true) {
        fetchLeague();
    }
});

const handleSuccess = (updatedLeague: League) => {
    // Navigate to the updated league view
    router.visit(route('leagues.show.page', {league: updatedLeague.id}));
};

const handleError = (error: ApiError) => {
    // Form validation errors are handled within the LeagueForm component
    console.error('Failed to update league:', error);
};

// Dynamic page title
const pageTitle = computed(() =>
    league.value
        ? t('Edit :name', {name: league.value.name})
        : t('Edit League')
);
</script>

<template>
    <Head :title="pageTitle"/>
    <div class="py-12">
        <div class="mx-auto max-w-4xl sm:px-6 lg:px-8">
            <div class="mb-6">
                <Link :href="route('leagues.index.page')">
                    <Button variant="outline">
                        <ArrowLeftIcon class="mr-2 h-4 w-4"/>
                        {{ t('Back to Leagues') }}
                    </Button>
                </Link>
                <Link v-if="league" :href="route('leagues.show.page', { league: league.id })" class="ml-4">
                    <Button variant="outline">{{ t('View League') }}</Button>
                </Link>
            </div>

            <div v-if="isAdmin">
                <div v-if="isLoading" class="p-10 text-center">
                    <Spinner class="text-primary mx-auto h-8 w-8"/>
                    <p class="mt-4 text-gray-500">{{ t('Loading league data...') }}</p>
                </div>
                <div v-else-if="loadingError" class="rounded bg-red-100 p-4 text-center text-red-600">
                    {{ t('Error loading league data: :error', {error: loadingError.message}) }}
                </div>
                <LeagueForm v-else-if="league" :is-edit-mode="true" :league="league" @error="handleError"
                            @submitted="handleSuccess"/>
                <div v-else class="p-10 text-center text-gray-500">{{ t('League not found or failed to load.') }}</div>
            </div>
            <div v-else class="rounded bg-red-100 p-4 text-center text-red-500">
                {{ t('You do not have permission to edit leagues. Redirecting...') }}
            </div>
        </div>
    </div>
</template>
