<script lang="ts" setup>
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import {Head, Link, router} from '@inertiajs/vue3';
import {useAuth} from '@/composables/useAuth';
import {useApi} from '@/composables/useApi';
import {apiClient} from '@/lib/apiClient';
import type {ApiError, League} from '@/types/api';
import LeagueForm from '@/Components/LeagueForm.vue';
import {Button, Spinner} from '@/Components/ui';
import {ArrowLeftIcon} from 'lucide-vue-next';
import {computed, onMounted, watchEffect} from 'vue';

// Apply the layout
defineOptions({layout: AuthenticatedLayout});

const props = defineProps<{
    leagueId: number | string; // ID of the league to edit
    header?: string; // Header from props (not used)
}>();

const {isAdmin} = useAuth();

// Redirect if user is not admin
watchEffect(() => {
    if (isAdmin.value === false) {
        console.warn('Non-admin user tried to access Edit League page. Redirecting.');
        router.visit(route('leagues.index'), {replace: true});
    }
});

// Load league data
// Check if API returns object directly or wrapped in data property
const fetchLeagueFn = () => apiClient<League>(`/api/leagues/${props.leagueId}`);
const {
    data: league,
    isLoading,
    error: loadingError,
    execute: fetchLeague
} = useApi<League>(fetchLeagueFn);

onMounted(() => {
    // Load only if user is admin (extra check)
    if (isAdmin.value === true) {
        fetchLeague();
    }
});

const handleSuccess = (updatedLeague: League) => {
    // Navigate to the updated league view
    router.visit(route('leagues.show', {league: updatedLeague.id}));
};

const handleError = (error: ApiError) => {
    // Form validation errors are handled within the LeagueForm component
    console.error('Failed to update league:', error);
};

// Dynamic page title
const pageTitle = computed(() => league.value ? `Edit ${league.value.name}` : 'Edit League');
</script>

<template>
    <Head :title="pageTitle"/>
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
                    <Spinner class="w-8 h-8 text-primary mx-auto"/>
                    <p class="mt-4 text-gray-500">Loading league data...</p>
                </div>
                <div v-else-if="loadingError" class="text-center text-red-600 bg-red-100 p-4 rounded">
                    Error loading league data: {{ loadingError.message }}
                </div>
                <LeagueForm
                    v-else-if="league"
                    :is-edit-mode="true"
                    :league="league"
                    @error="handleError"
                    @submitted="handleSuccess"
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
