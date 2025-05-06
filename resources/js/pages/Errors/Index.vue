<script lang="ts" setup>
import {Button} from '@/Components/ui';
import {Head, Link} from '@inertiajs/vue3';

const props = defineProps<{
    status: number;
    message?: string;
    description?: string;
}>();

// Determine error title and default message based on status code
const errorInfo = {
    401: {
        title: 'Unauthorized',
        description: 'You are not authorized to access this page. Please log in and try again.',
    },
    403: {
        title: 'Forbidden',
        description: 'You do not have permission to access this resource.',
    },
    404: {
        title: 'Page Not Found',
        description: 'The page you are looking for does not exist or has been moved.',
    },
    419: {
        title: 'Page Expired',
        description: 'Your session has expired. Please refresh and try again.',
    },
    429: {
        title: 'Too Many Requests',
        description: 'You have made too many requests. Please wait a moment and try again.',
    },
    500: {
        title: 'Server Error',
        description: 'Something went wrong on our server. We are working to fix the issue.',
    },
    503: {
        title: 'Service Unavailable',
        description: 'The service is temporarily unavailable. Please try again later.',
    },
};

const title = errorInfo[props.status as keyof typeof errorInfo]?.title || 'Error';
const description = props.description || errorInfo[props.status as keyof typeof errorInfo]?.description || 'An error occurred.';
</script>

<template>
    <Head :title="title" />

    <div class="flex min-h-screen flex-col items-center justify-center bg-gray-100 px-6 text-center dark:bg-gray-900">
        <div class="w-full max-w-md rounded-lg bg-white p-8 shadow-lg dark:bg-gray-800">
            <h1 class="text-primary mb-4 text-6xl font-extrabold">{{ props.status }}</h1>
            <h2 class="mb-2 text-2xl font-semibold text-gray-700 dark:text-gray-300">{{ title }}</h2>
            <p class="mb-6 text-gray-500 dark:text-gray-400">
                {{ description }}
            </p>

            <div class="flex flex-col justify-center space-y-3 sm:flex-row sm:space-y-0 sm:space-x-3">
                <Link href="/">
                    <Button>Go to Home</Button>
                </Link>

                <Link v-if="props.status === 401" href="/login">
                    <Button variant="outline">Log In</Button>
                </Link>

                <Button v-else-if="props.status === 419 || props.status === 429" variant="outline"
                        @click="() => window.location.reload()">
                    Refresh Page
                </Button>

                <Button v-else variant="outline" @click="() => window.history.back()"> Go Back</Button>
            </div>
        </div>
    </div>
</template>
