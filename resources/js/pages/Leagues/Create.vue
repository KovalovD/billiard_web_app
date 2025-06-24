<script lang="ts" setup>
import LeagueForm from '@/Components/LeagueForm.vue'; // Import form component
import {Button} from '@/Components/ui';
import {useAuth} from '@/composables/useAuth';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue'; // Import for defineOptions
import type {ApiError, League} from '@/types/api';
import {Head, Link, router} from '@inertiajs/vue3';
import {useLocale} from '@/composables/useLocale';
import {ArrowLeftIcon} from 'lucide-vue-next';
import {watchEffect} from 'vue'; // For checking admin status

// --- Apply the layout ---
defineOptions({layout: AuthenticatedLayout});
// ------------------------

defineProps<{ header?: string }>();

const {isAdmin} = useAuth();
const {t} = useLocale();

// Redirect if user is not admin (better to do this on backend via middleware)
watchEffect(() => {
    if (isAdmin.value === false) {
        // Explicit check against false to account for null during initialization
        console.warn('Non-admin user tried to access Create League page. Redirecting.');
        router.visit(route('leagues.index.page'), {replace: true});
    }
});

const handleSuccess = (createdLeague: League) => {
    // Navigate to the newly created league
    router.visit(route('leagues.show.page', {league: createdLeague.id}));
};

const handleError = (error: ApiError) => {
    // Form validation errors are handled within the LeagueForm component
    console.error('Failed to create league:', error);
};
</script>

<template>
    <Head :title="t('Create League')"/>
    <div class="py-12">
        <div class="mx-auto max-w-4xl sm:px-6 lg:px-8">
            <div class="mb-6">
                <Link :href="route('leagues.index.page')">
                    <Button variant="outline">
                        <ArrowLeftIcon class="mr-2 h-4 w-4"/>
                        {{ t('Back to Leagues') }}
                    </Button>
                </Link>
            </div>

            <LeagueForm v-if="isAdmin" :is-edit-mode="false" @error="handleError" @submitted="handleSuccess"/>
            <div v-else class="rounded bg-red-100 p-4 text-center text-red-500">
                {{ t('You do not have permission to create leagues. Redirecting...') }}
            </div>
        </div>
    </div>
</template>
