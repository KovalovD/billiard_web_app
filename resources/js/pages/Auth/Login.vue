<script lang="ts" setup>
import InputError from '@/Components/InputError.vue';
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
} from '@/Components/ui/index';
import {useAuth} from '@/composables/useAuth';
import {Head, useForm} from '@inertiajs/vue3';
import {ref} from 'vue';
import {useLocale} from '@/composables/useLocale';

const auth = useAuth();
const {t} = useLocale();
const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const processing = ref(false);
const formError = ref('');

async function submit() {
    // Prevent multiple submissions
    if (processing.value) return;

    processing.value = true;
    formError.value = '';

    try {
        await auth.login({
            email: form.email,
            password: form.password,
        });

        // Redirect to dashboard
        window.location.href = route('dashboard');
    } catch (err: any) {
        formError.value = err.message || t('Failed to login. Please check your credentials.');
        form.reset('password');
    } finally {
        processing.value = false;
    }
}

// Handle Enter key press
function handleKeydown(event: KeyboardEvent) {
    if (event.key === 'Enter' && !processing.value) {
        event.preventDefault();
        submit();
    }
}
</script>

<template>
    <Head :title="t('Log in')"/>

    <div class="flex min-h-screen flex-col items-center bg-gray-100 pt-6 sm:justify-center sm:pt-0 dark:bg-gray-900">
        <div class="mt-6 w-full px-6 py-4 sm:max-w-md">
            <Card>
                <CardHeader class="space-y-1">
                    <CardTitle class="text-2xl font-bold">{{ t('Login') }}</CardTitle>
                    <CardDescription>{{ t('Enter your credentials to access your account') }}</CardDescription>
                </CardHeader>
                <CardContent>
                    <form class="space-y-4" @keydown="handleKeydown" @submit.prevent="submit">
                        <div v-if="formError"
                             class="rounded-md bg-red-50 p-3 text-sm text-red-500 dark:bg-red-900/30 dark:text-red-400">
                            {{ formError }}
                        </div>

                        <div class="space-y-2">
                            <Label for="email">{{ t('Email') }}</Label>
                            <Input
                                id="email"
                                v-model="form.email"
                                :disabled="processing"
                                autocomplete="username"
                                required
                                type="email"
                            />
                            <InputError :message="form.errors.email"/>
                        </div>

                        <div class="space-y-2">
                            <div class="flex items-center justify-between">
                                <Label for="password">{{ t('Password') }}</Label>
                                <!--                                <a href="/forgot-password" class="text-sm text-blue-600 hover:underline dark:text-blue-400">
                                                                    Forgot password?
                                                                </a>-->
                            </div>
                            <Input
                                id="password"
                                v-model="form.password"
                                :disabled="processing"
                                autocomplete="current-password"
                                required
                                type="password"
                            />
                            <InputError :message="form.errors.password"/>
                        </div>

                        <div class="flex items-center space-x-2">
                            <input
                                id="remember_me"
                                v-model="form.remember"
                                class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:focus:ring-offset-gray-800"
                                type="checkbox"
                            />
                            <Label for="remember_me">{{ t('Remember me') }}</Label>
                        </div>

                        <!-- Hidden submit button to handle Enter key -->
                        <button class="sr-only" tabindex="-1" type="submit">Submit</button>
                    </form>
                </CardContent>
                <CardFooter class="flex flex-col space-y-4">
                    <Button :disabled="processing" class="w-full" type="button" @click="submit">
                        <span v-if="processing">{{ t('Logging in...') }}</span>
                        <span v-else>{{ t('Sign in') }}</span>
                    </Button>
                    <p class="text-center text-sm text-gray-600 dark:text-gray-400">
                        {{ t("Don't have an account?") }}
                        <a :href="route('register')"
                           class="font-semibold text-indigo-600 hover:underline dark:text-indigo-400">
                            {{ t('Register') }} </a>
                    </p>
                </CardFooter>
            </Card>
        </div>
    </div>
</template>
