<script lang="ts" setup>
import {Head, Link} from '@inertiajs/vue3';
import RegisterForm from "@/Components/Auth/RegisterForm.vue";
import {useLocale} from '@/composables/useLocale';
import AuthenticatedLayout from "@/layouts/AuthenticatedLayout.vue";

defineOptions({
    layout: AuthenticatedLayout,
});

const {t} = useLocale();
</script>

<template>
    <Head :title="t('Register')"/>

    <div
        class="min-h-screen flex flex-col items-center justify-center bg-gradient-to-br from-gray-50 to-blue-50 dark:from-gray-900 dark:to-gray-800 px-4 py-12">
        <div class="w-full max-w-md">
            <!-- Header -->
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                    {{ t('Join WinnerBreak') }}
                </h1>
                <p class="mt-2 text-gray-600 dark:text-gray-400">
                    {{ t('Start your competitive billiard journey today') }}
                </p>
            </div>

            <RegisterForm/>

            <!-- Additional links -->
            <div class="mt-8 text-center space-y-2">
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    {{ t('Already have an account?') }}
                    <Link :href="route('login')"
                          class="font-semibold text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 dark:hover:text-indigo-300 transition-colors">
                        {{ t('Sign in') }}
                    </Link>
                </p>
                <Link href="/"
                      class="block text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200 transition-colors">
                    ← {{ t('Back to home') }}
                </Link>
            </div>
        </div>
    </div>
</template>
