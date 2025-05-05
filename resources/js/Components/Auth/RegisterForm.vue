<script lang="ts" setup>
import {computed, onMounted, ref} from 'vue';
import {useRegister} from '@/composables/useRegister';
import type {RegisterCredentials} from '@/types/api';
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
import InputError from '@/Components/InputError.vue';

const emit = defineEmits(['success', 'error', 'cancel']);

const registerService = useRegister();
const form = ref<RegisterCredentials>({
    firstname: '',
    lastname: '',
    email: '',
    phone: '',
    password: '',
    password_confirmation: ''
});

// Client-side validation
const formValid = computed(() => {
    return form.value.firstname.trim() !== '' &&
        form.value.lastname.trim() !== '' &&
        isEmailValid.value &&
        form.value.phone.trim() !== '' &&
        isPasswordValid.value &&
        form.value.password === form.value.password_confirmation;
});

const isEmailValid = computed(() => {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return form.value.email.trim() !== '' && emailRegex.test(form.value.email);
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
    console.log();
})

</script>

<template>
    <Card>
        <CardHeader class="space-y-1">
            <CardTitle class="text-2xl font-bold">Create an account</CardTitle>
            <CardDescription>Enter your information to create an account</CardDescription>
        </CardHeader>
        <CardContent>
            <form class="space-y-4" @submit.prevent="register">
                <div v-if="registerService.error.value"
                     class="p-3 rounded-md bg-red-50 text-red-500 text-sm dark:bg-red-900/30 dark:text-red-400">
                    {{ registerService.error.value }}
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <Label for="firstname">First Name</Label>
                        <Input
                            id="firstname"
                            v-model="form.firstname"
                            :disabled="registerService.isLoading.value"
                            required
                            type="text"
                        />
                        <InputError :message="registerService.getError('firstname')"/>
                    </div>

                    <div class="space-y-2">
                        <Label for="lastname">Last Name</Label>
                        <Input
                            id="lastname"
                            v-model="form.lastname"
                            :disabled="registerService.isLoading.value"
                            required
                            type="text"
                        />
                        <InputError :message="registerService.getError('lastname')"/>
                    </div>
                </div>

                <div class="space-y-2">
                    <Label for="email">Email</Label>
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
                                message="Please enter a valid email address"/>
                </div>

                <div class="space-y-2">
                    <Label for="phone">Phone Number</Label>
                    <Input
                        id="phone"
                        v-model="form.phone"
                        :disabled="registerService.isLoading.value"
                        required
                        type="tel"
                    />
                    <InputError :message="registerService.getError('phone')"/>
                </div>

                <div class="space-y-2">
                    <Label for="password">Password</Label>
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
                                message="Password must be at least 8 characters"/>
                </div>

                <div class="space-y-2">
                    <Label for="password_confirmation">Confirm Password</Label>
                    <Input
                        id="password_confirmation"
                        v-model="form.password_confirmation"
                        :class="{ 'border-red-300 focus:border-red-300 focus:ring-red-300': !passwordsMatch && form.password_confirmation !== '' }"
                        :disabled="registerService.isLoading.value"
                        autocomplete="new-password"
                        required
                        type="password"
                    />
                    <InputError v-if="registerService.hasError('password_confirmation')"
                                :message="registerService.getError('password_confirmation')"/>
                    <InputError v-else-if="!passwordsMatch && form.password_confirmation !== ''"
                                message="Passwords do not match"/>
                </div>
            </form>
        </CardContent>
        <CardFooter class="flex justify-end space-x-4">
            <Button variant="outline" @click="$emit('cancel')">
                Cancel
            </Button>
            <Button :disabled="registerService.isLoading.value || !formValid" @click="register">
                <span v-if="registerService.isLoading.value">Registering...</span>
                <span v-else>Register</span>
            </Button>
        </CardFooter>
    </Card>
</template>
