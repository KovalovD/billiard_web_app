<script setup lang="ts">
import { ref } from 'vue';
import { useAuth } from '@/composables/useAuth';
import { router } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue'; // Simple layout for guest pages
import Button from '@/Components/ui/Button.vue';
import Input from '@/Components/ui/Input.vue';
import Label from '@/Components/ui/Label.vue';
import InputError from '@/Components/InputError.vue';

defineOptions({ layout: GuestLayout }); // Specify layout for this page

const { login, isLoading, error: authError } = useAuth(); // Get login and state from composable

const form = ref({
    email: '',
    password: '',
});

// Error specifically for this form (e.g., frontend validation failed)
const formError = ref<string | null>(null);

// IMPORTANT: Remove automatic redirection on mount to avoid infinite loops
// onMounted(() => {
//   if (isAuthenticated.value) {
//     router.visit(route('dashboard'), { replace: true });
//   }
// });

const submit = async () => {
    formError.value = null; // Reset local form error

    // Basic frontend validation
    if (!form.value.email) {
        formError.value = 'Email is required';
        return;
    }

    if (!form.value.password) {
        formError.value = 'Password is required';
        return;
    }

    try {
        console.log('[Login] Submitting login form');
        await login({ email: form.value.email, password: form.value.password });
        // Redirect happens inside login() in useAuth
    } catch (err: any) {
        // Error already recorded in authError in useAuth, but can add specific logic
        console.error('Login component caught error:', err);

        // Check for validation errors from API
        if (err.data?.errors) {
            // Handle Laravel validation errors if present in err.data.errors
            const errorMessages = Object.values(err.data.errors).flat();
            formError.value = errorMessages.join(', ');
        } else {
            // Use general error from useAuth
            formError.value = authError.value;
        }
    }
};
</script>

<template>
    <div class="flex items-center justify-center min-h-screen">
        <div class="w-full max-w-md p-8 space-y-6 bg-white rounded shadow-md dark:bg-gray-800 dark:text-white">
            <h2 class="text-2xl font-bold text-center">Login to B2B League</h2>
            <form @submit.prevent="submit" class="space-y-4">
                <div>
                    <Label for="email">Email</Label>
                    <Input
                        id="email"
                        type="email"
                        v-model="form.email"
                        required
                        autofocus
                        placeholder="your@email.com"
                        :disabled="isLoading"
                        class="mt-1 block w-full"
                    />
                </div>

                <div>
                    <Label for="password">Password</Label>
                    <Input
                        id="password"
                        type="password"
                        v-model="form.password"
                        required
                        placeholder="Your password"
                        :disabled="isLoading"
                        class="mt-1 block w-full"
                    />
                </div>

                <div v-if="formError" class="text-sm text-red-600 dark:text-red-400">
                    {{ formError }}
                </div>
                <div v-else-if="authError" class="text-sm text-red-600 dark:text-red-400">
                    {{ authError }}
                </div>

                <div>
                    <Button type="submit" class="w-full justify-center" :disabled="isLoading">
                        <span v-if="isLoading">Logging in...</span>
                        <span v-else>Login</span>
                    </Button>
                </div>
            </form>
        </div>
    </div>
</template>
