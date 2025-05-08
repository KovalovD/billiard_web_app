<script lang="ts" setup>

import {Head} from '@inertiajs/vue3';
import RegisterForm from "@/Components/Auth/RegisterForm.vue";
</script>

<template>
    <Head title="Register" />

    <div class="flex min-h-screen flex-col items-center bg-gray-100 pt-6 sm:justify-center sm:pt-0 dark:bg-gray-900">
        <div class="mt-6 w-full px-6 py-4 sm:max-w-md">
            <RegisterForm></RegisterForm>
        </div>
    </div>
</template>
