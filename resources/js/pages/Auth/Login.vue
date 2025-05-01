<script lang="ts" setup>
import {ref} from 'vue';
import {Head, useForm} from '@inertiajs/vue3';
import {useAuth} from '@/composables/useAuth.ts';
import {
    Button,
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
    Input,
    Label
} from '@/Components/ui/index.ts';
import InputError from '@/Components/InputError.vue';

const auth = useAuth();
const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const processing = ref(false);
const formError = ref('');

async function submit() {
    processing.value = true;
    formError.value = '';

    try {
        await auth.login({
            email: form.email,
            password: form.password
        });

        // Redirect to dashboard
        window.location.href = route('dashboard');
    } catch (err) {
        formError.value = err.message || 'Failed to login. Please check your credentials.';
        form.reset('password');
    } finally {
        processing.value = false;
    }
}
</script>

<template>
    <Head title="Log in" />

    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-100 dark:bg-gray-900">
        <div class="w-full sm:max-w-md mt-6 px-6 py-4">
            <Card>
                <CardHeader class="space-y-1">
                    <CardTitle class="text-2xl font-bold">Login</CardTitle>
                    <CardDescription>Enter your credentials to access your account</CardDescription>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submit" class="space-y-4">
                        <div v-if="formError" class="p-3 rounded-md bg-red-50 text-red-500 text-sm dark:bg-red-900/30 dark:text-red-400">
                            {{ formError }}
                        </div>

                        <div class="space-y-2">
                            <Label for="email">Email</Label>
                            <Input
                                id="email"
                                v-model="form.email"
                                type="email"
                                autocomplete="username"
                                required
                                :disabled="processing"
                            />
                            <InputError :message="form.errors.email" />
                        </div>

                        <div class="space-y-2">
                            <div class="flex justify-between items-center">
                                <Label for="password">Password</Label>
                                <a href="/forgot-password" class="text-sm text-blue-600 hover:underline dark:text-blue-400">
                                    Forgot password?
                                </a>
                            </div>
                            <Input
                                id="password"
                                v-model="form.password"
                                type="password"
                                autocomplete="current-password"
                                required
                                :disabled="processing"
                            />
                            <InputError :message="form.errors.password" />
                        </div>

                        <div class="flex items-center space-x-2">
                            <input
                                id="remember_me"
                                v-model="form.remember"
                                type="checkbox"
                                class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:focus:ring-offset-gray-800"
                            />
                            <Label for="remember_me">Remember me</Label>
                        </div>
                    </form>
                </CardContent>
                <CardFooter class="flex flex-col space-y-4">
                    <Button type="submit" class="w-full" :disabled="processing" @click="submit">
                        <span v-if="processing">Logging in...</span>
                        <span v-else>Sign in</span>
                    </Button>
                    <p class="text-center text-sm text-gray-600 dark:text-gray-400">
                        Don't have an account?
                        <a href="/register" class="font-semibold text-indigo-600 hover:underline dark:text-indigo-400">
                            Register
                        </a>
                    </p>
                </CardFooter>
            </Card>
        </div>
    </div>
</template>
