<script setup lang="ts">
import { ref } from 'vue';
import { useAuth } from '@/composables/useAuth';
import { fetchCsrfToken } from '@/bootstrap';
import { Head } from '@inertiajs/vue3';
import InputError from '@/Components/InputError.vue';
import { Button } from '@/Components/ui/Button.vue';
import { Input } from '@/Components/ui/Input.vue';
import { Label } from '@/Components/ui/Label.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';

// Use GuestLayout specifically for login page
defineOptions({ layout: GuestLayout });

const { login, isActing, error: authError } = useAuth();

const form = ref({
    email: '',
    password: '',
});

// Form-specific errors
const formError = ref<string | null>(null);

const submit = async (e: Event) => {
    // Prevent default form submission which would cause a page reload
    e.preventDefault();

    formError.value = null;

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
        console.log('[Login] Getting CSRF token first');
        // Fetch CSRF token before attempting login
        await fetchCsrfToken();

        console.log('[Login] Submitting login form');
        await login({
            email: form.value.email,
            password: form.value.password
        });
        // Redirect happens inside login() in useAuth composable
    } catch (err: any) {
        console.error('Login component caught error:', err);

        // Check for validation errors from API
        if (err.data?.errors) {
            // Handle Laravel validation errors if present
            const errorMessages = Object.values(err.data.errors).flat();
            formError.value = errorMessages.join(', ');
        } else {
            // Use general error from useAuth
            formError.value = authError.value || 'Login failed. Please try again.';
        }
    }
};
</script>

<template>
    <Head title="Log in" />

    <div class="flex items-center justify-center">
        <div class="w-full max-w-md px-6 py-8 bg-white shadow-md overflow-hidden sm:rounded-lg dark:bg-gray-800">
            <h1 class="text-2xl font-bold mb-6 text-center">Log in to your account</h1>

            <form @submit.prevent="submit" class="space-y-6">
                <div>
                    <Label for="email">Email</Label>
                    <Input
                        id="email"
                        type="email"
                        v-model="form.email"
                        required
                        autofocus
                        :disabled="isActing"
                        placeholder="name@example.com"
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
                        :disabled="isActing"
                        placeholder="Your password"
                        class="mt-1 block w-full"
                    />
                </div>

                <div v-if="formError" class="text-sm text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20 p-3 rounded">
                    {{ formError }}
                </div>

                <Button type="submit" class="w-full justify-center" :disabled="isActing">
                    <span v-if="isActing" class="mr-2">Logging in...</span>
                    <span v-else>Log in</span>
                </Button>
            </form>
        </div>
    </div>
</template>
