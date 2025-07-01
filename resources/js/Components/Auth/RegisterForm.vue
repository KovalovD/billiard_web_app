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
import {computed, onMounted, ref} from 'vue';
import {useLocale} from '@/composables/useLocale';

// Phone validation regex - matches formats like +1234567890, (123) 456-7890, 123-456-7890
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
});

// Client-side validation
const formValid = computed(() => {
    return (
        form.value.firstname.trim() !== '' &&
        form.value.lastname.trim() !== '' &&
        isEmailValid.value &&
        isPhoneValid.value &&
        isPasswordValid.value &&
        form.value.password === form.value.password_confirmation
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
    // Password must be at least 8 characters
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

            // Redirect to dashboard or another page
            window.location.href = '/dashboard';
        }
    } catch (error: any) {
        emit('error', error);
    }
};

onMounted(() => {
    // Any initialization if needed
});
</script>

<template>
    <Card>
        <CardHeader class="space-y-1">
            <CardTitle class="text-2xl font-bold">{{ t('Create an account') }}</CardTitle>
            <CardDescription>{{ t('Enter your information to create an account') }}</CardDescription>
        </CardHeader>
        <CardContent>
            <form class="space-y-4" @submit.prevent="register">
                <div v-if="registerService.error.value"
                     class="rounded-md bg-red-50 p-3 text-sm text-red-500 dark:bg-red-900/30 dark:text-red-400">
                    {{ registerService.error.value }}
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <Label for="firstname">{{ t('First Name') }}</Label>
                        <Input id="firstname" v-model="form.firstname" :disabled="registerService.isLoading.value"
                               required type="text"/>
                        <InputError :message="registerService.getError('firstname')"/>
                    </div>

                    <div class="space-y-2">
                        <Label for="lastname">{{ t('Last Name') }}</Label>
                        <Input id="lastname" v-model="form.lastname" :disabled="registerService.isLoading.value"
                               required type="text"/>
                        <InputError :message="registerService.getError('lastname')"/>
                    </div>
                </div>

                <div class="space-y-2">
                    <Label for="email">{{ t('Email') }}</Label>
                    <Input
                        id="email"
                        v-model="form.email"
                        :class="{ 'border-red-300 focus:border-red-300 focus:ring-red-300': !isEmailValid && form.email !== '' }"
                        :disabled="registerService.isLoading.value"
                        autocomplete="username"
                        required
                        type="email"
                    />
                    <InputError v-if="registerService.hasError('email')" :message="registerService.getError('email')"/>
                    <InputError v-else-if="!isEmailValid && form.email !== ''"
                                :message="t('Please enter a valid email address')"/>
                </div>

                <div class="space-y-2">
                    <Label for="phone">{{ t('Phone Number') }}</Label>
                    <Input
                        id="phone"
                        v-model="form.phone"
                        :class="{ 'border-red-300 focus:border-red-300 focus:ring-red-300': form.phone && !isPhoneValid }"
                        :disabled="registerService.isLoading.value"
                        :placeholder="t('e.g., (123) 456-7890')"
                        required
                        type="tel"
                    />
                    <InputError v-if="registerService.getError('phone')" :message="registerService.getError('phone')"/>
                    <InputError
                        v-else-if="form.phone && !isPhoneValid"
                        :message="t('Please enter a valid phone number format (e.g., +1234567890, 123-456-7890)')"
                    />
                </div>

                <div class="space-y-2">
                    <Label for="password">{{ t('Password') }}</Label>
                    <Input
                        id="password"
                        v-model="form.password"
                        :class="{ 'border-red-300 focus:border-red-300 focus:ring-red-300': !isPasswordValid && form.password !== '' }"
                        :disabled="registerService.isLoading.value"
                        autocomplete="new-password"
                        required
                        type="password"
                    />
                    <InputError v-if="registerService.hasError('password')"
                                :message="registerService.getError('password')"/>
                    <InputError v-else-if="!isPasswordValid && form.password !== ''"
                                :message="t('Password must be at least 8 characters')"/>
                </div>

                <div class="space-y-2">
                    <Label for="password_confirmation">{{ t('Confirm Password') }}</Label>
                    <Input
                        id="password_confirmation"
                        v-model="form.password_confirmation"
                        :class="{ 'border-red-300 focus:border-red-300 focus:ring-red-300': !passwordsMatch && form.password_confirmation !== '' }"
                        :disabled="registerService.isLoading.value"
                        autocomplete="new-password"
                        required
                        type="password"
                    />
                    <InputError
                        v-if="registerService.hasError('password_confirmation')"
                        :message="registerService.getError('password_confirmation')"
                    />
                    <InputError v-else-if="!passwordsMatch && form.password_confirmation !== ''"
                                :message="t('Passwords do not match')"/>
                </div>
            </form>
        </CardContent>
        <CardFooter class="flex justify-end space-x-4">
            <Button variant="outline" @click="$emit('cancel')">{{ t('Cancel') }}</Button>
            <Button :disabled="registerService.isLoading.value || !formValid" @click="register">
                <span v-if="registerService.isLoading.value">{{ t('Registering...') }}</span>
                <span v-else>{{ t('Register') }}</span>
            </Button>
        </CardFooter>
    </Card>
</template>
