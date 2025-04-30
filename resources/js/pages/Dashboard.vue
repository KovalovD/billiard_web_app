<script setup>
import {ref} from 'vue';
import {Head} from '@inertiajs/vue3';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';

// Use the authenticated layout
defineOptions({
    layout: AuthenticatedLayout
});

// We'll use the auth data directly from props instead of fetching it again
// This matches how Inertia.js normally handles auth state
const props = defineProps({
    auth: Object
});

// Get user from the auth prop that comes from the Inertia shared props
const user = ref(props.auth?.user || null);

// No need to fetch user data again - it's already provided by AuthenticatedLayout
// through Inertia's shared props
</script>

<template>
    <Head title="Dashboard"/>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Content State -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg dark:bg-gray-800">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <h1 class="text-xl font-semibold mb-4">Welcome back, {{ user?.firstname || 'User' }}!</h1>
                    <p class="mb-4">You are logged in.</p>

                    <div class="mt-6 space-x-4">
                        <a
                            class="inline-flex items-center px-4 py-2 bg-gray-200 rounded-md font-semibold text-gray-800 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600"
                            href="/leagues"
                        >
                            View Leagues
                        </a>

                        <a
                            class="inline-flex items-center px-4 py-2 bg-gray-200 rounded-md font-semibold text-gray-800 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600"
                            href="/profile/edit"
                        >
                            My Profile
                        </a>

                        <a
                            v-if="user?.is_admin"
                            class="inline-flex items-center px-4 py-2 bg-blue-600 rounded-md font-semibold text-white hover:bg-blue-700"
                            href="/leagues/create"
                        >
                            Create New League
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
