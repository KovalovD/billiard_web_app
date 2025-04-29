<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { useAuth } from '@/composables/useAuth';
import { Button } from '@/Components/ui';

defineOptions({ layout: AuthenticatedLayout });
defineProps<{ title?: string }>();

const { user, isAdmin } = useAuth(); // Получаем user и isAdmin
</script>

<template>
    <Head title="Dashboard" />

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <h1 v-if="user" class="text-xl font-semibold mb-4">Welcome back, {{ user.firstname || 'User' }}!</h1>
                    <h1 v-else class="text-xl font-semibold mb-4">Welcome!</h1>

                    <p class="mb-2">{{ user ? 'You are logged in.' : 'Please log in to continue.' }}</p>

                    <p v-if="isAdmin" class="text-green-600 font-semibold mb-4">You have administrator privileges.</p>

                    <div v-if="user" class="mt-6 space-x-4">
                        <Link :href="route('leagues.index')">
                            <Button variant="outline">View Leagues</Button>
                        </Link>
                        <Link :href="route('profile.edit')">
                            <Button variant="outline">My Profile</Button>
                        </Link>
                        <Link v-if="isAdmin" :href="route('leagues.create')">
                            <Button>Create New League</Button>
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
