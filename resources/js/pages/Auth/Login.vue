<script lang="ts" setup>
import InputError from '@/Components/ui/form/InputError.vue';
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
import {Head, Link, useForm} from '@inertiajs/vue3';
import {ref} from 'vue';
import {useLocale} from '@/composables/useLocale';
import AuthenticatedLayout from "@/layouts/AuthenticatedLayout.vue";
import {LockIcon, LogInIcon, MailIcon} from 'lucide-vue-next';

defineOptions({
    layout: AuthenticatedLayout,
});

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
    if (processing.value) return;

    processing.value = true;
    formError.value = '';

    try {
        await auth.login({
            email: form.email,
            password: form.password,
        });

        window.location.href = route('dashboard');
    } catch (err: any) {
        formError.value = err.message || t('Failed to login. Please check your credentials.');
        form.reset('password');
    } finally {
        processing.value = false;
    }
}

function handleKeydown(event: KeyboardEvent) {
    if (event.key === 'Enter' && !processing.value) {
        event.preventDefault();
        submit();
    }
}
</script>

<template>
    <Head :title="t('Log in')"/>

    <div
        class="min-h-screen flex flex-col items-center justify-center bg-gradient-to-br from-gray-50 to-blue-50 dark:from-gray-900 dark:to-gray-800 px-4 py-12">
        <div class="w-full max-w-md">
            <!-- Logo -->
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                    {{ t('Welcome back') }}
                </h1>
                <p class="mt-2 text-gray-600 dark:text-gray-400">
                    {{ t('Sign in to your account to continue') }}
                </p>
            </div>

            <Card class="shadow-xl border-0 bg-white/95 dark:bg-gray-800/95 backdrop-blur">
                <CardHeader class="space-y-1 pb-6">
                    <div
                        class="mx-auto w-16 h-16 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-full flex items-center justify-center mb-4">
                        <LogInIcon class="h-8 w-8 text-white"/>
                    </div>
                    <CardTitle class="text-2xl font-bold text-center">{{ t('Login') }}</CardTitle>
                    <CardDescription class="text-center">
                        {{ t('Enter your credentials to access your account') }}
                    </CardDescription>
                </CardHeader>

                <CardContent>
                    <form class="space-y-4" @keydown="handleKeydown" @submit.prevent="submit">
                        <div v-if="formError"
                             class="rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 p-4 text-sm text-red-600 dark:text-red-400">
                            {{ formError }}
                        </div>

                        <div class="space-y-2">
                            <Label for="email" class="text-sm font-medium">{{ t('Email') }}</Label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <MailIcon class="h-5 w-5 text-gray-400"/>
                                </div>
                                <Input
                                    id="email"
                                    v-model="form.email"
                                    :disabled="processing"
                                    autocomplete="username"
                                    required
                                    type="email"
                                    class="pl-10"
                                    :placeholder="t('you@example.com')"
                                />
                            </div>
                            <InputError :message="form.errors.email"/>
                        </div>

                        <div class="space-y-2">
                            <div class="flex items-center justify-between">
                                <Label for="password" class="text-sm font-medium">{{ t('Password') }}</Label>
                                <Link href="/forgot-password"
                                      class="text-sm text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 dark:hover:text-indigo-300 transition-colors">
                                    {{ t('Forgot password?') }}
                                </Link>
                            </div>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <LockIcon class="h-5 w-5 text-gray-400"/>
                                </div>
                                <Input
                                    id="password"
                                    v-model="form.password"
                                    :disabled="processing"
                                    autocomplete="current-password"
                                    required
                                    type="password"
                                    class="pl-10"
                                    :placeholder="t('Enter your password')"
                                />
                            </div>
                            <InputError :message="form.errors.password"/>
                        </div>

                        <div class="flex items-center space-x-2">
                            <input
                                id="remember_me"
                                v-model="form.remember"
                                class="w-4 h-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700"
                                type="checkbox"
                            />
                            <Label for="remember_me" class="text-sm font-normal cursor-pointer">
                                {{ t('Remember me') }}
                            </Label>
                        </div>

                        <button class="sr-only" tabindex="-1" type="submit">Submit</button>
                    </form>
                </CardContent>

                <CardFooter class="flex flex-col space-y-4 pt-6">
                    <Button
                        :disabled="processing"
                        class="w-full bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white shadow-lg"
                        size="lg"
                        type="button"
                        @click="submit"
                    >
                        <LogInIcon v-if="!processing" class="mr-2 h-5 w-5"/>
                        <span v-if="processing">{{ t('Signing in...') }}</span>
                        <span v-else>{{ t('Sign in') }}</span>
                    </Button>

                    <div class="relative w-full">
                        <div class="absolute inset-0 flex items-center">
                            <div class="w-full border-t border-gray-300 dark:border-gray-600"></div>
                        </div>
                        <div class="relative flex justify-center text-xs uppercase">
                            <span class="bg-white dark:bg-gray-800 px-2 text-gray-500">
                                {{ t('Or') }}
                            </span>
                        </div>
                    </div>

                    <p class="text-center text-sm text-gray-600 dark:text-gray-400">
                        {{ t("Don't have an account?") }}
                        <Link :href="route('register')"
                              class="font-semibold text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 dark:hover:text-indigo-300 transition-colors">
                            {{ t('Create an account') }}
                        </Link>
                    </p>
                </CardFooter>
            </Card>

            <!-- Additional links -->
            <div class="mt-8 text-center">
                <Link href="/"
                      class="text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200 transition-colors">
                    ← {{ t('Back to home') }}
                </Link>
            </div>
        </div>
    </div>
</template>
