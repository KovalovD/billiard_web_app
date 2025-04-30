<script setup>
import {ref} from 'vue';
import axios from 'axios';

// Direct imports, no dynamic UI components
const form = ref({
    email: '',
    password: ''
});

const error = ref('');
const processing = ref(false);

async function submit() {
    processing.value = true;
    error.value = '';

    try {
        // Get CSRF token
        await axios.get('/sanctum/csrf-cookie');

        // Basic login
        const res = await axios.post('/api/auth/login', {
            email: form.value.email,
            password: form.value.password,
            deviceName: 'web-browser'
        });

        // Store token
        if (res.data.token) {
            localStorage.setItem('authToken', res.data.token);
            localStorage.setItem('authDeviceName', 'web-browser');

            // Hard navigation to avoid SPA issues
            window.location.href = '/dashboard';
        }
    } catch (err) {
        console.error('Login error:', err);
        error.value = err.response?.data?.message || 'Login failed';
    } finally {
        processing.value = false;
    }
}
</script>

<template>
    <div class="min-h-screen flex flex-col justify-center items-center pt-6 sm:pt-0 bg-gray-100">
        <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white shadow-md overflow-hidden sm:rounded-lg">
            <h1 class="text-2xl font-bold mb-6 text-center">Log in</h1>

            <form @submit.prevent="submit">
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="email">
                        Email
                    </label>
                    <input
                        id="email"
                        v-model="form.email"
                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                        placeholder="Email"
                        required
                        type="email"
                    >
                </div>

                <div class="mb-6">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="password">
                        Password
                    </label>
                    <input
                        id="password"
                        v-model="form.password"
                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                        placeholder="Password"
                        required
                        type="password"
                    >
                </div>

                <div v-if="error" class="mb-4 text-red-500">
                    {{ error }}
                </div>

                <div class="flex items-center justify-between">
                    <button
                        :disabled="processing"
                        class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline"
                        type="submit"
                    >
                        {{ processing ? 'Logging in...' : 'Log In' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
