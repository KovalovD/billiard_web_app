<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Button } from '@/Components/ui';
</script>

<template>
    <Head title="Page Not Found" />
    <div class="min-h-screen flex flex-col items-center justify-center bg-gray-100 dark:bg-gray-900 text-center px-6">
        <h1 class="text-6xl font-extrabold text-primary mb-4">404</h1>
        <h2 class="text-2xl font-semibold text-gray-700 dark:text-gray-300 mb-2">Page Not Found</h2>
        <p class="text-gray-500 dark:text-gray-400 mb-6">
            Sorry, the page you are looking for does not exist or has been moved.
        </p>
        <Link :href="route('dashboard')">
            <Button>Go to Dashboard</Button>
        </Link>
    </div>
</template>
