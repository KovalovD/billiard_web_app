<script lang="ts" setup>
import {Button} from '@/Components/ui';
import {Head, Link} from '@inertiajs/vue3';
</script>

<template>
    <Head title="Page Not Found"/>
    <div class="flex min-h-screen flex-col items-center justify-center bg-gray-100 px-6 text-center dark:bg-gray-900">
        <h1 class="text-primary mb-4 text-6xl font-extrabold">404</h1>
        <h2 class="mb-2 text-2xl font-semibold text-gray-700 dark:text-gray-300">Page Not Found</h2>
        <p class="mb-6 text-gray-500 dark:text-gray-400">Sorry, the page you are looking for does not exist or has been
            moved.</p>
        <Link :href="route('dashboard')">
            <Button>Go to Dashboard</Button>
        </Link>
    </div>
</template>
