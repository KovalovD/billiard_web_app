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
} from '@/Components/ui';
import {Head, useForm} from '@inertiajs/vue3';
import {ref} from 'vue';

const form = useForm({
    firstname: '',
    lastname: '',
    email: '',
    phone: '',
    password: '',
    password_confirmation: '',
});

const processing = ref(false);
const registrationError = ref('');

function submit() {
    processing.value = true;
    registrationError.value = '';

    form.post(route('register'), {
        onFinish: () => {
            processing.value = false;
        },
        onSuccess: () => {
            // Redirect is handled automatically by Inertia
        },
        onError: (errors) => {
            registrationError.value = Object.values(errors).flat().join(', ');
        },
    });
}
</script>

<template>
    <Head title="Register" />

    <div class="flex min-h-screen flex-col items-center bg-gray-100 pt-6 sm:justify-center sm:pt-0 dark:bg-gray-900">
        <div class="mt-6 w-full px-6 py-4 sm:max-w-md">
            <Card>
                <CardHeader class="space-y-1">
                    <CardTitle class="text-2xl font-bold">Create an account</CardTitle>
                    <CardDescription>Enter your information to create an account</CardDescription>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submit" class="space-y-4">
                        <div v-if="registrationError"
                             class="rounded-md bg-red-50 p-3 text-sm text-red-500 dark:bg-red-900/30 dark:text-red-400">
                            {{ registrationError }}
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <Label for="firstname">First Name</Label>
                                <Input id="firstname" v-model="form.firstname" :disabled="processing" required
                                       type="text"/>
                                <InputError :message="form.errors.firstname" />
                            </div>

                            <div class="space-y-2">
                                <Label for="lastname">Last Name</Label>
                                <Input id="lastname" v-model="form.lastname" :disabled="processing" required
                                       type="text"/>
                                <InputError :message="form.errors.lastname" />
                            </div>
                        </div>

                        <div class="space-y-2">
                            <Label for="email">Email</Label>
                            <Input id="email" v-model="form.email" :disabled="processing" autocomplete="username" required
                                   type="email"/>
                            <InputError :message="form.errors.email" />
                        </div>

                        <div class="space-y-2">
                            <Label for="phone">Phone Number</Label>
                            <Input id="phone" v-model="form.phone" :disabled="processing" required type="tel"/>
                            <InputError :message="form.errors.phone" />
                        </div>

                        <div class="space-y-2">
                            <Label for="password">Password</Label>
                            <Input
                                id="password"
                                v-model="form.password"
                                type="password"
                                autocomplete="new-password"
                                required
                                :disabled="processing"
                            />
                            <InputError :message="form.errors.password" />
                        </div>

                        <div class="space-y-2">
                            <Label for="password_confirmation">Confirm Password</Label>
                            <Input
                                id="password_confirmation"
                                v-model="form.password_confirmation"
                                type="password"
                                autocomplete="new-password"
                                required
                                :disabled="processing"
                            />
                        </div>
                    </form>
                </CardContent>
                <CardFooter class="flex flex-col space-y-4">
                    <Button class="w-full" :disabled="processing" @click="submit">
                        <span v-if="processing">Registering...</span>
                        <span v-else>Register</span>
                    </Button>
                    <p class="text-center text-sm text-gray-600 dark:text-gray-400">
                        Already have an account?
                        <a class="font-semibold text-indigo-600 hover:underline dark:text-indigo-400" href="/login">
                            Login </a>
                    </p>
                </CardFooter>
            </Card>
        </div>
    </div>
</template>
