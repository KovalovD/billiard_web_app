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
} from '@/Components/ui';
import {useRegister} from '@/composables/useRegister';
import type {RegisterCredentials} from '@/types/api';
import {computed, ref} from 'vue';
import {useLocale} from '@/composables/useLocale';
import {LockIcon, MailIcon, PhoneIcon, UserIcon, UserPlusIcon} from 'lucide-vue-next';
import {Link} from "@inertiajs/vue3";

const phonePattern = /^(\+?\d{1,3}[-\s]?)?\(?(\d{3})\)?[-\s]?(\d{3})[-\s]?(\d{4})$/;

const emit = defineEmits(['success', 'error', 'cancel']);

const registerService = useRegister();
const {t} = useLocale();
const form = ref<RegisterCredentials>({
    firstname: '',
    lastname: '',
    email: '',
    phone: '',
    password: '',
    password_confirmation: '',
    agreeToTerms: false
});

const formValid = computed(() => {
    return (
        form.value.firstname.trim() !== '' &&
        form.value.lastname.trim() !== '' &&
        isEmailValid.value &&
        isPhoneValid.value &&
        isPasswordValid.value &&
        form.value.password === form.value.password_confirmation &&
        form.value.agreeToTerms
    );
});

const isEmailValid = computed(() => {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return form.value.email.trim() !== '' && emailRegex.test(form.value.email);
});

const isPhoneValid = computed(() => {
    if (!form.value.phone.trim()) return false;
    return phonePattern.test(form.value.phone);
});

const isPasswordValid = computed(() => {
    return form.value.password.length >= 8;
});

const passwordsMatch = computed(() => {
    return form.value.password === form.value.password_confirmation;
});

const register = async () => {
    if (!formValid.value) {
        return;
    }

    try {
        const user = await registerService.register(form.value);

        if (user) {
            emit('success', user);
            window.location.href = '/dashboard';
        }
    } catch (error: any) {
        emit('error', error);
    }
};
</script>

<template>
    <Card class="shadow-xl border-0 bg-white/95 dark:bg-gray-800/95 backdrop-blur">
        <CardHeader class="space-y-1 pb-6">
            <div
                class="mx-auto w-16 h-16 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-full flex items-center justify-center mb-4">
                <UserPlusIcon class="h-8 w-8 text-white"/>
            </div>
            <CardTitle class="text-2xl font-bold text-center">{{ t('Create an account') }}</CardTitle>
            <CardDescription class="text-center">
                {{ t('Join WinnerBreak to start competing in leagues and tournaments') }}
            </CardDescription>
        </CardHeader>

        <CardContent>
            <form class="space-y-4" @submit.prevent="register">
                <div v-if="registerService.error.value"
                     class="rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 p-4 text-sm text-red-600 dark:text-red-400">
                    {{ registerService.error.value }}
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <Label for="firstname" class="text-sm font-medium">{{ t('First Name') }}</Label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <UserIcon class="h-5 w-5 text-gray-400"/>
                            </div>
                            <Input
                                id="firstname"
                                v-model="form.firstname"
                                :disabled="registerService.isLoading.value"
                                required
                                type="text"
                                class="pl-10"
                                :placeholder="t('John')"
                            />
                        </div>
                        <InputError :message="registerService.getError('firstname')"/>
                    </div>

                    <div class="space-y-2">
                        <Label for="lastname" class="text-sm font-medium">{{ t('Last Name') }}</Label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <UserIcon class="h-5 w-5 text-gray-400"/>
                            </div>
                            <Input
                                id="lastname"
                                v-model="form.lastname"
                                :disabled="registerService.isLoading.value"
                                required
                                type="text"
                                class="pl-10"
                                :placeholder="t('Doe')"
                            />
                        </div>
                        <InputError :message="registerService.getError('lastname')"/>
                    </div>
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
                            :class="{ 'border-red-300 focus:border-red-300 focus:ring-red-300': !isEmailValid && form.email !== '' }"
                            :disabled="registerService.isLoading.value"
                            autocomplete="username"
                            required
                            type="email"
                            class="pl-10"
                            :placeholder="t('you@example.com')"
                        />
                    </div>
                    <InputError v-if="registerService.hasError('email')" :message="registerService.getError('email')"/>
                    <InputError v-else-if="!isEmailValid && form.email !== ''"
                                :message="t('Please enter a valid email address')"/>
                </div>

                <div class="space-y-2">
                    <Label for="phone" class="text-sm font-medium">{{ t('Phone Number') }}</Label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <PhoneIcon class="h-5 w-5 text-gray-400"/>
                        </div>
                        <Input
                            id="phone"
                            v-model="form.phone"
                            :class="{ 'border-red-300 focus:border-red-300 focus:ring-red-300': form.phone && !isPhoneValid }"
                            :disabled="registerService.isLoading.value"
                            :placeholder="t('+1 (123) 456-7890')"
                            required
                            type="tel"
                            class="pl-10"
                        />
                    </div>
                    <InputError v-if="registerService.getError('phone')" :message="registerService.getError('phone')"/>
                    <InputError
                        v-else-if="form.phone && !isPhoneValid"
                        :message="t('Please enter a valid phone number')"
                    />
                </div>

                <div class="space-y-2">
                    <Label for="password" class="text-sm font-medium">{{ t('Password') }}</Label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <LockIcon class="h-5 w-5 text-gray-400"/>
                        </div>
                        <Input
                            id="password"
                            v-model="form.password"
                            :class="{ 'border-red-300 focus:border-red-300 focus:ring-red-300': !isPasswordValid && form.password !== '' }"
                            :disabled="registerService.isLoading.value"
                            autocomplete="new-password"
                            required
                            type="password"
                            class="pl-10"
                            :placeholder="t('Minimum 8 characters')"
                        />
                    </div>
                    <InputError v-if="registerService.hasError('password')"
                                :message="registerService.getError('password')"/>
                    <InputError v-else-if="!isPasswordValid && form.password !== ''"
                                :message="t('Password must be at least 8 characters')"/>
                </div>

                <div class="space-y-2">
                    <Label for="password_confirmation" class="text-sm font-medium">{{ t('Confirm Password') }}</Label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <LockIcon class="h-5 w-5 text-gray-400"/>
                        </div>
                        <Input
                            id="password_confirmation"
                            v-model="form.password_confirmation"
                            :class="{ 'border-red-300 focus:border-red-300 focus:ring-red-300': !passwordsMatch && form.password_confirmation !== '' }"
                            :disabled="registerService.isLoading.value"
                            autocomplete="new-password"
                            required
                            type="password"
                            class="pl-10"
                            :placeholder="t('Repeat your password')"
                        />
                    </div>
                    <InputError
                        v-if="registerService.hasError('password_confirmation')"
                        :message="registerService.getError('password_confirmation')"
                    />
                    <InputError v-else-if="!passwordsMatch && form.password_confirmation !== ''"
                                :message="t('Passwords do not match')"/>
                </div>
                <div class="space-y-2">
                    <div class="flex items-start space-x-3">
                        <input
                            id="agree_to_terms"
                            v-model="form.agreeToTerms"
                            type="checkbox"
                            class="mt-1 w-4 h-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700"
                            :disabled="registerService.isLoading.value"
                        />
                        <Label for="agree_to_terms"
                               class="text-sm font-normal text-gray-700 dark:text-gray-300 cursor-pointer">
                            {{ t('I agree to the') }}
                            <Link :href="route('agreement')"
                                  target="_blank"
                                  class="text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 dark:hover:text-indigo-300 underline">
                                {{ t('Terms of Service') }}
                            </Link>
                            {{ t('and') }}
                            <Link :href="route('privacy')"
                                  target="_blank"
                                  class="text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 dark:hover:text-indigo-300 underline">
                                {{ t('Privacy Policy') }}
                            </Link>
                        </Label>
                    </div>
                    <InputError v-if="!form.agreeToTerms && form.email !== ''"
                                :message="t('You must agree to the terms and conditions')"/>
                </div>
            </form>
        </CardContent>

        <CardFooter class="flex flex-col sm:flex-row gap-3 pt-6">
            <Button
                v-if="$attrs.onCancel"
                variant="outline"
                class="w-full sm:w-auto"
                @click="$emit('cancel')"
            >
                {{ t('Cancel') }}
            </Button>
            <Button
                :disabled="registerService.isLoading.value || !formValid"
                class="w-full sm:flex-1 bg-gradient-to-r from-indigo-600 to-indigo-600 hover:from-indigo-700 hover:to-indigo-700 text-white shadow-lg"
                size="lg"
                @click="register"
            >
                <UserPlusIcon v-if="!registerService.isLoading.value" class="mr-2 h-5 w-5"/>
                <span v-if="registerService.isLoading.value">{{ t('Creating account...') }}</span>
                <span v-else>{{ t('Create account') }}</span>
            </Button>
        </CardFooter>
    </Card>
</template>
